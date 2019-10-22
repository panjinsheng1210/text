<?php
namespace app\common\model;
use think\Validate;

class Leilast extends Common
{
    protected $name = 'type';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';
    protected $updateTime = 'utime';
    //验证
    protected $rule =   [
        'name'              => 'require|max:50',
        'sort'              =>  'number',
    ];
    protected $msg  =   [
        'sort'          =>  '排序必须是数字',
        'name.max'      => '类型名称长度最大50位',
        'name.require'  => '请输入类型名称',
    ];


    public function tableData($post)
    {
        if(isset($post['limit'])){
            $limit = $post['limit'];
        }else{
            $limit = config('paginate.list_rows');
        }

        $tableWhere = $this->tablet($post);
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
        $result = [
            'status' => true,
            'msg' => '保存成功',
            'data' => ''
        ];
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
        $validate = new Validate($this->rule,$this->msg);
        $result = [
            'status' => true,
            'msg' => '编辑成功',
            'data' => []
        ];
        if (!$validate->check($data)) {

            $result['status'] = false;
            $result['msg'] = $validate->getError();
        } else {
            if (!$this->allowField(true)->save($data,['id'=>$data['id']]))
            {
                $result['status'] = false;
                $result['msg'] = '编辑失败';
            }
        }
        return $result;
    }

    protected function tableFormat($list)
    {
//        foreach ( $list as $key => $val ) {
//            $list[$key]['ctime'] = date('Y-m-d H:i:s', $val['ctime']);
//            $list[$key]['utime'] = date('Y-m-d H:i:s', $val['utime']);
//        }
//        return $list;
        foreach($list as $val){
            $val['ctime'] = getTime($val['ctime']);
            $val['utime'] = getTime($val['utime']);
        }
        return $list;
    }

    /**
     *  一对多关联
     * User:tianyu
     * @return \think\model\relation\HasMany
     */
    public function shixi()
    {
        return $this->hasMany('Shixi','type_id','id');
    }



    /**
     *  API
     *  获取类型列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function leilastgetList()
    {
        $result = [
            'status' => true,
            'msg' => '获取成功',
            'data' => []
        ];

        $field = 'id,name,sort';

        $page = input('param.page', 1);
        $limit = input('param.limit', 10);

        $list = $this->field($field)->page($page, $limit)->select();
        $count  = $this->field($field)->count();

        $result['data'] = [
            'list' => $list,
            'count' => $count
        ];

        return $result;

    }

    /**
     * 删除
     * @param $id
     * @return int
     */
    public function del($id = "")
    {
        $where[] = array('id', 'eq', $id);
        $res = $this->where($where)
            ->delete();
        return $res;
    }



}