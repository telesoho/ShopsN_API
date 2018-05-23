<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/8 0008
 * Time: 下午 3:13
 */
namespace Common\Controller;
use Common\Controller\SmSController;
use Think\Controller;

class MsmFactory extends Controller
{
    public function factory($UserPhone,$tem)
    {
        $type = M('sms_check')->where(['check_title' => '开启短信'])->getField('status');
        switch($type){
            case '0':
                return $this->error('短信功能暂未开启');
                break;
            case '1':
                $huaxin =  SMSController::getinstance();//华信
                return $huaxin ->send_msg($UserPhone,$tem);
            break;

            case '2':
                $dayu = new sendSMS_DayuController($UserPhone,$tem);
                return $dayu->send();
            break;
        }
    }
}