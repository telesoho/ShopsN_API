<?php
namespace Common\Tool\Intface;

/**
 * @author Administrator
 */
interface TimeInterFace
{
    abstract public function parseTime(array $data,$key ='create_time');
}

