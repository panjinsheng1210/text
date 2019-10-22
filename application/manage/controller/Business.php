<?php
namespace app\Manage\controller;

use app\common\controller\Manage;
use app\common\model\Appropriate;
use app\common\model\Buy;
use app\common\model\Deal;
use app\common\model\SalableRecord as SalableRecordModel;
use app\common\model\SalableRecord;
use app\common\model\Sale;
use think\Db;
use think\facade\Request;

class Business extends Manage
{
    /**单价列表
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function salableIndex(){
        if(Request::isAjax()){
            $data = input();
            $Business = new SalableRecordModel();
            return $Business->tableData($data);
        }else{
            $this->assign('financeType',config('params.financeType'));
            return $this->fetch();
        }
    }
    /**
     * 添加单价
     * @return array|mixed
     */
    public function addSalable()
    {
        $this->view->engine->layout(false);
        if (Request::isPost()) {
            $input     = Request::param();
            $Model = new SalableRecord();
            $result    = $Model->manageAdd($input);
            return $result;
        }
        $financeType = config('params.financeType');
        $this->assign('financeType',$financeType);
        return $this->fetch('addSalable');
    }

    /**
     * 编辑单价
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editSalable()
    {
        $this->view->engine->layout(false);
        $Model = new SalableRecord();
        if (Request::isPost()) {
            $input  = Request::param();
            $result = $Model->manageEdit($input);
            return $result;
        }
        $id   = Request::param('id');
        $info = $Model->where('id',$id)->find();
        $this->assign('info', $info);
        $financeType = config('params.financeType');
        $this->assign('financeType',$financeType);
        return $this->fetch('editSalable');
    }

    /**
     * 删除单价记录
     * User: wjima
     * Email:1457529125@qq.com
     * Date: 2018-02-06 10:42
     */
    public function salableDel()
    {
        $result     = [
            'status' => false,
            'msg'    => '关键参数丢失',
            'data'   => '',
        ];
        $id   = input("post.id");
        $SalableRecordModel = new SalableRecordModel();
        if (!$id) {
            return $result;
        }
        $delRes = $SalableRecordModel->delSalable($id);
        if (!$delRes['status']) {
            $result['msg'] = $delRes['msg'];
            return $result;
        }
        $result['status'] = true;
        $result['msg']    = '删除成功';
        return $result;
    }

    /**卖出记录
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function buyIndex(){
        if(Request::isAjax()){
            $data = input();
            $Buy = new Buy();
            return $Buy->tableData($data);
        }else{
            $this->assign('status',config('params.business')['status']);
            return $this->fetch();
        }
    }
    /**
     * 删除卖出记录
     * User: wjima
     * Email:1457529125@qq.com
     * Date: 2018-02-06 10:42
     */
    public function buyDel()
    {
        $result     = [
            'status' => false,
            'msg'    => '关键参数丢失',
            'data'   => '',
        ];
        $id   = input("post.id");
        $Buy  = new Buy();
        if (!$id) {
            return $result;
        }
        $delRes = $Buy->delBuy($id);
        if (!$delRes['status']) {
            $result['msg'] = $delRes['msg'];
            return $result;
        }
        $result['status'] = true;
        $result['msg']    = '删除成功';
        return $result;
    }
    /**卖入记录
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function saleIndex(){
        if(Request::isAjax()){
            $data = input();
            $Sale = new Sale();
            return $Sale->tableData($data);
        }else{
            $this->assign('status',config('params.business')['status']);
            return $this->fetch();
        }
    }

    /**
     * 删除买入记录
     * User: wjima
     * Email:1457529125@qq.com
     * Date: 2018-02-06 10:42
     */
    public function saleDel()
    {
        $result     = [
            'status' => false,
            'msg'    => '关键参数丢失',
            'data'   => '',
        ];
        $id    = input("post.id");
        $Sale  = new Sale();
        if (!$id) {
            return $result;
        }
        $delRes = $Sale->delSale($id);
        if (!$delRes['status']) {
            $result['msg'] = $delRes['msg'];
            return $result;
        }
        $result['status'] = true;
        $result['msg']    = '删除成功';
        return $result;
    }

    /**匹配记录
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function dealIndex(){
        if(Request::isAjax()){
            $data = input();
            $Deal = new Deal();
            return $Deal->tableData($data);
        }else{
            return $this->fetch();
        }
    }



}
