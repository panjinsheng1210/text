<?php
// +----------------------------------------------------------------------
// | JSHOP [ 小程序商城 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018 http://jihainet.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: tianyu <tianyu@jihainet.com>
// +----------------------------------------------------------------------
namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Buy;
use app\common\model\Deal;
use app\common\model\SalableRecord;
use app\common\model\Sale;
use app\common\model\User;
use org\QRcode;
use think\Db;


class Business extends Api
{
    // 添加卖出
    public function addBuy(){
        $password2 = input('param.password2');
        $userInfo = User::get($this->userId);
        if(!password_verify($password2,$userInfo->password2)) return error_code(17011);
        $number = input('param.number');
        if (!$number) return error_code(17005);
        $Buy = new Buy();
        return $Buy->addBuy($this->userId,$number);
    }
    // 添加买入
    public function addSale(){
        $password2 = input('param.password2');
        $userInfo = User::get($this->userId);
        if(!password_verify($password2,$userInfo->password2)) return error_code(17011);
        $number = input('param.number');
        if (!$number) return error_code(17005);
        $Sale   = new Sale();
        return $Sale->addSale($this->userId,$number);
    }
    // 卖出列表
    public function buyIndex(){
        $page   = input('param.page', 1);
        $limit  = input('param.limit', config('jshop.page_limit'));
        $status = input('param.status', '');
        $Buy = new Buy();
        return $Buy->userBuyList($this->userId,$page,$limit,$status);
    }
    // 买入列表
    public function saleIndex(){
        $page   = input('param.page', 1);
        $limit  = input('param.limit', config('jshop.page_limit'));
        $status = input('param.status', '');
        $Sale   = new Sale();
        return $Sale->userSaleList($this->userId,$page,$limit,$status);
    }
    // 积分单价列表
    public function salableIndex(){
        $page   = input('param.page', 1);
        $limit  = input('param.limit', config('jshop.page_limit'));
        $type   = input('param.type', 4);
        $Salable= new SalableRecord();
        return $Salable->userSaleList($page,$limit,$type);
    }
    // 匹配记录列表
    public function dealIndex(){
        $page = input('param.page',1);
        $limit= input('param.limit',config('jshop.page_limit'));
        $type = input('param.status','');
        $Deal = new Deal();
        sprintf('%.2f',1234);

//        $a = $this->redirect(captcha_src().'?'.time());exit;
//        exit;
//        var_dump(captcha_src());
        $a = captcha_check('B2KUH');
        var_dump($a);exit;
//        var_dump('d2d977c58444271d9c780187e93f80e5');
//        var_dump(session('d2d977c58444271d9c780187e93f80e5'));

        exit;


        $a = 'a123456';
        $this->userId = 10027;
//        \app\common\model\User::update(['password2'=>password_hash($a,PASSWORD_DEFAULT)],['id'=>$this->userId]);exit;
        $userInfo = \app\common\model\User::get($this->userId);
        var_dump(password_verify($a,$userInfo->password2));exit;
    }
    // 匹配记录每月成交金额 (用作统计)
    public function dealCount(){
        $Deal = new Deal();
//        $count=12;
        $data[]= $Deal->getSum(1)['money'];
        $data[]= $Deal->getSum(2)['money'];
        $data[]= $Deal->getSum(3)['money'];
        $data[]= $Deal->getSum(4)['money'];
        $data[]= $Deal->getSum(5)['money'];
        $data[]= $Deal->getSum(6)['money'];
        $data[]= $Deal->getSum(7)['money'];
        $data[]= $Deal->getSum(8)['money'];
        $data[]= $Deal->getSum(9)['money'];
        $data[]= $Deal->getSum(10)['money'];
        $data[]= $Deal->getSum(11)['money'];
        $data[]= $Deal->getSum(12)['money'];
//        for ($i=1;$i<=$count;$i++){
//            $data[]= $Deal->getSum($i)['money'];
//        }
        $result = [
            'status' => true,
            'msg' => '获取成功',
            'data'=>$data,
        ];
        return $result;
    }

    public function getShopQRCode(){
        $return = [
            'status' => false,
            'msg'    => '生成失败',
            'data'   => ''
        ];
        $shop_id = input('param.shop_id');
        $shop_id = 2;
        include ('../extend/org/phpqrcode/phpqrcode.php');
        $value = request()->domain().'/api/business/text?shop_id='.$shop_id;
        $errorCorrectionLevel = 'L';//容错级别
        $matrixPointSize = 8;//生成图片大小
        //生成二维码图片
        $defaultPath = ROOT_PATH . 'public' . DS .'static'. DS .'qrcode'.DS.'shop/'; //默认目录
        $path = $defaultPath."qrcode_{$shop_id}.png";
        $logoPath = $defaultPath."qrcode_logo_{$shop_id}.png";
        if(!file_exists($defaultPath)){mkdir($defaultPath);}
        \QRcode::png($value, $path , $errorCorrectionLevel, $matrixPointSize, 2);
        $shopInfo = \app\common\model\Shop::get($shop_id);
        $logo = _sImage($shopInfo->logo);
        $QR = $path;//已经生成的原始二维码图
        if ($logo !== FALSE) {
            $QR = imagecreatefromstring(file_get_contents($QR));
            $logo = imagecreatefromstring(file_get_contents($logo));
            $QR_width = imagesx($QR);//二维码图片宽度
            $QR_height = imagesy($QR);//二维码图片高度
            $logo_width = imagesx($logo);//logo图片宽度
            $logo_height = imagesy($logo);//logo图片高度
            $logo_qr_width = $QR_width / 5;
            $scale = $logo_width/$logo_qr_width;
            $logo_qr_height = $logo_height/$scale;
            $from_width = ($QR_width - $logo_qr_width) / 2;
            //重新组合图片并调整大小
            imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,

                $logo_qr_height, $logo_width, $logo_height);
        }
        //输出图片
        if(imagepng($QR, $logoPath)){
            $return = [
                'status' => true,
                'msg'    => '生成成功',
                'data'   => request()->domain().$logoPath
            ];
        }
        return $return;
    }
    public function text(){
        var_dump(input());
        var_dump(111);
    }






}