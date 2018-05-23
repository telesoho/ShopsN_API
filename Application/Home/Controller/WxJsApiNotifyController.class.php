<?php
namespace Home\Controller;

use Common\Model\BaseModel;
use Common\Model\OrderGoodsModel;
use Common\Model\OrderModel;
use Common\Model\OrderWxpayModel;
use Common\Model\PayModel;
vendor( 'WxPay.lib.WxPay#Data' );
vendor( 'WxPay.lib.WxPay#Notify' );
vendor( 'WxPay.lib.WxPay#Exception' );
vendor( 'WxPay.lib.WxPay#Api' );
vendor( 'WxPay.example.log' );
class WxJsApiNotifyController extends \WxPayNotify
{
    private $order_id;
    private $data_goods = [ ];
    private $data_space = [ ];

    public function __construct()
    {
        vendor( 'WxPay.lib.WxPay#Config' );
        new \WxPayConfig($this->getWxpayConfig());
    }

    public function getWxpayConfig()
    {
        return  BaseModel::getInstance(PayModel::class)->where(['pay_type_id' => 1, 'type' => 2])->find();
    }

    //查询订单
    public function Queryorder( $transaction_id )
    {
        $input = new \WxPayOrderQuery();
        $input->SetTransaction_id( $transaction_id );
        $result = \WxPayApi::orderQuery( $input );
        \Log::DEBUG( "query:" . json_encode( $result ) );
        if ( array_key_exists( "return_code",$result )
            && array_key_exists( "result_code",$result )
            && $result[ "return_code" ] == "SUCCESS"
            && $result[ "result_code" ] == "SUCCESS"
        ) {
            return true;
        }
        return false;
    }

    //重写回调处理函数
    public function NotifyProcess( $data,&$msg )
    {
        \Log::DEBUG( "call back:" . json_encode( $data ) );

        if ( !array_key_exists( "transaction_id",$data ) ) {
            $msg = "输入参数不正确";
            return false;
        }
        //查询订单，判断订单真实性
        if ( !$this->Queryorder( $data[ "transaction_id" ] ) ) {
            $msg = "订单查询失败";
            return false;
        }
        return true;
    }

    /**[dec] 微信回调地址
     * @return bool|void
     */
    public function WXJsPayUrl9961()
    {
        file_put_contents( './Uploads/qqq/qingcai----.txt',"-收到回调----1---------" . date( 'Y-m-d H:i:s',time() ) . "----111-\r\n",FILE_APPEND );

        $simple         = json_decode( json_encode( simplexml_load_string( file_get_contents('php://input'),'SimpleXMLElement',LIBXML_NOCDATA ) ),true );
        file_put_contents( './Uploads/qqq/qingcai----.txt',"-收到回调-----2--------" . date( 'Y-m-d H:i:s',time() ) . "----111-\r\n",FILE_APPEND );
        file_put_contents( './Uploads/qqq/qingcai----.txt',print_r($simple,true) . "--------------" . date( 'Y-m-d H:i:s',time() ) . "----111-\r\n",FILE_APPEND );
        $wx_pay_id      = $simple[ 'out_trade_no' ];
        $this->order_id = BaseModel::getInstance( OrderWxpayModel::class )->where( [ 'wx_pay_id' => $wx_pay_id,'status' => 0 ] )->getField( 'order_id' );
        file_put_contents( './Uploads/qqq/qingcai----.txt',$this->order_id . "--------------" . date( 'Y-m-d H:i:s',time() ) . "----111-\r\n",FILE_APPEND );

        if ( empty( $this->order_id ) ) {
            file_put_contents( './Uploads/qqq/qingcai----.txt',$this->order_id . "--------------" . date( 'Y-m-d H:i:s',time() ) . "----err1-\r\n",FILE_APPEND );
            //订单不存在
            echo 'error';
            die;
        }
        file_put_contents( './Uploads/qqq/qingcai----.txt',$this->order_id . "--------------" . date( 'Y-m-d H:i:s',time() ) . "----222-\r\n",FILE_APPEND );
        unset( $simple );
        //初始化日志
        $logHandler = new \CLogFileHandler( C( 'jsapi_log_pash' ) . "/logs/" . date( 'Y-m-d' ) . '.log' );
        $log        = \Log::Init( $logHandler,15 );
        \Log::DEBUG( "begin notify" );
        file_put_contents( './Uploads/qqq/qingcai----.txt',$this->order_id . "--------------" . date( 'Y-m-d H:i:s',time() ) . "----333-\r\n",FILE_APPEND );

        $this->Handle( false );
        if ( $this->GetReturn_code() == 'FAIL' ) {
            file_put_contents( './Uploads/qqq/qingcai----.txt',$this->order_id . "--------------" . date( 'Y-m-d H:i:s',time() ) . "----err3-\r\n",FILE_APPEND );
            echo 'error';
            die;
        }
        file_put_contents( './Uploads/qqq/qingcai----.txt',"successssssssssssssssssssss--------------" . date( 'Y-m-d H:i:s',time() ) . "----successsss-\r\n",FILE_APPEND );

        $this->updateOrder( $wx_pay_id );


    }

