<?php
namespace Common\Model;

use Think\Model;

class ConfigChildrenModel extends Model
{
    private static $obj;
    
    public static function getInitnation()
    {
        $name = __CLASS__;
        return self::$obj = !(self::$obj instanceof $name) ? new self() : self::$obj;
    }
    
    /**
     * 获取全部数据 
     */
    public function getAllConfig(array $options = NULL)
    {
        return $this->field('type_name')->where($options)->select();
    }
    
}