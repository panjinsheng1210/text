<?php
/**
 * Created by PhpStorm.
 * User: zhangchong
 * Date: 2019/5/29
 * Time: 16:09
 */

namespace app\common\model;


class ShopImages extends Common
{
    public function returnLogoPath($shopId,$type=0){
        $info = $this->field('image_id')->where("shop_id=$shopId and is_default = 1")->find();
        $url = _sImage(getSetting('shop_default_image'));
        if(isset($info['image_id'])&&$info['image_id']) {
            $url = _sImage($info['image_id']);
        }
        if($type==1){
            return $url;
        }
        return strstr($url,'static');
    }
    /**
     * 获取店铺所有图片
     * @param $shop_id
     * @return array
     * User: wjima
     * Email:1457529125@qq.com
     * Date: 2018-02-06 11:32
     */
    public function getAllImages($shop_id)
    {
        $result = [
            'status' => false,
            'msg'  => '店铺ID不能为空',
            'data'  => ''
        ];
        if(!$shop_id){
            return $result;
        }
        $images = $this->where([ 'shop_id' => $shop_id ])->select();
        if(!$images->isEmpty()) {
            $result['status'] = true;
            $result['msg'] = '查询成功';
            $result['data'] = $images->toArray();
        }else{
            $result['status'] = false;
            $result['msg'] = '无数据';
        }
        return $result;
    }

}