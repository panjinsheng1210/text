<?php
namespace app\api\controller;
use app\common\controller\Api;
use app\common\model\Area;
use app\common\model\Balance;
use app\common\model\GoodsComment;
use app\common\model\PrizeRecord;
use app\common\model\Ranking;
use app\common\model\Setting;
use app\common\model\ShopOtayonii;
use app\common\model\UserBankcards;
use app\common\model\UserPointLog;
use app\common\model\UserShip;
use app\common\model\UserTocash;
use app\common\model\UserToken;
use app\common\model\User as UserModel;
use app\common\model\GoodsBrowsing;
use app\common\model\GoodsCollection;
use app\common\model\UserWx;
use app\common\model\BillPayments;
use app\common\model\UserTransfer;
use app\common\model\OtayoniiPrice;
use app\common\model\OtayoniiSold;
use org\Curl;
use think\facade\Cache;
use think\facade\Request;
use think\Container;
use app\common\model\LeaveMessage;
class User extends Api
{
    /**
     * 手机号密码登陆
     * @return array
     */
    public function login()
    {
        $platform = input('param.platform',1);      //1就是h5登陆（h5端和微信公众号端），2就是微信小程序登陆，3是支付宝小程序，4是app，5是pc
        $userModel = new UserModel();
        $data = input('param.');

        return $userModel->toLogin($data, 2,$platform);
    }


    /**
     * 手机短信验证注册账号
     * mobile       手机号码，必填
     * code         手机验证码，必填
     * invitecode   邀请码，推荐人的邀请码 选填
     * password     注册的时候，可以传密码 选填
     * repassword   重复密码
     * user_wx_id   第三方登录，微信公众号里的登陆，微信小程序登陆等需要绑定账户的时候，要传这个参数，这是第一次的时候需要这样绑定，以后就不需要了  选填
     *
     * @return array
     */
    public function smsRegister()
    {
        $platform = input('param.platform',1);
        $userModel = new UserModel();
        $data = input('param.');
        return $userModel->smsRegister($data, 2,$platform);
    }


    /**
     * 微信小程序创建用户，不登陆，只是保存登录态
     * @return array
     */
    public function wxappLogin1()
    {
        $result = [
            'status' => false,
            'data' => [],
            'msg' => ''
        ];

        if(!input("?param.code")){
            $result['msg'] = 'code参数缺失';
            return $result;
        }
        $userWxModel = new UserWx();
        $type = input('type','weixin');

        if($type == 'weixin'){
            $userWxModel = new UserWx();
            $data = $userWxModel->codeToInfo(input('param.code'));
            if($data['add']){
                //往user表里面的加数据
                $userModel = new UserModel();
                $insertId = $userModel->insertUser();
                $userWxModel->updateWxUser($data['data'],$insertId);
            }
            return $data;
        }elseif ($type == 'alipay'){
            return $userWxModel->alipayCodeToInfo(input('param.code'));
        }
    }


    /**
     * 微信小程序传过来了手机号码，那么取他的手机号码
     * @return array 他肯定知道 直接问老板
     */
    public function wxappLogin2()
    {
        $result = [
            'status' => false,
            'data' => [],
            'msg' => ''
        ]; 

        if(!input("?param.open_id"))
        {
            $result['msg'] = 'open_id';
            return $result;
        }
        if(!input("?param.iv"))
        {
            $result['msg'] = 'iv参数缺失';
            return $result;
        }
        if(!input("?param.edata"))
        {
            //加密的encryptedData数据，这是个加密的字符串
            $result['msg'] = '加密参数缺失';
            return $result;
        }
        $userWxModel = new UserWx();
        $re = $userWxModel->updateWxInfo(input('param.open_id'),input('param.edata'),input('param.iv'));

        $invitecode =  input('param.pid',0);
        if(!$re['status'])
        {
            return $re;
        }
        if($re['data']['update'] == 0){
            $re['data']['invitecode'] = $invitecode;
            //更新
            $userModel = new UserModel();
            $userModel->wxLogin($re['data'], 2);
            //把微信表的update字段改成1
            $userWxModel->updateWxUpdate($re['data']['user_id']);
        }
        $userTokenModel = new UserToken();
        $re = $userTokenModel->setToken($re['data']['user_id'],2);

        if($re['status']){
            $re['data'] = ['token'=>$re['data']];
        }

        return $re;
        /*if($re['data']['user_id'] == 0){
            //未绑定用户，需要先绑定手机号码
            $result['status'] = true;
            $result['data'] = ['user_wx_id' => $re['data']['id']];
            return $result;
        }else{
            //绑定好手机号码了，去登陆,去取user_token
            $userTokenModel = new UserToken();
            $re = $userTokenModel->setToken($re['data']['user_id'],2);
            if($re['status']){
                $re['data'] = ['token'=>$re['data']];
            }
            return $re;
        }*/
    }