    /**[dec] 修改订单状态
     * @param $wx_pay_id
     */
    private function updateOrder( $wx_pay_id )
    {
        file_put_contents( './Uploads/qqq/qingcai----.txt',"updateOrder--------------" . date( 'Y-m-d H:i:s',time() ) . $wx_pay_id . "----11-\r\n",FILE_APPEND );
        $Wx = BaseModel::getInstance( OrderWxpayModel::class );
        $Wx->startTrans();
        //修改微信订单表状态
        $result = $Wx->where( [ 'wx_pay_id' => $wx_pay_id ] )->save( [ 'status' => 1,'type' => 0 ] );
        //修改订单主表状态
        $result2 = BaseModel::getInstance( OrderModel::class )->where( [ 'id' => $this->order_id ] )->save( [ 'order_status' => 1,'pay_time' => time(),'pay_type' => 1 ] );
        //修改订单商品表状态
        $result3 = BaseModel::getInstance( OrderGoodsModel::class )->where( [ 'order_id' => $this->order_id ] )->save( [ 'status' => 1 ] );
        //减库存:商品表 商品规格表(如果 存在)
        $result4 = $this->delArray()->buildSql( $this->data_goods,'goods','stock' );
        //先给变量result5 附一个1,如果存在规格则需要减库存,再重新赋值,再进行判断.
        $result5 = 1;
        if ( $this->data_space ) {
            $result5 = $this->buildSql( $this->data_space,'spec_goods_price','store_count' );
        }

        //所有都成立则提交
        if ( ( $result !== false ) && ( $result2 !== false ) && ( $result3 !== false ) && ( $result4 > 0 ) && ( $result5 > 0 ) ) {
            file_put_contents( './Uploads/qqq/qingcai----.txt',"updateOrder--------------" . date( 'Y-m-d H:i:s',time() ) . $wx_pay_id . "----33-\r\n",FILE_APPEND );
            $Wx->commit();
        } else {
            //失败,添加一条记录
            file_put_contents( './Uploads/qqq/errorrrrrrrrrrrrr.txt',$wx_pay_id . "用户已支付,订单状态修改失败--------------" . date( 'Y-m-d H:i:s',time() ) . "-----\r\n",FILE_APPEND );
            $Wx->rollback();
        }


    }

    private function delArray()
    {
        $orderData = BaseModel::getInstance( OrderGoodsModel::class )->field( 'goods_id,space_id,goods_num' )->where( [ 'order_id' => $this->order_id ] )->select();
        if ( empty( $orderData ) ) {
            return false;
        }
        //处理数组
        foreach ( $orderData as $k => $v ) {
            if ( $v[ 'goods_id' ] ) {
                $this->data_goods[ $v[ 'goods_id' ] ] = $v[ 'goods_num' ];
            }
            if ( $v[ 'space_id' ] ) {
                $this->data_space[ $v[ 'space_id' ] ] = $v[ 'goods_num' ];
            }
        }
        return $this;
    }


    public function buildSql( $keyArray,$tableName,$key )
    {
        $sql = "UPDATE " . C( 'DB_PREFIX' ) . $tableName . " SET ";
        $sql .= $key . " = CASE id ";
        foreach ( $keyArray as $k => $v ) {
            $sql .= " WHEN " . $k . " THEN " . $key . '-' . $v;
        }
        $sql .= " END WHERE `id` IN (" . join( ',',array_keys( $keyArray ) ) . ')';
        file_put_contents( './Uploads/qqq/sql-------.txt',$sql."--------------" . date( 'Y-m-d H:i:s') . "---$tableName-\r\n",FILE_APPEND );
        return M()->execute( $sql );
    }


}
