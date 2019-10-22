<?php
// +----------------------------------------------------------------------
// | JSHOP [ 小程序 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017~2018 http://jihainet.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: mark <jima@jihainet.com>
// +----------------------------------------------------------------------
/**
 * b2c模块的各种回调方法都写到这里
 */
namespace app\b2c\controller;

use app\common\model\BillPayments;
use app\common\model\BillPaymentsRel;
use app\common\model\OperationLog;
use app\common\model\Order;
use app\common\model\Payments;
use app\common\model\Recharge;
use Request;
use app\common\model\User;
use app\common\controller\Base;


class Callback extends Base
{
    public function pay()
    {
        $result = [
            'status' => false,
            'data' => [],
            'msg' => ''
        ];
        if(!input('?param.code')){
            return error_code(10051,true);
        }
        //判断支付方式合法性
        $paymentsModel = new Payments();
        $paymentInfo = $paymentsModel->getPayment(input('param.code'));
        if(!$paymentInfo){
            return error_code(10057);
        }

        $billPaymentModel = new BillPayments();

        //取此支付方式的配置信息，然后去支付,
        $conf = json_decode($paymentInfo['params'],true);

        //校验合法性
        $payment = \org\Payment::create(input('param.code'),$conf);
        $result = $payment->callback();
//        if($result['status']){
//            //到这里就说明校验成功了，去更新支付单
//            $resultBillPayment = $billPaymentModel->toUpdate($result['data']['payment_id'],$result['data']['status'],input('param.code'),$result['data']['money'],$result['data']['payed_msg'],$result['data']['trade_no']);
//            $order_id = BillPaymentsRel::where(['payment_id'=>$result['data']['payment_id']])->find();
//            $isOrder  = Order::where(['order_id'=>$order_id])->find();
//            $user_id  = BillPayments::get($result['data']['payment_id']);
//            if($isOrder){
//                $addPrize  = (new Order())->settlementBonus($result['data']['money'],$user_id['user_id'],$order_id['source_id']);
//            }else{
//                if($user_id['type']==4){
//                    $result1 = (new Recharge())->recharge($paymentInfo);
//                    if(!$result1['status']){
//                        $billPaymentModel->where(['payment_id' => $result['data']['payment_id']])->data(array("status"=>3))->update();
//                    }
//                }if($user_id['type']==3){
//                    $addPrize  = (new Order())->offlineBonus($result['data']['money'],$user_id['user_id'],$user_id['shop_id']);
//                }
//            }
//            if($resultBillPayment['status']){
//                return $result['msg'];
//            }else{
//                return $resultBillPayment['msg'];
//            }
//        }else{
//            return $result['msg'];
//        }
        if($result['status']){
            //到这里就说明校验成功了，去更新支付单
            $resultBillPayment = $billPaymentModel->toUpdate($result['data']['payment_id'],$result['data']['status'],input('param.code'),$result['data']['money'],$result['data']['payed_msg'],$result['data']['trade_no']);
            if($resultBillPayment['status']){
                return $result['msg'];
            }else{
                return $resultBillPayment['msg'];
            }
        }else{
            return $result['msg'];
        }
    }
}