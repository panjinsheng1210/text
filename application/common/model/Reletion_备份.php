<?php
namespace app\common\model;
use think\Validate;
use app\common\model\Shixi as ShixiModel; 
use think\Db;
class Relation extends Common
{
    protected $name = 'relation';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';
    protected $updateTime = 'utime';  
   
    protected $rule =   [
       
    ];

    protected $msg  =   [
      
    ];
    

    public function baoming($user_id,$post)
    {
        if(isset($post['limit'])){
            $limit = $post['limit'];
        }else{
            $limit = config('paginate.list_rows');
        }
        $tableWhere = $this->tableWhere($post);

          $where = [];
      
         $where[] = ['user_id', 'eq', $user_id];
         $where[] = ['sta', 'eq', '1'];

        //var_dump($this->tableData());exit;
        $list = $this->field($tableWhere['field'])->where($where)->order($tableWhere['order'])->paginate($limit);

          // $sql = 'select * from jshop_relation ';
//$sql = 'select * from jshop_relation where user_id ='.$user_id;
           //$list = $this->query($sql);

       


        $data = $this->tableFormat($list->getCollection());         //返回的数据格式化，并渲染成table所需要的最终的显示数据类型


       
        $newarr = array(); 
        $all = array();
      // echo "<pre>" ;
        
        foreach($list as $key => $vol){
            $job_id = $vol['job_id'];
//print_r($job_id);exit;

            $sta=$vol['status'];

            if($sta==1){
                $vol['status']="已完成";
            }else if($sta==2){
                  $vol['status']="已报名";

            }else if($sta==3){
                 $vol['status']="已结束";
                
            }
//$job_id=1;
//$job_id = $vol['job_id'];
            $sql = 'select * from jshop_job where id ='.'"'.$job_id.'"';
           // $sql = 'select * from jshop_relation where user_id ='.$user_id;
            $one = $this->query($sql);
            // echo "<pre>" ;
 //print_r($one) ;exit;
                // 
            ///$status = $vol['status'];
            ///$all[] = $status;
            foreach($one as $vo){

                $vo['ctime']= date('Y-m-d',$vo['ctime']);
                $vo['utime']= date('Y-m-d',$vo['utime']);
                $newarr = $vo;
                $type_id = $vo['type_id'];
                $res = explode(',',$type_id);
                $new = array();

                //print_r($res) ;exit;
                foreach($res as $vv){
                   // print_r($vv) ;exit;
                    $sql = "select * from jshop_type where id =".$vv;
                    $one = $this->query($sql);


                    $new[] = $one[0]['name'];
                }
                $newarr['type_id'] = $new;

                $newarr['state']= $vol['status'];

                $all[] = $newarr;
            }

        }

        $re['code'] = 0;
        $re['msg'] = '';
        $re['count'] = count($all);
        $re['data'] = $all;

        return $re;
    }
    public function baomings($user_id,$post)
    {
        if(isset($post['limit'])){
            $limit = $post['limit'];
        }else{
            $limit = config('paginate.list_rows');
        }
        $tableWhere = $this->tableWhere($post);



         $where = [];
      
         $where[] = ['user_id', 'eq', $user_id];
         $where[] = ['sta', 'eq', '2'];
    

       

        //var_dump($this->tableData());exit;
        $list = $this->field($tableWhere['field'])->where($where)->order($tableWhere['order'])->paginate($limit);

          // $sql = 'select * from jshop_relation ';
//$sql = 'select * from jshop_relation where user_id ='.$user_id;
           //$list = $this->query($sql);

       
  //var_dump(11111111) ;exit;
       // var_dump($list) ;exit;
        $data = $this->tableFormat($list->getCollection());         //返回的数据格式化，并渲染成table所需要的最终的显示数据类型


       
        $newarr = array();
        $all = array();
        foreach($list as $key => $vol){
            $job_id = $vol['job_id'];
            $sta=$vol['status'];

            if($sta==1){
                $vol['status']="已完成";
            }else if($sta==2){
                  $vol['status']="已报名";

            }else if($sta==3){
                 $vol['status']="已结束";
                
            }
            //$job_id=1;
            $sql = 'select * from jshop_job where id ='.'"'.$job_id.'"';
           // $sql = 'select * from jshop_relation where user_id ='.$user_id;
            $one = $this->query($sql);
            ///$status = $vol['status'];
            ///$all[] = $status;
            ///
           //print_r($one);exit;
            foreach($one as $vo){

                $vo['ctime']= date('Y-m-d',$vo['ctime']);
                $vo['utime']= date('Y-m-d',$vo['utime']);
                $newarr = $vo;
                $type_id = $vo['type_id'];
                $res = explode(',',$type_id);
                $new = array();
                foreach($res as $vv){
                    $sql = "select * from jshop_type where id = ".$vv;
                    $one = $this->query($sql);
                    $new[] = $one[0]['name'];
                }
                $newarr['type_id'] = $new;

                $newarr['state']= $vol['status'];
//            echo "<pre/>";
                $all[] = $newarr;
            }

        }

        $re['code'] = 0;
        $re['msg'] = '';
        $re['count'] = count($all);
        $re['data'] = $all;

        return $re;
    }

