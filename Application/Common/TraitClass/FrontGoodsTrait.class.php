<?php
namespace Common\TraitClass;

use Home\Model\FootPrintModel;
use Home\Model\GoodsSpecModel;
use Home\Model\GoodsSpecItemModel;
use Common\Tool\Tool;
use Home\Model\SpecGoodsPriceModel;

trait FrontGoodsTrait
{
    /**
     * 添加收藏
     */
    protected  function addCollection($result)
    {
        if (!empty($_SESSION['user_id']) && !empty($result['goods']))
        {
            //添加我的足迹
            FootPrintModel::getInitation()->add(array(
                'uid'         => $_SESSION['user_id'],
                'gid'         => $_GET['id'],
                //    'goods_pic'   => $result['goods']['pic_url'],
                'goods_price' => $result['goods']['price_member'],
                'goods_name'  => $result['goods']['title'],
                'is_type'     => 1
            ));
        }
    }
    
    /**
     * @desc 规格子父类重组 
     * @param array $spcClassData 规格父类数据
     * @param array $spcItemClassData 规格子类数据
     * @return array
     */
     
    public function recombinationSpec(array $spcClassData, array $spcItemClassData)
    {
        
        if (!is_array($spcClassData) || empty($spcClassData) || !is_array($spcItemClassData) || empty($spcItemClassData) ) {
            return array();
        }
        
        foreach ($spcClassData as $key => & $name) {
            foreach ($spcItemClassData as $itemKey => &$itemValue)
                if ($name[GoodsSpecModel::$id_d] === $itemValue[GoodsSpecItemModel::$specId_d]) {
                    $name['children'][] = $itemValue;
                }
        }
        
        return $spcClassData;
    }
    
    protected function check(array $check, $isArray = true, $isString = false, $isInt = false)
    {
        if (empty($check)) {
            return false;
        }
        
        foreach ($check as $key => & $value) {
            
            if (is_array($value) && empty($value)) {
                
            }
            
        }
    }
    
    /**
     * 拼接规格与规格项 
     */
    public function conmbine(array $spcClassData, array $spcItemClassData)
    {
        if (!is_array($spcClassData) || empty($spcClassData) || !is_array($spcItemClassData) || empty($spcItemClassData) ) {
            return array();
        }
        
        $flag = null;
        
        $buildArray = array();
        
        foreach ($spcClassData as $key => & $name) {
            foreach ($spcItemClassData as $itemKey => &$itemValue) {
                
                if ($name[GoodsSpecModel::$id_d] !== $itemValue[GoodsSpecItemModel::$specId_d]) {
                    continue;
                }
                
                $itemValue['spec'] = $name[GoodsSpecModel::$name_d];
                $buildArray[$itemValue[GoodsSpecItemModel::$id_d]] = $itemValue;
            }
            $flag = null; 
        }
       
        unset($spcClassData, $spcItemClassData);
        return $buildArray;
    }
    
    /**
     *  组装到具体数据
     */
      protected function buildData (array $goodsDetail, array $spec)
      {
          if (!is_array($goodsDetail) || empty($goodsDetail) || !is_array($spec) || empty($spec) ) {
              return array();
          }
          
          $bulidArray = array();
          
          foreach ($spec as $key => & $name) 
          {
              foreach ($goodsDetail as $itemKey => &$itemValue) 
              {
                  if (false !== strpos($itemValue[SpecGoodsPriceModel::$key_d], $name[GoodsSpecItemModel::$id_d])) {
                     
                        $itemValue[GoodsSpecItemModel::$item_d][] = $name;
                  }
              }
          }
          return $goodsDetail;
      }
    /**
     * 处理字符串 
     * @param string $goodsId
     * @return NULL|NULL|string
     */
    public function parseString($goodsId)
    {
        if (empty($goodsId)) {
            return null;
        }
        
        $goodsId =  substr($goodsId, 0, -1);
        
        $array = explode(',', $goodsId);
         
        $productId = null;
        
        $where = array();
        
        foreach ($array as $key => $value) {
            if (false !== strpos($value, $_SESSION['goodsPId']) ) {
                unset($array[$key]);
            } else {
                list($id, $pId) = explode(':', $value);
                $productId .= ','.$id;
            }
        }
        
        return $productId;
    }
  
    /**
     * 重组数据 
     */
    public function buildDataForSpec (array $goods, array $specData, array $specItem)
    {
       $param = func_get_args();

        foreach ($param as $key => $value) {
            if (empty($value) || !is_array($value)) {
                return array();
            }
        }
        
        //线组合规格
        $specData = $this->recombinationSpec($specData, $specItem);

        Tool::showData($specData, 1);
    }
    
}