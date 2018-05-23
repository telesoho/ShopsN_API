<?php
namespace Common\Model;

use Think\Model;
use Think\Exception;
use Think\Page;
use Think\Hook;
use Common\TraitClass\MethodModel;
use Common\Tool\Tool;
use Common\TraitClass\ModelToolTrait;

/**
 * 数据操作 控制
 * @author 王强
 */

abstract class BaseModel extends Model
{
   use MethodModel;
   use ModelToolTrait;
   private static $obj = array();
   
   protected static $find = 'public static function getInitnation()';
   
   const DESC = ' DESC ';
   
   const ASC  = ' ASC ';
   
   const DBAS   = ' as ';
   
   const SUFFIX = '_d';
    /**
     * 取得子类的实例【用父类实例化子类】
     */
    public static function getInstance($className, $metheds='getInitnation')
    {
//         $array = array();
        
//         $model = S('model')[$className];
        
        /**
         * 确保只调用一次【单例模式】 
         */
        if (empty(self::$obj[$className]))
        {
            self::$obj[$className] = $className::$metheds();
           
//             S('model', $array, 100);
        }
      
        return self::$obj[$className];
    }
    
    /**
     * 组装搜索条件
     * @param array $data 搜索条件数组
     * @return array;
     */
    public function buildSearch(array $data,$isLike = false, array $likeSearch = array())
    {
        if (! is_array($data) || empty($data)) {
            return array();
        }
        //处理查询条件
        $orderBy = Tool::buildActive($data);
        
        if (empty($orderBy)) {
            return array();
        }
        
        $where = $this->create($orderBy);
        
        if ($isLike && empty($likeSearch)) {
            $queryWhere = array();
            foreach ($where as $key => $value) {
              $queryWhere[$key] = array('like', '%'.$value.'%');  
            }
            return $queryWhere;
        } else if ($isLike && !empty($likeSearch)) {
            foreach ($likeSearch as $key => $value) {
                if (!array_key_exists($value, $where)) {
                    continue;
                }
                $where[$value] = array('like', '%'.$value.'%');
            }
        }
        
        return $where;
    }
    
    
    /**
     * 利用__set()映射字段 
     */
    public function ormDbFileds()
    {
        $fieldsDb = $this->getDbFields();
        
        if (empty($fieldsDb)) {
            throw new \Exception('没有字段');
        }
        
        foreach ($fieldsDb as $key => $value) {
            $this->$value = $value;
        }
        
        return ;
    }
    
    
    /**
     * 实现 类的静态属性添加【代码】 
     */
    private final function autoAddProp(Model $model, $suffix = '_')
    {
        $this->throwError($model);
        
        try {
            $obj = new \ReflectionObject($model);
            
            $staticProp = $obj->getStaticProperties(); 
            
            $addByThisModel = array();

            $dbField = $model->getDbFields();

            $this->error($dbField, $model);
            
            $filePathName = $obj->getFileName();
            
            //连接 ArrayChildren 类 【为静态调用子类的方法做准备】
            
            //截取子类模型数据库属性字段 【因为 子类可能有其他的属性字段【getSplitUnset是ArrayChildren里的方法 】】
            
            $dbFileds  = $this->getSplitUnset($staticProp);
            if (!empty($dbFileds))
            {
                $dbFieldNumber = count($dbField);
               
                $classPropNumber = count($dbFileds);
                
                //是否有新添加得字段
                $diff    = $dbFieldNumber - $classPropNumber;
               
                if ($diff === 0) {
                    return  false; //不用添加
                } else {
                    // 由于索引 从 0开始 
                    // 筛选 要添加得字段
                    $addByThisModel = $this->screenField($dbField, $classPropNumber, $dbFieldNumber);
                   
                }
            } else {
                return self::rewriteModel($filePathName, $dbField, $suffix);
            }
           
            $status = false;
            if (!empty($addByThisModel)) {
                 $status = self::rewriteModel($filePathName, $addByThisModel, $suffix);
            }
            return $status;
           
        } catch (Exception $e) {
            $e->getTrace();
        }
    }
    
