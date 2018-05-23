<?php
namespace Common\Tool\Extend;
use Common\Tool\Tool;
use Common\Tool\Intface\TimeInterFace;

/**
 * 事件处理工具 
 */
class Time extends Tool implements TimeInterFace
{
    /**
     * 转换时间格式
     */
    public function parseTime(array $data,$key ='create_time')
    {
        if (empty($data))
        {
            return $data;
        }
        
        foreach ($data as $setkey => &$value)
        {
            if (!empty($value[$key]))
            {
                $value[$key] = date('Y-m-d H:i:s', $value[$key]);
            }
        }
        return $data;
    }
}