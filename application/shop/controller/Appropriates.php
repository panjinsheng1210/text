<?php
namespace app\shop\controller;


use app\common\model\Appropriate;
use app\common\model\User as UserModel;
use think\Db;
use think\facade\Request;

class Appropriates extends \app\common\controller\Shop
{

    /**
     * 查询账户余额
     */
    public function finance(){
        if(Request::isAjax()){
            $userModel = new UserModel();
            $input = input('param.');
            $input['id'] = $this->userId;
            return $userModel->tableData($input);
        }
        return $this->fetch();
    }

    /**拨款/扣款记录
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index(){
        $user_id = input('user_id','');
        if(Request::isAjax()){
            $data = input();
            $Appropriate = new Appropriate();
            return $Appropriate->tableData($data);
        }else{
            $this->assign('user_id',$user_id);
            $this->assign('financeType',config('params.financeType'));
            return $this->fetch();
        }
    }

    /**
     * 添加拨款/扣款
     * @return array|mixed
     */
    public function addAppropriate()
    {
        $this->view->engine->layout(false);
        if (Request::isPost()) {
            $input     = Request::param();
            $userModel = new Appropriate();
            $result    = $userModel->manageAdd($input);
            return $result;
        }
        $financeType = config('params.financeType');
        $this->assign('financeType',$financeType);
        return $this->fetch('addAppropriate');
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
        $AppropriateModel = new Appropriate();
        if (!$id) {
            return $result;
        }
        $delRes = $AppropriateModel->delAppropriate($id);
        if (!$delRes['status']) {
            $result['msg'] = $delRes['msg'];
            return $result;
        }
        $result['status'] = true;
        $result['msg']    = '删除成功';
        return $result;
    }


}