    /**
     * 发送登陆注册短信，type为1注册，为2登陆
     * @return array|mixed
     */
    public function sms()
    {
        $result = [
            'status' => false,
            'data' => [],
            'msg' => '成功'
        ];
        $userModel = new UserModel();
        if(!input("?param.mobile"))
        {
            $result['msg'] = '请输入手机号码';
            return $result;
        }
        //code的值可以为loign，reg，veri
        if(!input("?param.code"))
        {
            $result['msg'] = '缺少核心参数';
            return $result;
        }
        $code = input('param.code');
        $type = input('param.type');
        if($type == 'bind'){ //绑定会员，这个if迟早要拿掉，绑定的话，也发送login状态就行
            $code = 'login';
        }
        return $userModel->sms(input('param.mobile'),$code);
    }


    /**
     * 退出
     * @return array
     */
    public function logout()
    {
        $result = [
            'status' => false,
            'data' => [],
            'msg' => ''
        ];
        if(!input("?param.token"))
        {
            $result['msg'] = '请输入token';
            return $result;
        }
        $userTokenModel = new UserToken();
        return $userTokenModel->delToken(input("param.token"));
    }


    /**
     * 注册，此接口迟早要废弃，建议直接使用smsLogin接口
     * @return array
     */
    public function reg()
    {
        $userModel = new UserModel();
        $data = input('post.');
        return $userModel->smsLogin($data,2);
    }


    /**
     * 用户信息
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function info()
    {
        $result = [
            'status' => false,
            'data' => [],
            'msg' => ''
        ];
        $userModel = new UserModel();
        $userInfo = $userModel
            ->field('id,username,mobile,sex,birthday,avatar,nickname,balance,point,status,isshop,otayonii,coupon,email,password2')
            ->where(array('id'=>$this->userId))
            ->find();
        if($userInfo !== false)
        {
            $userInfo['avatar'] = _sImage($userInfo['avatar']);
            $userInfo['price'] = $userModel->returnBeanPrice($this->userId);
            $userInfo['password2'] = $userInfo['password2'] ? true : false;
            $result['data'] = $userInfo;
            $result['status'] = true;
        }
        else
        {
            $result['msg'] = '未找到此用户';
        }
        return $result;
    }


    /**
     * 更换头像
     * @return array|mixed
     */
    public function changeAvatar()
    {
        $result = [
            'status' => false,
            'data' => input('param.'),
            'msg' => '保存失败'
        ];
        if(!input("?param.avatar"))
        {
            return error_code(11003);
        }
        $userModel = new UserModel();
        if($userModel->changeAvatar($this->userId,input('param.avatar')))
        {
            $result['status'] = true;
            $result['data']['avatar'] = input('param.avatar');
            $result['msg'] = '保存成功';
        }
        return $result;
    }


    /**
     * 编辑用户信息
     * @return array|mixed
     */
    public function editInfo()
    {
        $sex = input('param.sex','');
        $username = input('param.username','');
        $mobile = input('param.mobile','');
        $email = input('param.email','');
        $userModel = new UserModel();
        return $userModel->editInfo($this->userId,$sex,$username,$mobile,$email);
    }


    /**
     * 添加商品浏览足迹
     * @return array
     */
    public function addGoodsBrowsing()
    {
        $result = [
            'status' => false,
            'data' => [],
            'msg' => ''
        ];
        if(!input("?param.goods_id"))
        {
            $result['msg'] = '请输入goods_id';
            return $result;
        }
        $goodsBrowsingModel = new GoodsBrowsing();
        return $goodsBrowsingModel->toAdd($this->userId, input("param.goods_id"));
    }


