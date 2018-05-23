<?php
namespace Common\Tool\Extend;

use Common\Tool\Tool;

class File extends Tool 
{
    
    public function parseFile(array $files, $setKey ='tmp_name')
    {
        if (empty($files))
        {
            return false;
        }
        /*一个图片时*/
        foreach ($files as $key => &$value)
        {  
            if (empty($value[$setKey])) 
            {
                continue;
            }
            $value[$setKey] = stripcslashes($value[$setKey]);
        }
        return $files;
    }
    
    //读一级目录
    public  function readOne($path)
    {
        if (!is_dir($path)  || !($dh = opendir($path))) {
            return array();
        }
        $fileArray = array();
    
        while (($file = readdir($dh)) !== false) {
            $fileArray[$file] = $file;
        }
    
        closedir($dh);
    
        return $fileArray;
    }
    
    /**
     * 读所有目录
     */
    public  function readAveryWhere($path, array &$data)
    {
        if (!is_dir($path) || !($dp=dir($path)))
        {
            return $data;
        }
    
        while($file=$dp->read()){
            if($file!='.'&& $file!='..'){
                self::readAveryWhere($path.'/'.$file, $data);
            } elseif (is_file($path.'/'.$file)) {
                $data[$file] = $file;
            }
        }
         
        $dp->close();
    
        return $data;
    }
}