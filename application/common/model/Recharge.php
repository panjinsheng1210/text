<?php
/**
 * Created by PhpStorm.
 * User: zhangchong
 * Date: 2019/6/14
 * Time: 10:11
 */

namespace app\common\model;
use think\Exception;
use think\model\concern\SoftDelete;
use think\Db;
use org\Curl;

class Recharge extends Common
{
    /**
     * 余额支付成功后开始充值
     * @param array $paymentInfo 用户支付信息
     *
     */
    public function recharge($paymentInfo){
        $result = array(
            'status' => true,
            'data' => '',
            'msg' => '交易成功'
        );
        //根据充值金额跟运营商取出商品编号
        $params = json_decode($paymentInfo['params'],true);
        $operator = mb_substr($params['service_provider'],-2);
        $data = $this->where(array('item_money' => $params['recharge_money'],'item_operator'=>$operator))->find();
        $item_id = isset($data['item_id'])?$data['item_id']:'';
        if($item_id){
            $userId = getSetting('userId');
            $key = getSetting('private_key');
            //开始充值
            $time = date("YmdHis",time());
            $sign = md5($time.$item_id.$params['serialno'].$params['mobile'].$userId.$key);
            $url = "http://101.201.33.233:8090/unicomAync/buy.do?sign=$sign&uid=$params[mobile]&dtCreate=$time&userId=$userId&itemId=$item_id&serialno=$params[serialno]";
            $xml = (new curl())->get($url);
            $objectxml = simplexml_load_string($xml);//将文件转换成 对象
            $xmljson= json_encode($objectxml );//将对象转换个JSON
            $xmlarray=json_decode($xmljson,true);//将json转换成数组
            if($xmlarray['status']=='failed'){
                $result['status'] = false;
                $result['msg'] = '充值失败，请联系客服退款';
            }
            return $result;
        }
    }
}