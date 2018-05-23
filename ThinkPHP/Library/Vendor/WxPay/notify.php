<?php

$simple = json_decode(json_encode(simplexml_load_string($GLOBALS['HTTP_RAW_POST_DATA'], 'SimpleXMLElement', LIBXML_NOCDATA)), true);

//$notify_data['order_no'] = $notify_data['trade_no'] = $simple['out_trade_no'];
//$notify_data['third_id'] = $simple['transaction_id'];
//$notify_data['pay_money'] = $simple['total_fee'];
//
//$notify_data['payment_method'] = 'weixin';


file_put_contents('ac_notify_data.txt', date("Y-m-d H:i:s")."：".json_encode($simple));
