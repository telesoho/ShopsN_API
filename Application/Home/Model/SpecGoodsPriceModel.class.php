<?php
namespace Home\Model;

use Common\Model\BaseModel;
use Common\Tool\Tool;

class SpecGoodsPriceModel extends BaseModel
{
    private static $obj;
    
    public static $id_d;
    
    public static $goodsId_d;
    
    public static $key_d;
    
    public static $keyName_d;
    
    public static $price_d;
    
    public static $storeCount_d;
    
    public static $barCode_d;
    
    public static $sku_d;
    

	public static $preferential_d;

    
    public static function getInitnation()
    {
        $name = __CLASS__;
        return self::$obj = !(self::$obj instanceof $name) ? new self() : self::$obj;
    }
    
    /**
     * 获取 商品规格  
     */
    public function getSpecByGoods(array $data, $split)
    {
        if (!$this->isEmpty($data) || !is_string($split)) {
            return array();
        }
        
        $idString = Tool::characterJoin($data, $split);
        
        if (empty($idString)) {
            return $data;
        }
        
        $goods = $this->field(array(
            self::$id_d,
            self::$goodsId_d,
            self::$key_d,
            self::$preferential_d,
            self::$price_d
        ))->where(self::$goodsId_d .' in ('.$idString.')')->select();
        
        if (empty($goods)) {
            return $data;
        }
        
    }
}