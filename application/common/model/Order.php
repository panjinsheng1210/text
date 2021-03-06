<?php
namespace app\common\model;

use addons\Distribution\Distribution;
use think\Exception;
use think\model\concern\SoftDelete;
use think\Db;

/**
 * 订单主表
 * Class Order
 * @package app\common\model
 * @author keinx
 */
class Order extends Common
{
    protected $pk = 'order_id';

    use SoftDelete;
    protected $deleteTime = 'isdel';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';
    protected $updateTime = 'utime';

    const ORDER_STATUS_NORMAL = 1;          //订单状态正常
    const ORDER_STATUS_COMPLETE = 2;        //订单状态完成
    const ORDER_STATUS_CANCEL = 3;          //订单状态取消

    const PAY_STATUS_NO = 1;                //未付款
    const PAY_STATUS_YES = 2;               //已付款
    const PAY_STATUS_PARTIAL_YES = 3;       //部分付款
    const PAY_STATUS_PARTIAL_NO = 4;        //部分退款
    const PAY_STATUS_REFUNDED = 5;          //已退款

    const SHIP_STATUS_NO = 1;               //未发货
    const SHIP_STATUS_PARTIAL_YES = 2;      //部分发货
    const SHIP_STATUS_YES = 3;              //已发货
    const SHIP_STATUS_PARTIAL_NO = 4;       //部分退货
    const SHIP_STATUS_RETURNED = 5;         //已退货

    const RECEIPT_NOT_CONFIRMED = 1;        //未确认收货
    const CONFIRM_RECEIPT = 2;              //确认收货

    const NO_COMMENT = 1;                   //没有评价
    const ALREADY_COMMENT = 2;              //已经评价

    const ALL_PENDING_PAYMENT = 1;          //总订单类型 待付款
    const ALL_PENDING_DELIVERY = 2;         //待发货
    const ALL_PENDING_RECEIPT = 3;          //待收货
    const ALL_PENDING_EVALUATE = 4;         //待评价
    const ALL_COMPLETED_EVALUATE = 5;       //已评价
    const ALL_COMPLETED = 6;                //已完成
    const ALL_CANCEL = 7;                   //已取消

    /**
     * 订单明细表关联
     * @return \think\model\relation\HasMany
     */
    public function items()
    {
        return $this->hasMany('OrderItems');
    }

    /**
     * 订单的用户信息关联
     * @return \think\model\relation\HasOne
     */
    public function user()
    {
        return $this->hasOne('User', 'id', 'user_id');
    }

    /**
     * 发货信息关联
     * @return \think\model\relation\HasMany
     */
    public function delivery()
    {
        return $this->hasMany('BillDelivery');
    }

    /**
     * 售后关联
     * @return \think\model\relation\HasMany
     */
    public function aftersales()
    {
        return $this->hasMany('BillAftersales', 'order_id', 'order_id');
    }

    /**
     * 支付单关联
     */
    public function paymentRelItem()
    {
        return $this->hasMany('BillPaymentsRel', 'source_id', 'order_id');
    }

    /**
     * 退款单关联
     */
    public function refundItem()
    {
        return $this->hasMany('BillRefund', 'source_id', 'order_id');
    }

    /**
     * 提货单关联
     */
    public function ladingItem()
    {
        return $this->hasMany('BillLading', 'order_id', 'order_id');
    }

    /**
     * 退货单关联
     */
    public function returnItem()
    {
        return $this->hasMany('BillReship', 'order_id', 'order_id');
    }

    /**
     * 售后单关联
     * @return \think\model\relation\HasMany
     */
    public function aftersalesItem()
    {
        return $this->hasMany('BillAftersales', 'order_id', 'order_id');
    }

    /**
     * 获取订单原始数据
     * @param $input
     * @param bool $isPage
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function getListByWhere($input, $isPage = true)
    {
        $where = [];
        //单个订单模糊搜索时
        if (!empty($input['order_id'])) {
            $where[] = array('o.order_id', 'LIKE', '%' . $input['order_id'] . '%');
        }
        //多个订单时
        if (isset($input['order_ids']) && $input['order_ids'] != "") {
            $where[] = ['o.order_id', 'in', $input['order_ids']];
        }
        if (!empty($input['username'])) {
            $where[] = array('u.username|u.mobile|u.nickname', 'eq', $input['username']);
        }
        if (!empty($input['ship_mobile'])) {
            $where[] = array('o.ship_mobile', 'eq', $input['ship_mobile']);
        }
        if (!empty($input['pay_status'])) {
            $where[] = array('o.pay_status', 'eq', $input['pay_status']);
        }
        if (!empty($input['ship_status'])) {
            $where[] = array('o.ship_status', 'eq', $input['ship_status']);
        }

        if (!empty($input['date'])) {
            $date_string = $input['date'];
            $date_array  = explode(' 到 ', $date_string);
            $sdate       = strtotime($date_array[0] . ' 00:00:00');
            $edate       = strtotime($date_array[1] . ' 23:59:59');
            $where[]     = array('o.ctime', ['>=', $sdate], ['<=', $edate], 'and');
        }
        if (!empty($input['start_date']) || !empty($input['end_date'])) {
            if (!empty($input['start_date']) && !empty($input['end_date'])) {
                $sdate   = strtotime($input['start_date'] . ' 00:00:00');
                $edate   = strtotime($input['end_date'] . ' 23:59:59');
                $where[] = array('o.ctime', ['>=', $sdate], ['<=', $edate], 'and');
            } elseif (!empty($input['start_date'])) {
                $sdate   = strtotime($input['start_date'] . ' 00:00:00');
                $where[] = array('o.ctime', '>=', $sdate);
            } elseif (!empty($input['end_date'])) {
                $edate   = strtotime($input['end_date'] . ' 23:59:59');
                $where[] = array('o.ctime', '<=', $edate);
            }
        }
        if (!empty($input['source'])) {
            $where[] = array('o.source', 'eq', $input['source']);
        }
        if (!empty($input['user_id'])) {
            $where[] = array('o.user_id', 'eq', $input['user_id']);
        }
        if (!empty($input['order_unified_status'])) {
            $where = array_merge($where, $this->getReverseStatus($input['order_unified_status'], 'o.'));
        }

        $page  = $input['page'] ? $input['page'] : 1;
        $limit = $input['limit'] ? $input['limit'] : 20;

        if ($isPage) {

            $data = $this->alias('o')
                ->field('o.order_id, o.user_id, o.ctime, o.ship_mobile, o.ship_address, o.status, o.pay_status, o.ship_status, o.confirm, o.is_comment, o.order_amount, o.source, o.ship_area_id,o.ship_name, o.mark')
                ->join(config('database.prefix') . 'user u', 'o.user_id = u.id', 'left')
                ->where($where)
                ->order('ctime desc')
                ->page($page, $limit)
                ->select();


            $count = $this->alias('o')
                ->field('o.order_id, o.user_id, o.ctime, o.ship_mobile, o.ship_address, o.status, o.pay_status, o.ship_status, o.confirm, o.is_comment, o.order_amount, o.source, o.ship_area_id,o.ship_name, o.mark')
                ->join(config('database.prefix') . 'user u', 'o.user_id = u.id', 'left')
                ->where($where)
                ->count();
        } else {
            $data  = $this->alias('o')
                ->field('o.order_id, o.user_id, o.ctime, o.ship_mobile, o.ship_address, o.status, o.pay_status, o.ship_status, o.confirm, o.is_comment, o.order_amount, o.source, o.ship_area_id,o.ship_name, o.mark')
                ->join(config('database.prefix') . 'user u', 'o.user_id = u.id', 'left')
                ->where($where)
                ->order('ctime desc')
                ->select();
            $count = $this->alias('o')
                ->field('o.order_id, o.user_id, o.ctime, o.ship_mobile, o.ship_address, o.status, o.pay_status, o.ship_status, o.confirm, o.is_comment, o.order_amount, o.source, o.ship_area_id,o.ship_name, o.mark')
                ->join(config('database.prefix') . 'user u', 'o.user_id = u.id', 'left')
                ->where($where)
                ->count();
        }

        return array('data' => $data, 'count' => $count);
    }

    /**
     * 后台获取数据
     * @param $input
     * @param bool $isPage
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getListFromAdmin($input, $isPage = true)
    {
        $result = $this->getListByWhere($input, $isPage);

        if (count($result['data']) > 0) {
            $as = new BillAftersales();

            foreach ($result['data'] as $k => &$v) {
                $v['status_text'] = config('params.order')['status_text'][$this->getStatus($v['status'], $v['pay_status'], $v['ship_status'], $v['confirm'], $v['is_comment'])];
                $v['username']    = get_user_info($v['user_id'], 'nickname');
                $v['operating']   = $this->getOperating($v['order_id'], $v['status'], $v['pay_status'], $v['ship_status']);
                $v['area_name']   = get_area($v['ship_area_id']) . '-' . $v['ship_address'];
                $v['pay_status']  = config('params.order')['pay_status'][$v['pay_status']];
                $v['ship_status'] = config('params.order')['ship_status'][$v['ship_status']];
                $v['source']      = config('params.order')['source'][$v['source']];
                //订单售后状态
                $v['after_sale_status'] = $as->getOrderAfterSaleStatus($v['order_id']);

                //获取订单打印状态
                $print_express = hook('getPrintExpressInfo', ['order_id' => $v['order_id']]);
                if ($print_express[0]['status']) {
                    $v['print'] = true;
                } else {
                    $v['print'] = false;
                }
                //备注醒目
                if (isset($v['mark']) && !empty($v['mark']) && $v['mark'] != '') {
                    $v['order_id_k'] = '<span style="color:#FF7159;" title="' . $v['mark'] . '">' . $v['order_id'] . '</span>';
                } else {
                    $v['order_id_k'] = $v['order_id'];
                }
            }
        }
        return $result;
    }

    /**
     * 总后台获取数据
     * @param $input
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getListFromManage($input)
    {
        $result = $this->getListByWhere($input);

        if (count($result['data']) > 0) {
            foreach ($result['data'] as $k => &$v) {
                $v['status_text'] = config('params.order')['status_text'][$this->getStatus($v['status'], $v['pay_status'], $v['ship_status'], $v['confirm'], $v['is_comment'])];
                $v['username']    = get_user_info($v['user_id'], 'nickname');
                $v['operating']   = $this->getOperating($v['order_id'], $v['status'], $v['pay_status'], $v['ship_status'], 'manage');
                $v['area_name']   = get_area($v['ship_area_id']) . '-' . $v['ship_address'];
                $v['pay_status']  = config('params.order')['pay_status'][$v['pay_status']];
                $v['ship_status'] = config('params.order')['ship_status'][$v['ship_status']];
                $v['source']      = config('params.order')['source'][$v['source']];
            }
        }
        return $result;
    }

    /**
     * API获取数据
     * @param $input
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getListFromApi($input)
    {
        $return_data = $this->getListByWhere($input);
        return $return_data;
    }

    /**
     * 获取待发货列表
     * @param $input
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
//    public function getWaitListFromAdmin($input)
//    {
//        $input['pay_status'] = self::PAY_STATUS_YES;
//        $input['ship_status'] = self::SHIP_STATUS_NO;
//
//        $result = $this->getListByWhere($input);
//
//        if(count($result['data']) > 0)
//        {
//            foreach($result['data'] as $k => &$v)
//            {
//                $v['username'] = get_user_info($v['user_id'], 'nickname');
//                $v['operating'] = $this->getOperating($v['order_id'], $v['status'], $v['pay_status'], $v['ship_status']);
//                $v['area_name'] = get_area($v['ship_area_id']).'-'.$v['ship_address'];
//                $v['pay_status'] = config('params.order')['pay_status'][$v['pay_status']];
//                $v['ship_status'] = config('params.order')['ship_status'][$v['ship_status']];
//                $v['source'] = config('params.order')['source'][$v['source']];
//            }
//        }
//        return $result;
//    }

    /**
     * 获取订单列表微信小程序
     * @param $input
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
//    public function getListFromWxApi($input)
//    {
//        $where = [];
//        if (!empty($input['user_id'])) {
//            $where[] = array('user_id', 'eq', $input['user_id']);
//        }
//
//        $page  = $input['page'] ? $input['page'] : 1;
//        $limit = $input['limit'] ? $input['limit'] : 20;
//        if(empty($input['status'])){
//            $whereOr[] = ['pay_status','neq',1];
//            $whereOr[] = ['parent_order_id','not null'];
//            $whereOr   = array_merge($where,$whereOr);
//            $where2[]  = ['pay_status','eq',1];
//            $where2[]  = ['parent_order_id','null'];
//            $where     = array_merge($where,$where2);
//            $data = $this::with('items')->where([$where])->whereOr([$whereOr])
//                ->order('ctime desc')
//                ->page($page, $limit)
//                ->select();
//
//            $count = $this->where([$where])->whereOr([$whereOr])
//                ->count();
//
//        }else{
//            $where = array_merge($where, $this->getReverseStatus($input['status']));
//            $data = $this::with('items')->where($where)
//                ->order('ctime desc')
//                ->page($page, $limit)
//                ->select()->toArray();
//            $count = $this->where($where)
//                ->count();
//        }
//        if ($data) {
//            foreach ($data as $key=>$val) {
//                $shop = [];
//                foreach ($val['items'] as $k=>$v) {
////                    if ($v['user_mobile']) {
////                        $shop[$v['user_mobile']][] = $v;
//////                        $data[$key][$v['user_mobile']][] = $v;
////                    } else {
////                        $data[$key]['autarky'][] = $v;
////                    }
//                    if ($v['user_mobile']) {
//                        $shop[$v['user_mobile']][] = $v;
//                    } else {
//                        $shop['autarky'][] = $v;
//                    }
//                }
//                $data[$key]['shop'] = $shop;
//            }
//
//
//        }
//
//        return array('data' => $data, 'count' => $count);
//    }
    public function getListFromWxApi($input)
    {
        $where = [];
        if (!empty($input['status'])) {
            $where = $this->getReverseStatus($input['status']);
        }
        if (!empty($input['user_id'])) {
            $where[] = array('user_id', 'eq', $input['user_id']);
        }

        $page  = $input['page'] ? $input['page'] : 1;
        $limit = $input['limit'] ? $input['limit'] : 20;

        $data = $this::with('items')->where($where)
            ->order('ctime desc')
            ->page($page, $limit)
            ->select();

        if ($data) {
            foreach ($data as $key=>$val) {
                $shop = [];
                foreach ($val['items'] as $k=>$v) {
//                    if ($v['user_mobile']) {
//                        $shop[$v['user_mobile']][] = $v;
////                        $data[$key][$v['user_mobile']][] = $v;
//                    } else {
//                        $data[$key]['autarky'][] = $v;
//                    }
                    if ($v['user_mobile']) {
                        $shop[$v['user_mobile']][] = $v;
                    } else {
                        $shop['autarky'][] = $v;
                    }
                }
                $data[$key]['shop'] = $shop;
            }
        }

        $count = $this->where($where)
            ->count();
        return array('data' => $data, 'count' => $count);
    }

    /**
     * 获取订单不同状态的数量
     * @param $input
     * @return array
     */
    public function getOrderStatusNum($input)
    {
        $ids = explode(",", $input['ids']);
        if ($input['user_id']) {
            $user_id = $input['user_id'];
        } else {
            $user_id = false;
        }

        $data = [];
        foreach ($ids as $k => $v) {
            $data[$v] = $this->orderCount($v, $user_id);
        }

        //售后状态查询
        $isAfterSale = $input['isAfterSale'];
        if ($isAfterSale) {
            $model               = new BillAftersales();
            $number              = $model->getUserAfterSalesNum($user_id, $model::STATUS_WAITAUDIT);
            $data['isAfterSale'] = $number;
        }
        return $data;
    }

