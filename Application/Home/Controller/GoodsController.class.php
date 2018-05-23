<?php 

namespace Home\Controller;

use Think\Controller;
use Think\Exception;

/**
 * 商品控制器
 */
class GoodsController extends CommonController {

    /**
     * 获取商品详情
     * @param  int  获取商品图文信息
     * @return array 获取商品信息详情
     */
    public function detail() {
        $gid  = I('get.gid', -1, 'intval');
        $data = D('goods')->detail($gid);
        if (count($data) > 0)
        {
            $data['description_url'] = $_SERVER['HTTP_HOST'].U('/Home/Goods/description?gid='.$gid);
            $user_id = zhong_decrypt(I('post.app_user_id'));
            $data['is_follow'] = D('goods')->collect($user_id, $gid)?1:0;
        }
        if (is_array($data))
        {
           $this->returnMessage(1,'成功', $data);
        }
        $this->returnMessage(0, '失败', array());
    }


    /**
     * 根据分类获取商品列表
     * @param  int $class_id 分类id
     * @return array  一类商品的列表
     */
    public function listByclass() {
        $page     = I('get.page', -1, 'intval');
        $class_id =I('get.class_id', -1, 'intval');
        //$order    = 1;//I('get.order', -1, 'intval');
        $sort    =I('get.sort', 'DESC');
        $data     = D('goods')->listByclass($class_id, $page, $sort);
        if (is_array($data)) {
           $this->returnMessage(1,'成功', $data);
        }
        $this->returnMessage(0, '失败', array());
    }


    /**
     * 获取商品的图文描述
     * @param  int  获取商品图文信息
     * @return array  商品图文信息
     */
    public function description() {

        $gid  = I('get.gid', -1, 'intval');
        $data = D('goods')->description($gid);
        echo $data['detail'];
    }


    /**
     * 根据商品ID,属性键获取  库存,单价,会员价
     * @param  int $gid 商品ID
     * @param  string $key 属性键值
     * @return array      返回数据
     */
    public function stock() {

        $gid  = I('get.gid', -1, 'intval');
        $key  = I('get.key', -1);
        $data = D('goods')->stock($gid, $key);
        if (is_array($data))
        {
           $this->returnMessage(1,'成功', $data);
        }
        $this->returnMessage(0, '失败', array());
    }

