<?php
// +----------------------------------------------------------------------
// | JSHOP [ 小程序商城 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018 http://jihainet.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: tianyu <tianyu@jihainet.com>
// +----------------------------------------------------------------------
namespace app\api\controller;

use app\common\model\Leilast as LeilastModel;
use app\common\controller\Api;

class Leilast extends Api
{

    /**
     *  API
     *  获取兼职列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function lists()
    {
        $leilastModel = new LeilastModel();
        return $leilastModel->leilastgetList(input('page/d',1), input('limit/d',5));
    }

    /**
     * 删除
     * @param array id
     * @return array
     */
    public function del(){
        $id = input('param.id',"");
        $result = model('common/Leilast')->del($id);
        if($result)
        {
            $return_data = array(
                'status' => true,
                'msg' => '删除成功',
                'data' => $result
            );
        } else {

            $return_data = array(
                'status' => false,
                'msg' => '删除失败',
                'data' => $result
            );
        }
        return $return_data;
    }

    /**
     * 添加
     * @return array|mixed
     */
    public function add()
    {
        //接值
        $data=input('param.');
        $leiModel = new LeilastModel();
        return $leiModel->addData($data);
    }

    /**
     * 修改
     * @return array|mixed
     */
    public function edit(){
        //接值 所有字段     ID..需要修改的地段..全使用$data接过来了
        $data = input('param.');
        $leiModel = new LeilastModel();
        return $leiModel->saveData($data);
    }

}


