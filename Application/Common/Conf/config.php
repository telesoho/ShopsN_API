<?php
define( '__SERVER__','http://haibaobei-ec.com:8081' );
define( 'API_SERVER', 'http://haibaobei-ec.com:8082');
define( 'WEB_SERVER', 'http://haibaobei-ec.com:8081');

//define('__SERVER__', 'http://www.shopsn.cn');
return array(
    //'配置项' => '配置值'
    'DEFAULT_MODULE'       => 'Home', //默认模块
    'URL_MODEL'            => '2', //URL模式
    'LOAD_EXT_CONFIG'      => 'db', // 加载数据库配置文件
    /* 加载公共函数 */
    'LOAD_EXT_FILE'        => 'common',
    'ceshi'                => '测试',
    'ceshi2'                => '测试2',
    //设置短信有效时间---5分钟
    'send_msg_time'        => 300,

    // 系统默认的变量过滤机制
    //'DEFAULT_FILTER'        => 'strip_sql,htmlspecialchars',
    //图片域名地址
    'img_url'              => WEB_SERVER,
    // 上传头像等图片设置图片大小
    'img_size'             => 3145728,
    'img_type'             => array( 'jpg','gif','png','jpeg' ),
    'page_size'            => 10,// 配置分页大小
    //加密字符串设置
    'DATA_AUTH_KEY'        => 'zhongwen',
    'ALIPAY_MOBILE_CONFIG' => array(

        'app_id'               => "2088821744784306",//此处填写应用ID,您的APPID。
        'merchant_private_key' => "MIICWwIBAAKBgQC0qVM2Lv18McSlhyzIn/T7LAmCcEypDabCIQIgkLTAW7Tjduzjw06qAOUj7flDydN3tYDR+vR91eYP6lYeCsOn5BF8np6gH/JsioMKooYZg0QS65Ok7ZhYBMa96VH4LsbGntA+AslvH64kg8XGnurJO3nz2y1Vix5VqP+uFA+o/QIDAQABAoGAFLXolSiT5J3r9jHl32X+9qBYwrxO/X5UJKMWFFeicP7SYNUsWPv106Vgn1rTnYLQnEORbgD/8EEKK77oem8veKlPaknjfrUachPvrghQC2Eeba3EYwAhQjY8rf26Q/s7v24Yg+rDVUNhIH8o3b5T4Uvjm80aDUkl4oLFqka2IP0CQQDmPSytx16IVw/WjedwFGaJx9IWJq7iQWbAQfa4Fl0jg2sdVCIcDOL5R+jySeMQDnteNS5T9VDRwaCrDUmphVZ3AkEAyOAU4TVH6N5qA1MmQ5S0Mhba6S+80OS2vsflieKly2IR4GrmqvYzQpDC+D1U81vBNq7JhlaAcOFeqNLFCbi1KwJAQDAn557glQQorzlKn62gVKM3x+Mq+HshSVJalUHu33rA/yE4jTdug+7vW7ULr6tJ657J9rA6wu/HekivE6rPywJAfO0PTzhJroOUPtkZdPIoVvZr0pYDwY5cMK41DNnN7nzhTUZuimhvXLiW6LeL+4VW1mFBp7BoVMt0iV37eJ5M3QJAd06WhrlKLOXLv7EK1yd4wPPB4Fq/iFPVkMEkp6RRI/naGIJFSRro4y79CaSTjCsdW2pLEDNrw+/fATUF7AucrQ==",//支付宝网关私钥

        //异步通知地址
        'notify_url'           => API_SERVER . "/home/AlipayMobile/aliMobileNot",

        //同步跳转
        'return_url'           => API_SERVER . "/#/home",

        //编码格式
        'charset'              => "UTF-8",

        //签名方式
        'sign_type'            => "RSA",

        //支付宝网关
        'gatewayUrl'           => "https://openapi.alipay.com/gateway.do",

        //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
        'alipay_public_key'    => "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC0qVM2Lv18McSlhyzIn/T7LAmCcEypDabCIQIgkLTAW7Tjduzjw06qAOUj7flDydN3tYDR+vR91eYP6lYeCsOn5BF8np6gH/JsioMKooYZg0QS65Ok7ZhYBMa96VH4LsbGntA+AslvH64kg8XGnurJO3nz2y1Vix5VqP+uFA+o/QIDAQAB",// 接收支付状态的连接
    ),
//	'SHOW_PAGE_TRACE'=>true,

    /*微信支付配置*/
    'WxJsApi'              => array(
        'APPID'           => '您的APPID',
        'MCHID'           => '您的商户ID',
        'KEY'             => '商户秘钥',
        'APPSECRET'       => '您的APPSECRET',
        'JS_API_CALL_URL' => WEB_HOST . '/index.php/Home/WxJsAPI/jsApiCall',
        'SSLCERT_PATH'    => WEB_HOST . '/ThinkPHP/Library/Vendor/WxPayPubHelper/cacert/apiclient_cert.pem',
        'SSLKEY_PATH'     => WEB_HOST . '/ThinkPHP/Library/Vendor/WxPayPubHelper/cacert/apiclient_key.pem',
        'NOTIFY_URL'      => WEB_HOST . '/index.php/Home/WxJsAPI/notify',
        'CURL_TIMEOUT'    => 30
    )
);