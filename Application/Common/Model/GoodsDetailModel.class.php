<?php
namespace Common\Model;

/**
 * 商品详情model 
 */
class GoodsDetailModel extends BaseModel
{
    public static $id_d;
    
    public static $goodsId_d;
    
    public static $detail_d;
    
    private static  $obj;

	public static $updateTime_d;

    
    public static function getInitnation()
    {
        $name = __CLASS__;
        return self::$obj = !(self::$obj instanceof $name) ? new self() : self::$obj;
    }
    
    protected function _before_insert(& $data, $options)
    {
        $data[self::$updateTime_d] = time();
        
        return $data;
    }
    
    protected function _before_update(& $data, $options)
    {
        $data[self::$updateTime_d] = time();
    
        return $data;
    }
    
}