<?php
namespace app\common\model;
use think\Validate;

/**
 * Class Shop
 * @package app\common\model
 */
class Shop extends Common
{
    const TypeList=[0=>'个人店铺',1=>'企业店铺'];
    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';
    protected $updateTime = false;

    protected $rule =   [
        'name'    =>  'require|max:50',
        'mobile'        =>  'mobile|unique:Shop',
        'user_id'=>'userExist:\\app\\common\\model\\User',
        'linkman'       =>  'require|max:25',
        //'address'       =>  'require',
        //'coordinate'    =>  'require'
    ];

    protected $msg  =   [
        'name.require'    =>  '请输入门店名称',
        'name.max'        =>  '门店名称不超过50个字符',
        'linkman.max' => '联系人不能超过25个字符',
        'mobile.mobile'          => '手机号格式错误',
        //'logo.require'          =>  '请上传门店logo',
        //'address.require'       =>  '请输入门店详细地址',
        //'coordinate.require'    =>  '请选择门店坐标位置'
    ];
    public $skeys = [
        'name' => [
            'name' => '店铺名称',
            'value' => '我的店铺'
        ],
        'info' => [
            'name' => '店铺描述',
            'value' => '店铺描述会展示在前台及微信分享店铺描述'
        ],
        'address' => [
            'name' => '店铺地址',
            'value' => '我的店铺地址'
        ],
        'logo' => [
            'name' => '店铺logo',
            'value' => '',
        ],
        'coordinate'=>[
            'name' => '店铺坐标',
            'value'=>'',
        ]
    ];
    /**
     * @param $post
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function tableData($post)
    {
        if(isset($post['limit']))
        {
            $limit = $post['limit'];
        }
        else
        {
            $limit = config('paginate.list_rows');
        }

        $tableWhere = $this->tableWhere($post);
        $list = $this->field($tableWhere['field'])
            ->where($tableWhere['where'])
            ->order($tableWhere['order'])
            ->paginate($limit);
        $data = $this->tableFormat($list->getCollection());
        $re['code'] = 0;
        $re['msg'] = '';
        $re['count'] = $list->total();
        $re['data'] = $data;
        return $re;
    }


    /**
     * 店铺添加
     * @param array $data
     * @return array
     */
    public function addData($data = [])
    {
        $result = ['status' => true, 'msg' => '保存成功','data' => ''];
        //查看有没有申请过
        $where['user_id'] = $data['user_id'];
        $info = $this->where($where)->find();
        if($info){
            return error_code(11056);
        }
        if(isset($data['lat']) && !empty($data['lat']) && isset($data['lng']) && !empty($data['lng'])){
            $data['coordinate'] = $data['lat'].','.$data['lng'];
        }else{
            return error_code(17013);
        }
        $validate = new \app\common\validate\Validate($this->rule,$this->msg);
        //$validate->extend('userExist',[$this,'userExistValidator']);
        if(!$validate->check($data))
        {
            $result['status'] = false;
            $result['msg'] = $validate->getError();
        }
        else
        {
            $data['password']=$this->enPassword($data['password']);
            $updateUser = User::update(['isshop'=>2],['id'=>$data['user_id']]);
            if(!$this->allowField(true)->save($data) || !$updateUser)
            {
                $result['status'] = false;
                $result['msg'] = '保存失败';
            }else{
                //保存售后图片
                if ($data['images']) {
                    //$data['images'] = json_decode($data['images'],true);
                    foreach ($data['images'] as $key=>$v) {
                        $is_default = 0;
                        if($key==0){
                            $is_default = 1;
                        }
                        $rel_img['shop_id'] = $this->id;
                        $rel_img['image_id']      = $v;

                        $rel_img['is_default'] = $is_default;
                        $rel_arr[]                = $rel_img;
                    }
                    $shopImagesModel = new ShopImages();
                    $shopImagesModel->saveAll($rel_arr);
                }
            }
        }
        return $result;
    }


    /**
     * 门店修改
     * @param array $data
     * @return array
     */
    public function editData($data = [],$images = [])
    {
        $result = [
            'status' => true,
            'msg' => '保存成功',
            'data' => ''
        ];
        $validate = new Validate($this->rule,$this->msg);
        if(!$validate->check($data))
        {
            $result['status'] = false;
            $result['msg'] = $validate->getError();
        }
        else
        {
            if($data['coordinate']){
                $info = explode(',',$data['coordinate']);
                $data['lat'] = $info[0];
                $data['lng'] = $info[1];
            }
            if(!$this->allowField(true)->save($data,['id' => $data['id']]))
            {
                $result['status'] = false;
                $result['msg'] = '保存失败';
            }else{
                //更改shop_images表里面的数据
                if($images){
                    //先删除在保存
                    $shopImagesModel = new ShopImages();
                    $shopImagesModel->where("shop_id=$data[id]")->delete();
                    foreach ($images as $key=>$v) {
                        $is_default = 0;
                        if($key==0){
                            $is_default = 1;
                        }
                        $rel_img['shop_id'] = $data['id'];
                        $rel_img['image_id']      = $v;

                        $rel_img['is_default'] = $is_default;
                        $rel_arr[]                = $rel_img;
                    }
                    $shopImagesModel->saveAll($rel_arr);
                }
            }
        }
        return $result;
    }


