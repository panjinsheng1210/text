<?php
namespace app\api\controller;
use app\common\controller\Api;
use app\common\model\Shop as ShopModel;
use app\common\model\ShopImages;
use app\common\model\User;
use app\common\model\UserTocash;
use app\common\model\BillPayments;
use think\Db;
use think\facade\Request;

/**
 * Class Store
 * @package app\api\controller
 */
class Shop extends Api
{

    /**
     * 获取店铺列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getShopList()
    {
        $model = new ShopModel();
        $key = Request::param('key', '');
        $longitude = Request::param('longitude', false);
        $latitude = Request::param('latitude', false);
        return $model->getAllShopList($key, $longitude, $latitude);
    }

    /**
     * 添加或申请店铺
     * @return array|mixed
     */
    public function add()
    {
        $storeModel = new ShopModel();
        $data=input('param.');
        $data['user_id']=$this->userId;
        return $storeModel->addData($data);
    }
    /*
     * 查看店铺信息
     */
    public function shopInfo(){
        $result = [
            'status' => false,
            'data' => [],
            'msg' => ''
        ];
        $asModel = new ShopModel();
        $info = $asModel->getInfo($this->userId);
        if(!$info['status']){
            return $info;
        }
        $result['data']['info'] = $info['data'];
        $result['status'] = true;
        return $result;
    }
    /**
     *根据用户商家的经纬度，获取附近的商家
     */
    public function myNearShop(){
        $result = [
            'status' => true,
            'msg' => '获取成功',
            'data' => []
        ];
        $slat = input('param.lat');
        $slng = input('param.lng');
        $name = input('param.name');
//        $slat = 34.46;
//        $slng = 113.40;
        if (!$slat || !$slng) return error_code(17007);
//        $sql =  "SELECT *, ROUND(6378.138*2*ASIN(SQRT(POW(SIN(($slat*PI()/180-lat*PI()/180)/2),2)+COS($slat*PI()/180)*COS(lat*PI()/180)*POW(SIN(($slng*PI()/180-lng*PI()/180)/2),2)))) AS juli
//    FROM jshop_shop  HAVING juli <= 1000";
        $sql = "SELECT
                    *,(
                    6371 * acos (
                        cos ( radians($slat) )
                        * cos( radians( lat ) )
                        * cos( radians( lng ) - radians($slng) )
                        + sin ( radians($slat) )
                        * sin( radians( lat ) )
                    )
                ) AS distance
                FROM jshop_shop
                WHERE name LIKE '%$name%'
                and coordinate is not null
                ORDER BY distance
                ";
        $data = Db::query($sql);
        if(isset($data) && !empty($data)){
            foreach ($data as $key=>$val){
                $imageLogo = ShopImages::where(['shop_id'=>$val['id'],'is_default'=>1])->find();
                $data[$key]['logo'] = _sImage($imageLogo['image_id']);
            }
            $result['data'] = $data;
        }
        return $result;
    }

    /**
     * 获取各店铺商品二级分类
     */
    public function getShopCat(){
        $shopId = Request::param('shop_id', '');
        $goodsModel = new \app\common\model\Goods();
        return $goodsModel->getShopCat($shopId);
    }

    public function upload(){
        $this->uploadImage();
    }