    /**
     * 订单数量统计
     * @param $id
     * @param bool $user_id
     * @return int|string
     */
    protected function orderCount($id = 0, $user_id = false)
    {
        $where = [];
        //都需要验证的
        if ($user_id) {
            $where[] = ['user_id', 'eq', $user_id];
        }

        $where = array_merge($where, $this->getReverseStatus($id));

        return $this->where($where)
            ->count();
    }

    /**
     * 根据订单状态生成不同的操作按钮
     * @param $id
     * @param $order_status
     * @param $pay_status
     * @param $ship_status
     * @param string $from
     * @return string
     */
    protected function getOperating($id, $order_status, $pay_status, $ship_status, $from = 'seller',$isShop=false)
    {
        $html = '<a class="layui-btn layui-btn-primary layui-btn-xs view-order" data-id="' . $id . '">查看</a>';

        if ($order_status == self::ORDER_STATUS_NORMAL) {
            //正常
            if ($pay_status == self::PAY_STATUS_NO && $from == 'seller' && $isShop == false) {
                $html .= '<a class="layui-btn layui-btn-xs pay-order" data-id="' . $id . '">支付</a>';
            }
            if ($pay_status != self::PAY_STATUS_NO) {
                if (($ship_status == self::SHIP_STATUS_NO || $ship_status == self::SHIP_STATUS_PARTIAL_YES) && $from == 'seller') {
                    $html .= '<a class="layui-btn layui-btn-xs edit-order" data-id="' . $id . '">编辑</a>';
                    $html .= '<a class="layui-btn layui-btn-xs ship-order" data-id="' . $id . '">发货</a>';
                }
                $html .= '<a class="layui-btn layui-btn-xs complete-order" data-id="' . $id . '">完成</a>';
//                if($ship_status == self::SHIP_STATUS_YES)
//                {
//                    $html .= '<a class="layui-btn layui-btn-primary layui-btn-xs order-logistics" data-id="'.$id.'">物流信息</a>';
//                }
            }
            if ($pay_status == self::PAY_STATUS_NO) {
                if ($from == 'seller') {
                    $html .= '<a class="layui-btn layui-btn-xs edit-order" data-id="' . $id . '" data-type="1">编辑</a>';
                }
                $html .= '<a class="layui-btn layui-btn-xs cancel-order" data-id="' . $id . '">取消</a>';
            }
        }
//        if ($order_status == self::ORDER_STATUS_COMPLETE)
//        {
//            $html .= '<a class="layui-btn layui-btn-primary layui-btn-xs order-logistics" data-id="'.$id.'">物流信息</a>';
//        }
        if ($order_status == self::ORDER_STATUS_CANCEL) {
            $html .= '<a class="layui-btn layui-btn-danger layui-btn-xs del-order" data-id="' . $id . '">删除</a>';
        }

        return $html;
    }

    /**
     * 获取订单信息
     * @param $id
     * @param bool $user_id
     * @param bool $logistics
     * @return Order|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOrderInfoByOrderID($id, $user_id = false, $logistics = true)
    {
        $order_info = $this->get($id); //订单信息
        if (!$order_info) {
            return false;
        }

        if ($user_id) {
            if ($user_id != $order_info['user_id']) {
                return false;
            }
        }

        $order_info->items; //订单详情
        $order_info->user; //用户信息
        $order_info->paymentRelItem; //支付单
        $order_info->refundItem; //退款单
        $order_info->delivery; //发货信息
        $order_info->ladingItem; //提货单
        $order_info->returnItem; //退货单
        $order_info->aftersalesItem; //售后单

        //获取提货门店
        $order_info['store'] = false;
        if ($order_info['store_id'] != 0) {
            $storeModel               = new Store();
            $storeInfo                = $storeModel->get($order_info['store_id']);
            $storeInfo['all_address'] = get_area($storeInfo['area_id']) . $storeInfo['address'];
            $order_info['store']      = $storeInfo;
        }

        foreach ($order_info['delivery'] as &$v) {
            $v['logi_name'] = get_logi_info($v['logi_code'], 'logi_name');
        }

        $order_info->hidden(['user' => ['isdel', 'password']]);

        if ($order_info['logistics_id']) {
            $w[]                     = ['id', 'eq', $order_info['logistics_id']];
            $order_info['logistics'] = model('common/Ship')->where($w)->find();
        } else {
            $order_info['logistics'] = null;
        }
        $order_info['text_status']    = $this->getStatus($order_info['status'], $order_info['pay_status'], $order_info['ship_status'], $order_info['confirm'], $order_info['is_comment']);
        $order_info['ship_area_name'] = get_area($order_info['ship_area_id']);

        if (isset(config('params.payment_type')[$order_info['payment_code']])) {
            $payment_name = config('params.payment_type')[$order_info['payment_code']];
        } else {
            $payment_name = false;
        }

        $order_info['payment_name'] = $payment_name ? $payment_name : '未知支付方式';

        //如果有优惠券，数据处理
        if ($order_info['coupon']) {
            $order_info['coupon'] = json_decode($order_info['coupon'], true);
        }

        //获取该状态截止时间
        switch ($order_info['text_status']) {
            case self::ALL_PENDING_PAYMENT: //待付款
                $cancel                       = getSetting('order_cancel_time') * 86400;
                $ctime                        = $order_info['ctime'];
                $remaining                    = $ctime + $cancel - time();
                $order_info['remaining']      = $this->dateTimeTransformation($remaining);
                $order_info['remaining_time'] = $remaining;
                break;
            case self::ALL_PENDING_RECEIPT: //待收货
                $sign                         = getSetting('order_autoSign_time') * 86400;
                $utime                        = $order_info['utime'];
                $remaining                    = $utime + $sign - time();
                $order_info['remaining']      = $this->dateTimeTransformation($remaining);
                $order_info['remaining_time'] = $remaining;
                break;
            case self::ALL_PENDING_EVALUATE: //待评价
                $eval                         = getSetting('order_autoEval_time') * 86400;
                $confirm                      = $order_info['confirm_time'];
                $remaining                    = $confirm + $eval - time();
                $order_info['remaining']      = $this->dateTimeTransformation($remaining);
                $order_info['remaining_time'] = $remaining;
                break;
            default:
                $order_info['remaining']      = false;
                $order_info['remaining_time'] = false;
                break;
        }

        //物流信息查询
        if (isset($order_info['delivery'][0]) && $order_info['delivery'][0] && $logistics) {
            $logi_code         = $order_info['delivery'][0]['logi_code'];
            $logi_no           = $order_info['delivery'][0]['logi_no'];
            $billDeliveryModel = new BillDelivery();
            $express_delivery  = $billDeliveryModel->getLogistic($logi_code, $logi_no);
            if ($express_delivery['status']) {
                $order_info['express_delivery'] = $express_delivery['data']['info']['data'][0];
            } else {
                $order_info['express_delivery'] = [
                    'context' => '已为你发货，请注意查收',
                    'time'    => date('Y-m-d H:i:s', $order_info['delivery'][0]['ctime'])
                ];
            }
        }

        //支付单
        if (count($order_info['paymentRelItem']) > 0) {
            $billPaymentsModel = new BillPayments();
            foreach ($order_info['paymentRelItem'] as &$v) {
                $v['bill']                      = $billPaymentsModel->get($v['payment_id']);
                $v['bill']['payment_code_name'] = config('params.payment_type')[$v['bill']['payment_code']];
                $v['bill']['status_name']       = config('params.bill_payments')['status'][$v['bill']['status']];
                $v['bill']['utime_name']        = getTime($v['bill']['utime']);
            }
        }

        //退款单
        if (count($order_info['refundItem']) > 0) {
            foreach ($order_info['refundItem'] as &$v) {
                $v['payment_code_name'] = config('params.payment_type')[$v['payment_code']];
                $v['status_name']       = config('params.bill_refund')['status'][$v['status']];
                $v['ctime_name']        = getTime($v['ctime']);
            }
        }

        //发货单
        if (count($order_info['delivery']) > 0) {
            $logiModel = new Logistics();
            $areaModel = new Area();
            foreach ($order_info['delivery'] as &$v) {
                $v['logi_code_name']    = $logiModel->getNameByCode($v['logi_code']);
                $v['ship_area_id_name'] = $areaModel->getAllName($v['ship_area_id']);
            }
        }

        //提货单
        if (count($order_info['ladingItem']) > 0) {
            $storeModel      = new Store();
            $clerkModel      = new Clerk();
            $billLadingModel = new BillLading();
            foreach ($order_info['ladingItem'] as &$v) {
                $v['store_id_name'] = $storeModel->getStoreName($v['store_id']);
                $v['status_name']   = config('params.bill_lading')['status'][$v['status']];
                if ($v['status'] == $billLadingModel::STATUS_YES) {
                    $v['utime_name'] = getTime($v['utime']);
                } else {
                    $v['utime_name'] = '';
                }

                if ($v['clerk_id']) {
                    $v['clerk_id_name'] = $clerkModel->getClerkName($v['clerk_id']);
                } else {
                    $v['clerk_id_name'] = '';
                }
            }
        }

        //退货单
        if (count($order_info['returnItem']) > 0) {
            $logiModel = new Logistics();
            foreach ($order_info['returnItem'] as &$v) {
                $v['logi_code_name'] = $logiModel->getNameByCode($v['logi_code']);
                $v['status_name']    = config('params.bill_reship')['status'][$v['status']];
                $v['utime_name']     = getTime($v['utime']);
            }
        }

        //售后单
        $order_info['bill_aftersales_id'] = false;
        if (count($order_info['aftersalesItem']) > 0) {
            $billAftersalesModel = new BillAftersales();
            foreach ($order_info['aftersalesItem'] as $v) {
                $order_info['bill_aftersales_id'] = $v['aftersales_id'];
                //如果售后单里面有待审核的活动售后单，那就直接拿这条
                if ($v['status'] == $billAftersalesModel::STATUS_WAITAUDIT) {
                    break;
                }
            }
        }

        //促销信息
        if ($order_info['promotion_list']) {
            $order_info['promotion_list'] = json_decode($order_info['promotion_list'], true);
        }

        return $order_info;
    }

    /**
     * 时间转换
     * @param $time
     * @return false|string
     */
    protected function dateTimeTransformation($time)
    {
        $newtime = '';
        $d       = floor($time / (3600 * 24));
        $h       = floor(($time % (3600 * 24)) / 3600);
        $m       = floor((($time % (3600 * 24)) % 3600) / 60);
        $s       = floor((($time % (3600 * 24)) % 3600) % 60);
        $s       = ($s < 10) ? '0' . $s : $s;
        if ($d > '0') {
            $newtime = $d . '天' . $h . '小时' . $m . '分' . $s . '秒';
        } else {
            if ($h != '0') {
                $newtime = $h . '小时' . $m . '分' . $s . '秒';
            } else {
                $newtime = $m . '分' . $s . '秒';
            }
        }
        return $newtime;
    }

