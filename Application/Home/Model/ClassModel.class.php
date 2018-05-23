<?php 

namespace Home\Model;

use Think\Model;
use Think\Think;

/**
 * 分类模型
 */
class ClassModel extends Model {

    protected $tableName = 'goods_class'; 

    /**
     * 获取分类产品
     * @param  integer $fid   商品父ID
     * @param  array   $fields 字段
     * @param  string  $limit  分页
     * @return string | array  错误说明或者正确数据数据
     */
    public function getClassList($fid = -1, $fields = array(), $limit = '0,10') {
        
        if ($fid === -1) { // 顶级商品分类
            $where = 'fid=0 AND hide_status=1';

        } elseif (is_integer($fid)) { // 更具父id获取子分类列表
            $where = 'fid='. $fid .' AND hide_status=1';

        } elseif (is_array($fid) && (count($fid) > 0)) { // 查询多个分类的子分类
            $where = 'fid in (' . implode(',', $fid).') AND hide_status=1';

        } else { //不存在这个分类 
            return '参数错误';
        }

        if (empty($fields)) {
            $fields = array('id', 'class_name', 'sort_num', 'pic_url', 'fid');
        }

        $model = $this->where($where)->field($fields)->limit($limit);
        return $model->order('sort_num DESC')->select();
    }

    /**
     * 根据分类获取商品详情
     */
    public function _getcategory($fid,$limit='',$page='',$flag='') {
        if ($fid < 1) {
           return false;
        }
        // 获取二级分类标题
        $fileds = array('id', 'class_name', 'fid');
        $cate   = $this->getClassList($fid, $fileds, '');
        if (is_array($cate)) {
            $data = array();
            foreach ($cate as $key => $value) {
                $data[$value['id']] = $value;
                $data[$value['id']]['child'] = array();
            }
            unset($cate);
        }

        // 获取二级分类下的小分类
        $list   =$this->getClassList(array_keys($data), array(), '');
        if (is_array($list) && count($list) > 0) {
            foreach ($list as $key => $child) {
                $data[$child['fid']]['child'][] = $child;
            }
            unset($list);
        }
        $ids='';
        foreach($data as $v){
            $ids.=$v['id'].',';
            foreach($v as $v1){
                foreach($v1 as $v2){
                    $ids.=$v2['id'].',';
                }

            }
        }

        $ids=trim($ids,',');
        $goods_id=explode(',',$ids);
        $where['class_id']=array('in',$goods_id);
        $where['p_id']=array('neq',0);
        if(!empty($flag)) {
            switch ($flag) {
                case 1:  //销量由高到低
                    $order = 'sales_sum DESC';
                    break;
                case 2:  //销量由低到高
                    $order = 'sales_sum ASC';
                    break;
                case 3:   //价格由高到低
                    $order = 'price_market DESC';
                    break;
                case 4:  //价格由低到高
                    $order = 'price_market ASC';
                    break;
                case 5:
                    $order = 'sales_sum DESC';
                    break;
            }
        }

        if(!empty($limit)){
            $goods= M('goods')
                ->where($where)
                ->field('id,p_id,title,price_market')
                ->limit($limit)
                ->order($order)
                ->select();
        }else{
            $goods= M('goods')
                ->where($where)
                ->field('id,p_id,title,price_market')
                ->page($page,10)
                ->order($order)
                ->select();
        }
       $goods_images= M('goods_images');
      foreach($goods as $k=>$v){
          $img=$goods_images->where(['goods_id'=>$v['p_id']])->getField('pic_url');
          $goods[$k]['pic_url']=$img;
      }
       return $goods;
    }
}