    /**
     * 根据查询结果，格式化数据
     * @param $list
     * @return mixed
     */
    protected function tableFormat($list)
    {
        foreach( $list as $val )
        {
            $shopImages = new ShopImages();
            $val['logo'] = $shopImages->returnLogoPath($val['id'],1);
            //$val['logo'] = _sImage($val['logo']);
            //$val['area'] = get_area($val['area_id']);
            $val['ctime'] = getTime($val['ctime']);
            $val['vtime'] = getTime($val['vtime']);
        }
        return $list;
    }


    /**
     * 获取商户门店
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function shopList()
    {
        $data = $this
            ->field('name,mobile,linkman,logo,address,coordinate')
            ->select();

        if(!$data->isEmpty())
        {
            $count = $this
                ->field('name,mobile,linkman,logo,info,address,coordinate')
                ->count();

            $result = [
                'status'=> true,
                'msg'   => '获取成功',
                'data'  => [
                    'list' => $data,
                    'count' => $count
                ]
            ];
        }
        else
        {
            $result = [
                'status'=> false,
                'msg'   => '获取失败',
                'data'  => ''
            ];
        }
        return $result;
    }


    /**
     * 获取店铺名称
     * @param $id
     * @return mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getShopName($id)
    {
        $where[] = ['id', 'eq', $id];
        $result = $this->field('name')->where($where)->find();
        return $result['name']?$result['name']:'';
    }


    /**
     * 判断店铺是否存在
     * @param $id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function shopExist($id)
    {
        $where[] = ['id', 'eq', $id];
        $result = $this->where($where)->find();
        return $result?true:false;
    }


    /**
     * 获取全部店铺
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAllList()
    {
        return $this->field('id,name')->select();
    }


    /**
     * 获取默认店铺
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getDefaultStore()
    {
        $return = [
            'status' => false,
            'msg' => '获取失败',
            'data' => []
        ];
        $return['data'] = $this->order('ctime desc')->find();
        if($return['data'])
        {
            $return['status'] = true;
            $return['msg'] = '获取成功';
        }
        return $return;
    }


    /**
     * 获取全部店铺列表
     * @param string $key
     * @param bool $longitude
     * @param bool $latitude
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAllShopList($key = '', $longitude = false, $latitude = false)
    {
        $return = [
            'status' => false,
            'msg' => '获取失败',
            'data' => [],
            'longitude' => $longitude,
            'latitude' => $latitude
        ];

        $where = [];
        if($key)
        {
            $where[] = ['name', 'like', '%'.$key.'%'];

        }

        $return['data'] = $this->where($where)->select();
        if($return['data'])
        {
            $return['status'] = true;
            $return['msg'] = '获取成功';
        }
        return $return;
    }
    /**
     * 店铺登陆
     * @param array $data 用户登陆信息
     *
     */
    public function toLogin($data)
    {
        $result = array(
            'status' => false,
            'data' => '',
            'msg' => ''
        );
        if(!isset($data['mobile']) || !isset($data['password'])) {
            $result['msg'] = '请输入手机号码或者密码';
            return $result;
        }

        //校验验证码
        if(session('?manage_login_fail_num')){
            if(session('manage_login_fail_num') >= config('jshop.manage_login_fail_num')){
                if(!isset($data['captcha']) || $data['captcha'] == ''){
                    return error_code(10013);
                }
                if(!captcha_check($data['captcha'])){
                    return error_code(10012);
                };
            }
        }

        $userInfo = $this->where(array('mobile'=>$data['mobile']))->find();
        if(!$userInfo){
            $result['msg'] = '没有找到此账号';
            return $result;
        }

        //判断账号状态
        if($userInfo->is_verify != 1) {
            $result['msg'] = '此店铺没有激活';
            return $result;
        }

        //判断用户名密码是否正确
        $userInfo = $this->where(array('mobile'=>$data['mobile']))->find();
        if($userInfo && password_verify($data['password'],$userInfo->password)){
            $result = $this->setSession($userInfo);
        }else{
            //写失败次数到session里
            if(session('?manage_login_fail_num')){
                session('manage_login_fail_num',session('manage_login_fail_num')+1);
            }else{
                session('manage_login_fail_num',1);
            }
            $result['msg'] = '密码错误，请重试';
        }
        return $result;
    }
    public function verify()
    {
        $result = [
            'status' => true,
            'msg' => '审核成功',
            'data' => ''
        ];
        if($this->is_verify===1){
            $result['status'] = false;
            $result['msg'] = '不要重复审核';
            return $result;
        }
        $this->is_verify=1;
        if(!$this->save()){
            $result['status'] = false;
            $result['msg'] = '保存失败';
            return $result;
        }

        return $result;
    }

