<?php
/**
 * Created by PhpStorm.
 * User: youtuo-e
 * Date: 2019/5/6
 * Time: 14:55
 */

namespace app\common\model;



use think\Db;

class RecommendConfig extends Common
{

    protected $pk = 'config_id';

    public function updateConfig($data){
        $result = ['status' => true, 'msg' => '更改成功','data' => ''];
        if(!$this->update($data,['id'=>1])){
            $result['status'] = false;
            $result['msg']    = '更改失败';
        }
        return $result;
    }
}