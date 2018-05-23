<?php
namespace Home\Controller;

use Think\Controller;
use Common\TraitClass\NoticeTrait;

header( "Content-type:text/html;charset=utf-8" );
//@mysqli_query( 'set names utf8' );
$isURL ='*';

header( "Access-Control-Allow-Origin:" . $isURL );//跨域解决
header('Access-Control-Allow-Methods:POST,GET');
header('Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept');
class CommonController extends Controller
{
    use NoticeTrait;

//    public function __construct()
//    {
//        parent::__construct();
//
//        $token = isset($_POST['sessionId']) ? $_POST['sessionId']: null;
//        //jksdhjkhjksd  跨域token识别
//        if(!empty($token)) {
//            session_write_close();
//
//            session_id($token);
//
//            session_start();
//        }
//    }


    //短信发送
    protected function send_msg( $tel )
    {
        $account  = "";                        //改为实际账户名
        $password = "";                        //改为实际短信发送密码
        $mobiles  = $tel;                //目标手机号码，多个用半角“,”分隔
        $extno    = "";
        $verify   = rand( 1111,9999 );
        $content  = "【shopSN开源电商】您的验证码:" . $verify;
        $sendtime = "";
        $url      = "https://dx.ipyy.net/sms.aspx";
        $body     = array(
            'action'   => 'send',
            'userid'   => '',
            'account'  => $account,
            'password' => $password,
            'mobile'   => $mobiles,
            'extno'    => $extno,
            'content'  => $content,
            'sendtime' => $sendtime
        );
        $ch       = curl_init();
        curl_setopt( $ch,CURLOPT_URL,$url );
        curl_setopt( $ch,CURLOPT_POSTFIELDS,$body );
        curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER,false );
        curl_setopt( $ch,CURLOPT_SSL_VERIFYHOST,false );
        curl_setopt( $ch,CURLOPT_RETURNTRANSFER,1 );
        $result = curl_exec( $ch );
        curl_close( $ch );
        $result['sessionId']=session_id();
        S( 'short_mobile'.$mobiles,$mobiles,C( 'send_msg_time' ) );
        S( 'short_msg_code'.$verify,$verify,C( 'send_msg_time' ) );
        $this->returnMessage( 1,'短信发送成功',$result );
    }
 //根据session_id活动session数据
    protected function getSession($sessionId){
        session_id($sessionId);
        session_start();
        return $_SESSION;

    }


    /*返回信息*/
    protected function returnMessage( $status,$msg,$data )
    {
        $msg       = urlencode( $msg );
        $remessage = array( 'status' => $status,'msg' => $msg,'data' => $data );
        exit( urldecode( json_encode( $remessage ) ) );
    }
    /********
     * 用户验证
     */
    protected function userVerify($user){
        $r=zhong_decrypt($user);
        if(empty($r)||is_numeric($r)==false){
            $this->returnMessage(0,'用户验证错误','');
        }else{
            return $r;
        }
    }

    /*二维数组排序---按照字段$key做降序 ASC是升序*/
    protected function arr_sort( $arr,$key )
    {
        $bb = array_column( $arr,$key );
        array_multisort( $bb,SORT_DESC,$arr );

        return $arr;
    }

    /*文件上传*/
    protected function upload( $mulu )
    {
        $upload           = new \Think\Upload();// 实例化上传类
        $upload->maxSize  = C( 'img_size' );// 设置附件上传大小
        $upload->exts     = C( 'img_type' );// 设置附件上传类型
        $upload->rootPath = './Uploads/';//上传根目录
        $upload->savePath = "/$mulu/"; // 设置附件上传目录    // 上传文件
        $info             = $upload->upload();
        if ( !$info ) {// 上传错误提示错误信息
            $msg = $upload->getError();
            return array( 'status' => 0,'msg' => $msg );
        } else {// 上传成功
            return array( 'status' => 1,'info' => $info );
        }
    }

    //判断是否登录
    protected function isLogin()
    {
        if ( empty( session( 'app_user_id' ) ) ) {
            $this->returnMessage( 0,'请先登录','' );
        }
    }

    //判断为不为空返回数据
    protected function isEmpty( $result )
    {
        if ( empty( $result ) )
            $this->returnMessage( 0,'暂无数据','' );
    }

    /***
     * 向cookie中添加内容
     * 我的足迹
     */
    public function fotoplace( $content )
    {
        $trace = cookie( 'trace' );//读取原有值
        if ( !empty( $trace ) ) {
            //取得COOKIE里面的值，并用逗号把它切割成一个数组
            $history = explode( ',',$_COOKIE[ 'trace' ] );
            //在这个数组的开头插入当前正在浏览的商品ID
            array_unshift( $history,$content );
            //去除数组里重复的值
            $history = array_unique( $history );
            //当数组的长度大于5里循环执行里面的代码
            while ( count( $history ) > 10 ) {
                //将数组最后一个单元弹出，直到它的长度小于等于5为止
                array_pop( $history );
            }
            //把这个数组用逗号连成一个字符串写入COOKIE，并设置其过期时间为一星期
            cookie( 'trace',implode( ',',$history ),3600 * 24 * 7 );
        } else {
            //如果COOKIE里面为空，则把当前浏览的商品ID写入COOKIE ，这个只在第一次浏览该网站时发生
            cookie( 'trace',$content,3600 * 24 * 7 );
        }
    }

    //浏览记录
    protected function browseRecord( $goods_id )
    {
        $find = M( 'goods' )->where( array( 'id' => $goods_id ) )->find();
        $data = array(
            'goods_id'     => $find[ 'id' ],
            'title'        => $find[ 'title' ],
            'price_member' => $find[ 'price_member' ],
            'img'          => M( 'goods_images' )->where( array( 'goods_id' => $goods_id ) )->getField( 'pic_url' ),
            'time'         => time(),
        );
        if ( cookie( 'browseRecord' ) ) {//如果存在
            $fotopl       = cookie( 'browseType' );
            $browseRecord = cookie( 'browseRecord' );
            if ( in_array( $find[ 'p_id' ],$fotopl ) == false ) {//如果不存在
                $fotopl[]       = $find[ 'p_id' ];
                $browseRecord[] = $data;
                cookie( 'browseType',$fotopl );
                cookie( 'browseRecord',$browseRecord );
            }
        } else {//如果不存在--创建
            $browseRecord   = array();
            $browseRecord[] = $data;
            $browseType     = array();
            $browseType[]   = $find[ 'p_id' ];
            cookie( 'browseType',$browseType );
            cookie( 'browseRecord',$data );
        }
    }

    //根据商品id获取对应商品的规格属性
    protected function selfAttr( $goods_id )
    {
        $spec_goods_price                     = M( 'spec_goods_price' );
        $goods_spec_item                      = M( 'goods_spec_item' );
        $attr                                 = $spec_goods_price->where( array( 'goods_id' => $goods_id ) )->getField( 'key' );
        $attr                                 = explode( '_',$attr );
        $condition[ 'db_goods_spec_item.id' ] = array( 'in',$attr );
        $join                                 = 'db_goods_spec ON db_goods_spec.id=db_goods_spec_item.spec_id';
        $attra                                = $goods_spec_item->join( $join )->where( $condition )->field( 'name,item' )->select();
        return $attra;
    }

    //根据子商品id,查找父类的图片并返回地址
    protected function fatherImg( $result )
    {
        $goods_model        = M( 'goods' );
        $goods_images_model = M( 'goods_images' );
        foreach ( $result as $k => $vo ) {
            $fatherId                  = $goods_model->where( array( 'id' => $vo[ 'id' ] ) )->getField( 'p_id' );
            $fatherImg                 = $goods_images_model->where( array( 'goods_id' => $fatherId ) )->getField( 'pic_url' );
            $result[ $k ][ 'pic_url' ] = $fatherImg;
        }
        return $result;
    }

    //订单号的生成
    protected function toGUID()
    {   //订购日期
        //订单号码主体（YYYYMMDDHHIISSNNNNNNNN）
        $orderIdMain = date( 'YmdHis' ) . rand( 10000000,99999999 );
        //订单号码主体长度
        $orderIdLen = strlen( $orderIdMain );
        $orderIdSum = 0;
        for ( $i = 0; $i < $orderIdLen; $i++ ) {
            $orderIdSum += (int)( substr( $orderIdMain,$i,1 ) );
        }
        //唯一订单号码（YYYYMMDDHHIISSNNNNNNNNCC）
        $orderId = $orderIdMain . str_pad( ( 100 - $orderIdSum % 100 ) % 100,2,'0',STR_PAD_LEFT );
        return $orderId;
    }

    /**
     * 配送方式选择
     */
    protected function _getShipping( $user_id,$address_id )
    {
        $Shipping = M( 'express' )
            ->where( [
                'status' => 1,
                'order'  => 1
            ] )
            ->field( 'name,id' )
            ->order( 'id desc' )
            ->limit(2)
            ->select();
        return $Shipping;
    }

    //判断用户是否存在--$type=1:加密判断 2:为解密判断
    protected function isExist( $user_id,$type = 1 )
    {
        $user_model = M( 'user' );
        if ( $type == 1 ) {
            $user_id = zhong_decrypt( $user_id );
            $find    = $user_model->where( array( 'id' => $user_id ) )->find();
            if ( !$find ) $this->returnMessage( 0,'数据异常','' );
        } else {
            $find = $user_model->where( array( 'id' => $user_id ) )->find();
            if ( $find ) $this->returnMessage( 0,'数据异常','' );
        }
    }

    //更改所有session获取用户id
    protected function app_user_id()
    {
        $user_id = zhong_decrypt( I( 'post.app_user_id' ) );
        return $user_id;
    }

    //获取商品属性
    public function childAttr( $goods )
    {
        //查询同级产品所拥有的
        $join                  = 'db_goods_spec ON db_goods_spec.id=db_goods_spec_item.spec_id';
        $join1                 = 'db_spec_goods_price ON db_spec_goods_price.goods_id=db_goods.id';
        $goods_model           = M( 'goods' );
        $goods_spec_item_model = M( 'goods_spec_item' );
        $chlidlist             = $goods_model->field( 'id' )->where( array( 'p_id' => $goods ) )->select();
        $allattr               = array();
        foreach ( $chlidlist as $vo ) {
            $chlidattr                            = $goods_model->join( $join1 )->field( 'key' )->where( array( 'db_goods.id' => $vo[ 'id' ] ) )->find();
            $chlidattra                           = explode( "_",$chlidattr[ 'key' ] );//得到每个子类有的产品属性
            $condition[ 'db_goods_spec_item.id' ] = array( 'in',$chlidattra );
            $chlidattrdetal                       = $goods_spec_item_model->join( $join )->field( 'item,name,spec_id,db_goods_spec_item.id' )->where( $condition )->select();
            $allattr[]                            = $chlidattrdetal;
        }
        $allattrcha = array();
        foreach ( $allattr as $k => $vo ) {
            foreach ( $vo as $vl ) {
                //如果属性不存在加入属性
                if ( in_array( $vl[ 'name' ],array_column( $allattrcha,'name' ) ) == false ) {
                    $allattrcha[] = array( 'name' => $vl[ 'name' ],'value' => array() );
                }
                if ( in_array( $vl[ 'name' ],array_column( $allattrcha,'name' ) ) ) {//属性已经存在数组中--判断属性值是否存在
                    //判断属性值是否是在属性里如果不在就创建数组
                    $aa = array_column( $allattrcha,'name' );
                    $b  = array_search( $vl[ 'name' ],$aa );
                    if ( in_array( $vl[ 'item' ],array_column( $allattrcha[ $b ][ 'value' ],'attr' ) ) == false ) {
                        $bb                            = array( 'attr' => $vl[ 'item' ],'spci' => $vl[ 'id' ] );
                        $allattrcha[ $b ][ 'value' ][] = $bb;
                    }
                }
            }
        }
        return $allattrcha;
    }

    /**
     * @param $str  图片的base64（注意是净base64）
     * @param $path 图片要存储的相对路径（只需传项目以下的文件路径就可以，例子：'/Uploads'）
     * @return string 返回新图片想对路径
     */
    public function getImage( $str,$path )
    {
        $path_route = getcwd() . $path;
        if ( !file_exists( $path_route ) ) {
            mkdir( $path_route,0777,true );
            chmod( $path_route,0777 );
        }
        //拓展名
        //$format = substr(getimagesize($str)['mime'],6);
        //新的相对路径
        $path = $path . '/' . time() . rand( 100,999 ) . '.' . 'jpg';
        //图片的的绝对路径
        $img_path = getcwd() . $path;
        //创建文件
        $open = fopen( $img_path,"x+" );
        fwrite( $open,base64_decode( $str ) );
        fclose( $open );
        //新图片路劲
        if ( file_exists( getcwd() . $path ) ) {
            return $path;
        };
    }

    /**
     * @param $img  图片路径
     * @return string 返回base_64的图片
     */
    function toBase64( $img )
    {
        $fp      = fopen( $img,'r' );
        $new_img = base64_encode( fread( $fp,filesize( $img ) ) );
        fclose( $fp );
        return $new_img;
    }
