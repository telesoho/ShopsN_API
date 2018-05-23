<?php
namespace Home\Model;

use Common\Model\BaseModel;

/**
 * 商品规格
 * @author Administrator
 */

class GoodsSpecModel extends BaseModel
{
    private static $obj;
    
    public static $id_d;
    
    public static $typeId_d;
    
    public static $name_d;
    
    public static $sort_d;
    
    public static $status_d;
    
    public static $createTime_d;
    
    public static $updateTime_d;
    
    
    public static function getInitnation()
    {
        $name = __CLASS__;
        return self::$obj = !(self::$obj instanceof $name) ? new self() : self::$obj;
    }
}