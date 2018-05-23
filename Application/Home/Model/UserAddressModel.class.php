<?php
namespace Home\Model;
use Think\Model;

/**
 * 用户地址模型 
 */
class UserAddressModel extends Model
{
    /**
     * 获取用户地址信息 
     */
    public function getUserAddressInfo(array $options)
    {
        if (!is_array($options) || empty($options) )
        {
            return array();
        }
        
        return $this->select($options);
    }
    
    /**
     * 获取默认地址 
     */
    public function getDefaultAddress($userId)
    {
        if ( empty($userId) || !is_numeric($userId))
        {
            return array();
        }
        
        $count = $this->field('id')->where('user_id = "'.$userId.'" and status = 1')->count();
        
        $res_addr_alone = $this->field('prov,city,dist,address,realname,mobile')->where('user_id = "'.$userId.'" and status = 1')->find();
        
        if(empty($res_addr_alone)){
            $res_addr_alone = $this->field('prov,city,dist,address,realname,mobile')->where('user_id = "'.$userId.'"')->order('create_time')->find();
        }
        if(!empty($res_addr_alone)){
        
            $addr_alone = $res_addr_alone['prov'].$res_addr_alone['city'].$res_addr_alone['dist'].$res_addr_alone['address'];
            $res_addr_alone['addr_alone'] = $addr_alone;
        }
        
        return array('count' => $count, 'res_ad' => $res_addr_alone);
    }
    /**
     * 根据商品信息【 查询地址】
     */
    public function goodsAdressByOrder(array $data)
    {
        if (empty($data) || !is_array($data))
        {
            return array();
        }
      
        $ids = Tool::characterJoin($data, 'address_id');
        
        if (empty($ids)) {
            return array();
        }
        $filed = array('prov','city,dist','address', 'realname', 'mobile');
        $address = $this->field('id as address_id,'.implode(',', $filed))->where('id in ('.$ids.')')->select();
        //此处 牵扯到 【一个收货地址 ，或多个】
        if (empty($address)) {
            return array();
        }
        $orderData = Tool::oneReflectManyArray($address, $data, 'address_id', $filed);
        return $orderData;
    }
    
    /**
     * 根据收货人 查询订单 
     */
    public function getOrderByRealName(array $post)
    {
        if (empty($post))
        {
            return array();
        }
        $where = $this->create($_POST);
        $userArray = array();
        if (!empty($where['realname'])) {
            $userArray = $this->field('id')->where('realname = "%s"', $where['realname'])->select();
        }
        return $userArray;
    }
}