    /**
     * 删除商品浏览足迹
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function delGoodsBrowsing()
    {
        $result = [
            'status' => false,
            'data' => [],
            'msg' => ''
        ];
        if(!input("?param.goods_ids"))
        {
            $result['msg'] = '请输入ids';
            return $result;
        }
        $goodsBrowsingModel = new GoodsBrowsing();
        return $goodsBrowsingModel->toDel($this->userId,input("param.goods_ids"));
    }


    /**
     * 取得商品浏览足迹
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function goodsBrowsing()
    {
        if(input("?param.limit"))
        {
            $limit = input("param.limit");
        }
        else
        {
            $limit = config('jshop.page_limit');
        }
        if(input("?param.page"))
        {
            $page = input("param.page");
        }
        else
        {
            $page = 1;
        }
        $goodsBrowsingModel = new GoodsBrowsing();
        return $goodsBrowsingModel->getList($this->userId, $page , $limit);
    }


    /**
     * 添加商品收藏（关注）
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function goodsCollection()
    {
        $result = [
            'status' => false,
            'data' => [],
            'msg' => ''
        ];
        if(!input("?param.goods_id"))
        {
            $result['msg'] = '请输入goods_id';
            return $result;
        }
        $goodsCollectionModel = new GoodsCollection();
        return $goodsCollectionModel->toDo($this->userId, input("param.goods_id"));
    }


    /**
     * 取得商品收藏记录（关注）
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function goodsCollectionList()
    {
        if(input("?param.limit"))
        {
            $limit = input("param.limit");
        }
        else
        {
            $limit = config('jshop.page_limit');
        }
        if(input("?param.page"))
        {
            $page = input("param.page");
        }
        else
        {
            $page = 1;
        }
        $goodsCollectionModel = new GoodsCollection();
        return $goodsCollectionModel->getList($this->userId,$page , $limit);
    }


    /**
     * 存储用户收货地址接口
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function saveUserShip()
    {
        //传入进来的数据
        $area_id = input('area_id');
        $user_name = input('user_name');
        $detail_info = input('detail_info');
        $tel_number = input('tel_number');
        $is_def = input('is_def');
        $user_id = $this->userId;

        $data['user_id'] = $user_id;
        $data['area_id'] = $area_id;
        $data['address'] = $detail_info;
        $data['name'] = $user_name;
        $data['mobile'] = $tel_number;
        $data['is_def'] = $is_def;

        //存储收货地址
        $model = new UserShip();
        $result = $model->saveShip($data);
        if($result !== false)
        {
            $return_data = array(
                'status' => true,
                'msg' => '存储收货地址成功',
                'data' => $result
            );
        }
        else
        {
            $return_data = array(
                'status' => false,
                'msg' => '存储收货地址失败',
                'data' => $result
            );
        }
        return $return_data;
    }


    /**
     * H5 添加收货地址
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function vueSaveUserShip()
    {
        $data['user_id'] = $this->userId;
        $data['area_id'] = input('param.area_id');
        $data['address'] = input('param.address');
        $data['name'] = input('param.name');
        $data['mobile'] = input('param.mobile');
        $data['is_def'] = input('param.is_def');
        $model = new UserShip();
        return $model->vueSaveShip($data);
//        if($result)
//        {
//            $return_data = [
//                'status' => true,
//                'msg' => '存储收货地址成功',
//                'data' => $result
//            ];
//        }
//        else
//        {
//            $return_data = [
//                'status' => false,
//                'msg' => '存储收货地址失败',
//                'data' => $result
//            ];
//        }
//        return $return_data;
    }


    /**
     * 获取收货地址详情
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getShipDetail()
    {
        $id = input('param.id');
        $model = new UserShip();
        $result = $model->getShipById($id,$this->userId);
        if($result)
        {
            $result['area_name'] = get_area($result['area_id']);
            $res = [
                'status' => true,
                'msg' => '获取成功',
                'data' => $result
            ];
        }
        else
        {
            $res = [
                'status' => false,
                'msg' => '该收货地址不存在',
                'data' => ''
            ];
        }
        return $res;
    }


    /**
     * 收货地址编辑
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editShip()
    {
        $data['name'] = input('param.name');
        $data['area_id'] = input('param.area_id');
        $data['address'] = input('param.address');
        $data['mobile'] = input('param.mobile');
        $data['is_def'] = input('param.is_def');
        $data['id'] = input('param.id');
        $model = new UserShip();
        return $model->editShip($data, $this->userId);
    }


    /**
     * 删除收货地址
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function removeShip()
    {
        if(!input('param.id'))
        {
            return error_code(10051);
        }
        $model = new UserShip();
        return $model->removeShip(input('param.id'), $this->userId);
    }


    /**
     * 设置默认地址
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function setDefShip()
    {
        if(!input('param.id'))
        {
            return error_code(10051);
        }
        $model = new UserShip();
        return $model->setDefaultShip(input('param.id'),$this->userId);
    }


    /**
     * 获取用户收货地址列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserShip()
    {
        $user_id = $this->userId;
        $model = new UserShip();
        $list = $model->getUserShip($user_id);
        if($list)
        {
            $return_data = array(
                'status' => true,
                'msg' => '获取用户收货地址成功',
                'data' => $list
            );
        }
        else
        {
            $return_data = array(
                'status' => true,
                'msg' => '用户暂无收货地址',
                'data' => $list
            );
        }
        return $return_data;
    }


    /**
     * 获取收货地址全部名称
     * @return string
     */
    public function getAllName()
    {
        $id = input('id');
        return get_area($id);
    }


