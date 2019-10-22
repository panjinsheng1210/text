<?php
namespace app\api\controller;

use app\common\model\Relation as RelationModel;
use app\common\controller\Api;
use app\common\model\UserToken;
use app\common\model\Shixi as ShixiModel;  
use think\Db;


use app\common\model;
use think\Validate;

class Relation extends Api
{

    //兼职,我的兼职列表
    public function getlist(){ 

       $data = input('param.');
       $user_id = getUserIdByToken($data['token']); 

       $relationModel = new RelationModel();
       
        return $relationModel->baoming($user_id,input('page/d',1), input('limit/d',5));   
    }

  //实习,我的实习列表
    public function test(){ 

       $data = input('param.');
       $user_id = getUserIdByToken($data['token']); 
        $relationModel = new RelationModel();
        return $relationModel->baomings($user_id,input('page/d',1), input('limit/d',5)); 
    }


    //我的兼职 添加报名  sta  1 兼职 2 实习 
    public function add(){
        $relationModel = new RelationModel();
       
         $data = input('param.');

         $user_id = getUserIdByToken($data['token']);  

         $data['status']=2;
         $data['sta']=1;

         $data['user_id'] = $user_id;

        $a=  $relationModel->baoming1($user_id,$data) ;

            if($a['count']>=1){
                $result['status'] = false;
                $result['msg'] = '保存失败,已经报名兼职了';
               // $result['msg'] = $a['count'];
                 return $result;

            }
        return $relationModel->addData($data);
    }
    //我的实习  实习添加报名   status  1 兼职 2
     public function adds(){
        $relationModel = new RelationModel();
      
        $data = input('param.');

        $user_id = getUserIdByToken($data['token']); 

        $data['status']=2;
        $data['sta']=2;

        $data['user_id'] = $user_id;
        $a=  $relationModel->baoming1($user_id,$data) ;
            if($a['count']>=1){
                $result['status'] = false;
                $result['msg'] = '保存失败,已经报名实习了';
            
                 return $result;

            }
        return $relationModel->addData($data);
    }
}