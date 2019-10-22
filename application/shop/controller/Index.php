<?php
namespace app\shop\controller;


use app\common\model\BillAftersales;
use app\common\model\Operation;
use app\common\model\Order;
use app\common\model\ShopImages;
use think\facade\Cache;
use app\common\model\WeixinAuthor;
use app\common\model\Goods;
use app\common\model\Brand;


class Index extends \app\common\controller\Shop
{

    public function index(){
        $operationModel = new Operation();
        $this->assign('menu', $operationModel->manageMenu(session('manage')['id']));
        $shopImages = new ShopImages();
        $logo = $shopImages->returnLogoPath($this->shopId,1);
        $this->assign('image',$logo);
        return $this->fetch('index');
    }

    public function welcome()
    {
        $orderModel = new Order();
        //未发货数量
        $unpaid_count = $orderModel->where(['status'=>1,'pay_status'=>1,'ship_status'=>1,'shop_id'=>session("shop.id")])->count();
        //待发货数量
        $unship_count = $orderModel->where(['status'=>1,'pay_status'=>2,'ship_status'=>1,'shop_id'=>session("shop.id")])->count();
        //待售后数量
        $billAfterSalesModel = new BillAftersales();
        $afterSales_count = $billAfterSalesModel->getCount($this->shopId);

        $turnover = $orderModel->where(['pay_status'=>2,'shop_id'=>$this->shopId])->sum('goods_amount');
        $turnover += \app\common\model\BillPayments::where(['shop_id'=>$this->shopId,'type'=>3,'status'=>2])->sum('money');

        $this->assign('turnover',$turnover);
        $this->assign('unpaid_count',$unpaid_count);
        $this->assign('unship_count',$unship_count);
        $this->assign('after_sales_count',$afterSales_count);

        $goodsModel = new Goods();
        $goodsStatics=$goodsModel->staticGoods();
        $this->assign('goods_statics',$goodsStatics);
        hook('adminindex', $this);//后台首页钩子
        return $this->fetch('welcome');
    }

    /**
     * 供tag标签选择品牌的时候使用
     */
    public function tagSelectBrands()
    {
        $this->view->engine->layout(false);
        if(input('param.type') != 'show'){
            $request = input('param.');
            $brandModel = new Brand();
            return $brandModel->tableData($request);
        }else{
            return $this->fetch('tagSelectBrands');
        }
    }
    /**
     * 供tag标签选择商品的时候使用
     */
    public function tagSelectGoods()
    {
        $this->view->engine->layout(false);
        if(input('param.type') != 'show'){
            $request = input('param.');
            $goodModel = new Goods();
            $request['marketable'] = $goodModel::MARKETABLE_UP;     //必须是上架的商品
            return $goodModel->tableData($request);

        }else{
            return $this->fetch('tagSelectGoods');
        }
    }

    /**
     * 清除整站全部缓存
     * 如果其它地方写了缓存的读写方法，一定要有判断是否有缓存的情况！！！
     */
    public function clearCache(){
        Cache::clear();
        $this->success('清除缓存成功','index/welcome');
    }
}