    /**
     * 获取最终地区ID
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAreaId()
    {
        $province_name = input('province_name');
        $city_name = input('city_name');
        $county_name = input('county_name');
        $postal_code = input('postal_code');
        $model = new Area();
        $area_id = $model->getThreeAreaId($county_name, $city_name, $province_name, $postal_code);
        if($area_id)
        {
            $res = [
                'status' => true,
                'msg' => '获取成功',
                'data' => $area_id
            ];
        }
        else
        {
            $res = [
                'status' => false,
                'msg' => '获取失败',
                'data' => $area_id
            ];
        }
        return $res;
    }


    /**
     * 支付
     * @return array|mixed
     */
    public function pay()
    {
        if(!input("?param.ids"))
        {
            return error_code(13100);
        }
        if(!input("?param.payment_code"))
        {
            return error_code(10055);
        }
        if(!input("?param.payment_type"))
        {
            return error_code(10051);
        }

        //支付的时候，有一些特殊的参数需要传递到支付里面，这里就是干这个事情的,key=>value格式的一维数组
        $data = input('param.');
        if(!isset($data['params']))
        {
            $params = [];
        }
        else
        {
            $params = $data['params'];
        }
        $billPaymentsModel = new BillPayments();
        //生成支付单,并发起支付
        return $billPaymentsModel->pay(input('param.ids'),input('param.payment_code'),$this->userId,input('param.payment_type'),$params);
    }


    /**
     * 订单评价接口
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function orderEvaluate()
    {
        if(!input('items/a'))
        {
            //缺少评价商品信息
            return error_code(13400);
        }
        if(!input('order_id'))
        {
            //没有order_id
            return error_code(13401);
        }

        $order_id = input('order_id');
        $items = input('items/a');

        //添加评价
        $model = new GoodsComment();
        $result = $model->addComment($order_id, $items, $this->userId);
        return $result;
    }


    /**
     * 获取用户默认收货地址
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserDefaultShip()
    {
        $user_id = $this->userId;
        $model = new UserShip();
        $res = $model->getUserDefaultShip($user_id);
        return $res;
    }


    /**
     * 判断是否签到
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function isSign()
    {
        $user_id = $this->userId;
        $userPointLog = new UserPointLog();
        $res = $userPointLog->isSign($user_id);
        return $res;
    }


    /**
     * 签到操作
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\BindParamException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function sign()
    {
        $user_id = $this->userId;
        $userPointLog = new UserPointLog();
        $res = $userPointLog->sign($user_id);
        return $res;
    }


    /**
     * 获取签到信息
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getSignInfo()
    {
        $user_id = $this->userId;
        $userPointLog = new UserPointLog();
        return $userPointLog->getSignInfo($user_id);
    }


    /**
     * 获取用户积分
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserPoint()
    {
        $user_id = $this->userId;
        $order_money = Request::param('order_money', 0);
        $userModel = new UserModel();
        return $userModel->getUserPoint($user_id, $order_money);
    }




    /**
     * 获取我的银行卡列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getBankCardList()
    {
        $bankCardsModel = new UserBankcards();
        return $bankCardsModel->getMyBankcardsList($this->userId);
    }


    /**
     * 获取默认的银行卡
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getDefaultBankCard()
    {
        $bankCardsModel = new UserBankcards();
        return $bankCardsModel->defaultBankCard($this->userId);
    }


    /**
     * 添加银行卡
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addBankCard()
    {
        $bankCardsModel = new UserBankcards();
        $data = [
            'bank_area_id' => input('param.areaId/d'), //开户行地区
            'bank_name'=>input('param.bankName'),//银行名称
            'account_name' => input('param.accountName'), //持卡人
            'card_number' => input('param.cardNumber'), //银行卡号
            'card_type' =>1,
            'is_default' =>1
        ];
        return $bankCardsModel->addBankcards($this->userId, $data);
    }


    /**
     * 删除银行卡
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function removeBankCard()
    {
        $card_id = input('param.id/d');
        if (!$card_id) return error_code(10051);
        $bankCardsModel = new UserBankcards();
        return $bankCardsModel->delBankcards($this->userId, $card_id);
    }


    /**
     * 设置默认银行卡
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function setDefaultBankCard()
    {
        $card_id = input('param.id/d');
        if (!$card_id) return error_code(10051);
        $bankCardsModel = new UserBankcards();
        return $bankCardsModel->setDefault($this->userId, $card_id);
    }


    /**
     * 获取银行卡信息
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getBankCardInfo()
    {
        $card_id = input('param.id/d');
        if (!$card_id) return error_code(10051);
        $bankCardsModel = new UserBankcards();
        return $bankCardsModel->getBankcardInfo($this->userId, $card_id);
    }


    /**
     *
     * 获取银行卡组织信息
     * @return array|mixed
     */
    public function getBankCardOrganization()
    {
        $card_code = input('param.card_code');
        if (!$card_code) return error_code(11017);
        $bankCardsModel = new UserBankcards();
        return $bankCardsModel->bankCardsOrganization($card_code);
    }


