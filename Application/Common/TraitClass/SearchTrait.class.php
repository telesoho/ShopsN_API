<?php
namespace Common\TraitClass;

use Common\Model\BaseModel;
use Admin\Model\GoodsModel;
use Common\Tool\Tool;
use Admin\Model\GoodsClassModel;
use Admin\Model\BrandModel;
use Admin\Model\CouponModel;

/**
 * 搜索商品
 * @author 王强
 */
trait SearchTrait
{
    public static  $keyName = 'class_name';
    
    public static  $checkFauther = true;
    
    /**
     * 搜索商品
     */
    public function searchGoods()
    {
        //获取商品数据
        $goodsModel = BaseModel::getInstance(GoodsModel::class);
    
        //组装筛选条件
        $static = (new \ReflectionObject($this))->getStaticProperties(); 
       
      
        $where = array();
        if (array_key_exists('configMinStock', $static)) {
          
            $where = array(GoodsModel::$stock_d => array('lt',static::$configMinStock));
        }
        
        Tool::connect("ArrayChildren");
        
        $initWhere = array_merge($where, array($goodsModel::$pId_d => array('gt', 0)));
    
        $where      = array_merge($initWhere, (array)$goodsModel->bulidWhere($_POST));
       
        $goodsData = $goodsModel->getDataByPage(array(
            'field' => array($goodsModel::$id_d, $goodsModel::$title_d, $goodsModel::$priceMember_d, $goodsModel::$stock_d),
            'where' => $where,
            'order' => $goodsModel::$createTime_d.BaseModel::DESC.','.$goodsModel::$updateTime_d.BaseModel::DESC
        ));
        //获取分类
        $goodsClassModel = BaseModel::getInstance(GoodsClassModel::class);
    
        $data = $goodsClassModel->getAttribute(array(
            'field' => array($goodsClassModel::$id_d, $goodsClassModel::$className_d),
            'where' => array($goodsClassModel::$hideStatus_d => 1)
        ));
    
    
        //获取品牌
        $brandModel = BaseModel::getInstance(BrandModel::class);
    
        $brandData = $brandModel->getAttribute(array(
            'field' => array($brandModel::$id_d, $brandModel::$brandName_d),
            'where' => array($brandModel::$recommend_d => 1)
        ));
    
        //设置默认值
        Tool::isSetDefaultValue($_POST, array(
            $goodsModel::$brandId_d => null,
            $goodsModel::$classId_d => null,
            $goodsModel::$title_d   => null
        ));
        
        $this->brandModel = $brandModel;
    
        $this->brandData  = $brandData;
    
        $this->classData = $data;
    
        $this->classModel = GoodsClassModel::class;
        $this->goodsData  = $goodsData;
    
        $this->goodsModel = GoodsModel::class;
    
        return $this->display();
    }
    
    /**
     * 获取分类
     */
    public function getClassByName()
    {
        Tool::checkPost($_POST, (array)null, false, array(self::$keyName)) ? true : $this->ajaxReturnData(null, 0, '参数错误') ;
        $model = BaseModel::getInstance(GoodsClassModel::class);
         
        $where =  array(
            GoodsClassModel::$className_d  => array('like', '%'.$_POST[self::$keyName].'%'),
            GoodsClassModel::$hideStatus_d => 1
        );
         
        if (!empty($_POST['fid']) && self::$checkFauther) {
            $where = array_merge($where, array(GoodsClassModel::$fid_d => $_POST['fid']));
        }
    
        $data = $model->getAttribute(array(
            'field' => array(
                    GoodsClassModel::$id_d,
                    GoodsClassModel::$className_d,
                    GoodsClassModel::$fid_d
                ),
            'where' => $where
            )
        );
        $this->updateClient($data, '操作');
    }
   
    /**
     * 获取 面额代金卷
     */
    public function getCouponModel()
    {
        $model = BaseModel::getInstance(CouponModel::class);
    
        $data  = $model->getAttribute(array(
            'field' => array($model::$id_d, $model::$name_d),
            'where' => array($model::$type_d => 0)
        ));
    
        $this->ajaxReturnData($data);
    } 
}