    /**
     * 获取状态
     * @param $status
     * @param $pay_status
     * @param $ship_status
     * @param $confirm
     * @param $is_comment
     * @return string
     */
    protected function getStatus($status, $pay_status, $ship_status, $confirm, $is_comment)
    {
        if ($status == self::ORDER_STATUS_NORMAL && $pay_status == self::PAY_STATUS_NO) {
            //待付款
            return self::ALL_PENDING_PAYMENT;
        } elseif ($status == self::ORDER_STATUS_NORMAL && $pay_status == self::PAY_STATUS_YES && $ship_status == self::SHIP_STATUS_NO) {
            //待发货
            return self::ALL_PENDING_DELIVERY;
        } elseif ($status == self::ORDER_STATUS_NORMAL && $ship_status == self::SHIP_STATUS_YES && $confirm == self::RECEIPT_NOT_CONFIRMED) {
            //待收货
            return self::ALL_PENDING_RECEIPT;
        } elseif ($status == self::ORDER_STATUS_NORMAL && $pay_status > self::PAY_STATUS_NO && $ship_status == self::SHIP_STATUS_YES && $confirm == self::CONFIRM_RECEIPT && $is_comment == self::NO_COMMENT) {
            //待评价
            return self::ALL_PENDING_EVALUATE;
        } elseif ($status == self::ORDER_STATUS_NORMAL && $pay_status > self::PAY_STATUS_NO && $ship_status == self::SHIP_STATUS_YES && $confirm == self::CONFIRM_RECEIPT && $is_comment == self::ALREADY_COMMENT) {
            //已评价
            return self::ALL_COMPLETED_EVALUATE;
        } elseif ($status == self::ORDER_STATUS_COMPLETE) {
            //已完成
            return self::ALL_COMPLETED;
        } elseif ($status == self::ORDER_STATUS_CANCEL) {
            //已取消
            return self::ALL_CANCEL;
        }
    }

    /**
     * 获取订单状态反查
     * @param $status
     * @param string $table_name
     * @return array
     */
    protected function getReverseStatus($status, $table_name = '')
    {
        $where = [];
        switch ($status) {
            case self::ALL_PENDING_PAYMENT: //待付款
                $where = [
                    [$table_name . 'status', 'eq', self::ORDER_STATUS_NORMAL],
                    [$table_name . 'pay_status', 'eq', self::PAY_STATUS_NO]
                ];
                break;
            case self::ALL_PENDING_DELIVERY: //待发货
                $where = [
                    [$table_name . 'status', 'eq', self::ORDER_STATUS_NORMAL],
                    [$table_name . 'pay_status', 'eq', self::PAY_STATUS_YES],
                    [$table_name . 'ship_status', 'eq', self::SHIP_STATUS_NO]
                ];
                break;
            case self::ALL_PENDING_RECEIPT: //待收货
                $where = [
                    [$table_name . 'status', 'eq', self::ORDER_STATUS_NORMAL],
                    [$table_name . 'ship_status', 'eq', self::SHIP_STATUS_YES],
                    [$table_name . 'confirm', 'eq', self::RECEIPT_NOT_CONFIRMED]
                ];
                break;
            case self::ALL_PENDING_EVALUATE: //待评价
                $where = [
                    [$table_name . 'status', 'eq', self::ORDER_STATUS_NORMAL],
                    [$table_name . 'pay_status', '>', self::PAY_STATUS_NO],
                    [$table_name . 'ship_status', 'eq', self::SHIP_STATUS_YES],
                    [$table_name . 'confirm', 'eq', self::CONFIRM_RECEIPT],
                    [$table_name . 'is_comment', 'eq', self::NO_COMMENT]
                ];
                break;
            case self::ALL_COMPLETED_EVALUATE: //已评价
                $where = [
                    [$table_name . 'status', 'eq', self::ORDER_STATUS_NORMAL],
                    [$table_name . 'pay_status', '>', self::PAY_STATUS_NO],
                    [$table_name . 'ship_status', 'eq', self::SHIP_STATUS_YES],
                    [$table_name . 'confirm', 'eq', self::CONFIRM_RECEIPT],
                    [$table_name . 'is_comment', 'eq', self::ALREADY_COMMENT]
                ];
                break;
            case self::ALL_CANCEL: //已取消
                $where = [
                    [$table_name . 'status', 'eq', self::ORDER_STATUS_CANCEL]
                ];
                break;
            case self::ALL_COMPLETED: //已完成
                $where = [
                    [$table_name . 'status', 'eq', self::ORDER_STATUS_COMPLETE]
                ];
                break;
            default:
                break;
        }

        return $where;
    }

    /**
     * 完成订单操作
     * @param $id
     * @return bool|int|string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function complete($id)
    {
        //等待售后审核的订单，不自动操作完成。
        $baModel             = new BillAftersales();
        $bawhere[]           = ['order_id', 'eq', $id];
        $bawhere[]           = ['status', 'eq', $baModel::STATUS_WAITAUDIT];
        $billAftersalesCount = $baModel->where($bawhere)->count();
        if ($billAftersalesCount > 0) {
            return true;
        }
        $where[] = ['order_id', 'eq', $id];
        $where[] = ['pay_status', 'neq', self::PAY_STATUS_NO];

        $data['status'] = self::ORDER_STATUS_COMPLETE;
        $data['utime']  = time();

        $info = $this->where($where)
            ->find();
        if ($info) {
            $result = $this->where($where)
                ->update($data);
            //计算订单实际支付金额（要减去售后退款的金额）
            $money     = $info['payed'];
            $bawhere   = [];
            $bawhere[] = ['order_id', 'eq', $id];
            $bawhere[] = ['status', 'eq', $baModel::STATUS_SUCCESS];
            $baList    = $baModel->where($bawhere)->select();
            if ($baList && count($baList) > 0) {
                $refundMoney = 0;
                foreach ($baList as $k => $v) {
                    $refundMoney = bcadd($refundMoney, $v['refund'], 2);
                }
                $money = bcsub($money, $refundMoney, 2);
            }

            //奖励积分
            $userPointLog = new UserPointLog();
            $userPointLog->orderComplete($info['user_id'], $money, $info['order_id']);

            //订单记录
            $orderLog = new OrderLog();
            $orderLog->addLog($info['order_id'], $info['user_id'], $orderLog::LOG_TYPE_COMPLETE, '后台订单完成操作', $where);
        } else {
            $result = false;
        }

        return $result;
    }

    /**
     * 取消订单操作
     * @param $id
     * @param bool $user_id
     * @return bool|static
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cancel($id, $user_id = false)
    {
        $where[] = array('order_id', 'in', $id);
        $where[] = array('pay_status', 'eq', self::PAY_STATUS_NO);
        $where[] = array('status', 'eq', self::ORDER_STATUS_NORMAL);
        $where[] = array('ship_status', 'eq', self::SHIP_STATUS_NO);

        if ($user_id) {
            $where[] = array('user_id', 'eq', $user_id);
        }

        $order_info = $this->where($where)
            ->select();

        if ($order_info) {
            Db::startTrans();
            try {
                //更改状态和库存
                $order_ids = [];
                $orderLog  = new OrderLog();
                foreach ($order_info as $k => $v) {
                    $order_ids[] = $v['order_id'];
                    //订单记录
                    $orderLog->addLog($v['order_id'], $v['user_id'], $orderLog::LOG_TYPE_CANCEL, '订单取消操作', $where);
                    //变更积分
                    if ($v['point'] > 0) {
                        $pointLogMode = new UserPointLog();
                        $res          = $pointLogMode->setPoint($v['user_id'], $v['point'], $pointLogMode::POINT_TYPE_ADMIN_EDIT, '取消订单返还积分');
                        if (!$res['status']) {
                            Db::rollback();
                            return false;
                        }
                    }
                }
                //状态修改
                $w[]         = ['order_id', 'in', $order_ids];
                $d['status'] = self::ORDER_STATUS_CANCEL;
                $d['utime']  = time();
                $this->where($w)
                    ->update($d);
                $itemModel  = new OrderItems();
                $goods      = $itemModel->field('product_id, nums')->where($w)->select();
                $goodsModel = new Goods();
                foreach ($goods as $v) {
                    $goodsModel->changeStock($v['product_id'], 'cancel', $v['nums']);
                }
                $parentWhere[] = ['parent_order_id','in',$order_ids];
                if($this->where($parentWhere)->select()){
                    $this->whereOr($parentWhere)->update($d);
                }

                $result = true;
                Db::commit();
                hook('cancelorder', $order_info); // 订单取消的钩子
            } catch (\Exception $e) {
                $result = false;
                Db::rollback();
            }
        } else {
            $result = false;
        }

        return $result;
    }

    /**
     * 删除订单
     * @param $order_ids
     * @param $user_id
     * @return int
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function del($order_ids, $user_id)
    {
        $where[] = array('order_id', 'in', $order_ids);
        $where[] = array('user_id', 'eq', $user_id);

        $result = $this->where($where)
            ->delete();
        return $result;
    }

    /**
     * 获取支付订单信息
     * @param $id
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPayInfo($id)
    {
        return $this->where('order_id', 'eq', $id)->find();
    }

    /**
     * 编辑保存订单
     * @param $data
     * @return int|string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function edit($data)
    {
        if ($data['edit_type'] == 1) {
            $update = [
                'ship_area_id' => $data['ship_area_id'],
                'ship_address' => $data['ship_address'],
                'ship_name'    => $data['ship_name'],
                'ship_mobile'  => $data['ship_mobile']
            ];
            if ($data['order_amount']) {
                $update['order_amount'] = $data['order_amount'];
            }
        } elseif ($data['edit_type'] == 2) {
            $update['store_id']    = $data['store_id'];
            $update['ship_name']   = $data['ship_name'];
            $update['ship_mobile'] = $data['ship_mobile'];
        }


        $res = $this->where('order_id', 'eq', $data['order_id'])
            ->update($update);

        //订单记录
        $orderLog = new OrderLog();
        $w[]      = ['order_id', 'eq', $data['order_id']];
        $info     = $this->where($w)
            ->find();
        $orderLog->addLog($info['order_id'], $info['user_id'], $orderLog::LOG_TYPE_EDIT, '后台订单编辑修改', $update);

        return $res;
    }

    /**
     * 获取需要发货的信息
     * @param $id
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOrderShipInfo($id)
    {
        $where[] = array('pay_status', 'neq', self::PAY_STATUS_NO);
        $where[] = array('order_id', 'eq', $id);
        $where[] = array('ship_status', 'in', self::SHIP_STATUS_NO . ',' . self::SHIP_STATUS_PARTIAL_YES);
        $where[] = array('status', 'eq', 1);

        $order = $this->field('order_id, logistics_id, logistics_name, cost_freight, ship_area_id, ship_address, ship_name, ship_mobile, weight, memo')
            ->where($where)
            ->find();

        $order['ship_area_id'] = get_area($order['ship_area_id']);

        return $order;
    }

    /**
     * 发货改状态
     * @param $order_id
     * @return bool
     */
    public function ship($order_id)
    {
        //查询发货数量和是否全部发货
        $ship_status = model('common/OrderItems')->isAllShip($order_id);
        //判断发货状态
        if ($ship_status == 'all') {
            $order_data['ship_status'] = self::SHIP_STATUS_YES;
        } else {
            $order_data['ship_status'] = self::SHIP_STATUS_PARTIAL_YES;
        }
        //发货
        $where[] = ['order_id', 'eq', $order_id];
        $result  = $this->save($order_data, $where);

        if ($result) {
            //判断生成门店自提单
            $order_info = $this->get($order_id);
            if ($order_info['store_id'] != 0) {
                $ladingModel = new BillLading();
                $ladingModel->addData($order_id, $order_info['store_id'], $order_info['ship_name'], $order_info['ship_mobile']);
            }
        }
        return $result;
    }

