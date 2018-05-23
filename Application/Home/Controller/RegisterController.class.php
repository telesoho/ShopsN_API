<?php
namespace Home\Controller;
use Common\Controller\sendSMS_DayuController;
use Think\Controller;
class RegisterController extends CommonController {
    //注册发送验证码
    public function re_send_msg(){

        if(IS_POST){
            $mobile=I('post.mobile');
            $user_name=I('post.user_name');
            $find=M('user')->where(array('user_name'=>$user_name))->getField('user_name');
            if($find){$this->returnMessage(0,'用户名已被占用','');}
            $finds=M('user')->where(array('mobile'=>$mobile))->getField('mobile');
            if($finds){$this->returnMessage(0,'手机号已被注册','');}

            $type = M('sms_check')->where(['check_title' => '开启短信'])->getField('status');
            switch($type){
                case '0':
                    return $this->error('短信功能暂未开启');
                    break;
                case '1':
                    $this->send_msg($mobile);
                    break;
                case '2':
                    $dayu = new sendSMS_DayuController($mobile,2);
                    $data = $dayu->send();
                    if($data->Code == 'OK'){
                        $this->returnMessage( 1,'短信发送成功',$data);
                    }
                    break;
            }

        }
    }
    /**
     * 生成验证码
     */

    public function verify(){
        $type=I('post.type');
        if($type=='Android'){//如果是安卓客户端
            $data=rand(1111,9999);
            session('android_verify',$data,C('send_msg_time'));
        }else {
            ob_clean();        //清除缓存
            $Verify = new \Think\Verify();
            $Verify->fontSize = 20;    //验证码字体大小
            $Verify->length = 4;    //验证码位数
            $picture = $Verify->entry();
            $data = base64_encode($picture);
        }
        $this->returnMessage(1,'返回成功',$data);
    }
    /**
     * 找回密码
     */
    public function find_pwd(){
        if (IS_POST) {
            $verify=I('post.verify');
            $mobile = I('post.mobile');
            $code = I('post.code');
            $type=I('post.type');
            if(empty(S('short_msg_code'.$code))){$this->returnMessage(0,'验证码已过期','');}
            if(S('short_mobile'.$mobile)!=$mobile){$this->returnMessage(0,'手机号码不一致','');}
            if(S('short_msg_code'.$code)!=$code){$this->returnMessage(0,'验证码不正确','');}
//            if($type=='Android'){//如果是安卓客户端
//                if( $verify!=session('android_verify'))
//                $this->returnMessage(0,'验证码不正确','');
//            }else {
//                $veri = new \Think\Verify(array('reset' => false));
//                if ($veri->check($verify) == false) {
//                    $this->returnMessage(0, '验证码不正确', '');
//                }
//            }
            $user = M('user')
                ->where([
                    'mobile'=>$mobile
                ])
                ->getField('id');
            $app_user_id=zhong_encrypt($user['id']);
            if (empty($mobile)) {
                $this->returnMessage(0, '手机号不存在', "");
            } else {
                session('check',1,C('send_msg_time'));//生成session，修改密码时验证
                S($user,'ok');
                session('short_mobile',null);
                session('short_msg_code',null);
                session('android_verify',null);
                $this->returnMessage(1, '返回成功', $app_user_id);
            }
        }
    }
    /**
     * 找回密码--重置密码
     */
public function resetPassword(){
    if(Is_POST) {
        $user_id =zhong_decrypt(I('post.app_user_id'));
        $check = S($user_id);
        if( $check =='ok') {//是否验证过

            $new_password1 =I('post.newPassword1');
            $new_password2 =I('post.newPassword2');
            if ($new_password1 != $new_password2) {
                $this->returnMessage(0, '两次密码设置不一致', '');
            }
            $data['password'] = md5($new_password1);
            M('user')->where([
                'id' => $user_id
            ])
                ->save($data);
            S($user_id,null);
            $this->returnMessage(1, '修改成功', '');
        }else{
            $this->returnMessage(0, '非法操作', $user_id);
        }

    }
}
    //注册表单提交---验正手机和验证码是否正确
    public function register(){
        if(IS_POST){
            $user_name=I('post.user_name');
            $mobile=I('post.mobile');
            $verify=I('post.verify');
            $email=I('post.email');
            $password=I('post.password');
            $re_password=I('post.re_password');
            if(empty($password)) $this->returnMessage(0,'密码不能为空');
            if($password!=$re_password){$this->returnMessage(0,'密码不一致','');}
            if(empty($user_name)) $this->returnMessage(0,'用户名不能为空','');
            if(empty($mobile)) $this->returnMessage(0,'手机号不能为空','');
            if(empty($email)) $this->returnMessage(0,'邮箱不能为空','');
            if(empty(S('short_msg_code'.$verify))){$this->returnMessage(0,'验证码已过期','');}
            if(S('short_mobile'.$mobile)!=$mobile){$this->returnMessage(0,'手机号码不一致','');}
            if(S('short_msg_code'.$verify)!=$verify){$this->returnMessage(0,'验证码不正确','');}
            $data=array(
                'user_name'=>$user_name,
                'mobile'=>$mobile,
                'email'=>$email,
                'password'=>md5($password),
                'create_time'=>time(),
                'update_time'=>time(),
                'nick_name'=>$user_name
            );
              $exist=M('user')->where(['user_name'=>$user_name])->getField('user_name');
              $exist_mobile=M('user')->where(['mobile'=>$mobile])->getField('mobile');
            if($exist) $this->returnMessage('1','用户名被占用','');
            if($exist_mobile) $this->returnMessage('1','手机号已注册','');
                if(M('user')->add($data)){
                    session('short_mobile',null);
                    session('short_msg_code',null);
                    $this->returnMessage(1,'注册成功',$mobile);
                }

        }
    }
    //账户登录
    public function login_account(){

        if(IS_POST){
            $account=I('post.account');
            $password=I('post.password');
            $model=M('user');
            //if(strpos($account,"@")){}//邮箱登录
            $find_mobile=$model
                ->where(
                    array('mobile'=>$account,'password'=>md5($password))
                )
                ->find();
            $find_user_name=$model
                ->where(
                    array('user_name'=>$account,'password'=>md5($password))
                )
                ->find();
            if(!empty($find_mobile))
            {
               $data=array(
                    'app_user_id'=>zhong_encrypt($find_mobile['id']),
                    'app_user_type'=>zhong_encrypt($find_mobile['user_type']),
                    'mobile'=>$find_mobile['mobile']
                );
                $model->where(
                        array('mobile'=>$find_mobile['mobile'])
                    )
                    ->save(
                        array('last_logon_time'=>time())
                    );
                $this->returnMessage(1,'登录成功',$data);
            }
            if(!empty($find_user_name)){
                $data=array(
                         'app_user_id'=>zhong_encrypt($find_user_name['id']),
                         'app_user_type'=>zhong_encrypt($find_user_name['user_type']),
                         'mobile'=>$find_user_name['mobile']
                );
                $model->where(
                    array('mobile'=>$find_user_name['mobile'])
                )
                    ->save(
                        array('last_logon_time'=>time())
                    );
                $this->returnMessage(1,'登录成功',$data);
            }else{
                $this->returnMessage(0,'用户名或密码不正确','');
            }

        }
    }
    //短信登录--短信发送
    public function short_message_send(){
        if(IS_POST){
            $mobile=I('post.mobile');
            $model=M('user');
            $find=$model
                ->where(
                    array('mobile'=>$mobile)
                )
                ->find();
            if(!empty($find))
            {//如果不为空--发送短信验证
                $this->send_msg($find['mobile']);
            }else{
                $this->returnMessage(0,'手机号不存在','');
            }
        }
    }
    //短信登录
    public function short_login(){
        if(IS_POST){
            $mobile=I('post.mobile');
            $verify=I('post.verify');
            if(empty(session('short_msg_code')))
            {$this->returnMessage(0,'验证码已过期','');}
            if(session('short_mobile')!=$mobile)
            {$this->returnMessage(0,'手机号码不一致','');}
            if(session('short_msg_code')!=$verify)
            {$this->returnMessage(0,'验证码不正确','');}
            M('user')
                ->where(
                    array('mobile'=>$mobile)
                )
                ->save(
                    array('last_logon_time'=>time())
                );
            $find=M('user')->where(array('mobile'=>$mobile))->find();
			$data=array(
                'app_user_id'=>zhong_encrypt($find['id']),
                'app_user_type'=>zhong_encrypt($find['user_type']),
                'mobile'=>$find['mobile']
            );
            $this->returnMessage(1,'登录成功',$data);

        }
    }
    //我的钱包
    private function my_wallet($id){
        //头像和用户名
        $nick_name=M('user')->where(array('id'=>$id))->getField('nick_name');//昵称
        $header_img=M('user_header')->where(array('user_id'=>$id))->find();
        //我的积分
        $integral=M('user')->where(array('id'=>$id))->getField('integral');
        //账号余额
        $balance=M('balance')->where(array('user_id'=>$id))->getField('account_balance');
        if(empty($balance)){
            $data['user_id']=$id;
            M('balance')->add($data);
            $balance=0;
        }
        //优惠劵张数
        $join='db_coupon ON db_coupon.id=db_coupon_list.c_id';
        $my_coupon=M('coupon_list')->join($join)->where("`user_id`=$id AND `use_end_time`>".time())->count();
        //发票数
        //余单
        $data=array(
            'integral'=>$integral,
            'balance' =>$balance,
            'my_coupon'=>$my_coupon,
            'nick_name'=>$nick_name,
            'header_img'=>$header_img,
            'fapiao'=>0,
            'yudan'=>0,
        );
        return $data;
    }
    
