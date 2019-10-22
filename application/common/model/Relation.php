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
    
    //兼职信息列表
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
        $list = $this->field($tableWhere['field'])->where($where)->order($tableWhere['order'])->paginate($limit);
        $data = $this->tableFormat($list->getCollection());         //返回的数据格式化，并渲染成table所需要的最终的显示数据类型
        $newarr = array(); 
        $all = array();
      // echo "<pre>" ;
        
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

            $sql = 'select * from jshop_job where id ='.'"'.$job_id.'"';
           // $sql = 'select * from jshop_relation where user_id ='.$user_id;
            $one = $this->query($sql);
            foreach($one as $vo){

                $vo['ctime']= date('Y-m-d',$vo['ctime']);
                $vo['utime']= date('Y-m-d',$vo['utime']);
                $newarr = $vo;
                $type_id = $vo['type_id'];
                $res = explode(',',$type_id);
                $new = array();
                foreach($res as $vv){
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

    //实习信息列表
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
        $list = $this->field($tableWhere['field'])->where($where)->order($tableWhere['order'])->paginate($limit);
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
            $sql = 'select * from jshop_job where id ='.'"'.$job_id.'"';
           // $sql = 'select * from jshop_relation where user_id ='.$user_id;
            $one = $this->query($sql);
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
                $all[] = $newarr;
            }

        }
        $re['code'] = 0;
        $re['msg'] = '';
        $re['count'] = count($all);
        $re['data'] = $all;
        return $re;
    }
    

    //进行添加报名实习兼职 查询是否已经报名
    public function baoming1($user_id,$post)
    {
        if(isset($post['limit'])){
            $limit = $post['limit'];
        }else{
            $limit = config('paginate.list_rows');
        }
        $tableWhere = $this->tableWhere($post);

          $where = [];
        $sta=$post['job_id'];
         $where[] = ['user_id', 'eq', $user_id];
         $where[] = ['job_id', 'eq', $sta];
        

        //var_dump($this->tableData());exit;
        $list = $this->field($tableWhere['field'])->where($where)->order($tableWhere['order'])->paginate($limit);

        $data = $this->tableFormat($list->getCollection());         //返回的数据格式化，并渲染成table所需要的最终的显示数据类型

        $newarr = array();
        $all = array();
        foreach($list as $key => $vol){
            $job_id = $vol['job_id'];
            $sql = 'select * from jshop_job where id ='.'"'.$job_id.'"';
            $one = $this->query($sql);
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
                $all[] = $newarr;
            }

        }

        $re['code'] = 0;
        $re['msg'] = '';
        $re['count'] = count($all);
        $re['data'] = $all;

        return $re;
    }



    public function addData($data){

        $validate = new Validate($this->rule,$this->msg);
        $result = ['status' => true, 'msg' => '保存成功' , 'data' => '']; 

        if(!$validate->check($data))
        {
            $result['status'] = false;
            $result['msg'] = $validate->getError();
        } else {
            if (!$this->allowField(true)->save($data)) {
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