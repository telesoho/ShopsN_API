<?php
namespace Common\Model;
use Think\Model;

/**
 * Class OrderModel
 * @package Common\Model
 */
class OrderModel extends BaseModel
{
    private static $obj;

	public static $id_d;

	public static $orderSn_id_d;

	public static $priceSum_d;

	public static $expressId_d;

	public static $addressId_d;

	public static $userId_d;

	public static $createTime_d;

	public static $deliveryTime_d;

	public static $payTime_d;

	public static $overTime_d;

	public static $orderStatus_d;

	public static $commentStatus_d;

	public static $freightId_d;

	public static $wareId_d;

	public static $payType_d;

	public static $remarks_d;

	public static $status_d;

	public static $translate_d;

	public static $shippingMonery_d;

	public static $expId_d;

	public static $platform_d;

	public static $orderType_d;

	public static $shipping_d;

	public static $integral_d;


    public static function getInitnation()
    {
        $name = __CLASS__;
        return self::$obj = !(self::$obj instanceof $name) ? new self() : self::$obj;
    }
}