	//第三方登录
    public function otherLogin(){
      if(IS_POST){
          $type=I('post.type');
          switch($type){
              case 1:
                  $data['qq_id']=I('post.accout');
                  break;
              case 2:
                  $data['weixin_openid']=I('post.accout');
                  break;
              case 3:
                  $data['sina_id']=I('post.accout');
                  break;
          }
          $find=M('user_other_accout')->where($data)->find();
          if(!$find){
              $this->returnMessage(0,'您暂未绑定账号!','');
          }else{//如果查找到授权登录相关信息-证明已绑定过，跳转
              $user=M('user')
                  ->where(
                      array('id'=>$find['user_id'])
                  )
                  ->find();
              $data=array(
                  'app_user_id'=>zhong_encrypt($user['id']),
                  'app_user_type'=>zhong_encrypt($user['user_type']),
                  'mobile'=>$user['mobile']
              );
              M('user')
                  ->where(
                      array('id'=>$find['user_id'])
                  )
                  ->save(
                      array('last_logon_time'=>time())
                  );
              $this->returnMessage(1,'登录成功',$data);
          }
      }
    }
    //与第三方绑定账号
    public function bindOtherAccount(){
        if(IS_POST){
            $user_model=M('user');
            $type=I('post.type');
            $accout=I('post.accout');
            $mobile=I('post.mobile');
            $password=md5(I('post.password'));
            $find=$user_model
                ->where(
                    array('mobile'=>$mobile)
                )
                ->find();
            if(!$find)
            {
                $this->returnMessage(0,'不存在此账号','');
            }
            if($find['password']!==$password)
            {
                $this->returnMessage(0,'账号密码错误','');
            }
            switch($type){
                case 1:
                    $data['qq_id']=I('post.accout');
                    break;
                case 2:
                    $data['weixin_openid']=I('post.accout');
                    break;
                case 3:
                    $data['sina_id']=I('post.accout');
                    break;

            }
            //进一步判断--是否已经和其他账号进行过绑定
            $user_other_accout_model=M('user_other_accout');
            $isbind=$user_other_accout_model->where($data)->find();
            if($isbind)
            {
                $this->returnMessage(0,'此账号已与同类型的其他账号绑定过','');
            }
            $isexist=$user_other_accout_model
                ->where(
                    array('user_id'=>$find['id'])
                )
                ->find();
            if($isexist)
            {//如果已经存在
                $user_other_accout_model
                    ->where(
                        array('user_id'=>$find['id'])
                    )
                    ->save($data);
                  $this->returnMessage(1,'绑定成功','');
            }else{//如果不存在-则创建
                  $data['user_id']=$find['id'];
                $user_other_accout_model->add($data);
                  $this->returnMessage(1,'绑定成功','');
            }
        }
    }
    //找回密码发送短信
    public function re_send_binding(){

        if(IS_POST){
            $mobile=I('post.mobile');
            $type = M('sms_check')->where(['check_title' => '开启短信'])->getField('status');
            switch($type){
                case '0':
                    return $this->error('短信功能暂未开启');
                    break;
                case '1':
                    $this->send_msg($mobile);
                    break;
                case '2':
                    $dayu = new sendSMS_DayuController($mobile,2);
                    $data = $dayu->send();
                    if($data->Code == 'OK'){
                        $this->returnMessage( 1,'短信发送成功',$data);
                    }
                    break;
            }

        }
    }
}