<?php

namespace Home\Controller;

//require_once __ROOT__."/ThinkPHP/Vendor/WxPay/lib/WxPay.Data.php";
//require_once __ROOT__."/ThinkPHP/Vendor/WxPay/lib/WxPay.Config.php";
//require_once __ROOT__."/ThinkPHP/Vendor/WxPay/lib/WxPay.Exception.php";
//require_once __ROOT__."/ThinkPHP/Vendor/WxPay/lib/WxPay.Api.php";
//require_once __ROOT__."/ThinkPHP/Vendor/WxPay/lib/WxPay.Notify.php";

use Common\Model\BaseModel;
use Common\Model\OrderModel;
use Common\Model\OrderWxpayModel;
use Common\Model\UserModel;
use Common\Model\PayModel;


/**
 * Class WxJsPayController  公众号支付
 */
class WxJsPayController extends CommonController
{

    public function __construct()
    {
        parent::__construct();
        vendor( 'WxPay.lib.WxPay#Config' );
        new \WxPayConfig($this->getWxpayConfig());
        vendor( 'WxPay.lib.WxPay#Data' );
        vendor( 'WxPay.lib.WxPay#Exception' );
        vendor( 'WxPay.lib.WxPay#Api' );

        vendor( 'WxPay.lib.WxPay#Notify' );
        //vendor( 'WxPay.example.WxPay#JsApiPay' );


    }

    private $url_notify;//回调url
    //返回的数组
    private $returnData = [
        'status' => 0,
        'msg'    => '未知错误111'
    ];

    public function getWxpayConfig()
    {
        return  BaseModel::getInstance(PayModel::class)->where(['pay_type_id' => 1, 'type' => 2])->find();
    }
    public function test()
    {
        $order_no = time();
        //$order_no = 201709211606391357055623;

        $order_money = 0.01;
        ////在Wx_order表中新生成一个订单传给微信接口,下面的订单都是order_wxpay中的订单号
        //$order_no = $this->getUUid( 62 );
        //
        ////将此订单插入order_wxpay表
        //$data = [
        //    'order_id'  => 62,
        //    'wx_pay_id' => $order_no,
        //    'status'    => 0
        //];
        //if ( $this->insertWxOrder( $data ) === false ) {
        //    echo json_encode( $this->returnData );
        //    die;
        //}
        //获取微信$jsApiParameters
        $jsApiParameters = $this->WxJsApi( $order_no,$order_money );
        $this->assign( 'order_money',$order_money );
        $this->assign( 'jsApiParameters',$jsApiParameters );
        $this->display();
    }


    /**
     * @return array 返回订单金额,微信支付信息(对象)
     */
    public function getJsApiData()
    {
        $user_id = zhong_decrypt(I( 'get.user_id' ));//接受user_id

        $order_no = I( 'post.order_id' );//接受order_id 不是编号
        //获取用户的oppen_id
        $open_id = BaseModel::getInstance(UserModel::class)->where(['id' => $user_id])->getField('open_id');
        if(empty($open_id)){
            echo json_encode( $this->returnData );
        }

        //检测订单,成功返回订单表的id 和价格
        if ( ( $order = $this->checkOrder( $order_no ) ) === false ) {
            echo json_encode( $this->returnData );
            die;
        }

        //在Wx_order表中新生成一个订单传给微信接口,下面的订单都是order_wxpay中的订单号
        $order_no = $this->getUUid( $order[ 'id' ] );
        //将此订单插入order_wxpay表
        $data = [
            'order_id'  => $order[ 'id' ],
            'wx_pay_id' => $order_no,
            'status'    => 0
        ];
        if ( $this->insertWxOrder( $data ) === false ) {
            echo json_encode( $this->returnData );
            die;
        }
        //获取微信$jsApiParameters
        $jsApiParameters                       = $this->WxJsApi( $order_no,$order[ 'price_sum' ] ,$open_id);
        $this->returnData[ 'order_money' ]     = $order[ 'price_sum' ];
        $this->returnData[ 'jsApiParameters' ] = json_decode( $jsApiParameters,true );
        $this->returnData[ 'status' ]          = 1;
        //成功时,不需要信息
        unset( $this->returnData[ 'msg' ] );
        $this->returnData = json_encode( $this->returnData );
        echo $this->returnData;
    }

