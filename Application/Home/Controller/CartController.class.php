<?php
namespace Home\Controller;
use Think\Controller;
use Common\Model\BaseModel;
use Home\Model\GoodsCartModel;
use Home\Model\GoodsModel;
use Home\Model\SpecGoodsPriceModel;
use Common\Tool\Tool;
use Home\Model\GoodsSpecItemModel;
use Home\Model\GoodsSpecModel;
use Common\TraitClass\FrontGoodsTrait;
use Home\Model\GoodsImagesModel;
class CartController extends CommonController {
    
    use FrontGoodsTrait;
    
    //加入购物车
    public function add_cart(){
      if(IS_POST){
           $model=M('goods_cart');
           $user_id=zhong_decrypt(I('post.app_user_id'));
          if(empty($user_id))
              $this->returnMessage(0,'请先登录','');
           $data['user_id']=$user_id;//用户id
           $goods_num=I('post.goods_num');
           $data['goods_num']=I('post.goods_num');//商品数量
           $data['price_new']=I('post.price_new');//套餐价格
           $data['goods_id']=I('post.goods_id');//商品id
           $d_integral=M('goods')
               ->where(
                   array('id'=>$data['goods_id'])
               )
               ->getField('d_integral');
           $data['integral_rebate']=$d_integral;//返利积分
           $data['create_time']=time();//创建时间
           $data['ware_id']=I('post.warehouse_id');
            $find=$model
                ->where(
                    array(
                    'goods_id'=>$data['goods_id'],
                    'user_id'=>$user_id,'is_del'=>0
                     )
                )
                ->find();
           if(empty($find)){//购物车里还没有此商品
                $re=   $model->add($data);
           }else{
               $num['goods_num']=$find['goods_num']+$goods_num;
             $re=  $model
                 ->where(
                     array(
                     'goods_id'=>$data['goods_id'],
                     'user_id'=>$user_id,'is_del'=>0
                     )
                 )
                 ->save($num);
           }
        if($re)
        {
            $this->returnMessage(1,'添加成功',"");
        }else
        {
            $this->returnMessage(0,'添加失败',"");
        }
        }
    }

	//购物车编辑
    public function getChildren(){
        if(IS_POST){
            $cart_model=M('goods_cart');
            $goods_model=M('goods');
            $goods_images_model=M('goods_images');
            $db_goods_spec_item=M('goods_spec_item');
            $db_goods_spec=M('goods_spec');
            $cart_id=I('post.id');
            $cart_goods_id=$cart_model
                ->where(array('id'=>$cart_id))
                ->getField('goods_id');
            $p_id=$goods_model
                ->where(array('id'=>$cart_goods_id))
                ->getField('p_id');
            $join='db_spec_goods_price ON db_spec_goods_price.goods_id=db_goods.id';
            $result=$goods_model
                ->where(array('p_id'=>$p_id))
                ->join($join)
                ->field(
                    'db_goods.id,title,price_member,p_id,stock,db_goods.id as goods_id,key'
                )
                ->select();
            foreach($result as $k=>$vo)
            {
               $fatherId=$vo['p_id'];
               $fatherImg=$goods_images_model
                   ->where(array('goods_id'=>$fatherId))
                   ->getField('pic_url');
               $result[$k]['pic_url']=$fatherImg;
            }
            $key=array_column($result,'key');
            $attr=array();
            foreach($key as $vo){
               $aa=explode('_',$vo);
               foreach($aa as $vc){
                    $attr[]=$vc;
                }
            }
            $aa=array_unique($attr);
            $condition['id']=array('in',$aa);
            $res=$db_goods_spec_item->where($condition)->select();
            $shux=array_column($res,'spec_id');
            $shux=array_unique($shux);
            $cond['id']=array('in',$shux);
            $attrFather=$db_goods_spec
                ->where($cond)
                ->field('id,name,type_id')
                ->select();
            foreach($attrFather as $k=>$vo)
            {
                 foreach($res as $vc)
                 {
                   if($vo['id']==$vc['spec_id'])
                       $attrFather[$k]['children'][]=$vc;
                 }
            }
            $data=array('childrenGoods'=>$result,'spec'=>$attrFather);
            $this->returnMessage(1,'获取成功',$data);
        }
    }
    