    /**
     * 支付
     * @param $order_id
     * @param $payment_code
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function pay($order_id, $payment_code)
    {
        $return_data = array(
            'status' => false,
            'msg'    => '订单支付失败',
            'data'   => array()
        );

        $w[]   = ['order_id', 'eq', $order_id];
        $w[]   = ['status', 'eq', self::ORDER_STATUS_NORMAL];
        $order = $this->where($w)
            ->find();

        if (!$order) {
            return $return_data;
        }
        if ($order['pay_status'] == self::PAY_STATUS_YES || $order['pay_status'] == self::PAY_STATUS_PARTIAL_NO || $order['pay_status'] == self::PAY_STATUS_REFUNDED) {
            $return_data['msg']  = '订单支付失败，该订单已支付';
            $return_data['data'] = $order;
            $data                = "订单" . $order_id . "支付失败，订单已经支付";
        } else {
            $data['payment_code'] = $payment_code;
            $data['payed']        = $order['order_amount'];
            $data['pay_status']   = self::PAY_STATUS_YES;
            $data['payment_time'] = time();
            $this->startTrans();
            $result                = $this->where('order_id', 'eq', $order_id)
                ->update($data);
//            $sonResult = Db::execute("update jshop_order set payed = order_amount,payment_code='".$payment_code."',pay_status=".self::PAY_STATUS_YES.",payment_time=".time()."  where parent_order_id = $order_id");
            $sonResult = true;
            $return_data['data'] = $result;
            if ($result !== false && $sonResult) {
                // 奖金
                if ($order->user->grade <= 2) {
                    if ($order['order_amount'] >= 399 && $order['order_amount'] <10000) { //一星 复购/升级
                        if ($order->user->grade < 2) {
                            // 会员升级
                            $this->userUpgrade($order->user_id);
                        }
                        // 增加排行榜
                        $this->addRanking($order_id, $order->user->grade);

                        // 增加会员封顶,更改会员等级
                        $order->user->capping_money += 4000;
                        $order->user->grade = 2;
                        $order->user->save();
                        // 增加星级提成金额(三钻奖金)
                        $setting     = new Setting();
                        $countMoney  = $setting->getValue('start_rating_money');
                        $setting->setValue('start_rating_money',$countMoney + 40);

                        //订单支付完成后的钩子
                        Hook('orderpayed', $order_id);
                    }
                }
                if ($order->user->grade <= 8) {
                    if ($order['order_amount'] >= 10000 && $order['order_amount'] < 50000) {  // 一钻
                        if ($order->user->grade == 8) {
                            // 补货提成
                            (new Distribution())->orderPayReplenish($order->user_id, $order->get_point);
                            // 增加伞下业绩
                            Db::query("call three_drill_upgrade($order->user_id,$order->order_amount)");
                            // 增加星级提成金额(三钻奖金)
                            $setting     = new Setting();
                            $countMoney  = $setting->getValue('replenish_rating_money');
                            $setting->setValue('replenish_rating_money',$countMoney + $order->order_amount*0.35);
                        } else {
                            //更改会员等级
                            $order->user->grade = 8;
                            $order->user->save();
                            // 会员升级
                            $this->userUpgrade($order->user_id);
                            // 增加星级提成金额(三钻奖金)
                            $setting     = new Setting();
                            $countMoney  = $setting->getValue('drill_rating_money');
                            $setting->setValue('drill_rating_money',$countMoney + 10000*0.25);
                            // 销售提成/超越奖金
                            if ($order->user->pid) {
                                (new Distribution())->orderPayRecommend($order->user->pid, $order->user->grade, $order->user_id);
                            }
                        }

                    }
                }
                if ($order->user->grade <= 9) {
                    if ($order['order_amount'] >= 50000 && $order['order_amount'] < 100000) {  // 二钻
                        if ($order->user->grade == 9) {
                            // 补货提成
                            (new Distribution())->orderPayReplenish($order->user_id, $order->get_point);
                            // 增加伞下业绩
                            Db::query("call three_drill_upgrade($order->user_id,$order->order_amount)");
                            // 增加星级提成金额(三钻奖金)
                            $setting     = new Setting();
                            $countMoney  = $setting->getValue('replenish_rating_money');
                            $setting->setValue('replenish_rating_money',$countMoney + $order->order_amount*0.35);
                        } else {
                            //更改会员等级
                            $order->user->grade = 9;
                            $order->user->save();
                            // 会员升级
                            $this->userUpgrade($order->user_id);
                            // 增加星级提成金额(三钻奖金)
                            $setting     = new Setting();
                            $countMoney  = $setting->getValue('drill_rating_money');
                            $setting->setValue('drill_rating_money',$countMoney + 50000*0.25);
                            // 销售提成/超越奖金
                            if ($order->user->pid) {
                                (new Distribution())->orderPayRecommend($order->user->pid, $order->user->grade, $order->user_id);
                            }
                        }
                    }
                }
                if ($order->user->grade <= 10) {
                    if ($order['order_amount'] >= 100000) {  // 三钻
                        if ($order->user->grade == 10) {
                            // 补货提成
                            (new Distribution())->orderPayReplenish($order->user_id, $order->get_point);
                            // 增加伞下业绩
                            Db::query("call three_drill_upgrade($order->user_id,$order->order_amount)");
                            // 增加星级提成金额(三钻奖金)
                            $setting     = new Setting();
                            $countMoney  = $setting->getValue('replenish_rating_money');
                            $setting->setValue('replenish_rating_money',$countMoney + $order->order_amount*0.35);
                        } else {
                            //更改会员等级
                            $order->user->grade = 10;
                            $order->user->save();
                            // 会员升级
                            $this->userUpgrade($order->user_id);
                            // 增加星级提成金额(三钻奖金)
                            $setting     = new Setting();
                            $countMoney  = $setting->getValue('drill_rating_money');
                            $setting->setValue('drill_rating_money',$countMoney + 10000*0.25);
                            // 销售提成/超越奖金
                            if ($order->user->pid) {
                                (new Distribution())->orderPayRecommend($order->user->pid, $order->user->grade, $order->user_id);
                            }
                        }
                        // 极差奖，分享奖
//                        (new Distribution())->orderpayeddrill($order_id);
                    }
                }
//                //发送支付成功信息,增加发送内容
//                $order['pay_time']  = date('Y-m-d H:i:s', $data['payment_time']);
//                $order['money']     = $order['order_amount'];
//                $order['user_name'] = get_user_info($order['user_id']);
//                sendMessage($order['user_id'], 'order_payed', $order);
//
//                sendMessage($order['user_id'], 'seller_order_notice', $order);//给卖家发消息
                $this->commit();
                $return_data['status'] = true;
                $return_data['msg']    = '订单支付成功';
            }else{
                $this->rollback();
            }
        }

        //订单记录
        $orderLog = new OrderLog();
        $orderLog->addLog($order_id, $order['user_id'], $orderLog::LOG_TYPE_PAY, $return_data['msg'], [$return_data, $data]);

        return $return_data;
    }

    /**
     * 确认签收
     * @param $order_id
     * @param $user_id
     * @return false|int
     */
    public function confirm($order_id, $user_id)
    {
        $where[] = array('order_id', 'eq', $order_id);
        $where[] = array('pay_status', 'neq', self::PAY_STATUS_NO);
        $where[] = array('ship_status', 'neq', self::SHIP_STATUS_NO);
        $where[] = array('status', 'eq', self::ORDER_STATUS_NORMAL);
        $where[] = array('confirm', 'neq', self::CONFIRM_RECEIPT);
        $where[] = array('user_id', 'eq', $user_id);

        $data['confirm']      = self::CONFIRM_RECEIPT;
        $data['confirm_time'] = time();

        Db::startTrans();
        try {
            //修改订单
            $this->save($data, $where);

            //修改发货单
            model('common/BillDelivery')->confirm($order_id);

            //订单记录
            $orderLog = new OrderLog();
            $w[]      = ['order_id', 'eq', $order_id];
            $info     = $this->where($w)
                ->find();
            $orderLog->addLog($order_id, $info['user_id'], $orderLog::LOG_TYPE_SIGN, '确认签收操作', $where);

            $return = true;
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $return = false;
        }
        return $return;
    }

