<?php
namespace Common\TraitClass;

use Common\Tool\Tool;
use Admin\Model\OrderModel;
use Common\Model\OrderWxpayModel;
use Common\Model\BaseModel;

/**
 * 退货 
 */
trait CancelOrder
{
    /**
     * 退款 
     */
    public function cancelOrder()
    {
        Tool::checkPost($_GET, array('is_numeric' => array('idsaw')), true, array('idsaw')) ? true : $this->error('退款出错');
        
        $model = BaseModel::getInstance(OrderModel::class);
        //获取订单数据
        $data = $model->find(array(
            'field' => array($model::$id_d, $model::$priceSum_d, $model::$orderSn_id_d, $model::$orderStatus_d),
            'where' => array('id' => $_GET['idsaw']),
        ));
        
        $click = S('click');
        
        if (empty($click) || $click < 3) {
            
            $click += 1;
           
            S('click', $click, 20);
        }
        
        if ($click >= 3) {
            $this->error('恶意点击，将会受到惩处');die();
        }
        
        !empty($data) || $data[$model::$orderStatus_d] != OrderModel::ReturnAudit ? : $this->error('系统错误或订单状态有误');
        
        //获取微信支付凭据
        
        $orderWx = OrderWxpayModel::getInitation()->getOrderWx($data['id']);
        
        !empty($orderWx) ? : $this->error('系统错误。未找到凭据<script>window.close();</script>');
        Vendor('Wxpay.WxPayPubHelper.WxPayPubHelper');
       
       
        
        $wx = new \Refund_pub();
        
        $wx->setParameter('out_trade_no', $orderWx['wx_pay_id']);
        $wx->setParameter('out_refund_no', $orderWx['wx_pay_id']);
        $wx->setParameter('total_fee', $data['price_sum']*100);
        $wx->setParameter('refund_fee', $data['price_sum']*100);
        $wx->setParameter('op_user_id', \WxPayConf_pub::MCHID);
        
        $res = $wx->getResult();
        
        $status = $this->returnGoodsByParse($res);
        
        return empty($status) ? null : array('id' => $_GET['idsaw'], 'monery' => $data['price_sum']) ;
        
    }
    
    /**
     * 退货返回数据处理 
     * @param array $array 微信返回的数据
     */
    private function returnGoodsByParse( array $array)
    {
        /**
         *  [return_code] => SUCCESS
            [return_msg] => OK
            [appid] => wx9a0f542d2f65702f
            [mch_id] => 1419955302
            [nonce_str] => 9e3OyfF7mIiBnLp0
            [sign] => 5E736FF4E2F9064ADBA940761BBA5962
            [result_code] => SUCCESS
            [transaction_id] => 4004602001201612274136434738
            [out_trade_no] => wx_201612271048464445312528-896
            [out_refund_no] => wx_201612271048464445312528-896
            [refund_id] => 2004602001201612270685459662
            [refund_channel] => Array
                (
                )
            [refund_fee] => 2
            [coupon_refund_fee] => 0
            [total_fee] => 2
            [cash_fee] => 2
            [coupon_refund_count] => 0
            [cash_refund_fee] => 2 
         */
        if (empty($array) || !is_array($array)) {
            
            return false;
        }
        
        if ($array['return_code'] !== 'SUCCESS' || empty($array['result_code']) || $array['result_code'] === 'FAIL' || empty($array['refund_fee'])) {
            return false;
        }
        
        //如果还有业务逻辑 请重写 或者 写在接口里
        return true;
        
    }
}