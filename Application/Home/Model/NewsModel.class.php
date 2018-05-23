<?php
/**
 * Created by PhpStorm.
 * User: Yisu-Administrator
 * Date: 2017/5/27
 * Time: 11:02
 */
namespace Home\Model;
use Think\Model;
/**
 * Class InformationModel
 * 消息模型
 */
class NewsModel extends Model{
/**
 * 我的消息
 */
public function _my_news($user_id){
        $news=M('news');
        $count=$news->field('id,news_info,create_time,theme')->where('user_id=%s',$user_id)->count();
       $page=new \Think\Page($count,C('page_size'));
    $news_list=$news->field('id,news_info,create_time,theme')->where('user_id=%s',$user_id)->limit($page->firstRow,$page->listRows)->order('create_time DESC')->select();
    return $news_list;
}
    /**
     * 消息内容
     */
    public function _news_content($news_id){
        $content= M('news')->where('id=%s',$news_id)->field('theme,create_time,news_info')->find();
        return $content;
    }
}