	//物车列表
	 public function myCart(){
        if(IS_POST) {
            $user_id = zhong_decrypt(I('post.app_user_id'));
            if(empty($user_id))
                $this->returnMessage(0,'请先登录','');
            $goods_model = M('goods');
            $cart_model = M('goods_cart');
            $spec_goods_price = M('spec_goods_price');
            $goods_spec_item = M('goods_spec_item');
            $goods_images_model = M('goods_images');
            $join = 'db_goods ON db_goods.id=db_goods_cart.goods_id';
            $join1 = 'db_goods_spec ON db_goods_spec.id=db_goods_spec_item.spec_id';
            $field = 'db_goods_cart.id,goods_id,price_new,goods_num,title,price_member,stock,p_id';
            $result = $cart_model
                ->where(
                    array(
                        'db_goods_cart.user_id' => $user_id,
                        'db_goods_cart.is_del'=>0
                    )
                )
                ->join($join)
                ->field($field)
                ->select();
            $this->isEmpty($result);
            foreach ($result as $k => $vo)
            {
                $fatherId = $goods_model
                    ->where(array('id' => $vo['goods_id']))
                    ->getField('p_id');
                $fatherImg = $goods_images_model
                    ->where(array('goods_id' => $fatherId))
                    ->getField('pic_url');
                $result[$k]['pic_url'] = $fatherImg;
                $result[$k]['key'] = $spec_goods_price
                    ->where(array('goods_id' => $vo['goods_id']))
                    ->getField('key');
                $chlidattra = explode("_", $result[$k]['key']);//得到每个子类有的产品属性
                $condition['db_goods_spec_item.id'] = array('in', $chlidattra);
                $chlidattrdetal = $goods_spec_item
                    ->join($join1)
                    ->field(
                        'db_goods_spec_item.id,spec_id,item,name as spec'
                    )
                    ->where($condition)
                    ->select();
                $result[$k]['item'] = $chlidattrdetal;
            }
            $this->returnMessage(1, '获取成功', $result);
        }
    }

    /**
     * 购物车推荐商品
     *
     */
public  function  cart_recommend(){
         $user_id=zhong_decrypt(I('post.app_user_id'));
         $good_cart=M('goods_cart')
             ->where(['user_id'=>$user_id])
             ->field('goods_id')
             ->select();
         $goods_model=M('goods');
         $image_model=M('goods_images');
         foreach($good_cart as $k=>$v)
         {
              $class_id= $goods_model
                  ->where(array('id'=>$v['goods_id']))
                  ->getField('class_id');
                  if(!empty($class_id))
                  {
                      $goods=$goods_model
                          ->where(['class_id'=>$class_id])
                          ->field('id,title,price_market,p_id')
                          ->select();
                  }

             foreach($goods as $k=>$v)
             {
                 $image=$image_model
                     ->where(array('goods_id' => $v['p_id']))
                     ->getField('pic_url');
                 $goods[$k]['pic_url']=$image;
             }
         }
            if(!empty($goods)){
                $this->returnMessage(1,'返回成功',$goods);
            }else{
                $this->returnMessage(0,'暂无数据',"");
            }
}

