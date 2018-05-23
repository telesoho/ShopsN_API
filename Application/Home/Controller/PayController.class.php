<?php
namespace Home\Controller;
use Think\Controller;

class PayController extends CommonController{
    //在类初始化方法中，引入相关类库11
    public function _initialize(){
        vendor('Dem.aop.AopClient');
    }
    //支付宝相关配置
    public function zf_pay(){
        $out_trade_no =I('post.order_id');//订单id
        $total_amount =I('post.price');//实付价格
        $title=I('post.order_name');//订单名称
        //组装系统参数
        $data = array(
            "app_id"        =>  '2017010304826935' ,//appid
            "version"		=> "1.0",
            "format"		=> "json",
            "method"		=>"alipay.trade.app.pay",
            "timestamp"		=>date("Y-m-d H:i:s",time()),
            "charset"		=>"utf-8",
            "sign_type"     => "RSA", //无需修改
            "notify_url"	=>"http://api.yisu.cn/Home/pay/notify",//回调地址
            "biz_content"	=> json_encode(array(
                "subject" 		=>$title.$out_trade_no,//商品名称
                "out_trade_no"	=>$out_trade_no,//商户网站唯一订单号
                "total_amount"	=>$total_amount,//0.01订单金额，单位元
                "seller_id"		=>"yujing_shiye@sina.com",//支付宝账号
                "product_code"	=>"QUICK_MSECURITY_PAY",
                "timeout_express" =>"1m",//交易超时时间
            )),
        );
        $privateKey = file_get_contents(ACCESS);
        //$alipayNotify = new \AopClient();
        //$data['sign'] = $alipayNotify->rsaSign($data);
        //$data['sign_type']="RSA";//RSA验证签名
        //$data = createLinkstring($data);
        ksort( $data );
        //重新组装参数
        $params = array();
        foreach($data as $key => $value){
            //生成加密的签名参数
            $params[] = $key .'='. rawurlencode($value);
            // 生成未加密的签名参数  用此参数去签名
            $signparams[] = $key .'='. $value;
        }
        //2种参数 都用&符合拼接
        $data = implode('&', $params);
        $signString = implode('&', $signparams);
        $res = openssl_get_privatekey($privateKey);
        openssl_sign($signString, $sign, $res,OPENSSL_ALGO_SHA1);
        openssl_free_key($res);
        $sign = urlencode(base64_encode($sign));
        $data.='&sign='.$sign;
        $result = array(
            'status'=>1,
            'msg'=>'返回成功',
            'data'=>$data
        );
        echo json_encode($result);
    }

