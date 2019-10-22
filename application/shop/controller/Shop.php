<?php
namespace app\shop\controller;

use app\common\model\Shop as shopModel;
use think\facade\Request;

/**
 * Class Store
 * @package app\Manage\controller
 */
class Shop extends \app\common\controller\Shop
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
            $post = input('param.');
            $post['id'] = $this->shopId;
            return $storeModel->tableData($post);
        }
        return $this->fetch('index',['typeList'=>shopModel::TypeList]);
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
            return $storeModel->editData(input('param.'));
        }
        $info = $storeModel->where('id',input('param.id/d'))->find();
        if(!$info)
        {
            return error_code(10002);
        }
        return $this->fetch('edit',[ 'info' => $info ]);
    }


    /**
     * 删除
     * @return array
     */
    public function del()
    {
        $storeModel = new shopModel();
        $result = ['status' => true,'msg' => '删除成功','data' => ''];
        if(!$storeModel->where('id',input('param.id/d'))->delete())
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

}