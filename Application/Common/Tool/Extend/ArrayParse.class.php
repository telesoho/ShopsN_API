<?php
namespace Common\Tool\Extend;

use Common\Tool\Tool;

/**
 * 数组操作类 
 */
class ArrayParse extends Tool implements \ArrayAccess,\Reflector
{
    private  $array = array();
    protected $children = array();
    
    protected $parseChildren = array();
    
    public static  $childrenClass = null;
    /**
     * {@inheritDoc}
     * @see ArrayAccess::offsetExists()
     */
    public function __construct(array $array)
    {
        $this->array = $array;
    }
    
    public function __set($name, $value)
    {
        if (!isset($this->array[$name]))
        {
            $this->array[$name] = $value;
        }
    }
    
    public function __get($name = null)
    {
        return isset($this->array[$name]) ? $this->array[$name] : $this->array;
    }
    
    public function offsetExists($offset)
    {
        // TODO Auto-generated method stub
        return isset($this->array[$offset]);
    }

    /**
     * {@inheritDoc}
     * @see ArrayAccess::offsetGet()
     */
    public function offsetGet($offset)
    {
        // TODO Auto-generated method stub
        return $this->array[$offset];
    }

    /**
     * {@inheritDoc}
     * @see ArrayAccess::offsetSet()
     */
    public function offsetSet($offset, $value)
    {
        // TODO Auto-generated method stub
        $this->array[$offset] = $value;
    }

    /**
     * {@inheritDoc}
     * @see ArrayAccess::offsetUnset()
     */
    public function offsetUnset($offset)
    {
        // TODO Auto-generated method stub
        unset($this->array[$index]);
    }
    
    /**
     * 组合数据 
     */
    public function buildData(array $data = null)
    {
        $data  = empty($data) ? $this->array : $data;
        
        //阵列变量
        extract($data);
        foreach ($children as $key => &$value)
        {
            
            foreach ($configValue as $config => $nameValue)
            {
                if (array_key_exists($value['type_name'], $nameValue))
                {
                    $value['value'] = $nameValue[$value['type_name']];
                }
            }
        }
        
        foreach ($pData as $key => &$value)
        {
            if ($value['p_id'] == 0)
            {
                foreach ($configValue as $config => $nameValue)
                {
                    if (!in_array($value['id'], $nameValue)) {
                        continue;
                    }
                    $value['parent_key']= $nameValue['parent_key'];
                }
                continue;
            }
            foreach ($children as $type => $name)
            {
                if ($value['id'] == $name['config_class_id'])
                {
                    $value['type_name'] = $name['type_name'];
                    $value['show_type'] = $name['show_type'];
                    $value['type']      = $name['type'];
                    $value['value']     = $name['value'];
                   
                    unset($children[$type]);
                }
            }
        }
        return $pData;
    }
    
    /**
     * 分析系统配 
     */
    public function buildConfig(array $data = null)
    {
        $data  = empty($data) ? $this->array : $data;
        //阵列变量
        extract($data);
        foreach ($children as $key => &$value)
        {
            foreach ($configValue as $config => $nameValue)
            { 
                if (array_key_exists($value['type_name'], $nameValue))
                {
                    $value['value'] = $nameValue[$value['type_name']];
                }
            }
        }
        $this->children = $children;
        return $this;
    }
    
    public function parseConfig()
    {
        if (empty($this->children))
        {
            return $this;
        }
        $data = $this->children;
        foreach ($data as $key => &$value)
        {
            if (isset($value['type_name']))
            {
                $value[$value['type_name']] = $value['value'];
            
                unset($data[$key]['value'], $data[$key]['type_name']);
            }
        }
        $this->parseChildren = $data;
       
        return $this;
    }
    
    public function oneArray( array &$receive, array $data = null)
    {
        $data =  (empty($data)) ? $this->parseChildren : $data;
        
        foreach ($data as $key => $value)
        {
            if (is_array($value))
            {
                $this->oneArray($receive, $value);
            }
            else
            {
                $receive[$key] = $value;
            }
        }
        return $receive;
    }
    
    /**
     * 转换类型 
     * @param  array  $variable 变量数组
     * @param  string $type     要转换的类型
     * @return array 
     */
    public function transformationToType(array & $variable, $type='array')
    {
        //获取自定义变量
        if (empty($variable))
        {
            return $variable;
        }
        foreach ($variable as $key => &$value)
        {
            $value = ($type).$value;
        }
        showData($variable, 1);
        return $variable;
    }
    
    /**
     * 组合数据 
     */
    public function lineArrayData(array $data, array $lineArray, $isExits = 'username',$secondExits = 'goods_title', $isXD ='user_id')
    {
        if (empty($data))
        {
            return array();
        }
        foreach ($data as $value)
        {
            if (!is_array($value) || empty($value))
            {
                return false;
            }
        }
        $lineArray = array_merge($lineArray, $data);
      
        $parseData = self::recursionData($lineArray, $isExits, $secondExits, $isXD);
        return $parseData;
    }
    
    /**
     * 处理数据 
     */
    private static function recursionData(array $data, $isExits = 'username',$secondExits = 'goods_title', $isXD ='user_id' , $id = 10000000)
    {
        $flag = array();
        foreach ($data as $key => $value)
        {   
           $flag = array_key_exists($isExits, $value) ? $value : null;
           !array_key_exists($secondExits, $value) ? array_shift($data) : false;
           foreach ($data as $sKey => $sValue)
           {
               if (!empty($flag) && $flag[$isXD] == $sValue[$isXD])
               {   
                   $data[$sKey][$isExits] = $flag[$isExits];
                   unset($data[$sKey][$isXD]);
               }
           }
        }
        return $data;
    }
    /**
     * {@inheritDoc}
     * @see Serializable::serialize()
     */
    public function serialize()
    {
        // TODO Auto-generated method stub
    }

    /**
     * {@inheritDoc}
     * @see Serializable::unserialize()
     */
    public function unserialize($serialized)
    {
        // TODO Auto-generated method stub
        
    }
    /**
     * {@inheritDoc}
     * @see Reflector::export()
     */
    public static function export()
    {
        // TODO Auto-generated method stub
        
    }

    /**
     * {@inheritDoc}
     * @see Reflector::__toString()
     */
    public function __toString()
    {
        // TODO Auto-generated method stub
        
    }
    
    public function __call($methods, $args = null)
    {
        $obj = new self::$childrenClass();
        
        return  method_exists($obj, $methods) ? call_user_func_array(array($obj, $methods), $args) : E('该类【'.get_class($obj).'】，没有该方法【'.$methods.'】');
    }
    
}