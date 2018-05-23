<?php
namespace Home\Controller;

use Common\Model\RegionModel;
use Org\Net\IpLocation;
use Think\Controller;
use Core\Common\functions;

class RegionController extends CommonController{
    public function getlist(){
        $region=new RegionModel();
        $region_list=$region->getRegionList();
        $region_list['status'] = 1;
        echo json_encode($region_list);
    }

    /**
     *  查询配送地址，并执行回调函数
     */
    public function getregion()
    {
        $fid = I('fid/d');
        $callback = I('callback');
        $parent_region = M('region')->field('id,name')->where(array('parentid'=>$fid))->cache(true)->select();
        echo $callback.'('.json_encode($parent_region).')';
        exit;
    }

    /**
     * 商品物流配送和运费
     */
    public function dispatching()
    {
        $goods_id = I('goods_id/d');//143
        $region_id = I('region_id/d');//28242
        $goods_logic = new SiteLogic();
        $dispatching_data = $goods_logic->getGoodsDispatching($goods_id,$region_id);
        $this->ajaxReturn($dispatching_data);
    }

    /**
     * 根据当前ip地址 获取所在区域
     */
    public function getLocationArea($name='country')
    {
        $ipLocationObj = new IpLocation();
        $area = $ipLocationObj->getlocation();
        return empty($area[$name]) ? $area : $area[$name];
    }

    /**
     * 根据客户端ip地址
     */

    public function getClientIp()
    {
        $ip = get_client_ip();
//        $ip='222.75.147.52';
        echo json_encode($ip);die;
    }

    /**
     * 根据高德返回值，确定地址在数据库中的id
     */
    public function getNameId(){
        $areaName=$_GET['gaodeIp'];
        $region=new SiteLogic();
        $areaId=$region->getNameId($areaName);
        $_SESSION['indexAreaId'] = $areaId['province'];
        $_SESSION['indexAreaName'] = $areaName['province'];
        echo json_encode($areaId);die;

    }
    /**
     * 根据a标签名称返回值，存session
     */
    function setSession(){
        $id = $_GET['indexAreaId'];
        $name = $_GET['indexAreaName'];
        $_SESSION['indexAreaId'] = $id;
        $_SESSION['indexAreaName'] = $name;

//        $iid=session('indexAreaId');
//                showData($iid);
        echo $id;die;
    }
    /**
     * 获取session
     */
    function getSession(){
        $indexArea['name'] = $_SESSION['indexAreaName'];
        echo json_encode($indexArea);

    }
    /**
     * 获取省份
     */
    public function getProv(){
        $region=new RegionModel();
        $areaId=$region->getprovince();
        echo json_encode($areaId);

    }

}