    /**
     * 用户修改密码
     * @return array|mixed
     */
    public function editPwd()
    {
        if (!input("?param.pwd")) return error_code(11012);
        if (!input('param.newpwd')) return error_code(11013);
        if (!input('param.repwd')) return error_code(11014);
        if (!input('param.type')) return error_code(11023);
        $data = [
            'password' => input('param.pwd'),
            'newPwd' => input('param.newpwd'),
            'rePwd' => input('param.repwd'),
            'type' => input('param.type'),
            'user_id' => $this->userId
        ];
        $userModel = new userModel();
        return $userModel->checkCode($data);
    }

    /**
     * 用户设置交易密码
     * @return array|mixed
     */
    public function setPwd2()
    {
        if (!input('param.newpwd')) return error_code(11013);
        if (!input('param.repwd')) return error_code(11014);
        $data = [
            'newPwd' => input('param.newpwd'),
            'rePwd' => input('param.repwd'),
            'type' => 'password2',
            'user_id' => $this->userId
        ];
        $userModel = new userModel();
        return $userModel->checkCode($data,0);
    }

    /**
     * 用户找回密码
     * @return array|mixed
     */
    public function forgotPwd()
    {
        if (!input('param.mobile')) return error_code(11051);
        if (!input('param.code')) return error_code(10013);
        if (!input('param.newpwd')) return error_code(11013);
        if (!input('param.repwd')) return error_code(11014);
        $data = [
            'mobile' => input('param.mobile'),
            'code' => input('param.code'),
            'newPwd' => input('param.newpwd'),
            'rePwd' => input('param.repwd'),
        ];
        $userModel = new userModel();
        return $userModel->checkCode($data);
    }


    /**
     * 获取我的余额明细
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userBalance()
    {
        $page = Request::param('page', 1);
        $limit = Request::param('limit', config('jshop.page_limit'));
        $order = Request::param('order', 'ctime desc');
        $type = Request::param('type', 0);
        if ($type == 5) {
            $type='10,14';
        }
        $balanceModel = new Balance();
        return $balanceModel->getBalanceList($this->userId, $order, $page, $limit, $type);
    }


    /**
     * 获取用户推荐列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function recommend()
    {
        $page = input('param.page', 1);
        $limit = input('param.limit', config('jshop.page_limit'));
        $userModel = new UserModel();
        return $userModel->recommendList($this->userId, $page, $limit);
    }


    /**
     * 邀请码
     * @return array
     */
    public function sharecode()
    {
        $userModel = new UserModel();
        return $result = [
            'status' => true,
            'data' => $userModel->getShareCodeByUserId($this->userId),
            'msg' => ''
        ];
    }


    /**
     * 用户提现申请
     * @return array|mixed
     */
    public function cash()
    {
        $password2 = input('param.password2');
        $userInfo = UserModel::get($this->userId);
        if(md5(md5($password2).$userInfo->ctime)!=$userInfo->password2) return error_code(17011);
        $money       = input('param.money');
        $remarks     = input('param.remarks','');
        if (!$money) return error_code(11018);
        $userToCashModel = new UserTocash();
        return $userToCashModel->tocash($this->userId, $money,$remarks);
    }
    /**
     * 用户撤销提现
     * @return array|mixed
     */
    public function cancelCash()
    {
        $id = input('param.id');
        if (!$id) return error_code(11023);
        $userToCashModel = new UserTocash();
        return $userToCashModel->cancelTocash($id);
    }
    /**
     * 获取用户提现记录
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cashList()
    {
        $page = input('param.page', 1);
        $limit = input('param.limit', config('jshop.page_limit'));
        $type = input('param.type', '');
        $userToCashModel = new UserTocash();
        return $userToCashModel->userToCashList($this->userId, $page, $limit, $type);
    }


    /**
     * 获取信任登录内容，标题，图标，名称，跳转地址
     * @return array
     */
    public function getTrustLogin(){
        $data = [
            'status' => true,
            'msg' => '获取成功',
            'data' => []
        ];

        $url = input('url/s','');//前台地址
        $uuid = input('uuid/s','');//前台用户信息，记录是同一个用户
        if(!$url){
            $url = Container::get('request')->domain();
        }
        $params['url'] = $url;
        $params['uuid'] = $uuid;
        if(!$params['url']||!$uuid)
        {
            $data['status'] = false;
            $data['msg'] = '获取失败';
            return $data;
        }
        if(checkAddons('trustlogin'))
        {
            $data['data'] = Hook('trustlogin',$params);
        }
        return $data;
    }


