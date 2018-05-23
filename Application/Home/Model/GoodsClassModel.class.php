<?php
namespace Home\Model;

use Think\Model;
use Common\Model\BaseModel;
/**
 * @author Administrator
 */

class GoodsClassModel extends BaseModel
{
    private static $obj;

	public static $id_d;

	public static $className_d;

	public static $createTime_d;

	public static $sortNum_d;

	public static $updateTime_d;

	public static $hideStatus_d;

	public static $picUrl_d;

	public static $fid_d;

	public static $type_d;

	public static $shoutui_d;

	public static $isShow_nav_d;

	public static $description_d;

	public static $cssClass_d;



	public static $hotSingle_d;



    
    public static function getInitnation()
    {
        $class = __CLASS__;
        return !(self::$obj instanceof $class) ? self::$obj = new self() : self::$obj;
    }
    
    /**
     * 查询 顶级分类下的子分类 moming
     */
    /* public function select($options = array(), \Think\Model $model, $limit = 5, $forNumber = 0)
     {
         if (!($model instanceof \Think\Model) || !is_object($model)) {
             return null;
         }
         static $flag = 0;
         static $flagData;
         $data = parent::select($options);
         foreach ($data as $key => &$value) {
             if (!empty($value['id']) || $flag < $forNumber) {
                 $flag++;
                 $this->select(array(
                     'where' => array('fid' => $value['id'], 'hide_status' => 0, 'shoutui' => 1),
                     'field' => array('id', 'class_name', 'fid')
                 ), $model, $limit, $forNumber);
             }
             $flagData[$key] = $value;
             $flagData[$key]['product'] = $model->field('title, id, pic_url, price_new, price_old, fanli_jifen')->where('class_fid="'.$value['id'].'" and shangjia = 1')->order('sort_num ASC, create_time DESC,update_time DESC')->limit($limit)->select();
             $flagData[$key]['children'] = parent::select(array('where' => array('fid' => $value['id'], 'hide_status' => 0), 'field' => array('id', 'class_name')));
         }
         if (!empty($flagData)) {
             foreach ($flagData as $key => $value) {
                 if (empty($value['product'])) {
                     unset($flagData[$key]);
                 }
             }
         }
         return $flagData;
     }*/

    /**
     * 获取全部子集分类
     * @param array $where 查询条件
     * @param array $field 查询的字段
     * @return string
     */
    public function getChildren(array $where = null, array $field = null)
    {
        // 根据地区编号  查询  该地区的所有信息
        $video_data   = parent::select(array(
            'where' => $where,
            'field' => $field,
        ));
        $pk    = $this->getPk();
        foreach ($video_data as $key => &$value)
        {
            if(!empty($value[$pk]))
            {
                $data .= ','. $value[$pk];
                $child = $this->getChildren(array('fid' => $value[$pk]), $field);
                if (!empty($child))
                {
                    foreach ($child as $key_value => $value_key)
                    {
                        if (!empty($value_key[$pk]))
                        {
                            $data.=','.$value_key[$pk];
                        }
                    }
                }
                unset($value, $child);
            }
        }
        return !empty($data) ? substr($data , 1) : null;
    }

    public function getProductClass(array $options = array())
    {
        if (!is_array($options) || empty($options))
        {
            return null;
        }

        $resul_class = parent::select($options);

        if (!empty($resul_class))
        {
            foreach($resul_class as $k => &$v){
                $where_sub['fid'] = $v['id'];
                $where_sub['hide_status'] = 0;
                $v['class_sub'] = parent::select(array('where'=> $where_sub));
            }
        }
        return $resul_class;
    }

    /**
     * 获取父及编号
     */
    public function isSameLevel( $id = null)
    {
        if (empty($id)) {return null;}

        //查询我的上级
        $topId = $this->where('id="'.$id.'"')->getField('fid');
        if ($topId != 0)
        {
            return str_replace('0,', null, $this->isSameLevel($topId).','.$topId);
        }
        else
        {
            return $topId;
        }
    }


    /**
     * 查询顶级分类 和当前子分类的数据
     * @param array $options 查询参数
     * @param int   $id      分类编号
     * @return array
     */
    public function classTop(array $options, $id)
    {
        if (empty($options) || !is_array($options))
        {
            return array();
        }
        //顶级分类
        $data = parent::select($options);

        $parentId = $this->where('id="'.intval($id).'"')->getField('fid');

        $children = array();

        if (!empty($parentId))
        {
            $children = parent::select(array(
                'where' => array('fid' => $parentId, 'hide_status' => 0, 'type' => 1),
                'field' => array('id', 'class_name'),
            ));
        }

        //再次查找 子类(根据父类查找子类)【只查一级，如果是多级 ，请调用getChildren】
        if (empty($children))
        {
            $children = parent::select(array(
                'where' => array('fid' => $id, 'hide_status' => 0, 'type' => 1),
                'field' => array('id', 'class_name'),
            ));
        }

        return array('pData' => $data, 'children' => $children);
    }

    public function getChildrens(array $options )
    {
        if (empty($options))
        {
            return array();
        }
        return parent::select($options);
    }

   


    /**
     * 获取所有数据
     * @return mixed
     */
    public function getList($filed="*"){
        return $this->field($filed)->where(['hide_status'=>1])->select();
    }



    /**
     * 获取分类下所有的id
     * @return mixed
     */
    public function selectClass($classData=[],$classId=0)
    {
        $classIds='';

        foreach($classData as $k=>$v){

            if($v['fid']==$classId){
                $classIds.= ','.$v['id'];
                $classIds.= $this->selectClass($classData,$v['id']);
            }
        }

        return $classIds;

    }
    /**
     * 左边商品品牌列表
     * @return mixed
     */
    public function brand($class=0){
        $rsClass=$this->field(['fid,id'])->where(['is_show_nav'=>0])->select();
        $classRs=$this->selectClass($rsClass,$class);
        $a=substr($classRs,1);
        $arr=explode(',',$a);
        array_unshift($arr,$class);
        $where['goods_class_id']=['in',$arr];
        $brands=M('brand')->field('id,brand_name,brand_logo')->where($where)->select();
        return $brands;
    }


    /**
     * 获取导航标题 
     * @param int $classId 商品分类编号
     * @param string $tag  标题标签
     * @return string
     */
    public function getTitleByClassId( $classId, $tag)
    {
        if (!is_numeric($classId) || $classId == 0) {
            return null;
        }
        
        $titleData = $this->field(array(
            self::$id_d,
            self::$className_d,
            self::$fid_d
        ))->where(self::$id_d .'="%s"', $classId)->find();
        
        if (empty($titleData)) {
            return null;
        }
        
        if ($titleData[self::$fid_d] == 0) {
            return '<'.$tag.'>'.$titleData[self::$className_d].'</'.$tag.'>';
        }
        return '<'.$tag.'>'.$this->getTitleByClassId($titleData[self::$fid_d], $tag).'</'.$tag.'>'.' > '.'<'.$tag.'>'.$titleData[self::$className_d].'</'.$tag.'>';
    }

}