    /**
     * 支付宝回调地址
     *
     */
    public function notify(){
       //支付成功后的状态
        $aop = new \AopClient;
        $aop->alipayrsaPublicKey = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDDI6d306Q8fIfCOaTXyiUeJHkrIvYISRcc73s3vF1ZT7XN8RNPwJxo8pWaJMmvyTn9N4HQ632qJBVHf8sxHi/fEsraprwCtzvzQETrNRwVxLO5jVmRGi60j8Ue1efIlzPXV9je9mkjzOmdssymZkh2QhUrCmZYI/FCEa3/cNMW0QIDAQAB';
        $verify_result = $aop->rsaCheckV1($_POST, NULL, "RSA");
 if($verify_result) {//验证成功
    //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
    $out_trade_no = $_POST['out_trade_no'];  //商户订单号
    //支付宝交易号
    $trade_no = $_POST['trade_no'];
    $total_amount=$_POST['total_fee'];//交易金额
    //交易状态$_POST['trade_status'];
    if($_POST['trade_status'] == 'TRADE_FINISHED') {

        } else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
        $orders_num = $out_trade_no;//订单号
       // $price_sum=M('order')->where('id=%s',$orders_num)->field('price_sum')->find()['price_sum'];
        //判断订单号防止多次回调
       $order_status=M('order')->where(['id'=>$orders_num])->getField('order_status');
        if($order_status=='0') {
            //修改支付状态
            $or['order_status'] = '1';//已支付
            $or['pay_type'] = 2;//支付宝支付
            $or['pay_time'] = time();
            M('order')->where(['id'=> $orders_num])->save($or);
            $or_goods['status'] = '1';
            M('order_goods')->where(['order_id'=>$orders_num])->save($or_goods);

            //积分订单修改积分
            $integral = M('order')->where(['id'=>$orders_num])->field('order_type,user_id')->find();
            $good_id = M('order_goods')->where(['order_id'=>$orders_num])->getField('goods_id');
            if ($integral['order_type']==4) {
                $integral_data['user_id'] = $integral['user_id'];
                $integral_data['integral'] = $integral['integral'];
                $integral_data['goods_id'] = $good_id;
                $integral_data['trading_time'] = time();
                $integral_data['remarks'] = '支付抵扣';
                $integral_data['type'] = 2;//支出
                $integral_data['status'] = 2;
                M('integral_use')->add($integral_data);
            }else { //支付成功添加积分
                $order_goods = M('order_goods')->where(['order_id'=>$orders_num])->field('goods_id,user_id')->select();
                $goods_model=M('goods');
                foreach ($order_goods as $k => $v) {
                    $integra = $goods_model->where(['id'=>$v['goods_id']])->field('d_integral')->find();
                    $integ['user_id'] = $v['user_id'];
                    $integ['integral'] = $integra['d_integral'];
                    $integ['remarks'] = '商品返积分';
                    $integ['goods_id'] = $v['goods_id'];
                    $integ['trading_time'] = time();
                    $integ['type'] = 1;
                    $integ['status'] = 1;
                    if ($integra['d_integral'] != 0) M('integral_use')->add($integ);
                }
            }
        }
        echo "success";
    }else{ }
     echo "success";
 }else{
     echo "fail";
 }
    //调试用，写文本函数记录程序运行情况是否正常
    //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
}

    /***
     * 微信支付
     */
    public function wxpay(){
        $order_sn =I("get.order_id");
        $orderData    = M('order')->field('id,order_sn_id,price_sum')->where(['id'=>$order_sn,'order_status'=>'0'])->select()[0];
        if(!$orderData['id']){
            exit( urldecode( json_encode( 'fail' ) ) );
        }
		//微信签名
// STEP 0. 账号帐户资料
//更改商户把相关参数后可测试
		$APP_ID="wx93926bb225cb6c31"; //APPID
		$APP_SECRET="3f11cf6d350b1c654e934ba626a58a08";//appsecret
//商户号，填写商户对应参数
		$MCH_ID="1434864002";
//商户API密钥，填写相应参数
		$PARTNER_ID="Yousheng123bangong456SHANGcheng7";
//支付结果回调页面
		$NOTIFY_URL= 'http://api.yisu.cn/Home/Pay/wx_notify';
//STEP 1. 构造一个订单。
		$order=array(
            "body" => $_REQUEST['body'],//商品描述
            "appid" => $APP_ID,//应用ID
            "mch_id" => $MCH_ID,//商户号
            "nonce_str" => mt_rand(),//随机字符串
            "notify_url" => $NOTIFY_URL,//notify_url
            "out_trade_no" => $orderData['order_sn_id'],//商户订单号
            "spbill_create_ip" => $_SERVER['REMOTE_ADDR'],//终端IP
            "total_fee" =>$orderData['price_sum']*100,// ($_REQUEST['total_fee'] *100),//坑！！！这里的最小单位时分，跟支付宝不一样。1就是1分钱。只能是整形。
            "trade_type" => "APP"//交易类型
        );
		//file_put_contents("wxtest.txt",json_encode($order));
		ksort($order);
//STEP 2. 签名
		$sign="";
		foreach ($order as $key => $value) {
            if($value&&$key!="sign"&&$key!="key"){
                $sign.=$key."=".$value."&";
            }
        }
		$sign.="key=".$PARTNER_ID;
		$sign=strtoupper(md5($sign));
//STEP 3. 请求服务器
		$xml="<xml>\n";
		foreach ($order as $key => $value) {
            $xml.="<".$key.">".$value."</".$key.">\n";
        }
		$xml.="<sign>".$sign."</sign>\n";
		$xml.="</xml>";
		$opts = array(
            'http' =>
                array(
                    'method' => 'POST',
                    'header' => 'Content-type: text/xml',
                    'content' => $xml
                ),
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            )
        );
		$context = stream_context_create($opts);
		$result = file_get_contents('https://api.mch.weixin.qq.com/pay/unifiedorder', false, $context);
		$result = simplexml_load_string($result,null, LIBXML_NOCDATA);
