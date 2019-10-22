<?php
/**
 * Created by PhpStorm.
 * User: youtuo-e
 * Date: 2019/5/6
 * Time: 14:55
 */

namespace app\common\model;



use think\Db;
use think\Exception;

class PrizeRecord extends Common
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
        $list = $this::with('userInfo,sourceUserInfo')->field($tableWhere['field'])->where($tableWhere['where'])->order($tableWhere['order'])->paginate($limit);
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
        $where[] = ['currency','neq',0];
        if(isset($post['type']) && $post['type'] != "" ){
            $where[] = ['type','eq',$post['type']];
        }
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
            if($v['type']){
                $list[$k]['type'] = config('params.bonus')['name'][$v['type']];
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
            'mobile'=>'mobile',
        ]);
    }

    /**
     * 关联用户信息
     * @return \think\model\relation\HasOne
     */
    public function sourceUserInfo()
    {
        return $this->hasOne('User', 'id', 'source_id')->bind([
            'mobile_source'=>'mobile',
        ]);
    }

    public function addPrizeRecord($data=[]){
        $result = ['status' => true, 'msg' => '保存成功','data' => ''];
        if(!$this->save($data))
        {
            $result['status'] = false;
            $result['msg'] = '保存失败';
        }
        return $result;
    }


    /**
     * 删除记录
     * @param int $goods_id
     * @return array
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function delPrizeRecord($id = 0)
    {
        $result = [
            'status' => false,
            'data' => [],
            'msg' => '记录不存在'
        ];
        $info = $this::get($id);
        if (!$info) {
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
     *  获取奖金明细列表
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
    public function userPrizeList($user_id, $page, $limit,$type='')
    {
        $result = [
            'status' => true,
            'msg' => '获取成功',
            'data' => []
        ];

        $where = [];
        if(isset($type) && $type != "" ){
            $where[] = ['type','eq',$type];
        }
        if ( isset($user_id) && $user_id != "" ) {
            $where[] = [ 'user_id', 'eq', $user_id ];
        }
        $list = $this->where($where)->order('ctime desc')->page($page, $limit)->select();
        $count = $this->where($where)->count();
        if (!$list->isEmpty()) {
            $result[ 'data' ] = $this->tableFormat($list);
            $result[ 'total' ] = ceil($count/$limit);
        }
        return $result;
    }

    /**发放奖金 带封顶
     * @param $userId 发放会员
     * @param $sourceId 来源会员
     * @param $currency 金额
     * @param $type 奖金类型 2 极差奖
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function bonusCapping($userId, $sourceId, $currency, $type) {
        Db::startTrans();
        try {
            $userModel = new User();
            $userInfo = $userModel::withTrashed()->where([ 'id' => $userId ])->find();
            if($userInfo) {
                if ($userInfo->capping_money >0) {
                    $money = ($userInfo->capping_money >= $currency) ? $currency : $userInfo->capping_money;
                    switch ($type) {
                        case 2:
                            $userModel = User::get($sourceId);
                            $balanceType = 14;
                            $memo = '极差奖增加'.$money.'元,来源用户:'.$userModel->mobile;
                            break;
                        case 3:
                            $balanceType = 16;
                            $memo = '分红奖增加'.$money.'元';
                            break;
                        case 4:
                            $balanceType = 17;
                            $memo = '直推分红奖增加'.$money.'元';
                            break;
                        case 5:
                            $userModel = User::get($sourceId);
                            $balanceType = 18;
                            $memo = '平级奖增加'.$money.'元,来源用户:'.$userModel->mobile;
                            break;
                        case 6:
                            $userModel = User::get($sourceId);
                            $balanceType = 19;
                            $memo = '分享增加'.$money.'元,来源用户:'.$userModel->mobile;
                            break;
                        case 7:
                            $userModel = User::get($sourceId);
                            $balanceType = 21;
                            $memo = '销售提成'.$money.'元,来源用户:'.$userModel->mobile;
                            break;
                        case 8:
                            $userModel = User::get($sourceId);
                            $balanceType = 22;
                            $memo = '超越奖金增加'.$money.'元,来源用户:'.$userModel->mobile;
                            break;
                        case 9:
                            $userModel = User::get($sourceId);
                            $balanceType = 23;
                            $memo = '补货提成增加'.$money.'元,来源用户:'.$userModel->mobile;
                            break;
                        case 10:
                            $balanceType = 24;
                            $memo = '三钻奖金(星级提成)增加'.$money.'元';
                            break;
                        case 11:
                            $balanceType = 24;
                            $memo = '三钻奖金(钻级提成)增加'.$money.'元';
                            break;
                        case 12:
                            $balanceType = 24;
                            $memo = '三钻奖金(补货提成)增加'.$money.'元';
                            break;
                    }
                    $data['user_id']   = $userId;
                    $data['type']      = $balanceType;
                    $data['money']     = $money;
                    $data['balance']   = $userInfo[ 'balance' ] + $money;
                    $data['source_id'] = $sourceId;
                    $data['memo']      = $memo;
                    $data['ctime']     = time();
                    $blanceModel = new Balance();
                    $blanceModel->save($data);
                    if (in_array($type, [10,11,12])) {
                        $type = 10;
                    }
                    (new PrizeRecord())->addPrizeRecord(['currency'=>$money,'user_id'=>$userId,'source_id'=>$sourceId,'type'=>$type,'ctime'=>time(),'meno'=>$memo]);
                    $userInfo->balance = $data['balance'];
                    $userInfo->capping_money -= $money;
                    $userInfo->save();
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            throw $e;
            Db::rollback();
        }
    }
}