    /**
     * 根据code 获取用户信息
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function trustCallBack()
    {
        $returnData = [
            'status' => false,
            'msg' => '获取失败',
            'data' => []
        ];
        $params['code'] = input('code');
        $params['type'] = input('type');
        $params['state'] = input('state');
        $params['uuid'] = input('uuid/s','');
        if(!$params['code'] || !$params['type'] ||! $params['state'])
        {
            $returnData['msg'] = '关键参数丢失';
            return $returnData;
        }
        $data = [];
        //此处钩子只能取第一个插件的返回值
        if(checkAddons('trustcallback'))
        {
            $data = Hook('trustcallback', $params);
        }

        if(isset($data[0]['status']) && !$data[0]['status'])
        {
            return $returnData;
        }

        $user = $data[0]['data'];
        $userWxModel = new UserWx();
        if(isset($user['unionId']) && $user['unionId'])
        {
            //有这个unionid的时候，用这个判断
            $where['unionid'] = $user['unionId'];
        }
        elseif(isset($user['openid']) && $user['openid'])
        {
            //有这个openid的时候，先用unionid，再用这个判断
            $where['openid'] = $user['openid'];
        }
        $wxInfo = $userWxModel->where($where)->find();
        if($wxInfo)
        {
            //存在第三方账号，检查是否存在会员，存在的话，直接登录，不存在则绑定手机号
            if($wxInfo['user_id'])
            {
                $where['type'] = $userWxModel::TYPE_OFFICIAL;
                $h5WxInfo      = $userWxModel->where($where)->find();
                if (!$h5WxInfo) {
                    //插入公众号授权信息
                    $user['user_id'] = $wxInfo['user_id'];
                    $user['type']    = $userWxModel::TYPE_OFFICIAL;
                    $res             = $userWxModel->toAddWx($user);
                    if (!$res['status']) {
                        $returnData['msg'] = $res['msg'];
                        return $returnData;
                    }
                }
                //直接登录
                $userModel = new UserModel();
                $userInfo = $userModel->where(array('id'=>$wxInfo['user_id']))->find();
                if(!$userInfo)
                {
                    $result['msg'] = '没有找到此账号';
                    return $result;
                }
                return $userModel->setSession($userInfo,2,1);
            }
            else
            {
                Cache::set('user_wx_'.$params['uuid'],json_encode($wxInfo));
                $returnData['msg'] = '请绑定手机号';
                $returnData['status'] = true;
                $returnData['data'] = [
                    'is_new' => true
                ];
                return $returnData;
            }
        }
        else
        {
            //不存在第三方账号,先插入第三方账号，然后跳转绑定手机号页面
            $res = $userWxModel->toAddWx($user);
            if(!$res['status'])
            {
                $returnData['msg'] = $res['msg'];
                return $returnData;
            }
            Cache::set('user_wx_'.$params['uuid'],json_encode($res['data']));

            $returnData['msg'] = '保存成功，请绑定手机号';
            $returnData['status'] = true;
            $returnData['data'] = [
                'is_new' => true
            ];
            return $returnData;
        }
        return $returnData;
    }


    /**
     * 用户手机号绑定,然后调用登录，返回登录信息
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function trustBind()
    {
        $returnData = [
            'status' => false,
            'msg' => '绑定失败',
            'data' => []
        ];
        $data = input('param.');
        if (!$data['uuid']) {
            return $returnData;
        }
        $wxinfo = Cache::get('user_wx_' . $data['uuid']);
        $wxinfo = json_decode($wxinfo, true);
        $data['user_wx_id'] = $wxinfo['id'];
        $userModel = new UserModel();
        $userWxModel = new UserWx();
        $wxInfo = $userWxModel->where(['id' => $data['user_wx_id']])->find();
        if (isset($wxInfo['user_id']) && $wxInfo['user_id']) {
            $returnData['msg'] = '请勿重复绑定';
            return $returnData;
        }
        return $userModel->smsLogin($data, 2);
    }


    /**
     * 是否开启积分
     * @return array
     */
    public function isPoint()
    {
        $return = [
            'status' => true,
            'msg' => '获取成功',
            'data' => 2
        ];
        $settingModel = new Setting();
        $return['data'] = $settingModel->getValue('point_switch');
        return $return;
    }


