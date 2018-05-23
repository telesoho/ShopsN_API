<?php
namespace Common\Model;


/**
 * 用户地址模型
 */
class UserModel extends BaseModel
{

    private static $obj;


    public static $id_d;

    public static $mobile_d;

    public static $createTime_d;

    public static $status_d;

    public static $updateTime_d;

    public static $openId_d;

    public static $password_d;

    public static $userName_d;

    public static $nickName_d;

    public static $birthday_d;

    public static $idCard_d;

    public static $email_d;

    public static $levelId_d;

    public static $sex_d;

    public static $integral_d;

    public static $lastLogon_time_d;

    public static $salt_d;

    public static $recommendcode_d;

    public static $validateEmail_d;

    public static $memberStatus_d;

    public static $memberDiscount_d;

    public static $pId_d;


    public static function getInitnation()
    {
        $class = __CLASS__;
        return self::$obj = !( self::$obj instanceof $class ) ? new self() : self::$obj;
    }

    public function findUserId_User( $openId )
    {
        if ( !$openId ) {
            return false;
        }
        $userId = $this->where( [ self::$openId_d => $openId ] )->getField( self::$id_d );
        return $userId;
    }

    public function updateNickName()
    {
        $updateNick = $this->where( self::$id_d . ' = ' . $_SESSION[ 'saveData_wx' ][ 'uid' ] )->save( [ self::$nickName_d => $_SESSION[ 'saveData_wx' ][ 'wxname' ] ] );
        $str = M('user')->_sql();
        file_put_contents( './Uploads/qqq/user_sql222222222.txt',$str."--------------" . date( 'Y-m-d H:i:s',time() ) . "-\r\n",FILE_APPEND );

        if ( false !== $updateNick ) {
            return true;
        }
        return false;
    }


}