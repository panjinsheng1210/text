<?php
/**
 * Created by PhpStorm.
 * User: zhangchong
 * Date: 2019/6/14
 * Time: 14:20
 */

namespace app\b2c\controller;
use Request;
use app\common\model\BillPayments;
use app\common\model\RechargeLog;
use app\common\controller\Base;
class Recharge extends Base
{
    /*
     * 手机充值回调接口
     * @return string
     */
    public function notify(){
        if(!input('?param.downstreamSerialno')){
            return error_code(18001);
        }
        if(input('param.status')==2){
            //充值成功,把支付表的状态变回3
            $serialno = input('param.downstreamSerialno');
            //取出充值单号对应的支付单号
            $payment = (new RechargeLog())->where(array("serialno"=>$serialno))->find();
            $payment_id = isset($payment['payment_id'])?$payment['payment_id']:0;
            if($payment_id){
                $billPayments = new BillPayments();
                $billPayments->where(array("payment_id"=>$payment_id))->data(array("status"=>4))->update();
            }
        }
        echo 'success';
    }
}