	 public function goods(){
        if(IS_GET){//获取商品详情
            $goods_id=I('get.goods_id');
            $user_id=zhong_decrypt(I('get.app_user_id'));
            $this->isEmpty($goods_id);
            $type=I('get.type');
            $goodsModel=M('goods');
            if($type=='Android')
            {
               $foot['uid']=$user_id;
               $foot['gid']=$goods_id;
               $goods=$goodsModel
                   ->where(['id'=>$goods_id])
                   ->field('title,price_market')
                   ->find();
               $foot['goods_name']=$goods['title'];
               $foot['goods_price']=$goods['price_market'];
               $foot['create_time']=time();
               $foot['is_type']=1;
               M('foot_print')->add($foot);
            }
           $p_id=$goodsModel
               ->field('p_id')
               ->where(['id'=>$goods_id])
               ->find()['p_id'];
		   $this->fotoplace($goods_id);//添加到我的足迹
           $this->browseRecord($goods_id);//添加到同类产品浏览记录
           $goods_img=M('goods_images')
               ->where(array('goods_id'=>$p_id,'status'=>1))
               ->field('pic_url')
               ->select();
           $goods_img=array_column($goods_img,'pic_url');//商品图
//           $warehouse=M('storehouse')
//               ->field('id,name')
//               ->select();//仓库地址
		    //是否已收藏
            if(!empty($user_id)){//如果已登录
                $is_collection = M('collection')
                    ->where(
                        array('goods_id' => $goods_id, 'user_id' =>$user_id )
                    )
                    ->find();
                if(!empty($is_collection))
                {
                    $is_collect=1;
                }else{
                    $is_collect=0;
                }
            } else{
                   $is_collect=0;
            }
          //商品其他信息
       $goods_otherinfo=$goodsModel
           ->where(
               array('id'=>$goods_id)
           )
           ->field(
               'price_market,  price_member,p_id,title'
           )
           ->find();
		       $minStock=$goodsModel
                   ->where(
                       array('p_id'=>$goods_otherinfo['p_id'])
                   )
                   ->min('stock');

           //商品详情
           $goods_detail=M('goods_detail')
               ->where(
                   array('goods_id'=>$goods_otherinfo['p_id'])
               )
               ->getField('detail');
           $time=time();
//商品促销活动
          //价格优惠活动
           $where['a.goods_id']=$goods_id;
           $where['a.end_time']=array('GT',$time);
           $join='join db_promotion_goods as a on b.id=a.prom_id';
          $prom= M('prom_goods as b')
              ->join($join)
              ->where($where)
              ->field(
                  'b.name,b.description,a.end_time,activity_price'
              )
              ->find();
           $endtime=$prom['end_time'];
           $activity_price=$prom['activity_price'];
           unset($prom['end_time']);
           unset($prom['activity_price']);
           $active[]=$prom;
         //赠品活动
          $activity=M('commodity_gift')
               ->where([
                   'goods_id'=>$goods_id,'status'=>1
               ])
               ->field(
                   'type,expression,goods_id,end_time,description'
               )
               ->find();
              $goods_name=$goodsModel
                  ->where([
                      'id'=>$activity['goods_id']
                  ])
                  ->getField('title');
     if($activity['type']=='0' && $goods_otherinfo['price_market']>=$activity['expression'])
     {
               $act['name']='满赠';
               $act['description']='满'.$activity['expression'].'送'.$goods_name.'';

           }elseif($activity['type']==1){
               $act['name']='满赠';
               $act['description'] ='买此商品送'.$goods_name.'';

           }
           $active[]=$act;

           $compatriot=$this->attr($goods_id);//同胞
           $allattrcha=$this->childAttr($goods_otherinfo['p_id']);//所有规格属性
           $cart['user_id'] =zhong_decrypt(I('get.app_user_id'));//用户id
           $cart['is_del']=0;
           //购物车中商品数量
           $goods_num = M('goods_cart')
               ->field('goods_num')
               ->where($cart)
               ->select();
           $sum=0;
            foreach($goods_num as $v)
            {
                $sum += $v['goods_num'];
            }
           //搭配套餐推荐Recommend
           $goods_img_model=M('goods_images');
           $field = 'id,goods_id,sub_ids,create_time,update_time';
            $combo = M('goodsCombo')
                ->field($field)
                ->where(
                    ['goods_id'=>$goods_id]
                )
                ->find();
            if (!empty($combo))
            {
                $recommend =$goodsModel
                    ->field(
                        'id, title, price_member as price,p_id'
                    )
                    ->where(
                        ['id'=>['in', $combo['sub_ids']]]
                    )
                    ->limit(3)
                    ->select();
                if (is_array($recommend) && count($recommend)>0)
                {
                    foreach($recommend as $k=>$vo)
                    {
                       $fatherId=$vo['p_id'];
                       $fatherImg=$goods_img_model
                           ->where(
                               array('goods_id'=>$fatherId)
                           )
                           ->getField('pic_url');
                       $recommend[$k]['pic_url']=$fatherImg;
                    }
                }
            }else{
                $Self=$goodsModel
                    ->where(
                        array('id'=>$goods_id)
                    )
                    ->getField('p_id');
                //得到同胞的所有元素
                $recommend=$goodsModel
                    ->where(
                        array('p_id'=>$Self)
                    )
                    ->field(
                        'id,title,price_member,p_id'
                    )
                    ->limit(3)
                    ->select();
                foreach($recommend as $k=>$vo)
                {
                    $fatherId=$vo['p_id'];
                    $fatherImg=$goods_img_model
                        ->where(
                            array('goods_id'=>$fatherId)
                        )
                        ->getField('pic_url');
                    $recommend[$k]['pic_url']=$fatherImg;
                }
            }
           $data=array(
                 'goods_img'   =>$goods_img,
                 'minStock'    =>$minStock,
                 'is_collect'  =>$is_collect,
                 'title'       =>$goods_otherinfo['title'],
                 'goods_active'=>$active,
                 'activity_price'=>$activity_price,
                 'end_time'    =>$endtime,
                 'price_market'=>$goods_otherinfo['price_market'],
                 'price_member'=>$goods_otherinfo['price_member'],
                 //'warehouse'   =>$warehouse,
                 'compatriot'  =>$compatriot,
                 'allattrcha'  =>$allattrcha,
                 'goods_detail'=>$goods_detail,
                 'goods_num'   =>$sum,
                 'recommend'   =>$recommend
                );
             $this->returnMessage(1,'获取成功',$data);
       }
    }
    //猜你喜欢
    public function my_love(){
        $where['uid'] = zhong_decrypt(I('get.app_user_id'));//用户id
        $goods = M('foot_print')
            ->field(
                'gid as id,goods_name as title,goods_pic as pic_url,goods_price as price_market'
            )
            ->where($where)
            ->order('rand()')
            ->limit(3)
            ->select();
        if (empty($goods)) {
            $goods_images_model=M('goods_images');
            $maybe_love=M('goods')
                ->where("`p_id`!=0")
                ->field(
                    'db_goods.id,title,price_market,p_id'
                )
                ->order('rand()')
                ->limit(3)
                ->group('p_id')
                ->select();
            foreach($maybe_love as $k=>$vo)
            {
               $fatherId=$vo['p_id'];
               $fatherImg=$goods_images_model
                   ->where(
                       array('goods_id'=>$fatherId)
                   )
                   ->getField('pic_url');
               $maybe_love[$k]['pic_url']=$fatherImg;
            }
        }else{
            $maybe_love = $goods;
        }
        $this->returnMessage(1,'获取成功',$maybe_love);
    }
    //根据产品号查出商品的同胞和所有属性
    public function attr($goods_id){
        $goods_model=M('goods');
        $self=$goods_model
            ->where(
                array('id'=>$goods_id)
            )
            ->getField('p_id');
        $join='db_spec_goods_price ON db_spec_goods_price.goods_id=db_goods.id';
        //得到同胞的所有元素
        $compatriot=$goods_model
            ->where(array('p_id'=>$self))
            ->join($join)
            ->field(
                'db_goods.id,price_member,p_id,stock,key'
            )
            ->select();
        return $compatriot;
    }

