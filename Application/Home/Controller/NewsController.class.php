<?php
/**
 * Created by PhpStorm.
 * User: Yisu-Administrator
 * Date: 2017/5/27
 * Time: 13:01
 */
namespace Home\Controller;
use Think\Controller;
use Home\Model\NewsModel;
 class NewsController extends CommonController
 {
     /**
      * 发送消息
      */
     /*  public function add_news()
       {
           $user_id = zhong_decrypt(I('post.app_user_id'));
           $receiver = I('post.receiver');
           $theme = I('post.theme');
           $content = I('post.content');
           if (!empty($_FILES)) {//有图片上传
               $info = $this->upload('news_image');
               if ($info['status'] == 0) $this->returnMessage(0, '新增错误', $info['msg']);
               if ($info['status'] == 1) {//图片上传成功
                   $info = $info['info'];
                   $address = array();
                   foreach ($info as $vo) {
                       $address[] = '/Uploads' . $vo['savepath'] . $vo['savename'];
                   }
                   $image = implode("$", $address);
               }
           }
           $re = (new NewsModel())->_add_news($user_id, $receiver, $theme, $content, $image);
           if ($re) $this->returnMessage(1, '发送成功', "");
           else $this->returnMessage(0, '发送失败', "");
       }*/

     /**
      * 我的消息-统计
      */
     /* public function news_count()
      {
          $user_id =zhong_decrypt(I('post.app_user_id'));
          $outbox = M('news')->where('sender=%s and type=%s', $user_id, 0)->count();
          $inbox = M('news')->where('receiver=%s and type=%s', $user_id, 0)->count();
          $system_news = M('news')->where('type=%s', 1)->count();
          $data = array(
              'outbox' => $outbox,
              'inbox' => $inbox,
              'system_news' => $system_news
          );
          if ($data) $this->returnMessage(1, '返回成功', $data);
      }*/
     /**
      * 我的消息列表
      */
     public function my_news()
     {
         $user_id =zhong_decrypt(I('post.app_user_id'));
         $data = (new NewsModel())->_my_news($user_id);
         if ($data) $this->returnMessage(1, '返回成功', $data);
         else $this->returnMessage(0, '暂无数据', "");
     }
     /**
      * 消息内容
      */
     public function content(){
        $new_id=I('post.news_id');
        $re= (new NewsModel())->_news_content($new_id);
        if($re) $this->returnMessage(1,'返回成功',$re);
         else $this->returnMessage(0,'暂无数据',"");
     }
 }