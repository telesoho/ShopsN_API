<?php 

namespace Home\Model;

//use Common\TraitClass\callBackClass;
//use Common\TraitClass\ModelToolTrait;
use Think\Model;

/**
 * 商品模型
 */
class GoodsModel extends Model
{

   // use callBackClass;
  //  use ModelToolTrait;
//    private static $obj ;
//    public $pageCount;
//
//	public static $id_d;
//
//	public static $brandId_d;
//
//	public static $title_d;
//
//	public static $priceMarket_d;
//
//	public static $priceMember_d;
//
//	public static $stock_d;
//
//	public static $selling_d;
//
//	public static $shelves_d;
//
//	public static $classId_d;
//
//	public static $recommend_d;
//
//	public static $dIntegral_d;
//
//	public static $integralRebate_d;
//
//	public static $code_d;
//
//	public static $top_d;
//
//	public static $minYunfei_d;
//
//	public static $seasonHot_d;
//
//	public static $restrictions_d;
//
//	public static $description_d;
//
//
//	public static $groupBuy_d;
//
//	public static $updateTime_d;
//
//	public static $createTime_d;
//
//	public static $goodsType_d;
//
//	public static $latestPromotion_d;
//
//	public static $sort_d;
//
//	public static $pId_d;
//
//	public static $status_d;
//
//
//	public static $commentMember_d;
//
//	public static $salesSum_d;
//
//	public static $attrType_d;
//
//	public static $extend_d;
//
//	public static $advanceDate_d;
//    public static function getInitnation()
//    {
//        $class = __CLASS__;
//       return  self::$obj= !(self::$obj instanceof $class) ? new self() : self::$obj;
//    }
    /**
     * 根据状态获取首页的信息
     */
    function _getstatus($status,$field,$limit=''){
        try{
            $goods=M('goods')
                ->field($field)
                ->where([
                    'db_goods.status'=>$status,
                    'p_id'=>['NEQ',0]
                ])
                ->limit($limit)
                ->select();

            if(!empty($goods)){
                foreach($goods as $k=>$v){
                $images=M('goods_images')->where(array('goods_id'=>$v['p_id']))->getField('pic_url');
                    $goods[$k]['pic_url']=$images;
                }
                return $goods;
            }
        }catch(Exception $e){
            if(APP_DEBUG){
                var_dump($e->getMessage());die;
            }
        }

    }
    /**
     * 根据商品id获取商品信息及图片
     */
    public function _getGoodsDetail($field,$goods_id=''){
        try{
            $join='db_goods_images ON db_goods.id=db_goods_images.goods_id';
            if($goods_id){
                $goods=M('goods')
                    ->join($join)
                    ->field($field)
                    ->where([
                        'db_goods.id'=>$goods_id
                    ])
                    ->find();
            }else{
                $goods=M('goods')
                    ->join($join)
                    ->field($field)
                    ->limit(4)
                    ->find();
            }

            if(!empty($goods))
                return $goods;
        }catch(Exception $e){
            if(APP_DEBUG){
                var_dump($e->getMessage());die;
            }
        }

    }

    /**
     * 获取商品信息:SPU Standard Product Unit(标准产品单位）
     * @param  int $goods_id 商品ID
     * @return array|Boolean 商品信息
     */
    public function spu($goods_id) {
        $fields = 'id,brand_id,title,price_market,price_member,stock,selling,shelves,class_id,recommend,d_integral,
            integral_rebate,code,top,min_yunfei,season_hot,restrictions,description,group_buy,goods_type,latest_promotion,p_id';
        $goods  = $this->field($fields)->where('id='.$goods_id)->find();
        $goodsSpec=    $this->goodsSpec($goods['goods_type']);
        $imgs   = $this->images($goods_id);
        if (empty($imgs)) {
            $imgs = array();
        }


        // TODO:根据用户信息调整价格
        // 特惠商品需要获取促销信息,最新促销：1表示热卖促销，2表示热卖精选，3表示人气特卖
        $promotion = M('promotion_goods')->join('db_prom_goods ON db_prom_goods.id=db_promotion_goods.goods_id')
            ->where('status=1 AND db_promotion_goods.goods_id='.$goods_id)->select();
        if (!empty($promotion)) {
           $promotion = array();
        }
        $imgs['promotion'] = $promotion;

        $goods['goodsSpec']=$goodsSpec;
        $goods['imgs'] = $imgs;
        return $goods;
    }


    /**
     * 获取商品图片
     * @param  int  $good_id  商品的ID
     * @param  int  $limit    最多获取多少张商品图片,默认 -1 获取所有
     * @return array 图片集
     */
    public function images($good_id, $limit=-1) {
        if (!is_int($limit)) {
            $limit = 1;
        }
        $model = M('goods_images')->where('status=1 AND goods_id='.$good_id)->field('pic_url');
        switch ($limit) {
            case 1:
                $img = $model->find();
                break;
            case -1:
                $img = $model->select();
                break;
            default:
                $img = $model->limit($limit)->select();
                break;
        }
        return $img;
    }


