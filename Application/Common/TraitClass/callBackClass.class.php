<?php
namespace Common\TraitClass;

trait callBackClass
{
    public  function compare ($goodsA, $goodsB) 
    {
        return $goodsA['goods_id'] - $goodsB['goods_id'] <0;
    }
    
    public  function compareSmall ($goodsA, $goodsB)
    {
        return $goodsA['goods_id'] - $goodsB['goods_id'] >0;
    }
    
    
    public  function compareOrder ($goodsA, $goodsB)
    {
        return $goodsA['order_id'] - $goodsB['order_id'] <0;
    }
    
    public  function compareUserId ($goodsA, $goodsB)
    {
        return $goodsA['id'] - $goodsB['id'] <0;
    }
    
}