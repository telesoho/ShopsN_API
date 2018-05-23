<?php
namespace Common\TraitClass;

use Common\Tool\Tool;

trait MethodModel
{
    protected $addFields  ;
    /**
     * 从数组中去除字段
     * @return array
     */
    public function getSplitUnset(array $array, $split='_d')
    {
        if (empty($array))
        {
            return array();
        }
    
        foreach ($array as $key => & $value)
        {
            if (false === strpos($key, $split))
            {
                unset($array[$key]);
            }
        }
    
        return $array;
    }
    
    /**
     * @desc 筛选出不同的字段
     * @param array $array 要筛选的数据
     * @param int   $classPropNum 类中数据表字段的数量
     * @param int   $dbFieldNumber 数据表的字段数量
     * @return array
     */
    public function screenField(array $array, $classPropNum, $dbFieldNumber)
    {
        if (empty($array) || !is_array($array) || !is_int($classPropNum) || !is_int($dbFieldNumber))
        {
            return array();
        }
    
        $sub = $dbFieldNumber - $classPropNum;
    
        //开始循环的地方
        $start = $dbFieldNumber - $sub;
         
        $parseDbField = array();
        $i = 0;
        for ($i = $start; $i < $dbFieldNumber; $i++) {
            $parseDbField[$i] = $array[$i];
        }
        unset($dbFieldNumber);
    
        return $parseDbField;
    }
    
    /**
     * @desc 指定行 插入代码
     * @param <resource>$source</resource> 资源
     * @param string $addByThis 要添加得代码
     * @param int    $iLine     要添加到的行数
     * @param int    $index     为第几个字符之前，默认0
     * @return array
     */
    public function insertContent($source, $addByThis, $iLine, $index = 0)
    {
        if (!is_file($source)) {
            return array();
        }
         
        $file_handle = fopen($source, "r");
        $i = 0;
        $arr = array();
        while (! feof($file_handle)) {
            $line = fgets($file_handle);
            ++ $i;
            if ($i == $iLine) {
                if ($index == strlen($line) - 1)
                    $arr[] = substr($line, 0, strlen($line) - 1) . $addByThis;
                    else
                        $arr[] = substr($line, 0, $index) . $addByThis . substr($line, $index);
            } else {
                $arr[] = $line;
            }
        }
        fclose($file_handle);
        return $arr;
    }
    
    /**
     * 获取某段内容的行号
     * @param string $filePath 文件路径
     * @param string $target   待查找字段
     * @param bool   $first    是否再匹配到第一个字段后退出
     * @return array
     */
    public function getLineNum($filePath, $target, $first = false)
    {
        self::isFile($filePath);
    
        $fp = fopen($filePath, "r");
        $lineNumArr = array();
        $lineNum = 0;
        $flag = 0;
        while (! feof($fp)) {
            $lineNum ++;
            $lineCont = fgets($fp);
            if (strstr($lineCont, $target)) {
                $flag = 1;
                if ($first) {
                    return $lineNum;
                } else {
                    $lineNumArr[] = $lineNum;
                }
            }
        }
        // 或者这里 抛出 找不到数据所在行  $flag  标记变量
        if (empty($lineNumArr) || $flag === 0) {
            throw new \Exception('没有找到 数据所在行');
        }
        return  $lineNumArr;
    }
    
    /**
     * 文件是否存在
     */
    private static function isFile($file)
    {
        if (!is_file($file)) {
            throw new \Exception('文件不存在');
        }
    }
    
    /**
     * @desc 重写文件
     * @param string $file 文件路径
     * @param array  $fileContent 要写入的内容；
     * @return bool;
     */
    public function rewriteFile($file, array $fileContent)
    {
        self::isFile($file);
    
        if (empty($fileContent) || !is_array($fileContent)) {
            throw new \Exception('内容不能为空，且只能是数组');
        }
    
        unlink($file);
        $status = false;
    
        foreach($fileContent as $value)
        {
            $status = file_put_contents($file, $value, FILE_APPEND);
        }
    
        return $status;
    }
    
    /**
     * 批量更新 组装sql语句 
     * @param array $parseData 要更新的数据【已经解析好的】
     * @param array $keyArray  要修改的键
     * @param string 表名
     * @return $sql
     */
    public function buildUpdateSql( array $parseData, array $keyArray, $table)
    {
        if (empty($parseData) || !is_array($parseData) || empty($table)) {
            return array();
        }
        
        $sql = 'UPDATE '.$table.'  SET ';
        
        $flag = 0;
        
        foreach ($keyArray as $k => $v) {
            $sql .=  $v .'= CASE '. '`id`';
            foreach ($parseData as $a => $b)
            {
                $sql .= sprintf(" WHEN %s THEN %s \t\n ", $a,$b[$flag]);
            }
            $flag++;
            $sql .='END,';
        }
        
        $sql = substr($sql, 0, -1);
         
        $sql .= ' WHERE `id` in('.implode(',', array_keys($parseData)).');';
        
        return $sql;
    }
    
    /**
     * 获取规格项名称
     * @param array $data 商品数组
     * @param string $splitKey 分割建
     * @return array
     */
    public function getSpecItemName(array $data, $splitKey, $whereField= null)
    {
        if (empty($data) || !is_array($data) || !is_string($splitKey) || empty($splitKey)) {
            return array();
        }
    
        $idString = Tool::characterJoin($data, $splitKey);
        if (false !== strpos($idString, '_')) {
            $single = explode(',', str_replace('"', null, $idString));
            
            
            foreach ($single as $key => & $value) {
               $value = str_replace('_', ',', $value);
            }
            $idString = implode(',', $single);
        }
       
        if (empty($idString)) {
            return  array();
        }
        
        $whereField = empty($whereField) ? static::$id_d : $whereField;
        
        
        
        
        $specData = $this->field($this->addFields)->where($whereField.' in ('.$idString.')')->select();
        return empty($specData) ? array() : $specData;
    }
    
    public function addFields ()
    {
        return $this->addFields;
    }
    
    public function setAddFields ($filed)
    {
        if (empty($filed)) {
            throw new \Exception('字段不能为空');
        }
        $this->addFields = $filed;
    }
}