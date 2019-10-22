<?php
/**
 * Created by PhpStorm.
 * User: youtuo-e
 * Date: 2019/5/6
 * Time: 14:55
 */

namespace app\common\model;



use think\Db;
use think\Exception;

class Buy extends Common
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
        $list = $this::with('UserInfo')->field($tableWhere['field'])->where($tableWhere['where'])->order($tableWhere['order'])->paginate($limit);
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
        if(isset($post['status']) && $post['status'] != "" ){
            $where[] = ['status','eq',$post['status']];
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
            if($v['status']){
                $list[$k]['status'] = config('params.business')['status'][$v['status']];
            }
        }
        return $list;
    }

    public function UserInfo()
    {
        return $this->hasOne('User','id','user_id')->bind([
            'mobile'
        ]);
    }

    /**
     * 删除记录
     * @param int $goods_id
     * @return array
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function delBuy($id = 0)
    {
        $result = [
            'status' => false,
            'data' => [],
            'msg' => '记录不存在'
        ];
        $buy = $this::get($id);
        if (!$buy) {
            return $result;
        }

        $this->startTrans();

        $res = $this->where(['id'=>$id])->delete();

        $tax         = config('params.business')['tax'];
        $money       = $buy->remain_currency+$buy->remain_currency * $tax;
        $memo        = '卖出撤回增加积分'.$money;
        // 添加积分明细(转入方)
        $UserPointLog = new UserPointLog();
        $addResult    = $UserPointLog->setPoint($buy->user_id, $money, 15, $memo)['status'];
        if (!$res || !$addResult) {
            $this->rollback();
            $result['msg'] = '删除失败';
            return $result;
        }
        $this->commit();
        $result['status'] = true;
        $result['msg'] = '删除成功';
        return $result;
    }

    public function addBuy($user_id,$number){
        $result = [
            'status' => false,
            'msg'    => '卖出失败',
            'data'   => ''
        ];
        Db::startTrans();
        $userInfo = User::get($user_id);
        if(!$userInfo){
            return error_code(11004);
        }
        $tax       = config('params.business')['tax'];
        if(($number+$number*$tax) > $userInfo->point){
            return error_code(17004);
        }


        $userPointLogModel = new UserPointLog();
        $remarks      = "卖出{$number}积分,手续费:".$number*$tax;
        $userUpdate   = $userPointLogModel->setPoint($user_id, -($number+$number*$tax), 15, $remarks);

        $price      = (new SalableRecord())->where(['type'=>4])->order(['utime'=>'desc'])->select()[0]['money'];
        $buyData    = [];
        $buyData['user_id'] = $user_id;
        $buyData['currency']= $number;
        $buyData['money']   = $number*$price;
        $buyData['tax']     = $number*$tax;
        $buyData['price']   = $price;
        $buyData['ctime']   = time();
        $buyModel = new Buy();
        $buyModel->save($buyData);
        $buy_id = $buyModel->id;
        if($buy_id && $userUpdate){
            $saleModel = new Sale();
            $saleInfo  = $saleModel->where(['price'=>$price,'status'=>[1,2]])->where('user_id','neq',$user_id)->order(['ctime'=>'asc'])->select();
            try{
                if(isset($saleInfo) && !empty($saleInfo)){
                    foreach ($saleInfo as $key=>$val){
                        if($number == 0){
                            break;
                        }
                        if($val['remain_currency']>=$number){
                            Sale::where(['id'=>$val['id']])->update([
                                'remain_currency'=>$val['remain_currency']-$number,
                                'status'          =>($val['remain_currency']-$number) == 0?3:2,
                                'remain_money'   =>($val['remain_currency']-$number)*$val['price']
                            ]);
                            // 添加余额明细(卖出方)
                            $buyBlanceModel = new Balance();
                            $buyBlanceModel->change($user_id,15,$number*$price,0,0,'卖出'.$number.'积分,单价:'.$price.';获得'.$number*$price.'元')['status'];
                            // 添加积分明细(买入方)
                            $saleUserPointLog = new UserPointLog();
                            $saleUserPointLog->setPoint($val['user_id'], $number, 15, '买入'.$number.'积分')['status'];

                            // 添加匹配记录
                            $dealData = [
                                'sale_id'      => $val['id'],
                                'buy_id'       => $buy_id,
                                'currency'     => $number,
                                'sale_user_id' => $val['user_id'],
                                'buy_user_id'  => $user_id,
                                'ctime'        => time(),
                                'price'        =>$price,
                            ];
                            (new Deal())->save($dealData);
                            $number = 0;
                        }elseif ($val['remain_currency']<$number){
                            Sale::where(['id'=>$val['id']])->update([
                                'remain_currency'=>0,
                                'status'          =>3,
                                'remain_money'   =>0
                            ]);
                            // 添加余额明细(卖出方)
                            $buyBlanceModel = new Balance();
                            $buyBlanceModel->change($user_id,15,$val['remain_currency']*$price,0,0,'卖出'.$val['remain_currency'].'积分,单价:'.$price.';获得'.$val['remain_currency']*$price.'元')['status'];
                            // 添加积分明细(买入方)
                            $saleUserPointLog = new UserPointLog();
                            $saleUserPointLog->setPoint($val['user_id'], $val['remain_currency'], 15, '买入'.$val['remain_currency'].'积分')['status'];
                            // 添加匹配记录
                            $dealData = [
                                'sale_id'      => $val['id'],
                                'buy_id'       => $buy_id,
                                'currency'     => $val['remain_currency'],
                                'sale_user_id' => $val['user_id'],
                                'buy_user_id'  => $user_id,
                                'ctime'        => time(),
                                'price'        =>$price,
                            ];
                            (new Deal())->save($dealData);
                            $number = $number - $val['remain_currency'];
                        }
                    }
                }
                // 添加卖出记录
                $buyUpdate['remain_currency'] = $number;
                $buyUpdate['remain_money']    = $number*$price;
                $buyUpdate['status']           = $number==0 ? 3:($number==$buyData['currency']?1:2);
                (new Buy())->where(['id'=>$buy_id])->update($buyUpdate);
                $result['status'] = true;
                $result['msg']    = '卖出成功';
                Db::commit();
            }catch (Exception $e){
                Db::rollback();
            }
        }else{
            Db::rollback();
        }
        return $result;
    }


    /**
     *
     *  获取卖出列表
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
    public function userBuyList($user_id, $page, $limit,$status='')
    {
        $result = [
            'status' => true,
            'msg' => '获取成功',
            'data' => []
        ];

        $where = [];
        if(isset($status) && $status != "" ){
            $where[] = ['status','eq',$status];
        }
        if ( isset($user_id) && $user_id != "" ) {
            $where[] = [ 'user_id', 'eq', $user_id ];
        }
//        $list = $this->where($where)->order('ctime desc')->page($page, $limit)->select();
        $list = $this->where($where)->order('ctime desc')->select();
        $count = $this->where($where)->count();
        if (!$list->isEmpty()) {
            $result[ 'data' ] = $this->tableFormat($list);
            $result[ 'total' ] = ceil($count/$limit);
        }
        return $result;
    }



}