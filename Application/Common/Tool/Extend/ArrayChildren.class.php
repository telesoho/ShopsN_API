<?php
namespace Common\Tool\Extend;

/**
 * 数组操作子类 
 */
class ArrayChildren extends ArrayParse
{
    /**
     * 计算和
     */
    public  function sumByArray(array $array, $sumKey = 'goods_price', $sumOne = 'goods_num', $sumSecond = 'fanli_jifen')
    {
        if (empty($array))
        {
            return $array;
        }
        $sum = 0;
        foreach ($array as $key => $value)
        {
            $sum += $value[$sumOne] * $value[$sumKey] - $value[$sumSecond];
        }
        return $sum;
    }
    /**
     * 立即购买 
     */
    public  function buyNowMonery(array $array, $goods_num, $sumKey = 'goods_price', $sumSecond = 'fanli_jifen')
    {
        if (empty($array) || !is_numeric($goods_num))
        {
            return $array;
        }
        $sum = 0;
        foreach ($array as $key => $value)
        {
            $sum += $goods_num * $value[$sumKey] - $value[$sumSecond];
        }
        return $sum;
    }
    /**
     * 添加数据
     */
    public function addValueAndKey(array $data, $setValue, $setDefault, $setKey = 'content')
    {
        if (empty($data))
        {
            return $data;
        }
        
        foreach ($data as $key => &$value)
        {
            $value[$setKey]       = $_POST[$setKey];
            $value['create_time']  = time();
            $value['user_id']     = $_SESSION['userId'];
            $value['status']      = $_POST['status'];
            $value['goods_orders_id'] = $_POST['goods_orders_id'];
        }
        
        return $data;
    }
    /**
     * 计算积分 
     */
    public function computationalIntegral(array $goods, $sumKey = 'fanli_jifen')
    {
        if (empty($goods))
        {
            return 0;
        }
        $sum = 0;
        
        foreach ($goods as $key => $value)
        {
            if (isset($value[$sumKey]))
            {
                $sum += $value[$sumKey];
            }
        }
        
        return $sum;
    }
    /**
     * 回归库存 
     */
    public function returnToStock(array $order, array $goods, $setKey = 'goods_num', $goodsKey = 'kucun')
    {
        if (empty($order) || empty($goods))
        {
            return false;
        }
        
        foreach ($goods as $key => &$value)
        {
            if ( $value['goods_id'] === $order[$key]['goods_id'])
            {
                $value[$goodsKey] += $order[$key][$setKey];
            }
        }
        return $goods;
    }
    
    /**
     * 去除空字段 
     * @param array $array 要处理的数组
     * @return array
     */
    public function deleteEmptyByArray(array $array)
    {
        if (empty($array)) {
            return array();
        }
        
        foreach ($array as $key => $value) {
            if (empty($value)) {
                unset($array[$key]);
            }
        }
        return $array;
    }
    
    /**
     * @param array $array
     * @param string $split
     * @return array
     */
    
    public function getSplitSuffix(array $array, $split='_d')
    {
        if (empty($array))
        {
            return array();
        }
        
        foreach ($array as $key => & $value)
        {
            if (false === strpos($key, $split))
            {
               continue;
            }
            $editKey = substr($key, 0, strrpos($key, $split));
            $array[$editKey]  = $editKey;
            unset($array[$key]);
        }
        
        return $array;
    }
    
    /**
     * 处理属性数组【组成 规格 属性】【根据post传值】
     */
    public function parseSpecific(array $data)
    {
         if (empty($data)) {
             return array();
         }
         $specArrSort =$parseData = array();
       
         //排序
         foreach ($data as $k => $v) {
             $specArrSort[$k] = count($v);
         }
        
         asort($specArrSort);
         
         foreach ($specArrSort as $key =>$val) {
             $parseData[$key] = $data[$key];
         }
         unset($data);
         $array = array();
         //笛卡尔积
         $array['cartesianProduct'] = $this->combineDika($parseData);
         $array['arrayKeys']        = array_keys($specArrSort);
         
         return $array;
    }
    
    
    /**
     * 多个数组的笛卡尔积
     * @param unknown_type $data
     * @return array
     */
    public function combineDika() 
    {
        $data = func_get_args();
      
        $data = current($data);
        $cnt = count($data);
        $result = array();
        $arr1 = array_shift($data);
       
        foreach($arr1 as $key=>$item)
        {
            $result[] = array($item);
        }
        
        foreach($data as $key=>$item)
        {
            $result = $this->combineArray($result,$item);
        }
        return $result;
    }

    /**
     * 两个数组的笛卡尔积
     * @param array $arr1
     * @param array $arr2
     * @return array;
     */
    public function combineArray(array $arr1, array $arr2) 
    {
        if (empty($arr1) || empty($arr2)) {
            return array();
        }
        $result = array();
        foreach ($arr1 as $item1)
        {
            foreach ($arr2 as $item2)
            {
                $temp = $item1;
                $temp[] = $item2;
                $result[] = $temp;
            }
        }
        return $result;
    }
    
    /**
     * 状态 改为键  value改为汉字提示；
     * @param array $array 要处理的数组
     * @param array $prompt 提示的数组
     * @return array
     */
    public function changeKeyValueToPrompt(array $array , array $prompt)
    {
        $param = func_get_args();
        //检测类型
        foreach ($param as $value) {
            if (empty($value) || !is_array($value)) {
                return array();
            }
        }
        $flag = array();
        foreach ($array as $key => $value)
        {
            if (!array_key_exists($key, $prompt))
            {
                continue;
            }
            $flag[$value] = $prompt[$key];
        }
        unset($array, $prompt);
        return $flag;
    }
    
    
    /**
     * 根据 标识 删除数组数据
     */
    public function deleteByCondition(array $array, $condition = '_')
    {
        if (empty($array) || !is_array($array)) {
            return array();
        }
    
        foreach ($array as $key => $value)
        {
            if (false === strpos($key, $condition)) {
                continue;
            }
    
            unset($array[$key]);
        }
        return $array;
    }
    
    /**
     * 组装 筛选控件
     * @param array $data post数据
     * @return array
     */
    public function buildActive(array  $data)
    {
        if (empty($data) || !is_array($data))
        {
            return array();
        }
        foreach ($data as $key => $value)
        {
            if (empty($value)) {
                unset($data[$key]);
            }
        }
        return $data;
    }
}