    public function WxJsApi( $order_no,$order_money,$open_id )
    {
        //②、统一下单
        $input = new \WxPayUnifiedOrder();
        $input->SetBody( '测试' );
        $input->SetAttach( "测试" );
        $input->SetOut_trade_no( $order_no );
        $input->SetTotal_fee( $order_money * 100 );
        $input->SetTime_start( date( "YmdHis" ) );
        $input->SetTime_expire( date( "YmdHis",time() + 600 ) );
        $input->SetGoods_tag( "测试" );
        $input->SetNotify_url( C( 'notify_url_wx_weiyi' ) );
        $input->SetTrade_type( "JSAPI" );
        $input->SetOpenid( $open_id );
        $order = \WxPayApi::unifiedOrder( $input );
        //$tools           = new \JsApiPay();
        file_put_contents( './Uploads/qqq/url----.txt',print_r(C( 'notify_url_wx_weiyi' ),true)."-------------" . date( 'Y-m-d H:i:s',time() ) . "----111-\r\n",FILE_APPEND );

        $jsApiParameters = $this->GetJsApiParameters( $order );
        return $jsApiParameters;

    }//end-WxJsApi

    /**
     * @param $order_no
     * @return bool|string
     */
    private function checkOrder( $order_no )
    {
        if ( empty( $order_no ) ) {
            return false;
        }
        $order = BaseModel::getInstance( OrderModel::class )->where( [
            'id'           => $order_no,//订单id
            'order_status' => '0',//订单状态,未支付
            'status'       => 0//状态,0,正常,1删除
        ] )->field( 'id,price_sum' )->find();

        if ( empty( $order ) ) {
            return false;
        }
        return $order;
    }

    /**
     * @param $id
     * @return string
     */
    private function getUUid( $id )
    {
        $chars = uniqid();
        //$chars = md5( uniqid( mt_rand(),true ) );//坑爹,这中订单号,微信不支持,长度不符合
        $chars = 'wx_' . $chars . '-' . $id;
        return $chars;
    }

    /**
     * @param $data
     * @return bool
     */
    private function insertWxOrder( $data )
    {
        $result = BaseModel::getInstance( OrderWxpayModel::class )->add( $data );
        if ( $result ) {
            return true;
        }
        return false;
    }


    /**
     * 获取jsapi支付的参数
     * @param array $UnifiedOrderResult 统一支付接口返回的数据
     * @throws WxPayException
     * @return json数据，可直接填入js函数作为参数
     */
    public function GetJsApiParameters( $UnifiedOrderResult )
    {
        if ( !array_key_exists( "appid",$UnifiedOrderResult ) || !array_key_exists( "prepay_id",$UnifiedOrderResult ) || $UnifiedOrderResult[ 'prepay_id' ] == "" ) {
            throw new \WxPayException( "参数错误" );
        }
        $jsapi = new \WxPayJsApiPay();
        $jsapi->SetAppid( $UnifiedOrderResult[ "appid" ] );
        $timeStamp = time();
        $jsapi->SetTimeStamp( "$timeStamp" );
        $jsapi->SetNonceStr( \WxPayApi::getNonceStr() );
        $jsapi->SetPackage( "prepay_id=" . $UnifiedOrderResult[ 'prepay_id' ] );
        $jsapi->SetSignType( "MD5" );
        $jsapi->SetPaySign( $jsapi->MakeSign() );
        $parameters = json_encode( $jsapi->GetValues() );
        return $parameters;
    }


}