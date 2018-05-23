<?php
namespace Home\Controller;
use Think\Controller;
use Home\Model\IntegralUseModel;
class OrderController extends CommonController {
    //提交评价
public function commentAdd(){
    $user_id=zhong_decrypt(I('post.app_user_id'));
    $data['user_id']=$user_id;
    $data['order_id']=I('post.order_id');
    $data['content']=I('post.content');
    $data['level']=I('post.status');
    $data['goods_id']=I('post.goods_id');
    $data['create_time']=time();
    //$mgtype=I('post.type');//手机移动网站传图
    $mgtype=json_decode($_POST['mobileImage'],true);
    $show=array();
    if(!empty($mgtype))
    {
        foreach($mgtype as $k=>$v)
        {
        $base_img = str_replace($v['type'],'',$v['img']);
        $path = "./Uploads/comment/";
        $prefix = 'com_';
        $output_file = $prefix . time().rand(100, 999) . '.jpg';
        $path = $path . $output_file;
        file_put_contents($path, base64_decode($base_img));
        $comment['create_time'] = time();
        $comment['path'] =substr(trim($path),1);
        $r=M('images')->add($comment);
        $show[]=$r;
        }
        $data['show_pic']=implode(',',$show);
    }else {

        if (!empty($_FILES)) {//有图片上传
            $info = $this->upload('comment');
            if ($info['status'] == 0) $this->returnMessage(0, '新增错误', $info['msg']);
            if ($info['status'] == 1) {//图片上传成功
                $data['isimg'] = 1;
                $info = $info['info'];
                $address = array();
                foreach ($info as $vo) {
                    $address[] = '/Uploads' . $vo['savepath'] . $vo['savename'];
                }
                $comment_img = implode("$", $address);
                $data['img'] = $comment_img;
            }
        }
    }
    $re=M('order_comment')->add($data);
   // $this->commentUpload($comment_img,$data['user_id'], $data['goods_id'],$data['order_id']);
    if($re){
        $status['comment']=1;
        $status['over']=1;
        M('order_goods')->where([
            'order_id'=>$data['order_id'],
            'goods_id'=> $data['goods_id']
        ])
            ->save($status);
        $sta['comment_status']=1;
        M('order')->where([
            'id'=>$data['order_id']
        ])
            ->save($sta);

        $this->returnMessage(1,'评价成功','');
    }else{
        $this->returnMessage(0,'评论失败','');
    }

}

    //收藏
    public function myCollection(){
    if(IS_POST){
             $user_id=zhong_decrypt(I('post.app_user_id'));
           // $user_id=13;
             $collection_model=M('collection');
             $goods_model=M('goods');
             $goods_images=M('goods_images');
             $result=$collection_model
                 ->where(array('user_id'=>$user_id))
                 ->field('goods_id')
                 ->select();
        if(!empty($result))
        {
            $classattr=array_column($result,'goods_id');
            $join='db_goods_class ON db_goods_class.id=db_goods.class_id';
            $condition['db_goods.id']=array('in',$classattr);
            //获得收藏中产品的所有类型
            $res=$goods_model
                ->where($condition)
                ->join($join)
                ->field(
                    'db_goods_class.id,class_name,p_id,db_goods.class_id'
                )
                ->select();
            $goods=$goods_model
                ->where($condition)
                ->field('id,p_id,title,price_member')
                ->select();
        }
             $newclass=array();
             $aa=array();
             foreach($res as $k=>$vo){
                if(in_array($vo['id'],$aa)==false){
                    $aa[] = $vo['id'];
                    $newclass[]=$vo;
            }
             }
        if(!empty($goods))
        {
            foreach($goods as $k=>$v)
            {
                $img=$goods_images
                    ->where(array('goods_id'=>$v['p_id']))
                    ->field('pic_url')
                    ->find();
                $goods[$k]['img']=$img['pic_url'];
            }
        }
           $data=array('classname'=>$newclass,'goods'=>$goods);
            if($data)
                $this->returnMessage(1,'获取成功',$data);
            else
                $this->returnMessage(0,'暂无数据','');
    }
      }
    /**
     * 我的收藏顶部搜索
     */
    public function myCollection_search(){
        if(IS_POST) {
            $user_id = zhong_decrypt(I('post.app_user_id'));
            $word = I('post.word');
            $condi['goods_name'] = (array('like', "%$word%"));
            $condi['user_id'] = $user_id;
            $result = M('collection')
                ->where($condi)
                ->field('goods_id,goods_name')
                ->select();
            $goodsModel=M('goods');
            if(!empty($result))
            {
                $classattr=array_column($result,'goods_id');
                $join='db_goods_class ON db_goods_class.id=db_goods.class_id';
                $condition['db_goods.id']=array('in',$classattr);
                //获得收藏中产品的所有类型
                $res=$goodsModel
                    ->where($condition)
                    ->join($join)
                    ->field(
                        'db_goods_class.id,class_name,p_id,db_goods.class_id'
                    )
                    ->select();
                $goods=$goodsModel
                    ->where($condition)
                    ->field('id,p_id,title,price_member')
                    ->select();
            }
            $newclass=array();
            $aa=array();
            foreach($res as $k=>$vo)
            {
                if(in_array($vo['id'],$aa)==false)
                {
                    $aa[] = $vo['id'];
                    $newclass[]=$vo;
                }
            }
            if(!empty($goods))
            {
                $goods_images_model=M('goods_images');
                foreach($goods as $k=>$v)
                {
                    $img=$goods_images_model->where(array('goods_id'=>$v['p_id']))->field('pic_url')->find();
                    $goods[$k]['img']=$img['pic_url'];
                }
            }
            $data=array('classname'=>$newclass,'goods'=>$goods);
            if($data) $this->returnMessage(1,'获取成功',$data);
            else $this->returnMessage(0,'暂无数据','');
        }
    }

