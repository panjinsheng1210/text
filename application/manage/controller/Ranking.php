<?php
namespace app\Manage\controller;

use app\common\controller\Manage;
use app\common\model\User as UserModel;
use think\Db;
use think\facade\Request;

class Ranking extends Manage
{

    /**排行榜记录
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index(){
        if(Request::isAjax()){
            $data    = input();
            $ranking = new \app\common\model\Ranking();
            return $ranking->tableData($data);
        }else{
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
        unset($financeType[3]);
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
        $rankingModel = new \app\common\model\Ranking();
        if (!$id) {
            return $result;
        }
        $delRes = $rankingModel->delRanking($id);
        if (!$delRes['status']) {
            $result['msg'] = $delRes['msg'];
            return $result;
        }
        $result['status'] = true;
        $result['msg']    = '删除成功';
        return $result;
    }


}
