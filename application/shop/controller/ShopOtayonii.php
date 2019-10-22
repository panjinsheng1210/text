<?php
namespace app\shop\controller;

use app\common\controller\Manage;
use app\common\model\PrizeRecord;
use app\common\model\ShopOtayonii as ShopOtayoniiModel;
use think\Db;
use think\facade\Request;

class ShopOtayonii extends \app\common\controller\Shop
{

    /**店铺账户/金豆明细
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index(){
        $user_id = input('user_id','');
        if(Request::isAjax()){
            $data = input();
            $data['finance_id'] = 3;
            $data['shop_id']    = $this->shopId;
            $ShopOtayonii = new ShopOtayoniiModel();
            return $ShopOtayonii->tableData($data);
        }else{
            $this->assign('user_id',$user_id);
            $this->assign('shopOtayonii',config('params.shopOtayonii')['type']);
            return $this->fetch();
        }
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
        $ShopOtayoniiModel = new ShopOtayoniiModel();
        if (!$id) {
            return $result;
        }
        $delRes = $ShopOtayoniiModel->delShopOtayonii($id);
        if (!$delRes['status']) {
            $result['msg'] = $delRes['msg'];
            return $result;
        }
        $result['status'] = true;
        $result['msg']    = '删除成功';
        return $result;
    }


    /**奖金明细明细
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function prizeIndex(){
        $type = input('type',1);
        if(Request::isAjax()){
            $data = input();
            $PrizeRecord = new PrizeRecord();
            return $PrizeRecord->tableData($data);
        }else{
            $this->assign('bonus',config('params.bonus')['name']);
            $this->assign('type',$type);
            return $this->fetch();
        }
    }


}
