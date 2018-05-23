<?php
/**
 * Created by PhpStorm.
 * User: Yisu-Administrator
 * Date: 2017/5/22
 * Time: 13:32
 */

namespace Home\Controller;
use  Think\Controller;
use Think\Think;

/**
 * Class SuppliesController
 * @package Home\Controller
 * 耗材租赁机控制器
 */

class SuppliesController extends CommonController
{
/**
 *补充耗材记录
 */
public  function  supply_record(){
       $user_id=I('get.app_user_id');
       $count=M('supplementary_supplies')
           ->field(
               'add_time,consumables,num,status'
           )
           ->where(
               ['user_id'=>$user_id]
           )
           ->count();
       $page=new \Think\Page($count,15);
       $show=$page->show();
       $detail=M('supplementary_supplies')
           ->field(
               'add_time,consumables,num,status'
           )
           ->where(['user_id'=>$user_id])
           ->limit($page->firstRow,$page->listRows)
           ->select();
      if($detail)
          $this->returnMessage(1,'返回成功',$detail);
      else
          $this->returnMessage(0,'返回失败','暂无数据');

}
    /**
     * 打印租赁机-抄表记录
     */
    public function copy_table(){
        $printer_id=I('get.printer_id');
        $copy_table=M('printer_meter')
            ->where([
                'printer_id'=>$printer_id
            ])
            ->field(
                'meter_time,meter_reading,colour_num,black_num,pay_price,pay_status'
            )
            ->select();
        if($copy_table)
            $this->returnMessage(1,'返回成功',$copy_table);
        else
            $this->returnMessage(0,'返回失败','');
    }
    /**
     * 打印机租赁
     */
    public function lease(){
        $user_id=I('get.app_user_id');
        $count=M('printer_rental')
            ->field('id')
            ->where([
                'user_id'=>$user_id
            ])
            ->select();
        $page=new \Think\Page($count,5);
        $field='start_time,due_time,goods_id,addtime,status,deposit,pay_type';
        $printer_detail=M('printer_rental')
            ->field($field)
            ->where([
                'user_id'=>$user_id
            ])
            ->limit($page->firstRow,$page->listRows)
            ->select();
        if($printer_detail)
            $this->returnMessage(1,'返回成功',$printer_detail);
        else
            $this->returnMessage(0,'返回失败',"");
    }
/**
 * 补充耗材需求
 */
    public function supply_need(){
        if(IS_POST) {
            $data['user_id'] = I('post.app_user_id');
            $data['printer_id'] = I('post.printer_id');//租赁打印机表id
            $data['add_time'] = time();
            $data['consumables'] = I('post.consumables');
            $data['num'] = I('post.num');
            $data['remark'] = I('post.remark');
            $re=M('supplementary_supplies')->add($data);
            if($re)
                $this->returnMessage(1,'提交成功','');
            else
                $this->returnMessage(0,'提交失败','');
        }

    }
    /**
     * 租赁合同详情
     */
    public function supply_detail(){
        $goods_id=I('get.goods_id');
        $supply_detail=M('printer_rental')
            ->where(
                ['goods_id'=>$goods_id]
            )
            ->field(
                'id,title,start_time,due_time,lease_price,black_price,colour_price,deposit,status'
            )
            ->find();
        $copy_table=M('printer_meter')
            ->where(
                ['printer_id'=>$supply_detail['id']]
            )
            ->field(
                'meter_time,meter_reading,colour_num,black_num,pay_price,pay_status'
            )
            ->select();
         $data=array(
             'supply_detail' =>$supply_detail,
             'copy_table'=>$copy_table
         );
        $this->returnMessage(1,'返回成功',$data);
    }

}