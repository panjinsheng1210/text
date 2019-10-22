<?php
namespace app\shop\controller;

use app\common\model\Shop as ShopModel;
use Request;


class Setting extends \app\common\controller\Shop
{
    public function index()
    {
        $storeModel = new shopModel();
        if(Request::isAjax())
        {
            $data = input('param.');
            $images = input('post.shop.img/a', []);
            return $storeModel->editData($data,$images);
        }
        $info = $storeModel->returnShopInfo($this->shopId);
        if(!$info)
        {
            return error_code(10002);
        }
        return $this->fetch('index',[ 'info' => $info['data'] ]);
    }
}