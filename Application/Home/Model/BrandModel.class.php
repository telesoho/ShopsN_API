<?php
namespace Home\Model;

use Common\Model\BaseModel;
use Common\Tool\Tool;

class BrandModel extends BaseModel
{
    //主键
    public static $id_d;
    
    //品牌名称
    public static $brandName_d;
    
    //所属商品分类编号
    public static $goodsClassId_d;
    
    //品牌logo
    public static $brandLogo_d;
    
    //品牌简介
    public static $brandDescription_d;
    
    //是否推荐
    public static $recommend_d;
    
    public static $createTime_d;
    
    public static $updateTime_d;
    
    
    private static  $obj;
    
    public static function getInitnation()
    {
        $name = __CLASS__;
        return self::$obj = !(self::$obj instanceof $name) ? new self() : self::$obj;
    }
    
    /**
     * 获取相应状态的商品 
     */
    public function getDataByStatus ($recommend = 1, $limit = 3)
    {
        if (!is_numeric($recommend)|| !is_numeric($limit) ) {
            return array();
        }
        
        return $hotBrandData = $this->getAttribute(array(
            'field' => array(
                BrandModel::$updateTime_d,
                BrandModel::$createTime_d,
            ),
            'where' => array(BrandModel::$recommend_d => $recommend),
            'limit' => $limit
        ), true);
    }
    
    /**
     * 生成 对应的品牌 +首字母 
     */
    public function getBrandBuild ()
    {
        
        $data = S('data');
        
        if (empty($data)) {
        
            $data = $this->getField(self::$id_d.','.self::$brandName_d);
            
            if (empty($data)) {
                return array();
            }
            
            foreach ($data as $key => & $value)
            {
                $value = Tool::getFirstEnglish($value).' '.$value;
            }
            
            S('data', $data, 180);
        }
        return $data;
    }
    
    /**
     * 根据对应的 首字母 寻找 品牌 
     * @param string $english 首字母
     * @param array $receive  接受数组
     * @return array;
     */
    public function getBrandEnglish ($english, array & $receive) 
    {
        
        $data = $this->getBrandBuild();
       
        if (empty($data)) {
            return array();
        }
     
        foreach ($data as $key => $value)
        {
            if (0 === strpos($value, $english)) {
                $receive[$key] = $value;
                
            }
        }
        return $receive;
        
    }
    //根据商品查询对应的品牌
    public function getBrandByData(array $data){
        if (empty($data)) {
            return false;
        }
        foreach ($data['res'] as $key => $value) {
            $where['id'] = $value['brand_id'];//品牌id
            $brand = M('Brand')->field('id,brand_name')->where($where)->find();
            $data['res'][$key]['brand'] = $brand['brand_name'];
        }
        return $data;
    }
    
}
