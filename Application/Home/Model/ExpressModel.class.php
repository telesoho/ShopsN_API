<?php
namespace Home\Model;
use Think\Model;
/**
 * 物流模型 
 */
class ExpressModel extends Model{
    /**
     * 获取物流信息
     * @author jim
     * @param String $com 物流公司编号
     * @param String $nu  物流单号，快递单号
     * @param String $show 0表示返回json,1表示返回xml
     * @param String $muti 0表示多行完整信息,1表示一行信息
     * @param String $order desc asc 按时间降序，升序
     * @return Array 
     * status
     * 0:物流单号暂无结果；
     * 3:在途，快递处于运输过程中；
     * 4:揽件，快递已被快递公司揽收并产生了第一条信息；
     * 5:疑难，快递邮寄过程中出现问题；
     * 6:签收，收件人已签收；
     * 7:退签，快递因用户拒签、超区等原因退回，而且发件人已经签收；
     * 8:派件，快递员正在同城派件；
     * 9:退回，货物处于退回发件人途中；
     *
     */
    public function getExpress($com,$nu) { //子类以及子类的子类可以访问
        // $id         = C('kuaidi_key');
        // $kuaidi_api = C('kuaidi_api');
        $url = 'http://www.kuaidi100.com/query'.'?type='.$com.'&postid='.$nu;
        // $url ='http://api.kuaidi100.com/api?id='.$id.'&com='.$com.'&nu='.$nu.'&show=2&muti=1&order=asc';
        $result = json_decode(file_get_contents($url),true);
        return $result;
    }

    
}