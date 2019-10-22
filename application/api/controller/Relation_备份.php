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
//    public function add(){
//        $relationModel = new RelationModel();
//        return $relationModel->addData(input('param.'));
////        $user_id = input('param.user_id',"");
////        $job_id = input('param.job_id',"");
////        $status = input('param.status',"");
////        return $relationModel->addData($user_id,$job_id,$status);
//    }
    
    //兼职,我的兼职列表
    public function getlist(){ 

       $data = input('param.');
       $user_id = getUserIdByToken($data['token']); 

       //var_dump($user_id);exit;
        

        
        $relationModel = new RelationModel();
       
        return $relationModel->baoming($user_id,input('page/d',1), input('limit/d',5));   
    }

  //实习,我的实习列表
    public function test(){ 
//var_dump($user_id);exit;

       $data = input('param.');
       $user_id = getUserIdByToken($data['token']); 

       //var_dump($user_id);exit;
        

        
        $relationModel = new RelationModel();
       
        return $relationModel->baomings($user_id,input('page/d',1), input('limit/d',5)); 
    }


    //我的兼职 sta  1 兼职 2 实习添加报名
    public function add(){
        $relationModel = new RelationModel();
        //$user_id = $this->userId;
//        $user_id = 10511;
        $data = input('param.');

         //$data = input('param.');
       $user_id = getUserIdByToken($data['token']);  

        $data['status']=2;
         $data['sta']=1;


//        $token = input('param.token');
//        $sql = "select * from jshop_user_token where token = '".$token."'";
//        $b = Db::query($sql);
//        foreach($b as $value){
//            $user_id = $value['user_id'];
//        }
        $data['user_id'] = $user_id;


           
            // $sql = 'select * from jshop_relation where user_id ='.$user_id;
            // $one = $this->query($sql); 
            // 
            $a=  $relationModel->baoming1($user_id,$data) ;

            //var_dump($a);exit;
           
            if($a['count']==1){
                $result['status'] = false;
                $result['msg'] = '保存失败,已经兼职了';
            
                 return $result;

            }
        return $relationModel->addData($data);
    }
//我的实习 status  1 兼职 2 实习添加报名
     public function adds(){
        $relationModel = new RelationModel();
        //$user_id = $this->userId;
//        $user_id = 10511;
        $data = input('param.');

         //$data = input('param.');
       $user_id = getUserIdByToken($data['token']); 

        $data['status']=2;
        $data['sta']=2;


//        $token = input('param.token');
//        $sql = "select * from jshop_user_token where token = '".$token."'";
//        $b = Db::query($sql);
//        foreach($b as $value){
//            $user_id = $value['user_id'];
//        }
        $data['user_id'] = $user_id;


           
            // $sql = 'select * from jshop_relation where user_id ='.$user_id;
            // $one = $this->query($sql); 
            // 
            $a=  $relationModel->baoming1($user_id,$data) ;

            //var_dump($a);exit;
           
            if($a['count']==1){
                $result['status'] = false;
                $result['msg'] = '保存失败,已经兼职了';
            
                 return $result;

            }
        return $relationModel->addData($data);
    }
}