    /**
     * 获取我的要求相关信息
     * @return array
     * @throws \think\exception\DbException
     */
    public function myInvite()
    {
        $return = [
            'status' => true,
            'msg' => '获取成功',
            'data' => []
        ];
        //我的邀请码
        $code = $this->sharecode();
        $return['data']['code'] = $code['data'];
        //我邀请的人数
        $userModel = new UserModel();
        $where[] = ['pid', 'eq', $this->userId];
        $return['data']['number'] = $userModel->where($where)->count();
        //邀请赚的佣金
        $return['data']['money'] = 0;
        $balanceModel = new Balance();
        $balance = $balanceModel->getInviteCommission($this->userId);
        if($balance['status'])
        {
            $return['data']['money'] = $balance['data'];
        }
        //是否有上级
        $userInfo = $userModel->get($this->userId);
        $is_superior = false;
        if($userInfo['pid'] && $userInfo['pid'] != 0)
        {
            $is_superior = true;
        }
        $return['data']['is_superior'] = $is_superior;

        return $return;
    }


    /**
     * 设置我的上级邀请人
     * @return array
     * @throws \think\exception\DbException
     */
    public function activationInvite()
    {
        $code = Request::param('code');
        $userModel = new UserModel();
        return $userModel->setMyInvite($this->userId, $userModel->getUserIdByShareCode($code));
    }


    /**
     * 用户积分明细
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userPointLog()
    {
        $user_id = $this->userId;
        $userPointLog = new UserPointLog();
        $page = Request::param('page', 1);
        $limit = Request::param('limit', 10);
        $res = $userPointLog->pointLogList($user_id, false, $page, $limit);
        return $res;
    }

    /**
     * 获取省市区信息
     */
    public function getAreaList()
    {
        $return = [
            'status' => true,
            'msg' => '获取成功',
            'data' => []
        ];
        $area = config('jshop.area_list');
        if(!file_exists($area)){
            $return['status'] = false;
            $return['msg'] = '地址库不存在，请重新生成';
            return $return;
        }
        $data = file_get_contents($area);
        echo $data;exit();
    }


    /**
     * 生成海报
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPoster()
    {
        $token = Request::param('token', false);
        if($token)
        {
            $data['user_id'] = getUserIdByToken($token);
        }
        else
        {
            $data['user_id'] = 0;
        }
        $data['type'] = Request::param('type', 1); //分享类型 1=商品海报 2=邀请海报
        $data['id'] = Request::param('id', 0); //类型值 1商品海报就是商品ID 2邀请海报无需填
        $data['source'] = Request::param('source', 1); //来源 1=普通H5页面 2=微信小程序 3=微信公众号H5
        $data['return_url'] = Request::param('return_url', ''); //返回URL地址

        $model = new UserModel();
        return $model->posterGenerate($data);
    }
    /**
     * 用户转账
     * @return array|mixed
     */
    public function transfer()
    {
        $password2 = input('param.password2');
        $userInfo = UserModel::get($this->userId);
        if(md5(md5($password2).$userInfo->ctime)!=$userInfo->password2) return error_code(17011);
        $money = input('param.money');
        $financeType = input('param.type',1); // 账户类型
        $dstMobile   = input('param.dst_mobile'); // 转入用户手机号
        $remark      = input('param.remark','');  // 留言
        if (!$money) return error_code(17001);
        $dstUser = get_user_id($dstMobile);
        if (!$dstUser) return error_code(11051);
        $userTransferModel = new UserTransfer();
        if($financeType == 3){
            $financeType = 5;
        }
        if($financeType == 2){
            $financeType = 4;
        }
        return $userTransferModel->transfer($this->userId, $money, $financeType,$dstUser,$remark);
    }


    /**
     * 获取用户转账/兑换记录
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function transferList()
    {
        $page = input('param.page', 1);
        //$limit = input('param.limit', config('jshop.page_limit'));
        $type = input('param.type', 1); // 1 转账 2 兑换
        $userTransferModel = new UserTransfer();
        return $userTransferModel->userTransferList($this->userId, $page, $type);
    }

    /**
     * 用户兑换
     * @return array|mixed
     */
    public function exchangeCoupon()
    {
        $password2 = input('param.password2');
        $userInfo = UserModel::get($this->userId);
        if(!password_verify($password2,$userInfo->password2)) return error_code(17011);
        $number = input('param.number');
        if (!$number) return error_code(17003);
        $userTransferModel = new UserTransfer();
        return $userTransferModel->exchange($this->userId, $number, '');
    }

