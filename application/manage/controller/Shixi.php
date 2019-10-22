<?php

namespace app\Manage\controller;

use app\common\controller\Manage;
use app\common\model\Shixi as ShixiModel;
use app\common\model\Leilast;
use think\facade\Request;

//找兼职 
class Shixi extends Manage
{

    public function index()
    {
        $shixiModel = new ShixiModel();
        if(Request::isAjax())
        {
            $data=input('param.');
            $data['state']=2;

            //file_put_contents('./a1.txt', print_r($data,true));
            return $shixiModel->tableData($data);  
        }
        return $this->fetch();
    }

    public function add(){
        $shixiModel = new ShixiModel();
//        print_r(input('param.'));die;
        if(Request::isPost())
        {
            $type_id = input('param.type_id');
            $data = input('param.');
            $data['state']=2;
            $data['type_id'] =  implode(',',$type_id);
            return $shixiModel->addData($data);
        }
        $leiLastModel = new Leilast();
        $positionList = $leiLastModel->select();
        return $this->fetch('add',[
            'list'=> $positionList,
            'company'=>config('params.danwei')['company']
        ]);
    }

    public function edit()
    {
        $shixiModel = new ShixiModel();
//        print_r(input('param.'));die;
        if(Request::isPost())
        {
            $type_id = input('param.type_id');
            $data = input('param.');
            $data['type_id'] =  implode(',',$type_id);
            return $shixiModel->saveData($data);
        }
        $data = $shixiModel->where('id',input('param.id/d'))->find();

        if (!$data) {
            return error_code(10002);
        }
        $leiLastModel = new Leilast();
//        $type = $leiLastModel->where('id',$data['type_id'])->find();
        $positionList = $leiLastModel->select();
        return $this->fetch('edit',[
//            'type' => $type,
            'data' => $data,
            'list' => $positionList,
            'company' => config('params.danwei')['company']
        ]);
    }

    public function positionList()
    {
        $leiLastModel = new Leilast();
        return $leiLastModel->field('id,name')->select();
    }

    public function del()
    {
        $shixiModel = new ShixiModel();
        $result = ['status'=>false,'msg'=>'删除失败','data'=>''];
        if ($shixiModel->where('id',input('param.id/d'))->delete())
        {
            $result['status'] = true;
            $result['msg'] = '删除成功';
        }
        return $result;
    }










}
