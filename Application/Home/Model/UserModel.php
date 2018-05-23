<?php
namespace Home\Model;
use Think\Model;
class UserModel extends Model{
    /*protected $_validate = array(
        array('verify','require','验证码必须！'), //默认情况下用正则进行验证
        array('name','','帐号名称已经存在！',0,'unique',1), // 在新增的时候验证name字段是否唯一
        array('value',array(1,2,3),'值的范围不正确！',2,'in'), // 当值不为空的时候判断是否在一个范围内
        array('repassword','password','确认密码不正确',0,'confirm'), // 验证确认密码是否和密码一致
        array('password','checkPwd','密码格式不正确',0,'function'), // 自定义函数验证密码格式   );
       }
      $User = D("User"); // 实例化User对象
      if (!$User->create()){     // 如果创建失败 表示验证没有通过 输出错误提示信息
         exit($User->getError());}
     else{     // 验证通过 可以进行其他数据操作}


    */
    protected $_validate = array(
                        array('user_name','require','用户名必须！'), //默认情况下用正则进行验证
                        array('verify','require','验证码必须！'), //默认情况下用正则进行验证
                        array('mobile','require','手机号必须！'), //默认情况下用正则进行验证
                        array('email','require','邮箱必须！'), //默认情况下用正则进行验证
                        array('re_password','password','确认密码不正确',0,'confirm'),
                        array('user_name','','帐号名称已经存在！',0,'unique',1),
                    );
       }