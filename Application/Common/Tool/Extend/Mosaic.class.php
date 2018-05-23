<?php
namespace Common\Tool\Extend;
use Common\Tool\Tool;

/**
 * 拼接工具类 
 */
class Mosaic extends Tool
{
    /**
     * 实现拼接 【适用于一位数组拼接】后续完善
     */
    public function MosaicPath(array $data, $path=null)
    {
        if (empty($data))
        {
            return $data;
        }
        $defaultPath = C('IMG_ROOT_PATH');
        $path = empty($path) ? $defaultPath :$path;
        foreach ($data as $key => &$value)
        {
            $value = false === strpos($value, $defaultPath) ? $path.$value : $value;
        }
        return $data;
    }
    
    /**
     * 拼接字符 
     */
    public function join(array $array, $parseKey = 'children', $join = 'goods_class_id')
    {
        if (empty($array))
        {
            return array();
        }
        $receive = array();
        
        if (array_key_exists($parseKey, $array))
        {
             $receive       = !empty($receive) ? array() : array();
             
             $array[$join]  .=  ','.implode(',', (array)$this->strrtrArray($array[$parseKey], $receive));
            
             unset($array[$parseKey]);
        }
        else 
        {
            foreach ($array as $key => &$value)
            {
                if (array_key_exists($parseKey, $value) && is_array($value))
                {
                   $receive = !empty($receive) ? array() : array();
                   $value[$parseKey] = implode(',', (array)$this->strrtrArray($value[$parseKey], $receive));
                   $value[$join] .= ','.$value[$parseKey];
                   unset($value[$parseKey]);
                }
            }
        }
        return $array;
    }
    
    public function strrtrArray(array $data, array & $receive)
    {
        if (empty($data))
        {
            return array();
        }
        
        foreach ($data as $key => $value)
        {
            if (is_array($value))
            {
                $this->strrtrArray($value, $receive);
            }
            else 
            {
                $receive[] = $value;
            }
        }
        return $receive;
    }
    
    
    public  function parseToArray(array $data)
    {
        if (empty($data))
        {
            return array();
        }
        
        static $flag = array();
        
        foreach ($data as $key => $value)
        {
             is_array($value)?   self::parseToArray($value) : $flag[$key]= $value;
        }
        return $flag;
    }
    
    /**
     * 获取数组维度 
     */
    public  function array_depth( array $array) 
    {
        if(empty($array)) return 0;
        $max_depth = 1;
        foreach ($array as $value) {
            if (is_array($value)) {
                $depth = $this->array_depth($value) + 1;
                if ($depth > $max_depth) {
                    $max_depth = $depth;
                }
            }
        }
        return $max_depth;
    }
    

    //短信发送
    public function requestSms($url, array $data)
    {
        $args = func_get_args();
        if (empty($args))
        {
            return false;
        }
        $data = http_build_query($data);
        $opts = array (
            'http' => array (
                'method' => 'POST',
                'header'=> "Content-type: application/x-www-form-urlencoded" .
                "Content-Length: " . strlen($data) . "",
                'content' => $data ,
            ),
        );
        $context = stream_context_create($opts);
        $html = file_get_contents($url, false, $context);
       
        return empty($html)? false : substr($html,0,1);
    }
    
    /**
     * 短信接口 post 
     */
    
    public function requestPostSms($url,  $param)
    {
        if (empty($url) || empty($param)) {
            return false;
        }
        
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_TIMEOUT,60);
        curl_setopt($ch,CURLOPT_HEADER,0);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_POST,count($param)) ;
        curl_setopt($ch,CURLOPT_POSTFIELDS,$param);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
    
    /**
     * 添加值 
     */
    public function addValue(array $goods , array $intral)
    {
        if (empty($goods) || empty($intral))
        {
            return $goods;
        }
        while (list($key, $value) = each($intral))
        {
            $goods[$key] = $value;
        }
        unset($intral);
        return $goods;
    }
}