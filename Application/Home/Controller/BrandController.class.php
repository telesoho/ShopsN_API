<?php
/**
 * Created by PhpStorm.
 * User: Yisu-Administrator
 * Date: 2017/7/6
 * Time: 17:54
 */

namespace Home\Controller;
use Home\Model\GoodsModel;
use Think\Controller;

/****品牌控制器
 * Class BrandController
 * @package Home\Controller
 */
class BrandController extends CommonController{
    /**
     * 品牌列表
     */
    public function brandList(){
        $brand_name=I('get.brand_name');
        if($brand_name){
            $sql="select id, brand_name,letter from db_brand where brand_name LIKE '%".$brand_name."%' ";
            $brand = M()->query($sql);
            $brand?$this->returnMessage('1','返回成功',$brand):$this->returnMessage(0,'没有此品牌','');
        }else {
            $sql = "select brand_name,letter from db_brand  group by letter ORDER BY letter ";
            $brand = M()->query($sql);
            $arr = array();
            foreach ($brand as $k => $v) {
                $arr[$k]['letter'] = $v['letter'];
                $sql1 = "select id,brand_name,brand_logo,letter from db_brand where letter='" . $v['letter'] . "'";
                $re = M()->query($sql1);
                foreach ($re as $k2 => $v2) {
                    $arr[$k]['value'][$k2]['brand_name'] = $v2['brand_name'];
                    $arr[$k]['value'][$k2]['id'] = $v2['id'];
                    $arr[$k]['value'][$k2]['brand_logo'] = $v2['brand_logo'];
                }
            }
            if (!empty($arr)) $this->returnMessage(1, '返回成功', $arr);
        }
    }
/**
 * 品牌店详情
 *
 */
public  function brandDetail()
{
    if (IS_POST) {
        $brand_id = I('post.id');
        $page=I('post.page');
    if (I('post.sort')) $flag =I('post.sort');
        $brand = M('brand')->field('brand_name,brand_description,brand_logo,brand_banner')->where(['id'=>$brand_id])->find();
        if (!empty($flag)) {
            switch ($flag) {
                case 1:  //销量由高到低
                    $order = 'sales_sum DESC';
                    break;
                case 2:  //销量由低到高
                    $order = 'sales_sum ASC';
                    break;
                case 3:   //价格由高到低
                    $order = 'price_market DESC';
                    break;
                case 4:  //价格由低到高
                    $order = 'price_market ASC';
                    break;
                case 5:
                    $order = 'sales_sum DESC';
                    break;
            }
        } else {
            $order = '';
        }
        $goods = M('goods')->where(['brand_id'=>$brand_id,'p_id'=>['NEQ',0]])->field('id,title,price_market')->order($order)->page($page,C('page_size'))->select();
        $goods_images_model=M('goods_images');
        $order_comment_model=M('order_comment');
        $order_goods_model=M('order_goods');
        foreach ($goods as $k => $v) {
            $goods[$k]['pic_url'] =$goods_images_model->where(['goods_id'=>$v['id']])->getField('pic_url');
            $goods[$k]['comment'] = $order_comment_model->where(['goods_id='=> $v['id']])->count();
            $goods[$k]['trade'] = $order_goods_model->where(['goods_id' => $v['id'], 'status' => 1])->count();
        }
        $data = array(
            'brand' => $brand,
            'goods' => $goods
        );
        if (!empty($data)) $this->returnMessage(1, '返回成功', $data);
    }

   }

}