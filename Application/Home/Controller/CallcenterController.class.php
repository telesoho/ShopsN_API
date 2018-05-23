<?php
/**
 *客户服务公告
 */
namespace Home\Controller;
use Think\Controller;
class CallcenterController extends CommonController{
    /**
     *客户服务公告
     */
    public function Announcement(){
         $announcement=M('announcement')->order('rand()')->field('id,title')->limit(3)->select();
        if($announcement){
            $this->returnMessage(1,'返回成功',$announcement);
        }else{
            $this->returnMessage(0,'暂无数据',"");
        }
    }
}