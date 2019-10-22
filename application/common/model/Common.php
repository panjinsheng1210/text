<?php
namespace app\common\model;

use think\Model;

class Common extends Model
{
    /**
     * 返回layui的table所需要的格式
     * @author sin
     * @param $post
     * @return mixed
     */
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

    /**
     * 根据输入的查询条件，返回所需要的where
     * @author sin
     * @param $post
     * @return mixed
     */
//    protected function tableWheres($post)
//    {
//        $where = [];
//        if(isset($post['company']) && $post['company'] !=""){
//            $where[] = ['company','eq',$post['company']];
//        }
//        $result['where'] = $where;
//        $result['field'] = "*";
//        $result['order'] = [];
//        return $result;
//    }

    protected function tablet($post)
    {
        $where = [];
        if(isset($post['name']) && $post['name'] != ""){
            $where[] = ['name', 'eq', $post['name']];
        }
        if(isset($post['utime']) && $post['utime'] != ""){
            $date_array = explode('到',$post['utime']);
            $sutime = strtotime($date_array[0].'00:00:00',time());
            $eutime = strtotime($date_array[1].'23:59:59',time());
            $where[] = ['utime', ['EGT',$sutime],['ELT',$eutime],'and'];
        }
        $result['where'] = $where;
        $result['field'] = "*";
        $result['order'] = [];
        return $result;
    }

    protected function tableWhere($post)
    {
        $where = [];
//        $where[] = ['coordinate','not null'];
        if(isset($post['id']) && $post['id'] !=""){
            $where[] = ['id','eq',$post['id']];
        }
        if(isset($post['is_verify']) && $post['is_verify'] !=""){
            $where[] = ['is_verify','eq',$post['is_verify']];
        }
        if(isset($post['type']) && $post['type'] !=""){
            $where[] = ['type','eq',$post['type']];
        }
        $result['where'] = $where;
        $result['field'] = "*";
        $result['order'] = [];
        return $result;
    }

    /**
     * 根据查询结果，格式化数据
     * @author sin
     * @param $list
     * @return mixed
     */
    protected function tableFormat($list)
    {
        return $list;
    }
}