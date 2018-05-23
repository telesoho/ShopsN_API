<?php
namespace Common\Tool\Extend;

use Common\Tool\Tool;

/**
 * 随机类 
 */
class PassMiMi extends Tool
{
    //随机生成 6 位数字短信码
    public  function getSmsCode($length=6){
        return str_pad(mt_rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }
}