    /**
     *  排序
     */
    protected  static function sort(array $addByThisModel)
    {
        if (empty($addByThisModel) || !is_array($addByThisModel)) {
            return array();
        }
        $number = count($addByThisModel);
        $flag = array();
        // 预防 键不从0开始
        foreach  ($addByThisModel as $value) {
            $flag[] = $value;
        }
        
        $arr = array();
       
        for ($j = $number-1; $j >= 0 ; $j--)
        {
            $arr[$j] = $flag[$j];
        }
        
        unset($addByThisModel);
        return $arr;
    }
    /**
     * @desc 写文件 不允许外部任何文件调用
     */
    private static function rewriteModel($filePathName, array $addByThisModel, $suffix = '_')
    {
       
        $line = self::getLineNum($filePathName, self::$find, true);
        $classData = array();
        $startString = "\n\tpublic static \$";
        $status = false;
        $i = -2;
        
        $addByThisModel = self::sort($addByThisModel);
       
        foreach ($addByThisModel as $key => & $value) {
            $length = strpos($value, $suffix);
            $i++;
            if ($length !== false) {
                $endString   = ucfirst(substr($value, $length+1)).self::SUFFIX.";\n\n";
        
                $newString = $startString.strchr($value, $suffix, true);
        
                $classData = self::insertContent($filePathName, $newString.$endString, $line + $i);
                $i--;
                $status =self::rewriteFile($filePathName, $classData);
            } else {
        
                $noString = $startString.$value."_d;\n\n";
                $classData = self::insertContent($filePathName, $noString, $line + $i);
                $i--;
                $status = self::rewriteFile($filePathName, $classData);
            }
        }
        
        return $status;
    }
   
    /**
     * 为子类中的数据库属性字段赋值 
     * @param Model   $model  子类模型
     * @param string  $suffix 数据表字段后缀
     * @return 
     */
    private final  function setDbFileds(Model $model, $suffix = '_d')
    {
        $this->throwError($model);
        
        try{
            // 反射类中的数据库属性
            $reflection         =  new \ReflectionObject($model);
            $staticProperties   =  $reflection->getStaticProperties();
          
            if (!empty($staticProperties))
            {
                //截取子类模型数据库属性字段 
                
                $dbFileds  = $this->getSplitUnset($staticProperties);
                
                //获取数据库的字段
                $dbData    = $model->getDbFields();
                
                // 如果此数据表没有字段 ，那么抛出异常
                $this->error($dbData, $model);
                
                // 获取字段数量
                $flag = count($dbData);
                // 标记变量
                $i    = 0;     
                foreach ($dbFileds as $key => &$value)
                {
                    // 利用了 可变变量的特性
                    $model::$$key = $dbData[$i];

                    $i++;
                    //如果 标记变量 大于 数据表的字段数量 就结束循环
                    if ($i > $flag-1)
                    {
                        break;
                    }
                }
             }
        } catch (\Think\Exception $e) {
            throw new \ErrorException('该模型不匹配基类模型');
        }
    }
    
    private function error($data, Model $model)
    {
        if (empty($data))
        {
            throw new \Exception('该模型【'.get_class($model).'】对应的数据表无字段');
        }
    }
    
    /**
     * 去除不查询的字段 
     * @param array $fields 要去除查询的字段
     * @return array;
     */
    public function deleteFields( array $fields)
    {
        $fieldsDb = $this->getDbFields();
        if (empty($fields) || empty($fields))
        {
            return array();
        }
        foreach ($fieldsDb as $key => $name)
        {
            if (in_array($name, $fields))
            {
                unset($fieldsDb[$key]);
            }
        }
        return $fieldsDb;
    }
    
    
    /**
     * @desc 根据其他模型数据 获取相应的数据
     * @param array $data  其他模型数据
     * @param string $id   以那个字段拼接数据
     * @param array  $field 字段
     * @param mixed  $where 筛选条件
     * @return array
     */

