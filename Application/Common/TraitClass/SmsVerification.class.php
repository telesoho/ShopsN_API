<?php
namespace Common\TraitClass;
use Common\Tool\Tool;
use Common\Model\ConfigChildrenModel;
use Common\Model\SystemConfigModel;
use Common\Model\BaseModel;

/**
 * 短信验证 
 */
trait SmsVerification
{
    public $error = '';

    protected $key = null;
   
    public function SmsVerification(array $smsConfig, $mobile)
    {
        if (empty($smsConfig) || !is_array($smsConfig) || !Tool::connect('ParttenTool')->validateData($mobile, 'mobile'))
        {
            $this->error = '暂无短信配置 或 手机格式不正确'; 
            
            return false;
        }
        $verfity = Tool::connect('PassMiMi')->getSmsCode();
        
        
        $argv = array(
            'Account' => $smsConfig['account'],     //必填参数。用户账号
            'Password'=>  $smsConfig['sms_pwd'],     //必填参数。（web平台：基本资料中的接口密码）
            'Content' => mb_convert_encoding(str_replace('[xxx]', $verfity, $smsConfig['sms_content']), "GB2312","UTF-8"),  //必填参数。发送内容（1-500 个汉字）UTF-8编码
            'Phones'  => $mobile,   //必填参数。手机号码。多个以英文逗号隔开
            //'stime'=>'',   //可选参数。发送时间，填写时已填写的时间发送，不填时为当前时间发送
            'Channel' => 1, //'【'.mb_substr( strrchr($smsConfig['sms_content'], '，'), 1, 4, 'UTF-8').'】',    //必填参数。用户签名。
            // 'extno'=>''    //可选参数，扩展码，用户定义扩展码，只能为数字
        );
        $flag = 0;
        $params = null;
        foreach ($argv as $key=>$value) {
            if ($flag != 0) {
                $params .= "&";
                $flag = 1;
            }
            $params.= $key."="; $params.= urlencode($value);// urlencode($value);
            $flag = 1;
        }
        //连接短信工具
        
        $smsInformation = Tool::connect('Mosaic')->requestPostSms( $smsConfig['sms_intnet'], $params);
       
        if ($smsInformation)
        {
            //设置sms_code 保存时间
            S('sms_code', $verfity, 120);
        }
        return $smsInformation ? $verfity : false;
    }
    
    /**
     * 获取系统配置
     */
    public function getConfig($key = null)
    {
        if (!S('config'))
        {
            //获取字表数据
            $children    = ConfigChildrenModel::getInitnation()->getAllConfig();
            //获取配置值
            $configValue = SystemConfigModel::getInitnation()->getAllConfig();
            //组合数据
            Tool::connect('ArrayParse', array('children' => $children, 'configValue'=> $configValue));
            $receive = array();
            $data = Tool::buildConfig()->parseConfig()->oneArray($receive);
            S('config', $receive, 100);
        }
        $arrayData = S('config');
        return $key === null ? $arrayData : $arrayData[$key];
    }

    /**
     * 获取组数据 配置
     */
    public function getGroupConfig ()
    {
        if (empty($this->key)) {
            return array();
        }
        $groupConfig = BaseModel::getInstance(SystemConfigModel::class)->getDataByKey($this->key);
        $receiveArray = array();
        Tool::connect('ArrayParse')->oneArray($receiveArray, $groupConfig);

        return $receiveArray;
    }

}