    /*
 * uploadImage
 * 上传图片
 */
    function uploadImage()
    {
        $filetypes = [
            'image' => [
                'title'      => 'Image files',
                'extensions' => 'jpg,jpeg,png,gif,bmp'
            ],
        ];

        $image_extensions = explode(',','jpg,jpeg,png,gif,bmp');
        if(Request::isPost()) {
            $all_allowed_exts = array();
            foreach($filetypes as $mfiletype) {
                array_push($all_allowed_exts,$mfiletype['extensions']);
            }

            $all_allowed_exts = implode(',',$all_allowed_exts);
            $all_allowed_exts = explode(',',$all_allowed_exts);
            $all_allowed_exts = array_unique($all_allowed_exts);
            $upload_max_filesize = config('jshop.upload_filesize');
            $upload_max_filesize = empty($upload_max_filesize) ? 5242880 : $upload_max_filesize;//默认5M

            if(isset($_FILES['upfile']))
            {
                $file_extension = get_file_extension($_FILES['upfile']['name']);
                $savepath =  '/static/uploads/images' . get_hash_dir($_FILES['upfile']['name']);
            }
            else
            {
                $file_extension = get_file_extension($_FILES['file']['name']);
                $savepath =  '/static/uploads/images' . get_hash_dir($_FILES['file']['name']);
            }

            //上传处理类
            $config = array(
                'rootPath' => ROOT_PATH . DIRECTORY_SEPARATOR . 'public',
                'savePath' => $savepath,
                'maxSize'  => $upload_max_filesize,
                'saveName' => array(
                    'uniqid',
                    ''
                ),
                'exts'     => $all_allowed_exts,
                'autoSub'  => false,
            );

            $image_storage = config('jshop.image_storage');
            if (!$image_storage) {
                $image_storage = [
                    'type' => 'Local',
                ];
            }
            //增加后台设置，如果设置则用后台设置的
            if (getSetting('image_storage_params')) {
                $image_storage = array_merge(['type' => getSetting('image_storage_type')], getSetting('image_storage_params'));
            }
            $upload = new \org\Upload($config,$image_storage['type'],$image_storage);
            $info = $upload->upload();

            if($info) {
                $first         = array_shift($info);
                $url           = getRealUrl($savepath . $first['savename']);
                $preview_url   = $url;
                $iData['id']   = md5(get_hash($first['name']));
                $iData['type'] = $image_storage['type'];
                $iData['name'] = $first['name'];
                $iData['url']  = $url;
                $iData['ctime']  = time();
                $iData['path'] = ROOT_PATH .DIRECTORY_SEPARATOR.'public'.$savepath . $first['savename'];
                $image_model   = new \app\common\model\Images();
                if($image_model->save($iData)) {

                    if(isset($_FILES['upfile'])){
                        $callback = input('callback','');
                        $editInfo = [
                            'originalName' => $iData['name'],
                            'name' => $first['savename'],
                            'url' => $url,
                            'size' => $first['size'],
                            'type' => $iData['type'],
                            'state' => 'SUCCESS',
                            'image_id' => $iData['id'],
                        ];

                        if($callback) {
                            echo '<script>'.$callback.'('.json_encode($editInfo).')</script>';exit;
                        } else {
                            echo json_encode($editInfo);exit;
                        }
                    }else{
                        $data = [
                            'url'        => $preview_url,
                            'image_id'   => $iData['id'],
                            'image_name' => $iData['name'],
                        ];
                        $response = [
                            'data'   => $data,
                            'status' => true,
                            'msg'    => $upload->getError()
                        ];
                        echo json_encode($response);exit;

                    }
                }else {
                    $response =  [
                        'data'   => '',
                        'status' => false,
                        'msg'    => "保存失败"
                    ];
                    echo json_encode($response);exit;
                }
            }else {

                $response = [
                    'data'   => '',
                    'status' => false,
                    'msg'    => $upload->getError()
                ];
                echo json_encode($response);exit;

            }
        }
    }
    //返回指定的店铺的logo和名字
    public function getAssignInfo(){
        $result = [
            'status' => false,
            'data' => [],
            'msg' => ''
        ];
        $shopModel = new ShopModel();
        $shop_id = input('param.shop_id');
        if(!$shop_id){
            $result['msg'] = '请输入店铺id';
            return $result;
        }
        $result['data'] = $shopModel->getAssignInfo($shop_id);
        $result['status'] = true;
        return $result;
    }
    // 店铺提现
    public function shopTocash(){
        $password2 = input('param.password2');
        $userInfo  = User::get($this->userId);
        if(!password_verify($password2,$userInfo->password2)) return error_code(17011);
        $money       = input('param.money');
        $bankcard_id = input('param.cardId');
        $shopId      = input('param.shopId');
        $remarks     = input('param.remarks');
        if (!$money) return error_code(11018);
        if (!$bankcard_id) return error_code(11017);
        if (!$shopId) return error_code(17012);
        $userTocash = new UserTocash();
        return $userTocash->addTocash($this->userId, $money,$bankcard_id,$shopId,$remarks);
    }

    // 店铺提现记录
    public function shopTocashRecord()
    {
        $page = input('param.page', 1);
        $limit = input('param.limit', config('jshop.page_limit'));
        $type = input('param.type', '');
        $shopId = input('param.shopId');
        if (!$shopId) return error_code(17012);
        $userToCashModel = new UserTocash();
        return $userToCashModel->userToCashList($this->userId, $page, $limit, $type, 2, $shopId);
    }
    //店铺线下支付日志
    public function offlinePayments(){
        $page = input('param.page', 1);
        $limit = input('param.limit', config('jshop.page_limit'));
        $shop_id = input('param.shop_id');
        if(!$shop_id){
            return "请输入商铺id";
        }
        $billPayments  = new BillPayments();
        return $billPayments->offlinePaymentsLog($shop_id,$page,$limit);
    }







}
