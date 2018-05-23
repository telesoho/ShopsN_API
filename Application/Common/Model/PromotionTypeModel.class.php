<?php
namespace Common\Model;

use Common\Model\BaseModel;
use Common\Tool\Tool;

/**
 * 促销类型model
 */
class PromotionTypeModel extends BaseModel
{
    private static $obj;

	public static $id_d;

	public static $promationName_d;

	public static $createTime_d;

	public static $updateTime_d;

	public static $status_d;

    
    public static function getInitnation()
    {
        $name = __CLASS__;
        return self::$obj = !(self::$obj instanceof $name) ? new self() : self::$obj;
    }
    
    public function getTypeData (array $data, $split)
    {
        if (!$this->isEmpty($data) || !is_string($split)) {
            return array();
        }
        
        $idString = Tool::characterJoin($data, $split);
        
        if (empty($idString)) {
            return array();
        }
        
        $typeData = $this->where(self::$id_d .' in ('.$idString.')')->select();
        
        if (empty($typeData)) {
            return array();
        }
        
        foreach ($data as $key => & $value)
        {
            foreach ($typeData as $name => $type) {
                if ($value[$split] !== $type[self::$id_d]) {
                    continue;
                }
                $value[self::$promationName_d] = $type[self::$promationName_d];
                $value['poopStatus'] = $type[self::$status_d];
            }
        }
        unset($typeData);
        return $data;
    }
    
}