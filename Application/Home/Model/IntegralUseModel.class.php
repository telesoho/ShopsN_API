<?php
/**
 * Created by PhpStorm.
 * User: Yisu-Administrator
 * Date: 2017/6/8
 * Time: 15:02
 */
/**
 * 积分模型
 */
namespace Home\Model;
use Think\Model;
class IntegralUseModel extends Model{
    /**
     * @param $user_id
     * @return array
     * 我的积分
     */
    public function integral($user_id)
    {
        $total=M('user')->where(['id'=>$user_id])->getField('integral');
        //积分详情按时间排序
        $list = M('integral_use')
            ->field('remarks,integral,type,trading_time')
            ->where(['user_id'=> $user_id])
            ->order('trading_time DESC')
            ->select();//1.可用;2.已用;3.过期
        foreach ($list as $k => $v) {
            if ($v['type'] == 1) {
                $list[$k]['integral'] = '+' . $v['integral'];
            } else {
                $list[$k]['integral'] = '-' . $v['integral'];
            }
        }
        $data = array(
            'sum' => $total,
            'list' => $list
        );
        return $data;
    }
    /**
     * 赠送积分更新用户积分
     */
    public function _updateIntegral($user_id,$integral){
        $data['integral']=$integral;
        M('user')->where(['id'=>$user_id])->save($data);
    }

    /**
     * 添加积分记录
     */
    public function _addIntegralRecord($orders_num){
        $order = M('order')->where(['id'=>$orders_num])->field('user_id,order_type')->find();
        $integral=M('order_goods')->where(['order_id'=>$orders_num])->field('goods_id,goods_num')->select();
        $goodsmodel=M('goods');
        $integral_use=M('integral_use');
        $use=0;
        $integral_data['user_id'] = $order['user_id'];
        $integral_data['trading_time'] = time();
        if($order['order_type']==4){//积分订单
            $integral_data['remarks'] = '支付抵扣';
            $integral_data['type'] = 2;//支出
            $integral_data['status'] = 2;
        }else{
            $integral_data['remarks'] = '商品返积分';
            $integral_data['type'] = 1;//收入
            $integral_data['status'] =1;
        }
        $integral_goods=M('integral_goods');
        foreach($integral as $ko=>$vo){
            if($order['order_type']==4) {
                $integralnum=$integral_goods->where(['goods_id' => $vo['goods_id']])->getField('integral')* $vo['goods_num'];
                $use+=$integral_goods->where(['goods_id' => $vo['goods_id']])->getField('integral')* $vo['goods_num'];
            }else {
                $integralnum = $goodsmodel->where(['id' => $vo['goods_id']])->getField('d_integral') * $vo['goods_num'];
                $use += $goodsmodel->where(['id' => $vo['goods_id']])->getField('d_integral') * $vo['goods_num'];
            }
            $integral_data['goods_id'] = $vo['goods_id'];
            $integral_data['integral'] = $integralnum;
            $integral_use->add($integral_data);//插入现金券记录
        }
        $integralsum=(new IntegralUseModel())->integral($order['user_id'])['sum'];
        if($order['order_type']==4){
            $inte=$integralsum-$use;
        }else{
            $inte=$integralsum+$use;
        }
        $this->_updateIntegral($order['user_id'],$inte);
    }


}