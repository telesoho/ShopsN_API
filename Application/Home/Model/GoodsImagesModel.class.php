<?php
namespace Home\Model;

use Common\Model\BaseModel;
use Common\Tool\Tool;

class GoodsImagesModel extends BaseModel
{
    private static $obj;
    

	public static $id_d;

	public static $goodsId_d;

	public static $picUrl_d;

	public static $status_d;

    
    public static function getInitnation()
    {
        $class = __CLASS__;
        return !(self::$obj instanceof $class) ? self::$obj = new self() : self::$obj;
    }

    /**
     * 商品相册
     */
    public  function getGoodsPictureAlbum($id)
    {
        if (!is_numeric($id) || $id == 0) {
            return array();
        }
    
        $data  = $this->getAttribute(array(
            'field' => array(self::$goodsId_d, GoodsImagesModel::$picUrl_d, self::$id_d),
            'where' => array(self::$goodsId_d => $id, self::$status_d => 1)
        ));
        return $data;
    }
    
    /**
     * 商品图片 
     */
    public function getPicture($data, $split, BaseModel $model)
    {
        if (!$this->isEmpty($data) || !is_string($split) || !($model instanceof BaseModel)) {
            return array();
        }
        
        $idString = Tool::characterJoin($data, $split);
        
        if (empty($idString)) {
            return $data;
        }
        
        $picture = $this->field(array(
            self::$picUrl_d,
            self::$goodsId_d
        ))->where(self::$goodsId_d.' in ('.$idString.')')->group(self::$goodsId_d)->order('rand()')->select();
      
        if (empty($picture)) {
            return $data;
        }

        foreach ($data as $key => &$value) {
            
            foreach ($picture as $name => $pic) {
            
                if ( $value[$model::$pId_d] !== $pic[self::$goodsId_d]) {
                    continue;
                }
                
                $value[self::$picUrl_d] = $pic[self::$picUrl_d];
                
            }
            
        }
        
        return $data;
        
    }
    
    /** 
     * @desc 热卖推荐
     * @param array $data
     * @param string $splitKey
     * @param array|string $field
     * @param string $where
     * @return array;
     */
    public function hotRecommendation (array $data, $splitKey,  $field, $where) 
    {
        if (!is_array($data) || !$data || !is_string($splitKey)) {
            return array();
        }
        
        
        $length   = count($data);
        
        $noImages = array();
        if ($length > 3 ) {
            
            $noImages = array_splice($data, 2);
        } 
        
        
        $data = $this->getDataByOtherModel($data, $splitKey, $field, $where, self::$goodsId_d);
        
        return array_merge($data, $noImages);
    }
}