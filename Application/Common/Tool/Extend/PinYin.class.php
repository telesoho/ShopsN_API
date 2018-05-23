<?php
namespace Common\Tool\Extend;

use Common\Tool\Tool;

/**
 * 拼音相关 
 */
class PinYin extends Tool
{
    /**
     * @desc 获取汉字的首拼音字母
     * @param string $str
     * @return string|NULL
     */
    public function getFirstEnglish($str)
    {
        if(empty($str))
        {
            return '';
        }
        $fchar=ord($str{0});
        if($fchar >= ord('A') && $fchar <= ord('z')) return strtoupper($str{0});
        
        $gb2312String=iconv('UTF-8','gb2312',$str);
        
        $UTF_8=iconv('gb2312', 'UTF-8', $gb2312String);
        
        $string = $UTF_8==$str ? $gb2312String :$str;
        
        $ascII = ord($string{0})*256 + ord($string{1}) - 65536;
        
        if($ascII >= -20319 && $ascII <= -20284) return 'A';
        if($ascII >= -20283 && $ascII <= -19776) return 'B';
        if($ascII >= -19775 && $ascII <= -19219) return 'C';
        if($ascII >= -19218 && $ascII <= -18711) return 'D';
        if($ascII >= -18710 && $ascII <= -18527) return 'E';
        if($ascII >= -18526 && $ascII <= -18240) return 'F';
        if($ascII >= -18239 && $ascII <= -17923) return 'G';
        if($ascII >= -17922 && $ascII <= -17418) return 'H';
        if($ascII >= -17417 && $ascII <= -16475) return 'J';
        if($ascII >= -16474 && $ascII <= -16213) return 'K';
        if($ascII >= -16212 && $ascII <= -15641) return 'L';
        if($ascII >= -15640 && $ascII <= -15166) return 'M';
        if($ascII >= -15165 && $ascII <= -14923) return 'N';
        if($ascII >= -14922 && $ascII <= -14915) return 'O';
        if($ascII >= -14914 && $ascII <= -14631) return 'P';
        if($ascII >= -14630 && $ascII <= -14150) return 'Q';
        if($ascII >= -14149 && $ascII <= -14091) return 'R';
        if($ascII >= -14090 && $ascII <= -13319) return 'S';
        if($ascII >= -13318 && $ascII <= -12839) return 'T';
        if($ascII >= -12838 && $ascII <= -12557) return 'W';
        if($ascII >= -12556 && $ascII <= -11848) return 'X';
        if($ascII >= -11847 && $ascII <= -11056) return 'Y';
        if($ascII >= -11055 && $ascII <= -10247) return 'Z';
        return null;
    }
    
}