    public function getDataByOtherModel(array $data, $id, array $field, $where, $group = null )
    {
        if (!$this->isEmpty($data)|| !$this->isEmpty($field)|| empty($id) || empty($where)) {
            return $data;
        }
        
        $dbFields = $this->getDbFields();
       
        if (!in_array($where, $dbFields)) {
            return $data;
        }
       
        $flag = null;
        $combione = array();
        
        foreach ($field as $key => & $value) {
            if (false !== strpos($value, self::DBAS)) {
                
                $masterId = strstr( $value, self::DBAS, true);
               
                $flag  = str_replace($masterId.self::DBAS, null, $value);
            }
          
            strpos($value, 'as') !== false ? :$combione[] = $value;
            
            !$flag || in_array($flag, $combione) ? : array_push($combione, $flag);
           
        }
      
        $idString = Tool::characterJoin($data, $id);
      
        if (empty($idString)) {
            return $data;
        }
        
        if (empty($group)) {
            $user = $this->field($field)->where($where.' in ('.$idString.')')->select();
        } else {
            $user = $this->field($field)->where($where.' in ('.$idString.')')->group($group)->select();
        }
       
        if (empty($user)) {
            return $data;
        }
        
        foreach ($user as $key => &$value) {
            
            if(!array_key_exists($where, $value)) {
               continue;
            }
            
            $user[$key][$id] = $value[$where];
//             unset($user[$key][$where]);
        }
        $data = Tool::oneReflectManyArray($user, $data, $id, $combione);
        return $data;
        
    }
    
    
    
    /**
     * 获取商品属性数据
     */
    public function getAttribute($options, $isNoSelect = false, $default = 'select')
    {
        if (empty($options['field']))
        {
            return array();
        }
        if ($isNoSelect)
        {
            $options['field'] = $this->deleteFields($options['field']);
        }
        
        return $this->$default($options);
    }
    
    /**
     * 分页读取数据
     */
    public function getDataByPage( array $options, $pageNumer = 10, $isNoSelect = false, $pageObj = Page::class)
    {
        if (empty($options) || !is_int($pageNumer))
        {
            return array();
        }
       
        if (!empty($_SESSION['where']) && is_array($_SESSION['where'])) {
            $count = $this->where($_SESSION['where'])->count();
           
            $_SESSION['where'] = null;
        } else {
            $count = !empty($options['where']) ? $this->where($options['where'])->count() : $this->count();
        }
        
        $page = new $pageObj($count, $pageNumer);
        $param = empty($_POST) ?  $_GET : $_POST;
        Hook::listen('Search', $param);
       
        $page->parameter = $param;
        
        $options['limit'] = $page->firstRow.','.$page->listRows;
         
        $data = $this->getAttribute($options, $isNoSelect);
         
        $array['data'] = $data;
        $array['page'] = $page->show();
        
        return $array;
    }
    
    /**
     * add
     */
    public function add($data='',$options=array(),$replace=false)
    {
        if (empty($data))
        {
            return false;
        }
        $data = $this->create($data);
        return parent::add($data, $options, $replace);
    }
    
    /**
     * save
     */
    public function save($data='',$options=array())
    {
        if (empty($data))
        {
            return false;
        }
        $data = $this->create($data);
    
        return parent::save($data, $options);
    }
    
    
    /**
     * 抛出异常 
     * @param Model $model 基类模型
     * @return \Throwable
     */
    private function throwError(Model $model)
    {
        if (!($model instanceof Model))
        {
            throw new Exception('模型不匹配');
        }
    }
    
    /**
     * 重写 构造方法 
     */
    public function __construct($name = '', $tablePrefix = '', $connection ='')
    {
        parent::__construct($name, $tablePrefix, $connection);

       //实现自动添加代码[静态属性]
        $this->autoAddProp($this);
        //数据字段赋值 【用父类 实例化子类】$this 代指 子类的实例
        $this->setDbFileds($this);
    }
}