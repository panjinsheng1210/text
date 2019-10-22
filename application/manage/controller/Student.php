<?php

namespace app\Manage\controller;

use app\common\controller\Manage;
use app\common\model\Student as StudentModel;
use think\facade\Request;

class Student extends Manage
{

    public function index()
    {
        $studentModel = new StudentModel();
        if (Request::isAjax()) {
            return $studentModel->tableData(input('param.'));
        }
        return $this->fetch();
    }

    public function add(){
        $studentModel = new StudentModel();
        if (Request::isAjax()) {
            $data = input('param.');
            return $studentModel->addData($data);
        }
        return $this->fetch();
    }

    public function edit(){
        $studentModel = new StudentModel();
        $id = input('param.id');
        if (Request::isAjax()) {
            return $studentModel->saveData(input('param.'));
        }
        $data = $studentModel->where('id',$id)->find();
        if (!$data) {
            return error_code(10002);
        }
        return $this->fetch('edit',['data'=>$data]);

    }

    public function del(){
        $studentModel = new StudentModel();
        $id = input('param.id');
        $result = [
            'status' => false,
            'msg' => '删除失败',
            'data' => "",
        ];
        if($studentModel->where('id',$id)->delete()){
            $result['status'] = true;
            $result['msg'] = '删除成功';
        }
        return $result;

    }

}