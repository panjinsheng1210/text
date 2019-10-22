<?php

// +----------------------------------------------------------------------
// | 接口设置
// +----------------------------------------------------------------------

return [
    // 买入卖出
    'business' =>[
        'code'     => 'Business',
        'method'   =>[
            'addsale'=>[
                'code' =>'addSale',
                'is_login' => true
            ],
            'addbuy'=>[
                'code' =>'addBuy',
                'is_login' => true
            ],
            'buyindex'=>[
                'code' =>'buyIndex',
                'is_login' => true
            ],
            'saleindex'=>[
                'code' =>'saleIndex',
                'is_login' => true
            ],

            'salableindex'=>[
                'code' =>'salableIndex',
                'is_login' => true
            ],
            'dealcount' => [
                'code' => 'dealCount',
                'is_login' => true,
            ],
        ]
    ],




    //用户
    'user'    => [
        'code' => 'User',
        'method' => [
            'login' => [
                'code' => 'login',
                'is_login' => false
            ],
            'smsregister' => [
                'code' => 'smsRegister',
                'is_login' => false
            ],
            'wxapplogin1' => [
                'code' => 'wxappLogin1',
                'is_login' => false
            ],
            'wxapplogin2' => [
                'code' => 'wxappLogin2',
                'is_login' => false
            ],
            'sms' => [
                'code' => 'sms',
                'is_login' => false
            ],
            'logout' => [
                'code' => 'logout',
                'is_login' => true
            ],
            'reg' => [
                'code' => 'reg',
                'is_login' => false
            ],
            'info' => [
                'code' => 'info',
                'is_login' => true
            ],
            'ship' => [
                'code' => 'changeAvatar',
                'is_login' => true
            ],
            'changeavatar' => [
                'code' => 'changeAvatar',
                'is_login' => true
            ],
            'editinfo' => [
                'code' => 'editInfo',
                'is_login' => true
            ],
            'addgoodsbrowsing' => [
                'code' => 'addGoodsBrowsing',
                'is_login' => true
            ],
            'delgoodsbrowsing' => [
                'code' => 'delGoodsBrowsing',
                'is_login' => true
            ],
            'goodsbrowsing' => [
                'code' => 'goodsBrowsing',
                'is_login' => true
            ],
            'goodscollection' => [
                'code' => 'goodsCollection',
                'is_login' => true
            ],
            'goodscollectionlist' => [
                'code' => 'goodsCollectionList',
                'is_login' => true
            ],
            'saveusership' => [
                'code' => 'saveUserShip',
                'is_login' => true
            ],
            'vuesaveusership' => [
                'code' => 'vueSaveUserShip',
                'is_login' => true
            ],
            'getshipdetail' => [
                'code' => 'getShipDetail',
                'is_login' => true
            ],
            'setdefship' => [
                'code' => 'setDefShip',
                'is_login' => true
            ],
            'editship' => [
                'code' => 'editShip',
                'is_login' => true
            ],
            'removeship' => [
                'code' => 'removeShip',
                'is_login' => true
            ],
            'getusership' => [
                'code' => 'getUserShip',
                'is_login' => true
            ],
            'getallname' => [
                'code' => 'getAllName',
                'is_login' => false
            ],
            'getareaid' => [
                'code' => 'getAreaId',
                'is_login' => false
            ],
            'pay' => [
                'code' => 'pay',
                'is_login' => true,
            ],
            'orderevaluate' => [
                'code' => 'orderEvaluate',
                'is_login' => true,
            ],
            'getuserdefaultship' => [
                'code' => 'getUserDefaultShip',
                'is_login' => true,
            ],
            'issign' => [
                'code' => 'isSign',
                'is_login' => true
            ],
            'sign' => [
                'code' => 'sign',
                'is_login' => true
            ],
            'getuserpoint' => [
                'code' => 'getUserPoint',
                'is_login' => true
            ],
            'getsetting' => [
                'code' => 'getSetting',
                'is_login' => false
            ],
            'getdefaultbankcard' => [
                'code' => 'getDefaultBankCard',
                'is_login' => true
            ],
            'getbankcardlist' => [
                'code' => 'getBankCardList',
                'is_login' => true
            ],
            'addbankcard' => [
                'code' => 'addBankCard',
                'is_login' => true
            ],
            'removebankcard' => [
                'code' => 'removeBankCard',
                'is_login' => true
            ],
            'setdefaultbankcard' => [
                'code' => 'setDefaultBankCard',
                'is_login' => true
            ],
            'getbankcardinfo' => [
                'code' => 'getBankCardInfo',
                'is_login' => true
            ],
            'getbankcardorganization' => [
                'code' => 'getBankCardOrganization',
                'is_login' => false
            ],
            'editpwd' => [
                'code' => 'editPwd',
                'is_login' => true
            ],
            'setpwd2' => [
                'code' => 'setPwd2',
                'is_login' => true
            ],
            'forgotpwd' => [
                'code' => 'forgotPwd',
                'is_login' => false
            ],
            'balancelist' => [
                'code' => 'userBalance',
                'is_login' => true
            ],
            'recommend' => [
                'code' => 'recommend',
                'is_login' => true
            ],
            'sharecode' => [
                'code' => 'sharecode',
                'is_login' => true
            ],
            'cash' => [
                'code' => 'cash',
                'is_login' => true
            ],
            'cashlist' => [
                'code' => 'cashList',
                'is_login' => true
            ],
            'cancelcash' => [
                'code' => 'cancelCash',
                'is_login' => true
            ],
            'gettrustlogin' => [ //获取信任登录接口
                'code' => 'getTrustLogin',
                'is_login' => false
            ],
            'trustcallback' => [ //获取用户信息接口
                'code' => 'trustCallBack',
                'is_login' => false
            ],
            'trustbind' => [  //账号绑定接口
                'code' => 'trustBind',
                'is_login' => false
            ],
            'ispoint' => [
                'code' => 'isPoint',
                'is_login' => false
            ],
            'myinvite' => [
                'code' => 'myInvite',
                'is_login' => true
            ],
            'activationinvite' => [
                'code' => 'activationInvite',
                'is_login' => true
            ],
            'userpointlog' => [
                'code' => 'userPointLog',
                'is_login' => true
            ],
            'getsigninfo' => [
                'code' => 'getSignInfo',
                'is_login' => true
            ],
            'getarealist' => [
                'code' => 'getAreaList',
                'is_login' => false
            ],
            'getposter' => [
                'code' => 'getPoster',
                'is_login' => false
            ],
             'tranfer' => [
                'code' => 'tranfer',
                'is_login' => true
            ],
            // 店铺账户/金豆/购物卷明细
            'financerecordlist' => [
                'code' => 'financeRecordList',
                'is_login' => true
            ],
            //优惠卷兑换
            'exchangecoupon' => [
                'code' => 'exchangeCoupon',
                'is_login' => true
            ],
            //转账记录
            'transferlist' => [
                'code' => 'transferList',
                'is_login' => true
            ],
            //转账
            'transfer' => [
                'code' => 'transfer',
                'is_login' => true
            ],
            //奖金明细
            'prizelist' => [
                'code' => 'prizeList',
                'is_login' => true
            ],
            //金豆分红
            'autoupdateotayoniidata' => [
                'code' => 'autoUpdateOtayoniiData',
                'is_login' => true
            ],
            //金豆售出
            'beansold' => [
                'code' => 'beanSold',
                'is_login' => true
            ],
            //金豆售出记录
            'beansoldlist' => [
                'code' => 'beanSoldList',
                'is_login' => true
            ],
            //生成验证码
            'createcaptcha'=>[
                'code' => 'createCaptcha',
                'is_login' => true
            ],
            'setpaypassword'=>[
                'code' => 'setPayPassword',
                'is_login' => true
            ],
            'getmobile'=>[
                'code' => 'getMobile',
                'is_login' => true
            ],
            //充值返回订单号跟运营商
            'recharge'=>[
                'code' => 'recharge',
                'is_login' => true
            ],
            //返回对应的充值记录
            'rechargelist'=>[
                'code' => 'rechargeList',
                'is_login' => true
            ],
            //发留言
            'sendmessage'=>[
                'code' => 'sendMessage',
                'is_login' => true
            ],
            //邮件记录
            'messagelist'=>[
                'code' => 'messageList',
                'is_login' => true
            ],
            //邮件的具体信息
            'messageinfo'=>[
                'code' => 'messageInfo',
                'is_login' => true
            ],
            'ranking' => [ // 排行榜
                'code' => 'ranking',
                'is_login' => false,
            ],
        ]
    ],

    'student' => [
        'code' => 'Student',
        'method' => [
            'getlist' => [
                'code' => 'getlist',
                'is_login' => false
            ],
            'add' => [
                'code' => 'add',
                'is_login' => false
            ],

        ],
    ],

    'relation' => [
        'code' => 'Relation',
        'method' => [
            'getlist' => [
                'code' => 'getlist',
                'is_login' => true
            ],
            'add' => [
                'code' => 'add',
                'is_login' => true
            ],
             'adds' => [
                'code' => 'adds',
                'is_login' => true
            ],
             'test' => [
                'code' => 'test',
                'is_login' => true
            ],

        ],
    ],

    'shixi' => [
        'code' => 'Shixi',
        'method' => [
            'getlist' => [
                'code' => 'getList',
                'is_login' => false
            ],
            'test' => [
                'code' => 'test', 
                'is_login' => false
            ],
            'add' => [
                'code' => 'add',
                'is_login' => false
            ],
            'del' => [
                'code' => 'del',
                'is_login' => false
            ],
            'edit' => [
                'code' => 'edit',
                'is_login' => false
            ],
            'details' => [
                'code' => 'details',
                'is_login' => false
            ],
        ],
    ],



     'shixis' => [
        'code' => 'Shixis',
        'method' => [
            'getlist' => [
                'code' => 'getList',
                'is_login' => false
            ],
            'test' => [
                'code' => 'test', 
                'is_login' => false
            ],
            'add' => [
                'code' => 'add',
                'is_login' => false
            ],
            'del' => [
                'code' => 'del',
                'is_login' => false
            ],
            'edit' => [
                'code' => 'edit',
                'is_login' => false
            ],
            'details' => [
                'code' => 'details',
                'is_login' => false
            ],
        ],
    ],



    'leilast' => [
        'code' => 'Leilast',
        'method' => [
            'lists' => [
                'code' => 'lists',
                'is_login' => false
            ],
            'add' => [
                'code' => 'add',
                'is_login' => false
            ],
            'del' => [
                'code' => 'del',
                'is_login' => false
            ],
            'edit' => [
                'code' => 'edit',
                'is_login' => false
            ],
        ],
    ],

    'advert' => [
        'code' => 'Carousel',
        'method' => [
            'positionList' => [
                'code' => 'carouselSeatList',
                'is_login' => false
            ],
            'getAdvertList' => [
                'code' => 'getList',
                'is_login' => false
            ],
            'getcarousellists' => [
                'code' => 'getCarouselLists',
                'is_login' => false
            ]
        ],
    ],

    'articles' => [
        'code' => 'Articles',
        'method' => [
            'getArticleType' => [
                'code' => 'getArticleType',
                'is_login' => false
            ],
            'getArticleList' => [
                'code' => 'getArticleList',
                'is_login' => false
            ],
            'getArticleDetail' => [
                'code' => 'getArticleDetail',
                'is_login' => false
            ]
        ]
    ],

    'brand' => [
        'code' => 'Brand',
        'method' => [
            'brandList' => [
                'code' => 'brandList',
                'is_login' => false
            ]
        ]
    ],

    'coupon' => [
        'code' => 'Coupon',
        'method' => [
            'couponlist' =>[
               'code' => 'couponList',
               'is_login' => false
            ],
            'coupondetail' => [
                'code' => 'couponDetail',
                'is_login' => false
            ],
            'getcoupon' => [
                'code' => 'getCoupon',
                'is_login' => true,
            ],
            'usercoupon' => [
                'code' => 'userCoupon',
                'is_login' => true
            ],
            'getcouponkey' => [
                'code' => 'getCouponKey',
                'is_login' => true
            ]
        ]
    ],

    'notice' => [
        'code' => 'Notice',
        'method' => [
            'noticeList' => [
                'code' => 'noticeList',
                'is_login' => false
            ],
            'noticeType' =>[
                'code' => 'noticeType',
                'is_login' => false
            ],
            'noticeInfo' => [
                'code' => 'noticeInfo',
                'is_login' => false
            ]
        ]
    ],

    //购物车
    'cart' => [
        'code' => 'Cart',
        'method' => [
            'add' => [
                'code' => 'add',
                'is_login' => true
            ],
            'del' => [
                'code' => 'del',
                'is_login' => true
            ],
            'getlist' => [
                'code' => 'getList',
                'is_login' => true
            ],
            'setnums' => [
                'code' => 'setNums',
                'is_login' => true
            ],
            'getnumber' => [
                'code' => 'getNumber',
                'is_login' => true
            ]
        ]
    ],

    //商品分类
    'categories' => [
        'code' => 'Categories',
        'method' => [
            'gettopcat' => [
                'code' => 'getTopCat',
                'is_login' => false  // 2
            ],
            'getchildcat' => [
                'code' => 'getChildCat',
                'is_login' => false  // 3
            ],
            'getallcat' => [
                'code' => 'getAllCat',
                'is_login' => false
            ],
            'getname' => [
                'code' => 'getName',
                'is_login' => false
            ]
        ]
    ],

    //订单模块
    'order' => [
        'code' => 'Order',
        'method' => [
            'cancel' => [
                'code' => 'cancel',
                'is_login' => true
            ],
            'del' => [
                'code' => 'del',
                'is_login' => true
            ],
            'details' => [
                'code' => 'details',
                'is_login' => true
            ],
            'confirm' => [
                'code' => 'confirm',
                'is_login' => true
            ],
            'getlist' => [
                'code' => 'getList',
                'is_login' => true
            ],
            'create' => [
                'code' => 'create',
                'is_login' => true
            ],
            'getship' => [
                'code' => 'getShip',
                'is_login' => true
            ],
            'getorderlist' => [
                'code' => 'getOrderList',
                'is_login' => true
            ],
            'getorderstatusnum' => [
                'code' => 'getOrderStatusNum',
                'is_login' => true
            ],
            'aftersaleslist' => [
                'code' => 'aftersalesList',
                'is_login' => true
            ],
            'aftersalesinfo'=> [
                'code' => 'aftersalesInfo',
                'is_login' => true,
            ],
            'aftersalesstatus' => [
                'code' => 'aftersalesStatus',
                'is_login' => true
            ],
            'addaftersales' => [
                'code' => 'addAftersales',
                'is_login' => true
            ],
            'sendreship' => [
                'code' => 'sendReship',
                'is_login' => true
            ],
            'iscomment' => [
                'code' => 'isComment',
                'is_login' => true
            ],
            'logistics' => [
                'code' => 'logistics',
                'is_login' => true
            ],
            'logisticbyapi' => [
                'code' => 'logisticsByApi',
                'is_login' => false
            ],
            'getcashpooling' => [
                'code' => 'getCashPooling',
                'is_login' => false
            ],
            'gettaxcode' => [
                'code' => 'getTaxCode',
                'is_login' => false
            ]
        ]
    ],

    //商品
    'goods' => [
        'code' => 'Goods',
        'method' => [
            'getlist' => [
                'code' => 'getList',
                'is_login' => false //1
            ],
            'getdetial' => [
                'code' => 'getDetail',
                'is_login' => false
            ],
            'appgetdetial' => [
                'code' => 'appGetDetail',
                'is_login' => false
            ],
            'getskudetial' => [
                'code' => 'getSkuDetial',
                'is_login' => false
            ],
            'getgoodsparams' => [
                'code' => 'getGoodsParams',
                'is_login' => false
            ],
            'getproductinfo' => [
                'code' => 'getProductInfo',
                'is_login' => false
            ],
            'appgetproductinfo' => [
                'code' => 'appGetProductInfo',
                'is_login' => false
            ],
            'getgoodscomment' => [
                'code' => 'getGoodsComment',
                'is_login' => false
            ],
            'getgoodscathotgoods' => [
                'code' => 'getGoodsCatHotGoods',
                'is_login' => false
            ],
        ]
    ],

    //支付单
    'payments' => [
        'code' => 'Payments',
        'method' => [
            'getlist' => [
                'code' => 'getList',
                'is_login' => false
            ],
            'getinfo' => [
                'code' => 'getInfo',
                'is_login' => true
            ],
        ]
    ],

    'images' => [
        'code' => 'Images',
        'method' => [
            'upload' => [
                'code' => 'upload',
                'is_login' => false
            ],
        ]
    ],

    //商户门店列表
    'store' => [
        'code' => 'Store',
        'method'    =>  [
            'getdefaultstore' => [
                'code' => 'getDefaultStore',
                'is_login' => false
            ],
            'getstorelist' => [
                'code' => 'getStoreList',
                'is_login' => false
            ],
            'isclerk' => [
                'code' => 'isClerk',
                'is_login' => true
            ],
            'storeladinglist' => [
                'code' => 'storeLadingList',
                'is_login' => true
            ],
            'ladinginfo' => [
                'code' => 'ladingInfo',
                'is_login' => true
            ],
            'lading' => [
                'code' => 'lading',
                'is_login' => true
            ],
            'getstoreswitch' => [
                'code' => 'getStoreSwitch',
                'is_login' => false
            ],
            'ladingdel' => [
                'code' => 'ladingDel',
                'is_login' => true
            ],
            'getinviteqrcode' => [
                'code' => 'getInviteQRCode',
                'is_login' => false
            ],
            'getrecommendkeys' => [
                'code' => 'getRecommendKeys',
                'is_login' => false
            ],
            'getstoreqrcode' => [
                'code' => 'getStoreQRCode',
                'is_login' => true
            ],
        ]
    ],
    //店铺列表
    'shop' => [
        'code' => 'Shop',
        'method'    =>  [
            'getshoplist' => [
                'code' => 'getShopList',
                'is_login' => false
            ],
            'add' => [
                'code' => 'add',
                'is_login' => true
            ],
            'shopinfo' => [
                'code' => 'shopInfo',
                'is_login' => true
            ],
            'mynearshop'=>[
                'code' => 'myNearShop',
                'is_login'=>false
            ],
            'getshopcat'=>[
                'code' => 'getShopCat',
                'is_login'=>false,
            ],
            'upload'=>[
                'code' => 'upload',
                'is_login'=>true,
            ],
            'getassigninfo'=>[
                'code' => 'getAssignInfo',
                'is_login'=>false,
            ],
            'shoptocash'=>[
                'code' => 'shopTocash',
                'is_login'=>true,
            ],
            'shoptocashrecord'=>[
                'code' => 'shopTocashRecord',
                'is_login'=>true,
            ],
            'offlinepayments'=>[
                'code' => 'offlinePayments',
                'is_login'=>true,
            ]
        ]
    ],
    //团购&秒杀
    'group' => [
        'code' => 'Group',
        'method'    =>  [
            'getlist' => [
                'code' => 'getList',
                'is_login' => false
            ],
            'getgoodsdetial'=>[
                'code'=>'getGoodsDetial',
                'is_login'=>false
            ]
        ]
    ],

    // h5 微信浏览器里分享
    'weixinshare' => [
        'code' => 'WeiXinShare',
        'method' => [
            'share' => [
                'code' => 'share',
                'is_login' => false
            ]
        ]
    ],

    // 表单
    'form'=>[
        'code'=>'Form',
        'method'=>[
            'getformdetial'=>[
                'code'=>'getFormDetial',
                'is_login' => false
            ],
            'addsubmit'=>[
                'code'=>'addSubmit',
                'is_login' => false
            ],

        ],
    ],
    'appplus' => [
        'code' => 'AppPlus',
        'method' => [
            'checkversion' => [
                'code' => 'checkVersion',
                'is_login' => false
            ]
        ]
    ]
];