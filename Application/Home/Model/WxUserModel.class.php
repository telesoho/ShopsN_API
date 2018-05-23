<?php

namespace Home\Model;

use Common\Model\BaseModel;
use Common\Model\UserModel;

class WxUserModel extends BaseModel
{
    private static $obj;

    public static $id_d;    //表id

    public static $uid_d;    //uid

    public static $wxname_d;    //公众号名称

    public static $aeskey_d;    //aeskey

    public static $encode_d;    //encode

    public static $appid_d;    //appid

    public static $appsecret_d;    //appsecret

    public static $wxid_d;    //公众号原始ID

    public static $weixin_d;    //微信号

    public static $headerpic_d;    //头像地址

    public static $token_d;    //token

    public static $wToken_d;    //微信对接token

    public static $createTime_d;    //create_time

    public static $updatetime_d;    //updatetime

    public static $tplcontentid_d;    //内容模版ID

    public static $shareTicket_d;    //分享ticket

    public static $shareDated_d;    //share_dated

    public static $authorizerAccess_token_d;    //authorizer_access_token

    public static $authorizerRefresh_token_d;    //authorizer_refresh_token

    public static $authorizerExpires_d;    //authorizer_expires

    public static $type_d;    //类型

    public static $webAccess_token_d;    // 网页授权token

    public static $webRefresh_token_d;    //web_refresh_token

    public static $webExpires_d;    //过期时间

    public static $qr_d;    //qr

    public static $menuConfig_d;    //菜单

    public static $waitAccess_d;    //微信接入状态,0待接入1已接入


    public static function getInitnation()
    {
        $class = __CLASS__;
        return static::$obj = !( static::$obj instanceof $class ) ? new static() : static::$obj;
    }

    /**获取公众号的appid跟sec
     * @return mixed
     */
    public function getMyWxConfig()
    {
        return $this->where( [ 'id' => 1 ] )->field( WxUserModel::$appid_d . ',' . WxUserModel::$appsecret_d )->find();
    }


    public function findUserId( $openId )
    {
        if ( !$openId ) {
            return false;
        }
        $userId = $this->where( [ self::$openId_d => $openId ] )->getField( self::$id_d );
        return $userId;
    }

    //保存数据,
    public function saveData()
    {
        //检查当前uid 在wx_user表中是否存在,如果存在则更新,否则插入
        if ( $this->findUid() ) {
            $update = $this->where('uid = '.$_SESSION[ 'saveData_wx' ]['uid'])->save( $_SESSION[ 'saveData_wx' ] );
            $set    = $this->setUserNickName();//更新未绑定手机用户的昵称

            if ( $update && $set ) {
                return true;
            }
            echo '更新昵称出错-1';die;

        }
        $add = $this->add( $_SESSION[ 'saveData_wx' ] );
        $str = M()->_sql();
        file_put_contents( './Uploads/qqq/user_sql11111.txt',$str."--------------" . date( 'Y-m-d H:i:s',time() ) . "-\r\n",FILE_APPEND );

        $set = $this->setUserNickName();//更新未绑定手机用户的昵称
        if ( $add && $set ) {
            return true;
        }
        echo '更新昵称出错-2';die;
    }

    public function findUid()
    {
        $uid    = $_SESSION[ 'saveData_wx' ][ 'uid' ];
        $status = $this->where( [ self::$uid_d => $uid ] )->getField( self::$uid_d );
        if ( $status ) {
            return true;
        }
        return false;
    }

    //设置微信用户的昵称
    public function setUserNickName()
    {
        //查询当前用户是否绑定手机号码,如绑定,即为合并过的用户,昵称不需要改!!
        $mobile = BaseModel::getInstance( UserModel::class )->where( UserModel::$id_d . ' = ' . $_SESSION[ 'saveData_wx' ][ 'uid' ] )->getField( UserModel::$mobile_d );
        if ( $mobile ) {
            return true;
        }
        return BaseModel::getInstance( UserModel::class )->updateNickName();


    }


}