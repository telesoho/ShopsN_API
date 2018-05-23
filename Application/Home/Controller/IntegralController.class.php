<?php
/**
 * Created by PhpStorm.
 * User: Yisu-Administrator
 * Date: 2017/6/8
 * Time: 13:20
 */
/**
 * 积分税换控制器
 */
namespace Home\Controller;
use Think\Controller;
use Home\Model\IntegralUseModel;
use Home\Model\GoodsModel;
class IntegralController extends CommonController{
    /**
     * 我的积分
     */
    public function integral(){
        if (IS_POST) {
            $user_id=zhong_decrypt(I('post.app_user_id'));
            $data= (new IntegralUseModel())->integral($user_id);
            if (!empty($data))
                $this->returnMessage(1, '返回成功', $data);
            else
                $this->returnMessage(0, '暂无数据', "");
        }
    }
    /**
     * 积分税换
     */
    public  function  integral_goods(){
        if(IS_POST) {
            $num =I('post.num');
            $number = explode("-", $num);
            $integral_goodsModel=M('integral_goods');
            if (empty($num))
            {
                $count =$integral_goodsModel
                    ->where(['status'=> 1])
                    ->field('id')
                    ->count();
                $page = new \Think\Page($count, C('page_size'));
                $integral_goods = $integral_goodsModel
                    ->where(['status'=>1])
                    ->field('goods_id,integral,money')
                    ->order('id desc')
                    ->limit($page->firstRow, $page->listRows)
                    ->select();
            } else
            {
                $condition['status'] = 1;
                $condition['integral'] = array('between', "$number[0],$number[1]");
                $count = $integral_goodsModel
                    ->where($condition)
                    ->field('id')
                    ->count();
                $page = new \Think\Page($count, C('page_size'));
                $integral_goods =$integral_goodsModel
                    ->where($condition)
                    ->field('goods_id,integral')
                    ->order('id desc')
                    ->limit($page->firstRow, $page->listRows)
                    ->select();
            }
            $goods_model=M('goods');
            $goods_images_model=M('goods_images');
            foreach ($integral_goods as $k => $v)
            {
                $goods = $goods_model
                    ->where(['id'=>$v['goods_id']])
                    ->field('title,price_market,p_id')
                    ->find();
                $image =$goods_images_model
                    ->where(array('goods_id' => $goods['p_id']))
                    ->field('pic_url')
                    ->find();
                $integral_goods[$k]['pic_url'] = $image['pic_url'];
                $integral_goods[$k]['title'] = $goods['title'];
                $integral_goods[$k]['price_market'] = $goods['price_market'];
            }
            if (!empty($integral_goods))
            {
                $this->returnMessage(1, '返回成功', $integral_goods);
            } else
            {
                $this->returnMessage(0, '暂无数据', "");
            }
        }
    }

    /**
 *积分商品详情
 */
    public function integral_goodsdetail(){
        if(IS_POST){
            $goods_id=I('post.goods_id');
            $integral=M('integral_goods')
                ->where(['goods_id'=>$goods_id])
                ->getField('integral');
            $goods=M('goods')
                ->where(['id'=>$goods_id])
                ->field('title,price_market,p_id,brand_id,code,goods_type')
                ->find();
        if($goods)
        {
            $brand_name=M('brand')
                ->where(['id'=>$goods['brand_id']])
                ->getField('brand_name');
            $image1=M('goods_images')
                ->where(['goods_id'=>$goods['p_id']])
                ->field('pic_url')
                ->select();
            foreach($image1 as $k=>$v)
            {
                $image[]=$v['pic_url'];
            }
           // $join='db_goods_spec ON db_goods_spec_item.spec_id=db_goods_spec.id';
           // $goods_spec=M('goods_spec_item')->join($join)->where('type_id=%s',$goods['goods_type'])->field('item,name')->find();
        }
            $goods['integral']=$integral;
            $goods['brand_name']=$brand_name;
        //获取商品的属性规格及对应的属性值
        $attr=M('spec_goods_price')
            ->where(array('goods_id'=>$goods_id))
            ->getField('key');
        $attr=explode('_',$attr);
        $condition['db_goods_spec_item.id']=array('in',$attr);
        $join='db_goods_spec ON db_goods_spec.id=db_goods_spec_item.spec_id';
        $spec=M('goods_spec_item')
            ->join($join)
            ->where($condition)
            ->field('name,item')
            ->select();
        $data=array(
            'goods'=>$goods,
            'pic_url'=>$image,
            'spec'=>$spec
        );
           $this->returnMessage(1,'返回成功',$data);
      }
    }

