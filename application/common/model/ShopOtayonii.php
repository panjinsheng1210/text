<?php
/**
 * Created by PhpStorm.
 * User: youtuo-e
 * Date: 2019/5/6
 * Time: 14:55
 */

namespace app\common\model;



use think\Db;

class ShopOtayonii extends Common
{

    protected $pk = 'id';

    //时间自动存储
    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';
    /**
     * 通用查询列表方法
     * @param $post
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function tableData($post)
    {
        if(isset($post['limit'])){
            $limit = $post['limit'];
        }else{
            $limit = config('paginate.list_rows');
        }

        $tableWhere = $this->tableWhere($post);
        $list = $this::with('userInfo,shopInfo')->field($tableWhere['field'])->where($tableWhere['where'])->order($tableWhere['order'])->paginate($limit);
        $data = $this->tableFormat($list->getCollection()); //返回的数据格式化，并渲染成table所需要的最终的显示数据类型

        $re['code'] = 0;
        $re['msg'] = '';
        $re['count'] = $list->total();
        $re['data'] = $data;

        return $re;
    }

    /**
     * 根据输入的查询条件，返回所需要的where
     * @author sin
     * @param $post
     * @return mixed
     */
    protected function tableWhere($post)
    {
        $where = [];
        if(isset($post['type']) && $post['type'] != "" ){
            $where[] = ['type','eq',$post['type']];
        }
        if ( isset($post[ 'user_id' ]) && $post[ 'user_id' ] != "" ) {
            $where[] = [ 'user_id', 'eq', $post[ 'user_id' ] ];
        } else {
            if ( isset($post[ 'mobile' ]) && $post[ 'mobile' ] != "" ) {
                if ( $user_id = get_user_id($post[ 'mobile' ]) ) {
                    $where[] = [ 'user_id', 'eq', $user_id ];
                } else {
                    $where[] = [ 'user_id', 'eq', '99999999' ];       //如果没有此用户，那么就赋值个数值，让他查不出数据
                }
            }
        }
        if(isset($post['finance_id']) && $post['finance_id'] != ""){
            $where[] = ['finance_id','eq',$post['finance_id']];
        }
        if(isset($post['shop_id']) && $post['shop_id'] != ""){
            $where[] = ['shop_id','eq',$post['shop_id']];
        }

        $result['where'] = $where;
        $result['field'] = "*";
        $result['order'] = ['id'=>'desc'];
        return $result;
    }

    /**
     * 根据查询结果，格式化数据
     * @param $list //array格式的collection
     * @return mixed
     */
    protected function tableFormat($list)
    {
        foreach($list as $k => $v)
        {
            if($v['ctime'])
            {
                $list[$k]['ctime'] = getTime($v['ctime']);
            }
            if($v['finance_id'])
            {
                $list[$k]['finance_id']  = config('params.financeType')[$v['finance_id']];
            }
            if($v['type']){
                $list[$k]['type'] = config('params.shopOtayonii')['type'][$v['type']];
            }
        }
        return $list;
    }



    /**
     * 关联用户信息
     * @return \think\model\relation\HasOne
     */
    public function userInfo()
    {
        return $this->hasOne('User', 'id', 'user_id')->bind([
            'mobile',
            'nickname'
        ]);
    }

    /**
     * 关联店铺信息
     * @return \think\model\relation\HasOne
     */
    public function shopInfo()
    {
        return $this->hasOne('Shop', 'id', 'shop_id')->bind([
            'name'
        ]);
    }


    /**
     * 删除记录
     * @param int $goods_id
     * @return array
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function delShopOtayonii($id = 0)
    {
        $result = [
            'status' => false,
            'data' => [],
            'msg' => '记录不存在'
        ];
        $shopOtayonii = $this::get($id);
        if (!$shopOtayonii) {
            return $result;
        }

        $this->startTrans();

        $res = $this->where(['id'=>$id])->delete();
        if (!$res) {
            $this->rollback();
            $result['msg'] = '删除失败';
            return $result;
        }
        $this->commit();
        $result['status'] = true;
        $result['msg'] = '删除成功';
        return $result;
    }

    /**
     * 金豆/购物卷 设置并添加记录
     * @param $user_id
     * @param $num
     * @param int $type
     * @param string $remarks
     * @param string $finance_id
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function setShopOtayonii($user_id, $num, $type = 10,$finance_id = 2, $remarks = '')
    {
        $return = [
            'status' => false,
            'msg' => ''
        ];
        if($num != 0)
        {
            //获取积分账号信息
            $user_model   = new User();
            $user_info    = $user_model->where(['id'=>$user_id])->find();
            $financeField = config('params.financeField');
            $field = $financeField[$finance_id];
            $user_info->$field = $user_info->$field + $num;
            //积分余额判断
            if($user_info->$field < 0)
            {
                $financeType = config('params.financeType');
                $return['msg'] = $financeType[$finance_id].'不足';
                return $return;
            }
            //插入记录
            $data = [
                'user_id' => $user_id,
                'type' => $type,
                'num' => $num,
                'balance' => $user_info->$field,
                'remarks' => $remarks,
                'finance_id'=>$finance_id,
                'ctime' => time()
            ];
            $this->insert($data);
            //插入主表
            $user_info->save();
        }
        $return['status'] = true;
        $return['msg'] = '更改成功';

        return $return;
    }



    /**
     * 店铺账户 设置并添加记录
     * @param $user_id
     * @param $num
     * @param int $type
     * @param string $remarks
     * @param string $finance_id
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function setShop($shop_id, $num, $type = 10,$finance_id = 3, $remarks = '')
    {
        $return = [
            'status' => false,
            'msg' => ''
        ];
        if($num != 0)
        {
            //获取积分账号信息
            $shop_info    = Shop::get($shop_id);
            $shop_info->money = $shop_info->money + $num;
            //积分余额判断
            if($shop_info->money < 0)
            {
                $return['msg'] = '余额不足';
                return $return;
            }
            //插入记录
            $data = [
                'user_id' => $shop_info->user_id,
                'type' => $type,
                'num' => $num,
                'balance' => $shop_info->money,
                'remarks' => $remarks,
                'finance_id'=>$finance_id,
                'shop_id'=>$shop_id,
                'ctime' => time()
            ];
            $this->insert($data);
            //插入主表
            $shop_info->save();
        }
        $return['status'] = true;
        $return['msg'] = '更改成功';

        return $return;
    }

    /**
     *
     *  获取用户铺账户/金豆/购物卷明细列表
     * @param $user_id
     * @param $page
     * @param $limit
     * @param string $type  类型
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userFinanceRecordList($user_id, $page, $limit, $type='',$finance_id,$shop_id=0)
    {
        $result = [
            'status' => true,
            'msg' => '获取成功',
            'data' => []
        ];

        $where = [];
        if(isset($type) && $type != "" ){
            $where[] = ['type','eq',$type];
        }
        if ( isset($user_id) && $user_id != "" ) {
            $where[] = [ 'user_id', 'eq', $user_id ];
        }
        if(isset($finance_id) && $finance_id != ""){
            $where[] = ['finance_id','eq',$finance_id];
        }
        if(isset($shop_id) && $shop_id != ""){
            $where[] = ['shop_id','eq',$shop_id];
        }
        $list = $this->where($where)->order('ctime desc')->page($page, $limit)->select();
        $count = $this->where($where)->count();
        if (!$list->isEmpty()) {
            $result[ 'data' ] = $this->tableFormat($list);
            $result[ 'total' ] = ceil($count/$limit);
        }
        return $result;
    }

}