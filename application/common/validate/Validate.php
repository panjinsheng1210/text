<?php
// +----------------------------------------------------------------------
// | JSHOP [ 小程序商城 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018 http://jihainet.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: tianyu <tianyu@jihainet.com>
// +----------------------------------------------------------------------
namespace app\common\validate;


class Validate extends \think\Validate
{
    /**
     * 验证用户是否存在
     * @param $value
     * @param $rule
     * @param $data
     * @param $fieldName
     * @return bool|string
     */
    public function userExist($value,$rule=null,$data)
    {
        if(is_null($rule)){
            return '没有指定模型类';
        }
        if($rule::get($value)){
            return true;
        }
        return '用户不存在';
    }
}