    /**
     * 获取商品属性
     * @param  int $ids 商品规格数组
     * @param  int $class 商品规格数组
     * @return array|Boolean   商品信息
     */
    public function goodsSpec($goods_type) {

        // 获取属性
        $spce = array();
        $specification = M('goods_spec')->alias('s')->join('db_goods_spec_item as i ON s.id=i.`spec_id`')
            ->where('type_id='.$goods_type)->field('s.id as type_id,name,i.id as item_id,item')->select();
        foreach ($specification as $item) {
            $type_id = $item['type_id'];
            if (!empty($spce[$type_id])) {
                unset($item['name']);
                unset($item['type_id']);
                $spce[$type_id]['items'][] = $item;
            } else {
                $spce[$type_id]['type_id'] = $type_id;
                $spce[$type_id]['name']    = $item['name'];
                $spce[$type_id]['items'][] = ['item_id'=>$item['item_id'], 'item'=>$item['item']];
            }
        }
        unset($specification);
        return array_values($spce);
    }

    /**
     * 获取商品库存
     * @param  int 商品ID
     * @return array  库存信息
     */
    public function stock($goods_id,$key) {
        $data = M('spec_goods_price')->alias('p')->where("p.goods_id=$goods_id AND p.key='$key'")
            ->field('price,store_count')->find();
        return $data;
    }


    /**
     * 图文详情
     * @param  int $goods_id 商品ID
     */
    public function description($goods_id) {
        $desc = M('goods_detail')->field('detail')->where("goods_id=$goods_id")->find();
        return $desc;
    }


    /**
     * 获取商品信息详情
     * @param  int $goods_id 商品ID
     * @return array|Boolean   商品信息
     */
    public function detail($goods_id) {
        // 获取商品 SPU
        $goods = $this->spu($goods_id);

        // 获取商品 SKU
        // TODO::获取商品规格
        return $goods;
    }


    /**
     * 用户是否收藏该商品
     * @param  integer $user_id 用户ID
     * @param  integer $goods_id 商品ID
     * @return boolean 收藏状态
     */
    public function collect($user_id, $goods_id) {
        if ($user_id < 1 || $goods_id < 1) {
            return false;
        }
        $data = M('collection')->where("user_id=$user_id AND goods_id=$goods_id")->find();
        return $data['id'] > 0;
    }


    /**
     * 通过 分类ID 获取商品列表,需要获取顶级商品
     * @param  integer $class_id 商品分类ID
     * @param  int $page 当前是第几页
     * @param  int $page 排序类型:1.销量;2.价格; 3.新品 ; 4.默认,数据库排序字段
     * @return array|booble   商品列表
     */
    public function listByclass($class_id, $page = 0, $sort="" ) {
        $where['class_id']  = $class_id;//"class_id=$class_id AND p_id!=0 ";
        $where['p_id']=['gt',0];

        $fields = 'id,title,price_market,sales_sum,price_member,class_id,goods_type,p_id';
        $page_size = C('page_size');
        if (is_integer($page) && $page>0) {
            $limit = sprintf('%d,%d', (($page-1) * $page_size), $page_size);
        } else {
            $page  = 1;
            $limit = sprintf("0,%d",$page_size);
        }
        $model = $this->where($where)->field($fields)->limit($limit);
        switch ($sort) {
            case 1:  //销量由高到低
                $model = $model->order('sales_sum DESC');
                break;
            case 2:  //销量由低到高
                $model = $model->order('sales_sum ASC');
                break;
            case 3:   //价格由高到低
                $model=$model->order('price_market DESC');
                break;
            case 4:  //价格由低到高
                $model = $model->order('price_market ASC');
                break;
            case 5:
                $model = $model->order('sales_sum DESC');
                break;
           // case 2: // 价格,默认降序
             //   if ($sort == 'DESC')
               //     $model = $model->order('price_market DESC');
             //   else if ($sort == 'ASC')
                  //  $model = $model->order('price_market ASC');
               // break;

        }
        $list= $model->group('p_id')->select();

        if (is_array($list)) {
            foreach ($list as &$good) {
                // 获取父产品的图
                $good['pic_url'] = $this->images($good['p_id'], 1)['pic_url'];
                // 获取评论数量
                $good['comment_count'] = D('comment')->count($good['id']);
            }
        }

        // 分页信息
        $sql   = 'select count(1) as num from (SELECT id FROM `db_goods` WHERE ( class_id='.$class_id .' AND p_id!=0  ) GROUP BY p_id) as total';
        $count = $this->query($sql);
        $count = $count[0]['num'];
//        $list['pager'] = array(
//            'page'  => $page,
//            'count' => $count,
//            'more'  => ($page<ceil($count/$page_size)) ? 1 : 0
//        );
        
        return $list;
    }

    /**
     * 获取同类商品
     */
    public function getAllSameGoods($id)
    {
        if (intval($id) == 0) {
            return array();
        }
        
        $data = $this->getAttribute([
            'field' => [
                self::$id_d,
                self::$priceMember_d,
                self::$pId_d,
                self::$stock_d
            ],
            'where' => [
                self::$id_d => $id
            ]
        ], false, 'find');
        
        if (empty($data)) {
            return array();
        }
        
        $allGoods = $this->getAttribute([
            'field' => [
                self::$id_d,
                self::$priceMember_d,
                self::$pId_d,
                self::$stock_d
            ],
            'where' => [
                self::$pId_d => $data[self::$pId_d]
            ]
        ]);
        
        return $allGoods;
        
    }
    
}



