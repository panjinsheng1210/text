<?php
//余额支付
namespace org\payments;
use app\common\model\BillPayments;
use app\common\model\BillRefund;
use app\common\model\Order;
use app\common\model\ShopOtayonii;
use app\common\model\User;
use think\Db;

class coupon implements Payment
{
    private $config = [];

    function __construct($config)
    {
        $this->config = $config;
    }

    //购物卷支付，减去用户购物卷
    public function pay($paymentInfo,$order_id =0)
    {
        $result = [
            'status' => false,
            'data' => [],
            'msg' => ''
        ];
        $shopOtayonii = new ShopOtayonii();
        $res = $shopOtayonii->setShopOtayonii($paymentInfo['user_id'], -$paymentInfo['money'],13,5, "购物消费".$paymentInfo['money'].'张购物卷');
        if(!$res['status']){
            $result['msg'] = $res['msg'];
            return $result;
        }

        //改变支付单状态
        $billPaymentModel = new BillPayments();
        $billPaymentInfo = $billPaymentModel->where(['payment_id' => $paymentInfo['payment_id']])->find();
        if(!$billPaymentInfo){
            return error_code(10056,true);
        }
        $resultBillPayment = $billPaymentModel->toUpdate( $paymentInfo['payment_id'],$billPaymentModel::STATUS_PAYED,'coupon',$paymentInfo['money']);
        if($order_id){
            $addPrize  = (new Order())->settlementBonus($paymentInfo['money'],$paymentInfo['user_id'],$order_id);
        }

        if($resultBillPayment['status']){
            $result['msg'] = $resultBillPayment['msg'];
            $result['status'] = true;
            $result['data'] = $paymentInfo;
            return $result;
        }else{
            $result['msg'] = $resultBillPayment['msg'];
            return $result;
        }
    }

    public function callback()
    {

    }

    //用户余额退款
    public function refund($refundInfo, $paymentInfo)
    {
        $result  = [
            'status' => false,
            'data'   => [],
            'msg'    => ''
        ];
        if(!$refundInfo['money'] || $refundInfo['money'] == 0){
            $result['status'] = true;
            $result['msg']    = '退款成功';
            return $result;
        }
        Db::startTrans();
        $balance = new \app\common\model\Balance();
        $res     = $balance->change($paymentInfo['user_id'], $balance::TYPE_REFUND, $refundInfo['money'], $paymentInfo['payment_id']);
        $order = Db::table(config('database.prefix').'bill_refund')
                ->alias('br')
                ->leftJoin(config('database.prefix').'bill_aftersales ba','br.aftersales_id = ba.aftersales_id')
                ->leftJoin(config('database.prefix').'order o','ba.order_id = o.order_id')
                ->where(['br.refund_id'=>$refundInfo['refund_id']])
                ->field('o.shop_id')
                ->find();
        $shopResult = true;
        if($order){
            $shopResult = (new ShopOtayonii())->setShop($order['shop_id'],-$refundInfo['money'],2,3,"订单退款");
        }

        if (!$res['status'] || !$shopResult['status']) {
            Db::rollback();
            $result['msg'] = $shopResult['msg'];
            return $result;
        }
        Db::commit();
        $result['status'] = true;
        $result['msg']    = '退款成功';
        return $result;
    }
}
