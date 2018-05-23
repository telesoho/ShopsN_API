<?php
//手机移动网站支付
namespace Home\Controller;
use Home\Model\IntegralUseModel;
use Think\Controller;

class AlipayMobileController extends    CommonController
{
     public function PayInfo(){

		 $orderModel = M("order");
		 $goodsModel = M("goods");
		 $order_sn =I("get.order_id");
		 $cond['id'] = $order_sn;
		 $cond['order_status'] ='0';
		 $result = $orderModel->where($cond)->find();
       	 if($result){
			 $goods_title ='测试商品';//I('post.goods_name');
			 $alipay = C("ALIPAY_MOBILE_CONFIG");
			
			 //商户订单号，商户网站订单系统中唯一订单号，必填
			 $out_trade_no = $result['order_sn_id'];
             $body = '2天清仓';
			 //订单名称，必填
			 $subject = $goods_title;
			 //付款金额，必填
			 $total_amount = $result['price_sum'];
			 //商品描述，可空
			
			 //超时时间
			 $timeout_express="1m";
			 $config['app_id'] = $alipay['app_id'];
			 $config['merchant_private_key'] =$alipay['merchant_private_key'];
			 $config['notify_url'] = $alipay['notify_url'];
			 $config['return_url'] = $alipay['return_url'];
			 $config['charset'] = $alipay['charset'];
			 $config['sign_type'] = $alipay['sign_type'];
			 $config['gatewayUrl'] =$alipay['gatewayUrl'];
			 $config['alipay_public_key'] = $alipay['alipay_public_key'];
			 Vendor('AlipayMobile.AlipayTradeWapPayContentBuilder');
			 $payRequestBuilder = new \AlipayTradeWapPayContentBuilder();
			 $payRequestBuilder->setBody($body);
			 $payRequestBuilder->setSubject($subject);
			 $payRequestBuilder->setOutTradeNo($out_trade_no);
			 $payRequestBuilder->setTotalAmount($total_amount);
			 $payRequestBuilder->setTimeExpress($timeout_express);
			 Vendor('AlipayMobile.AlipayTradeService');
			 $payResponse = new \AlipayTradeService($config);
			 $result=$payResponse->wapPay($payRequestBuilder,$config['return_url'],$config['notify_url']);
		 }	
		
    }
	//回调
	public function aliMobileNot(){
		
           $alipay = C("ALIPAY_MOBILE_CONFIG");
        $config['app_id'] = $alipay['app_id'];
        $config['merchant_private_key'] =$alipay['merchant_private_key'];
        $config['notify_url'] = $alipay['notify_url'];
        $config['return_url'] = $alipay['return_url'];
        $config['charset'] = $alipay['charset'];
        $config['sign_type'] = $alipay['sign_type'];
        $config['gatewayUrl'] =$alipay['gatewayUrl'];
        $config['alipay_public_key'] = $alipay['alipay_public_key'];
        $arr = $_POST;
        Vendor('AlipayMobile.AlipayTradeService');
        $alipaySevice = new \AlipayTradeService($config);
         $alipaySevice->writeLog(var_export($_POST,true));
        $result = $alipaySevice->check($arr);
        if ($result) {//验证成功          
            $orderModel = M("order");
            //商户订单号
            $order_sn_id = $_POST['out_trade_no'];
            //支付宝交易号
            $trade_no = $_POST['trade_no'];
            file_put_contents("./alimobile2222.txt",$order_sn_id);
            $arr['trade_no'] = $trade_no;
            $arr['order_status'] = '1';
            $arr['pay_time'] = time();
            $arr['pay_type'] = 2;
            if($order_sn_id){
                $orderModel
                    ->where([
                        'order_sn_id'=>$order_sn_id
                    ])
                    ->save($arr);
                $or_id=$orderModel
                    ->where([
                        'order_sn_id'=>$order_sn_id
                    ])
                    ->getField('id');
                (new IntegralUseModel())->_addIntegralRecord($or_id);//添加积分
            }
            echo "success";        //请不要修改或删除
           $this->returnMessage(1,'支付成功','');
        } else {
            //验证失败

            echo "fail";    //请不要修改或删除

        }
       
    }
	//支付成功后，同步跳转
	public function aliMobileRet(){
          $alipay = C("ALIPAY_MOBILE_CONFIG");
        $config['app_id'] = $alipay['app_id'];
        $config['merchant_private_key'] =$alipay['merchant_private_key'];
        $config['notify_url'] = $alipay['notify_url'];
        $config['return_url'] = $alipay['return_url'];
        $config['charset'] = $alipay['charset'];
        $config['sign_type'] = $alipay['sign_type'];
        $config['gatewayUrl'] =$alipay['gatewayUrl'];
        $config['alipay_public_key'] = $alipay['alipay_public_key'];
        $arr = $_GET;
        Vendor('AlipayMobile.AlipayTradeService');
        $alipaySevice = new \AlipayTradeService($config);
        $result = $alipaySevice->check($arr);
        if($result) {//验证成功

            //请在这里加上商户的业务逻辑程序代码
            $orderModel = M("order");
            //商户订单号
            $order_sn_id = htmlspecialchars($_GET['out_trade_no']);
            //支付宝交易号
            $trade_no = htmlspecialchars($_GET['trade_no']);
            $arr['trade_no'] = $trade_no;
            $arr['order_status'] = '1';
            $arr['pay_time'] = time();
            $arr['pay_type'] = 2;
            if($order_sn_id){
                $orderModel->where(['order_sn_id'=>$order_sn_id])->save($arr);
            }
            $uid = $orderModel->where(['order_sn_id'=>$order_sn_id])->getField('user_id');
            $app_user_id = zhong_encrypt($uid);
            header("Refresh: 0; url=http://www.2tianqc.com/mobile/#/getInfo?data_token=" . $app_user_id);
            exit;
            $this->success("支付成功");

        }else {
            //验证失败
            $this->error("支付失败");
        }
    }
    /**
     * 余额支付
     */

public function balancePay(){
    $order_id=I('post.order_id');
    $user_id=zhong_decrypt(I('post.app_user_id'));
    if(IS_POST &&!empty($order_id)&&!empty($user_id) ) {
        $order = M('order');
        $balance = M('balance');
        $price_sum = $order->where(['id' => $order_id])->getField('price_sum');
        $myBalance = $balance->where(['user_id' => $user_id])->getField('account_balance');

        if ($myBalance >= $price_sum) {
            $balance->startTrans();
            $price['account_balance'] = $myBalance - $price_sum;
            $orderPay['pay_time'] = time();
            $orderPay['order_status'] = '1';
            $orderPay['pay_type'] = 3;//余额支付
            $r1 = $balance->where(['user_id' => $user_id])->save($price);

            if (!empty($r1)) {
                $balance->commit();
                $order->where(['id' => $order_id])->save($orderPay);
                M('order_goods')->where(['order_id' => $order_id])->save(['status' => '1']);
                $this->returnMessage(1, '支付成功', '');
            } else {
                $balance->rollback();
                $this->returnMessage(0, '支付失败', '');
            }
        } else {
            $this->returnMessage(0, '余额不足', '');
        }

    }
}






}