//在此打印出 result 可以看出各项参数是否正确
//file_put_contents('wx_result.txt',json_encode($result));
//使用$result->nonce_str和$result->prepay_id。再次签名返回app可以直接打开的链接。
		$data=array(
            "noncestr"=>"".$result->nonce_str,
            "prepayid"=>"".$result->prepay_id,//上一步请求微信服务器得到nonce_str和prepay_id参数。
            "appid"=>$APP_ID,
            "package"=>"Sign=WXPay",
            "partnerid"=>$MCH_ID,
            "timestamp"=>time(),
        );
		ksort($data);
		$sign="";
		foreach ($data as $key => $value) {
            if($value&&$key!="sign"&&$key!="key"){
                $sign.=$key."=".$value."&";
            }
        }
		$sign.="key=".$PARTNER_ID;
		$sign=strtoupper(md5($sign));
		$data['sign']=$sign;
//		$this->returnMessage(1,'返回成功',$data);
        exit( urldecode( json_encode( $data ) ) );
    }

/**
 * 微信回调函数
 */
  public  function wx_notify()
  {
      define('APPKEY', 'Yousheng123bangong456SHANGcheng7');
//获取通知的数据
      $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
      $data = array();
      $data =$this->Init($xml);
//var_dump($data);
//$orders_num =$data['out_trade_no'];
      $orders_num = isset($data['out_trade_no']) ? $data['out_trade_no'] : '';
//var_dump($orders_num);
      //查询订单
      if (!empty($orders_num)) $order = M('order')->where(['order_sn_id'=>$orders_num])->find();
      if ($order) {
          $orders_num= M('order')->where(['order_sn_id'=>$orders_num])->getField('id');
          //判断订单状态是否可支付
          if ($order['order_status'] =='0') {
                  //修改支付状态
                  $or['order_status'] = '1';//已支付
                  $or['pay_type'] = 1;//微信支付
                  $or['pay_time'] = time();
                  M('order')->where(['id'=>$orders_num])->save($or);
                  $or_goods['status'] = '1';
                  M('order_goods')->where(['order_id'=>$orders_num])->save($or_goods);
                  //积分订单修改积分
                  $integral = M('order')->where(['id'=>$orders_num])->field('order_type,user_id')->find();
                  $good_id = M('order_goods')->where(['order_id'=>$orders_num])->getField('goods_id');
                  if ($integral['order_type']==4) {
                      $integral_data['user_id'] = $integral['user_id'];
                      $integral_data['integral'] = $integral['integral'];
                      $integral_data['goods_id'] = $good_id;
                      $integral_data['trading_time'] = time();
                      $integral_data['remarks'] = '支付抵扣';
                      $integral_data['type'] = 2;//支出
                      $integral_data['status'] = 2;
                      M('integral_use')->add($integral_data);
                  }else { //支付成功添加积分
                      $order_goods = M('order_goods')->where(['order_id'=>$orders_num])->field('goods_id,user_id')->select();
                      $goods_model=M('goods');
                      foreach ($order_goods as $k => $v) {
                          $integra = $goods_model->where(['id'=> $v['goods_id']])->field('d_integral')->find();
                          $integ['user_id'] = $v['user_id'];
                          $integ['integral'] = $integra['d_integral'];
                          $integ['remarks'] = '商品返积分';
                          $integ['goods_id'] = $v['goods_id'];
                          $integ['trading_time'] = time();
                          $integ['type'] = 1;
                          $integ['status'] = 1;
                          if ($integra['d_integral'] != 0) M('integral_use')->add($integ);
                      }
                  }

          } else {
              echo "ERROR";
              exit;
          }
          echo "SUCCESS";
      }else {
          echo "ERROR";
      }
  }
    /**
     * 微信支付结果返回给客户端
     */
    public function WXReturnApp(){
        if(IS_POST){
            $order_id=I('post.order_id');
            $order=M('order')->where(['id'=>$order_id])->field('order_status')->find();
            if($order['order_status']==1) $this->returnMessage(1,'支付成功','');
            else $this->returnMessage(0,'支付失败','');
        }
    }



    /**
     * @param $xml
     * @return mixed|string
     * xml转换
     */

   function Init($xml){
    $fromxml =$this->FromXml($xml);
    if($fromxml['return_code'] != 'SUCCESS'){
        return $fromxml;
    }
    //var_dump($fromxml);
    $w_sign = array();           //参加验签签名的参数数组
    $w_sign['appid']             = $fromxml['appid'];
    $w_sign['bank_type']         = $fromxml['bank_type'];
    $w_sign['cash_fee']          = $fromxml['cash_fee'];
    $w_sign['fee_type']          = $fromxml['fee_type'];
    $w_sign['is_subscribe']      = $fromxml['is_subscribe'];
    $w_sign['mch_id']            = $fromxml['mch_id'];
    $w_sign['nonce_str']         = $fromxml['nonce_str'];
    $w_sign['openid']            = $fromxml['openid'];
    $w_sign['out_trade_no']      = $fromxml['out_trade_no'];
    $w_sign['result_code']       = $fromxml['result_code'];
    $w_sign['return_code']       = $fromxml['return_code'];
    $w_sign['time_end']          = $fromxml['time_end'];
    $w_sign['total_fee']         = $fromxml['total_fee'];
    $w_sign['trade_type']        = $fromxml['trade_type'];
    $w_sign['transaction_id']    = $fromxml['transaction_id'];
    //验证签名
    $sign =$this->MakeSign($w_sign);
    if($sign != $fromxml['sign']){
        return "签名错误";
    }
    return $fromxml;
}
/**
 * 生成签名
 * @return 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
 */
