<?php
namespace app\Manage\controller;
use app\common\controller\Manage;
use app\common\model\Shop as shopModel;
use think\facade\Request;

/**
 * Class Store
 * @package app\Manage\controller
 */
class Shop extends Manage
{
    /**
     * 列表
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index()
    {
        if(Request::isAjax())
        {
            $storeModel = new shopModel();
            return $storeModel->tableData(input('param.'));
        }
        return $this->fetch('index',['typeList'=>shopModel::TypeList]);
    }

    /**
     * 添加
     * @return array|mixed
     */
    public function add()
    {
        if(Request::isAjax())
        {
            $storeModel = new shopModel();
            return $storeModel->addData(input('param.'));
        }
        return $this->fetch();
    }


    /**
     * 门店修改
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit()
    {
        $storeModel = new shopModel();
        if(Request::isAjax())
        {
            $data = input('param.');
            $images = input('post.shop.img/a', []);
            return $storeModel->editData($data,$images);
        }
        $info = $storeModel->returnShopInfo(input('param.id'));
        if(!$info)
        {
            return error_code(10002);
        }
        return $this->fetch('edit',[ 'info' => $info['data'] ]);
    }


    /**
     * 删除
     * @return array
     */
    public function del()
    {
        $storeModel = new shopModel();
        $result = ['status' => true,'msg' => '删除成功','data' => ''];
        $id     = input('param.id/d');
        $updateShop = shopModel::update(['is_verify'=>0],['id'=>shopModel::get($id)->user_id]);
        if(!$storeModel->where('id',$id)->delete() || !$updateShop)
        {
            $result['status'] = false;
            $result['msg'] = '删除失败';
        }
        return $result;
    }


    /**
     * 地图展示
     * @return mixed
     */
    public function showMap()
    {
        $this->view->engine->layout(false);
        $coordinate = input('param.coordinate');
        if($coordinate)
        {
            $this->assign('coordinate',$coordinate);
        }
        $qq_map_key = getSetting('qq_map_key');
        $this->assign('qq_map_key',$qq_map_key);
        return $this->fetch('map');
    }

    public function verify(){
        $result = ['status' => true, 'msg' => '审核成功','data' => ''];
        $status = input('param.status/d');
        if($status ==1){
            $updateShop = shopModel::update(['is_verify'=>$status],['id'=>input('param.id/d')]);
        }else{
            $updateShop = shopModel::where('id',input('param.id/d'))->delete();
        }
        $updateUser = \app\common\model\User::update(['isshop'=>$status==1?1:0],['id'=>input('param.user_id/d')]);
        if(!$updateShop || !$updateUser){
            $result['status'] = false;
            $result['msg']    = '审核失败';
        }
        return $result;
    }

}