    /**
     * 生成订单方法
     * @param $user_id
     * @param $cart_ids
     * @param $uship_id
     * @param $memo
     * @param $area_id
     * @param int $point
     * @param bool $coupon_code
     * @param bool $formId
     * @param int $receipt_type
     * @param bool $store_id
     * @param bool $lading_name
     * @param bool $lading_mobile
     * @param int $source //来源平台，1 pc，2 h5，3微信小程序
     * @param array $tax
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function toAdd($user_id, $cart_ids, $uship_id, $memo, $area_id,$point = 0, $coupon_code = false, $formId = false, $receipt_type = 1, $store_id = false, $lading_name = false, $lading_mobile = false, $source = 2, $tax = [],$isCoupon=0)
    {
        $result = [
            'status' => false,
            'data'   => array(),
            'msg'    => ''
        ];
        if ($receipt_type == 1) {
            //快递邮寄
            $ushopModel = new UserShip();
            $ushopInfo  = $ushopModel->getShipById($uship_id, $user_id);
            if (!$ushopInfo) {
                return error_code(11050);
            }
        } else {
            //门店自提
            $storeModel = new Store();
            $storeInfo  = $storeModel->get($store_id);
            if (!$storeInfo) {
                return error_code(11055);
            }
        }

        $orderInfo = $this->formatOrderItems($user_id, $cart_ids, $area_id, $point, $coupon_code, $receipt_type);
        if (!$orderInfo['status']) {
            return $orderInfo;
        }
        if (!isset($orderInfo['data']['items']) || count($orderInfo['data']['items']) <= 0) {
            return error_code(11100);
        }
        $order['order_id']     = get_sn(1);
        $order['goods_amount'] = $orderInfo['data']['goods_amount'];
        $order['order_amount'] = $orderInfo['data']['amount'];
        $order['get_point']    = $orderInfo['data']['get_point'];
        if ($order['order_amount'] <= 0) {
            $order['pay_status']   = 2;
            $order['payment_time'] = time();
        }
        $order['cost_freight'] = $orderInfo['data']['cost_freight'];
        $order['user_id']      = $user_id;

        //收货地址信息
        if ($receipt_type == 1) {
            //快递邮寄
            $order['ship_area_id']   = $ushopInfo['area_id'];
            $order['ship_address']   = $ushopInfo['address'];
            $order['ship_name']      = $ushopInfo['name'];
            $order['ship_mobile']    = $ushopInfo['mobile'];
            $shipInfo                = model('common/Ship')->getShip($ushopInfo['area_id']);
            $order['logistics_id']   = $shipInfo['id'];
            $order['logistics_name'] = $shipInfo['name'];
            $order['cost_freight']   = model('common/Ship')->getShipCost($ushopInfo['area_id'], $orderInfo['data']['weight'], $order['goods_amount']);
            $order['store_id']       = 0;
        } else {
            //门店自提
            $order['ship_area_id'] = $storeInfo['area_id'];
            $order['ship_address'] = $storeInfo['address'];
            $order['ship_name']    = $lading_name;
            $order['ship_mobile']  = $lading_mobile;
            $order['store_id']     = $store_id;
            $order['logistics_id'] = 0;
            $order['cost_freight'] = 0;
        }

        //优惠信息存储
        $promotion_list = [];
        foreach ($orderInfo['data']['promotion_list'] as $k => $v) {
            if ($v['type'] == 2) {
                $promotion_list[] = $v;
            }
        }
        $order['promotion_list'] = json_encode($promotion_list);

        //积分使用情况
        $order['point']       = $orderInfo['data']['point'];
        $order['point_money'] = $orderInfo['data']['point_money'];

        $order['weight'] = $orderInfo['data']['weight'];;
        $order['order_pmt']  = isset($orderInfo['data']['order_pmt']) ? $orderInfo['data']['order_pmt'] : 0;
        $order['goods_pmt']  = isset($orderInfo['data']['goods_pmt']) ? $orderInfo['data']['goods_pmt'] : 0;
        $order['coupon_pmt'] = $orderInfo['data']['coupon_pmt'];
        $order['coupon']     = json_encode($orderInfo['data']['coupon']);
        if (isset($orderInfo['promotion_list'])) {
            $promotion_list = [];
            foreach ($orderInfo['promotion_list'] as $k => $v) {
                $promotion_list[$k] = $v['name'];
            }
            $item['promotion_list'] = json_encode($promotion_list);
        }
        $order['memo']        = $memo;
        $order['source']      = $source;
        $order['tax_type']    = $tax['tax_type'];
        $order['tax_title']   = $tax['tax_name'];
        $order['tax_code']    = $tax['tax_code'];
        $order['tax_content'] = '商品明细';

        $order['ip'] = get_client_ip();
        if($isCoupon ==1){
            $order['is_coupon'] = 1;
        }
        Db::startTrans();
        try {
            $this->save($order);
            //上面保存好订单表，下面保存订单的其他信息
            //更改库存
            $goodsModel = new Goods();
            foreach ($orderInfo['data']['items'] as $k => $v) {
                $orderInfo['data']['items'][$k]['order_id'] = $order['order_id'];
                //更改库存
                $sflag = $goodsModel->changeStock($v['product_id'], 'order', $v['nums']);
                if (!$sflag['status']) {
                    Db::rollback();
                    return $sflag;
                }
            }
            $orderItemsModel = new OrderItems();
            $orderItemsModel->saveAll($orderInfo['data']['items']);
            // 分订单
            if ($this->user->grade == 1 || $this->user->grade == 2) {
                if ($order['order_amount'] >= 399 && $order['order_amount'] <10000) {
                    (new Order())->getOrderSaveAll($order['order_id'], 1);
                }

            }
            if ($order->user->grade < 8) {
                if ($order['order_amount'] >= 10000 && $order['order_amount'] < 50000) {  // 一钻
                    (new Order())->getOrderSaveAll($order['order_id'], 2);
                }
            }
//            (new Order())->getOrderSaveAll($order['order_id']);
            //优惠券核销
            if ($coupon_code) {
                $coupon     = new Coupon();
                $coupon_res = $coupon->usedMultipleCoupon($coupon_code, $user_id);
                if (!$coupon_res['status']) {
                    Db::rollback();
                    return $coupon_res;
                }
            }

            //积分核销
            if ($order['point'] > 0) {
                $userPointLog = new UserPointLog();
                $pflag        = $userPointLog->setPoint($user_id, 0 - $order['point'], $userPointLog::POINT_TYPE_DISCOUNT, $remarks = '');
                if (!$pflag['status']) {
                    Db::rollback();
                    return $pflag;
                }
            }

            //提交数据库
            Db::commit();

            //清除购物车信息
            $cartModel = new Cart();
            $cartModel->del($user_id, $cart_ids);

            //订单记录
            $orderLog = new OrderLog();
            $orderLog->addLog($order['order_id'], $user_id, $orderLog::LOG_TYPE_CREATE, '订单创建', $order);
            //0元订单记录支付成功
            if($order['order_amount'] <= 0)
            {
                $orderLog->addLog($order['order_id'], $user_id, $orderLog::LOG_TYPE_PAY, '0元订单直接支付成功', $order);
            }

            //企业发票信息记录
            if ($tax['tax_type'] == 3) {
                $irModel = new InvoiceRecord();
                $irModel->add(['name' => $tax['tax_name'], 'code' => $tax['tax_code']]);
            }
            $order['tax_title']   = $tax['tax_name'];
            $order['tax_code']    = $tax['tax_code'];
            $order['tax_content'] = '商品明细';

            //发送消息
            $shipModel          = new Ship();
            $ship               = $shipModel->getInfo(['id' => $order['logistics_id']]);
            $order['ship_id']   = $ship['name'];
            $order['ship_addr'] = get_area($order['ship_area_id']) . $order['ship_address'];
            $order['form_id']   = $formId;
            sendMessage($user_id, 'create_order', $order);

            $result['status'] = true;
            $result['data']   = $order;
            return $result;
        } catch (\Exception $e) {
            Db::rollback();
            $result['msg'] = $e->getMessage();
            return $result;
        }
    }

    /**
     * 订单前执行
     * @param $user_id
     * @param $cart_ids
     * @param $area_id
     * @param $point
     * @param $coupon_code
     * @param int $receipt_type
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function formatOrderItems($user_id, $cart_ids, $area_id, $point, $coupon_code, $receipt_type = 1)
    {
        $cartModel = new Cart();
        $cartList  = $cartModel->info($user_id, $cart_ids, '', $area_id, $point, $coupon_code, $receipt_type);
        if (!$cartList['status']) {
            return $cartList;
        }
        foreach ($cartList['data']['list'] as $v) {
            $item['goods_id']         = $v['products']['goods_id'];
            $item['product_id']       = $v['products']['id'];
            $item['sn']               = $v['products']['sn'];
            $item['bn']               = $v['products']['bn'];
            $item['name']             = $v['products']['name'];
            $item['price']            = $v['products']['price'];
            $item['costprice']        = $v['products']['costprice'];
            $item['mktprice']         = $v['products']['mktprice'];
            $item['image_url']        = get_goods_info($v['products']['goods_id'], 'image_id');
            $item['nums']             = $v['nums'];
            $item['amount']           = $v['products']['amount'];
            $item['promotion_amount'] = isset($v['products']['promotion_amount']) ? $v['products']['promotion_amount'] : 0;
            $item['weight']           = $v['weight'];
            $item['sendnums']         = 0;
            $item['addon']            = $v['products']['spes_desc'];
            $item['get_point']        = $v['products']['point'];
            if (isset($v['products']['promotion_list'])) {
                $promotion_list = [];
                foreach ($v['products']['promotion_list'] as $k => $v) {
                    $promotion_list[$k] = $v['name'];
                }
                $item['promotion_list'] = json_encode($promotion_list);
            }
            $cartList['data']['items'][] = $item;
        }
        //unset($cartList['data']['list']);
        return $cartList;
    }

    /**
     * 判断订单是否可以评价
     * @param $order_id
     * @param $user_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function isOrderComment($order_id, $user_id)
    {
        $where[] = ['order_id', 'eq', $order_id];
        $where[] = ['user_id', 'eq', $user_id];

        $res = $this->where($where)
            ->find();
        if ($res) {
            if ($res['pay_status'] > self::PAY_STATUS_NO && $res['status'] == self::ORDER_STATUS_NORMAL && $res['ship_status'] > self::SHIP_STATUS_NO && $res['is_comment'] == self::NO_COMMENT) {
                $data = [
                    'status' => true,
                    'msg'    => '可以评价',
                    'data'   => $res
                ];
            } else {
                $data = [
                    'status' => false,
                    'msg'    => '订单状态存在问题，不能评价',
                    'data'   => $res
                ];
            }
        } else {
            $data = [
                'status' => false,
                'msg'    => '不存在这个订单',
                'data'   => $res
            ];
        }
        return $data;
    }

    /**
     * 自动取消订单
     * @param $setting
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function autoCancel($setting)
    {
        $orderLog = new OrderLog();
        unset($where);
        $where[] = ['pay_status', 'eq', self::PAY_STATUS_NO];
        $where[] = ['status', 'eq', self::ORDER_STATUS_NORMAL];
        $where[] = ['ctime', '<=', time() - $setting * 86400];

        $order_info = $this->where($where)
            ->select();

        if (count($order_info) > 0) {
            Db::startTrans();
            try {
                //更改状态和库存
                unset($order_ids);
                $order_ids = [];
                foreach ($order_info as $kk => $vv) {
                    $order_ids[] = $vv['order_id'];

                    //订单记录
                    $orderLog->addLog($vv['order_id'], $vv['user_id'], $orderLog::LOG_TYPE_AUTO_CANCEL, '订单后台自动取消', $vv);
                }
                //状态修改
                unset($w);
                $w[]         = ['order_id', 'in', $order_ids];
                $d['status'] = self::ORDER_STATUS_CANCEL;
                $d['utime']  = time();
                $this->where($w)
                    ->update($d);

                //修改库存
                $itemModel  = new OrderItems();
                $goods      = $itemModel->field('product_id, nums')->where($w)->select();
                $goodsModel = new Goods();
                foreach ($goods as $vv) {
                    $goodsModel->changeStock($vv['product_id'], 'cancel', $vv['nums']);
                }
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
            }
        }

    }

    /**
     * 自动签收订单
     * @param $setting
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function autoSign($setting)
    {
        $orderLog = new OrderLog();

        $where[] = ['pay_status', 'eq', self::PAY_STATUS_YES];
        $where[] = ['ship_status', 'eq', self::SHIP_STATUS_YES];
        $where[] = ['status', 'eq', self::ORDER_STATUS_NORMAL];
        $where[] = ['utime', '<=', time() - $setting * 86400];

        $order_list = $this->field('order_id, user_id')->where($where)->select();
        if (count($order_list) > 0) {
            unset($order_ids);
            unset($wh);
            $order_ids = [];
            foreach ($order_list as $vv) {
                $order_ids[] = $vv['order_id'];
            }
            $wh[]                 = ['order_id', 'in', $order_ids];
            $data['confirm']      = self::CONFIRM_RECEIPT;
            $data['confirm_time'] = time();
            $data['utime']        = time();
            $this->where($wh)->update($data);

            //订单记录
            $orderLog->addLogs($order_list, $orderLog::LOG_TYPE_AUTO_SIGN, '订单后台自动签收', $where);
        }

    }

    /**
     * 自动评价订单
     * @param $setting
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function autoEvaluate($setting)
    {
        $orderLog = new OrderLog();

        //查询订单
        $where[]    = ['pay_status', 'eq', self::PAY_STATUS_YES];
        $where[]    = ['ship_status', 'eq', self::SHIP_STATUS_YES];
        $where[]    = ['status', 'eq', self::ORDER_STATUS_NORMAL];
        $where[]    = ['confirm', 'eq', self::CONFIRM_RECEIPT];
        $where[]    = ['is_comment', 'eq', self::NO_COMMENT];
        $where[]    = ['confirm_time', '<=', time() - $setting * 86400];
        $order_info = $this::with('items')->field('order_id, user_id')->where($where)
            ->select()->toArray();

        unset($order_ids);
        $order_ids   = [];
        $order_items = [];
        foreach ($order_info as $vo) {
            $order_ids[] = $vo['order_id'];
            if (count($vo['items']) > 0) {
                foreach ($vo['items'] as &$vv) {
                    $vv['user_id'] = $vo['user_id'];
                }
                $order_items = array_merge($order_items, $vo['items']);
            }
            //订单记录
            $orderLog->addLog($vo['order_id'], $vo['user_id'], $orderLog::LOG_TYPE_AUTO_EVALUATION, '订单后台自动评价', $where);
        }

        //更新订单
        unset($wheres);
        $wheres[]           = ['order_id', 'in', $order_ids];
        $data['is_comment'] = self::ALREADY_COMMENT;
        $data['utime']      = time();
        $this->where($wheres)->update($data);

        //查询评价商品
        unset($goods_comment);
        $goods_comment = [];
        foreach ($order_items as $vo) {
            $goods_comment[] = [
                'score'    => 5,
                'user_id'  => $vo['user_id'],
                'goods_id' => $vo['goods_id'],
                'order_id' => $vo['order_id'],
                'addon'    => $vo['addon'],
                'content'  => '用户' . $setting . '天内未对商品做出评价，已由系统自动评价。',
                'ctime'    => time(),
            ];
        }
        model('common/GoodsComment')->insertAll($goods_comment);

    }

    /**
     * 自动完成订单
     * @param $setting
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function autoComplete($setting)
    {
        unset($where);
        $where[] = ['pay_status', 'eq', self::PAY_STATUS_YES];
        $where[] = ['status', 'eq', self::ORDER_STATUS_NORMAL];
        $where[] = ['ship_status', 'eq', self::SHIP_STATUS_YES];//已发货
        $where[] = ['confirm', 'eq', self::CONFIRM_RECEIPT];//已确认收货
        $where[] = ['payment_time', '<=', time() - $setting * 86400];

        $order_list = $this->field('order_id, user_id')
            ->where($where)
            ->select();

        if (count($order_list) > 0) {
            foreach ($order_list as $k => $v) {

                $where[]    = ['ctime', '<=', time() - $setting * 86400];
                $order_list = $this->field('order_id, user_id')
                    ->where($where)
                    ->select();
                if (count($order_list) > 0) {
                    foreach ($order_list as $k => $v) {
                        $this->complete($v['order_id']);
                    }
                }
            }
        }
    }

    /**
     * 获取当月的资金池
     * @return array
     */
    public function cashPooling()
    {
        $monthTimeStamp = $this->specifiedTimeStamp();
        $where[]        = ['utime', 'egt', $monthTimeStamp['start_time']];
        $where[]        = ['utime', 'elt', $monthTimeStamp['end_time']];
        $where[]        = ['pay_status', 'eq', self::PAY_STATUS_YES];
        $order_amount   = $this->where($where)->sum('order_amount');
        $result['data'] = $order_amount / 10;
        $result         = [
            'status' => true,
            'msg'    => '获取成功',
            'data'   => $result
        ];
        return $result;
    }