    //根据收藏的类找产品
    public function classGoods(){
        if(IS_GET){
            $user_id=zhong_decrypt(I('get.app_user_id'));
            $collection_model=M('collection');
            $goods_model=M('goods');
            $goods_images=M('goods_images');
            $class_id=I('get.class_id');
            if($class_id!=""){
                $result=$collection_model
                    ->where(
                        array(
                            'class_id'=>$class_id,
                            'user_id'=>$user_id
                        )
                    )
                    ->field('goods_id')
                    ->select();
            }
            $newgoods=array();
            foreach($result as $vo)
            {
                $bb=$goods_model
                    ->where(
                        array('id'=>$vo['goods_id'])
                    )
                    ->field(
                        'id,p_id,title,price_member'
                    )
                    ->find();
                $img=$goods_images
                    ->where(
                        array('goods_id'=>$bb['p_id'])
                    )
                    ->field('pic_url')
                    ->find();
                $bb['img']=$img['pic_url'];
                $newgoods[]=$bb;
            }
            if(!empty($newgoods))
                $this->returnMessage(1,'获取成功',$newgoods);
            else
                $this->returnMessage(0,'暂无数据','');
        }
    }

    /**
     * 配送方式
     */
    public function Shipping(){
        $user_id=zhong_decrypt(I('post.app_user_id'));
        $address_id=I('post.address_id');
         $r=  $this->_getShipping($user_id,$address_id);
       if($r) $this->returnMessage(1,'成功',$r);
    }

    /**
     * 运费
     */
    public function freight(){
        $user_id=zhong_decrypt(I('post.app_user_id'));
        $address_id=I('post.address_id');
        $carry_id=I('post.id');
        $goods=json_decode($_POST['goods'],true);
        $goods_num=0;
        foreach($goods as $k=>$vo){
            $goods_num+=$vo['num'];
        }
        $r['freight']=$this->_getfreight($carry_id,$user_id,$address_id,$goods_num);
        $this->returnMessage(1,'成功',$r);
    }

    /**
     * 获取运费
     */
    public function _getfreight($carry_id,$user_id,$address_id,$goods_num){
        if(empty($carry_id)){
            $r=$this->_getShipping($user_id,$carry_id);
            $carry_id=$r['id'];
        }
        $user_address=M('user_address');
        if(empty($address_id))
        {
            $address=$user_address
                ->where(array('user_id'=>$user_id,'status'=>1))
                ->field('prov,dist,city')
                ->find();//地址
        }else{
            $address=$user_address
                ->where(array('id'=>$address_id))
                ->field('prov,dist,city')
                ->find();
        }
        $addre=array_values($address);
        $freight=M('freight_mode')
            ->where(['carry_way'=>$carry_id])
            ->field(
                'id,frist_money,first_thing,continued_money,freight_id,continued_thing'
            )
            ->find();
        if($freight['freight_id'])
        {
            $freightRe=M('freights')
                ->where(['id'=>$freight['freight_id']])
                ->find();
            $freight_condition=M('freight_condition as a')
                ->join(
                    'db_freight_area as b on b.freight_id=a.id'
                )
                ->where([
                        'a.freight_id'=>$freight['freight_id'],
                        'b.mail_area'=>['in',$addre]
                    ])
                ->find();
        }//var_dump($freight_condition);die;
        //默认按件计价
        if(!empty($freight_condition))
        {//包邮地区内
            if($goods_num<=$freight_condition['mail_area_num'])
            {
                $freig='0';
            }else
            {
                $n_freight=ceil((intval($goods_num)-intval($freight['first_thing']))/$freight['continued_thing'])*$freight['continued_money'];
                $freig=$freight['frist_money']+$n_freight;
            }

        }elseif($freightRe['is_free_shipping']==2)
        {//卖家包邮
            $freig='0';
        } elseif($goods_num<=$freight['first_thing'])
        {
            $freig=$freight['frist_money'];
        }else
        {
            $n_freight=ceil((intval($goods_num)-intval($freight['first_thing']))/$freight['continued_thing'])*$freight['continued_money'];
            $freig=$freight['frist_money']+$n_freight;
        }
       return $freig;
    }


