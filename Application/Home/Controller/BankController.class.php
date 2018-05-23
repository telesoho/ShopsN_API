<?php
/**
 * Created by PhpStorm.
 * User: Yisu-Administrator
 * Date: 2017/5/26
 * Time: 11:13
 */

namespace Home\Controller;
use Think\Controller;

/**
 * 银行卡相关控制器
 * Class BankController
 * @package Home\Controller
 */
class BankController extends CommonController{
    /**
     * 添加银行卡
     */
  public function add_bankcard(){
      if (IS_POST) {
          $user_id=zhong_decrypt(I('post.app_user_id'));
          $data['user_id']=$user_id;
          $data['realname']=I('post.name');
          $card_num=trim(I('post.card_num'));
          $data['card_num']=$card_num;
          $data['belong']=I('post.bank_name');
          $data['create_time']=time();
          $re=M('bank_card')->where('user_id=%s and card_num=%s',$user_id,$card_num)->find();
          if($re){
              $this->returnMessage(0,'此卡已添加过','');
          }else{
              M('bank_card')->add($data);
              $this->returnMessage(1,'添加成功','');
          }
      }
  }
    /**
     * 删除银行卡
     */
    public function delete_bankcard(){
        if(IS_POST){
            $user_id=zhong_decrypt(I('post.app_user_id'));
            $card_num=trim(I('post.card_num'));
            $re=M('bank_card')->where('user_id=%s and card_num=%s',$user_id,$card_num)->delete();
            if($re){
                $this->returnMessage(1,'删除成功',"");
            }else{
                $this->returnMessage(0,'删除失败',"");
            }
        }
    }
    /**
     * 银行卡管理
     */
    public function card_list(){
        if(IS_POST){
            $user_id=zhong_decrypt(I('post.app_user_id'));
            $data=M('bank_card')->where('user_id=%s',$user_id)->field('id,belong,card_num')->select();
            if(!empty($data)){
                $this->returnMessage(1,'返回成功',$data);
            }else{
                $this->returnMessage(0,'暂无数据',"");
            }
        }
    }
/**
 * 修改银行卡
 */
    public  function update_bankcard(){
        if(IS_POST){
            $bank_id=I('post.bank_id');
            $data['realname']=I('post.name');
            $card_num=trim(I('post.card_num'));
            $data['card_num']=$card_num;
            $data['belong']=I('post.bank_name');
            $data['create_time']=time();
            $re= M('bank_card')->where('id=%s',$bank_id)->save($data);
            if($re) $this->returnMessage(1,'修改成功',"");
            else $this->returnMessage(0,'修改失败',"");
        }
    }
}