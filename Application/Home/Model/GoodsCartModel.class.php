<?php
namespace  Home\Model;

use Common\Model\BaseModel;

/**
 * 购物车 模型 
 */
class GoodsCartModel extends BaseModel
{
    
    private static $obj;
    

	public static $id_d;

	public static $goodsId_d;

	public static $userId_d;

	public static $goodsNum_d;

	public static $attributeId_d;

	public static $priceNew_d;

	public static $integralRebate_d;

	public static $updateTime_d;

	public static $createTime_d;

	public static $isDel_d;

    
    public static function getInitnation()
    {
        $class = __CLASS__;
        return !(self::$obj instanceof $class) ? self::$obj = new self() : self::$obj;
    }
    
    // 添加购物车
    public function addCart(array $data)
    {
        
        if (empty($data) || !is_array($data))
        {
            return array();
        }
        $result = $this-> getAttribute(array(
            'field' => array(
                self::$id_d,
                self::$goodsNum_d
            ),
            'where' => array(
                self::$userId_d => $_SESSION['user_id'],
                self::$goodsId_d  => $data[self::$goodsId_d]
            )
        ), false, 'find');
       
        //购物车中无商品，添加一条新信息，购物车中已有信息，则数量 +1
        $id = 0;
       
        $data[self::$userId_d] = $_SESSION['user_id'];
      
        if(empty($result)){
            $id   = $this->add($data);
        }else{
            $data[self::$goodsNum_d] = $result[self::$goodsNum_d] + $data[self::$goodsNum_d];
            
            $id = $this->where(self::$id_d.'="'.$result[self::$id_d].'"')->save($data);
        }
        return empty($id) ? false : true;
    }
    
    protected function _before_insert(& $data, $options)
    {
        $data[self::$updateTime_d] = time();
        $data[self::$createTime_d] = time();
        return $data;
    }
    
    protected function _before_update(& $data, $options)
    {
        $data[self::$updateTime_d] = time();
        return $data;
    }
    
    
    /**
     * 获取购物车数量 
     */
    public function getCartCount(array $options)
    {
       $isSuccess =  \Common\Tool\Tool::checkPost($options);
       
       if (!$isSuccess) {
           return false;
       }
       
       $count = $this->where($options)->count();
       
       return $count;
    }


    /**
     * 获取最后添加的一个商品
     */
    public function getLastGoods(array $options, BaseModel $model)
    {
        $isSuccess =  \Common\Tool\Tool::checkPost($options);
        if (!$isSuccess || !($model instanceof BaseModel)) {
            return false;
        }
        
        $data =  $this->find($options);
       
        if (!empty($data)) {
           $goods = $model->field('title,description')->where('id = "'.$data['goods_id'].'"')->find();
           $data = array_merge((array)$goods, $data);
        }
        return $data;
    }
   

    /**
     * 获取购物车商品
     * @param  int    $user_id 用ID
     * @param  int    $del 是否删除
     * @return array
     */
    public function getCartGoods(int $user_id, $del = 0, $limit = '0,10')
    {
        $field = 'c.id as cart_id ,c.goods_id, g.title, g.price_market, g.price_member, g.stock, g.min_yunfei,c.goods_num,g.status';

        $data = $this->alias('c')->join('__GOODS__ as g ON g.id=c.goods_id')->where(['c.user_id'=>$user_id, 'c.is_del'=>$del])
            ->field($field)->order('c.create_time DESC')->select();
        return $data;
    }
} 