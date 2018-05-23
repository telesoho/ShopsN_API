<?php
namespace Home\Model;
use Think\Model;
use Think\Upload;
use Common\Tool\Tool;

class UserHeaderModel extends Model
{
    private static $obj ;
    
    public static function getInitation()
    {
        $class = __CLASS__;
        return self::$obj = !(self::$obj instanceof $class) ? new self() : self::$obj;
    }
    
     
    
    public function add($data='', $options=array(), $replace=false)
    {
        if (empty($data))
        {
            return false;
        }
        $data = $this->create($data);
        return parent::add($data, $options, $replace);
    }
    
    public function save($data = '', $options = array())
    {
        if (empty($data))
        {
            return false;
        }
        $data = $this->create($data);
        return parent::save($data, $options);
    }
    
    public function UploadFile($config, $file = '', $driver = 'Local', $driverConfig = null)
    {
        if (empty($config))
        {
            return false;
        }  
        $upload = new Upload($config, $driver, $driverConfig);
        $file = $upload->upload($file);
        if (empty($file))
        {
            return array();
        }
      
        $file = Tool::array_depth($file) ===2 ? Tool::parseToArray($file) : $file;
        $filePath = C('USER_HEADER').$file['savepath'].$file['savename'];
        
        return $filePath;
    }
    
    public function isHaveHeader($userId)
    {
        if ( !is_numeric($userId))
        {
            return false;
        }
        
        return $this->where('user_id = "%s"', $userId)->getField('user_header');
    }
    
    public function updateOrAdd($file,  $userId)
    {
        if (empty($file) || !is_numeric($userId))
        {
            return false;
        }
        $isHave = $this->isHaveHeader($userId);
        if (empty($isHave)) {
            
            $insert = $this->add(array(
                'user_header' => $file,
                'user_id'     => $userId
            ));
        } else {
            $insert = $this->save(array(
                'user_header' => $file,
            ), array(
                'where' => array('user_id' => $userId)
            ));
            
            $insert = Tool::partten(array($isHave));
        }
        return $insert ? $file : null;
    }
}