function MakeSign($input){
    //签名步骤一：按字典序排序参数
    ksort($input);
    $string = $this->ToUrlParams($input);
    //签名步骤二：在string后加入KEY
    $string = $string . "&key=".APPKEY;
    //签名步骤三：MD5加密
    $string = md5($string);
    //签名步骤四：所有字符转为大写
    $result = strtoupper($string);
    return $result;
}

/**
 * 格式化参数格式化成url参数
 */
function ToUrlParams($array){
    $buff = "";
    foreach ($array as $k => $v)
    {
        if($k != "sign" && $v != "" && !is_array($v)){
            $buff .= $k . "=" . $v . "&";
        }
    }

    $buff = trim($buff, "&");
    return $buff;
}
/**
 *
 * 产生随机字符串，不长于32位
 * @param int $length
 * @return 产生的随机字符串
 */
function getNonceStr($length = 32){
    $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
    $str ="";
    for ( $i = 0; $i < $length; $i++ )  {
        $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
    }
    return $str;
}


/**
 * 将xml转为array
 * @param string $xml
 * @throws WxPayException
 */
function FromXml($xml){
    if(!$xml){
        return "xml数据异常！";
    }
    //将XML转为array
    //禁止引用外部xml实体
    libxml_disable_entity_loader(true);
    $aa = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    return $aa;
}











}

