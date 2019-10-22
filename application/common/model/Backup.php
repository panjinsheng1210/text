<?php
/**
 * Created by PhpStorm.
 * User: youtuo-e
 * Date: 2019/5/6
 * Time: 14:55
 */

namespace app\common\model;



use think\Db;

class Backup extends Common
{

    protected $pk = 'id';

    //时间自动存储
    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';
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
        $list = $this::field($tableWhere['field'])->where($tableWhere['where'])->order($tableWhere['order'])->paginate($limit);
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
        if(isset($post['type']) && !empty($post['type'])){
            $where[] = ['type','eq',$post['type']];
        }
        $result['where'] = $where;
        $result['field'] = "*";
        $result['order'] = ['id'=>'desc'];
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
            if($v['backup_time'])
            {
                $list[$k]['backup_time'] = getTime($v['backup_time']);
            }
            if($v['recovery_time'])
            {
                $list[$k]['recovery_time'] = getTime($v['recovery_time']);
            }

        }
        return $list;
    }

    /**
     * 添加备份记录
     * @param $data
     * @return array
     */
    public function backupAdd($data)
    {
        $return = [
            'status' => false,
            'msg'    => '添加失败',
            'data'   => ''
        ];
        if(!isset($data['table']) || !$data['table']){
            $return['msg'] = '请选择要添加的功能';
            return $return;
        }
        // 添加记录
        $cleanTable = config('params.cleanTable');
        $newData['name']  = $data['table'] == 'all'? '全部':$cleanTable[$data['table']];
        $newData['table'] = $data['table'];
        $newData['type']  = 1;
        $newData['ctime'] = time();
        if($this->save($newData)){
            $return['status'] = true;
            $return['msg']    = '添加成功';
        }
        return $return;
    }

    /**
     * 删除
     * @param int $goods_id
     * @return array
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function delBackup($id = 0)
    {
        $result = [
            'status' => false,
            'data' => [],
            'msg' => '记录不存在'
        ];
        $backup = $this::get($id);
        if (!$backup) {
            return $result;
        }
        $delFile = true;
        $this->startTrans();
        if($backup->file_path){
            $Baksql = new \org\Baksql(Db::getConfig());
            $delFile = $Baksql->delfilename($backup->file_path);
        }
        if($this->where(['id'=>$id])->delete() && $delFile){
            $result['status'] = true;
            $result['msg'] = '删除成功';
            $this->commit();
        }else{
            $result['msg'] = '删除失败';
            $this->rollback();
        }
        return $result;
    }
    // 备份
    public function backup($id){
        $result = [
            'status' => false,
            'msg' => '记录不存在'
        ];
        $backup = $this::get($id)->toArray();
        if (!$backup) {
            return $result;
        }
        if($backup['table'] == 'all'){
            $tableArr = [];
        }else{
            $tableArr[] = $backup['table'];
        }
        if($backup['table'] == 'jshop_bill_aftersales'){
            $tableArr[] = 'jshop_bill_aftersales_images';
            $tableArr[] = 'jshop_bill_aftersales_items';
        }elseif ($backup['table'] == 'jshop_bill_delivery'){
            $tableArr[] = 'jshop_bill_delivery_items';
        }elseif ($backup['table'] == 'jshop_bill_payments'){
            $tableArr[] = 'jshop_bill_payments_rel';
        }elseif ($backup['table'] == 'jshop_bill_reship'){
            $tableArr[] = 'jshop_bill_reship_items';
        }elseif ($backup['table'] == 'jshop_order'){
            $tableArr[] = 'jshop_order_items';
            $tableArr[] = 'jshop_order_log';
        }elseif ($backup['table'] == 'jshop_user'){
            $tableArr[] = 'jshop_user_bankcards';
            $tableArr[] = 'jshop_user_ship';
            $tableArr[] = 'jshop_user_token';
            $tableArr[] = 'jshop_user_wx';
        }
        $Baksql = new \org\Baksql(Db::getConfig());
        $result = $Baksql->backup($tableArr);
        if($result['success']){
            $res = $this->update(['file_path'=>$result['data'],'backup_time'=>time()],['id'=>$id]);
            if(!$res){
                $result['success'] = false;
                $result['msg']     = '备份失败';
            }
        }
        return $result;
    }

    /**
     * 恢复
     * @param int $goods_id
     * @return array
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function restore($id = 0)
    {
        ini_set("max_execution_time", 10*60);
        $result = [
            'status' => false,
            'data' => [],
            'msg' => '记录不存在'
        ];
        $backup = $this::get($id);
        if (!$backup) {
            return $result;
        }
        if(!file_exists(ROOT_PATH . 'public' . DS .'static'. DS .'data/'.$backup->file_path)){
            $result['msg'] = '目录不存在';
            return $result;
        }
        $Baksql = new \org\Baksql(Db::getConfig());
        if($Baksql->restore($backup->file_path)){
            $update = ['recovery_time'=>time(),'backup_time'=>$backup->backup_time,'file_path'=>$backup->file_path];
            (new Backup())->update($update,['id'=>$id]);
            $result['status'] = true;
            $result['msg'] = '恢复成功';
        }else{
            $result['msg'] = '恢复失败';
        }
        return $result;
    }

    /**
     * 下载
     * @param int $goods_id
     * @return array
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function dowonload($id = 0)
    {
        $result = [
            'status' => false,
            'data' => [],
            'msg' => '记录不存在'
        ];
        $backup = $this::get($id);
        if (!$backup) {
            return $result;
        }
        if(!file_exists(ROOT_PATH . 'public' . DS .'static'. DS .'data/'.$backup->file_path)){
            $result['msg'] = '目录不存在';
            return $result;
        }
        $Baksql = new \org\Baksql(Db::getConfig());
        return $Baksql->downloadFile($backup->file_path);
        $result['status'] = true;
        $result['msg'] = '下载成功';
        return $result;
    }
}