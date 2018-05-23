<?php
namespace Common\Model;
use Think\Model;
use Common\Tool\Tool;

// +----------------------------------------------------------------------
// | 订单数量模型
// +----------------------------------------------------------------------
// | Another ：王强
// +----------------------------------------------------------------------

class OrderGoodsModel extends BaseModel
{
    private static $obj;

	public static $id_d;

	public static $orderId_d;

	public static $goodsId_d;

	public static $goodsNum_d;

	public static $goodsPrice_d;


	public static $status_d;


	public static $spaceId_d;


	public static $userId_d;

	public static $comment_d;

	public static $over_d;

	public static $wareId_d;

    
    public static function getInitnation()
    {
        $name = __CLASS__;
        return self::$obj = !(self::$obj instanceof $name) ? new self() : self::$obj;
    }
    /**
     * 根据订单编号查询商品编号  
     */
    public function getGoodsIdByOrderId($orderId, $field = 'goods_id')
    {
        if (empty($orderId) || !is_numeric($orderId))
        {
            return array();
        }
        
        return $this->field($field)->where('order_id = %s', $orderId)->select();
    }
    
    /**
     * {@inheritDoc}
     * @see \Think\Model::add()
     */
    
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
     * 根据父类表信息查询数据 ，传递给商品表 
     */
    public function getGoodsInfoByOrder(array $data)
    {
        if (empty($data))
        {
            return array();
        }
        
        //整合编号
        $orderIds = Tool::characterJoin($data, 'order_id');
       
        $orderGoods = $this->field('order_id,goods_id,goods_num')->where('order_id in ('.$orderIds.')')->order('order_id DESC')->select();
       
        if (empty($orderGoods))
        {
            return array();
        }
        
        $parseOrder = array();
        
        foreach ($orderGoods as $value)
        {
            if (!isset($parseOrder[$value['order_id']]))
            {
                $parseOrder[$value['order_id']] = $value;
            }
            else
            {
                if (strpos($parseOrder[$value['order_id']]['goods_id'], ',') === false)
                {
                    $goodsId = $parseOrder[$value['order_id']]['goods_id'];
                }
                $parseOrder[$value['order_id']]['goods_id'] .= ','.$value['goods_id'];
                $parseOrder[$value['order_id']]['goods_num'] .= ','.$value['goods_id'].':'.$value['goods_num'];
            }
        }
        
        foreach ($parseOrder as $key => &$value)
        {
            if (strpos($value['goods_id'], ',') !== false)
            {
                $id = $value['goods_num']; 
                
                $newId = $goodsId.':'.$id;
                
                $value['goods_num'] = $newId;
            }
        }
        return $parseOrder;
    }
    
    /**
     * 获取商品编号 
     */
    public function getGoodsId($data, $field, $filter = FALSE)
    {
        if (empty($data['id']) || empty($field))
        {
            return array();
        }
        //整合编号
        return $orderGoods = $this->field($field, $filter)->where('order_id in ('.$data['id'].')')->order('order_id DESC')->select();
    }
    
    /**
     * 删除用户的购买记录 
     */
    public function deleteOrderGoodsByUserId(array $order, $id)
    {
        if (empty($order) || !is_array($order) ||empty($id)) {
            return false;
        }
        
        $ids = Tool::characterJoin($order, $id);
        
        if (empty($ids)) {
            return false;
        }
        
        return $this->delete(array(
                'where' => array(self::$orderId_d => array('in', $ids))
        ));
    }
}