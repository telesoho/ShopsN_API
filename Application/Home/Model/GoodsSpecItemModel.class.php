<?php
namespace Home\Model;

use Common\Model\BaseModel;

/**
 * 规格数据模型
 * @author 王强
 */
class GoodsSpecItemModel extends BaseModel
{
    private static $obj ;

	public static $id_d;

	public static $specId_d;

	public static $item_d;
    
    
    public static function getInitnation()
    {
        $class = __CLASS__;
        return  self::$obj= !(self::$obj instanceof $class) ? new self() : self::$obj;
    }
}