     //根据商品id获取规格
    public function specifications(){
       if(IS_GET){
           $goods_id=I('get.goods_id');
           $find=M('goods')->where(array('id'=>$goods_id))->find();
           //品牌
           $brand=M('brand')->where(array('id'=>$find['brand_id']))->getField('brand_name');
           //所属类型classname
           $className=M('goods_class')
               ->where(array('id'=>$find['class_id']))
               ->getField('class_name');
           //商品类型名称
           $typename=M('goods_type')
               ->where(array('id'=>$find['goods_type']))
               ->getField('name');
           //获取商品的属性规格及对应的属性值
           $attr=M('spec_goods_price')
               ->where(array('goods_id'=>$find['id']))
               ->getField('key');
           $attr=explode('_',$attr);
           $condition['db_goods_spec_item.id']=array('in',$attr);
           $join='db_goods_spec ON db_goods_spec.id=db_goods_spec_item.spec_id';
           $attra=M('goods_spec_item')
               ->join($join)
               ->where($condition)
               ->field('name,item')
               ->select();
           $data=array(
                       'title'    =>$find['title'],
                       'brand'    =>$brand,
                       'className'=>$className,
                       'typename' =>$typename,
                       'attra'    =>$attra,
                      );
           $this->returnMessage(1,'获取成功',$data);
        }
    }
	 //商品评论-为了减少数据的请求，分两步走
    //第一：统计数量
    public function goodsComment(){
        if(IS_GET){
            $goods_id=I('get.goods_id');
            $this->isEmpty($goods_id);
            $order_comment_model=M('order_comment');
            $data['allcount']=$order_comment_model
                ->where(array('goods_id'=>$goods_id))
                ->count();//全部
            $data['nice']=$order_comment_model
                ->where(array('goods_id'=>$goods_id,'level'=>3))
                ->count();//好
            $data['height']=$order_comment_model
                ->where(array('goods_id'=>$goods_id,'level'=>2))
                ->count();//中
            $data['bad']=$order_comment_model
                ->where(array('goods_id'=>$goods_id,'level'=>1))
                ->count();//差
            //有图
        $data['isimg']=$order_comment_model
            ->where(
                array('show_pic'=>['NEQ',''],'goods_id'=>$goods_id)
            )
            ->count();
            $this->returnMessage(1,'获取成功',$data);
        }
    }
    //第二：各种评价列表
    public function commentList(){
        $status=I('get.status');
        if (!empty($status))
        {
           $condition['level']=$status;
        }
        $condition['goods_id']=I('get.goods_id');
        $spec_goods_price_model=M('spec_goods_price');
        $goods_spec_item=M('goods_spec_item');
        $goods_spec=M('goods_spec');
        $User = M('order_comment'); // 实例化User对象
        $count      = $User->where($condition)->count();// 查询满足要求的总记录数
        $Page       = new \Think\Page($count,25);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show       = $Page->show();// 分页显示输出// 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $join='db_user ON db_user.id=db_order_comment.user_id';
        $field='db_order_comment.id,goods_id,db_order_comment.level,nick_name,show_pic,space_id,user_name,db_order_comment.create_time,db_order_comment.status,content';
        $list = $User
            ->where($condition)
            ->join($join)
            ->field($field)
            ->order(
                'db_order_comment.create_time DESC'
            )
            ->limit($Page->firstRow.','.$Page->listRows)
            ->select();
        $join1='db_goods_spec ON db_goods_spec.id=db_goods_spec_item.spec_id';
        $field1='name,item';
        $imagesModel=M('images');
        foreach($list as $k=>$vo)
        {
            $find=$spec_goods_price_model
                ->where(array('id'=>$vo['space_id']))
                ->getField('key');
            $attr=explode('_',$find);
            $condi['db_goods_spec_item.id']=array('in',$attr);
            $result=$goods_spec_item
                ->where($condi)
                ->join($join1)
                ->field($field1)
                ->select();
            $list[$k]['attr']=$result;
            $show_pic=explode(',',$vo['show_pic']);
            if(!empty($vo['show_pic']))
            {
                foreach ($show_pic as $v)
                {
                    $list[$k]['imgs'][] =$imagesModel
                        ->where(['id' => $v])
                        ->find()['path'];
                }
            }else{
                $list[$k]['imgs']='';
            }
        }
        if(!empty($list))
            $this->returnMessage(1,'获取成功',$list);
        else
            $this->returnMessage(0,'暂无数据','');
    }
    //嗮图的单独显示
     public function displayImg(){
         $goods_id=I('get.goods_id');
         $this->isEmpty($goods_id);
         $User = M('order_comment'); // 实例化User对象
         $goods_spec_item=M('goods_spec_item');
         $count= $User
             ->where(
                array(
                 'goods_id'=>$goods_id,
                 'show_pic'=>['NEQ','']
                 )
             )
             ->count();// 查询满足要求的总记录数
         $Page       = new \Think\Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数(25)
         $show       = $Page->show();// 分页显示输出// 进行分页数据查询 注意limit方法的参数要使用Page类的属性
         $join='db_user ON db_user.id=db_order_comment.user_id';
         $field='db_order_comment.id,goods_id,db_order_comment.level,nick_name,show_pic,space_id,user_name,db_order_comment.create_time,img,db_order_comment.status,content';
         $list = $User
             ->where(
                 array(
                     'show_pic'=>['NEQ',''],
                     'goods_id'=>$goods_id)
             )
             ->join($join)
             ->field($field)
             ->order('create_time DESC')
             ->limit($Page->firstRow.','.$Page->listRows)
             ->select();
         $join1='db_goods_spec ON db_goods_spec.id=db_goods_spec_item.spec_id';
         $field1='name,item';
         $spec_goods_price=M('spec_goods_price');
         foreach($list as $k=>$vo)
         {
             $find=$spec_goods_price
                 ->where(
                     array('id'=>$vo['space_id'])
                 )
                 ->getField('key');
             $attr=explode('_',$find);
             $condi['db_goods_spec_item.id']=array('in',$attr);
             $result=$goods_spec_item
                 ->where($condi)
                 ->join($join1)
                 ->field($field1)
                 ->select();
             $list[$k]['attr']=$result;
         }
         if(!empty($list))
             $this->returnMessage(1,'获取成功',$list);
         else
             $this->returnMessage(0,'暂无数据','');
     }
	//产品详细-商品咨询列表
    public function Consultation(){
     if(IS_POST){
           $join='db_answer ON db_problem.id=db_answer.problem_id';
           $goods_id=I('post.goods_id');//商品id
           $this->isEmpty($goods_id);
           $count=M('problem')
               ->where(
                   array('goods_id'=>$goods_id)
               )
               ->count();
           $Page       = new \Think\Page($count,20);// 实例化分页类 传入总记录数和每页显示的记录数(25)
           $show       = $Page->show();// 分页显示输出// 进行分页数据查询 注意limit方法的参数要使用Page类的属性
           $consultation=M('problem')
               ->where(
                   array('goods_id'=>$goods_id)
               )
               ->join($join)
               ->field(
                   'problem_id,db_problem.addtime,answer,problem'
               )
               ->limit($Page->firstRow.','.$Page->listRows)
               ->select();
        if($consultation)
        {
            $this->returnMessage(1,'获取成功',$consultation);
        }else
        {
            $this->returnMessage(0,'暂无数据','');
        }
      }
    }


