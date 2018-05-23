<?php
namespace Common\Tool\Extend;

use Common\Tool\Tool;
use Common\Tool\Intface\Picture;

class UnlinkPicture extends Tool implements Picture
{
    /**
     * {@inheritDoc}
     * @see \Common\Tool\Intface\Picture::delPicture()
     */
    public function delPicture( $data, $isPartten = false, $parttenCondition = 'imgSrc')
    {
        // TODO Auto-generated method stub
        return !empty($data) && is_file('./'.$data)  ? unlink('./'.$data) : false;
    }
}