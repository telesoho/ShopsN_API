<?php
namespace Common\Tool\Extend;

use Common\Tool\Tool;

/**
 * 字符处理帮助类 
 */
class parseString extends Tool
{
    protected $string = null;
    
    public  static $split = ':';
    
    public function __construct($string)
    {
        $this->string = $string;
    }
    
    public function __set($name, $value)
    {
        $this->$name = $value;
    }
    
    public function __get($name)
    {
        return $this->$name;
    }
    
    /**
     * 添加字符串 
     */
    public function addString($string)
    {
        if (empty($string))
        {
            return ;
        }
        
        $this->string = $string;
    }
    
    /**
     * 检测依据符号分割的数据
     * @param array $args  array(',',':')
     * @ 
     */
    public function checkSplit(array $args, $string = null)
    {
        $string = $string === null ? $this->string : $string;
        if (empty($args))
        {
            throw new \ErrorException('无效的参数');
        }
        
        foreach ($args as $key => $value)
        {
             if (false === strpos($string, $value))
             {
                 throw new \ErrorException('未找到这样的分隔符');
             }
        }
       
    }
    
    /**
     * 根据某种格式 分解字符串 
     */
    public function joinString($string = null, array $split = array('douHao' =>',','fenHao' => ':'))
    {
        $string = $string === null ? $this->string : $string;
        
        if (empty($string))
        {
            return null;
        }
        
        $this->checkSplit($split, $string);
        $receive = array();
        return self::splitString($receive, $string);
    }
    /**
     * 5:6,2:3
     * array(
     *   0 => '5:6'//5号商品6件
     *   1 => '2:3'
     * ),
     */
    private static function splitString( & $receive ,$string, $split = ',' )
    {
        if (false !== strpos($string, $split))
        {
            $string = explode($split, $string); 
        }
        
       
        foreach ($string as $key => $value)
        {
            if (false !== strpos($value, self::$split))
            {
                self::splitString($receive, $value, self::$split);
            }
            else 
            {
               $key % 2 ===0 ? $baseParam[] = $value : ($key % 2 === 1 ?   $param[] = $value : false);
            }
        }
        
        if (!empty($baseParam))
        {
            foreach ($baseParam as $keyValue => $valueKey) 
            {
                if (!empty($param[$keyValue]))
                {
                    $keyValue % 2 ===0 ? $receive[$valueKey] = $param[$keyValue] : ($keyValue % 2 === 1 ?   $receive[$valueKey] = $param[$keyValue] : false);
                }
            }
        }
        return $receive;
    }
    /**
     * 字符拼接 
     */
    public function characterJoin(array $goodIds, $setkey = 'goods_id')
    {
        if (empty($goodIds))
        {
            return null;
        }
        $goods = null;
        foreach ($goodIds as $key => $value)
        {
            if (empty($value[$setkey])) {
                continue;
            }
            $goods .= ','.'"'.$value[$setkey].'"';
        }
        $goods = substr($goods, 1);
        
        return $goods;
    }
    
    /**
     * 重组数组 
     */
    public function buildArray(array $data, $setKey ='goods_id')
    {
        if (empty($data))
        {
            return $data;
        }
        foreach ($data as $key => &$value) 
        {
            $setkey = $value[$setKey];
            $data[$setkey] = $value;
            unset($data[$key]);
        }
        
        return $data;
    }
    
    /**
     * 处理两个数组【合并数组根据相同的编号】
     * @param array   $orderComment 订单数组
     * @param array   $goods        商品数组
     * @param string  $setKey       条件
     * @param array   $addKey       要合并的数组的建的值
     * @param boolean $isCover      是否覆蓋
     * @return array  
     */
    public function parseTwoArray(array $orderComment, array $goods, $setKey ='goods_id',array $addKey = array('title'), $isCover = false)
    {
        if (empty($orderComment) || empty($goods))
        {
            return $orderComment;
        }
       
        
        foreach ($orderComment as $key => & $value)
        {
            if ($value[$setKey] == $goods[$key][$setKey])
            {
               
                foreach ($addKey as $name => $byValue)
                {
                    if (!array_key_exists($byValue, $goods[$key]))
                    {
                        continue;
                    }
                    $value[$isCover ? $setKey :$byValue] = $goods[$key][$byValue];
                }
            }
        }
        unset($goods);
        return $orderComment;
    }
    /**
     * 处理多对多数组
     */
    public function oneReflectManyArray(array $oneArray , array $twoMany, $conditionKey ='address_id', array $addKey = array('prov','city,dist','address', 'realname'))
    {
        if (empty($oneArray) || empty($twoMany))
        {
            return $twoMany;
        }
    
        /**
         *后续优化
         */
        foreach ($oneArray as $key => $value)
        {
            foreach ($twoMany as $orm => &$name )
            {
                if ($name[$conditionKey] == $value[$conditionKey])
                {
                    foreach ($addKey as  $byValue)
                    {
                        if (!array_key_exists($byValue, $value))
                        {
                            continue;
                        }
                        $name[$byValue] = $value[$byValue];
                    }
                }
            }
        }
    
        return $twoMany;
    }
}