<?php
namespace Common\Tool\Intface;

interface Picture
{
    /**
     * 删除图片
     * @param string  $data 图片数据
     * @param bool   $isPartten 是否使用正则【编辑器图片】
     * @param string $parttenCondition 正则数组中的建
     * @return bool
     */
    public  function delPicture( $data, $isPartten = false, $parttenCondition = 'imgSrc');
}