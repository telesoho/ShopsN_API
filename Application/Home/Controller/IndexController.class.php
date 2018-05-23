<?php
namespace Home\Controller;

use Home\Model\ClassModel;
use Home\Model\GoodsModel;
use Think\Controller;

class IndexController extends CommonController
{


    //public function __construct()
    //{
    //    parent:: __construct();
    //
    //
    //}




    public function home()
    {
        //检测微信登录
        $this->checkWxLogin();

        $adModel = M( 'ad' );
        //获取首页banner图
        $banner = $adModel
            ->field( 'id,title,pic_url' )
            ->where( array( 'ad_space_id' => 1 ) )
            ->limit( 3 )
            ->select();
        //读取配置里的的图片访问域名地址
        $img_url = C( 'img_url' );
        //分类板块
        $nav_top=M('nav')
            ->field('nav_titile,link')
            ->limit(8)
            ->select();
        //首页公告
        $announcement = M( 'announcement' )
            ->field( 'id,title' )
            ->where( array( 'status' => 1 ) )
            ->order( 'sort' )
            ->limit( 3 )
            ->select();
        //最新促销
        $GoodsModel     = new GoodsModel();
        $status         = 2;
        $field          = 'id,title,p_id,price_market,price_member';
        $limit          = 8;
        $promotions     = $GoodsModel->_getstatus( $status,$field,$limit );
        $promotions_img = $adModel
            ->field( 'id,title,ad_link,pic_url' )
            ->where( [
                'ad_space_id' => 42
            ] )
            ->limit( 1 )
            ->select();
        //尾货清仓
        $poop_Clear   = M( 'poop_clearance' )
            ->field( 'goods_id,type_id,expression' )
            ->limit( 8 )
            ->select();
        $goods        = M( 'goods' );
        $goods_images = M( 'goods_images' );
        foreach ( $poop_Clear as $k => $v ) {
            $poop  = $goods
                ->field( 'id,p_id,title,price_market' )
                ->where( [ 'id' => $v[ 'goods_id' ],'p_id' => [ 'NEQ',0 ] ] )
                ->find();
            $image = $goods_images
                ->where(
                    [ 'goods_id' => $poop[ 'p_id' ] ]
                )
                ->getField( 'pic_url' );
            if ( $v[ 'type_id' ] == 1 || $v[ 'type_id' ] == 3 ) {
                $price = $poop[ 'price_market' ] * $v[ 'expression' ] / 10;

            } elseif ( $v[ 'type_id' ] == 2 ) {
                $price = $poop[ 'price_market' ] - $v[ 'expression' ];

            } else {
                $price = $poop[ 'price_market' ];
            }
            $clearance[]                       = $poop;
            $clearance[ $k ][ 'price_member' ] = sprintf( "%.2f",$price );
            $clearance[ $k ][ 'pic_url' ]      = $image;
        }
        $end_time      = M( 'system_config' )
            ->where( [
                'parent_key' => 'poop'
            ] )
            ->getField( 'config_value' );
        $p             = unserialize( $end_time );
        $time          = strtotime( $p[ 'end_time' ] );
        $poopClear_img = $adModel
            ->field(
                'id,title,ad_link,pic_url'
            )
            ->where( [
                'ad_space_id' => 43
            ] )
            ->limit( 1 )
            ->select();
//品牌馆
        $brand     = M( 'brand' )
            ->field( 'id,brand_logo' )
            ->order( 'recommend DESC' )
            ->limit( 12 )
            ->select();
        $brand_img = $adModel
            ->field( 'id,title,ad_link,pic_url' )
            ->where( array( 'ad_space_id' => 44 ) )
            ->limit( 1 )
            ->select();
        //积分商城
        $integral_top_img  = $adModel
            ->field(
                'id,title,ad_link,pic_url'
            )
            ->where( [
                'ad_space_id' => 10
            ] )
            ->limit( 1 )
            ->select();
        $status            = 3;
        $limit             = 3;
        $field             = 'db_goods.id,p_id,title';
        $integral          = $GoodsModel->_getstatus( $status,$field,$limit );
        $integral_foot_img = $adModel
            ->field(
                'id,title,ad_link,pic_url'
            )
            ->where( [
                'ad_space_id' => 12
            ] )
            ->limit( 1 )
            ->select();
        $goodsClassModel   = M( 'goods_class' );
        $ClassModel        = new ClassModel();
        //家用电器
        $fid            = $goodsClassModel
            ->where(
                array( 'class_name' => '家用电器' )
            )
            ->getField( 'id' );
        $appliances     = $ClassModel->_getcategory( intval( $fid ),4 );
        $appliances_img = $adModel
            ->field(
                'id,ad_link,pic_url'
            )
            ->where(
                [ 'ad_space_id' => 45 ]
            )
            ->limit( 1 )
            ->select();
        //手机数码
        $phone_fid         = $goodsClassModel
            ->where(
                array( 'class_name' => '手机数码' )
            )
            ->getField( 'id' );
        $phone_digital     = $ClassModel->_getcategory( intval( $phone_fid ),4 );
        $phone_digital_img = $adModel
            ->field( 'id,ad_link,pic_url' )
            ->where( [ 'ad_space_id' => 40 ] )
            ->limit( 1 )
            ->select();
        //电脑办公
        $computerid         = $goodsClassModel->where( array( 'class_name' => '电脑办公' ) )->getField( 'id' );
        $computerOffice     = $ClassModel->_getcategory( intval( $computerid ),4 );
        $computerOffice_img = $adModel
            ->field( 'id,ad_link,pic_url' )
            ->where( [ 'ad_space_id' => 46 ] )
            ->limit( 1 )
            ->select();
        $data               = array(
            'img_url'            => $img_url,
            'banner'             => $banner,
            'nav'                => $nav_top,
            'announcement'       => $announcement,
            'promotions'         => $promotions,
            'promotions_img'     => $promotions_img,
            'poopClear'          => $clearance,
            'endtime'            => $time,
            'poopClear_img'      => $poopClear_img,
            'brand'              => $brand,
            'brand_img'          => $brand_img,
            'integral'           => $integral,
            'integral_top_img'   => $integral_top_img,
            'integral_foot_img'  => $integral_foot_img,
            'appliances'         => $appliances,
            'appliances_img'     => $appliances_img,
            'phone_digital'      => $phone_digital,
            'phone_digital_img'  => $phone_digital_img,
            'computerOffice'     => $computerOffice,
            'computerOffice_img' => $computerOffice_img
        );
        if ( !empty( $data ) ) {
            $this->returnMessage( 1,'获取成功',$data );
        }
    }

