<?php

/**
 * 商户的资金管理（余额管理，取店铺创始人的余额）
 */

namespace app\shop\controller;


use app\common\model\Balance as BalanceModel;

use app\common\model\UserBankcards;
use app\common\model\UserTocash;
use think\facade\Request;


class Balance extends \app\common\controller\Shop
{
    public function index()
    {
        $user_id = input('user_id','');
        if(Request::isAjax()){

            $data = input('param.');
            $data['user_id'] = $this->userId;
            $balanceModel = new BalanceModel();
            return $balanceModel->tableData($data);
        }else{
            $this->assign('user_id',$user_id);
            return $this->fetch('index');
        }
    }
    public function tocash()
    {
        if(Request::isAjax()){
            $data = input('param.');
            $data['shop_id'] = $this->shopId;
            $tocashModel = new UserTocash();
            return $tocashModel->tableData($data);
        }else{
            $this->assign('user_id',$this->userId);
            return $this->fetch('tocash');
        }
    }
    public function tocashexamine(){
        if(!input('param.id')){
            return error_code(10002);
        }

        $tocashModel = new UserTocash();
        return $tocashModel->delTocash(input('param.id'));
    }
    public function addTocash(){
        $this->view->engine->layout(false);
        if (Request::isPost()) {
            $input     = Request::param();
            $userModel = new UserTocash();
            $result    = $userModel->addTocash($this->userId,$input['money'],$input['bank_id'],$this->shopId,$input['remarks']);
            return $result;
        }
        $userBankcardsModel = new UserBankcards();
        $bankcardsInfo = $userBankcardsModel->where(['user_id'=>$this->userId])->select();
        $this->assign('bankInfo',$bankcardsInfo);
        return $this->fetch('addTochsh');
    }
}