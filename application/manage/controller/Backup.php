<?php
namespace app\Manage\controller;

use app\common\controller\Manage;
use \app\common\model\Backup as BackupModel;
use think\Db;
use think\Exception;
use think\facade\Request;


class Backup extends Manage
{
    public function clean(){
        if(Request::isAjax()){
            $isUser = input('param.isUser',0);
            $return = [
                'status' => true,
                'msg'    => '清空成功',
                'data'   => ''
            ];
            $backup = new \org\Baksql(Db::getConfig());
            $info = $backup->backup();
            if($info['success'] == true){
                $cleanTable = array_keys(config('params.cleanTable'));
                $shopInfo = \app\common\model\Shop::select()->toArray();
                $userTable = [];
                if($shopInfo){
                    $shopId   = implode(',',array_column($shopInfo,'user_id'));
                    $shopId .= ',10482';
                }else{
                    $userTable = [
                    'jshop_shop',
                    'jshop_user_bankcards',
                    'jshop_user_ship',
                    'jshop_user_token',
                    'jshop_user_wx',
                    'jshop_user',
                    ];
                    $shopId = '10482';
                }
                if($isUser ==1){
                    $cleanTable = array_merge($cleanTable,$userTable);
                }
                Db::startTrans();
                try{
                    if($shopId && $isUser ==1){
                        Db::query("delete from jshop_user where id not in ($shopId)");
                        Db::query("delete from jshop_user_bankcards where user_id not in ($shopId)");
                        Db::query("delete from jshop_user_ship where user_id not in ($shopId)");
                        Db::query("delete from jshop_user_token where user_id not in ($shopId)");
                        Db::query("delete from jshop_user_wx where user_id not in ($shopId)");
                    }
                    foreach ($cleanTable as $key=>$val){
                        Db::execute("truncate table $val");
                    }
                    $newData['name']  = '清空数据备份';
                    $newData['table'] = '';
                    $newData['type']  = 2;
                    $newData['ctime'] = time();
                    $newData['backup_time'] = time();
                    $newData['file_path'] = $info['data'];
                    $BackupModel = new BackupModel;
                    $BackupModel->save($newData);
                    if ($isUser == 1) {
                        // 更改跟会员
                        $userModel = \app\common\model\User::get(10482);
                        $userModel->balance = 1000000;
                        $userModel->grade   = 1;
                        $userModel->recommend_number = 0;
                        $userModel->capping_money = 4000;
                        $userModel->recommend_orders_number = 0;
                        $userModel->recommend_futou_number  = 0;
//                        $userModel->mobile = '18888888888';
//                        $userModel->username = 'root';
                        $userModel->save();
                    }
                    $setting = new \app\common\model\Setting();
                    // 更改星级提成/钻级提成/补货提成 金额 (月结算三钻奖用到)
                    $setting->setValue('start_rating_money',0);
                    $setting->setValue('drill_rating_money',0);
                    $setting->setValue('replenish_rating_money',0);
                    // 更改一星升级/复购数 (周结算分红奖用到)
                    $setting->setValue('upgrade_one_start',0);
                    $setting->setValue('futou_one_start',0);
                    $setting->setValue('ranking_period_num',1);
                    Db::commit();

                }catch (\Exception $e) {
                    $return['success'] = false;
                    $return['msg']     = $e->getMessage();
                    Db::rollback();
                }
            }
            return $return;
        }
        return $this->fetch();
    }

    public function index(){
        if(Request::isAjax()){
            $data = input();
            $BackupModel = new BackupModel();
            return $BackupModel->tableData($data);
        }
        return $this->fetch();
    }

    public function addBackup(){
        $this->view->engine->layout(false);
        if (Request::isPost()) {
            $input       = Request::param();
            $BackupModel = new BackupModel();
            $result      = $BackupModel->backupAdd($input);
            return $result;
        }
        $cleanTable = config('params.cleanTable');
        $unset = ['jshop_bill_aftersales_images','jshop_bill_aftersales_items','jshop_bill_delivery_items','jshop_bill_payments_rel','jshop_bill_reship_items','jshop_order_items','jshop_order_log','jshop_otayonii_sold','jshop_print_express','jshop_user_point_log'];
        foreach ($unset as $key=>$val){
            unset($cleanTable[$val]);
        }
        $cleanTable = array_merge($cleanTable,['jshop_user'=>'用户信息']);
        $this->assign('cleanTable',$cleanTable);
        return $this->fetch('addBackup');
    }

    /**
     * 删除记录
     * User: wjima
     * Email:1457529125@qq.com
     * Date: 2018-02-06 10:42
     */
    public function del()
    {
        $result     = [
            'status' => false,
            'msg'    => '关键参数丢失',
            'data'   => '',
        ];
        $id   = input("post.id");
        if (!$id) {
            return $result;
        }
        $BackupModel = new BackupModel();
        $delRes = $BackupModel->delBackup($id);
        return $delRes;
    }
    // 备份
    public function backup(){
        $result     = [
            'status' => false,
            'msg'    => '关键参数丢失',
            'data'   => '',
        ];
        $id   = input("post.id");
        if (!$id) {
            return $result;
        }
        $BackupModel = new BackupModel();
        $backupRes   = $BackupModel->backup($id);
        return $backupRes;
    }
    // 恢复
    public function restore(){
        $result     = [
            'status' => false,
            'msg'    => '关键参数丢失',
            'data'   => '',
        ];
        $id   = input("post.id");
        if (!$id) {
            return $result;
        }
        $BackupModel = new BackupModel();
        $backupRes   = $BackupModel->restore($id);
        return $backupRes;
    }

    // 下载
    public function dowonload(){
        $result     = [
            'status' => false,
            'msg'    => '关键参数丢失',
            'data'   => '',
        ];
        $id   = input("get.id");
        if (!$id) {
            return $result;
        }
        $BackupModel = new BackupModel();
        $backupRes   = $BackupModel->dowonload($id);
        return $backupRes;
    }




//    //数据库备份
//    public function indexs()
//    {
//        var_dump(124);
////        $model = new \app\common\model\Balance();
////        $model->save(['user_id'=>1,'type'=>1,'money'=>3,'balance'=>2]);
////        exit;
////        $result = Db::execute('truncate table jshop_balance');
////        var_dump($result);exit;
//        //获取操作内容：（备份/下载/还原/删除）数据库
////        $type = input("type");
////        //获取需要操作的数据库名字
//        $name = input("name");
//        $type = 'backup';
//        $name = 'shop_190528_2019-05-28_14_50_48_2bf74e756440695fd6d1511e065171a0.sql';
//        $type = 'restore';
//        $backup = new \org\Baksql(Db::getConfig());
//        switch ($type) {
//            //备份
//            case "backup":
//                $info = $backup->backup();
//                $this->success("$info", 'index/backup/bak');
//                break;
//            //下载
//            case "dowonload":
//                $info = $backup->downloadFile($name);
//                $this->success("$info", 'index/backup/bak');
//                break;
//            //还原
//            case "restore":
//                $info = $backup->restore($name);
//                $this->success("$info", 'index/backup/bak');
//                break;
//            //删除
//            case "del":
//                $info = $backup->delfilename($name);
//                $this->success("$info", 'index/backup/bak');
//                break;
//            //如果没有操作，则查询已备份的所有数据库信息
//            default:
//                return $this->fetch("index");//将信息由新到老排序
////                return $this->fetch("index", ["list" => array_reverse($sql->get_filelist())]);//将信息由新到老排序
//        }
//    }


}
