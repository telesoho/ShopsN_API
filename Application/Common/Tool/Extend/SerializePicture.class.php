<?php
namespace Common\Tool\Extend;

use Common\Tool\Tool;
use Common\Tool\Intface\Picture;

class SerializePicture extends Tool implements Picture
{
    /**
     * {@inheritDoc}
     * @see \Common\Tool\Intface\Picture::delPicture()
     */
    public function delPicture( $data, $isPartten = false, $parttenCondition = 'imgSrc')
    {
        // TODO Auto-generated method stub
        $data = unserialize($data['pic_tuji']);
        
        $flag = 0;
        foreach ($data as $key => &$value)
        {
            $value = false !== strpos($value, '/Uploads/good/') ? '/Uploads/good/'.$value : $value;
            
            is_file('./'.$value) ? unlink($value) : $flag++;
        }
        return $flag === 0 ? true : false;
    }
}