 /**
* 点击我要税换调用商品去结算接口
*/

/**
 * 积分订单生成
 */
    public function integral_order(){
        if(IS_POST){
            $order_goods_model = M('order_goods');
            $order_model = M('order');
            $spec_goods_price=M('spec_goods_price');
            $user_id=zhong_decrypt(I('post.app_user_id'));
            $integral=I('post.integral');//商品所需积分
            $sum=(new IntegralUseModel())->integral($user_id)['sum'];
            $integeal_sum=intval(str_replace('+','',$sum));//积分总数
            if($integral>$integeal_sum)
            {
                $this->returnMessage(0,'积分不够不能税换',"");
            }
            $order_sn=$this->toGUID();
            $data['order_sn_id'] = $order_sn;   //订单单号
            $price_sum=I('post.price_sum');
            $data['price_sum'] =$price_sum; //总金额
            $data['address_id'] = I('post.address_id');//收货地址
            $data['user_id'] = $user_id; //购买者
            $data['create_time'] = time();          //创建时间
            if($price_sum==0){
                $data['order_status'] =1;
            }else{
                $data['order_status']=0;
            }
            //默认为0：未支付状态     //订单状态
            // $data['pay_type'] = I('post.pay_type');//支付类型
            $data['shipping']=I('post.shipping');//配送方式
            $data['remarks'] = I('post.remarks');//备注
            $data['translate'] =I('post.translate');//是否需要发票
            $data['order_type']=4;
            //若需要发票,1为需要，0为不需要
            if(I('post.translate')==1)
            {
                $invoice=json_decode($_POST['invoice'],true);
                foreach($invoice as $key=>$value)
                {
                    $bill['order_id'] = $order_sn;
                    $bill['invoice_title'] = $value['invoice_title'];
                    $bill['invoice_type'] = $value['invoice_type'];
                    $bill['create_time'] = time();
                    $bill['user_id'] = $user_id;
                    $bill['remarks'] = $value['content'];
                    M('invoice')->add($bill);
                }
            }
            $shipping_monery=I('post.shipping_monery');
            $data['shipping_monery'] =$shipping_monery;//订单运费
            //$data['exp_id'] = I('post.exp_id');//快递编号？？？？
            $data['platform']=2;//1:代表pc,2:代表app
            $order_big = $order_model->add($data);
            if ($order_big)
            {//添加成功--开始生成小订单号
                $goods=json_decode($_POST['goods'],true);
                /*$goods = array(
                    array('id' => 1097, 'num' => 2,'goods_price'=>10),
                    array('id' => 1094, 'num' => 3,'goods_price'=>10),
                );*/
                //1:到仓库里去查找第一个仓库id。如果为立即购买则因是一个一维数组：
                $integral_use=M('integral_use');
                foreach($goods as $k=>$v)
                {
                    //$storehouse_id = M('storehouse')->find();
                    //$order['ware_id'] = $storehouse_id['id'];//仓库号
                    $order['order_id'] = $order_big;//订单号
                    $order['goods_id'] = $v['id'];//商品id
                    $order['goods_num'] = $v['num'];//商品
                    $order['goods_price'] = $v['goods_price'];//商品价格
                    $order['space_id'] = $spec_goods_price->where(array('id' => $v['id']))->getField('id');//商品规格
                    $order['user_id'] = $user_id;
                    $order_small = $order_goods_model->add($order);
                    $inte['goods_id']=$v['id'];
                    $inte['trading_time']=time();
                    $integral_use->add($inte);
                }
                if($order_small){
                    $this->returnMessage(1,'下单成功',$order_big);
                } else{
                    $this->returnMessage(0,'下单失败','');
                }
            }else{
                $this->returnMessage(0,'创建订单失败','');
            }
        }
    }
    /**
     * 收银台当前积分
     */
    public function cashier(){
        $user_id=zhong_decrypt(I('post.app_user_id'));
        $r=(new IntegralUseModel())->integral($user_id)['sum'];
        $this->returnMessage(1,'成功',$r);
    }

    /**
     *积分支付
     */
    function payIntegral(){
        $user_id=zhong_decrypt(I('post.app_user_id'));
        $integral=I('post.integral');
        $order_id=I('post.order_id');
        $user=M('user')->where(['id'=>$user_id])->getField('integral');
        if($integral>$user){
            $this->returnMessage(0,'用户积分不够','');
        }else{
            (new IntegralUseModel())->_addIntegralRecord($order_id);
            $this->returnMessage(1,'支付成功','');
        }
    }
}
