<?php
namespace app\common\model;
use think\Validate;

class Shixis extends Common
{
    protected $name = 'job';  

    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';
    protected $updateTime = 'utime';


    protected $rule =   [
        'name'              =>  'require|max:50',  
        'sort'              =>  'number',
    ];

    protected $msg  =   [
        'sort'          =>  '排序必须是数字',
        'name.require'  => '请输入类型名称',
    ];

    public function tableData($post)
    {
        if(isset($post['limit'])){
            $limit = $post['limit'];
        }else{
            $limit = config('paginate.list_rows');
        }
        $tableWhere = $this->tableWhere($post);
        $state=$post['state'];
        //$state=1;
        file_put_contents('./a2.txt', print_r($state,true));
//        $list = $this->with('leiLast')->field($tableWhere['field'])->where($tableWhere['where'])->order($tableWhere['order'])->paginate($limit);
        
//        $list = $this->field($tableWhere['field'])->where(['user_id'=>$user_id])->order($tableWhere['order'])->paginate($limit);
        $list = $this->field($tableWhere['field'])->where(['state'=>$state])->order($tableWhere['order'])->paginate($limit);
        $data = $this->tableFormat($list->getCollection());         //返回的数据格式化，并渲染成table所需要的最终的显示数据类型
        $newarr = array();
        $all = array();
        foreach($list as $k => $v){
            $newarr = $v;
            $type_id = $v['type_id'];
            $res = explode(',',$type_id);
            $new = array();
            foreach($res as $vv){
                $sql = "select * from jshop_type where id = ".$vv;
                $one = $this->query($sql);
                $new[] = $one[0]['name'];
            }
//            $news = implode(',',$new);
            $newarr['type_id'] = $new;
//            echo "<pre/>";
//            print_r($newarr);
            $all[] = $newarr;
        }

        $re['code'] = 0;
        $re['msg'] = '';
        $re['count'] = $list->total();
        $re['data'] = $all;

        return $re;
    }

    public function addData($data)
    {
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

    public function saveData($data)
    {
        $result = [
            'status' => true,
            'msg' => '保存成功',
            'data' => []
        ];

        $validate = new Validate($this->rule,$this->msg);
        if (!$validate->check($data)) {
            $result['status'] = false;
            $result['msg'] = $validate->getError();
        } else {
            if (!$this->allowField(true)->save($data,['id'=>$data['id']]))
            {
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
            $list[$key]['company'] = config('params.danwei')['company'][$val['company']];
            $list[$key]['worry'] = config('params.ji')['worry'][$val['worry']];
            $list[$key]['ctime'] = date('Y-m-d H:i:s', $val['ctime']);
            $list[$key]['utime'] = date('Y-m-d H:i:s', $val['utime']);
        }
        return $list;
    }


    /**
     *  API
     *  兼职详情展示
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function detailsList($id,$post)
    {
        if(isset($post['limit'])){
            $limit = $post['limit'];
        }else{
            $limit = config('paginate.list_rows');
        }

        $tableWheres = $this->tableWhere($post);
        $list = $this->field($tableWheres['field'])->where(['id'=>$id])->order($tableWheres['order'])->paginate($limit);
        $data = $this->tableFormat($list->getCollection());         //返回的数据格式化，并渲染成table所需要的最终的显示数据类型
//        处理数组入库，展示
        $newarr = array();
        $all = array();
        foreach($list as $k => $v){
            $newarr = $v;
            $type_id = $v['type_id'];
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
        

        if(count($all)<=0){
            $re['state'] = 'false';
        }else{
            $re['state'] = 'true';
        }
        
        $re['code'] = 0;
        $re['msg'] = '';
        $re['count'] = $list->total();
        $re['data'] = $all;

        return $re;

    }

    /**
     *  关联类型表
     * @return \think\model\relation\HasOne
     */
    public function leiLast()
    {
        return $this->hasOne('Leilast','id','type_id')->bind(['pname'=>'name']);
    }

    /**
     * API
     */

    public function del($id){
        $where[] = array('id','eq',$id);
        $res = $this->where($where)->delete();
        return $res;
    }



}