    /**
     * 管理员修改密码
     * @param $manage_id
     * @param $oldPassword
     * @param $newPassword
     * @return array|string
     */
    public function chengePwd($shop_id,$oldPassword,$newPassword)
    {
        $result = [
            'status' => false,
            'data' => '',
            'msg' => ''
        ];
        $info = $this->where(['id' => $shop_id])->find();
        if(!$info){
            $result['msg'] = "没有找到此账号";
            return $result;
        }
        if($oldPassword  == $newPassword){
            $result['msg'] = "新密码和旧密码一致";
            return $result;
        }

        //if($info['password'] != $this->enPassword($oldPassword)){
         if(password_verify($this->enPassword($oldPassword),$info['password'])){
            $result['msg'] = "原密码不对";
            return $result;
        }

        $re = $this->save(['password'=>$this->enPassword($newPassword)], ['id'=> $info['id']]);
        if($re){
            $result['status'] = true;
            $result['msg'] = "修改成功";
        }else{
            return $result['msg'] = "更新失败";
        }
        return $result;


    }

    private function setSession($userInfo)
    {
        $result = [
            'status' => false,
            'data' => '',
            'msg' => ''
        ];
        session('shop',$userInfo->toArray());

/*        $userLogModel = new UserLog();//添加登录日志
        $userLogModel->setLog($userInfo->id,$userLogModel::USER_LOGIN);*/

        $result['status'] = true;
        return $result;
    }
    /**
     * 密码加密方法
     * @param string $pw 要加密的字符串
     * @return string
     */
    private function enPassword($password)
    {
        return password_hash($password,PASSWORD_DEFAULT);
    }
    //设置参数
    public function setValue($skey, $value)
    {
        $info[$skey] = $value;
        if($info['coordinate']){
            $data = explode(',',$info['coordinate']);
            $info['lat'] = $data[0];
            $info['lng'] = $data[1];
        }
        $shop_id = session("shop.id");
        $this->save($info,['id'=>$shop_id]);
        $result['status'] = true;
        $result['msg'] = $skey."===".$value;
        return $result;
    }
    //取得全部参数
    public function getAll()
    {
        $list = $this->where(array('id' => session("shop.id")))->find();
        if($list['name']) $this->skeys['name']['value'] = $list['name'];
        if($list['info']) $this->skeys['info']['value'] = $list['info'];
        if($list['logo']) $this->skeys['logo']['value'] = $list['logo'];
        if($list['address']) $this->skeys['address']['value'] = $list['address'];
        if($list['coordinate']) $this->skeys['coordinate']['value'] = $list['coordinate'];
        return $this->skeys;
    }
    /**
     * 获取店铺信息
     * @param $aftersales_id
     * @param $user_id
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getInfo($user_id)
    {
        $result = [
            'status' => false,
            'data' => [],
            'msg' => ''
        ];
        $where['user_id'] = $user_id;
        $info = $this->where($where)->find();
        if(!$info){
            return $result;
        }
        $shopImagesModel = new ShopImages();
        $Images = $shopImagesModel->field('image_id')->where("shop_id=$info[id]")->select();
        foreach($Images as $k => $v){
            $data[] = _sImage($v['image_id']);
        }
        $info['image'] = $data;
        $result['status'] = true;
        $result['data'] = $info;

        return $result;
    }
    /**
     * 返回指定店铺id的名字和头像
     */
     public function getAssignInfo($shop_id){
         $info = $this->field('name')->where("id=$shop_id")->find();
         $shopImagesModel = new ShopImages();
         $info['logo'] = $shopImagesModel->returnLogoPath($shop_id,1);
         return $info;
     }
    /**
     * 获取全部店铺
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCertificationList()
    {
        return $this->field('id,name')->where("is_verify=1")->select();
    }
    /*
     * 后台返回指定的店铺信息
     */
    public function returnShopInfo($shop_id)
    {
        $info = $this->where("id=$shop_id")->find();
        $shopImagesModel = new ShopImages();
        $images = $shopImagesModel->getAllImages($shop_id);
        if($images['data']){
            foreach ((array)$images['data'] as $key => $val) {
                if (isset($val['image_id'])) {
                    $images['data'][$key]['image_path'] = _sImage($val['image_id']);
                }
                $info['images'] = $images['data'];
            }
        }else{
            $logo = _sImage(getSetting('shop_default_image'));
            $array[]['imagepath'] = $logo;
            $info['images'] = $array;
        }
        $result['data']   = $info;
        $result['msg']    = '查询成功';
        $result['status'] = true;
        return $result;
    }

}