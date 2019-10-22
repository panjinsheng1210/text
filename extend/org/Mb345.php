<?php
/**
 * Created by JetBrains PhpStorm.
 * User: hetao
 * Date: 13-10-1
 * Time: 下午2:21
 * To change this template use File | Settings | File Templates.
 */

namespace org;

/**
 * Class Mb345
 */
class Mb345
{
    /**
     * @param $mobile
     * @param $content
     * @return bool
     * @throws Exception
     */
    public static function send($mobile, $content)
    {
        $result = [
            'status' => false,
            'msg'    => '发送失败',
        ];
        $config   = config('params.mb345');
        $username = $config['sms_username'];
        $password = $config['sms_password'];
        $smsSignature = "\r\n" . $config['sms_signature'];
        $content = $content . $smsSignature;
        $phoneStatus = static::getPhoneNumber($mobile);
        if ($phoneStatus['status'] == false) {
            $result['msg'] = $phoneStatus['msg'];
            return $result;
        }
        $phoneNumber = $phoneStatus['data'];
        $url = "http://mb345.com:999/ws/BatchSend.aspx?CorpID={$username}&Pwd={$password}&Mobile=" . urlencode($phoneNumber) . "&Content=" . urlencode(iconv('UTF-8', 'GBK', $content));
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true) ; // 获取数据返回
        $result1 = curl_exec($ch);
        curl_close($ch);
        if ($result1 < 0) {
            switch ($result1) {
                case -1:
                    $log_params['info'] = "($content) \r\n账号未注册";
                    break;
                case -2:
                    $log_params['info'] = "($content) \r\n其它错误";
                    break;
                case -3:
                    $log_params['info'] = "($content) \r\n密码错误";
                    break;
                case -4:
                    $log_params['info'] = "($content) \r\n手机号格式不对";
                    break;
                case -5:
                    $log_params['info'] = "($content) \r\n余额不足";
                    break;
                case -6:
                    $log_params['info'] = "($content) \r\n定时发送时间不是有效的时间格式";
                    break;
                case -7:
                    $log_params['info'] = "($content) \r\n提交信息末尾未加签名，请添加中文企业签名";
                    break;
                case -8:
                    $log_params['info'] = "($content) \r\n发送内容需在1到500个字之间";
                    break;
                case -9:
                    $log_params['info'] = "($content) \r\n发送号码为空";
                    break;
                default:
                    $log_params['info'] = "($content) \r\n未知错误";
                    break;
            }
            $result['msg'] = $log_params['info'];
            return $result;
        } else {
            $result['status'] = true;
            $result['msg']    = '发送成功';
            return $result;
        }
        return true;
    }

    /**
     * @param $mobile
     * @return string
     * @throws Exception
     */
    public static function getPhoneNumber($mobile)
    {
        $result = [
            'status' => false,
            'msg'    => '手机号为空',
        ];
        if (is_null($mobile) || (is_string($mobile) && $mobile == '')||(is_array($mobile) && $mobile==[])) {
            return $result;
        }
        if (is_string($mobile)) {
            $result['status'] = true;
            $result['data']   = $mobile;
            return $result;
        }
        if (is_array($mobile)) {
            $result['status'] = true;
            $result['data'] = implode(';', $mobile);
            return $result;
        }
        return $result;
    }
}