	 //去结算
    public function goBuy(){
        if(IS_POST){
             $user_id=zhong_decrypt(I('post.app_user_id'));
                $carry_id=I('post.carry_id');
            if(empty($carry_id)){
                $r=$this->_getShipping($user_id,$carry_id);
                $carry_id=$r['id'];
            }
            $address_id=I('post.address_id');
            $list=M('user_address')
                ->field(
                    'id,realname,mobile,prov,city,dist,address'
                )
                ->where(
                   ['user_id'=>$user_id]
                )
                ->find();
            if(empty($list)){
                $this->returnMessage(0,'请完善收货地址','');
            }

                $goods_model=M('goods');
                $goods_images_model=M('goods_images');
                $goods=json_decode($_POST['goods'],true);
                $goods_num=0;
                $total_price=0;
                $integral=0;
                foreach($goods as $k=>$vo)
                {
                    $goods[$k]['attr']=$this->selfAttr($vo['id']);
                    $fatherId=$goods_model
                        ->where(array('id'=>$vo['id']))
                        ->getField('p_id');
                    $fatherImg=$goods_images_model
                        ->where(array('goods_id'=>$fatherId))
                        ->getField('pic_url');
                    $goods[$k]['fatherImg']=$fatherImg;
                    $price=$goods_model
                        ->where(array('id'=>$vo['id']))
                        ->field('price_member,title')
                        ->find();
                    $goods[$k]['title']=$price['title'];
                    $goods[$k]['price_member']=$price['price_member'];
                    $total_price+=$price['price_member']*$vo['num'];
                    $goods_num+=$vo['num'];
                    //获得商品积分
                    $integra=M('integral_goods')
                        ->where(['goods_id'=>$vo['id']])
                        ->field('integral,money')
                        ->find();
                    $integral+=$integra['integral']*$vo['num'];
                    $integra_money=$integra['money'];
                }

                $region_model=M('region');
                $address=M('user_address')
                    ->where(array('user_id'=>$user_id,'status'=>1))
                    ->field('id,realname,mobile,prov,city,dist,address,status')
                    ->find();//地址
                $address['prov']=$region_model
                    ->where(array('id'=>$address['prov']))
                    ->getField('name');
                $address['city']=$region_model
                    ->where(array('id'=>$address['city']))
                    ->getField('name');
                $address['dist']=$region_model
                    ->where(array('id'=>$address['dist']))
                    ->getField('name');
            //运费
            $freight=$this->_getfreight($carry_id,$user_id,$address_id,$goods_num);
                //有效的优惠劵
                $coupon_model=M('coupon_list');
                $time=time();
                $coupon=$coupon_model->query("SELECT `condition`,`name`,`money`,`use_start_time`,`use_end_time`,__PREFIX__coupon_list.id FROM `__PREFIX__coupon_list` INNER JOIN __PREFIX__coupon ON __PREFIX__coupon.id=__PREFIX__coupon_list.c_id WHERE ( user_id=$user_id AND use_start_time<$time AND use_end_time>$time )");
                //计算自己的积分
                //$integral=M('user')->where(array('id'=>$user_id))->getField('integral');
             $sum=(new IntegralUseModel())->integral($user_id)['sum'];
             $integeal_sum=intval(str_replace('+','',$sum));//积分总数
            if($_POST['is_integral']){
                if($integral>$integeal_sum){
                    $this->returnMessage(0,'积分不够不能税换',"");
                }
            }
                $data=array(
                    'goods'=>$goods,
                    'freight'=>$freight,
                    'total_price'=>$total_price,
                    'address'=>$address,
                    'coupon'=>$coupon,
                    'integral'=>$integeal_sum,
                    'pay_integral'=>$integral,
                    'integral_money'=>$integra_money
                );
                $this->returnMessage(1,'获取成功',$data);
       }
    }

