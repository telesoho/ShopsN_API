<?php
namespace Common\Model;

use Common\Tool\Tool;
use Common\TraitClass\ModelToolTrait;

/**
 * 地址模型 
 */
class RegionModel extends BaseModel
{
    use ModelToolTrait;
    private static $obj;

	public static $id_d;

	public static $parentid_d;
	
	public static $name_d;

	public static $type_d;

	public static $displayorder_d;

    
    public static function getInitnation()
    {
        $class = __CLASS__;
        return  self::$obj= !(self::$obj instanceof $class) ? new self() : self::$obj;
    }
    
    /**
     * 获取数据 并添加标识 
     */
    public function getContent($id)
    {
        if (!is_numeric($id)) {
            return array();
        }
        
        // 此处 可显示出 与数据库字段 的无关性【
        
        $data      = $this->getAttribute(array(
            'field' => array(self::$displayorder_d),
            'where' => array(
               self::$parentid_d => $_POST['id']
            )
        ), true);
        
        if (empty($data)) {
            return array();
        }
        foreach ($data as $key => & $value) {
            if (empty($value[self::$name_d])) {
                continue;
            }
            $value[self::$name_d] = Tool::getFirstEnglish($value[self::$name_d]).' '. $value[self::$name_d];
        }
        return $data;
    }
    /**
     * 获取地址 
     */
    public function getArea (array $data, $split)
    {
        if (!$this->isEmpty($data) || !is_string($split)) {
            return array();
        }
        
        $idString = Tool::characterJoin($data, $split);
        
        if (empty($idString)) {
            return $data;
        }
        $area = $this->field(self::$id_d.','.self::$name_d.','.self::$parentid_d)->where(self::$id_d .' in('.$idString.')')->select();
        if (empty($area)) {
            return $data;
        }
        $area = self::getLevelTop($area, self::$parentid_d);
        
        // 纠正 编号
        foreach ($area as $key => &$value) {
            foreach ($data as $name => & $stock) {
                if ($key === $name) {
                    $value[self::$id_d] = $stock[$split];
                }
                   
            }
        }
        
        foreach ($area as $id => $address) {
            foreach ($data as $name => & $stock) {
                if ($address[self::$id_d] === $stock[$split]){
                    $stock[self::$name_d] = $address[self::$name_d];
                }
                
            }
        }
        return $data;
    }
    
    /**
     * 获取 上级地区
     */
    private  function getLevelTop ($area, $split)
    {
        static $level;
        $idString = Tool::characterJoin($area, $split);
        
        if (empty($idString)) {
            return $area;
        }
        
        $data = $this->field(self::$id_d.','.self::$name_d.','.self::$parentid_d)->where(self::$id_d .' in('.$idString.')')->select();
       
        if (empty($data)) {
            return $area;
        }
        $flag ='';
        foreach  ($data as $key => $value) {
            
            foreach ($area as $address =>  & $detail) {
                if ($value[self::$id_d] !== $detail[self::$parentid_d]) {
                   continue;
                }
                //保证下一次循环 值还在
                $data[$key][self::$name_d] .='-'. $detail[self::$name_d];
                
                $flag .= $value[self::$name_d].'-'.$detail[self::$name_d].'-';
                
                $detail[self::$name_d] = substr($flag, 0, -1);
                
                $level[$address] =$detail; 
                
                $flag = null;
            }
        }
        foreach ($data as $key => $value) {
            if ($value[self::$parentid_d] == 0 || empty($value)) {
                return $level;
            } else {
               return $this->getLevelTop($data, $split);
            }
        }
        return $level;
    }

    /**
     * 获取地址
     * @return array
     */
    function getRegionList(){
        $res = S('getRegionList');
        if(!empty($res))
            return $res;
        $parent_region = M('region')->field('id,name')->where(array('parentid'=>0))->cache(true)->select();
        $ip_location = array();
        $city_location = array();
        foreach($parent_region as $key=>$val){
            $c = M('region')->field('id,name')->where(array('parentid'=>$parent_region[$key]['id']))->order('id asc')->cache(true)->select();
            $ip_location[$parent_region[$key]['name']] = array('id'=>$parent_region[$key]['id'],'root'=>0,'djd'=>1,'c'=>$c[0]['id']);
            $city_location[$parent_region[$key]['id']] = $c;
        }
        $res = array(
            'ip_location'=>$ip_location,
            'city_location'=>$city_location
        );
        S('getRegionList',$res);
        return $res;
    }
    /**
     * 获取省份
     */
    function getprovince(){
        $region=M('region');
        $province=$region->field('id,name')->where('parentid=0')->select();
        return $province;
    }



    /**
     * 由高德返回的城市名称查找该地址在数据库中的id值
     */
    function getNameById($areaName){
        $data=$areaName['province'];
        $area['province']=$this->field('id')->where('name="%s"', $data)->find();

        return $area;
    }


    /**
     * 寻找Region_id的父级id
     * @param $cid
     * @return array
     */
    function getParentRegionList($cid){
        //$pids = '';
        $pids = array();
        $parent_id =  M('region')->where(array('id'=>$cid))->getField('parentid');
        if($parent_id != 0){
            //$pids .= $parent_id;
            array_push($pids,$parent_id);
            $npids = $this->getParentRegionList($parent_id);
            if(!empty($npids)){
                //$pids .= ','.$npids;
                $pids = array_merge($pids,$npids);
            }

        }
        return $pids;
    }
}