	 //根据类找到对应类的商品
    public function getProduct(){
      if(IS_GET){
           $class_id=I('get.class_id');
          $this->isEmpty($class_id);
           $User = M('goods'); // 实例化User对象
           $goodsimg_model=M('goods_images');
           $order_comment=M('order_comment');
           $count      = $User
               ->where("`class_id`=$class_id AND `p_id`!=0")
               ->count();// 查询满足要求的总记录数
           $Page       = new \Think\Page($count,25);// 实例化分页类 传入总记录数和每页显示的记录数(25)
           $show       = $Page->show();// 分页显示输出// 进行分页数据查询 注意limit方法的参数要使用Page类的属性
           $result = $User
               ->where("`class_id`=$class_id AND `p_id`!=0")
               ->order('create_time DESC')
               ->field('id,p_id,title,price_market')
               ->group('p_id')
               ->limit($Page->firstRow.','.$Page->listRows)
               ->select();
           foreach($result as $k=>$vo)
           {
             $img=$goodsimg_model
                 ->where(array('goods_id'=>$vo['p_id']))
                 ->find();
             $result[$k]['img']=$img['pic_url'];
             $result[$k]['count']=$order_comment
                 ->where(array('goods_id'=>$vo['id']))
                 ->count();
           }
           $this->isEmpty($result);
           $this->returnMessage(1,'获取成功',$result);
        }
    }

    /**
     * 商品图文详情
     *
     */
    public function goodsDetail(){
        $goods_id=I('get.goods_id');
        $this->isEmpty($goods_id);
        $p_id=M('goods')
            ->field('p_id')
            ->where(['id'=>$goods_id])
            ->find()['p_id'];
        $this->isEmpty($p_id);
        $re= M('goods_detail')
            ->where(['goods_id'=>$p_id])
            ->find();
        $re['detail']=html_entity_decode($re['detail']);
        $re['detail']= preg_replace('/(<img.+?src=")(.*?)/','$1'.__SERVER__.'$2', $re['detail']);
        $this->assign('detail',$re['detail']);
        $this->display();
    }
}