	 //去结算中商品数量的增加，减少
    public function orderGoodsNumChange(){
        if(IS_POST) {
            $cart_id = I('post.cart_id');
            $type = I('post.type');//1为减少 2为增加
            $goods_cart = M('goods_cart');
            $find = $goods_cart->where(array('id' => $cart_id))->find();
            if (!$find)
                $this->returnMessage(0, '购物车不存在商品', '');
            if ($type == 1 && $find['goods_num'] == 1)
            {//减少且购物车数量只剩1
                $this->returnMessage(2, '您确定要将此产品移除购物车?', '');
            } elseif ($type == 1) {
                $goods_cart->where(array('id' => $cart_id))->setDec('goods_num');
                $yunfei=-2;
                $this->returnMessage(1, '操作成功', $yunfei);
            } else {
                $goods_cart->where(array('id' => $cart_id))->setInc('goods_num');

                $yunfei=2;
                $this->returnMessage(1, '操作成功', $yunfei);
            }
        }
    }
	 //支付配送
    public function orderBegin(){
        if(IS_POST){
            $order_goods_model = M('order_goods');
            $order_model = M('order');
            $spec_goods_price=M('spec_goods_price');
            $cart_model = M('goods_cart');
            $db_coupon_list=M('coupon_list');
            $goodsModel=M('goods');
            $user_id=zhong_decrypt(I('post.app_user_id'));
            $order_sn=$this->toGUID();
            $data['order_sn_id'] = $order_sn;   //订单单号
            $price_sum=I('post.price_sum');
            $data['price_sum'] =$price_sum; //总金额
            $data['address_id'] = I('post.address_id');//收货地址
            if($user_id == 0 || $user_id == ''){
                $this->returnMessage(0,'请登录','');
            }else{
                $_SESSION['user_id'] = $user_id;
            }
            $list=M('user_address')
                ->field(
                    'id,realname,mobile,prov,city,dist,address'
                )
                ->where(
                    ['id'=>$data['address_id'],'user_id'=>$user_id]
                )
                ->find();
            if(empty($list)){
                $this->returnMessage(0,'请完善收货地址','');
            }
            $data['user_id'] = $user_id; //购买者
            $data['create_time'] = time();          //创建时间
            $data['order_status'] = 0;//默认为0：未支付状态     //订单状态
            $data['pay_type'] = I('post.pay_type');//支付类型
            if(!empty(I('post.shipping'))){
                $data['shipping']=I('post.shipping');//配送方式
            }else{
                $shipping=$this->_getShipping($user_id,"");
                $data['shipping']=$shipping[0]['name'];//默认配送方式
            }
            $data['remarks'] = I('post.remarks');//备注
            $data['translate'] =I('post.translate');//是否需要发票
            $shipping_monery=I('post.shipping_monery');
            $shipping_monery?$shipping_monery:0;
            $data['shipping_monery'] =$shipping_monery;//订单运费
            //$data['exp_id'] = I('post.exp_id');//快递编号？？？？
            $data['platform']=2;//1:代表pc,2:代表app
            $order_big = $order_model->add($data);
            //若需要发票,1为需要，0为不需要
            if(I('post.translate')==1){
                $invoice=json_decode($_POST['invoice'],true);
                foreach($invoice as $k1=>$v1){
                    $bill['content']=$v1['content'];
                    $bill['invoice_title']=$v1['invoice_title'];
                    $bill['invoice_type']=$v1['invoice_type'];
                    $bill['order_id']=$order_big;
                    $bill['create_time']=time();
                    $bill['user_id']=$user_id;
                    M('invoice')->add($bill);
                }

                //$bill['content']=$invoice['content'];
                //$bill['invoice_title']=$invoice['invoice_title'];
                //$bill['invoice_type']=$invoice['invoice_type'];
                //$this->returnMessage(1,'查看查看',$invoice);
            }
            if ($order_big) {//添加成功--开始生成小订单号
                $goods=json_decode($_POST['goods'],true);
                /*$goods = array(
                    array('id' => 1097, 'num' => 2,'goods_price'=>10),
                    array('id' => 1094, 'num' => 3,'goods_price'=>10),
                );*/
                //如果使用了优惠劵
                if(!empty(I('post.coupon_id'))){
                    $coup['order_id']=$order_big;
                    $coup['use_time']=time();
                    $coup['status']=1;
                    $db_coupon_list->where(array('c_id'=>I('post.coupon_id'),'user_id'=>$user_id))->save($coup);
                    $money=M('coupon')->where(['id'=>I('post.coupon_id')])->find()['money'];
                    $coup['status']=1;
                    M('coupon_list')->where(['id'=>I('post.coupon_id')])->save($coup);
                }
                //立即购买不经过购物车可能存在购物车里已存在此商品但仍立即下单购买
                if($_POST['buyType']==2){//2:为立即购买型 1：为购物车购买
                    //1:到仓库里去查找第一个仓库id。如果为立即购买则因是一个一维数组：
                    foreach($goods as $k2=>$v2)
                    {
                        $order['goods_id'] = $v2['id'];//商品id
                        $order['goods_num'] = $v2['num'];//商品数量
                        $order['goods_price'] = $v2['goods_price'];//商品价格
                        //$storehouse_id = M('storehouse')->find();
                        //$order['ware_id'] = $storehouse_id['id'];//仓库号
                        $order['order_id'] = $order_big;//订单号
                        $order['space_id'] = $spec_goods_price
                            ->where(array('id' =>$v2['id']))
                            ->getField('id');//商品规格
                        $goods_price = $goods['num'] * $goods['goods_price'];//商品总价
                        $order['user_id'] = $user_id;
                        $order_small = $order_goods_model->add($order);
                        //修改商品销量
                       $sales_sum= $goodsModel
                           ->where(['id'=>$v2['id']])
                           ->getField('sales_sum');
                        $sales['sales_sum']=$sales_sum+$v2['num'];
                        $goodsModel->where(['id'=>$v2['id']])->save($sales);
                    }
                    if($order_small)
                    {
                        $this->returnMessage(1,'下单成功',$order_big);
                    } else
                    {
                        $this->returnMessage(0,'下单失败','');
                    }

                }
                //经过购物车购买--需要删除购物车数据
                if($_POST['buyType']==1)
                {
                  foreach ($goods as $k => $vo)
                  {
                    //$Cart=$cart_model->where(array('goods_id'=>$vo['id'],'user_id'=>$user_id))->find();
                    $order['ware_id'] = $cart_model
                        ->where(
                            array('user_id' => $user_id, 'goods_id' => $vo['id'])
                        )
                        ->getField('ware_id');
                    $order['order_id'] = $order_big;//订单号
                    $order['goods_id'] = $vo['id'];//商品id
                    $order['goods_num'] = $vo['num'];//商品数量
                    $order['goods_price'] = $vo['goods_price'];//商品价格
                    $order['space_id'] = $spec_goods_price
                        ->where(
                            array('goods_id' => $vo['id'])
                        )
                        ->getField('id');//商品规格
                    $goods_price += $vo['num'] * $vo['goods_price'];//商品总价
                    $order['user_id'] = $user_id;
                    //通过购物车购买-创建小订单-删除购物
                    $order_goods_model->add($order);//创建小订单
                    $cart_model
                        ->where(
                            array('goods_id' => $vo['id'], 'user_id' => $user_id)
                        )
                        ->delete();//删除购物车
                      //修改商品销量
                      $sales_sum=$goodsModel->where(['id'=>$vo['id']])->getField('sales_sum');
                      $sales['sales_sum']=$sales_sum+$vo['num'];
                      $goodsModel->where(['id'=>$vo['id']])->save($sales);
                  }
              }
                 $money_sum =$goods_price+$shipping_monery-$money;
                if($money_sum ==$price_sum){
                    $this->returnMessage(1,'创建订单成功',$order_big);
                    //删除购物车
                    $ondition['id']=array('in',array_column($goods,'id'));
                    $ondition['user_id']=$user_id;
                    $cart_model->where($ondition)->delete();
                }else{
                    $this->returnMessage(0,'订单金额错误','');
                }
            }else{
                $this->returnMessage(0,'创建订单失败','');
            }
        }
        //$this->display('index/orderBegin');
    }
	
