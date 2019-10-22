<?php
namespace addons\Distribution;

use app\common\model\Balance;
use app\common\model\Order;
use app\common\model\PrizeRecord;
use app\common\model\RecommendConfig;
use app\common\model\Setting;
use app\common\model\UpgradeConfig;
use app\common\model\User;
use myxland\addons\Addons;
use \think\Db;

use think\Exception;

/**
 * 分销插件
 */
class Distribution extends Addons
{
    // 该插件的基础信息
    public $info = [
        'name' => 'Distribution',
        'title' => '三级分销系统插件',
        'description' => '用户购买商品支付成功后，直推和直推的直推会拿一部分佣金奖励',
        'status' => 0,
        'author' => 'sin',
        'version' => '1.1'
    ];

    /**
     * 插件安装方法
     * @return bool
     */
    public function install()
    {
        return true;
    }

    /**
     * 插件卸载方法
     * @return bool
     */
    public function uninstall()
    {
        return true;
    }
//    //订单支付成功后的插件
//    public function orderpayed($order_id)
//    {
//        $balanceModel = new Balance();
//        $userModel = new User();
//        $orderModel = new Order();
//        $addonModel = new \app\common\model\Addons();
//        $config    = $addonModel->getSetting($this->info['name']);
//
//
//        $order_info = $orderModel->where(['order_id'=>$order_id])->find();
//        if(!$order_info){
//            return;
//        }
//        $info = $userModel->where(['id'=>$order_info['user_id']])->find();
//        if(!$info){
//            return;
//        }
//        //给直推返利
//        if($info['pid'] == '0'){
//            return;
//        }
//        if($info['pid'] == $info['id']){
//            //直推不给自己返
//            return;
//        }
//        $pinfo = $userModel->where(['id'=>$info['pid']])->find();
//        if(!$pinfo){
//            return;
//        }
//        $balanceModel->change($pinfo['id'], $balanceModel::TYPE_DISTRIBUTION, $order_info['order_amount']*$config['level1'],$order_id);
//        //去给次推返利
//        if($pinfo['pid'] == '0'){
//            return;
//        }
//        if($pinfo['pid'] == $info['id']){
//            //次推不给自己返
//            return;
//        }
//        if($pinfo['pid'] == $pinfo['id']){
//            //次推不给次推返
//            return;
//        }
//        $ppinfo = $userModel->where(['id'=>$pinfo['pid']])->find();
//        if(!$ppinfo){
//            return;
//        }
//        $balanceModel->change($ppinfo['id'], $balanceModel::TYPE_DISTRIBUTION, $order_info['order_amount']*$config['level2'],$order_id);
//        return;
//
//    }

    public function config($params = [])
    {
        $config = $this->getConfig();
        $this->assign('config', $config);
        $this->assign('config_params', $params);
        return $this->fetch('config');
    }

