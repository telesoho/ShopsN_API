<?php

namespace Common\Controller;

use Common\TraitClass\SmsVerification;

class sendSMS_DayuController
{
    use SmsVerification;
    private $AccessKeyId;
    private $AccessKeySecret;
    private $Signatures;//短信签名
    private $TemplateNum;//短信模板编号
    private $UserPhone;//短信接收者手机
    private $Code;//验证码
    public function __construct($UserPhone,$check_id = 2)
    {
        $this->key = 'a_li_da_yu';
        $config = $this->getGroupConfig();
        $this->AccessKeyId = $config['access_key_id'];
        $this->AccessKeySecret = $config['access_key_secret'];
        $this->Signatures = $config['signatures'];
        $this->TemplateNum = M('sms_template')->where(['sms_id' => 2,'check_id' => $check_id])->getField('template');

        $this->UserPhone = $UserPhone;
        $this->Code = $this->getCode();
    }

    public function getCode($length=6)
    {
        $code = rand(pow(10,($length-1)), pow(10,$length)-1);
        S( 'short_mobile'.$this->UserPhone,$this->UserPhone,C( 'send_msg_time' ) );
        S( 'short_msg_code'.$code,$code,C( 'send_msg_time' ) );
        return $code;
    }
    //发送短信
    public function send()
    {
        $send = new SmS_DayuController(
            $this->AccessKeyId,
            $this->AccessKeySecret
        );
        $result = $send->sendSms(
            $this->Signatures, // 短信签名
            $this->TemplateNum, // 短信模板编号
            $this->UserPhone, // 短信接收者
            Array(  // 短信模板中字段的值
                "code"=>$this->Code//验证码
            )
        );
        $result->sessionId=session_id();
       return $result;

    }


}