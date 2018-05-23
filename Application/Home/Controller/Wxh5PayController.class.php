<?php

namespace Home\Controller;
use Common\Controller\RebateLogController;
use Think\Controller;
use Common\Model\OrderGoodsModel;
use Common\Model\OrderModel;
use Common\Model\BaseModel;

/**
 * Class WxJsPayController  公众号支付
 */

//微信支付
class Wxh5PayController extends CommonController
{
    private $order_id;
    private $data_space = [];
    private $data_goods = [];

    public function wxh5pay(){
         $orderModel = M("order");
         $goodsModel = M("goods");
         $order_sn =I("get.order_id");
         $cond['id'] = $order_sn;
         $cond['order_status'] ='0';
         $result = $orderModel->field('order_sn_id,price_sum')->where($cond)->find();

        if(empty($result)){
            $this->returnMessage(0,'订单不存在','');
        }

//        $ip = $_SERVER['REMOTE_ADDR'];
//        if(!$ip){
//            $ip = getenv("REMOTE_ADDR");
//        }
        $ip = $this->get_client_ip();

        //参数（一部分前端传过来）
        $appid        = 'wx4155f0f5b87a950a';      //微信公众号id
        $mch_id       = '1499024422';            //微信商户号
        $nonce_str    =  '2tianqc2tianqc2tianqc2tianqc2tia';            //随机字符串
        $body         =  '商品内容';
        $out_trade_no =  $result['order_sn_id'];  //订单号
        $total_fee    =  $result['price_sum']*100;                        //总金额
        $spbill_create_ip = $ip;           //用户端ip
        $notify_url    =   'http://api.2tianqc.com/home/Wxh5Pay/noticeurl'; //异步回调地址
        $trade_type    =    'MWEB';                                       //支付类型
        $scene_info    =   '{"h5_info":{"type":"Wap","wap_url":https://pay.qq.com","wap_name": "腾讯充值"}}';   //场景信息
//        $key           = "27bfad1c4fea55f70dbe02ea679a15d5";        //秘钥
        $key           = "2tianqc2tianqc2tianqc2tianqc2tia";        //秘钥


        //签名
        $signA = "appid=".$appid."&body=".$body."&mch_id=".$mch_id."&nonce_str=".$nonce_str."&notify_url=".$notify_url."&out_trade_no=".$out_trade_no."&scene_info=".$scene_info."&spbill_create_ip=".$spbill_create_ip."&total_fee=".$total_fee."&trade_type=".$trade_type;

        $strSignTmp = $signA."&key=".$key; //拼接字符串

        $sign = strtoupper(MD5($strSignTmp)); // MD5 后转换成大写


         //上传参数
        $data = "<xml>
        <appid>".$appid."</appid>
        <body>".$body."</body>
        <mch_id>".$mch_id."</mch_id>
        <nonce_str>".$nonce_str."</nonce_str>
        <notify_url>".$notify_url."</notify_url>
        <out_trade_no>".$out_trade_no."</out_trade_no>
        <spbill_create_ip>".$spbill_create_ip."</spbill_create_ip>
        <total_fee>".$total_fee."</total_fee>
        <trade_type>".$trade_type."</trade_type>
        <scene_info>".$scene_info."</scene_info>
        <sign>".$sign."</sign>
        </xml>";
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $resXml =$this->http_post($url,$data);

        $objectxml = (array)simplexml_load_string($resXml,'SimpleXMLElement', LIBXML_NOCDATA);


        $redirect_url = urlencode('http://www.2tianqc.com/mobile/#/home');

        $lastUrl =$objectxml['mweb_url'].'&redirect_url='.$redirect_url;

        $this->returnMessage(1,'',$lastUrl);

         //echo  "<script>window.location.href='".$lastUrl."'</script>";
 
    }
    //curl post 请求数据
     function http_post($url,$data){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HEADER,0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,false);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

    function noticeurl(){
        $response=$this->xmlToArray(file_get_contents('php://input'));
        file_put_contents('./test.text',$response['appid']);
        if($response['return_code'] == 'SUCCESS') {
            $orderModel=M('order');
            $order_sn_id=$response['out_trade_no'];
            $this->order_id = M('order')->where(['order_sn_id'=>$order_sn_id]) ->getField('id');
            /*$arr['order_status'] = '1';
            $arr['pay_time'] = time();
            $arr['pay_type'] = 2;
            $paytime=$orderModel
                    ->where([
                        'order_sn_id'=>$order_sn_id
                    ])
                    ->getField('pay_time');
             if($order_sn_id && empty($paytime)){
                $orderModel
                    ->where([
                        'order_sn_id'=>$order_sn_id
                    ])
                    ->save($arr);

            }*/
            $this->updateOrder($this->order_id);
        }
        if ($response['result_code'] == 'FAIL') {
            return $err_code_des = $response['err_code_des'];
      }

}
    //xml转换数组
    function xmlToArray($xml){

         //禁止引用外部xml实体

         libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $values;

    }
    /**[dec] 修改订单状态
     * @param $wx_pay_id
     */
    private function updateOrder( $order_id )
    {
        file_put_contents( './Uploads/qqq/qingcai----.txt',"updateOrder--------------" . date( 'Y-m-d H:i:s',time() ) . $order_id . "----11-\r\n",FILE_APPEND );
        //修改订单主表状态
        $result2 = BaseModel::getInstance( OrderModel::class )->where( [ 'id' => $this->order_id ] )->save( [ 'order_status' => 1,'pay_time' => time(),'pay_type' => 1 ] );
        //修改订单商品表状态
        $result3 = BaseModel::getInstance( OrderGoodsModel::class )->where( [ 'order_id' => $this->order_id ] )->save( [ 'status' => 1 ] );
        //减库存:商品表 商品规格表(如果 存在)
//        $result4 = $this->delArray()->buildSql( $this->data_goods,'goods','stock' );
        //先给变量result5 附一个1,如果存在规格则需要减库存,再重新赋值,再进行判断.
        $result5 = 1;
        if ( $this->data_space ) {
            $result5 = $this->buildSql( $this->data_space,'spec_goods_price','store_count' );
        }

        //所有都成立则提交
        if ( ( $result2 !== false ) && ( $result3 !== false )  && ( $result5 > 0 ) ) {
            file_put_contents( './Uploads/qqq/qingcai----.txt',"updateOrder--------------" . date( 'Y-m-d H:i:s',time() ) . $order_id . "----33-\r\n",FILE_APPEND );
        } else {
            //失败,添加一条记录
            file_put_contents( './Uploads/qqq/errorrrrrrrrrrrrr.txt',$order_id . "用户已支付,订单状态修改失败--------------" . date( 'Y-m-d H:i:s',time() ) . "-----\r\n",FILE_APPEND );
        }


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

    //获取用户端IP
    function get_client_ip() {
        if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $ip = getenv('REMOTE_ADDR');
        } elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return preg_match ( '/[\d\.]{7,15}/', $ip, $matches ) ? $matches [0] : '';
    }


}