	 //获取本产品的属性
    private function selfAttribute($goods_model,$goods_id){
        $goods_spec_item_model=M('goods_spec_item');
        $join='db_goods_spec ON db_goods_spec.id=db_goods_spec_item.spec_id';
        $join1='db_spec_goods_price ON db_spec_goods_price.goods_id=db_goods.id';
        $chlidattr=$goods_model
            ->join($join1)
            ->field('key')
            ->where(array('db_goods.id'=>$goods_id))
            ->find();
        $chlidattra=explode("_",$chlidattr['key']);//得到每个子类有的产品属性
        $condition['db_goods_spec_item.id']=array('in',$chlidattra);
        $chlidattrdetal=$goods_spec_item_model
            ->join($join)
            ->field('item,name')
            ->where($condition)
            ->select();
        return $chlidattrdetal;
    }
    //获取图片
    private function selfImg($goods_model,$goods_images_model,$goods_id){
        $fatherId=$goods_model
            ->where(array('id'=>$goods_id))
            ->getField('p_id');
        $fatherImg=$goods_images_model
            ->where(array('goods_id'=>$fatherId))
            ->getField('pic_url');
        return $fatherImg;
    }
    private function smallOrder($list){
        $db_order_goods=M('order_goods');
        $goods_model=M('goods');
        $goods_images_model=M('goods_images');
        foreach ($list as $key=>$value)
        {
            $count=$db_order_goods
                ->where(array('order_id'=>$value['id']))
                ->count();
            $goods_id=$db_order_goods
                ->where(
                    array('order_id'=>$value['id'])
                )
                ->field('goods_id')
                ->select();
            foreach($goods_id as $k=>$v)
            {
               $goods=$goods_model
                   ->where(
                       array('id'=>$v['goods_id'])
                   )
                   ->field(
                       'title,price_market,p_id'
                   )
                   ->find();
                if($goods){
                    $selfImg=$goods_images_model
                        ->where(['goods_id'=>$goods['p_id']])
                        ->field('pic_url')
                        ->find();
            }else{
                    $selfImg="";
                }
                $selfAttr=$this->selfAttribute($goods_model,$v['goods_id']);
                $goods_id[$k]['selfImg']=$selfImg['pic_url'];
                $goods_id[$k]['selfAttr']=$selfAttr;
                $goods_id[$k]['goods_id']=$v['goods_id'];
                $goods_id[$k]['title']=$goods['title'];
                $goods_id[$k]['price_market']=$goods['price_market'];
            }
            $list[$key]['count']=$count;
            $list[$key]['goods']=$goods_id;
        }
        return $list;
    }
    //我的订单
    public function myOrder(){
        $condition['user_id']=zhong_decrypt(I('get.app_user_id'));
        $order_status=I('get.order_status');
        $p=I('get.p');
        $integral_order=I('get.order_type');
        if($order_status!="")
        {
            $condition['order_status']=$order_status;
        }
        if(!empty($integral_order))
        {
            $condition['order_type']='4';
        }else
        {
            $condition['order_type'] = array('NEQ', '4');
        }
        $condition['status']= '0' ;
        $User = M('order'); // 实例化User对象
        $count      = $User->where($condition)->count();// 查询满足要求的总记录数
       if($count==0) $this->returnMessage(0,'暂无数据','');
        $list = $User
            ->where($condition)
            ->field(
                'id,price_sum,create_time,order_sn_id,order_status,comment_status,order_type'
            )
            ->order('create_time DESC')
            ->page($p,C('page_size'))
            ->select();
        if(!empty($integral_order))
        {
            $condition['order_type'] = '4';
            $integral_goodsModel=M('integral_goods as a');
            foreach ($list as $k => $v)
            {
                $integral=$integral_goodsModel
                    ->join(
                        'db_order_goods as b on b.goods_id=a.goods_id'
                    )
                    ->where(
                        ['b.order_id' =>$v['id']]
                    )
                    ->getField('a.integral');
                $list[$k]['integral']=$integral;
            }
        }
        $list=$this->smallOrder($list);
        if($list[0]['id']){
            $this->returnMessage(1,'获取成功',$list);
        }else{
            $this->returnMessage(0,'暂无数据',$list);
        }
    }

