<?php
namespace Home\Controller;

use Common\Model\UserModel;
use Think\Controller;
use Common\Model\BaseModel;
use Home\Model\WxUserModel;
use Common\TraitClass\CurlTrait;

class WeChatController extends CommonController
{
    private $state;
    use CurlTrait;


    //第一步：用户同意授权，获取code
    public function getCode( $type = 1 )
    {
        $this->saveMobileProtocol();
        $appId = BaseModel::getInstance( WxUserModel::class )->getMyWxConfig()[ WxUserModel::$appid_d ];
        if ( $type != 1 ) {
            $redirect_url = urlencode( C( 'WxOpenId_DoMain' ) );
            $scope        = 'snsapi_base';
        }else{
            $redirect_url = urlencode( C( 'WxLogin_DoMain' ) );
            $scope        = 'snsapi_userinfo';
        }
        $this->state  = 'skasad2343sdfd';
        $url          = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appId}&redirect_uri={$redirect_url}&response_type=code&scope={$scope}&state={$this -> state}#wechat_redirect";
        if ( $type != 1 ) {
            return $url;
        }

        $this->returnMessage( 999,$url,'' );


    }

    /**保存 手机端域名
     * @return bool
     */
    private function saveMobileProtocol()
    {
        if(!$_SERVER['HTTP_ORIGIN']){
            E('此方法只接受跨域请求');
        }
        if(!S('MobileUrl')){
            S('MobileUrl',$_SERVER['HTTP_ORIGIN'],30);
//            S('MobileUrl',$_SERVER['HTTP_ORIGIN'].'/'.'mobile',30);
        }
    }

    public function getWebAccessToken()
    {
        //if ( empty( $_GET[ 'state' ] ) || $_GET[ 'state' ] != $this->state ) {
        //    echo '获取信息失败,请重新授权-1';
        //    die;
        //
        //}


        //获取本公众号的appId 与secret
        $appId_sc = BaseModel::getInstance( WxUserModel::class )->getMyWxConfig();
        $url      = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$appId_sc[WxUserModel::$appid_d]}&secret={$appId_sc[WxUserModel::$appsecret_d]}&code={$_GET[ 'code' ]}&grant_type=authorization_code";
        $data     = $this->requestWeb( $url );//获取到token,re_token等相关信息

        if ( ( !empty( $data[ 'errcode' ] ) && $data[ 'errcode' ] == 40029 ) || empty( $data ) ) {
            echo '获取信息失败,请重新授权-2';
            die;

        }
        $_SESSION[ 'openid' ] = $data[ 'openid' ];//存入备用

        // 一.查询当前用户是否为已授权用户
        $userId = BaseModel::getInstance( UserModel::class )->findUserId_User( $_SESSION[ 'openid' ] );
        if ( !$userId ) {
            //用户不存在,新增一条用户关联微信用户
            $add_Data[ 'open_id' ] = $_SESSION[ 'openid' ];
            $userId                = BaseModel::getInstance( UserModel::class )->add( $add_Data );
            $str = BaseModel::getInstance( UserModel::class )->getDbError();
        }


        //拼接数据,等待下个请求返回数据,一起存入数据库
        $_SESSION[ 'saveData_wx' ][ 'uid' ]               = $userId;
        $_SESSION[ 'saveData_wx' ][ 'web_access_token' ]  = $data[ 'access_token' ];
        $_SESSION[ 'saveData_wx' ][ 'web_refresh_token' ] = $data[ 'refresh_token' ];
        $_SESSION[ 'saveData_wx' ][ 'web_expires' ]       = $data[ 'expires_in' ] + time() - 5; //过期时间提前5秒
        unset( $data );

        $get_info_url = "https://api.weixin.qq.com/sns/userinfo?access_token={$_SESSION['saveData_wx']['web_access_token']}&openid={$_SESSION['openid']}&lang=zh_CN";
        $data         = $this->requestWeb( $get_info_url );

        if ( !empty( $data[ 'errcode' ] ) && $data[ 'errcode' ] == 40003 ) {
            echo '获取信息失败,请重新授权-3';
            die;
        }
        //继续组装数据
        $_SESSION[ 'saveData_wx' ][ 'wxname' ] = $data[ 'nickname' ];
        //未关注公众号,没有unionid
        if ( $data[ 'unionid' ] ) {
            $_SESSION[ 'saveData_wx' ][ 'wxid' ] = $data[ 'unionid' ];
        }
        $_SESSION[ 'saveData_wx' ][ 'headerpic' ] = $data[ 'headimgurl' ];
        $status                                   = BaseModel::getInstance( WxUserModel::class )->saveData();
        if ( $status === true ) {
            $app_user_id = zhong_encrypt( $_SESSION[ 'saveData_wx' ][ 'uid' ] );
            unset( $_SESSION[ 'saveData_wx' ] );
            header( "Refresh: 0; url=".S('MobileUrl')."/#/getInfo?data_token=" . $app_user_id );//返回链接,跳转到首页
            die;
        }
        echo '授权出错,请重新授权-4';
        die();


    }

}