    /**
     * 获取指定年月的第一天开始和最后一天结束的时间戳
     * @param string $year 年份
     * @param string $month 月份
     * @return array (本月开始时间，本月结束时间)
     */
    public function specifiedTimeStamp($year = "", $month = "")
    {
        if ($year == "") $year = date("Y");
        if ($month == "") $month = date("m");
        $month = sprintf("%02d", intval($month));
        //填充字符串长度
        $y = str_pad(intval($year), 4, "0", STR_PAD_RIGHT);
        $month > 12 || $month < 1 ? $m = 1 : $m = $month;
        $firstDay    = strtotime($y . $m . "01000000");
        $firstDayStr = date("Y-m-01", $firstDay);
        $lastDay     = strtotime(date('Y-m-d 23:59:59', strtotime("$firstDayStr +1 month -1 day")));

        return [
            "start_time" => $firstDay,
            "end_time"   => $lastDay
        ];
    }

    /**
     * 订单催付款
     * 默认提前1小时通知
     * @param int $setting
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function remind_order_pay($setting = 0)
    {
        ini_set('date.timezone', 'Asia/Shanghai');
        $where[]           = ['pay_status', 'eq', self::PAY_STATUS_NO];
        $where[]           = ['status', 'eq', self::ORDER_STATUS_NORMAL];
        $remind_order_time = getSetting('remind_order_time');//催付款时间
        $second            = $setting * 86400 - $remind_order_time * 3600;

        $second = time() - $second;

        $where[]    = ['ctime', '<=', $second];
        $order_info = $this->where($where)
            ->select();
        if (count($order_info) > 0) {
            foreach ($order_info as $kk => $vv) {
                sendMessage($vv['user_id'], 'remind_order_pay', $vv);
            }
        }
    }

    /**
     * 获取csv数据
     * @param $post
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCsvData($post)
    {
        $result    = [
            'status' => false,
            'data'   => [],
            'msg'    => '无可导出订单'
        ];
        $header    = $this->csvHeader();
        $orderData = $this->getListFromAdmin($post, false);
        if ($orderData['count'] > 0) {
            $tempBody = $orderData['data'];
            $body     = [];
            $i        = 0;
            foreach ($tempBody as $key => $val) {
                $i++;
                $orderItems = $this->orderItems($val['order_id']);
                $itemData   = [];
                foreach ($header as $hk => $hv) {
                    if (isset($hv['modify']) && isset($val[$hv['id']]) && $val[$hv['id']]) {
                        if (function_exists($hv['modify'])) {
                            $body[$i][$hk] = $hv['modify']($val[$hv['id']]);
                        }
                    } elseif (isset($val[$hv['id']]) && $val[$hv['id']]) {
                        $body[$i][$hk] = $val[$hv['id']];
                    } else {
                        $body[$i][$hk] = '';
                    }
                }

                foreach ($orderItems as $itemKey => $itemVal) {
                    $i++;
                    $sval['item_name']   = $itemVal['name'] . '-' . $itemVal['addon'];
                    $sval['item_price']  = $itemVal['price'];
                    $sval['item_nums']   = $itemVal['nums'];
                    $sval['item_amount'] = $itemVal['amount'];
                    $sval['item_sn']     = $itemVal['sn'];
                    $sval['item_bn']     = $itemVal['bn'];
                    $sval['item_weight'] = $itemVal['weight'];
                    foreach ($header as $hk => $hv) {
                        if (isset($hv['modify']) && isset($sval[$hv['id']]) && $sval[$hv['id']]) {
                            if (function_exists($hv['modify'])) {
                                $body[$i][] = $hv['modify']($sval[$hv['id']]);
                            }
                        } elseif (isset($sval[$hv['id']]) && $sval[$hv['id']]) {
                            $body[$i][] = $sval[$hv['id']];
                        } else {
                            $body[$i][] = '';
                        }
                    }
                }
            }
            $result['status'] = true;
            $result['msg']    = '获取成功';
            $result['data']   = $body;
            return $result;
        } else {
            //失败，导出失败
            return $result;
        }
    }

    /**
     * 设置csv header
     * @return array
     */
    public function csvHeader()
    {
        return [
            [
                'id'     => 'order_id',
                'desc'   => '订单号',
                'modify' => 'convertString'
            ],
            [
                'id'     => 'ctime',
                'desc'   => '下单时间',
                'modify' => 'getTime',
            ],
            [
                'id'   => 'status_text',
                'desc' => '订单状态',
            ],
            [
                'id'   => 'username',
                'desc' => '用户名',
            ],
            [
                'id'   => 'ship_name',
                'desc' => '收货人',
            ],
            [
                'id'   => 'area_name',
                'desc' => '收货地址',
            ],
            [
                'id'     => 'ship_mobile',
                'desc'   => '收货人手机号',
                'modify' => 'convertString'
            ],
            [
                'id'   => 'pay_status',
                'desc' => '支付状态',
            ],
            [
                'id'   => 'ship_status',
                'desc' => '发货状态',
            ],
            [
                'id'   => 'order_amount',
                'desc' => '订单总额',
            ],
            [
                'id'   => 'source',
                'desc' => '订单来源',
            ],
            [
                'id'   => 'item_name',
                'desc' => '商品名称',
            ],
            [
                'id'   => 'item_price',
                'desc' => '商品单价',
            ],
            [
                'id'   => 'item_nums',
                'desc' => '购买数量',
            ],
            [
                'id'   => 'item_amount',
                'desc' => '商品总价',
            ],
            [
                'id'   => 'item_sn',
                'desc' => '货品编码',
            ],
            [
                'id'   => 'item_bn',
                'desc' => '商品编码',
            ],
            [
                'id'   => 'item_weight',
                'desc' => '商品总重量',
            ]
        ];
    }

    /**
     * 获取明细
     * @param string $order_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function orderItems($order_id = '')
    {
        $itemModel = new OrderItems();
        $items     = $itemModel->field('*')->where(['order_id' => $order_id])->select();
        if (!$items->isEmpty()) {
            return $items->toArray();
        }
        return [];
    }

    /**
     * 卖家备注
     * @param $order_id
     * @param string $mark
     * @return array
     */
    public function saveMark($order_id, $mark = '')
    {
        $return = [
            'status' => false,
            'msg'    => '备注失败',
            'data'   => $mark
        ];

        $where[]      = ['order_id', 'eq', $order_id];
        $data['mark'] = $mark;
        $result       = $this->save($data, $where);

        if ($result !== false) {
            $return['status'] = true;
            $return['msg']    = '备注成功';
        }

        return $return;
    }

    /**
     * @param $money 消费金额
     * @param $consumer_id  消费者id
     * @param $shop_id      店铺id
     * @param $shop_type    店铺类型  0个人   1 企业
     * @return array
     */
    public function settlementBonus_old($money,$consumer_id,$order_id){
        $return = [
            'status' => false,
            'msg'    => '参数有误',
        ];
        if(!$money || !$consumer_id){
            return $return;
        }
//        $orderInfo = Order::with('ShopInfo')->where(['parent_order_id'=>$order_id])->where('shop_id','neq',null)->select();
        $orderInfo = Order::with('ShopInfo')->where(['parent_order_id'=>$order_id])->select();

        $userInfo    = User::get($consumer_id);
//        $ratio = config('params.bonus');
        $prizeConfig = PrizeConfig::get(1);
        $addPoint    = 0;
        $addOtayonii = 0;
        $countMoney  = 0; // 根据店铺类型获取订单奖金金额
//        $addPoint    = $money * $ratio['pointRatio'][$shop_type] * $ratio['pointRatio']['public'];
//        $addOtayonii = $money * 0.2 * $ratio['pointRatio']['public'];

        Db::startTrans();
        try{
            // 增加店铺账户金额
            if(isset($orderInfo) && !empty($orderInfo)){
                foreach ($orderInfo as $key=>$val){
                    if($val['shop_id']){
                        $addShop      = $val['goods_amount'] * ($val['shop_type']==1 ?$prizeConfig['enterprise_shop']:$prizeConfig['personal_shop'])/100;
                        $shopOtayonii = new ShopOtayonii();
                        $remarks      = $userInfo->mobile.'购买了'.$val['goods_amount'].'元商品,店铺增加:'.$addShop;
                        $shopOtayonii->setShop($val['shop_id'],$addShop,13,3,$remarks);
                    }
                    $addPoint += $val['goods_amount'] * ($val['shop_type']==1?$prizeConfig['enterprise_point_ratio']*0.1:$prizeConfig['personal_point_ratio']*0.2)/100;
                    $addOtayonii += $val['goods_amount'] * ($val['shop_type']==1?$prizeConfig['enterprise_otayonii_ratio']*0.1:$prizeConfig['personal_otayonii_ratio']*0.2)/100;
                    $countMoney += $val['goods_amount'] * ($val['shop_type']==1?0.1:0.2);
                }
            }
            // 消费者添加积分明细
            $userPointLogModel = new UserPointLog();
            $remarks      = '购物增加'.$addPoint.'积分';
            $userPointLogModel->setPoint($consumer_id, $addPoint, 11, $remarks);

            $shopOtayonii = new ShopOtayonii();
            $remarks      = '购物增加'.$addOtayonii.'金豆';
            $shopOtayonii->setShopOtayonii($consumer_id,$addOtayonii,13,2,$remarks);

            $parentData  = (new User())->getParentAndMoney($consumer_id,3);
            if(isset($parentData) && !empty($parentData)){
                // 推荐奖
                foreach ($parentData as $key=>$val){
                    // 添加余额明细
                    if(in_array($key,[1,2])){
                        // 添加奖金明细
                        if($val['mobile']){
                            $blanceModel = new Balance();
                            $memo = '推荐奖增加'.$countMoney*$val['ratio'].'元,来源用户:'.$userInfo->mobile;
                            $blanceModel->change($val['id'],10,$countMoney*$val['ratio'],0,0,$memo);
                            (new PrizeRecord())->addPrizeRecord(['currency'=>$countMoney*$val['ratio'],'user_id'=>$val['id'],'source_id'=>$consumer_id,'type'=>1,'ctime'=>time()]);
                        }
                    }else{
                        break;
                    }
                }
                $currentRatio  = 0;
                $previousRatio = 0;
                // 极差奖
                $rangeRatio = [4=>$prizeConfig['range_province'],3=>$prizeConfig['range_city'],2=>$prizeConfig['range_county']];
                foreach ($parentData as $key=>$val){
//                    $currentRatio = $ratio['rangeRatio'][$val['grade']];
                    if($val['grade'] >=2){
                        $currentRatio = $rangeRatio[$val['grade']]/100;
                        if($currentRatio>$previousRatio){
                            if($val['mobile']){
                                $blanceModel = new Balance();
                                $memo = '极差奖增加'.$countMoney*($currentRatio-$previousRatio).'元,来源用户:'.$userInfo->mobile;
                                $blanceModel->change($val['id'],14,$countMoney*($currentRatio-$previousRatio),0,0,$memo);
                                (new PrizeRecord())->addPrizeRecord(['currency'=>$countMoney*($currentRatio-$previousRatio),'user_id'=>$val['id'],'source_id'=>$consumer_id,'type'=>2,'ctime'=>time()]);
                                $previousRatio = $currentRatio;
                            }
                        }
                    }
                }
            }
            $return['status'] = true;
            $return['msg']    = '成功';
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            $return['msg'] = $e->getMessage();
            return $return;
        }

        return $return;
    }



