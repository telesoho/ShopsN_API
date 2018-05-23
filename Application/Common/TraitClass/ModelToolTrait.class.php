<?php
namespace Common\TraitClass;

/**
 * 模型工具类 
 */
trait ModelToolTrait
{
    /**
     * 验证 数组以及是否为空 
     */
    public function isEmpty (array $post)
    {
        return is_array($post) && (new \ArrayObject($post))->count();
    }
    
    /**
     * id 转换为key 
     */
    public function covertKeyById (array $data, $keyId)
    {
        if (!$this->isEmpty($data) || !is_string($keyId)) {
            return array();
        }
        
        $newValue = array();
        
        foreach ($data as $key => $value) {
            
            if (!array_key_exists($keyId, $value)) {
                continue;
            }
            $newValue[$value[$keyId]] = $value;
        }
        unset($data);
        return $newValue;
    }
    
    /**
     * 获取最下级分类
     */
    private static function flag($data, $forKey)
    {
        $flag = 0;
        foreach ($data[$forKey] as $key => $value) {
            if(!empty($value)) {
                $flag = $value;
                continue;
            }
            unset($data[$forKey][$key]);
        }
        return $flag;
    }
    
    /**
     * 是否 还有下级分类 
     */
    private  function isHaveSon ( &$data, $id)
    {
        if (empty($id)) {
            return ;
        }
       
        $data[$id] = $this->dataClass[$id];
        
        foreach ($this->dataClass as $name => $class)
        {
             if(!empty($id) && $class[static::$fid_d] == $id)
             {  
                 $this->isHaveSon($data, $class[self::$id_d]);
                 
                 $data[$id]['hasSon'] = 1;
             }
        }            
    }
}