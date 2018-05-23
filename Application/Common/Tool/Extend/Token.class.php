<?php
namespace Common\Tool\Extend;

use Common\Tool\Tool;

class Token extends Tool
{
    /**
     * @param string $string 原文或者密文
     * @param string $operation 操作(ENCODE | DECODE), 默认为：DECODE
     * @param string $key 密钥
     * @param int $expiry 密文有效期, 加密时候有效， 单位：秒，0：为永久有效
     * @return string 处理后的原文或者经过 base64_encode 处理后的密文
     *
     * @example
     *
     *  $a = authcode('www.springload.cn', 'ENCODE', 'springload');
     *  $b = authcode($a, 'DECODE', 'springload');  // $b(www.springload.cn)
     *
     *  $a = authcode('www.springload.cn', 'ENCODE', 'springload', 60);
     *  $b = authcode($a, 'DECODE', 'springload'); // 在60秒内，$b(www.springload.cn)，否则 $b 为空
     */
    public function authCode($string, $operation = 'DECODE', $key = '', $expiry = 60) 
    {
        $ckey_length = 4;
        // 随机密钥长度 取值 0-32;
        // 加入随机密钥，可以令密文无任何规律，即便是原文和密钥完全相同，加密结果也会每次不同，增大破解难度。
        // 取值越大，密文变动规律越大，密文变化 = 16 的 $ckey_length 次方
        // 当此值为 0 时，则不产生随机密钥
    
        $key = md5($key ? $key : 'default_key'); //这里可以填写默认key值
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
    
        $cryptkey = $keya.md5($keya.$keyc);
        $key_length = strlen($cryptkey);
    
        $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
        $string_length = strlen($string);
    
        $result = '';
        $box = range(0, 255);
    
        $rndkey = array();
        for($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }
    
        for($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
    
        for($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
    
        if($operation == 'DECODE') {
            if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            return $keyc.str_replace('=', '', base64_encode($result));
        }
    }
    
    /**
     * 订单号
     * @return string
     */
    
    public function toGUID()
    {
        //订购日期
        $order_date = date('Y-m-d');
        
        //订单号码主体（YYYYMMDDHHIISSNNNNNNNN）
        $orderIdMain = date('YmdHis') . rand(10000000,99999999);
        
        //订单号码主体长度
        $orderIdLen = strlen($orderIdMain);
        
        $orderIdSum = 0;
        
        for($i=0; $i < $orderIdLen; $i++)
        {
          $orderIdSum += (int)(substr($orderIdMain,$i,1));
        }
        
        //唯一订单号码（YYYYMMDDHHIISSNNNNNNNNCC）
        $orderId = $orderIdMain . str_pad((100 - $orderIdSum % 100) % 100,2,'0',STR_PAD_LEFT);
        return $orderId;
    }
    
    /**
     * 加密  
     */
    public function alipayToken(array $data, $privateKey)
    {
        if (empty($data) || empty($privateKey))
        {
            return $data;
        }
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
        
        return $data;
    }
    
    /**
     * 微信支付相关
     */
    public function wx(array $order, $url, $parterId)
    {
        if (empty($order) || empty($url))
        {
            return false;
        }
        ksort($order);
        // STEP 2. 签名
        $sign = "";
        foreach ($order as $key => $value) 
        {
            if ($value && $key != "sign" && $key != "key") {
                $sign .= $key . "=" . $value . "&";
            }
        }
        $sign .= "key=" . $parterId;
        $sign = strtoupper(md5($sign));
        // STEP 3. 请求服务器
        $xml = "<xml>\n";
        foreach ($order as $key => $value) {
            $xml .= "<" . $key . ">" . $value . "</" . $key . ">\n";
        }
        $xml .= "<sign>" . $sign . "</sign>\n";
        $xml .= "</xml>";
        $opts = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type: text/xml',
                'content' => $xml
            ),
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false
            )
        );
        $context = stream_context_create($opts);
        $result = file_get_contents($url, false, $context);
        $result = simplexml_load_string($result, null, LIBXML_NOCDATA);
        return $result;
    }
    
    public function init($xml)
    {
        $fromxml = FromXml($xml);
        if ($fromxml['return_code'] != 'SUCCESS') {
            return $fromxml;
        }
        // var_dump($fromxml);
        $w_sign = array(); // 参加验签签名的参数数组
        $w_sign['appid'] = $fromxml['appid'];
        $w_sign['bank_type'] = $fromxml['bank_type'];
        $w_sign['cash_fee'] = $fromxml['cash_fee'];
        $w_sign['fee_type'] = $fromxml['fee_type'];
        $w_sign['is_subscribe'] = $fromxml['is_subscribe'];
        $w_sign['mch_id'] = $fromxml['mch_id'];
        $w_sign['nonce_str'] = $fromxml['nonce_str'];
        $w_sign['openid'] = $fromxml['openid'];
        $w_sign['out_trade_no'] = $fromxml['out_trade_no'];
        $w_sign['result_code'] = $fromxml['result_code'];
        $w_sign['return_code'] = $fromxml['return_code'];
        $w_sign['time_end'] = $fromxml['time_end'];
        $w_sign['total_fee'] = $fromxml['total_fee'];
        $w_sign['trade_type'] = $fromxml['trade_type'];
        $w_sign['transaction_id'] = $fromxml['transaction_id'];
        // 验证签名
        $sign = self::MakeSign($w_sign);
        if ($sign != $fromxml['sign']) {
            return null;
        }
        return $fromxml;
    }
    
    /**
     * 生成签名
     *
     * @return 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     */
    private static function MakeSign($input)
    {
        // 签名步骤一：按字典序排序参数
        ksort($input);
        $string = self::ToUrlParams($input);
        // 签名步骤二：在string后加入KEY
        $string = $string . "&key=" . APPKEY;
        // 签名步骤三：MD5加密
        $string = md5($string);
        // 签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }
    
    /**
     * 格式化参数格式化成url参数
     */
    private static  function ToUrlParams($array)
    {
        $buff = "";
        foreach ($array as $k => $v) {
            if ($k != "sign" && $v != "" && ! is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }
    
        $buff = trim($buff, "&");
        return $buff;
    }
    
    /**
     *
     * 产生随机字符串，不长于32位
     *
     * @param int $length
     * @return 产生的随机字符串
     */
    public function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i ++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
    
    /**
     * 将xml转为array
     *
     * @param string $xml
     * @throws WxPayException
     */
    private static  function FromXml($xml)
    {
        if (! $xml) {
            return "xml数据异常！";
        }
        // 将XML转为array
        // 禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        
        $aa = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $aa;
    }
    
    /**
     * 获取随机字符串
     * @param int $randLength  长度
     * @param int $addtime  是否加入当前时间戳
     * @param int $includenumber   是否包含数字
     * @return string
     */
    public function getRandStr($randLength=6,$addtime=1,$includenumber=0)
    {
        if ($includenumber){
            $chars='abcdefghijklmnopqrstuvwxyzABCDEFGHJKLMNPQEST123456789';
        }else {
            $chars='abcdefghijklmnopqrstuvwxyz';
        }
        $len=strlen($chars);
        $randStr='';
        for ($i=0;$i<$randLength;$i++){
            $randStr.=$chars[rand(0,$len-1)];
        }
        $tokenvalue=$randStr;
        if ($addtime){
            $tokenvalue=$randStr.time();
        }
        return $tokenvalue;
    }
    
}