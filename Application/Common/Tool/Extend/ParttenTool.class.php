<?php
namespace Common\Tool\Extend;

use Common\Tool\Tool;
use Think\Exception;

class ParttenTool extends Tool 
{
    protected $parrten = array(
        'mobile' => '/^(1(([35][0-9])|(47)|[8][01236789]))\d{8}$/',
    ) ;  
    //验证手机号
    
    public function __construct($parrten, $args = null, array $options = null)
    {
        $this->parrten = empty($options) ? $this->parrten : array_merge($this->parrten, $options);
    }
    
    public function addPartten($name, $value)
    {
        $this->parrten[$name] = $value;
    }
    
    public function validateData($data, $key ='idCard')
    {
        if (empty($this->parrten[$key]))
        {
           throw new \Exception('没有待验证'.$key.'的正则表达式，请添加', 500, null);
        }
        return (preg_match($this->parrten[$key],$data)) ? true : false;
    }
}