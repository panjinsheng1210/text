<?php

namespace app\Manage\controller;

use app\common\controller\Manage;
use app\common\model\Leilast as LeilastModel;
use think\facade\Request;

class Leilast extends Manage
{

    public function index()
    {
        if(Request::isAjax())
        {
            $lastModel = new LeilastModel();
            return $lastModel->tableData(input('param.'));
        }
        return $this->fetch();
    }

    public function del()
    {
        $lastModel = new LeilastModel();
        $result = ['status'=>false,'msg'=>'删除失败','data'=>''];
        if ($lastModel->where('id',input('param.id/d'))->delete())
        {
            $result['status'] = true;
            $result['msg'] = '删除成功';
        }
        return $result;
    }

    public function add(){

        $lastModel = new LeilastModel();
        if(Request::isPost())
        {
            return $lastModel->addData(input('param.'));
        }
        return $this->fetch();
    }

    public function edit()
    {
//        $this->view->engine->layout(false);
        $lastModel = new LeilastModel();
        if(Request::isPost())
        {
            return $lastModel->saveData(input('param.'));
        }
        $data = $lastModel->where('id',input('param.id/d'))->find();

        if (!$data) {
            return error_code(10002);
        }
        return $this->fetch('edit',['data' => $data]);
    }



}
