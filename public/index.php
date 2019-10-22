<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]
namespace think;
define('ROOT_PATH',dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR);
define('DS',DIRECTORY_SEPARATOR);
// 插件目录
define('ADDON_PATH', ROOT_PATH . 'addons'   . DIRECTORY_SEPARATOR);

if(!file_exists(ROOT_PATH.'/public/install/install.lock')&&!file_exists(ROOT_PATH.'/config/install.lock')){
    header('Location:/install');exit;
}
// 加载基础文件
require __DIR__ . '/../thinkphp/base.php';
$handler = \PhpConsole\Handler::getInstance();
$handler->setHandleErrors(true);  // disable errors handling
$handler->setHandleExceptions(true); // disable exceptions handling
$handler->setCallOldHandlers(true); // disable passing errors & exceptions to prviously defined handlers
$connector=$handler->getConnector();
$connector->setAllowedIpMasks(['192.168.*.*', '2001:0:5ef5:79fb:*:*:*:*', '127.*.*.*','::1']);
$connector->setHeadersLimit(1275000);
$handler->start(); // initialize handlers
\PhpConsole\Helper::register();
// 支持事先使用静态方法设置Request对象和Config对象
// 执行应用并响应
Container::get('app')->run()->send();