//    public function test1($img='',$user_id=''){
//        $file= '.'.$img;
//        $ch = curl_init();
//        //加@符号curl就会把它当成是文件上传处
//        $data = array('img'=>new \CURLFile($file),'user_id'=>$user_id );
//        curl_setopt($ch,CURLOPT_URL,"http://test.shopsn.net/index.php/Home/AppUpload/headerUpload");
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//        curl_setopt($ch, CURLOPT_POST, true);
//        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
//        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
//        $result = curl_exec($ch);
//        curl_close($ch);
//        echo json_decode($result);
//    }
    /**
     * @param string $img
     * @param string $user_id
     * @param string $position
     * curl上传头像
     */
    public function uploadsHead( $img = '',$user_id = '' )
    {
        $path = $img;
        $p    = explode( '$',$path );
        $file = array();
        foreach ( $p as $v ) {
            $file[] = '.' . trim( $v );
        }
        $ch = curl_init();
        //加@符号curl就会把它当成是文件上传处
        curl_setopt( $ch,CURLOPT_URL,"http://test.shopsn.net/index.php/Home/AppUpload/headerUpload" );
        curl_setopt( $ch,CURLOPT_RETURNTRANSFER,true );
        curl_setopt( $ch,CURLOPT_POST,true );
        curl_setopt( $ch,CURLOPT_SAFE_UPLOAD,false );
        foreach ( $file as $v ) {
            $data = array(
                'img'     => new \CURLFile( $v ),
                'user_id' => $user_id,
            );
            curl_setopt( $ch,CURLOPT_POSTFIELDS,$data );
            $result = curl_exec( $ch );
        }
        curl_close( $ch );
        echo json_decode( $result );
    }

    /**
     * 商品评价curl传图
     */
    public function commentUpload( $img = '',$user_id = '',$goods_id = '',$order_id = '' )
    {
        $path = $img;
        $p    = explode( '$',$path );
        $file = array();
        foreach ( $p as $v ) {
            $file[] = '.' . trim( $v );
        }
        $ch = curl_init();
        //加@符号curl就会把它当成是文件上传处
        curl_setopt( $ch,CURLOPT_URL,"http://test.shopsn.net/index.php/Home/AppUpload/commentUpload" );
        curl_setopt( $ch,CURLOPT_RETURNTRANSFER,true );
        curl_setopt( $ch,CURLOPT_POST,true );
        curl_setopt( $ch,CURLOPT_SAFE_UPLOAD,false );
        foreach ( $file as $v ) {
            $data = array(
                'img'      => new \CURLFile( $v ),
                'user_id'  => $user_id,
                'goods_id' => $goods_id,
                'order_id' => $order_id
            );
            curl_setopt( $ch,CURLOPT_POSTFIELDS,$data );
            $result = curl_exec( $ch );
        }
        curl_close( $ch );
        echo json_decode( $result );
    }

    /**
     * 检测是否是微信浏览器
     * @return bool
     */
    public function isWxBrow()
    {
        if ( strpos( $_SERVER[ 'HTTP_USER_AGENT' ],'MicroMessenger' ) == false ) {
            return false;
        }
        return true;
    }

    /**
     * 需要微信登录的地方调用
     */
    public function checkWxLogin()
    {
        //当微信客户端登录时
        if ( $this->isWxBrow() && empty( $_GET['SDKFJSD'] ) ) {
            $WxChat = new WeChatController();
            $WxChat->getCode();
        }
    }

    /**
     * 检测是否授权
     */
    public function checkAuthorise()
    {
        $url = ltrim(strstr($_SERVER['HTTP_HOST'],'.'),'.');
        $data = ['url' => $url];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://www.shopsn.net/index.php?ac=check&at=check&token=SDIOFSDJ12348923-SADJ');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }


}