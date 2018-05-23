<?php
namespace Common\Model;

/**
 * 支付方式
 */
class PayModel extends BaseModel
{
    private static $obj;



	public static $id_d;

	public static $payType_id_d;

	public static $payAccount_d;

	public static $mchid_d;

	public static $payKey_d;

	public static $openId_d;

	public static $createTime_d;

	public static $updateTime_d;

	public static $payName_d;

	public static $returnName_d;

	public static $type_d;

	public static $privatePem_d;

	public static $publicPem_d;

    
    public static function getInitnation()
    {
        $name = __CLASS__;
        return self::$obj = !(self::$obj instanceof $name) ? new self() : self::$obj;
    }
}