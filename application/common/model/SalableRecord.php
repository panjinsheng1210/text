<?php
/**
 * Created by PhpStorm.
 * User: youtuo-e
 * Date: 2019/5/6
 * Time: 14:55
 */

namespace app\common\model;



use think\Db;

class SalableRecord extends Common
{

    protected $pk = 'id';

    //时间自动存储
    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';
    protected $updateTime = 'utime';
    /**
     * 通用查询列表方法
     * @param $post
     * @return mixed
     * @throws \think\exception\DbException
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
        $data = $this->tableFormat($list->getCollection()); //返回的数据格式化，并渲染成table所需要的最终的显示数据类型

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
    protected function tableWhere($post)
    {
        $where = [];
        if(isset($post['type']) && $post['type'] != "" ){
            $where[] = ['type','eq',$post['type']];
        }
        $result['where'] = $where;
        $result['field'] = "*";
        $result['order'] = ['utime'=>'desc'];
        return $result;
    }

    /**
     * 根据查询结果，格式化数据
     * @param $list //array格式的collection
     * @return mixed
     */
    protected function tableFormat($list)
    {
        foreach($list as $k => $v)
        {
            if($v['ctime'])
            {
                $list[$k]['ctime'] = getTime($v['ctime']);
            }
            if($v['utime'])
            {
                $list[$k]['utime'] = getTime($v['utime']);
            }
            if($v['type']){
                $list[$k]['type'] = config('params.financeType')[$v['type']];
            }
        }
        return $list;
    }

    /**
     * 后台添加单价
     * @param $data
     * @return array
     */
    public function manageAdd($data)
    {
        $return = [
            'status' => false,
            'msg'    => '添加失败',
            'data'   => ''
        ];
        if (!isset($data['type']) || $data['type'] == '') {
            $return['msg'] = '财务类型必填';
            return $return;
        }
        if (!isset($data['money']) || $data['money'] == 0) {
            $return['msg'] = '单价不能为空';
            return $return;
        }
        // 添加单价记录
        $newData['money']   = $data['money'];
        $newData['type']    = $data['type'];
        $newData['ctime']   = time();
        $newData['utime']   = time();
        $result              = $this->save($newData);
        $return['data']      = $this->id;

        if ($result) {
            $return['status'] = true;
            $return['msg']    = '添加成功';
        }
        return $return;
    }
    /**
     * 后台编辑单价
     * @param $data
     * @return array
     */
    public function manageEdit($data){
        $return = [
            'status' => false,
            'msg'    => '参数获取失败',
            'data'   => ''
        ];
        if (!isset($data['id']) || $data['id'] == '') {
            return $return;
        }
        if (!isset($data['type']) || $data['type'] == '') {
            $return['msg'] = '财务类型必填';
            return $return;
        }
        if (!isset($data['money']) || $data['money'] == 0) {
            $return['msg'] = '单价不能为空';
            return $return;
        }
        $result = $this::where('id',$data['id'])->update(['type'=>$data['type'],'money'=>$data['money'],'utime'=>time()]);
        if ($result) {
            $return['status'] = true;
            $return['msg']    = '更新成功';
        }else{
            $return['msg']    = '更新失败';
        }
        return $return;
    }

    /**
     * 删除记录
     * @param int $goods_id
     * @return array
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function delSalable($id = 0)
    {
        $result = [
            'status' => false,
            'data' => [],
            'msg' => '记录不存在'
        ];
        $salable = $this::get($id);
        if (!$salable) {
            return $result;
        }

        $this->startTrans();

        $res = $this->where(['id'=>$id])->delete();
        if (!$res) {
            $this->rollback();
            $result['msg'] = '删除失败';
            return $result;
        }
        $this->commit();
        $result['status'] = true;
        $result['msg'] = '删除成功';
        return $result;
    }

    /**
     *
     *  获取积分单价列表
     * @param $user_id
     * @param $page
     * @param $limit
     * @param string $type  类型
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userSaleList( $page, $limit,$type=4)
    {
        $dealModel = new Deal();
        $sumInfo   = $dealModel->getSumByTime();
        $result = [
            'status' => true,
            'msg' => '获取成功',
            'data' => [],
            'dealNumber'=>$sumInfo['number']?$sumInfo['number']:0,
            'dealMoney' =>$sumInfo['money']?$sumInfo['money']:0,
        ];

        $where = [];
        if(isset($type) && $type != "" ){
            $where[] = ['type','eq',$type];
        }
        $list = $this->where($where)->order('utime desc')->page($page, $limit)->select();
        $count = $this->where($where)->count();
        if (!$list->isEmpty()) {
            $result[ 'data' ]  = $this->tableFormat($list);
            $result[ 'total' ] = ceil($count/$limit);
        }
        return $result;
    }



}