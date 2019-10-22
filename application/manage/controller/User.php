<?php
namespace app\Manage\controller;

use addons\Distribution\Distribution;
use app\common\controller\Manage;
use app\common\model\Balance;
use app\common\model\GoodsComment;
use app\common\model\UserGrade;
use app\common\model\UserLog;
use app\common\model\User as UserModel;
use app\common\model\UserPointLog;
use app\common\model\UserUpgrade;
use think\facade\Request;
use app\common\model\LeaveMessage;

class User extends Manage
{
    public function index()
    {
        //订单支付完成后的钩子
//        Hook('orderpayed', 1);
//        Hook('orderpayedone', 1);exit;
//        $orders = new \app\common\model\Order();
//        $orders->addRanking('15690309072185',1);exit;
//        $orders->fenhong(4);exit;
//        $dis = new Distribution();
//        $dis->threeDrillByMonth();exit;
////        $a = $orders->fenhong(3);
////        var_dump($a);exit;
//        $orders->getOrderSaveAll(15680896065124);exit;
        $mobile = input('mobile','');
        if(Request::isAjax()){
            $userModel = new UserModel();
            $post = input('param.');
            $post['filtermobile'] = input('param.filtermobile',1);
            return $userModel->tableData($post);
        }else{
            $this->assign('mobile',$mobile);
            return $this->fetch('index');
        }
    }
    /*推荐列表*/
    public function commend(){
        $userGradeModel = new UserGrade();
        $list = $userGradeModel->getAll();
        $this->assign('list',$list);
        $gradeInfo = UserGrade::order('id', 'asc')->select();
        $this->assign('gradeInfo', $gradeInfo);
        return $this->fetch('commend');
    }
    /*推荐列表*/
    public function commendList(){
        $type = input('type','');
        $mobile = input('mobile','');
        if($type=='getList'){
            $userModel = new UserModel();
            return $userModel->getUser();
        }
    }
    /*根据手机号取id*/
    public function getDivId(){

        $mobile = input('mobile','');
        if($mobile){
            $userModel = new UserModel();
            $data=$userModel->field("id")->where("mobile=$mobile")->find();
            if($data){
                return $data['id'];
            }
        }
        return 0;
    }
    /**
     * 获取积分记录
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pointLog()
    {
        $this->view->engine->layout(false);
        $user_id = input('user_id','');
        $flag    = input('flag', 'false');
        if($flag == 'true')
        {
            $mobile  = input('mobile','');
            if($mobile != ''){
                if(!($user_id = get_user_id($mobile))){
                    $user_id = '999999999999';
                }
            }
            $userPointLog = new UserPointLog();
            $res = $userPointLog->pointLogList($user_id, false, input('page', 1), input('limit', 20));
            return $res;
        }
        else
        {
            $this->assign('user_id', $user_id);
            return $this->fetch('pointLog');
        }
    }


    /**
     * @return array|bool|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editPoint()
    {
        $this->view->engine->layout(false);
        $user_id = input('user_id');
        $flag = input('flag', 'false');

        if($flag == 'true')
        {
            $point = input('point');
            $memo = input('memo');
            $userPointLog = new UserPointLog();
            $res = $userPointLog->setPoint($user_id, $point, $userPointLog::POINT_TYPE_ADMIN_EDIT, $memo);
            return $res;
        }
        else
        {
            $this->assign('user_id', $user_id);
            $User = new UserModel();
            $where[] = ['id', 'eq', $user_id];
            $user_info = $User->where($where)->find();
            $this->assign('point', $user_info['point']);
            return $this->fetch('editPoint');
        }
    }


    //取当前店铺的所有用户的登陆退出消息,现在是绑定死一个用户，以后可能有多个用户
    public function userLogList()
    {
        $userLogModel = new UserLog();
        return $userLogModel->getList(session('user.id'));
    }
    //用户统计
    public function statistics()
    {
        $userLogModel = new UserLog();
        $list_login = $userLogModel->statistics(7,$userLogModel::USER_LOGIN);
        $list_reg = $userLogModel->statistics(7,$userLogModel::USER_REG);

        $data = [
            'legend' => [
                'data' => ['新增记录', '活跃记录']
            ],
            'xAxis' => [
                [
                    'type' => 'category',
                    'data' => $list_login['day']
                ]
            ],
            'series' => [
                [
                    'name' => '新增记录',
                    'type' => 'line',
                    'data' => $list_reg['data']
                ],
                [
                    'name' => '活跃记录',
                    'type' => 'line',
                    'data' => $list_login['data']
                ]
            ]
        ];
        return $data;
    }


    /**
     * 评价列表
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function comment()
    {
        if(Request::isPost())
        {
            $page = input('page', 1);
            $limit = input('limit', 20);
            $order_id = input('order_id', '');
            $evaluate = input('evaluate', 'all');
            $mobile = input('mobile', false);
            $goodsCommentModel = new GoodsComment();
            $res = $goodsCommentModel->getListComments($page, $limit, $order_id, $evaluate, 'all', $mobile);
            if($res['status'])
            {
                $return = [
                    'status' => true,
                    'msg' => '获取成功',
                    'data' => $res['data']['list'],
                    'count' => $res['data']['count']
                ];
            }
            else
            {
                $return = [
                    'status' => false,
                    'msg' => '获取失败',
                    'data' => $res['data']['list'],
                    'count' => $res['data']['count']
                ];
            }
            return $return;
        }
        else
        {
            return $this->fetch('comment');
        }
    }


    /**
     * 修改邀请人
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editInvite()
    {
        $id = Request::param('id');
        $mobile = Request::param('mobile');
        $model = new UserModel();
        return $model->editInvite($id, $mobile);
    }

    /**
     * 添加用户
     * @return array|mixed
     */
    public function addUser()
    {
        $this->view->engine->layout(false);
        if (Request::isPost()) {
            $input     = Request::param();
            $userModel = new UserModel();
            $result    = $userModel->manageAdd($input);
            return $result;
        }
        $gradeModel = new UserGrade();
        $userGrade =$gradeModel->getAll();
        $this->assign('grade',$userGrade);
        return $this->fetch('addUser');
    }

