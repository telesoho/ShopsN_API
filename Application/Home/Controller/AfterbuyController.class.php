<?php
/**
 * Created by PhpStorm.
 * User: Yisu-Administrator
 * Date: 2017/6/1
 * Time: 14:00
 */
namespace Home\Controller;
use Think\Controller;
use Home\Model\ExpressModel;
/**
 * Class AfterbuyController
 * @package Home\Controller
 * 商品售后后续
 */
class AfterbuyController extends CommonController {
    /**
     * 物流查询
     */
    public function express(){
        if(IS_POST) {
            $order_id = I('post.order_id');
            $exp_id = M('order')
                ->where(
                    ['id'=>$order_id]
                )
                ->field('exp_id,express_id')
                ->find();
            if (!empty($exp_id['exp_id'])) {
                $code = M('express')
                    ->where(
                        ['id'=>$exp_id['exp_id']]
                    )
                    ->getField('code');//快递公司编号
                $data = (new ExpressModel())->getExpress($code, $exp_id['express_id']);
            }
            if ($data)
                $this->returnMessage(1, '返回成功', $data);
            else
                $this->returnMessage(0, '暂无数据', "");
        }
    }
    /**
     * 确认收货
     */
   function goods_receipt(){
        $order_id = I('post.order_id');
        $user_id = I('post.app_user_id');
        // $user_id = zhong_encrypt($user_id);
        $user_id = zhong_decrypt($user_id);

        $model = M();

        $model->startTrans();

        try {
            $order_update = $model->table('__ORDER__')
                ->where(
                    ['id'=>$order_id]
                )
                ->save(['order_status'=>4]);
    
            $order_goods_update = $model->table('__ORDER_GOODS__')
                ->where(
                    array('order_id'=>$order_id,'user_id'=>$user_id)
                )
                ->save(['status'=>4]);
            $model->commit();
            $this->returnMessage(1,'返回成功',"");
        } catch (Exception $e) {
            $model->rollback();
            $this->returnMessage(0,'返回失败',"");
        }
    }
}