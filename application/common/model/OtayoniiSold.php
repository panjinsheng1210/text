<?php
/**
 * Created by PhpStorm.
 * User: zhangchong
 * Date: 2019/5/18
 * Time: 17:29
 */
namespace app\common\model;
use think\Validate;
use think\Db;
use think\model\concern\SoftDelete;


class OtayoniiSold extends Common
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';
    protected $updateTime = 'utime';

    const TYPE_WITE = 1;          //等待审核
    const TYPE_SUCCESS = 2;        //提现成功
    const TYPE_FAIL = 3;          //提现失败

    public function tocash($user_id, $money)
    {
        $result = [
            'status' => false,
            'msg' => '',
            'data' => ''
        ];
        //最低提现金额
        if($money < 100){
            $result['msg'] = "出售数量最低不能少于100个";
            return $result;
        }
        $userModel = new User();
        $userInfo = $userModel->getUserInfo($user_id);
        if (!$userInfo) {
            return error_code(11004);
        }
        if ($money > $userInfo['otayonii']) {
            return error_code(12002);
        }
        // 计算提现服务费(金额)
        $cateMoney = $money*0.3;
        if (($money + $cateMoney) > $userInfo['otayonii']) {
            return error_code(12002);
        }
        //返回金豆的钱数
        $otayoniiModel = new OtayoniiPrice();
        $bean = $otayoniiModel->field('price')->where("user_id=$user_id")->find();
        $beanPrince = $bean['price'];
        $data['user_id'] = $user_id;
        $data['bean_num'] = $money;
        $data['bean_price'] = sprintf("%.2f",$beanPrince/$userInfo['otayonii']);
        $data['money'] = sprintf("%.2f",$data['bean_price']*$data['bean_num']);
        $data['withdrawals'] = $cateMoney;
        $re = $this->save($data);
        if ($re) {
            //user表里面的金豆数减少
            $user['otayonii'] = $userInfo['otayonii']-($money + $cateMoney);
            $userModel->save($user,['id' => $user_id]);
            //otayonii_price表里面的钱数减少
            $otayonii['price'] = $beanPrince-($data['bean_price']*($money + $cateMoney));
            $otayoniiModel->save($otayonii,['user_id' => $user_id]);
            $shopOtayoniiModel = new ShopOtayonii();
            $memo="金豆售出".$money."个,手续费".$cateMoney."个";
            $money = -($money+$cateMoney);
            $balanceData = [
                'user_id' => $user_id,
                'type' => 16,
                'num' => $money,
                'balance' => $user['otayonii'],
                'remarks' => $memo,
                'finance_id'=>2,
                'ctime' => time()
            ];
            $shopOtayoniiModel->save($balanceData);
            $result['status'] = true;
            $result['msg'] = '金豆售出成功';
            return $result;
        } else {
            $result['msg'] = "金豆售出失败";
            return $result;
        }
    }
    /**
     * 返回layui的table所需要的格式
     *
     * @author sin
     *
     * @param $post
     *
     * @return mixed
     */
    public function tableData( $post )
    {
        if ( isset($post[ 'limit' ]) ) {
            $limit = $post[ 'limit' ];
        } else {
            $limit = config('paginate.list_rows');
        }
        $tableWhere = $this->tableWhere($post);
        $list = $this::with('userInfo')->field($tableWhere[ 'field' ])->where($tableWhere[ 'where' ])->order($tableWhere[ 'order' ])->paginate($limit);
        $data = $this->tableFormat($list->getCollection());         //返回的数据格式化，并渲染成table所需要的最终的显示数据类型

        $re[ 'code' ] = 0;
        $re[ 'msg' ] = '';
        $re[ 'count' ] = $list->total();
        $re[ 'data' ] = $data;
        $re[ 'sql' ] = $this->getLastSql();

        return $re;
    }

    protected function tableWhere( $post )
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

        if ( isset($post[ 'type' ]) && $post[ 'type' ] != "" ) {
            $where[] = [ 'type', 'eq', $post[ 'type' ] ];
        }
        $result[ 'where' ] = $where;
        $result[ 'field' ] = "*";
        $result[ 'order' ] = 'ctime desc';
        return $result;
    }

    /**
     * 根据查询结果，格式化数据
     *
     * @author sin
     *
     * @param $list  array格式的collection
     *
     * @return mixed
     */
    protected function tableFormat( $list )
    {
        foreach ( $list as $k => $v ) {
            if ( $v[ 'ctime' ] ) {
                $list[ $k ][ 'ctime' ] = getTime($v[ 'ctime' ]);
            }
            if ( $v[ 'utime' ] ) {
                $list[ $k ][ 'utime' ] = getTime($v[ 'utime' ]);
            }
            if ( $v[ 'type' ] ==1) {
                $list[ $k ][ 'type' ] = '待审核';
            }else if($v['type']==2){
                $list[ $k ][ 'type' ] = '已通过';
            }else if($v['type']==3){
                $list[ $k ][ 'type' ] = '已驳回';
            }
        }
        return $list;
    }
    public function userInfo()
    {
        return $this->hasOne('User','id','user_id')->bind([
            'mobile'
        ]);
    }
    //提现审核
    public function examine($id,$type){
        $result = [
            'status' => false,
            'msg' => '',
            'data' => ''
        ];
        $where['id'] = $id;
        $where['type'] = self::TYPE_WITE;
        $info = $this->where($where)->find();
        if(!$info){
            $result['msg'] = "没有此记录或不是待审核状态";
            return $result;
        }
        if($type>1){
            $res = $this->save(['type'=>$type],$where);
            $result['status'] = true;
            $result['data'] = $type;
            if($res !== false)
            {
                //失败给用户退钱到余额
                if($type == self::TYPE_FAIL)
                {
                    $tocash = $this->get($id);
                    $userModel = new User();
                    $userWhere[] = ['id', 'eq', $tocash['user_id']];
                    // 提现金额 加 服务费返还
                    $r = $userModel->where($userWhere)->inc('otayonii',$tocash['bean_num'] + $tocash['withdrawals'])->update();
                    if($r !== false)
                    {
                        //添加记录
                        $newUserInfo = $userModel->get($tocash['user_id']);
                        $shopOtayoniiModel = new ShopOtayonii();
                        $num = $tocash['bean_num']+$tocash['withdrawals'];
                        $balanceData = [
                            'user_id' => $tocash['user_id'],
                            'type' => 16,
                            'num' => $num,
                            'balance' => $newUserInfo['otayonii'],
                            'remarks' => "金豆售出被驳回退款".$num.'个',
                            'finance_id'=>2,
                            'ctime' => time()
                        ];
                        $shopOtayoniiModel->save($balanceData);
                        //返回金豆的钱数
                        $otayoniiModel = new OtayoniiPrice();
                        $otayooiiWhere[] = ['user_id','eq',$tocash['user_id']];
                        $price = $tocash['bean_price']*($tocash['bean_num'] + $tocash['withdrawals']);
                        $otayoniiModel->where($otayooiiWhere)->inc('price',$price)->update();
                    }
                }
            }
            return $result;
        }else{
            return error_code(10000);
        }
    }
    /**
     *
     *  获取用户金豆记录列表
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
    public function beanSoldList($user_id, $page)
    {
        $result = [
            'status' => true,
            'msg' => '获取成功',
            'data' => []
        ];

        $where = [];
        $where[] = ['user_id', 'eq', $user_id];
        $list = $this->where($where)->order('ctime desc')->page($page)->select();
        $count = $this->where($where)->count();

        if (!$list->isEmpty()) {
            foreach ($list as $v) {
                $v['type'] = config('params.bean_sold')['type'][$v['type']];
                $v['ctime'] = getTime($v['ctime']);
            }
            $result[ 'data' ] = $list;
            $result[ 'total' ] = ceil($count);
        }
        return $result;
    }

}