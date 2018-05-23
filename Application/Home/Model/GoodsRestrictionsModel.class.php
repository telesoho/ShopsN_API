<?php
namespace Home\Model;
use Think\Model;

class GoodsRestrictionsModel extends Model{
    private static $obj ;

    public static function getInitation()
    {
        return self::$obj = !(self::$obj instanceof GoodsModel) ? new self() : self::$obj;
    }
    //根据class_id查询抢购中的商品
    public function restric($num=4){
        parent::limit($num)->select();
    }
}