    public function baoming1($user_id,$post)
    {
        if(isset($post['limit'])){
            $limit = $post['limit'];
        }else{
            $limit = config('paginate.list_rows');
        }
        $tableWhere = $this->tableWhere($post);

          $where = [];
        $sta=$post['sta'];
         $where[] = ['user_id', 'eq', $user_id];
         $where[] = ['sta', 'eq', $sta];


        //var_dump($this->tableData());exit;
        $list = $this->field($tableWhere['field'])->where($where)->order($tableWhere['order'])->paginate($limit);

          // $sql = 'select * from jshop_relation ';
//$sql = 'select * from jshop_relation where user_id ='.$user_id;
           //$list = $this->query($sql);

       

        //var_dump($list) ;exit;
        $data = $this->tableFormat($list->getCollection());         //返回的数据格式化，并渲染成table所需要的最终的显示数据类型


        // $shixiModel = new ShixiModel();
        // $list_a= $shixiModel->tableData(input('page/d',1), input('limit/d',100));

        
        // $list_a=$list_a['data'];
        // $b=count($list_a)+2;

        // var_dump(count($data).$b) ;exit;
        //   if(count($list)<=0){

        //     $re['code'] = 0;
        //     $re['msg'] = '此用户还没有兼职';
        //     $re['count'] = 0;
        //     $re['data'] = $all;

        //     return $re;

        // }
        $newarr = array();
        $all = array();
        foreach($list as $key => $vol){
            $job_id = $vol['job_id'];
            $sql = 'select * from jshop_job where id ='.'"'.$job_id.'"';
           // $sql = 'select * from jshop_relation where user_id ='.$user_id;
            $one = $this->query($sql);
            ///$status = $vol['status'];
            ///$all[] = $status;
            foreach($one as $vo){
                $newarr = $vo;
                $type_id = $vo['type_id'];
                $res = explode(',',$type_id);
                $new = array();
                foreach($res as $vv){
                    $sql = "select * from jshop_type where id = ".$vv;
                    $one = $this->query($sql);
                    $new[] = $one[0]['name'];
                }
                $newarr['type_id'] = $new;
//            echo "<pre/>";
                $all[] = $newarr;
            }

        }

        $re['code'] = 0;
        $re['msg'] = '';
        $re['count'] = count($all);
        $re['data'] = $all;

        return $re;
    }

//      public function baoming11($user_id,$post)
//     {
//         if(isset($post['limit'])){
//             $limit = $post['limit'];
//         }else{
//             $limit = config('paginate.list_rows');
//         }
//        // $tableWhere = $this->tableWhere($post);
//         //$list = $this->field($tableWhere['field'])->where(['user_id'=>$user_id])->order($tableWhere['order'])->paginate($limit);
        
        
//           // $tableWhere = $this->tableWhere($post);
// //        $list = $this->with('leiLast')->field($tableWhere['field'])->where($tableWhere['where'])->order($tableWhere['order'])->paginate($limit);
//        // $list = $this->field($tableWhere['field'])->where($tableWhere['where'])->order($tableWhere['order'])->paginate($limit);
//         //$data = $this->tableFormat($list->getCollection());         //返回的数据格式化，并渲染成table所需要的最终的显示数据类型

//          $shixiModel = new ShixiModel();
//         $list= $shixiModel->tableData(input('page/d',1), input('limit/d',1000));

        
//         $list=$list['data'];
//         $b=count($list);
//        // print_r($list) ;exit;
//         //$data = $this->tableFormat($list->getCollection());         //返回的数据格式化，并渲染成table所需要的最终的显示数据类型

//         // var_dump($data) ;exit;
//         //   if(count($data)==count($data)){

//         //     $re['code'] = 0;
//         //     $re['msg'] = '此用户还没有兼职';
//         //     $re['count'] = 0;
//         //     $re['data'] = $all;

//         //     return $re;

//         // }
//         $newarr = array();
//         $all = array();
//         foreach($list as $key => $vol){
//             $job_id = $vol['id'];
//             $sql = 'select * from jshop_job where id ='.$job_id;
//            // $sql = 'select * from jshop_relation where user_id ='.$user_id;
//             $one = $this->query($sql);
//             ///$status = $vol['status'];
//             ///$all[] = $status;
//             foreach($one as $vo){
//                 $newarr = $vo;
//                 $type_id = $vo['type_id'];
//                 $res = explode(',',$type_id);
//                 $new = array();
//                 foreach($res as $vv){
//                     $sql = "select * from jshop_type where id = ".$vv;
//                     $one = $this->query($sql);
//                     $new[] = $one[0]['name'];
//                 }
//                 $newarr['type_id'] = $new;
// //            echo "<pre/>";
//                 $all[] = $newarr;
//             }

//         }

//         if($b=count($all)){
//             $re['code'] = 0;
//         $re['msg'] = '';
//         $re['count'] = 0;
//         $re['data'] =  array();

//         return $re;exit;

//         }

//         $re['code'] = 0;
//         $re['msg'] = '';
//         $re['count'] = $b;
//         $re['data'] = $all;

//         return $re;
//     }

//    public function baoming($token,$post)
//    {
////        var_dump($token);die;
////        string(32) "b963ae865c831d7c9d9232a33c61e99e"
//        $sql = "select * from jshop_user_token where token = '".$token."'";
//        $b = $this->query($sql);
//        foreach($b as $value){
//            $user_id = $value['user_id'];
//        }
//
//        if(isset($post['limit'])){
//            $limit = $post['limit'];
//        }else{
//            $limit = config('paginate.list_rows');
//        }
//        $tableWhere = $this->tableWhere($post);
//
//        $list = $this->field($tableWhere['field'])->where(['user_id'=>$user_id])->order($tableWhere['order'])->paginate($limit);
//        $data = $this->tableFormat($list->getCollection());         //返回的数据格式化，并渲染成table所需要的最终的显示数据类型
//        $newarr = array();
//        $all = array();
//        foreach($list as $key => $vol){
//            $job_id = $vol['job_id'];
//            $sql = 'select * from jshop_job where id ='.$job_id;
//            $one = $this->query($sql);
//            $status = $vol['status'];
//            $all[] = $status;
//            foreach($one as $vo){
//                $newarr = $vo;
//                $type_id = $vo['type_id'];
//                $res = explode(',',$type_id);
//                $new = array();
//                foreach($res as $vv){
//                    $sql = "select * from jshop_type where id = ".$vv;
//                    $one = $this->query($sql);
//                    $new[] = $one[0]['name'];
//                }
//                $newarr['type_id'] = $new;
////            echo "<pre/>";
//                $all[] = $newarr;
//            }
//
//        }
//
//        $re['code'] = 0;
//        $re['msg'] = '';
//        $re['count'] = $list->total();
//        $re['data'] = $all;
//
//        return $re;
//    }

    public function addData($data){

        $validate = new Validate($this->rule,$this->msg);
        $result = ['status' => true, 'msg' => '保存成功' , 'data' => '']; 

        if(!$validate->check($data))
        {
            $result['status'] = false;
            $result['msg'] = $validate->getError();
        } else {
            if (!$this->allowField(true)->save($data)) {
//                echo 11;die;
                $result['status'] = false;
                $result['msg'] = '保存失败';
            }
        }
        return $result;
    }


    protected function tableWhere($post)
    {

        $where = [];
        if(isset($post['name']) && $post['name'] != ""){
            $where[] = ['name', 'like', '%'.$post['name'].'%'];
        }
        if(isset($post['type_id']) && $post['type_id'] != ""){
            $where[] = ['type_id', 'eq', $post['type_id']];
        }

        $result['where'] = $where;
        $result['field'] = "*";
        $result['order'] = [];
        return $result;
    }

    protected function tableFormat($list)
    {
        foreach ( $list as $key => $val ) {
            $list[$key]['ctime'] = date('Y-m-d H:i:s', $val['ctime']);
            $list[$key]['utime'] = date('Y-m-d H:i:s', $val['utime']);
        }
        return $list;
    }
}