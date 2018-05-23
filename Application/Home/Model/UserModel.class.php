<?php
namespace Home\Model;


use Org\Util\RandString;
use Think\Model;

/**
 * 用户模型 
 */
class UserModel extends Model
{
    /**
     * 获取 积分余额 
     */
    public function getIntegral($userId)
    {
        if (empty($userId) || !is_numeric($userId))
        {
            return array();
        }
        return $this->where(array('id'=>$userId))->field('add_jf_currency,add_jf_limit')->find();
    }

    public function addUser($arr){
        $salt =  RandString::randString();
        $password = $arr['password'];
        $arr['salt'] = $salt;
        $arr['password'] = salt_mcrypt($password,$salt);
        $this->add($arr);
    }

    /**
     * 添加前操作
     */
    protected function _before_insert(&$data,$options)
    {
        $data['create_time'] = time();
        $data['update_time'] = time();
        return $data;
    }
    //根据user_id查询收货人默认收货地址
    public function getDefaultAddressByUserId($user_id){
        $user_id = $_SESSION['user_id'];
        if (empty($user_id)) {
            return false;
        }
        $where['user_id'] = $user_id;
        $where['status'] = '1';
        $field = 'realname,mobile,create_time,prov,city,dist,address,zipcode';
        $res = M('user_address')->field($field)->where($where)->find();
        return $res;
    }
    //查询用户收货地址
    public function getAddressByUserId($user_id){
        $user_id = $_SESSION['user_id'];
        if(empty($user_id) ) {   
            return false;
        }
        $field = 'id,realname,mobile,user_id,create_time,update_time,prov,city,dist,address,status,zipcode';
        $where['user_id'] = $user_id;
        $res = M('user_address')->field($field)->where($where)->select();
        return $res;
    }
    //查询单条收货地址
    public function getAddressById($id){
        if(empty($id) ) {   
            return false;
        }
        $field = 'id,realname,mobile,user_id,create_time,update_time,prov,city,dist,address,status,zipcode';
        $where['id'] = $id;
        $res = M('user_address')->field($field)->where($where)->find();
        return $res;
    }
    //根据user_id查询用户企业信息
    public function getEnterpriseByUserId($user_id){
        $user_id = $_SESSION['user_id'];
        if(empty($user_id) ) {   
            return false;
        }
        $where['user_id'] = $user_id;
        $res = M('enterprise')->where($where)->find();
        if (!empty($res['reg_address'])) {
            $reg_address = explode("-", $res['reg_address']);
            $res['province'] = $reg_address[0];
            $res['city'] = $reg_address[1];
            $res['area'] = $reg_address[2];
        }
        if (!empty($res['place_address'])) {
            $place_address = explode("-", $res['place_address']);
            $res['province1'] = $place_address[0];
            $res['city1'] = $place_address[1];
            $res['area1'] = $place_address[2];
        }
        return $res;
    }

    //根据user_id查询用户信息
    public function getUserByUserId($user){
        $user_id = $_SESSION['user_id'];
        if(empty($user_id) ) {   
            return false;
        }
        $where['id'] = $user_id;
        $field = 'id,user_name,nick_name,email,sex,mobile,last_logon_time';
        $res = M('user')->field($field)->where($where)->find();
        return $res;
    }
    //根据用户信息查询头像
    public function getUserHeaderByUser($data){
        if(empty($data) ) {   
            return false;
        }
        $where['user_id'] = $data['id'];
        $field = 'user_header';
        $img = M('user_header')->field($field)->where($where)->find();
        $data['user_header'] = $img['user_header'];
        return $data;
    }
    //根据user_id查询用户的密保问题
    public function getQuestionByUserId($user_id){
         $user_id = $_SESSION['user_id'];
        if(empty($user_id) ) {   
            return false;
        }
        $where['user_id'] = $user_id;
        $field = 'id,problem,answer';
        $data = M('security_question')->field($field)->where($where)->select();
        return $data;
    }
}