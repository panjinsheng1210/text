<?php
/**
 * Created by PhpStorm.
 * User: zhangchong
 * Date: 2019/5/18
 * Time: 14:46
 */

namespace app\common\model;
use think\Validate;
use think\Db;
use think\model\concern\SoftDelete;

class OtayoniiPrice extends Common
{
    /**
     * 往用户金豆表加数据
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
     public function autoUpdateOtayoniiData($setting=0){
         $result = [
             'status' => true,
             'msg' => '获取成功',
             'data' => []
         ];
         $beanArray = array();
         //当天订单总收入
         $today = strtotime(date('Y-m-d',time()));
         $yesterday = $today-24*60*60;
         $billPayments = new BillPayments();
         $revenue = $billPayments->field('sum(money) as money')->where("ctime>=$yesterday and ctime<= $today and (type = 1 or type = 3) and status = 2")->find();
         //公司总收入
         $totalRevenue = $revenue['money']*0.2;
         $totalRevenue = sprintf("%.2f",$totalRevenue);
         if($totalRevenue>10000){
             //总收入大于1万开始分红
             //公司分给用户的钱
             $totalBeanBividend = $totalRevenue*0.025;
             //取user表里面的金豆总数
             $user = new User();
             $beanData = $user->field('sum(otayonii) as otayonii')->find();
             $totalBeanNum = $beanData['otayonii'];
             if($totalBeanNum){
                 //单个金豆分红数
                 $singleBeanPrince =  sprintf("%.2f",$totalBeanBividend /$totalBeanNum);
                 //计算单个用户的金豆分红总数
                 $where[] = ['otayonii', 'gt', '0'];
                 $data = $user->field('otayonii,id')->where($where)->select();
                 foreach($data as $key=>$value){
                     $bean = array();
                     $beanPrice = sprintf("%.2f",$singleBeanPrince*$value['otayonii']);
                     if($beanPrice){
                         $bean['user_id'] = $value['id'];
                         $bean['price'] = $beanPrice;
                         $info = $this->field('price')->where("user_id=$value[id]")->find();
                         if(isset($info['price'])&&$info['price']){
                             $this->where("user_id=$value[id]")->inc('price',$bean['price'])->update();
                             $bean1['utime'] = time();
                             $this->where("user_id=$value[id]")->data($bean1)->update();
                         }else{
                             $bean['ctime'] = time();
                             $this->insert($bean);
                         }
                    }
                     $beanArray[] = $bean;
                 }
             }
         }
         $result['data'] = $beanArray;
         return $result;
     }
}