    /**
     * 订单搜索
     */
	public function orderSearch(){
        if(IS_POST) {
            $word = I('post.word');
            $user_id =zhong_decrypt(I('post.app_user_id'));
            $where['a.user_id']=$user_id;
            $where['a.order_sn_id|g.title']=array('like','%'.$word.'%');
            $order = M('order as a')
                ->where($where)
                ->join(
                    'join db_order_goods as b on b.order_id=a.id join db_goods as g on g.id=b.goods_id'
                )
                ->field(
                    'a.id,a.price_sum,a.create_time,a.order_sn_id,a.order_status,a.comment_status'
                )
                ->select();
        //数组去重
        foreach ($order as $k=>$v)
        {
            $v=join(',',$v); //降维,也可以用implode,将一维数组转换为用逗号连接的字符串
            $temp[$k]=$v;
        }
        $temp=array_unique($temp); //去掉重复的字符串,也就是重复的一维数组
        foreach ($temp as $k => $v)
        {
            $array=explode(',',$v); //再将拆开的数组重新组装
            //下面的索引根据自己的情况进行修改即可
            $temp2[$k]['id'] =$array[0];
            $temp2[$k]['price_sum'] =$array[1];
            $temp2[$k]['create_time'] =$array[2];
            $temp2[$k]['order_sn_id'] =$array[3];
            $temp2[$k]['order_status'] =$array[4];
            $temp2[$k]['comment_status'] =$array[5];
        }
                $list = $this->smallOrder($temp2);
                if (!empty($list))
                {
                    $this->returnMessage(1, '获取成功', $list);
                } else
                {
                    $this->returnMessage(0, '没有查找到订单', "");
                }
        }
    }