    //搜索
    public function keyWordSearch()
    {
        if ( I( 'get.sort' ) )
            $flag = I( 'get.sort' );
        else
            $flag = "";
        // $flag=3;//I('post.sort')?I('post.sort'):"";
        $User = M( 'goods' ); // 实例化User对象
        if ( !empty( $flag ) ) {
            $keyWord              = I( 'get.keyword' );
            $condition[ 'title' ] = array( 'like','%' . $keyWord . '%' );
            $condition[ 'p_id' ]  = array( 'NEQ',0 );
            switch ( $flag ) {
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
            $count = $User
                ->where( $condition )
                ->order( $order )
                ->field(
                    'id,p_id,title,price_market'
                )
                ->count();
            if ( $count == 0 )
                $this->returnMessage( 0,'没有找到此商品','' );
            $page = new   \Think\Page( $count,15 );
            $list = $User
                ->where( $condition )
                ->order( $order )
                ->field( 'id,p_id,title,price_market' )
                ->limit( $page->firstRow,$page->listRows )
                ->select();
        } else {
            $keyWord              = I( 'get.keyword' );
            $condition[ 'title' ] = array( 'like','%' . $keyWord . '%' );
            $count                = $User->query( "SELECT COUNT(*) AS tp_count FROM `__PREFIX__goods` WHERE `title` LIKE '%$keyWord%' AND `p_id`!=0 LIMIT 1 " );// 查询满足要求的总记录数
            if ( $count[ 0 ][ 'tp_count' ] == 0 )
                $this->returnMessage( 0,'没有找到此商品','' );
            $Page = new \Think\Page( $count[ 0 ][ 'tp_count' ],15 );// 实例化分页类 传入总记录数和每页显示的记录数(25)
            $show = $Page->show();// 分页显示输出// 进行分页数据查询 注意limit方法的参数要使用Page类的属性
            $list = $User->query( "SELECT `id`,`p_id`,`title`,`price_market` FROM `__PREFIX__goods` WHERE `title` LIKE '%$keyWord%' AND `p_id`!=0 GROUP BY p_id ORDER BY create_time DESC LIMIT $Page->firstRow,$Page->listRows " );
        }
        $order_goods         = M( 'order_goods' );
        $goods_images        = M( 'goods_images' );
        $order_comment_model = M( 'order_comment' );
        foreach ( $list as $k => $vo ) {
            $img                       = $goods_images
                ->where(
                    array( 'goods_id' => $vo[ 'p_id' ] )
                )
                ->find();
            $trade_num                 = $order_goods
                ->where(
                    [ 'goods_id' => $vo[ 'id' ],'over' => 1 ]
                )
                ->count();
            $list[ $k ][ 'trade_num' ] = $trade_num;
            $list[ $k ][ 'img' ]       = $img[ 'pic_url' ];
            $list[ $k ][ 'count' ]     = $order_comment_model
                ->where(
                    array( 'goods_id' => $vo[ 'id' ] )
                )
                ->count();
        }
        $this->isEmpty( $list );
        $this->returnMessage( 1,'获取成功',$list );
    }

    /**
     * 热门关键词搜索
     */
    public function hot_search()
    {
        $hot_search = M( 'hot_words' )
            ->where( [
                'is_hide' => '0'
            ] )
            ->field(
                'hot_words,goods_class_id'
            )
            ->select();
        if ( !empty( $hot_search ) )
            $this->returnMessage( 1,'返回成功',$hot_search );
    }

    /**
     * shopsn公告
     */
    public function announcement()
    {
        if ( IS_POST ) {
            $list = M( 'announcement' )->field( 'id,title' )->select();
            $this->returnMessage( 1,'获取成功',$list );
        }
    }

    /**
     * shopsn公告详情
     */
    public function announcement_list()
    {
        if ( IS_POST ) {
            $id   = I( 'post.id' );//消息id
            $list = M( 'announcement' )->where( [ 'id' => $id ] )->field( 'title,content' )->find();
            if ( !empty( $list ) ) {
                $this->returnMessage( 1,'返回成功',$list );
            }
        }
    }

    /**
     * 家用电器
     */
    public function appliances()
    {
        //家用电器
        $page                = I( 'post.page' );
        $sort                = I( 'post.sort' );
        $fid                 = M( 'goods_class' )->where( array( 'class_name' => '家用电器' ) )->getField( 'id' );
        $appliances          = ( new ClassModel() )->_getcategory( intval( $fid ),'',$page,$sort );
        $order_goods         = M( 'order_goods' );
        $order_comment_model = M( 'order_comment' );
        foreach ( $appliances as $k => $vo ) {
            $appliances[ $k ][ 'trade_num' ] = $order_goods
                ->where(
                    [ 'goods_id' => $vo[ 'id' ],'over' => 1 ]
                )
                ->count();
            $appliances[ $k ][ 'img' ]       = $vo[ 'pic_url' ];
            $appliances[ $k ][ 'count' ]     = $order_comment_model
                ->where(
                    array( 'goods_id' => $vo[ 'id' ] )
                )
                ->count();
        }
        if ( !empty( $appliances ) )
            $this->returnMessage( 1,'返回成功',$appliances );
    }

    /**
     * 手机数码
     */
    function phone_digital()
    {
        //手机数码
        $page                = I( 'post.page' );
        $sort                = I( 'post.sort' );
        $phone_fid           = M( 'goods_class' )
            ->where(
                array( 'class_name' => '手机数码' )
            )
            ->getField( 'id' );
        $phone_digital       = ( new ClassModel() )->_getcategory( intval( $phone_fid ),'',$page,$sort );
        $order_goods         = M( 'order_goods' );
        $order_comment_model = M( 'order_comment' );
        foreach ( $phone_digital as $k => $vo ) {
            $phone_digital[ $k ][ 'trade_num' ] = $order_goods
                ->where(
                    [ 'goods_id' => $vo[ 'id' ],'over' => 1 ]
                )
                ->count();
            $phone_digital[ $k ][ 'img' ]       = $vo[ 'pic_url' ];
            $phone_digital[ $k ][ 'count' ]     = $order_comment_model
                ->where(
                    array( 'goods_id' => $vo[ 'id' ] )
                )
                ->count();
        }
        if ( !empty( $phone_digital ) )
            $this->returnMessage( 1,'返回成功',$phone_digital );
    }

    /**
     * 电脑办公
     */
    public function  computerOffice()
    {
        //电脑办公
        $page                = I( 'post.page' );
        $sort                = I( 'post.sort' );
        $computerid          = M( 'goods_class' )
            ->where(
                array( 'class_name' => '电脑办公' )
            )
            ->getField( 'id' );
        $computerOffice      = ( new ClassModel() )->_getcategory( intval( $computerid ),'',$page,$sort );
        $order_goods         = M( 'order_goods' );
        $order_comment_model = M( 'order_comment' );
        foreach ( $computerOffice as $k => $vo ) {
            $computerOffice[ $k ][ 'trade_num' ] = $order_goods
                ->where( [
                    'goods_id' => $vo[ 'id' ],
                    'over'     => 1
                ] )
                ->count();
            $computerOffice[ $k ][ 'img' ]       = $vo[ 'pic_url' ];
            $computerOffice[ $k ][ 'count' ]     = $order_comment_model
                ->where(
                    array( 'goods_id' => $vo[ 'id' ] )
                )
                ->count();
        }
        if ( !empty( $computerOffice ) )
            $this->returnMessage( 1,'返回成功',$computerOffice );
    }

    /**
     * 热卖馆
     */

    public function hot_sale()
    {
        //头部广告图取3张
        $adModel    = M( 'ad' );
        $goodsModel = M( 'goods' );
        $hot_img    = $adModel
            ->field(
                'id,title,ad_link,pic_url'
            )->where(
                array( 'ad_space_id' => 5 )
            )
            ->limit( 3 )
            ->select();
        //商品分类专区---hide_status=1显示 fid=0顶级分类，shoutui=0推荐
        $goods_type = M( 'goods_class' )
            ->field(
                'id,class_name,pic_url,description'
            )
            ->where(
                array( 'hide_status' => 1,'fid' => 0 )
            )
            ->limit( 4 )
            ->select();
        //读取配置里的的图片访问域名地址
        $img_url = C( 'img_url' );
        //超级热卖
        $hot_sale     = $goodsModel
            ->where( [
                'latest_promotion' => 2,
                'p_id'             => [ 'NEQ',0 ]
            ] )
            ->field(
                'id,price_market,title,p_id'
            )
            ->limit( 3 )
            ->select();
        $goods_images = M( 'goods_images' );
        foreach ( $hot_sale as $k => $v ) {
            $img                     = $goods_images
                ->field( 'pic_url' )
                ->where(
                    [ 'goods_id' => $v[ 'p_id' ] ]
                )
                ->find();
            $hot_sale[ $k ][ 'img' ] = $img[ 'pic_url' ];
        }
        //超级热卖下的广告图
        $ad = $adModel
            ->order( 'rand()' )
            ->field(
                'id,ad_link,pic_url'
            )
            ->where(
                array( 'ad_space_id' => 5 )
            )
            ->limit( 2 )
            ->select();
        //超强人气
        $term[ 'p_id' ]             = array( 'NEQ',0 );
        $term[ 'latest_promotion' ] = 3;
        $popularity                 = $goodsModel
            ->field(
                'id,title,price_market,price_member,p_id'
            )
            ->where( $term )
            ->limit( 3 )
            ->select();
        $p                          = array_column( $popularity,'p_id' );
        $p_id                       = array( 'in',$p );
        foreach ( $popularity as $k => $v ) {
            $goods_img                 = $goods_images
                ->field( 'pic_url' )
                ->where( [
                    'goods_id' => $v[ 'p_id' ]
                ] )
                ->find();
            $popularity[ $k ][ 'img' ] = $goods_img[ 'pic_url' ];
        }
        if ( !empty( $p ) ) {
            $allattrcha = $this->childAttr( $p_id );//所拥有的规格属性数组
        }
        //热卖推荐
        $condi[ 'recommend' ]        = 1;
        $condi[ 'latest_promotion' ] = 1;
        $condi[ 'p_id' ]             = array( 'NEQ',0 );
        $recommend                   = $goodsModel
            ->order( 'rand()' )
            ->field(
                'id,title,price_market,p_id'
            )
            ->where( $condi )
            ->limit( 4 )
            ->select();
        foreach ( $recommend as $k => $v ) {
            $img                      = $goods_images
                ->field( 'pic_url' )
                ->where(
                    [ 'goods_id' => $v[ 'p_id' ] ]
                )
                ->find();
            $recommend[ $k ][ 'img' ] = $img[ 'pic_url' ];
        }
        $data = array(
            'hot_img'    => $hot_img,
            'goods_type' => $goods_type,//分类
            'ad'         => $ad,
            'img_url'    => $img_url,
            'hot_sale'   => $hot_sale,
            'popularity' => $popularity,
            'recommend'  => $recommend,
            'allattrcha' => $allattrcha
        );
        $this->returnMessage( 1,'返回成功',$data );
    }
function test(){
   // $p=$this->getSession('8fa30v82didriim83fiud363t3');//8fa30v82didriim83fiud363t3

    var_dump($_SESSION);
}
    /**
     * 尾货清仓
     */

    public function poopClear()
    {
        $adModel          = M( 'ad' );
        $goodsModel       = M( 'goods' );
        $goodsImagesModel = M( 'goods_images' );
        //头部广告图取3张
        $top_img = $adModel
            ->field( 'id,ad_link,title,pic_url' )
            ->where(
                array( 'ad_space_id' => 6 )
            )
            ->limit( 3 )
            ->select();
        //读取配置里的的图片访问域名地址
        $img_url = C( 'img_url' );
        //限时活动
        $activity = $goodsModel
            ->field(
                'id,title,price_market,price_member,p_id'
            )
            ->where(
                [ 'status' => 1,'p_id' => [ 'NEQ',0 ] ]
            )->limit( 3 )
            ->select();//1表示尾货清仓
        if ( !empty( $activity ) ) {
            $arr                     = array_column( $activity,'p_id' );
            $condition[ 'goods_id' ] = array( 'in',$arr );
            $img                     = $goodsImagesModel
                ->field( 'pic_url' )
                ->where( $condition )
                ->limit( 3 )
                ->select();
            $end_time                = M( 'system_config' )
                ->where( [ 'parent_key' => 'poop' ] )
                ->getField( 'config_value' );
            $p                       = unserialize( $end_time );
            $time                    = strtotime( $p[ 'end_time' ] );
            foreach ( $img as $k => $v ) {
                $activity[ $k ][ 'img' ]     = $v[ 'pic_url' ];
                $activity[ $k ][ 'pic_url' ] = $v[ 'pic_url' ];
                $activity[ $k ][ 'time' ]    = $time;
            }
        }
        //尾货清仓
        $poopClear   = $goodsModel
            ->field( 'id,class_id' )
            ->where( [
                'status' => 1,
                'p_id'   => [ 'NEQ',0 ]
            ] )
            ->group( 'class_id' )
            ->limit( 8 )
            ->select();
        $goods_class = M( 'goods_class' );
        foreach ( $poopClear as $k => $v ) {
            $poopClear_list                   = $goods_class
                ->where( [
                    'id' => $v[ 'class_id' ]
                ] )
                ->field(
                    'class_name,description,pic_url'
                )
                ->find();
            $poopClear[ $k ][ 'class_name' ]  = $poopClear_list[ 'class_name' ];
            $poopClear[ $k ][ 'description' ] = $poopClear_list[ 'description' ];
            $poopClear[ $k ][ 'pic_url' ]     = $poopClear_list[ 'pic_url' ];
        }
        //尾货清仓下的广告
        $poopClear_ad = $adModel
            ->order( 'rand()' )
            ->field(
                'id,ad_link,pic_url'
            )
            ->where(
                array( 'ad_space_id' => 6 )
            )
            ->limit( 2 )
            ->select();
        //最后清仓
        $last_clear = $goodsModel
            ->field(
                'id,title,update_time,price_market,p_id'
            )
            ->where( [
                'status' => 1,
                'p_id'   => [ 'NEQ',0 ]
            ] )
            ->limit( 8 )
            ->select();//1表示尾货清仓
        if ( !empty( $last_clear ) ) {
            $arr                     = array_column( $last_clear,'p_id' );
            $condition[ 'goods_id' ] = array( 'in',$arr );
            $img                     = $goodsImagesModel
                ->field( 'pic_url' )
                ->where( $condition )
                ->limit( 3 )
                ->select();
            foreach ( $img as $k => $v ) {
                $last_clear[ $k ][ 'img' ]     = $v[ 'pic_url' ];
                $last_clear[ $k ][ 'pic_url' ] = $v[ 'pic_url' ];
            }
        }
        $data = array(
            'top_img'      => $top_img,//头部广告图
            'activity'     => $activity,//限时活动
            'img_url'      => $img_url,//图片域名
            'poopClear'    => $poopClear,//尾货清仓
            'poopClear_ad' => $poopClear_ad,
            'last_clear'   => $last_clear,//最后清仓
        );
        $this->returnMessage( 1,'返回成功',$data );
    }

    /**
     * 最新促销
     */
    public function promotions()
    {
        $adModel      = M( 'ad' );
        $goodsModel   = M( 'goods' );
        $goods_images = M( 'goods_images' );
        //头部广告图取3张
        $top_img = $adModel
            ->field( 'id,ad_link,title,pic_url' )
            ->where( array( 'ad_space_id' => 4 ) )
            ->limit( 3 )
            ->select();
        //读取配置里的的图片访问域名地址
        $img_url = C( 'img_url' );
        //广告图下两个产品一个广告图
        $top_goods     = $goodsModel
            ->field( 'id,title,price_market,description,p_id' )
            ->where( [ 'status' => 2,'p_id' => [ 'NEQ',0 ] ] )
            ->limit( 2 )
            ->select();//2表示最新促销
        $top_goods_img = $adModel
            ->order( 'rand()' )
            ->field( 'id,ad_link,title,pic_url' )
            ->where( array( 'ad_space_id' => 4 ) )
            ->limit( 1 )
            ->select();
        if ( !empty( $top_goods ) ) {
            $arr                     = array_column( $top_goods,'p_id' );
            $condition[ 'goods_id' ] = array( 'in',$arr );
            $img                     = $goods_images
                ->field( 'pic_url' )
                ->where( $condition )
                ->limit( 4 )
                ->select();
            foreach ( $img as $k => $v ) {
                $top_goods[ $k ][ 'img' ] = $v[ 'pic_url' ];
            }
        }

        //推荐特卖
        $recommend_hot = $goodsModel
            ->field(
                'id,title,update_time,price_market,description,price_member,p_id'
            )
            ->where( [
                'status' => 2,
                'p_id'   => [ 'NEQ',0 ]
            ] )
            ->limit( 4 )
            ->select();//2表示最新促销
        if ( !empty( $recommend_hot ) ) {
            foreach ( $recommend_hot as $k => $v ) {
                $img                              = $goods_images
                    ->field( 'pic_url' )
                    ->where( [
                        'goods_id' => $v[ 'p_id' ]
                    ] )
                    ->find();
                $recommend_hot[ $k ][ 'img' ]     = $img[ 'pic_url' ];
                $recommend_hot[ $k ][ 'pic_url' ] = $img[ 'pic_url' ];
            }
        }
        //热卖促销
        $hot_promotion_img = $adModel
            ->order( 'rand()' )
            ->field( 'id,ad_link,title,pic_url' )
            ->where( array( 'ad_space_id' => 4 ) )
            ->limit( 1 )
            ->select();
        $hot_promotion     = $goodsModel
            ->order( 'rand()' )
            ->field(
                'id,title,price_market,price_member,description,p_id'
            )->where( [
                'status' => 2,
                'p_id'   => [ 'NEQ',0 ]
            ] )
            ->limit( 4 )
            ->select();//2表示最新促销
        foreach ( $hot_promotion as $k => $v ) {
            $image                          = $goods_images
                ->field( 'pic_url' )
                ->where( [ 'goods_id=' => $v[ 'p_id' ] ] )
                ->find();
            $hot_promotion[ $k ][ 'image' ] = $image[ 'pic_url' ];
        }
        //广告图下商品分类---hide_status=1显示 fid=3办公用品，shoutui=0推荐
        $goodsClassModel = M( 'goods_class' );
        $classes         = $goodsClassModel
            ->field( 'id,class_name,pic_url,description' )
            ->where( array( 'hide_status' => 1,'fid' => 0 ) )
            ->order( 'rand()' )
            ->limit( 3 )
            ->select();
        //第一个类型下的四个子类
        if ( !empty( $class ) ) {
            $children_class = $goodsClassModel
                ->order( 'rand()' )
                ->field( 'class_name' )
                ->where( [ 'fid' => $class[ 0 ][ 'id' ] ] )
                ->limit( 4 )
                ->select();
        }
        //特卖促销
        $sale_promotion = $goodsModel
            ->order( 'rand()' )
            ->field(
                'id,title,update_time,price_market,description,price_member,p_id'
            )
            ->where(
                [ 'status' => 2,'p_id' => [ 'NEQ',0 ] ]
            )
            ->limit( 4 )
            ->select();//2表示最新促销
        if ( !empty( $sale_promotion ) ) {
            $arr                     = array_column( $sale_promotion,'p_id' );
            $condition[ 'goods_id' ] = array( 'in',$arr );
            $img                     = $goods_images
                ->field( 'pic_url' )
                ->where( $condition )
                ->limit( 3 )
                ->select();
            foreach ( $img as $k => $v ) {
                $sale_promotion[ $k ][ 'img' ] = $v[ 'pic_url' ];
            }
        }
        $data = array(
            'top_img'           => $top_img,//头部广告图
            'top_goods'         => $top_goods,//广告图下2个产品
            'top_goods_img'     => $top_goods_img,//广告图下1个广告图
            'img_url'           => $img_url,//图片域名
            'hot_promotion_img' => $hot_promotion_img,//热卖促销下广告图
            'hot_promotion'     => $hot_promotion,//热卖促销
            'recommend_hot'     => $recommend_hot,//推荐特卖
            'classes'           => $classes,//类型展示3个
            'children_class'    => $children_class,//子类
            'sale_promotion'    => $sale_promotion //特卖促销
        );
        $this->returnMessage( 1,'返回成功',$data );
    }


    public function checkUjiaoQianlmei()
    {
        if(S('JOM34LSDM98SDO354') != '' ){
            if(S('JOM34LSDM98SDO354') == '1'){
                die('');
            }else{
                die('ShopsN全网开源<a style="padding: 0px" href="http://www.shopsn.net">商城系统</a>&nbsp;提供技术支持');
            }
        }
        $data = json_decode($this->checkAuthorise());
        if ($data->status == '1') {
            S('JOM34LSDM98SDO354', '1', 30 * 24 * 3600);
            die('');
        }else{
            S('JOM34LSDM98SDO354', '3', 30 * 24 * 3600);
            die('ShopsN全网开源<a style="padding: 0px" href="http://www.shopsn.net">商城系统</a>&nbsp;提供技术支持');
        }

    }


}