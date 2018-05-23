<?php
namespace Common\Tool\Extend;

use Common\Tool\Tool;
use Common\Tool\Intface\Picture;

class PregPicture extends Tool implements Picture
{
    /**
     * {@inheritDoc}
     * @see \Common\Tool\Intface\Picture::delPicture()
     */
    public function delPicture( $data, $isPartten = false, $parttenCondition = 'imgSrc')
    {
        // TODO Auto-generated method stub
        
        if (!array_key_exists($parttenCondition, self::$partten))
        {
            return false;
        }
        
        $isSuccess = preg_match_all(parent::$partten[$parttenCondition], $data, $parseData);
        
        if ($isSuccess && !empty($parseData[1]))
        {
            $flg = 0;
            foreach ($parseData[1] as $key => &$file)
            {
                //本地文件的删除
                 is_file('./'.$file) ? unlink('./'.$file) : $flg++;
            }
            return $flg === 0 ? true : false;
        }
        else
        {
            return false;
        }
        
    }
}