	//待评价
    public function notEvaluate(){
        $condition['user_id']=zhong_decrypt(I('get.app_user_id'));
        $condition['comment_status']=0;
        $condition['order_status']='4';
        $User = M('order'); // 实例化User对象
        $count      = $User->where($condition)->count();// 查询满足要求的总记录数
        if($count==0)$this->returnMessage(0,'暂无数据','');
        $Page       = new \Think\Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $list = $User
            ->where($condition)
            ->order('create_time DESC')
            ->field(
                'id,price_sum,create_time,order_sn_id,order_status,comment_status'
            )
            ->limit($Page->firstRow.','.$Page->listRows)
            ->select();
        foreach($list as $k=>$v)
        {
            $list[$k]['order_state']='1';
        }
        $list=$this->smallOrder($list);
        $this->returnMessage(1,'获取成功',$list);
    }
	//根据订单id进入到订单详情
    public function orderDetail(){
       if(IS_POST){
            $order_id=I('post.order_id');
            $coupon_id=I('post.coupon_id');
            $goods_model=M('goods');
            $goods_images_model=M('goods_images');
            $order_model=M('order');
            $order_goods_model=M('order_goods');
            $db_user_address=M('user_address');
            $region_model=M('region');
         if($coupon_id)
         {
            $coupon_money=M('coupon')
                ->where(['id'=>$coupon_id])
                ->field('money')
                ->find()['money'];
         }
            //获取得大订单号
            $result=$order_model
                ->where(array('id'=>$order_id))
                ->field(
                    'id,order_sn_id,address_id,remarks,pay_type,order_type,price_sum,pay_time,shipping,shipping_monery,create_time,pay_time,delivery_time'
                )
                ->find();
           if($result['order_type']==4)
           {
               $re=M('order_goods as a')
                   ->join(
                       'db_integral_goods as b on b.goods_id=a.goods_id'
                   )
                   ->where(['a.order_id'=>$order_id])
                   ->field('b.integral')
                   ->find();
           }
        $result['coupon_money']=$coupon_money;
            //根据大订单号获取小订单号
            $list=$order_goods_model
                ->where(
                    array('order_id'=>$result['id'])
                )
                ->field(
                    'goods_id,goods_num,goods_price'
                )
                ->select();
            foreach($list as $k=>$vo)
            {
               $list[$k]['img']=$this->selfImg($goods_model,$goods_images_model,$vo['goods_id']);
               $list[$k]['attribute']=$this->selfAttribute($goods_model,$vo['goods_id']);
                if($result['order_type']==4)
                {
                    $list[$k]['integral']=$re['integral'];
                }
                if(!empty($vo['goods_id']))
                {
                $list[$k]['goods_title']=$goods_model
                    ->where(['id'=>$vo['goods_id']])
                    ->field('title')
                    ->find()['title'];
                }
            }
           //拼接收货地址
           $exp_name = M('express')->where(['id'=>$result['exp_id']])->getField('name');
           $result['shipping'] = $exp_name . ' : ' . $result['express_id'];

            $result['child']=$list;
            //获取收货人详情地址信息等
            $address=$db_user_address
                ->field(
                    'realname,mobile,prov,city,dist,address'
                )
                ->where(
                    array('id'=>$result['address_id'])
                )
                ->find();
            $address['prov']=$region_model->where(array('id'=>$address['prov']))->getField('name');
            $address['city']=$region_model->where(array('id'=>$address['city']))->getField('name');
            $address['dist']=$region_model->where(array('id'=>$address['dist']))->getField('name');
            $result['address_id']=$address;
           //获取发票类型
           $result['invoice']=M('invoice')
               ->where(
                   ['order_id'=>$order_id]
               )
               ->getField('invoice_type');
           if($result['pay_type']=="0"){
               $result['pay_type_z']='未支付';
           }elseif($result['pay_type']=="1"){
               $result['pay_type_z']='微信支付';
           }elseif($result['pay_type']=="2"){
               $result['pay_type_z']='支付宝支付';
           }elseif($result['pay_type']=="3"){
               $result['pay_type_z']='银联支付';
           }elseif($result['pay_type']=="4"){
               $result['pay_type_z']='余额支付';
           }
            if(!empty($result))
                $this->returnMessage(1,'获取成功',$result);
            else
                $this->returnMessage(0,'暂无数据',"");
       }
    }
    /**
     * 待处理订单--申请退款
     */
    public function refund(){
        if(IS_POST){
        $order_id=I('post.order_id');
        $order=M('order')
            ->where(['id'=>$order_id])
            ->field('id,create_time,price_sum')
            ->find();
        if($order['id'])
        $order_goods=M('order_goods')
            ->where(['order_id'=>$order['id']])
            ->field('goods_id,goods_num')
            ->select();
        if($order_goods){
            $goods_model=M('goods');
            $goods_images_model=M('goods_images');
            foreach($order_goods as $k=>$v)
            {
             $good=$goods_model
                 ->where(['id'=>$v['goods_id']])
                 ->field('title,p_id')
                 ->find();
             if($good) $image=$goods_images_model
                 ->where(['goods_id'=>$good['p_id']])
                 ->find()['pic_url'];
                $order_goods[$k]['title']=$good['title'];
                $order_goods[$k]['pic_url']=$image;
            }
        }
          $order['goods']=$order_goods;
        $this->returnMessage(1,'返回成功',$order);
     }
    }

