<?php
namespace app\Manage\controller;

use app\common\controller\Manage;
use app\common\model\Appropriate;
use app\common\model\User as UserModel;
use app\common\model\UserTransfer;
use think\Db;
use think\facade\Request;

class Transfer extends Manage
{


    /**转账记录
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index(){
        if(Request::isAjax()){
            $data = input();
            $data['type'] = 1;
            $Transfer = new UserTransfer();
            return $Transfer->tableData($data);
        }else{
            $this->assign('financeType',config('params.financeType'));
            return $this->fetch();
        }
    }

    /**兑换记录
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function exchangeIndex(){
        if(Request::isAjax()){
            $data = input();
            $data['type'] = 2;
            $Transfer = new UserTransfer();
            return $Transfer->tableData($data);
        }else{
            $this->assign('financeType',config('params.financeType'));
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
        $Transfer = new UserTransfer();
        if (!$id) {
            return $result;
        }
        $delRes = $Transfer->delTransfer($id);
        if (!$delRes['status']) {
            $result['msg'] = $delRes['msg'];
            return $result;
        }
        $result['status'] = true;
        $result['msg']    = '删除成功';
        return $result;
    }


}
