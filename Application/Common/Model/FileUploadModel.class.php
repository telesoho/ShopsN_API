<?php
namespace Common\Model;

use Think\Upload;
use Common\Tool\Tool;

/**
 * 上传模型 
 */
class FileUploadModel 
{
    private static  $obj;
    public static function getInitnation()
    {
        $name = __CLASS__;
        return self::$obj = !(self::$obj instanceof $name) ? new self() : self::$obj;
    }
    
    
    public function UploadFile($config, $file = '', $driver = 'Local', $driverConfig = null)
    {
        $upload = new Upload($config, $driver, $driverConfig);
        $file = $upload->upload($file);

        if (empty($file))
        {
            return array();
        }

        $file = Tool::array_depth($file) ===2 ? Tool::parseToArray($file) : $file;
        
        $filePath = str_replace('.', null,C('GOODS_UPLOAD.rootPath')).$file['savepath'].$file['savename'];
        return $filePath;
    }
}