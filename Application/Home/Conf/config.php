<?php
define('ACCESS', './rsa_private_key.pem');
define('PRV', './rsa_public_key.pem');
//define('__SERVER__', 'http://demo.shopsn.net');

return array(

    //'配置项'=>'配置值'
    
    'DEFAULT_MODULE'     => 'Home', //默认模块
    'URL_MODEL'          => '2', //URL模式
    'LOAD_EXT_CONFIG'    => 'db_config', // 加载数据库配置文件
    /* 加载公共函数 */
    'LOAD_EXT_FILE'      => 'common',
    //设置短信有效时间---5分钟
    'send_msg_time'      => 300,
    // 系统默认的变量过滤机制
    //'DEFAULT_FILTER'        => 'strip_sql,htmlspecialchars',
    //图片域名地址
    'img_url'            => 'http://haibaobei-ec.com:8081',
//    'img_url'            => 'http://demo.shopsn.net',

    // 上传头像等图片设置图片大小
    'img_size'           => 3145728,
    'img_type'           => array('jpg', 'gif', 'png', 'jpeg'),
    'page_size'          => 10,// 配置分页大小
    //加密字符串设置
    'DATA_AUTH_KEY'      =>'zhongwen',
    'ALIPAY_MOBILE_CONFIG' => array(

        'app_id' =>"2017120800456516",//此处填写应用ID,您的APPID。
        'merchant_private_key'=>"MIICXAIBAAKBgQDLRM4oSWZXkqo6avcN68gFeXlyZuhHhdrAu6FoZ3AD5ZLRaj07G2iAuqsXId8QJa+8d+HfRWdnZf6Uf41ZlKl+n6P9WUbv2BUWEXe8u94CXXfWSxECVCqCxVSIprgJKojZSXpxVWasJ3g5oe6L4/umWUsD3evUAvQSzhUODSHWmQIDAQABAoGAFdqR55bsn+Gu15UEdsSwvpXuzrPtqTLk7++8TMNCMckO3eD0MFSkCaMIHfaQSuYiXLru19hYY699jW2hPs5S4o74kEp4ieSHLi+kJHJ7XrdIAc8lYZX/qetN925f1vDKBPQojJ8ejJ0NbHbAY6nDnK+UKOICr1x+fSznApn8sFkCQQD6NISlxG9l4sm8cCyqCCkHSO1j2KJG4bYqUBXcDy1FVoUm55zH8a5tCa6wTIziOOnINGdZ/2k1CVgHXc9Rl97zAkEAz/n/mmj7pdKSbKJDmmFSqgNFXUIV9K2WzEnjxhux8c40hOhm1wZCRCGbk++pCce78TGgSalesvaoocuJZXTPQwJAX+Ieb1Q/CIGHo+ItC6AC8Rq+doP/dEBtSfvU1LcwNyE397fMukbg/EI4orFDUDJVTPbgIHojvEJvbKtDltYnhwJAPnZkGRj8s2HZzjyxtxURwbP3yjmF5JWaG8L5YM+CkxAOX/h4oo3jqxi45CZvi1tsi9UOwfDXW0KPhQBfRJRfhwJBAI73ZIm7bgjQowtL2PqSFSl2qf+RNNllCxOuJxZWOP3bJBUGEC/Gjal1Iho33QsXcHmT+OBDQ26nKM8LLZxW0eI=",//支付宝网关私钥

        //异步通知地址
        'notify_url' => "http://api.huaanair.com/home/AlipayMobile/aliMobileNot",

        //同步跳转
        'return_url' => "http://api.huaanair.com/#/home",

        //编码格式
        'charset' => "UTF-8",

        //签名方式
        'sign_type'=>"RSA",

        //支付宝网关
        'gatewayUrl' => "https://openapi.alipay.com/gateway.do",

        //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
        'alipay_public_key' => "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDDI6d306Q8fIfCOaTXyiUeJHkrIvYISRcc73s3vF1ZT7XN8RNPwJxo8pWaJMmvyTn9N4HQ632qJBVHf8sxHi/fEsraprwCtzvzQETrNRwVxLO5jVmRGi60j8Ue1efIlzPXV9je9mkjzOmdssymZkh2QhUrCmZYI/FCEa3/cNMW0QIDAQAB",
    ),

//	'SHOW_PAGE_TRACE'=>true,// 接收支付状态的连接

    /*微信支付配置*/
    'WxJsApi'=>array(
        'APPID' => '您的APPID',
        'MCHID' => '您的商户ID',
        'KEY' => '商户秘钥',
        'APPSECRET' => '您的APPSECRET',
        'JS_API_CALL_URL' => WEB_HOST.'/index.php/Home/WxJsAPI/jsApiCall',
        'SSLCERT_PATH' => WEB_HOST.'/ThinkPHP/Library/Vendor/WxPayPubHelper/cacert/apiclient_cert.pem',
        'SSLKEY_PATH' => WEB_HOST.'/ThinkPHP/Library/Vendor/WxPayPubHelper/cacert/apiclient_key.pem',
        'NOTIFY_URL' =>  WEB_HOST.'/index.php/Home/WxJsAPI/notify',
        'CURL_TIMEOUT' => 30
    ),
    'WxLogin_DoMain'      => 'http://' . $_SERVER['SERVER_NAME'] . '/Home/WeChat/getWebAccessToken',//微信网页授权的 域名
    'WxOpenId_DoMain'     => 'http://' . $_SERVER['SERVER_NAME'] . '/Home/WeChat/getOpenId',//只获取openId 回调域名
    'notify_url_wx_weiyi' => 'http://' . $_SERVER['SERVER_NAME'] . '/Home/WxJsApiNotify/WXJsPayUrl9961',//JSAPI 回调地址
    'MOBILE_DOMAIN'       => 'demo.shopsn.net/mobile',//手机端域名
);