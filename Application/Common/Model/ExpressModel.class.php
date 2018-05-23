<?php
namespace Common\Model;

/**
 * 快递公司模型 
 */
class ExpressModel extends BaseModel
{
    private static $obj;

	public static $id_d;

	public static $name_d;

	public static $status_d;

	public static $code_d;

	public static $letter_d;

	public static $order_d;

	public static $url_d;

	public static $ztState_d;

    
    public static function getInitnation()
    {
        $name = __CLASS__;
        return self::$obj = !(self::$obj instanceof $name) ? new self() : self::$obj;
    }
}