    /**
     * 编辑用户
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editUser()
    {
        $this->view->engine->layout(false);
        $userModel = new UserModel();

        if (Request::isPost()) {
            $input  = Request::param();
            $result = $userModel->manageEdit($input);
            return $result;
        }

        $user_id = Request::param('user_id');
        $info    = $userModel->getUserInfo($user_id);
        $this->assign('info', $info);
        $gradeModel = new UserGrade();
        $userGrade =$gradeModel->getAll();
        $this->assign('grade',$userGrade);
        return $this->fetch('editUser');
    }


    /**
     * 用户详情
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function details()
    {
        $this->view->engine->layout(false);

        $user_id = Request::param('user_id');
        $model   = new UserModel();
        $info    = $model->getUserInfo($user_id);
        $this->assign('info', $info);
        return $this->fetch('details');
    }


    /**
     * 编辑余额
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editMoney()
    {
        $this->view->engine->layout(false);
        $user_id = input('user_id');
        $flag = input('flag', 'false');

        if($flag == 'true')
        {
            $money = input('money');
            $balanceMoney = new Balance();
            $res = $balanceMoney->change($user_id, $balanceMoney::TYPE_ADMIN, $money, 0);
            return $res;
        }
        else
        {
            $this->assign('user_id', $user_id);
            $User = new UserModel();
            $where[] = ['id', 'eq', $user_id];
            $user_info = $User->where($where)->find();
            $this->assign('money', $user_info['balance']);
            return $this->fetch('editMoney');
        }
    }


    //用户等级列表
    public function grade(){
        if(Request::isAjax()){
            $userGradeModel = new UserGrade();
            return $userGradeModel->tableData(input('param.'));
        }else{
            return $this->fetch('grade_index');
        }
    }

    //用户等级新增和编辑，都走这里
    public function gradeAdd(){
        $this->view->engine->layout(false);
        $result = [
            'status' => false,
            'data' => '',
            'msg' => ''
        ];

        $userGradeModel = new UserGrade();
        if(Request::isPost()){
            $validate = new \app\common\validate\UserGrade();

            if (!$validate->check(input('param.'))) {
                $result['msg'] = $validate->getError();
                return $result;
            }

            return $userGradeModel->toEdit(input('param.id'),input('param.name'),input('param.is_def',2),input('param.color'));
        }
        if(input('?param.id')){
            $info = $userGradeModel->where('id',input('param.id'))->find();
            if(!$info){
                $result['msg'] = "没有此条记录";
            }
            $this->assign('data',$info);
        }
        return $this->fetch('grade_edit');
    }
    //用户等级删除
    public function gradeDel(){
        $result = [
            'status' => false,
            'data' => '',
            'msg' => ''
        ];

        $userGradeModel = new UserGrade();
        if(!input('?param.id')){
            return error_code(10000);
        }

        $info = $userGradeModel->where('id',input('param.id'))->find();
        if(!$info){
            $result['msg'] = "没有此用户等级";
            return $result;
        }
        $re = $userGradeModel->where('id',input('param.id'))->delete();
        if($re){
            $result['status'] = true;
        }else{
            $result['msg'] = "删除失败";
        }
        return $result;

    }
    /*
     * 升级记录
     */
    public function userUpgrade(){
        if(Request::isAjax()){
            $data = input();
            $Upgrade = new UserUpgrade();
            return $Upgrade->tableData($data);
        }else{
            return $this->fetch('userUpgrade');
        }
    }
    /*
     * 升级
     */
    public function addUpgrade(){
        $this->view->engine->layout(false);
        if (Request::isPost()) {
            $input        = Request::param();
            $upgradeModel = new UserUpgrade();
            $result       = $upgradeModel->manageAdd($input);
            return $result;
        }

        $UserGrade = UserGrade::field('id,name')->select();
        $this->assign('userGrade',$UserGrade);
        return $this->fetch('addUpgrade');
    }
    /*
     * 删除升级记录
     */
    public function upgradeDel(){
        $result     = [
            'status' => false,
            'msg'    => '关键参数丢失',
            'data'   => '',
        ];
        $id   = input("post.id");
        $UserUpgrade = new UserUpgrade();
        if (!$id) {
            return $result;
        }
        $delRes = $UserUpgrade->delUpgrade($id);
        if (!$delRes['status']) {
            $result['msg'] = $delRes['msg'];
            return $result;
        }
        $result['status'] = true;
        $result['msg']    = '删除成功';
        return $result;
    }
    /**
     * 留言列表
     */
    public function message(){
        $mobile = input('mobile','');
        if(Request::isAjax()){
            $leaveMessage = new LeaveMessage();
            $post = input('param.');
            return $leaveMessage->tableData($post);
        }else{
            $this->assign('mobile',$mobile);
            return $this->fetch('message');
        }
    }
    /**
     * 回复详情
     * @return array|bool|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function replyInfo()
    {
        $this->view->engine->layout(false);
        $message_id = input('message_id');
        $flag = input('flag', 'false');
        if($flag == 'true')
        {
            $title = input('title');
            $content = input('content');
            $leaveMessage = new LeaveMessage();
            $res = $leaveMessage->replyMessage($message_id,$title,$content);
            return $res;
        }
        else
        {
            $this->assign('message_id', $message_id);
            $leaveMessage = new LeaveMessage();
            $where[] = ['id', 'eq', $message_id];
            $reply = $leaveMessage->where($where)->find()->reply_id;
            $message_info = $leaveMessage->where(['id'=>$reply,'type'=>2])->find();
            $this->assign('reply', $message_info);
            return $this->fetch('replyInfo');
        }
    }
}