    /**
     * 获取用户店铺账户/金豆/购物卷明细
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function financeRecordList()
    {
        $page  = input('param.page', 1);
        $limit = input('param.limit', config('jshop.page_limit'));
        $type  = input('param.type', '');
        $finance_id = input('param.finance_id','');
        $shop_id= input('param.shop_id',0);
        $shopOtayoniiModel = new ShopOtayonii();
        return $shopOtayoniiModel->userFinanceRecordList($this->userId,$page,$limit,$type,$finance_id,$shop_id);
    }

    /**
     * 获取奖金明细
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function prizeList()
    {
        $page  = input('param.page', 1);
        $limit = input('param.limit', config('jshop.page_limit'));
        $type  = input('param.type', '');
        $prizeRecordModel = new PrizeRecord();
        return $prizeRecordModel->userPrizeList($this->userId,$page,$limit,$type);
    }
    /*金豆分红*/
    public function autoUpdateOtayoniiData(){
        $otayoniiModel = new OtayoniiPrice();
        return $otayoniiModel->autoupdateotayoniidata();
    }
    /*金豆售出*/
    public function beanSold(){
        $password2 = input('param.password2');
        $userInfo = UserModel::get($this->userId);
        if(!password_verify($password2,$userInfo->password2)) return error_code(17011);
        $money = input('param.money');
        if (!$money) return error_code(12001);
        $otayoniiModel = new OtayoniiSold();
        return $otayoniiModel->tocash($this->userId, $money);
    }
    /*
     * 金豆售出记录
     */
    public function beanSoldList()
    {
        $page = input('param.page', 1);
        $otayoniisoldModel = new OtayoniiSold();
        return $otayoniisoldModel->beanSoldList($this->userId, $page);
    }
    /*
     * 生成验证码
     */
    public function createCaptcha(){
        $code = captcha_src();
        $code = $code."?".time();
        return $code;
    }
    /**
     * 设置支付密码
     */
    public function setPayPassword(){
        $result = [
            'status' => false,
            'msg' => '更改失败',
            'data' => ''
        ];
        $beforePassword = input('param.before_password','');
        $nowPassword = input('param.now_password','');
        if (!$beforePassword) return error_code(17008);
        $userInfo = UserModel::get($this->userId);
        if(password_verify($beforePassword,$userInfo->password2)){
            $res = UserModel::update(['password2'=>password_hash($nowPassword,PASSWORD_DEFAULT)],['id'=>$this->userId]);
            if($res){
                $result['status'] = true;
                $result['msg']    = '更改成功';
            }
        }else{
            return error_code(17009);
        }
        return $result;
    }
    /*
     * 返回会员的手机号
     */
    public function getMobile(){
        $result = [
            'status' => false,
            'msg' => '',
            'data' => ''
        ];
        $userModel = new UserModel();
        $info = $userModel->field('mobile')->where("id=$this->userId")->find();
        $result['status'] = true;
        $result['data'] = isset($info)?$info:'';
        return $result;
    }
    /*
     * 话费充值，返回手机号的运营商跟订单号
     * @return array
     */
      public function recharge(){
          $result = [
              'status' => false,
              'msg' => '',
              'data' => ''
          ];
          //取输入的手机号
          $mobile = input('param.mobile','');
          if (!$mobile) return error_code(11051);
          $data['service_provider'] = getMobileInfo($mobile);
          $data['serialno'] = get_sn(1);
          $result['status'] = true;
          $result['data'] = $data;
          return $result;
      }
      /**
       * 返回会员对应的充值记录
       * @return array
       */
      public function rechargeList(){
          $page = input('param.page', 1);
          $limit = input('param.limit', config('jshop.page_limit'));
          $billPayments  = new BillPayments();
          return $billPayments->rechargePaymentsLog($this->userId,$page,$limit);
      }
      /**
       * 前台发送留言
       * @return array
       */
      public function sendMessage(){
          $title = input('param.title','');
          $content = input('param.content','');
          if(!$title) return error_code(19001);
          if(!$content) return error_code(19002);
          $leaveMessage = new LeaveMessage();
          return $leaveMessage->sendMessage($this->userId,$title,$content);
      }
      /**
       * 返回留言的具体信息
       */
      public function messageInfo(){
          $id =  input('param.id',0);
          if(!$id) return error_code(19003);
          $leaveMessage =  new LeaveMessage();
          return $leaveMessage->getMessageInfo($id);
      }
      /**
       * 返回留言列表
       */
      public function messageList(){
          $postWhere = array();
          if (input('?param.where')) {
              $postWhere = json_decode(input('param.where'), true);
          }
          $leaveMessage =  new LeaveMessage();
          return $leaveMessage->getList($this->userId,$postWhere);
      }
      /**
       * 排行榜接口
       */
      public function ranking()
      {
          $limit  = input('param.limit', config('jshop.page_limit'));
          $order  = input('param.order', null);
          $level  = input('level', 3);
          $ranking = new Ranking();
          return $ranking->rankingList($limit, $level+1, $order);
      }
}