    /**
     * @param $money 消费金额
     * @param $consumer_id  消费者id
     * @param $shop_id      店铺id
     * @param $shop_type    店铺类型  0个人   1 企业
     * @return array
     */
    public function settlementBonus($money,$consumer_id,$order_id){
        $return = [
            'status' => false,
            'msg'    => '参数有误',
        ];
        if(!$order_id || !$consumer_id){
            return $return;
        }
//        $orderInfo = Order::with('ShopInfo')->where(['parent_order_id'=>$order_id])->where('shop_id','neq',null)->select();
        $orderInfo = Order::with('ShopInfo')->where(['parent_order_id'=>$order_id])->select();

        $userInfo    = User::get($consumer_id);
//        $ratio = config('params.bonus');
        $prizeConfig = PrizeConfig::get(1);
        $personalRatio = (100-$prizeConfig['personal_shop'])/100;  // 个人店铺奖金比例
        $enterpriseRatio = (100-$prizeConfig['enterprise_shop'])/100;  // 个人店铺奖金比例
        $addPoint    = 0;
        $addOtayonii = 0;
//        $addPoint    = $money * $ratio['pointRatio'][$shop_type] * $ratio['pointRatio']['public'];
//        $addOtayonii = $money * 0.2 * $ratio['pointRatio']['public'];
        $recommendMoney[1] = ['id'=>0,'money'=>0];
        $recommendMoney[2] = ['id'=>0,'money'=>0];
        $rangeMoney[1]     = ['id'=>0,'money'=>0];
        $rangeMoney[2]     = ['id'=>0,'money'=>0];
        $rangeMoney[3]     = ['id'=>0,'money'=>0];
        Db::startTrans();
        try{
            // 获取推荐关系
            $parentData  = (new User())->getParentAndMoney($consumer_id,3);
            // 增加店铺账户金额
            if(isset($orderInfo) && !empty($orderInfo)){
                foreach ($orderInfo as $key=>$val){
                    if($val['shop_id']){
                        $addShop      = $val['goods_amount'] * ($val['shop_type']==1 ?$prizeConfig['enterprise_shop']:$prizeConfig['personal_shop'])/100;
                        $shopOtayonii = new ShopOtayonii();
                        $remarks      = $userInfo->mobile.'购买了'.$val['goods_amount'].'元商品,店铺增加:'.$addShop;
                        $shopOtayonii->setShop($val['shop_id'],$addShop,13,3,$remarks);
                        // 店铺执行奖金
                        if($val['shop_type']==1){
                            $this->bonus($val['goods_amount']*$enterpriseRatio,$val['shop_id']);
                        }else{
                            $this->bonus($val['goods_amount']*$personalRatio,$val['shop_id']);
                        }

                    }
                    $addPoint += $val['goods_amount'] * ($val['shop_type']==1?$prizeConfig['enterprise_point_ratio']*$enterpriseRatio:$prizeConfig['personal_point_ratio']*$personalRatio)/100;
                    $addOtayonii += $val['goods_amount'] * ($val['shop_type']==1?$prizeConfig['enterprise_otayonii_ratio']*$enterpriseRatio:$prizeConfig['personal_otayonii_ratio']*$personalRatio)/100;


                    if(isset($parentData) && !empty($parentData)){
                        // 推荐奖
                        foreach ($parentData as $rkey=>$rval){
                            // 添加余额明细
                            if(in_array($rkey,[1,2])){
                                // 添加奖金明细
                                if($rval['mobile']){
                                    $recommendMoney[$rkey]['id'] = $rval['id'];
                                    if($rkey ==1){
                                        $recommendMoney[$rkey]['money'] = $recommendMoney[$rkey]['money']+$val['goods_amount'] * ($val['shop_type']==1?$enterpriseRatio*$prizeConfig['enterprise_recommend_one']/100:$personalRatio*$prizeConfig['recommend_one']/100);
                                    }else{
                                        $recommendMoney[$rkey]['money'] = $recommendMoney[$rkey]['money']+$val['goods_amount'] * ($val['shop_type']==1?$enterpriseRatio*$prizeConfig['enterprise_recommend_two']/100:$personalRatio*$prizeConfig['recommend_two']/100);
                                    }
                                }
                            }else{
                                break;
                            }
                        }
                        // 极差奖
                        $currentRatio  = 0;
                        $previousRatio = 0;
                        $range = $val['shop_type']==1?$enterpriseRatio:$personalRatio;
                        $rangeRatio_personal    = [4=>$prizeConfig['range_province'],3=>$prizeConfig['range_city'],2=>$prizeConfig['range_county']];
                        $rangeRatio_enterprise  = [4=>$prizeConfig['enterprise_range_province'],3=>$prizeConfig['enterprise_range_city'],2=>$prizeConfig['enterprise_range_county']];
                        foreach ($parentData as $jkey=>$jval){
                            if($jval['grade'] >=2){
                                $currentRatio = $val['shop_type']==1?$rangeRatio_enterprise[$jval['grade']]/100:$rangeRatio_personal[$jval['grade']]/100;
                                if($currentRatio>$previousRatio){
                                    if($jval['mobile']){
                                        $rangeMoney[$jkey]['id']    = $jval['id'];
                                        $rangeMoney[$jkey]['money'] = $rangeMoney[$jkey]['money']+$val['goods_amount']*($currentRatio-$previousRatio)*$range;
                                        $previousRatio = $currentRatio;
                                    }
                                }
                            }
                        }
                    }

                }
            }
            foreach ($recommendMoney as $key=>$val){
                if($val['id'] !=0){
                    $blanceModel = new Balance();
                    $memo = '推荐奖增加'.$val['money'].'元,来源用户:'.$userInfo->mobile;
                    $blanceModel->change($val['id'],10,$val['money'],0,0,$memo);
                    (new PrizeRecord())->addPrizeRecord(['currency'=>$val['money'],'user_id'=>$val['id'],'source_id'=>$consumer_id,'type'=>1,'ctime'=>time()]);
                }
            }

            foreach ($rangeMoney as $key=>$val){
                if($val['id'] !=0){
                    $blanceModel = new Balance();
                    $memo = '极差奖增加'.$val['money'].'元,来源用户:'.$userInfo->mobile;
                    $blanceModel->change($val['id'],14,$val['money'],0,0,$memo);
                    (new PrizeRecord())->addPrizeRecord(['currency'=>$val['money'],'user_id'=>$val['id'],'source_id'=>$consumer_id,'type'=>2,'ctime'=>time()]);
                }
            }
            // 消费者添加积分明细
            $userPointLogModel = new UserPointLog();
            $remarks      = '购物增加'.$addPoint.'积分';
            $userPointLogModel->setPoint($consumer_id, $addPoint, 11, $remarks);

            $shopOtayonii = new ShopOtayonii();
            $remarks      = '购物增加'.$addOtayonii.'金豆';
            $shopOtayonii->setShopOtayonii($consumer_id,$addOtayonii,13,2,$remarks);

            $return['status'] = true;
            $return['msg']    = '成功';
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            $return['msg'] = $e->getMessage();
            return $return;
        }

        return $return;
    }




