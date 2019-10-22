<?php
namespace app\common\model;
use think\Validate;

class Student extends Common
{
    protected $name = 'student';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';
    protected $updateTime = 'utime';


    protected $rule = [
        'student_id' => 'require|max:50',
        'mobile' => ['regex' => '^1[3|4|5|7|8][0-9]\d{4,8}$'],
        'id_card' => ['regex' => '/(^\d{15}$)|(^\d{17}(x|X|\d)$)/'],
        'sex' => 'in:1,2',
        'email' => 'email',
    ];

    protected $msg = [
        'student_id.require' => '请输入学号',
        'sex' => '请选择合法的性别',
        'mobile' => '请输入合法的手机号码',
        'id_card' => '请输入身份证',
        'email' => '请输入邮箱',
    ];

    public function tableData($post)
    {
        if(isset($post['limit'])){
            $limit = $post['limit'];
        }else{
            $limit = config('paginate.list_rows');
        }
        $tableWhere = $this->tableWhere($post);
        $list = $this->field($tableWhere['field'])->where($tableWhere['where'])->order($tableWhere['order'])->paginate($limit);
        $data = $this->tableFormat($list->getCollection());         //返回的数据格式化，并渲染成table所需要的最终的显示数据类型

        $re['code'] = 0;
        $re['msg'] = '';
        $re['count'] = $list->total();
        $re['data'] = $data;

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

    public function saveData($data){

       // file_put_contents('./a.txt', print_r($data,true));
        $validate = new Validate($this->rule,$this->msg);
        $result = ['status' => true, 'msg' => '保存成功' , 'data' => ''];
        //$this->allowField(true)->save($data,['user_id'=>$data['user_id']]);
        if(!$validate->check($data))
        {
            $result['status'] = false;
            $result['msg'] = $validate->getError();
        } else {
            if (!$this->allowField(true)->save($data,['user_id'=>$data['user_id']])) {
                $result['status'] = false;
                $result['msg'] = '保存失败';
            }
        }
        return $result;
    }

    protected function tableWhere($post)
    {
        $where = [];
        $result['where'] = $where;
        $result['field'] = "*";
        $result['order'] = [];
        return $result;
    }


    protected function tableFormat($list)
    {
        foreach ( $list as $key => $val ) {
            $list[$key]['sex'] = config('params.user')['sex'][$val['sex']];
            $list[$key]['ctime'] = date('Y-m-d H:i:s', $val['ctime']);
            $list[$key]['utime'] = date('Y-m-d H:i:s', $val['utime']);
        }
        return $list;
    }

}