    //订单支付成功后的插件(极差奖)
    public function orderpayed($order_id)
    {
        Db::startTrans();
        try {
            $order_info = Order::where(['order_id'=>$order_id])->find();
            $sql = "select p.id,cfg.config_dif_award from jshop_user as my,jshop_user as p,jshop_jicha_config as cfg
		        where  my.id = {$order_info->user_id}
		        and p.grade = cfg.config_level 
		        and my.path like CONCAT(p.path,'/%')
		        and p.grade <= 4
		        ORDER BY p.layer desc";
            $recommend = Db::query($sql);
            if ($recommend) {
                $previous_award = 0;
                $current_award  = 0;
                foreach ($recommend as $key=>$val) {
                    $current_award = $val['config_dif_award'];
                    if ($current_award > $previous_award) {
                        (new PrizeRecord())->bonusCapping($val['id'], $order_info->user_id, ($current_award - $previous_award), 2);
                        $previous_award = $current_award;
                    }
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            throw $e;
            Db::rollback();
        }
        return ;
    }
    // 订单支付成功后插件(极差) 一钻调用
    public function orderpayeddrill($order_id)
    {
        Db::startTrans();
        try {
            $order_info = Order::where(['order_id'=>$order_id])->find();
            if ($order_info->user->pid) {
                // 分享奖
                (new PrizeRecord())->bonusCapping($order_info->user->pid, $order_info->user_id, 1000, 6);
            }
            $sql = "select p.id,cfg.config_dif_award,cfg.config_equal_award from jshop_user as my,jshop_user as p,jshop_jicha_config as cfg
		        where  my.id = {$order_info->user_id}
		        and p.grade = cfg.config_level 
		        and my.path like CONCAT(p.path,'/%')
		        and p.grade >= 8
		        ORDER BY p.layer desc";
            $recommend = Db::query($sql);
            if ($recommend) {
                $previous_award = 0;
                $current_award  = 0;
                $previous_income_award = 0;
                foreach ($recommend as $key=>$val) {
                    $current_award = $val['config_dif_award'];
                    if (($current_award == $previous_award) && $previous_income_award >0) {
                        if ($val['config_equal_award']) {
                            (new PrizeRecord())->bonusCapping($val['id'], $order_info->user_id, $val['config_equal_award'], 5);
                        }
                        $previous_income_award = 0;
                    }
                    if ($current_award > $previous_award) {
                        (new PrizeRecord())->bonusCapping($val['id'], $order_info->user_id, ($current_award - $previous_award), 2);
                        $previous_income_award = $current_award - $previous_award;
                        $previous_award = $current_award;
                    }
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            throw $e;
            Db::rollback();
        }
        return ;
    }
    // 销售提成
    public function orderPayRecommend($recommend, $userLevel, $user)
    {
        Db::startTrans();
        try {
            $levelConfig   = [8=>10000, 9=>50000, 10=>100000];
            $recommendInfo = User::get($recommend);
            $config = RecommendConfig::where(['config_recommend_level'=>$recommendInfo->grade, 'config_user_level'=>$userLevel])->find();
            // 销售提成
            if ($config) {
                (new PrizeRecord())->bonusCapping($recommend, $user, round($config->config_ratio*$config->config_base,2), 7);
            }
            // 超越奖金
            if (($userLevel > $recommendInfo->grade)) {
                $compareLevel = $recommendInfo->grade < 8 ? 8 : $recommendInfo->grade;
                $sql = "select p.id,p.grade from jshop_user as my,jshop_user as p
		        where  my.id = {$recommend}
		        and my.path like CONCAT(p.path,'/%')
		        and p.grade > {$compareLevel}
		        ORDER BY p.layer desc";
                $recommendAll = Db::query($sql);
                if ($recommendAll) {
                    $current_award  = 0;
                    $previous_award = 0;
                    foreach ($recommendAll as $key=>$val) {
                        $current_award = $val['grade'] == 9 ? 0.1 :0.2;
                        if ($current_award > $previous_award) {
                            (new PrizeRecord())->bonusCapping($val['id'], $user, round(($current_award-$previous_award) * $levelConfig[$userLevel], 2), 8);
                            $previous_award = $current_award;
                        }
                    }
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            throw $e;
            Db::rollback();
        }
        return true;
    }
    // 补货提成
    public function orderPayReplenish($user, $point)
    {
        Db::startTrans();
        try {
            // 全网的二钻18%*总积分 加权平分
            $twoDrill = User::where(['grade'=>9])->select()->toArray();
            if ($twoDrill) {
                $twoDrillMoney = round(($point*0.18) / count($twoDrill), 2);
                foreach ($twoDrill as $key=>$val) {
                    (new PrizeRecord())->bonusCapping($val['id'], $user, $twoDrillMoney, 9);
                }
            }
            // 全网的三钻7%*总积分 加权平分
            $threeDrill = User::where(['grade'=>10])->select()->toArray();
            if ($threeDrill) {
                $threeDrillMoney = round(($point*0.07) / count($threeDrill), 2);
                foreach ($threeDrill as $key=>$val) {
                    (new PrizeRecord())->bonusCapping($val['id'], $user, $threeDrillMoney, 9);
                }
            }
            // 给推荐关系上级一条线发极差
            // 一钻 30% ；二钻40% ；三钻40%
            $sql = "select p.id,p.grade from jshop_user as my,jshop_user as p
		        where  my.id = {$user}
		        and my.path like CONCAT(p.path,'/%')
		        and p.grade >= 8
		        ORDER BY p.layer desc";
            $recommendAll = Db::query($sql);
            if ($recommendAll) {
                $current_award  = 0;
                $previous_award = 0;
                $ratio = [8=>0.3,9=>0.4,10=>0.4];
                foreach ($recommendAll as $key=>$val) {
                    $current_award = $ratio[$val['grade']];
                    if ($current_award > $previous_award) {
                        (new PrizeRecord())->bonusCapping($val['id'], $user, round(($current_award-$previous_award)*$point, 2), 9);
                        $previous_award = $current_award;
                    }
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            throw $e;
            Db::rollback();
        }
        return true;
    }
    // 三钻奖金
    public function orderPayThreeDrill($money, $type)
    {
        Db::startTrans();
        try {
            $upgradeConfig = UpgradeConfig::order('config_level','asc')->select();
            // 一级
            $oneLevel   = User::where(['grade'=>10,'three_drill_level'=>1])->select()->toArray();
            $oneCount   = count($oneLevel);
            // 二级
            $twoLevel   = User::where(['grade'=>10,'three_drill_level'=>2])->select()->toArray();
            $twoCount   = count($twoLevel);
            // 三级
            $threeLevel = User::where(['grade'=>10,'three_drill_level'=>3])->select()->toArray();
            $threeCount = count($threeLevel);
            // 四级
            $fourLevel  = User::where(['grade'=>10,'three_drill_level'=>4])->select()->toArray();
            $fourCount  = count($fourLevel);
            // 五级
            $fiveLevel  = User::where(['grade'=>10,'three_drill_level'=>5])->select()->toArray();
            $fiveCount  = count($fiveLevel);
            $countArray = [1=>$oneCount,2=>$twoCount,3=>$threeCount,4=>$fourCount,5=>$fiveCount];
            $levelArray = [1=>$oneLevel,2=>$twoLevel,3=>$threeLevel,4=>$fourLevel,5=>$fiveLevel];
            $current_award  = 0;
            $previous_award = 0;
            foreach ($upgradeConfig as $key=>$val) {
                $current_award = $val->config_ratio;
                if ($countArray[$val->config_level] >0) {
                    // 发奖金
                    foreach ($levelArray[$val->config_level] as $k=>$v) {
                        (new PrizeRecord())->bonusCapping($v['id'], null, round(($current_award-$previous_award)*$money/$countArray[$val->config_level], 2), $type);
                    }
                    $previous_award = $current_award;
                }
            }
            Db::commit();

        } catch (\Exception $e) {
            throw $e;
            Db::rollback();
        }
        return true;
    }
    // 月结三钻奖金
    public function threeDrillByMonth() {
        Db::startTrans();
        try {
            $setting = new Setting();
            // 星级提成
            $start_rating_money = $setting->getValue('start_rating_money');
            if ($start_rating_money > 0) {
                $this->orderPayThreeDrill($start_rating_money, 10);
            }
            // 钻级提成
            $drill_rating_money = $setting->getValue('drill_rating_money');
            if ($drill_rating_money > 0) {
                $this->orderPayThreeDrill($drill_rating_money, 11);
            }
            // 补货提成
            $replenish_rating_money  = $setting->getValue('replenish_rating_money');
            if ($replenish_rating_money > 0) {
                $this->orderPayThreeDrill($replenish_rating_money, 12);
            }
            // 更改星级提成/钻级提成/补货提成 金额
            $setting->setValue('start_rating_money',0);
            $setting->setValue('drill_rating_money',0);
            $setting->setValue('replenish_rating_money',0);
            // 会员更改伞下业绩
            User::where('three_drill_level', '>',0)->update(['three_drill_level'=>0, 'under_bill'=>0]);
            Db::commit();
        } catch (\Exception $e) {
            throw $e;
            Db::rollback();
        }
        return true;
    }
}