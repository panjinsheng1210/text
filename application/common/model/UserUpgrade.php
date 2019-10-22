<?php
/**
 * Created by PhpStorm.
 * User: youtuo-e
 * Date: 2019/5/6
 * Time: 14:55
 */

namespace app\common\model;



use think\Db;

class UserUpgrade extends Common
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
        $list = $this::with('userInfo,newLevel,oldLevel')->field($tableWhere['field'])->where($tableWhere['where'])->order($tableWhere['order'])->paginate($limit);
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
        if ( isset($post[ 'user_id' ]) && $post[ 'user_id' ] != "" ) {
            $where[] = [ 'user_id', 'eq', $post[ 'user_id' ] ];
        } else {
            if ( isset($post[ 'mobile' ]) && $post[ 'mobile' ] != "" ) {
                if ( $user_id = get_user_id($post[ 'mobile' ]) ) {
                    $where[] = [ 'user_id', 'eq', $user_id ];
                } else {
                    $where[] = [ 'user_id', 'eq', '99999999' ];       //如果没有此用户，那么就赋值个数值，让他查不出数据
                }
            }
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
        }
        return $list;
    }



    /**
     * 关联用户信息
     * @return \think\model\relation\HasOne
     */
    public function userInfo()
    {
        return $this->hasOne('User', 'id', 'user_id')->bind([
            'mobile',
            'nickname'
        ]);
    }

    public function newLevel(){
        return $this->hasOne('UserGrade', 'id', 'upgrade_type')->bind([
            'level_new'=>'name',
        ]);
    }

    public function oldLevel(){
        return $this->hasOne('UserGrade', 'id', 'upgrade_old_type')->bind([
            'level_old'=>'name',
        ]);
    }


    /**
     * 后台升级
     * @param $data
     * @return array
     */
    public function manageAdd($data)
    {
        $return = [
            'status' => false,
            'msg'    => '升级失败',
            'data'   => ''
        ];
        $status = true;

        if (!isset($data['mobile']) || $data['mobile'] == '') {
            $return['msg'] = '手机号必填';
            return $return;
        }
        if (!isMobile($data['mobile'])) {
            $return['msg'] = '请输入正确的手机号';
            return $return;
        }
        if(!get_user_id($data['mobile'])){
            $return['msg'] = '用户不存在';
            return $return;
        }
        if (!isset($data['upgrade_type']) || $data['upgrade_type'] == '') {
            $return['msg'] = '会员等级必填';
            return $return;
        }
        Db::startTrans();
        $userModel = User::get(get_user_id($data['mobile']));
        // 添加升级记录
        $time                            = time();
        $newData['user_id']              = get_user_id($data['mobile']);
        $newData['upgrade_type']         = $data['upgrade_type'];
        $newData['upgrade_old_type']     = $userModel->grade;
        $newData['ctime']       = $time;
        $result         = $this->save($newData);
        $return['data'] = $this->id;
        // 更改会员等级
        $userModel->grade = $data['upgrade_type'];

        if ($result &&  $userModel->save()) {
            Db::commit();
//            if (session('manage.id')) {
//                $userLogModel = new UserLog();
//                $userLogModel->setLog(session('manage.id'), $userLogModel::USER_REG);
//            }
            $return['status'] = true;
            $return['msg']    = '升级成功';

        }else{
            Db::rollback();
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
    public function delUpgrade($id = 0)
    {
        $result = [
            'status' => false,
            'data' => [],
            'msg' => '记录不存在'
        ];
        $upgrade = $this::get($id);
        if (!$upgrade) {
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

}