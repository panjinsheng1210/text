<?php
/**
 * Created by PhpStorm.
 * User: youtuo-e
 * Date: 2019/5/6
 * Time: 14:55
 */

namespace app\common\model;



use think\Db;

class Ranking extends Common
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
        $list = $this::with('userInfo')->field($tableWhere['field'])->where($tableWhere['where'])->order($tableWhere['order'])->paginate($limit);
//        $list = $this::with('userInfo')->field($tableWhere['field'])->where($tableWhere['where'])->order($tableWhere['order'])->fetchSql();
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
        if(isset($post['is_send']) && $post['is_send'] != "" ){
            $where[] = ['is_send','eq',$post['is_send']];
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
        if(isset($post['period']) && $post['period'] != ""){
            $where[] = ['period','eq',$post['period']];
        }
        if(isset($post['level']) && $post['level'] != ""){
            $where[] = ['level','eq',$post['level']];
        }

        $result['where'] = $where;
        $result['field'] = "*";
        $result['order'] = ['period'=>'desc', 'money'=>'desc'];
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
            if ($v['stime']) {
                $list[$k]['stime'] = getTime($v['stime']);
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
     * 后台拨款/扣款
     * @param $data
     * @return array
     */
    public function manageAdd($data)
    {
        $return = [
            'status' => false,
            'msg'    => '失败',
            'data'   => ''
        ];
        $status = true;

        if (!isset($data['mobile']) || $data['mobile'] == '') {
            $return['msg'] = '手机号必填';
            return $return;
        }
        if (!isMobile($data['mobile'])) {
            $return['msg'] = '请输入正确的手机号';
            return $return;
        }
        if(!get_user_id($data['mobile'])){
            $return['msg'] = '用户不存在';
            return $return;
        }
        if (!isset($data['finance_id']) || $data['finance_id'] == '') {
            $return['msg'] = '账户类型必填';
            return $return;
        }
        if (!isset($data['currency']) || $data['currency'] == 0) {
            $return['msg'] = '拨款金额不能为空';
            return $return;
        }
        Db::startTrans();
        // 添加拨款记录
        $time                   = time();
        $newData['user_id']    = get_user_id($data['mobile']);
        $newData['currency']   = $data['currency'];
        $newData['finance_id'] = $data['finance_id'];
        $newData['type']        = $data['currency'] >0 ? 1:2;
        $newData['ctime']       = $time;
        $result         = $this->save($newData);
        $return['data'] = $this->id;
        // 更改账户
        $userModel = User::get($newData['user_id']);
        if($newData['finance_id'] == 1){
            // 添加余额明细
            $blanceModel = new Balance();
            $status = $blanceModel->change($newData['user_id'],$data['currency'] >0 ? 8:9,$data['currency'],$this->id,0)['status'];
        }elseif ($newData['finance_id'] == 2){
            $shopOtayonii = new ShopOtayonii();
            $remarks      = $data['currency'] >0?'公司拨款'.$data['currency'].'金豆':'公司扣款'.$data['currency'].'金豆';
            $status = $shopOtayonii->setShopOtayonii($newData['user_id'],$data['currency'],$data['currency'] >0 ? 8:9,$newData['finance_id'],$remarks)['status'];
        }elseif ($newData['finance_id'] == 3){
            $shopOtayonii = new ShopOtayonii();
            $remarks      = $data['currency'] >0?'公司拨款'.$data['currency'].'店铺账户':'公司扣款'.$data['currency'].'店铺账户';
            $status = $shopOtayonii->setShopOtayonii($newData['user_id'],$data['currency'],$data['currency'] >0 ? 8:9,$newData['finance_id'],$remarks)['status'];
        }elseif ($newData['finance_id'] == 4){
            $userPointLog = new UserPointLog();
            $remarks      = $data['currency'] >0?'公司拨款'.$data['currency'].'积分':'公司扣款'.$data['currency'].'积分';
            // 插入积分呢记录
            $status = $userPointLog->setPoint($newData['user_id'], $data['currency'], $data['currency'] >0 ? 8:9, $remarks)['status'];
        }elseif ($newData['finance_id'] == 5){
            $shopOtayonii = new ShopOtayonii();
            $remarks      = $data['currency'] >0?'公司拨款'.$data['currency'].'购物卷':'公司扣款'.$data['currency'].'购物卷';
            $status = $shopOtayonii->setShopOtayonii($newData['user_id'],$data['currency'],$data['currency'] >0 ? 8:9,$newData['finance_id'],$remarks)['status'];
        }



        if ($result && $status) {
            Db::commit();
//            if (session('manage.id')) {
//                $userLogModel = new UserLog();
//                $userLogModel->setLog(session('manage.id'), $userLogModel::USER_REG);
//            }
            $return['status'] = true;
            $return['msg']    = $data['currency'] >0?'拨款成功':'扣款成功';

        }else{
            Db::rollback();
        }
        return $return;
    }

    /**
     * 删除记录
     * @param int $goods_id
     * @return array
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function delRanking($id = 0)
    {
        $result = [
            'status' => false,
            'data' => [],
            'msg' => '记录不存在'
        ];
        $appropriat = $this::get($id);
        if (!$appropriat) {
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

    /**获取排行榜接口数据
     * @param $user_id
     * @param $page
     * @param $limit
     * @param string $status
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function rankingList($limit,$level,$order = null)
    {
        $result = [
            'status' => true,
            'msg' => '获取成功',
            'data' => []
        ];

        $where = [];
        if(isset($level) && $level != "" ){
            $where[] = ['level','eq',$level];
        }
//        var_dump($order);exit;
        if ($order == null) {
            $order = 'money desc';
        }

        $where[] = ['is_send','eq',0];
//        $list = $this->where($where)->order('ctime desc')->page($page, $limit)->select();
        $list = $this::with('userInfo')->where($where)->order($order)->select();
        $count = $this->where($where)->count();
        if (!$list->isEmpty()) {
            $result[ 'data' ] = $this->tableFormat($list);
            $result[ 'total' ] = ceil($count/$limit);
        }
        return $result;
    }

}