<?php
namespace app\api\controller;

use app\common\model\Student as StudentModel;
use app\common\controller\Api;

use app\common\model\UserToken;
use app\common\model\User as UserModel;

use app\common\model;
use think\Validate;
use think\Db;
class Student extends Api
{
    public function getlist(){
        $studentModel = new StudentModel();
        return $studentModel->tableData(input('page/d',1), input('limit/d',5)); 
    }

    public function add(){

        $data = input('param.');

        //认证状态
    	if( $data['sta']==1 ){
    		
         $user_id = getUserIdByToken($data['token']); 
    
        $sql = 'select * from jshop_student where user_id ='.'"'.$user_id.'"'; 
        $b = Db::query($sql);

         if (count($b)>0) {
            $result['status'] = 1;
            $result['msg'] = '已认证';
            $result['data']=$b;
          
        }else{

        	$result['status'] = 0;
            $result['msg'] = '未认证';
          
        }

         return   $result;

       
     }else{
        //添加认证
        
         $user_id = getUserIdByToken($data['token']); 

	     $sql = 'select * from jshop_student where user_id ='.'"'.$user_id.'"'; 
	      

	     $b = Db::query($sql); 

	     
	     

	    if (count($b)>0) {
	        //  $result['status'] = false;
            //     $result['msg'] = '保存失败';

	        // return $result;exit;
	        // 
	        $data['user_id']=$user_id;

	       
					     // $data['method'] = 'student.add';
					     // $data['student_id'] = '1254654654';
					     // $data['sex'] = '1';
					     // $data['mobile'] = '18236003161';
					     // $data['id_card'] = '410922166588878945';
					     // $data['email'] = 'qweqw@qq.com';
					     // $data['token'] = 'd8e0b9e7ae90769c97adab480cb611ec';
					     // $data['user_id'] = '10516';

	        //$data['id']=$user_id;
			$studentModel = new StudentModel();
			return $studentModel->saveData($data);   
	      
	    }else{

			$data['user_id']=$user_id;
			$studentModel = new StudentModel();
			return $studentModel->addData($data);


	    }
       
      
     }
       
    }


     
    
    public function auth($token){

     	
       $data = input('param.');
       $user_id = getUserIdByToken($data['token']); 
       // $user_id = getUserIdByToken($token); 
       
       $user = new UserModel();

       $userinfo=$user->getUserInfo($user_id);


       $userinfo['mobile']; 


            $sql = 'select * from jshop_student where mobile ='.'"'.$userinfo['mobile'].'"';
            //$one = $this->query($sql);

            $b = Db::query($sql); 

            //print_r($b);exit;\
            //
         

         if (count($b)>0) {
            $result['status'] = 1;
            $result['msg'] = '已认证';
          
        }else{

        	$result['status'] = 0;
            $result['msg'] = '未认证';
          
        }

         return   $result;



    
    }
}