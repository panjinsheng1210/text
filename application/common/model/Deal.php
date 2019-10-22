<?php
/**
 * Created by PhpStorm.
 * User: youtuo-e
 * Date: 2019/5/6
 * Time: 14:55
 */

namespace app\common\model;



use function GuzzleHttp\Psr7\str;
use think\Db;

class Deal extends Common
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
        $list = $this::with('buyUserInfo,saleUserInfo')->field($tableWhere['field'])->where($tableWhere['where'])->order($tableWhere['order'])->paginate($limit);
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
        if ( isset($post[ 'buy_user_id' ]) && $post[ 'buy_user_id' ] != "" ) {
            $where[] = [ 'buy_user_id', 'eq', $post[ 'buy_user_id' ] ];
        } else {
            if ( isset($post[ 'mobile_buy' ]) && $post[ 'mobile_buy' ] != "" ) {
                if ( $user_id = get_user_id($post[ 'mobile_buy' ]) ) {
                    $where[] = [ 'buy_user_id', 'eq', $user_id ];
                } else {
                    $where[] = [ 'buy_user_id', 'eq', '99999999' ];       //如果没有此用户，那么就赋值个数值，让他查不出数据
                }
            }
        }
        if ( isset($post[ 'sale_user_id' ]) && $post[ 'sale_user_id' ] != "" ) {
            $where[] = [ 'sale_user_id', 'eq', $post[ 'sale_user_id' ] ];
        } else {
            if ( isset($post[ 'mobile_sale' ]) && $post[ 'mobile_sale' ] != "" ) {
                if ( $user_id = get_user_id($post[ 'mobile_sale' ]) ) {
                    $where[] = [ 'sale_user_id', 'eq', $user_id ];
                } else {
                    $where[] = [ 'sale_user_id', 'eq', '99999999' ];       //如果没有此用户，那么就赋值个数值，让他查不出数据
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

    public function buyUserInfo()
    {
        return $this->hasOne('User','id','buy_user_id')->bind([
            'mobile_buy'=>'mobile'
        ]);
    }
    public function saleUserInfo()
    {
        return $this->hasOne('User','id','sale_user_id')->bind([
            'mobile_sale'=>'mobile'
        ]);
    }

    // 根据时间获取总数量以及总金额
    public function getSumByTime(){
        $where[]     = array('ctime', ['>=', strtotime(date("Y-m-01"))], ['<=', strtotime(date("Y-m-t"))], 'and');
        return $this->field(["ifnull(sum(currency),0)"=>'number','ifnull(sum(currency*price),0)'=>'money'])->where($where)->find();
    }

    public function getSum($month){
        $where[] = ['ctime',['>=',strtotime(date("Y-$month-01"))],['<=',strtotime(date("Y-$month-t"))],'and'];
        return $this->field(["ifnull(sum(currency),0)"=>'money'])->where($where)->find();
    }


}