<?php
// +----------------------------------------------------------------------
// | JSHOP [ 小程序商城 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018 http://jihainet.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: tianyu <tianyu@jihainet.com>
// +----------------------------------------------------------------------
namespace app\api\controller;

use app\common\model\Shixis as ShixiModel;
use app\common\controller\Api;

class Shixis extends Api 
{

    /**
     *  API
     *  获取兼职列表 
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getlist()
    {
       // session_start();
       //var_dump($_SESSION);die;

        $shixiModel = new ShixiModel();

          $data['state']=1;
            //file_put_contents('./a2.txt', print_r($data,true));
            return $shixiModel->tableData($data);    
        //return $shixiModel->tableData(input('page/d',1), input('limit/d',5));     
    }

    /**
     *  API
     *  获取兼职列表 
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */


    public function   test()
    {
       // session_start();
       //var_dump($_SESSION);die;
       //
       echo  '113423423222'; 

        //$shixiModel = new ShixiModel();
        //return $shixiModel->tableData(input('page/d',1), input('limit/d',5));
    }
    //3 ... 180

    /**
     *  兼职详情列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function details(){
        $shixiModel = new ShixiModel();
        $id = input('param.id',"");
        return $shixiModel->detailsList($id,input('page/d',1), input('limit/d',5));
    }


        /**
     * 添加
     * @return array|mixed
     */
    public function add()
    {
        //接值 获取 参数
        $data = input('param.');
//        $user_id = $this->userId;
        $shixiModel = new ShixiModel();
        return $shixiModel->addData($data);
    }

    /**
     * 修改
     * @return array|mixed
     */
    public function edit(){
        //接值ID，修改字段
        $data = input('param.');
        $shiModel = new ShixiModel();
        return $shiModel->saveData($data);
    }


    /**
     * 删除
     * @param array id
     * @return array
     */
    public function del(){

//        $user_id = $this->userId;
//        var_dump($user_id);
        $id = input('param.id',"");
        $result = model('common/Shixi')->del($id);

        if($result){
            $return_data = array(
                'status' => true,
                'msg' => "删除成功",
                'data' => $result
            );
        }else{
            $return_data = array(
                'status' => false,
                'msg' => "删除失败",
                'data' => $result
            );
        }

        return $return_data;
    }
}


