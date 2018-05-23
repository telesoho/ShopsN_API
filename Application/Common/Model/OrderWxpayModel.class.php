<?php
namespace Common\Model;

use Think\Model;

/**
 * 微信支付 凭据模型 
 */
class OrderWxpayModel extends BaseModel
{
    private static $obj ;

	public static $id_d;

	public static $orderId_d;

	public static $wxPay_id_d;

	public static $status_d;

	public static $type_d;

    
    public static function getInitnation()
    {
        $class = __CLASS__;
        return self::$obj = !(self::$obj instanceof $class) ? new self() : self::$obj;
    }
    
    protected function _before_insert(&$data, $options)
    {
        $data['status']  = 0;
        return $data;
    }
    
    
    public function add($data='', $options=array(), $replace=false)
    {
        if (empty($data))
        {
            return false;
        }
        $data = $this->create($data);
        return parent::add($data, $options, $replace);
    }
    /**
     * 获取凭据 
     */
    public function getOrderWx($id, $where = ' and status = 1 ')
    {
        if (!is_numeric($id)) {
            return array();
        }
        return $this->field('order_id, wx_pay_id')->where('order_id = "%s" '.$where, $id)->find();
    }
    /**
     * 失败更新支付码 
     */
    public function alipayError($id, array $data)
    {
        if (!is_numeric($id) || !is_array($data) || empty($data['order_id'])) {
            return array();
        }
        
        $isHave = $this->getOrderWx($id, null);
        
        $status = false;
        if (empty($isHave)) {
            
            $status = $this->add($data);
        } else {
            $status = $this->where('order_id = "%s"', $data['order_id'])->save($data);
        }
        
        return $status;
    }
}