    /**
     *
     * 申请退款提交
     */
    public function refundSubmit(){
           $data['order_id']=I('post.order_id');
           $data['price']=I('post.price');
           $data['user_id']=zhong_decrypt(I('post.app_user_id'));
           $data['tuihuo_case']=I('post.case');
           $data['type']=2;
           $data['create_time']=time();
        if(!empty($_FILES)){//有图片上传
            $info=$this->upload('refund');
            if($info['status']==0) $this->returnMessage(0,'新增错误',$info['msg']);
            if($info['status']==1){//图片上传成功
                $info=$info['info'];
                $address=array();
                foreach($info as $vo){
                    $address[]='/Uploads'.$vo['savepath'].$vo['savename'];
                }
                $data['apply_img']=implode("$",$address);
            }
            // /Uploads/apply/2017-06-16/594351e45507d.jpeg$/Uploads/apply/2017-06-16/594351e4567ed.png$/Uploads/apply/2017-06-16/594351e457b75.png
            $re=M('order_return_goods')->add($data);
            if($re){
                $this->returnMessage(1,'申请成功','');
                if($data['order_id']){
                    $return_goods['status']=5;
                 M('order_goods')
                     ->where(['order_id'=>$data['order_id']])
                     ->save($return_goods);
                    }
                }
                }
        }
    /**
     * 再次购买
     */
    public function buyAgain(){
        if(IS_POST) {
           $user_id = zhong_decrypt(I('post.app_user_id'));
           $goods = json_decode($_POST['goods'], true);
//        $goods = array(
//                   array('goods_id' => 60, 'goods_num' => 2,'goods_price'=>10,'price_new'=>100,'warehouse_id'=>1,),
//                   array('goods_id' => 61, 'goods_num' => 3,'goods_price'=>10,'price_new'=>1012),
//               );
            $model = M('goods_cart');
            $data['user_id'] = $user_id;//用户id
        $goodsmodel=M('goods');
        $goodscart=M('goods_cart');
            foreach ($goods as $k => $v)
            {
                $goods_num = $v['goods_num'];
                $data['goods_num'] = $v['goods_num'];//商品数量
                $data['price_new'] = $v['price_new'];//套餐价格
                $data['goods_id'] = $v['goods_id'];//商品id
                $d_integral = $goodsmodel
                    ->where(array('id' => $v['goods_id']))
                    ->getField('d_integral');
                $data['integral_rebate'] = $d_integral;//返利积分
                $data['create_time'] = time();//创建时间
                $ware_id=$goodscart
                    ->where(['goods_id'=>$v['goods_id']])
                    ->getField('ware_id');
                $data['ware_id']=$ware_id?$ware_id:1;
                $find = $model
                    ->where(
                        array(
                            'goods_id' => $v['goods_id'],
                            'user_id' => $user_id,
                            'is_del' => 0
                        )
                    )
                    ->find();
                if (empty($find)) {//购物车里还没有此商品
                    $re = $model->add($data);
                } else {
                    $num['goods_num'] = $find['goods_num'] + $goods_num;
                    $re = $model
                        ->where(
                            array(
                                'goods_id' => $v['goods_id'],
                                'user_id' => $user_id,
                                'is_del' => 0
                            )
                        )
                        ->save($num);
                }
            }
            if ($re)
                $this->returnMessage(1, '添加成功', "");
            else
                $this->returnMessage(0, '添加失败', "");
        }
    }
    /**
     * 删除订单
     */

public function deleteOrder(){
    if(IS_POST){
        $order_id=I('post.order_id');
        $user_id=zhong_decrypt(I('post.app_user_id'));
        $re=M('order')
            ->where(
                array(
                    'id'=>$order_id,
                    'user_id'=>$user_id
                )
            )
            ->delete();

        if($re){
            $this->returnMessage(1,'删除成功','');
            M('order_goods')
                ->where(
                    array(
                        'order_id'=>$order_id,
                        'user_id'=>$user_id
                    )
                )
                ->delete();
        }else{
            $this->returnMessage(0,'删除失败','');
        }
    }
}
/**
 * 发票
 */
    public function invoice(){
        if (IS_POST) {
            $user_id =zhong_decrypt(I('post.app_user_id'));
            $invoice = M('invoice')
                ->where(['user_id'=>$user_id])
                ->order('id DESC')
                ->field(
                    'invoice_title,invoice_type,content'
                )
                ->limit(1)
                ->select();
            if (!empty($invoice))
            {
                $this->returnMessage(1, '返回成功', $invoice);
            } else
            {
                $this->returnMessage(0, '尚未设置开票信息', "");
            }
        }
    }
/**
 * 取消订单
 */

  public  function order_cancel(){
      $order_id=I('post.order_id');
      $or['order_status']='-1';
      $re=M('order')
          ->where([
              'id'=>$order_id
          ])
          ->save($or);
      if($re)
          $this->returnMessage(1,'取消成功','');
      else
          $this->returnMessage(0,'取消失败','');
  }

    //取消订单，逻辑删除订单
    public function setOrderStatus (){
        $user_id=zhong_decrypt(I('post.app_user_id'));
        $order_id = I('post.order_id');
        $status = I('post.status');
        $post = $_POST;
        $post['user_id'] = $user_id;
        if($status == '-1'){
            //改变订单状态
            $res = M('order')->where(['id'=>$order_id,'user_id'=>$user_id])->setField('order_status',-1);
            //退回库存,一个订单只有一个商品
            $goods = M('order_goods')->field('goods_id,goods_num')->where(['order_id'=>$order_id])->select()[0];
            $good_pid = M('goods')->where(['id'=>$goods['goods_id']])->getField('p_id');
            $res = M('goods')->where(['id'=>$goods['goods_id']])->setInc('stock',$goods['goods_num']);
            $p_goods = M('goods')->where(['id'=>$good_pid])->setInc('stock',$goods['goods_num']);
            $_goods = M('spec_goods_price')->where(['goods_id'=>$goods['goods_id']])->setDec('store_count',$goods['goods_num']);
        }else if($status == '-2'){
            $res = M('order')->where(['id'=>$order_id])->setField('status',1);
        }

        if($res){
            if($status == '-1'){
                $this->returnMessage(1,'取消成功',$post);
            }
            $this->returnMessage(1,'删除成功',$post);
        }else{
            $this->returnMessage(0,'',$post);
        }

    }
}