    // 分订单
//    public function getOrderSaveAll($order_id){
//        $orderItems = OrderItems::where(['order_id'=>$order_id])->select()->toArray();
//        $order      = $this->get($order_id)->toArray();
//        $orderData = [];
//        if(isset($orderItems) && !empty($orderItems)){
//            foreach ($orderItems as $key=>$val){
//                $shop_id = (Goods::where(['id'=>$val['goods_id']])->find())['shop_id'];
//                if(!$shop_id){
//                    $orderItemsData[0][]=$val;
//                }else{
//                    $orderItemsData[$shop_id][]=$val;
//                }
//            }
//
//            foreach ($orderItemsData as $key=>$val){
//                $orderData[$key] = $order;
//                $orderData[$key]['order_id'] = get_sn(1);
//                $orderData[$key]['shop_id']  = $key;
//                $orderData[$key]['goods_amount'] = array_sum(array_column($val,'amount'));
//                $orderData[$key]['order_amount'] = array_sum(array_column($val,'amount'));
//                $orderData[$key]['parent_order_id'] = $order['order_id'];
//                foreach ($val as $k=>$v){
//                    $v['order_id'] = $orderData[$key]['order_id'];
//                    unset($v['id']);
//                    $newOrderItemsData[] = $v;
//                }
//                usleep(10000);
//            }
//            Order::insertAll($orderData);
//            OrderItems::insertAll($newOrderItemsData);
//        }
//
//    }
    // 分订单
//    public function getOrderSaveAll($order_id){
//        $orderItems = OrderItems::where(['order_id'=>$order_id])->order('amount','desc')->select()->toArray();
//        $order      = $this->get($order_id)->toArray();
//        $sql = "select p.* from jshop_user as my,jshop_user as p
//                where  my.id = {$order['user_id']}
//                and my.path like CONCAT(p.path,'/%')
//                order by layer desc";
//        $recommend = Db::query($sql);
//        $orderItemsData = [];
//        $config = [1=>199,2=>299,3=>359,4=>399];
//        if ($recommend) {
//            $recommendOne[] = $recommend[0];
//            $recomendGrade = $recommendOne[0]['grade'];
//            foreach ($recommend as $key => $val) {
//                if ($val['grade'] > $recomendGrade) {
//                    $recommendOne[$key] = $val;
//                    $recomendGrade = $val['grade'];
//                }
//            }
//            foreach ($recommendOne as $key=>$val) {
//                foreach ($orderItems as $k=>$v) {
//                    if ($v['amount'] <= $config[$val['grade']]) {
//                        $orderItemsData[$val['id']][] = $v;
//                        $config[$val['grade']] -= $v['amount'];
//                        unset($orderItems[$k]);
//                    }
//                }
//            }
//        }
//        if ($orderItems) {
//            $orderItemsData[0] = $orderItems;
//        }
//        $orderData = [];
//        foreach ($orderItemsData as $key=>$val){
//            $orderData[$key] = $order;
//            $orderData[$key]['order_id'] = get_sn(1);
//            $orderData[$key]['shop_id']  = $key;
//            $orderData[$key]['goods_amount'] = array_sum(array_column($val,'amount'));
//            $orderData[$key]['order_amount'] = array_sum(array_column($val,'amount'));
//            $orderData[$key]['parent_order_id'] = $order['order_id'];
//            foreach ($val as $k=>$v){
//                $v['order_id'] = $orderData[$key]['order_id'];
//                unset($v['id']);
//                $newOrderItemsData[] = $v;
//            }
//            usleep(10000);
//        }
//        Order::insertAll($orderData);
//        OrderItems::insertAll($newOrderItemsData);
//        exit;
//    }
    // 分明细 $type =1 一星  2一钻
    public function  getOrderSaveAll($order_id, $type = 1){
        Db::startTrans();
        try {
            $orderItems = OrderItems::where(['order_id'=>$order_id])->order('amount','desc')->select()->toArray();
            $order      = $this->get($order_id)->toArray();
            $sql = "select p.* from jshop_user as my,jshop_user as p
                where  my.id = {$order['user_id']}
                and my.path like CONCAT(p.path,'/%')
                order by p.layer desc";
            $recommend = Db::query($sql);
            $config = [2=>199,3=>299,4=>399,5=>0,8=>5000,9=>8000,10=>10000];
            if ($recommend) {
                $recomendGrade = $type == 1 ? 1:7;
                if ($type == 1) {
                    foreach ($recommend as $key => $val) {
                        if (($val['grade'] > $recomendGrade) && ($val['grade'] <= 4)) {
                            $recommendOne[] = $val;
                            $recomendGrade = $val['grade'];
                        }
                    }
                } else {
                    foreach ($recommend as $key => $val) {
                        if (($val['grade'] > $recomendGrade)) {
                            $recommendOne[] = $val;
                            $recomendGrade = $val['grade'];
                        }
                    }
                }
                if ($recommendOne) {
                    $fafang = 0;
                    foreach ($recommendOne as $key=>$val) {
                        if ($key == 0) {
                            $money = $config[$val['grade']];
                        } else {
//                            $money = $config[$val['grade']] - $config[$recommendOne[$key-1]['grade']];
                            $money = $config[$val['grade']] - $fafang;
                        }
                        foreach ($orderItems as $k=>$v) {
                            if ($v['amount'] <= $money) {
                                OrderItems::update(['user_id'=>$val['id'], 'user_mobile'=>$val['mobile']], ['id'=>$v['id']]);
                                $money -= $v['amount'];
                                $fafang += $v['amount'];
                                unset($orderItems[$k]);
                            }
                        }
                    }
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }
    // 支付成功调用 增加排行榜数据  $type 1 准一星 2复购
    public function addRanking($order_id, $type=1) {
        Db::startTrans();
        try {
            $order     = $this->get($order_id);
            $recommend = User::get($order->user->pid);
            // 增加这周 升级一星/复购数
            $setting = new Setting;
            if ($type == 1) {
                $number  = $setting->getValue('upgrade_one_start');
                $setting->setValue('upgrade_one_start',$number+1);
                $level = 4;
            } else {
                $number  = $setting->getValue('futou_one_start');
                $setting->setValue('futou_one_start',$number+1);
                $level = 3;
            }
            if ($recommend) {
                if ($type == 1) {
                    $recommend->recommend_orders_number += 1;
                } else {
                    $recommend->recommend_futou_number += 1;
                }
                $recommend->save();
            }
            //更改 明细中 level 值
            $sql = "update jshop_order_items as oi, jshop_user as u set oi.level = u.grade where order_id = $order_id and user_id = u.id";
            Db::query($sql);
            $orderItems = OrderItems::where(['order_id'=>$order_id, 'level'=>$level])->field('min(user_id) as user_id,sum(amount) as amount')->group('user_id')->select()->toArray();
            foreach ($orderItems as $key=>$val) {
                $ranking = Ranking::where(['user_id'=>$val['user_id'],'is_send'=>0, 'level'=>$level])->find();
                if ($ranking) {
                    $ranking->money += $val['amount'];
                    $ranking->save();
                } else {
                    $period = $setting->getValue('ranking_period_num');
                    $rankingModel = new Ranking();
                    $data['user_id'] = $val['user_id'];
                    $data['money']   = $val['amount'];
                    $data['level']   = $level;
                    $data['orders_user'] = $order->user_id;
                    $data['period']  = $period;
                    $data['ctime'] = time();
                    $rankingModel->save($data);
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }

    }

    // 分红奖金
    public function fenhong($level) {
        Db::startTrans();
        try {
            $oneStartRanking = Ranking::where(['level'=>$level,'is_send'=>0])->order('money', 'desc')->select()->toArray();
            $setting         = new Setting;
            // 本周准一星购物数
            $upgradeNumber   = $level == 4 ? $setting->getValue('upgrade_one_start') : $setting->getValue('futou_one_start');

            // 四星分红金额
            $oneStartMoney   = 20*$upgradeNumber*0.3;
            // 四星数
            $oneStartCount = count($oneStartRanking);
            // 分红奖
            if ($oneStartCount >0) {
                if ($oneStartCount <= 2) {
                    foreach ($oneStartRanking as $key=>$val) {
                        (new PrizeRecord())->bonusCapping($val['user_id'], null, round($oneStartMoney,2), 3);
                    }
                } else {
                    $oneStage   = round($oneStartCount * 0.2);
                    $twoStage   = round($oneStartCount * 0.3);
                    $threeStage = $oneStartCount - $oneStage - $twoStage;
                    foreach ($oneStartRanking as $key=>$val) {
                        if (($key+1) <= $oneStage) {
                            (new PrizeRecord())->bonusCapping($val['user_id'], null, round($oneStartMoney/$oneStage,2), 3);
                        }
                        if (($key+1) <= ($twoStage+$oneStage) && ($key+1) > $oneStage) {
                            (new PrizeRecord())->bonusCapping($val['user_id'], null, round($oneStartMoney/$twoStage,2), 3);
                        }
                        if (($key+1) <= $oneStartCount && ($key+1) > ($twoStage+$oneStage)) {
                            (new PrizeRecord())->bonusCapping($val['user_id'], null, round($oneStartMoney/$threeStage,2), 3);
                        }
                    }
                }

                Ranking::update(['is_send'=>1], ['is_send'=>0, 'level'=>$level]);
                $setting->setValue($level == 4 ?'upgrade_one_start' : 'futou_one_start',0);
            }
            // 增加期数
            $period = $setting->getValue('ranking_period_num');
            $setting->setValue('ranking_period_num',$period+1);
            // 直推分红奖
            $field     = $level == 4 ? 'recommend_orders_number' : 'recommend_futou_number';
            $recommend = User::order($field, 'desc')->where("$field != 0")->find();
            if ($recommend) {
                (new PrizeRecord())->bonusCapping($recommend->id, null, round(20*$upgradeNumber*0.1,2), 4);
                User::update([$field=>0], "$field != 0");
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    // 会员升级
    public function userUpgrade($user_id){
        return Db::query("call user_upgrade($user_id)");
    }
    // 获取推荐指定会员等级的人数
    public function getRecommendByGrade($user_id, $grade){
        $sql = "select count(1) as num from jshop_user as my,jshop_user as p
                where  my.id = $user_id
                and p.path like CONCAT(my.path,'/%')
				and p.layer - my.layer = 1
				and p.grade >= $grade";
        return Db::query($sql)[0]['num'];
    }
    // 获取伞下指定会员等级的人数
    public function getUnderByGrade($user_id, $grade){
        $sql = "select count(1) as num from jshop_user as my,jshop_user as p
                where  my.id = $user_id
                and p.path like CONCAT(my.path,'/%')
				and p.grade >= $grade";
        return Db::query($sql)[0]['num'];
    }


    public function ShopInfo()
    {
        return $this->hasOne('Shop','id','shop_id')->bind([
            'shop_type'=>'type'
        ]);
    }


    /**
     * @param $money 消费金额
     * @param $consumer_id  消费者id
     * @param $shop_id      店铺id
     * @param $shop_type    店铺类型  0个人   1 企业
     * @return array
     */
    public function offlineBonus($money,$consumer_id,$shop_id){
        $return = [
            'status' => false,
            'msg'    => '参数有误',
        ];
        if(!$money || !$consumer_id || !$shop_id){
            return $return;
        }
        $shopInfo = Shop::get($shop_id);
        $userInfo    = User::get($consumer_id);
        $prizeConfig = PrizeConfig::get(1);

        // 增加店铺账户金额
        $addShop      = $money * ($shopInfo['type']==1 ?$prizeConfig['enterprise_shop']:$prizeConfig['personal_shop'])/100;
        $personalRatio = (100-$prizeConfig['personal_shop'])/100;  // 个人店铺奖金比例
        $enterpriseRatio = (100-$prizeConfig['enterprise_shop'])/100;  // 个人店铺奖金比例

        $shopOtayonii = new ShopOtayonii();
        $remarks      = $userInfo->mobile.'购买了'.$money.'元商品,店铺增加:'.$addShop;
        $shopOtayonii->setShop($shop_id,$addShop,13,3,$remarks);

        $addPoint = $money * ($shopInfo['type']==1?$prizeConfig['enterprise_point_ratio']*$enterpriseRatio:$prizeConfig['personal_point_ratio']*$personalRatio)/100;
        $addOtayonii = $money * ($shopInfo['type']==1?$prizeConfig['enterprise_otayonii_ratio']*$enterpriseRatio:$prizeConfig['personal_otayonii_ratio']*$personalRatio)/100;
        $recommentRatio = $shopInfo['type']==1 ?[1=>$prizeConfig['enterprise_recommend_one'],2=>$prizeConfig['enterprise_recommend_two']] : [1=>$prizeConfig['recommend_one'],2=>$prizeConfig['recommend_two']] ;
        $rangeRatio = $shopInfo['type']==1?[4=>$prizeConfig['enterprise_range_province'],3=>$prizeConfig['enterprise_range_city'],2=>$prizeConfig['enterprise_range_county']]:[4=>$prizeConfig['range_province'],3=>$prizeConfig['range_city'],2=>$prizeConfig['range_county']];


        $countMoney = $money * ($shopInfo['type']==1?$enterpriseRatio:$personalRatio);
        Db::startTrans();
        try{
            // 店铺执行推荐奖
            $this->bonus($countMoney,$shop_id);

            // 消费者添加积分明细
            $userPointLogModel = new UserPointLog();
            $remarks      = '购物增加'.$addPoint.'积分';
            $userPointLogModel->setPoint($consumer_id, $addPoint, 11, $remarks);

            $shopOtayonii = new ShopOtayonii();
            $remarks      = '购物增加'.$addOtayonii.'金豆';
            $shopOtayonii->setShopOtayonii($consumer_id,$addOtayonii,13,2,$remarks);

            $parentData  = (new User())->getParentAndMoney($consumer_id,3);
            if(isset($parentData) && !empty($parentData)){
                // 推荐奖
                foreach ($parentData as $key=>$val){
                    // 添加余额明细
                    if(in_array($key,[1,2])){
                        // 添加奖金明细
                        if($val['mobile']){
                            $blanceModel = new Balance();
                            $memo = '推荐奖增加'.$countMoney*$recommentRatio[$key]*0.01.'元,来源用户:'.$userInfo->mobile;
                            $blanceModel->change($val['id'],10,$countMoney*$recommentRatio[$key]*0.01,0,0,$memo);
                            (new PrizeRecord())->addPrizeRecord(['currency'=>$countMoney*$recommentRatio[$key]*0.01,'user_id'=>$val['id'],'source_id'=>$consumer_id,'type'=>1,'ctime'=>time()]);
                        }
                    }else{
                        break;
                    }
                }
                $currentRatio  = 0;
                $previousRatio = 0;
                // 极差奖
                foreach ($parentData as $key=>$val){
//                    $currentRatio = $ratio['rangeRatio'][$val['grade']];
                    if($val['grade'] >=2){
                        $currentRatio = $rangeRatio[$val['grade']]/100;
                        if($currentRatio>$previousRatio){
                            if($val['mobile']){
                                $blanceModel = new Balance();
                                $memo = '极差奖增加'.$countMoney*($currentRatio-$previousRatio).'元,来源用户:'.$userInfo->mobile;
                                $blanceModel->change($val['id'],14,$countMoney*($currentRatio-$previousRatio),0,0,$memo);
                                (new PrizeRecord())->addPrizeRecord(['currency'=>$countMoney*($currentRatio-$previousRatio),'user_id'=>$val['id'],'source_id'=>$consumer_id,'type'=>2,'ctime'=>time()]);
                                $previousRatio = $currentRatio;
                            }
                        }
                    }
                }
            }
            $return['status'] = true;
            $return['msg']    = '成功';
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            $return['msg'] = $e->getMessage();
            return $return;
        }

        return $return;
    }

    public function bonus($countMoney,$shop_id){
        $prizeConfig = PrizeConfig::get(1);
        $shopInfo    = Shop::get($shop_id);
        $parentData  = (new User())->getParentAndMoney($shopInfo['user_id'],3);
        $recommentRatio = $shopInfo['type']==1 ?[1=>$prizeConfig['enterprise_recommend_one'],2=>$prizeConfig['enterprise_recommend_two']] : [1=>$prizeConfig['recommend_one'],2=>$prizeConfig['recommend_two']] ;
        $rangeRatio = $shopInfo['type']==1?[4=>$prizeConfig['enterprise_range_province'],3=>$prizeConfig['enterprise_range_city'],2=>$prizeConfig['enterprise_range_county']]:[4=>$prizeConfig['range_province'],3=>$prizeConfig['range_city'],2=>$prizeConfig['range_county']];
        if(isset($parentData) && !empty($parentData)){
            // 推荐奖
            foreach ($parentData as $key=>$val){
                // 添加余额明细
                if(in_array($key,[1,2])){
                    // 添加奖金明细
                    if($val['mobile']){
                        $blanceModel = new Balance();
                        $memo = '推荐奖增加'.$countMoney*$recommentRatio[$key]*0.01.'元,来源店铺:'.$shopInfo['name'];
                        $blanceModel->change($val['id'],10,$countMoney*$recommentRatio[$key]*0.01,0,0,$memo);
                        (new PrizeRecord())->addPrizeRecord(['currency'=>$countMoney*$recommentRatio[$key]*0.01,'user_id'=>$val['id'],'source_id'=>$shopInfo['user_id'],'type'=>1,'ctime'=>time()]);
                    }
                }else{
                    break;
                }
            }
            // 极差奖
            $currentRatio  = 0;
            $previousRatio = 0;
            // 极差奖
            foreach ($parentData as $key=>$val){
//                    $currentRatio = $ratio['rangeRatio'][$val['grade']];
                if($val['grade'] >=2){
                    $currentRatio = $rangeRatio[$val['grade']]/100;
                    if($currentRatio>$previousRatio){
                        if($val['mobile']){
                            $blanceModel = new Balance();
                            $memo = '极差奖增加'.$countMoney*($currentRatio-$previousRatio).'元,来源店铺:'.$shopInfo['name'];
                            $blanceModel->change($val['id'],14,$countMoney*($currentRatio-$previousRatio),0,0,$memo);
                            (new PrizeRecord())->addPrizeRecord(['currency'=>$countMoney*($currentRatio-$previousRatio),'user_id'=>$val['id'],'source_id'=>$shopInfo['user_id'],'type'=>2,'ctime'=>time()]);
                            $previousRatio = $currentRatio;
                        }
                    }
                }
            }
        }
    }

}