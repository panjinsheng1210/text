<?php
namespace app\common\model;
use org\Curl;
use think\Db;
use think\Validate;


class UserTransfer extends Common
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';
    protected $updateTime = 'utime';



    /*
     * 转账申请
     */
    public function transfer($user_id,$money,$financeType,$dstUser,$remark)
    {
        $result = [
            'status' => false,
            'msg' => '',
            'data' => ''
        ];
//        //最低转账金额
//        if($money < getSetting('tocash_money_low')){
//            $result['msg'] = "提现最低不能少于".getSetting('tocash_money_low')."元";
//            return $result;
//        }

        $userModel = new User();
        $userInfo = $userModel->getUserInfo($user_id);
        if(!$userInfo){
            return error_code(11004);
        }

        $dstUserModel = new User();
        $dstUserInfo  = $dstUserModel->getUserInfo($dstUser);
        if(!$dstUserInfo){
            return error_code(11004);
        }
        $financeField = config('params.financeField')[$financeType];

        if($money > $userInfo[$financeField]){
            return error_code(11015);
        }

        $cateMoney = 0;
        // 计算提现服务费(金额)
        if($financeType != 5 ){
            $cateMoney = $this->cateMoney($money);
        }
        if (($money + $cateMoney) > $userInfo[$financeField]) {
            return error_code(11015);
        }

        $data['src_user_id']      = $user_id;
        $data['src_finance_type'] = $financeType;
        $data['dst_user_id']       = $dstUser;
        $data['dst_finance_type']  = $financeType;
        $data['money']              = $money;
        $data['tax']                = $cateMoney;
        $data['remark']             = $remark;
        $data['ctime']              = time();
        Db::startTrans();
        $re = $this->save($data);
        $dstDescribe = '收到'.$userInfo->mobile.'转账'.$money;
        $srcDescribe = '转账给'.$dstUserInfo->mobile.'会员'.$money.',手续费:'.$cateMoney;
        if($financeType == 1){
            // 添加余额明细(转入方)
            $dstBlanceModel = new Balance();
            $dstResult      = $dstBlanceModel->change($dstUser,12,$money,$re,0,$dstDescribe)['status'];
            // 添加余额明细(转出方)
            $srcBlanceModel = new Balance();
            $srcResult      = $srcBlanceModel->change($user_id,12,-($money+$cateMoney),$re,0,$srcDescribe)['status'];
        }elseif ($financeType == 2){
            // 添加金豆明细(转入方)
            $dstShopOtayonii = new ShopOtayonii();
            $dstResult       = $dstShopOtayonii->setShopOtayonii($dstUser,$money,12,$financeType,$dstDescribe)['status'];
            // 添加金豆明细(转出方)
            $srcShopOtayonii = new ShopOtayonii();
            $srcResult       = $srcShopOtayonii->setShopOtayonii($user_id,-($money+$cateMoney),12,$financeType,$srcDescribe)['status'];
        }elseif ($financeType == 3){
            // 添加店铺账户明细(转入方)
            $dstShopOtayonii = new ShopOtayonii();
            $dstResult       = $dstShopOtayonii->setShopOtayonii($dstUser,$money,12,$financeType,$dstDescribe)['status'];
            // 添加店铺账户明细(转出方)
            $srcShopOtayonii = new ShopOtayonii();
            $srcResult       = $srcShopOtayonii->setShopOtayonii($user_id,-($money+$cateMoney),12,$financeType,$srcDescribe)['status'];
        }elseif ($financeType == 4){
            // 添加积分明细(转入方)
            $dstUserPointLog = new UserPointLog();
            $dstResult       = $dstUserPointLog->setPoint($dstUser, $money, 12, $dstDescribe)['status'];
            // 添加积分明细(转出方)
            $srcUserPointLog = new UserPointLog();
            $srcResult       = $srcUserPointLog->setPoint($user_id, -($money+$cateMoney), 12, $dstDescribe)['status'];
        }elseif($financeType == 5){
            // 添加购物卷明细(转入方)
            $dstShopOtayonii = new ShopOtayonii();
            $dstResult       = $dstShopOtayonii->setShopOtayonii($dstUser,$money,12,5,$remark)['status'];
            // 添加购物卷明细(转出方)
            $srcShopOtayonii = new ShopOtayonii();
            $srcResult       = $srcShopOtayonii->setShopOtayonii($user_id,-($money+$cateMoney),12,5,$remark)['status'];
        }

//        $dstUserModel = new User();
//        $dstUserWhere[] = ['id', 'eq', $dstUser];
//        // 转入账户添加金额
//        $dstResult = $dstUserModel->where($dstUserWhere)->inc($financeField,$money)->update();
//        $srcUserModel = new User();
//        $srcUserWhere[] = ['id','eq',$user_id];
//        // 转出账户减少金额+转账手续费
//        $srcResult = $srcUserModel->where($srcUserWhere)->dec($financeField,$money + $cateMoney)->update();

        if($re && $dstResult && $srcResult){
            Db::commit();
            $result = [
                'status' => true,
                'msg'    => '转账成功',
                'data'   => ''
            ];
            return $result;
        }else{
            Db::rollback();
            $result['msg'] = "转账失败";
            return $result;
        }
    }



    /**
     *
     *  转账服务费(金额)
     * @param $transferMoney
     * @return float|int
     */
    protected function cateMoney ($transferMoney,$type=1)
    {

        $cate = $type ==1 ? getSetting('transfer_money_rate'):getSetting('exchange_money_rate');

        $cateMoney = $transferMoney * ($cate / 100);

        return $cateMoney;
    }

    /**
     * 返回layui的table所需要的格式
     *
     * @author sin
     *
     * @param $post
     *
     * @return mixed
     */
    public function tableData( $post )
    {
        if ( isset($post[ 'limit' ]) ) {
            $limit = $post[ 'limit' ];
        } else {
            $limit = config('paginate.list_rows');
        }
        $tableWhere = $this->tableWhere($post);
        $list = $this::with('dstUserInfo,srcUserInfo')->field($tableWhere[ 'field' ])->where($tableWhere[ 'where' ])->order($tableWhere[ 'order' ])->paginate($limit);
        $data = $this->tableFormat($list->getCollection());         //返回的数据格式化，并渲染成table所需要的最终的显示数据类型

        $re[ 'code' ] = 0;
        $re[ 'msg' ]  = '';
        $re[ 'count' ] = $list->total();
        $re[ 'data' ]  = $data;
        $re[ 'sql' ]   = $this->getLastSql();

        return $re;
    }

    protected function tableWhere( $post )
    {
        $where = [];

        if ( isset($post[ 'src_finance_type' ]) && $post[ 'src_finance_type' ] != "" ) {
            $where[] = [ 'src_finance_type', 'eq', $post[ 'src_finance_type' ] ];
        }
        if ( isset($post[ 'type' ]) && $post[ 'type' ] != "" ) {
            $where[] = [ 'type', 'eq', $post[ 'type' ] ];
        }
        if ( isset($post[ 'src_user_id' ]) && $post[ 'src_user_id' ] != "" ) {
            $where[] = [ 'src_user_id', 'eq', $post[ 'src_user_id' ] ];
        } else {
            if ( isset($post[ 'mobile' ]) && $post[ 'mobile' ] != "" ) {
                if ( $user_id = get_user_id($post[ 'mobile' ]) ) {
                    $where[] = [ 'src_user_id', 'eq', $user_id ];
                } else {
                    $where[] = [ 'src_user_id', 'eq', '99999999' ];       //如果没有此用户，那么就赋值个数值，让他查不出数据
                }
            }
        }

        $result[ 'where' ] = $where;
        $result[ 'field' ] = "*";
        $result[ 'order' ] = 'ctime desc';
        return $result;
    }

    /**
     * 根据查询结果，格式化数据
     *
     * @author sin
     *
     * @param $list  array格式的collection
     *
     * @return mixed
     */
    protected function tableFormat( $list )
    {

        foreach ( $list as $k => $v ) {
            if ( $v[ 'ctime' ] ) {
                $list[ $k ][ 'ctime' ] = getTime($v[ 'ctime' ]);
            }
            if ( $v[ 'src_finance_type' ] ) {
                $list[ $k ][ 'src_finance_type' ] = config('params.financeType')[ $v[ 'src_finance_type' ] ];
            }
            if ( $v[ 'dst_finance_type' ] ) {
                $list[ $k ][ 'dst_finance_type' ] = config('params.financeType')[ $v[ 'dst_finance_type' ] ];
            }
        }
        return $list;
    }


    /**
     *
     *  获取用户提现记录列表
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
    public function userTransferList($user_id, $page, $type='')
    {
        $result = [
            'status' => true,
            'msg' => '获取成功',
            'data' => []
        ];

        $where = [];
        if (isset($type) && !empty($type)) {
            $where[] = ['type', 'eq', $type];
        }
//        $where[] = ['src_user_id', 'eq', $user_id];
        $list = $this->with('dstUserInfo,srcUserInfo')->where($where)->order('ctime desc')->page($page)->select();
        $count = $this->where($where)->count();

        if (!$list->isEmpty()) {
            foreach ($list as $v) {
                $v['type'] = config('params.financeType')[$v['src_finance_type']];
                $v['ctime'] = getTime($v['ctime']);
            }
            $result[ 'data' ]  = $list;
            $result[ 'total' ] = ceil($count);
            $result[ 'count' ] = $count;
        }
        return $result;
    }

    public function dstUserInfo()
    {
        return $this->hasOne('User','id','dst_user_id')->bind([
            'dst_mobile'=>'mobile'
        ]);
    }
    public function srcUserInfo()
    {
        return $this->hasOne('User','id','src_user_id')->bind([
            'src_mobile'=>'mobile'
        ]);
    }

    /**
     * 删除转账记录
     * @param int $goods_id
     * @return array
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function delTransfer($id = 0)
    {
        $result = [
            'status' => false,
            'data' => [],
            'msg' => '记录不存在'
        ];
        $transfer = $this::get($id);
        if (!$transfer) {
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

    /*
    * 兑换申请
    */
    public function exchange($user_id,$number,$remark)
    {
        $result = [
            'status' => false,
            'msg' => '',
            'data' => ''
        ];
//        //最低转账金额
//        if($money < getSetting('tocash_money_low')){
//            $result['msg'] = "提现最低不能少于".getSetting('tocash_money_low')."元";
//            return $result;
//        }

        $userModel = new User();
        $userInfo = $userModel->getUserInfo($user_id);
        if(!$userInfo){
            return error_code(11004);
        }

        // 计算提现服务费(金额)
        $cateMoney = $this->cateMoney($number,2);
        if (($number + $cateMoney) > $userInfo['point']) {
            return error_code(17004);
        }

        $data['src_user_id']      = $user_id;
        $data['src_finance_type'] = 4;
        $data['dst_user_id']       = $user_id;
        $data['dst_finance_type']  = 5;
        $data['money']              = $number;
        $data['tax']                = $cateMoney;
        $data['remark']             = $remark;
        $data['type']               = 2; // 兑换
        $data['ctime']              = time();
        Db::startTrans();
        $re = $this->save($data);
        $remark = '成功兑换购物卷'.$number.'张';
        // 添加积分明细
        $srcUserPointLog = new UserPointLog();
        $srcResult       = $srcUserPointLog->setPoint($user_id, -($number+$cateMoney), 14, $remark)['status'];
        // 添加购物卷明细
        $dstShopOtayonii = new ShopOtayonii();
        $dstResult       = $dstShopOtayonii->setShopOtayonii($user_id,$number,14,5,$remark)['status'];

        if($re && $dstResult && $srcResult){
            Db::commit();
            $result = [
                'status' => true,
                'msg'    => '兑换成功',
                'data'   => ''
            ];
            return $result;
        }else{
            Db::rollback();
            $result['msg'] = "兑换失败";
            return $result;
        }
    }

}