	//收藏产品
    public function add_collection(){
        if(IS_POST){
            $condition['goods_id']=I('post.goods_id');
			$user_id=zhong_decrypt(I('post.app_user_id'));
            if(empty($user_id))
                $this->returnMessage(0,'请先登录','');
            $condition['user_id']=$user_id;
            $type=I('post.type');
            $collectionModel=M('collection');
            if($type==1)
            {//加入收藏
                $data['goods_id']=I('post.goods_id');
                $data['space_id']=M('spec_goods_price')
                    ->where(array('goods_id'=>$data['goods_id']))
                    ->getField('key');
                $goodsinfo=M('goods')
                    ->where(array('id'=>$data['goods_id']))
                    ->field('title,class_id')
                    ->find();
                $data['goods_id']= $condition['goods_id'];
                $data['goods_name']=$goodsinfo['title'];
                $data['class_id']=$goodsinfo['class_id'];
                $data['user_id']=$user_id;
                $find=$collectionModel
                    ->where($condition)
                    ->find();
                if($find)
                {
                    $this->returnMessage(0,'已加入收藏','');
                }else
                {
                    $data['add_time']=time();
                    $id=$collectionModel->add($data);
                    if($id)
                    {
                        $this->returnMessage(1,'收藏成功','');
                    }else
                    {
                        $this->returnMessage(0,'收藏失败','');
                    }
                }
            }
            if($type==2)
            {
                $collectionModel->where($condition)->delete();
                $this->returnMessage(1,'已取消','');
            }
        }
    }
     //购物车移入收藏夹
    public function muchCollection(){
        if(IS_POST){
        $user_id=zhong_decrypt(I('post.app_user_id'));
            if(empty($user_id))
            $this->returnMessage(0,'请先登录','');
             $collectionModel=M('collection');
             $goods_id=json_decode($_POST['goods_id'],true);
             $condition['goods_id']=array('in',$goods_id);
             $condition['user_id']=$user_id;
             $result=$collectionModel
                 ->where($condition)
                 ->select();
             if(!empty($result))
             {
                 $result_array = array_column($result, 'goods_id');
                 foreach ($result_array as $k => $vo)
                 {
                     //如果收藏夹已存在
                     if (in_array($vo, $goods_id))
                         unset($goods_id[$k]);//销毁重复的收藏id
                 }
             }
            $goodsModel=M('goods');
            $spec_goods_price_model=M('spec_goods_price');
                 if(empty($goods_id))
                 {
                     $this->returnMessage(0,'已收藏过此类商品','');
                 }
                 foreach($goods_id as $vo)
                 {
                     $data['goods_id']=$vo;
                     $data['space_id']=$spec_goods_price_model
                         ->where(array('goods_id'=>$vo))
                         ->getField('key');
                     if(empty($data['space_id']))
                         $data['space_id']=0;
                     $goodsinfo=$goodsModel
                         ->where(array('id'=>$vo))
                         ->field('title,class_id')
                         ->find();
                     $data['goods_name']=$goodsinfo['title'];
                     $data['class_id']=$goodsinfo['class_id'];
                     $data['user_id']=$user_id;
                     $data['add_time']=time();
                     $collectionModel->add($data);
                 }
             $this->returnMessage(1,'收藏成功','');
         }
    }
	 //点击属性获取到不同的产品
    public function addReduce(){
     if(IS_POST){//如果type=1增加 type=2减少
         $type=I('post.type');
         $id=I('post.id');
         $User=M('goods_cart');
         switch($type){
             case 1:
                 $User->where(array('id'=>$id))->setInc('goods_num'); //商品数量加一
                 break;
             case 2:
                 $User->where(array('id'=>$id))->setDec('goods_num'); //商品数量减一
                 break;
         }
         $this->returnMessage(1,'操作成功','');
     }
    }
     //删除
    public function delete(){
        if(IS_POST){
            $id=I('post.id');
            $data['is_del']=1;
            M('goods_cart')->where(array('id'=>$id))->save($data);
            $this->returnMessage(1,'操作成功','');
        }
    }
    //根据传过来的商品id,购物车id,修改数量
    public function editCart(){
      if(IS_POST){
            $cart_id=I('post.cart_id');
            $goods_id=I('post.goods_id');
            $goods_cart_model=M('goods_cart');
			$user_id=zhong_decrypt(I('post.app_user_id'));
            $find=$goods_cart_model
                ->where(
                    array('goods_id'=>$goods_id,'user_id'=>$user_id)
                )
                ->find();
            if(!empty($find)){//已经存在
                $goods_cart_model->where(array('id'=>$goods_id))->delete();
                $goods_cart_model->where(array('id'=>$find['id']))->setInc('goods_num');
            }else {
                $goods_cart_model->where(array('id' => $cart_id))->save(array('goods_id' => $goods_id));
            }
            $this->returnMessage(1,'操作成功','');
        }
    }
}