<?php



/**
 *    配置账号信息
 */
class WxPayConfig
{
    //private $data;


    public static $APPID;
    public static $MCHID;
    public static $KEY;
    public static $APPSECRET;
    public static $SSLCERT_PATH;
    public static $SSLKEY_PATH;
    public static $CURL_PROXY_HOST;
    public static $CURL_PROXY_PORT;
    public static $REPORT_LEVENL;


    public function __construct($data)
    {

        self:: $APPID           = $data['pay_account'];
        self:: $MCHID           = $data['mchid'];
        self:: $KEY             = $data['pay_key'];
        self:: $APPSECRET       = $data['open_id'];
        self:: $SSLCERT_PATH    = '../cert/apiclient_cert.pem';
        self:: $SSLKEY_PATH     = '../cert/apiclient_key.pem';
        self:: $CURL_PROXY_HOST = "0.0.0.0";
        self:: $CURL_PROXY_PORT = 0;
        self:: $REPORT_LEVENL   = 1;
    }


    ////const APPID = 'wx68fa4860d905394f';
    ////const MCHID = '1338796801';
    ////const KEY = 'abcdefghigklmnopqrstuvwxyz123456';
    ////const APPSECRET = 'a2e6719247d964727e7987159ff0213c';
    //
    ////=======【证书路径设置】=====================================
    ///**
    // * TODO：设置商户证书路径
    // * 证书路径,注意应该填写绝对路径（仅退款、撤销订单时需要，可登录商户平台下载，
    // * API证书下载地址：https://pay.weixin.qq.com/index.php/account/api_cert，下载之前需要安装商户操作证书）
    // * @var path
    // */
    //const SSLCERT_PATH = '../cert/apiclient_cert.pem';
    //const SSLKEY_PATH = '../cert/apiclient_key.pem';
    //
    ////=======【curl代理设置】===================================
    ///**
    // * TODO：这里设置代理机器，只有需要代理的时候才设置，不需要代理，请设置为0.0.0.0和0
    // * 本例程通过curl使用HTTP POST方法，此处可修改代理服务器，
    // * 默认CURL_PROXY_HOST=0.0.0.0和CURL_PROXY_PORT=0，此时不开启代理（如有需要才设置）
    // * @var unknown_type
    // */
    //const CURL_PROXY_HOST = "0.0.0.0";//"10.152.18.220";
    //const CURL_PROXY_PORT = 0;//8080;
    //
    ////=======【上报信息配置】===================================
    ///**
    // * TODO：接口调用上报等级，默认紧错误上报（注意：上报超时间为【1s】，上报无论成败【永不抛出异常】，
    // * 不会影响接口调用流程），开启上报之后，方便微信监控请求调用的质量，建议至少
    // * 开启错误上报。
    // * 上报等级，0.关闭上报; 1.仅错误出错上报; 2.全量上报
    // * @var int
    // */
    //const REPORT_LEVENL = 1;


}
