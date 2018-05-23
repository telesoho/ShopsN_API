<?php
namespace Home\Controller;
use Common\Tool\QRcode;
use Home\Model\IntegralUseModel;
use Think\Controller;

class PcenterController extends CommonController {
    //我的钱包
    public function my_wallet(){

    $id=zhong_decrypt(I('post.app_user_id'));
        $userModel=M('user');
    //头像和用户名
    $nick_name=$userModel->where(array('id'=>$id))->getField('nick_name');//昵称
    $mobile   =$userModel->where(array('id'=>$id))->getField('mobile');//电话
    $email   =$userModel->where(array('id'=>$id))->getField('email');//邮箱
    $header_img=M('user_header')
        ->where(
            array('user_id'=>$id)
        )
        ->getField('user_header');
    //我的积分
        $integral=(new IntegralUseModel())->integral($id)['sum'];
        $i['integral']=$integral;
        M('user')->where(['id'=>$id])->save($i);
    //账号余额
    $balance=M('balance')
        ->where(
            array('user_id'=>$id)
        )
        ->getField('account_balance');

    if($balance==null)
    {
        $balance='0.00';
    }
    //优惠劵张数
    $join='db_coupon ON db_coupon.id=db_coupon_list.c_id';
    if(!empty($id))
    $my_coupon=M('coupon_list')
        ->join($join)
        ->where(
            "`user_id`=$id AND `use_end_time`>".time()
        )
        ->count();
    //发票数

    //余单.
    $data=array(
        'integral'=>$integral,
        'balance' =>$balance,
        'my_coupon'=>$my_coupon,
        'nick_name'=>$nick_name,
        'email'=>$email,
        'mobile'=>$mobile,
        'header_img'=>$header_img,
        'fapiao'=>0,
        'yudan'=>0,
    );
   $this->returnMessage(1,'获取成功',$data);
}
    //我的优惠劵-未使用-未过期
    public function myCoupon(){
        if(IS_POST){
          $coupon_listModel=M('coupon_list');
            $user_id=zhong_decrypt(I('post.app_user_id'));
            $condition['user_id']=$user_id;
            $time=time();
            $couponList=$coupon_listModel->query("SELECT __PREFIX__coupon.id,`name`,`money`,`condition`,`use_start_time`,`use_end_time` FROM `__PREFIX__coupon_list` INNER JOIN __PREFIX__coupon ON __PREFIX__coupon.id=__PREFIX__coupon_list.c_id WHERE `user_id` = $user_id  AND order_id=0 ");
            if(!empty(I('post.status'))&&I('post.status')==1)
            {//status=1为已使用
                $couponList=$coupon_listModel->query("SELECT __PREFIX__coupon.id,`name`,`money`,`condition`,`use_start_time`,`use_end_time` FROM `__PREFIX__coupon_list` INNER JOIN __PREFIX__coupon ON __PREFIX__coupon.id=__PREFIX__coupon_list.c_id WHERE ( user_id=$user_id AND order_id!=0 ) ");
            }
           if(!empty(I('post.status'))&&I('post.status')==2)
           {//status=2已过期
                $couponList=$coupon_listModel->query("SELECT __PREFIX__coupon.id,`name`,`money`,`condition`,`use_start_time`,`use_end_time` FROM `__PREFIX__coupon_list` INNER JOIN __PREFIX__coupon ON __PREFIX__coupon.id=__PREFIX__coupon_list.c_id WHERE ( user_id=$user_id AND order_id=0 AND use_end_time<$time AND order_id=0 ) ");
            }
                if(!empty(I('post.status'))&&I('post.status')==3)
                {//status=3为未使用
                    $couponList=$coupon_listModel->query("SELECT __PREFIX__coupon.id,`name`,`money`,`condition`,`use_start_time`,`use_end_time` FROM `__PREFIX__coupon_list` INNER JOIN __PREFIX__coupon ON __PREFIX__coupon.id=__PREFIX__coupon_list.c_id WHERE ( user_id=$user_id AND order_id=0 AND use_end_time>$time) ");

            }
            $this->isEmpty($couponList);
            $this->returnMessage(1,'获取成功',$couponList);
        }
    }
    //获取个人信息
    public function userinfo(){
      if(IS_POST){
          $user_id=zhong_decrypt(I('post.app_user_id'));
          $result=M('user')
              ->field(
                  'nick_name,email,integral,mobile,user_name,sex,birthday'
              )
              ->where(
                  array('id'=>$user_id)
              )
              ->find();
          //账号余额
          $balance=M('balance')
              ->where(
                  array('user_id'=>$user_id)
              )
              ->getField('account_balance');
          $result['balance']=$balance;

          $header_img=M('user_header')
              ->where(
                  array('user_id'=>$user_id)
              )
              ->getField('user_header');//头像
          $weixheader=M('wx_user')->where(['uid'=>$user_id])->getField('headerpic');
          $result['user_header']=$header_img;
          $result['weixheader']=$weixheader;
          if(!empty($result))
          {
              $this->returnMessage(1,'获取成功',$result);
          }else
          {
              $this->returnMessage(0,'暂未找到相关信息','');
          }
        }
    }
    public function mobilePersoninfo(){//手机移动网站 ajax上传个人头像资料
        $user_id=zhong_decrypt(I('post.app_user_id'));
        if(!empty($user_id))
        {
            $data['nick_name'] = I('post.nick_name') ? I('post.nick_name') : NULL;//昵称
            $data['email'] = I('post.email') ? I('post.email') : "";//邮箱
            $data['update_time'] = time();
            $data['sex'] = I('post.sex') ? I('post.sex') : NULL;//性别
            $data['birthday'] = I('post.birthday') ? I('post.birthday') : NULL;//生日
            M('user')
                ->where(
                    array('id' => $user_id)
                )
                ->save($data);//修改其他基本信息
        }
        //头像上传
        $picture=I('post.user_header');
        $mgtype=I('post.type');//图片类型
        if(!empty($picture))
        {
            $base_img = str_replace($mgtype,'',$picture);
            $pcUrl = M('system_config')->where(['parent_key'=>'pc_url'])->getField('config_value');
            $pc_url = unserialize($pcUrl)['pcUrl'];
            $path = $pc_url."/Uploads/header/".date('Y-m');
            if(!file_exists($path)){
                mkdir($path);
            }
            $prefix = '/pic_';
            $output_file = $prefix . time() . rand(100, 999) . '.jpg';
            $path = $path . $output_file;
            file_put_contents($path, base64_decode($base_img),true);
            $path = str_replace($pc_url,'.',$path);
            $data['user_id'] = $user_id;
            $data['user_header'] =substr(trim($path),1);
            $user_header=M('user_header');
            $isheader=$user_header
                ->where(
                    array('user_id'=>$user_id)
                )
                ->find();
            if(!empty($isheader))
            {
                $user_header->where(
                    array('user_id' => $user_id)
                )
                    ->save($data);
            }else
            {
                $user_header->add($data);
            }
            $this->returnMessage(1,'成功','');
        }
    }

    //文件上传
    public function personinfo(){
        if(IS_POST){//如果有文件上传
          
            $user_id=zhong_decrypt(I('post.app_user_id'));
            if(!empty($user_id))
            {
                $data['nick_name'] = I('post.nick_name') ? I('post.nick_name') : NULL;//昵称
                $data['email'] = I('post.email') ? I('post.email') : "";//邮箱
                $data['update_time'] = time();
                $data['sex'] = I('post.sex') ? I('post.sex') : NULL;//性别
                $data['birthday'] = I('post.birthday') ? I('post.birthday') : NULL;//生日
                M('user')->where(
                    array('id' => $user_id)
                )
                    ->save($data);//修改其他基本信息
                if (!empty($_FILES))
                {//如果有文件上传
                    $img = $this->upload('header');
                    if ($img['status'] == 0)
                    {//图片上传失败
                        $this->returnMessage(0, '', $img['msg']);
                    } else {//图片上传成功
                        $info = $img['info'];
                        //$user_id=$this->app_user_id();
                        $find = M('user_header')
                            ->where(
                                array('user_id' => $user_id)
                            )
                            ->find();
                        if (!empty($find)) {//则数据库里已存在头像
                            $image = '/Uploads' . $info['user_header']['savepath'] . $info['user_header']['savename'];
                            $this->uploadsHead($image,$user_id);
                            $this->returnMessage(1, '操作成功', '');
                        } else {//数据库里不存在头像

                            $image = '/Uploads' . $info['user_header']['savepath'] . $info['user_header']['savename'];
                            $this->uploadsHead($image,$user_id);
                            $this->returnMessage(1, '操作成功', '');
                        }
                    }
                }
                $this->returnMessage(1, '操作成功', '');
            }
        }
    }

	//收货地址列表
    public function addresslist(){
        if(IS_POST){
          
            $id=zhong_decrypt(I('post.app_user_id'));
            $region_model=M('region');
            $list=M('user_address')
                ->field(
                    'id,realname,mobile,prov,city,dist,address'
                )
                ->where(
                    array('user_id'=>$id)
                )
                ->select();
            foreach($list as $k=>$vo)
            {
                $arr=array($vo['prov'],$vo['city'],$vo['dist']);
                $conditon['id']=array('in',$arr);
                $add=$region_model->where($conditon)->select();
                $newKey=array_column($add,'id');
                $a=array_search($vo['prov'],$newKey);
                $b=array_search($vo['city'],$newKey);
                $list[$k]['prov']=$add[$a]['name'];
                $list[$k]['city']=$add[$b]['name'];
                if($vo['dist']!=-1)
                {
                    $c=array_search($vo['dist'],$newKey);
                    $list[$k]['dist']=$add[$c]['name'];
                }
            }
            if(empty($list))
            {
                $this->returnMessage(0,'暂无数据','');
            }else
            {
                $this->returnMessage(1,'获取成功',$list);
            }
        }
    }
	 //收货地址地区获取
    public function addressPlace(){
      if(IS_POST){
          $region_model=M('region');
          $field='id,name';
          if(!S('app_receive_address')){
              $result=$region_model
                  ->field($field)
                  ->where(
                      array('parentid'=>0)
                  )
                  ->select();
              foreach($result as $k=>$vo)
              {
                  $son=$region_model
                      ->where(
                          array('parentid'=>$vo['id'])
                      )
                      ->field($field)
                      ->select();
                  foreach($son as $key=>$voo)
                  {
                      $son[$key]['grandson']=$region_model
                          ->where(
                              array('parentid'=>$voo['id'])
                          )
                          ->field($field)
                          ->select();
                  }
                  $result[$k]['son']=$son;
              }
              S('app_receive_address',$result);
          }else{
              $result=S('app_receive_address');
          }
          $this->returnMessage(1,'获取成功',$result);
      }

    }
    //新建收货地址
    public function addressadd(){
        if(IS_POST){
            $model=M('user_address');
            $data['realname']=I('post.realname');
            $data['mobile']=I('post.mobile');
            $data['prov']=I('post.prov');
            $data['city']=I('post.city');
            $data['dist']=I('post.dist');
            $data['address']=I('post.address');
            $default=I('post.default');
            if(!empty($_POST['id'])){//如果是修改
                $id=$_POST['id'];
                $data['update_time']=time();
                $model->where(
                    array('id'=>$_POST['id'])
                )
                    ->save($data);
            }else{//是新增
			    $data['user_id']=zhong_decrypt(I('post.app_user_id'));
                $data['create_time']=time();
                $id=$model->add($data);
            }

            if($default==1){//设置为默认地址--将原默认地址设置为非默认地址
              $model->where(
                  array(
                      'user_id'=>zhong_decrypt(I('post.app_user_id')),
                      'status'=>1)
                  )
                  ->save(
                      array('status'=>0)
                  );
              $model->where(
                  array('id'=>$id)
              )
                  ->save(
                      array('status'=>1)
                  );
            }
            $this->returnMessage(1,'新增成功','');

        }
    }
     //修改收货地址--获取信息
    public function addinfo(){
        if(IS_POST){
            $id=I('post.id');
            $region_model=M('region');
            $find=M('user_address')->where(array('id'=>$id))->find();
            $arr=array($find['prov'],$find['city'],$find['dist']);
            $conditon['id']=array('in',$arr);
            $add=$region_model->where($conditon)->select();
            $newKey=array_column($add,'id');
            $a=array_search($find['prov'],$newKey);
            $b=array_search($find['city'],$newKey);
            $find['prov']=array();
            $find['city']=array();
            $find['prov']['id']=$add[$a]['id'];
            $find['city']['id']=$add[$b]['id'];
            $find['prov']['name']=$add[$a]['name'];
            $find['city']['name']=$add[$b]['name'];
            if($find['dist']!=-1){
                $c=array_search($find['dist'],$newKey);
                $find['dist']=array();
                $find['dist']['id']=$add[$c]['id'];
                $find['dist']['name']=$add[$c]['name'];
            }else{
                $find['dist']=array();
                $find['dist']['id']=-1;
                $find['dist']['name']='';
            }
            if(!empty($find))
            {
                $this->returnMessage(1,'获取成功',$find);
            }else
            {
                $this->returnMessage(0,'获取失败','');
            }
        }
    }
    /**
     * 收货地址修改
     */
    public function addressSave(){
        if(IS_POST){
            $address_id=I('post.address_id');
            $data['realname']=I('post.receiver');
            $data['mobile']=I('post.mobile');
            $data['prov']=I('post.prov');
            $data['city']=I('post.city');
            $data['dist']=I('post.dist');
            $data['address']=I('post.address');
            $r=M('user_address')->where(['id'=>$address_id])->save($data);
            if($r) $this->returnMessage('1','修改成功','');
        }
    }

    //收货地址删除
    public function addressde(){
       if(IS_POST){
           $id=I('post.id');
           $user_id=zhong_decrypt(I('post.app_user_id'));
           $res = M('user_address')->where(array('id'=>$id,'user_id'=>$user_id))->getField('id');
           if($res){
               $res2 = M( 'order' )->where( [ 'address_id' => $id ] )->getField( 'user_id' );
               if($res2){
                   $this->returnMessage(0,'已使用过该收获地址，请勿删除','');
               }else{
                   M('user_address')->where(array('id'=>$id))->delete();
                   $this->returnMessage(1,'删除成功','');
               }
           }
           $this->returnMessage(0,'删除失败','');
        }
    }
    //我的浏览记录足迹
    public function myFootprint()
    {
        if (IS_POST) {
            //取得COOKIE里面的值，并用逗号把它切割成一个数组
            $goods_id = explode(',', $_COOKIE['trace']);
             $goods_model=M('goods');
            $goods_images_model=M('goods_images');
            foreach($goods_id as $k=>$v)
            {
                $goods_g=$goods_model
                    ->where(
                        array('id'=>$v)
                    )
                    ->field(
                        'id,title,price_member,p_id'
                    )
                    ->find();
               $img=$goods_images_model
                   ->where(
                       array('goods_id'=>$goods_g['p_id'])
                   )
                   ->getField('pic_url');
                $goods[$k]['id']=$goods_g['id'];
                $goods[$k]['title']=$goods_g['title'];
                $goods[$k]['price_member']=$goods_g['price_member'];
                $goods[$k]['img']=$img;
            }
            if($_COOKIE['trace'])
                $this->returnMessage(1,'返回成功',$goods);
            else
                $this->returnMessage(0,'暂无数据','');
       }

    }
    /**
     * 清除我的足迹
     */
public function deleteFootprint(){
    if(IS_POST){
        setcookie("trace", "", time()-3600,'/');
        unset($_COOKIE['trace']);
        if(empty($_COOKIE['trace'])){
           $this->returnMessage(1,'删除成功','');
        }
    }
}
    //我的足迹找相似
    public function brother(){
        if(IS_GET){
            $flag=I('get.sort');
            $goods_id=I('get.goods_id');
            $goods_model=M('goods');
            $join='db_goods_images ON db_goods.p_id=db_goods_images.goods_id';
            $find=$goods_model
                ->where(
                    array('id'=>$goods_id)
                )
                ->find();
            if(!empty($flag)){
                switch($flag){
                    case 1:  //销量由高到低
                        $order='sales_sum DESC';
                        break;
                    case 2:  //销量由低到高
                        $order='sales_sum ASC';
                        break;
                    case 3:   //价格由高到低
                        $order='price_market DESC';
                        break;
                    case 4:  //价格由低到高
                        $order='price_market ASC';
                        break;
                    case 5:
                        $order='';
                        break;
                }
                $result=$goods_model
                    ->join($join)
                    ->order($order)
                    ->where(
                        array('p_id'=>$find['p_id'])
                    )
                    ->field(
                        'db_goods.id,title,price_member,pic_url'
                    )
                    ->group('id')
                    ->select();
            }else{
                $result=$goods_model
                    ->join($join)
                    ->where(
                        array('p_id'=>$find['p_id'])
                    )
                    ->field(
                        'db_goods.id,title,price_member,pic_url'
                    )
                    ->group('id')
                    ->select();
            }
            $aa=array_column($result,'id');
            $k=array_search($goods_id,$aa);//找到所在地
            array_splice($result,$k,1);
          if($result)
            $this->returnMessage(1,'获取成功',$result);
            else $this->returnMessage(0,'暂无相似产品','');
        }
    }
	
	 //个人中心---我的评价
    public function myComment(){
       if(IS_GET){
         
           $user_id=zhong_decrypt(I('get.app_user_id'));
           $User = M('order_comment'); // 实例化User对象
           $goods_images_model=M('goods_images');
           $img=$User
               ->where(
                   array('user_id'=>$user_id,
                       'show_pic'=>['NEQ',''])
               )
               ->count();//有图
           $count      = $User->where(array('user_id'=>$user_id))->count();// 查询满足要求的总记录数
           $Page       = new \Think\Page($count,15);// 实例化分页类 传入总记录数和每页显示的记录数(25)
           $show       = $Page->show();// 分页显示输出// 进行分页数据查询 注意limit方法的参数要使用Page类的属性
           $field='goods_id,show_pic,status,content,create_time,space_id';
           $list = $User
               ->field($field)
               ->where(
                   array('user_id'=>$user_id)
               )
               ->order('create_time DESC')
               ->limit($Page->firstRow.','.$Page->listRows)
               ->select();
           $goods_model=M('goods');
           $imagesModel=M('images');
           foreach($list as $k=>$vo){
               $goods=$goods_model
                   ->where(
                       array('id'=>$vo['goods_id'])
                   )
                   ->field('title,p_id')
                   ->find();
			   $list[$k]['title']=$goods['title'];

               $list[$k]['mainImg']=$goods_images_model
                   ->where(
                       array('goods_id'=>$goods['p_id'])
                   )
                   ->getField('pic_url');
                $show_pic=explode(',',$vo['show_pic']);
               if(!empty($vo['show_pic'])) {
                   foreach ($show_pic as $v)
                   {
                       $list[$k]['imgs'][] = $imagesModel
                           ->where(['id' => $v])
                           ->find()['path'];
                   }
               }else{
                   $list[$k]['imgs']='';
               }

               $list[$k]['attra']=$this->selfAttr($vo['goods_id']);

           }
           $this->isEmpty($list);
           $num=array('count'=>$count,'img'=>$img);
           $data=array('num'=>$num,'list'=>$list);
           $this->returnMessage(1,'获取成功',$data);
       }
    }
	//个人中心--我的评价--有图评价
    public function imgComment(){
        if(IS_GET){
            $user_id=zhong_decrypt(I('get.app_user_id'));
            $User = M('order_comment'); // 实例化User对象
            $goods_model=M('goods_images');
			$goods=M('goods');
            $field='goods_id,status,show_pic,content,create_time,space_id';
            $list = $User
                ->field($field)
                ->where([
                        'user_id'=>$user_id,
                        'show_pic'=>['NEQ','']
                    ])
                ->order('create_time DESC')
                ->select();
            $imagesModel=M('images');
            foreach($list as $k=>$vo)
            {
				$list[$k]['title']=$goods
                    ->where(
                        array('id'=>$vo['goods_id'])
                    )
                    ->getField('title');
                $p_id= $goods
                    ->where(
                        array('id'=>$vo['goods_id'])
                    )
                    ->getField('p_id');
                $list[$k]['mainImg']=$goods_model
                    ->where(
                        array('goods_id'=>$p_id)
                    )
                    ->getField('pic_url');
                $show_pic=explode(',',$vo['show_pic']);
                if(!empty($vo['show_pic']))
                {
                    foreach ($show_pic as $v)
                    {
                        $list[$k]['imgs'][] = $imagesModel->where(['id' => $v])->find()['path'];
                    }
                }else{
                    $list[$k]['imgs']='';
                }
                $list[$k]['attra']=$this->selfAttr($vo['goods_id']);
            }
            $this->isEmpty($list);
            $this->returnMessage(1,'获取成功',$list);
        }
    }
     //申请加盟
    public function applayJoin(){
        if(IS_POST){
          
            $user_id=zhong_decrypt(I('post.app_user_id'));
            $data['user_id']=$user_id;
            $find=M('apply')->where(array('user_id'=>$user_id))->find();
            if(!empty($find))
                $this->returnMessage(0,'请勿重复申请','');
            $data['applicant']=I('post.applicant');
            $data['tel']=trim(I('post.tel'));
            $data['email']=I('post.email');
            $data['province']=I('post.province');
            $data['city']=I('post.city');
            $data['county']=I('post.county');
            $data['address']=I('post.address');
            $data['age']=I('post.age');
            $data['qq']=I('post.qq');
            $data['fax']=I('post.fax');
            $data['remark']=I('post.remark');
            $data['application_time']=time();
            $id=M('apply')->add($data);
            if($id){
                $this->returnMessage(1,'申请成功,待审核','');
            }else{
                $this->returnMessage(0,'申请失败','');
                }
        }
    }

	//客户中心-举报中心
    public function reportingCenter(){
        if(IS_POST){
            $user_id=zhong_decrypt(I('post.app_user_id'));
            $result=M('app_reporting_center')
                ->where(
                    array('user_id'=>$user_id)
                )
                ->field(
                    'reporting_center_id,reason,create_time,content'
                )
                ->select();
            $this->isEmpty($result);
            $this->returnMessage(1,'获取成功',$result);
        }
    }
    //客户中心-举报中心-提交
    public function reportingCenterAdd(){
        if(IS_POST){
            $time=time();
            $user_id=zhong_decrypt(I('post.app_user_id'));
            $find=M('app_reporting_center')
                ->where(
                    array('user_id'=>$user_id)
                )
                ->order(
                    'create_time DESC'
                )
                ->getField(
                    'create_time'
                );
            if($find!=null){
                $date=date("Y-m-d",(int)$find);
                $aa=date("Y-m-d");
                if($date==$aa){
                    $this->returnMessage(0,'您今天已经提交过反馈了','');
                }
            }
            $data['reason']=I('post.reason');
            $data['user_id']=$user_id;
            $data['content']=I('post.content');
            $data['create_time']=$time;
            $id=M('app_reporting_center')->add($data);
            if($id)
                $this->returnMessage(1,'反馈成功','');
            else
                $this->returnMessage(0,'反馈失败','');
        }
    }
	//意见反馈
    public function feedback(){
     if(IS_POST){
         $app_feedback_model=M('app_feedback');
         $user_id=zhong_decrypt(I('post.app_user_id'));
         $find=$app_feedback_model
             ->where(
                 array('user_id'=>$user_id)
             )
             ->order('create_time DESC')
             ->getField('create_time');
         if($find!=null)
         {
             $date=date("Y-m-d",(int)$find);
             $aa=date("Y-m-d");
             if($date==$aa){
                 $this->returnMessage(0,'您今天已经提交过反馈了','');
             }
         }
         $data['type']=I('post.type');
         $data['tel']=I('post.tel');
         $data['content']=I('post.content');
         $data['user_id']=$user_id;
         $data['create_time']=time();
         $id=$app_feedback_model->add($data);
         if($id)
             $this->returnMessage(1,'反馈成功','');
         else
             $this->returnMessage(0,'反馈失败','');
          }
    }

    //修改密码
    public function modifyPassword()
    {
        if(IS_POST){
            $user_id=zhong_decrypt(I('post.app_user_id'));
            $password=md5(I('post.password'));
            $newPassword1=I('post.newPassword1');
            $newPassword2=I('post.newPassword2');
            if(!empty($password)&&!empty($newPassword1))
            {
                $data['password'] = md5($newPassword1);
                if ($newPassword1 != $newPassword2)
                {
                    $this->returnMessage(0, '两次设置密码不一致', '');
                }
               $password_model= M('user')
                    ->where([
                        'id' => $user_id,
                    ])
                    ->getField('password');
                if($password!=$password_model)
                    $this->returnMessage(0,'原密码错误','');
                $chang_password = M('user')
                    ->where([
                        'id' => $user_id,
                        'password' => $password
                    ])
                    ->save($data);

                if ($chang_password) {
                    $this->returnMessage(1, '密码修改成功', '');
                }
            }else{
                $this->returnMessage(0,'密码不能为空','');
            }
        }
    }
    //常购清单
    public function oftenBuy(){
        if(IS_POST){
          
            $user_id=zhong_decrypt(I('post.app_user_id'));
            $condition['user_id']=$user_id;
            $condition['status']='4';//4代表已收货，已完成订单
            $count=M('order_goods')->where($condition)->count();
          // dump( M()->_sql());
            $Page       = new \Think\Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数(25)
            $show       = $Page->show();// 分页显示输出// 进行分页数据查询 注意limit方法的参数要使用Page类的属性
            $oftenBuy=  M('order_goods')
                ->where($condition)
                ->field(
                    'goods_id,goods_num'
                )
                ->limit($Page->firstRow,$Page->listRows)
                ->select();
            $goods_model=M('goods');
            $images_model=M('goods_images');
          foreach($oftenBuy as $k=>$v)
          {
              if(!empty($oftenBuy)){
                  $goods=$goods_model
                      ->where(
                          ['id'=>$v['goods_id']]
                      )
                      ->field(
                          'title,price_market,p_id'
                      )
                      ->find();
              }else{
                  $goods="";
              }
              if(!empty($goods))
              {
                  $image=$images_model
                      ->where(
                      ['goods_id'=>$goods['p_id']]
                  )
                      ->field('pic_url')
                      ->find();
              }else
              {
                  $image="";
              }
              $oftenBuy[$k]['title']=$goods['title'];
              $oftenBuy[$k]['price_market']=$goods['price_market'];
              $oftenBuy[$k]['pic_url']=$image['pic_url'];
          }
            if(!empty($oftenBuy)){
                $this->returnMessage(1,'返回成功',$oftenBuy);
            }else{
                $this->returnMessage(0,'暂无数据',"");
            }
        }
    }
    /**
     *
     * 售后管理-列表展示
     */
    public function customerService(){
        if(IS_POST){
          
            $user_id=zhong_decrypt(I('post.app_user_id'));
            $count=M('order_return_goods')
                ->field('id')
                ->where(
                    array('user_id'=>$user_id,'status'=>0)
                )
                ->count();
            $page=new \Think\Page($count,20);
            $show=$page->show();
            $data=M('order_return_goods')
                ->field(
                    'id,order_id,create_time,status,tuihuo_case'
                )->where(
                    array('user_id'=>$user_id,'status'=>0)
                )
                ->limit($page->firstRow,$page->listRows)
                ->select();
            if($data){
                $this->returnMessage(1,'返回成功',$data);
            }else{
                $this->returnMessage(0,'暂无数据','');
            }
        }
    }
    /**
     * 售后管理搜索
     */
    public function customerServiceSearch(){
        $user_id=zhong_decrypt(I('post.app_user_id'));
        $keyword=I('post.keyword');
        $where['r.user_id']=$user_id;
        $where['g.title|a.order_sn_id']=array('like','%'.$keyword.'%');
        $join='join db_order as a on r.order_id=a.id join db_order_goods as b on a.id=b.order_id join db_goods as g on b.goods_id=g.id';
        $list=M('order_return_goods as r')
            ->join($join)
            ->where($where)
            ->field(
            'r.id,r.order_id,r.create_time,r.status,r.tuihuo_case'
             )
            ->select();
        if($list){
            $this->returnMessage(1,'返回成功',$list);
        }else{
            $this->returnMessage(0,'暂无数据','');
        }
    }


    /**
     * 售后管理详情
     */
    public function customerServiceList()
    {
        if(IS_POST)
        {
            $id=I('post.id');//售后编号id
            $data= M('order_return_goods')
                ->where(
                    ['id'=>$id]
                )
                ->field(
                    'id,goods_id,order_id,is_receive,type,explain,create_time,revocation_time,price'
                )
                ->find();
           $order_goods=M('order_goods')
               ->where(
                   ['goods_id'=>$data['goods_id']]
               )
               ->field('status')
               ->find();
            $data['status']=$order_goods['status'];
            if ($data)
            {
                $this->returnMessage(1,'返回成功',$data);
            } else {
                $this->returnMessage(0, '返回失败', '');
            }
        }
    }
    /**
     *
     * 上门服务-商品维修申请
     */
    public function door_repair(){
        if(IS_POST) {
            $data['user_id'] = zhong_decrypt(I('post.app_user_id'));
            $data['repair_project'] = I('post.repair_project');
            $data['is_ys'] = I('post.type');
            $data['tel'] = I('post.tel');
            $data['repair_address'] = I('post.repair_address');
            $data['describe'] = I('describe');
            $data['add_time'] = time();
            $data['status'] = 1;//预约中
            $re = M('door_repair')->add($data);
            if ($re) {
                $this->returnMessage(1, '提交成功', '');
            } else {
                $this->returnMessage(0, '提交失败', '');
            }
        }
    }
    /**
     * 申请售后
     */
    public function afterSaleApply(){
        if(IS_POST){
            $data['order_id']=I('post.order_id');
            $data['goods_id']=I('post.goods_id');
            $data['type']=I('post.type');//服务类型
            $data['price']=I('post.price');
            $data['number']=I('post.number');
            $data['tuihuo_case']=I('post.explain');
            $data['user_id']=zhong_decrypt(I('app_user_id'));
            $data['create_time']=time();
            if(!empty($_FILES))
            {//有图片上传
                $info=$this->upload('apply');
                if($info['status']==0)
                    $this->returnMessage(0,'新增错误',$info['msg']);
                if($info['status']==1)
                {//图片上传成功
                    $info=$info['info'];
                    $address=array();
                    foreach($info as $vo)
                    {
                        $address[]='/Uploads'.$vo['savepath'].$vo['savename'];
                    }
                    $data['apply_img']=implode("$",$address);
                }
            }
            // /Uploads/apply/2017-06-16/594351e45507d.jpeg$/Uploads/apply/2017-06-16/594351e4567ed.png$/Uploads/apply/2017-06-16/594351e457b75.png
            $re=M('order_return_goods')->add($data);
            if($re)
            {
                $this->returnMessage(1,'申请成功','');
                if($data['goods_id'])
                {
                    $return_goods['status']='5';
                    M('order_goods')->where([
                        'goods_id'=>$data['goods_id'],
                        'user_id'=>$data['user_id']
                    ])
                        ->save($return_goods);
                }
                //如果订单中的商品都已申请则改变订单状态
                if($data['order_id'])
                {
                    $condition['order_id']=$data['order_id'];
                    $condition['status']=array('neq','5');
                    $count= M('order_goods')->where($condition)->count();
                    if($count==0){
                        $order_status['order_status']='5';
                        M('order')->where(['id'=>$data['order_id'], 'user_id'=>$data['user_id']])->save($order_status);
                }
                }
            }
        }
    }


    /**
     * 售后申请列表
     */
    public function afetrsale_list(){
         $user_id=zhong_decrypt(I('post.app_user_id'));
        //4代表已收货订单已完成
        $User = M('order'); // 实例化User对象
        $where['user_id']=$user_id;
        $where['order_status']=array('between','1,4');
        $count      = $User->where($where)->count();// 查询满足要求的总记录数
        if($count==0)
            $this->returnMessage(0,'暂无数据','');
        $list = $User
            ->where($where)
            ->field(
                'id,create_time,order_sn_id,order_status,comment_status'
            )
            ->order('create_time DESC')
            ->select();
        $goods_model=M('goods');
        $goods_images= M('goods_images');
        $order_goods_model=M('order_goods');
        foreach($list as $k=>$v)
        {
         $order_goods=$order_goods_model
             ->where(
                 ['order_id'=>$v['id'],'status'=>['between','0,4']]
             )
             ->field(
                 'goods_id,goods_price,status,goods_num'
             )
             ->select();
               if(!empty($order_goods)) {
                   foreach ($order_goods as $key => $value)
                   {
                       $goods = $goods_model
                           ->field(
                               'title,p_id'
                           )
                           ->where(
                               ['id' => $value['goods_id']]
                           )
                           ->find();
                       if (!empty($goods['p_id']))
                           $image = $goods_images
                               ->where(
                                   ['goods_id'=>$goods['p_id']]
                               )
                               ->getField('pic_url');
                       $order_goods[$key]['title'] = $goods['title'];
                       $order_goods[$key]['pic_url'] = $image;
                       $list[$k]['order_goods']= array_values($order_goods);
                   }
               }
        }
        if($list){
            $this->returnMessage(1,'获取成功',$list);
        }else{
            $this->returnMessage(0,'暂无数据',"");
        }
    }

    /**
     * 售后申请搜索
     */
    public function afterSaleSearch(){
        $user_id=zhong_decrypt(I('post.app_user_id'));
        $keyword=I('post.keyword');
        //$user_id=112;
        //4代表已收货订单已完成
        // $where['a.user_id']=$user_id;
        //$where['a.order_status']='4';
        //$where['g.title|a.order_sn_id']=array('like','%'.$keyword.'%');
        $condition=[
            'a.user_id'=>$user_id,
            'a.order_status'=>['between','0,4'],
            'g.title|a.order_sn_id'=>['like','%'.$keyword.'%']
        ];
        $join='join db_order_goods as b on a.id=b.order_id join db_goods as g on b.goods_id=g.id';
          $list=M('order as a')
              ->join($join)
              ->where($condition)
              ->field(
              'a.id,a.create_time,a.order_sn_id,a.order_status,a.comment_status'
               )
              ->select();
//        //数组去重
//        foreach ($list as $k=>$v){
//            $v=join(',',$v); //降维,也可以用implode,将一维数组转换为用逗号连接的字符串
//            $temp[$k]=$v;
//        }
//        $temp=array_unique($temp); //去掉重复的字符串,也就是重复的一维数组
//        foreach ($temp as $k => $v){
//            $array=explode(',',$v); //再将拆开的数组重新组装
//            //下面的索引根据自己的情况进行修改即可
//            $list[$k]['id'] =$array[0];
//            $list[$k]['create_time'] =$array[1];
//            $list[$k]['order_sn_id'] =$array[2];
//            $list[$k]['order_status'] =$array[3];
//            $list[$k]['comment_status'] =$array[4];
//        }
        $goods_model=M('goods');
        $goods_images= M('goods_images');
        $order_return_goods=M('order_return_goods');
        $order_goods_model=M('order_goods');
        foreach($list as $k=>$v){
            $order_goods=$order_goods_model
                ->where(
                    ['order_id'=>$v['id'],'status'=>['between','0,4']]
                )
                ->field(
                    'goods_id,goods_price,goods_num'
                )
                ->select();
            if(!empty($order_goods)) {
                foreach ($order_goods as $key => $value)
                {
                    $goods = $goods_model
                        ->field(
                            'title,p_id'
                        )
                        ->where(
                            ['id'=> $value['goods_id']]
                        )
                        ->find();

                    if (!empty($goods['p_id']))
                        $image =$goods_images
                            ->where(
                                ['goods_id'=>$goods['p_id']]
                            )
                            ->getField('pic_url');
                    $order_goods[$key]['title'] = $goods['title'];
                    $order_goods[$key]['pic_url'] = $image;
                    $list[$k]['order_goods'] = array_values($order_goods);
                }
            }//$list=array_values($list);
        }
        if($list){
            $this->returnMessage(1,'获取成功',$list);
        }else{
            $this->returnMessage(0,'暂无数据',"");
        }
    }



    /**
     * 售后进度查询
     */
    public function speed_check(){
        $user_id=zhong_decrypt(I('post.app_user_id'));
        $return_goods= M('order_return_goods')
            ->where(
                ['user_id'=>$user_id]
            )
            ->field(
                'id,goods_id,number,status,create_time,price'
            )
            ->select();
        $goods_model=M('goods');
        $image_model=M('goods_images');
        foreach($return_goods as $k=>$v)
        {
            $goods= $goods_model
                ->where([
                    'id'=>$v['goods_id']
                ])
                ->field(
                    'id,title,p_id'
                )
                ->find();
            if($goods['p_id']) {
                $image =$image_model
                    ->where([
                        'goods_id'=>$goods['p_id']
                    ])
                    ->field('pic_url')
                    ->find();
            }else{
                $image="";
            }
            $return_goods[$k]['title']=$goods['title'];
            $return_goods[$k]['pic_url']=$image['pic_url'];
        }
        if(!empty($return_goods))
            $this->returnMessage(1,'返回成功',$return_goods);
        else
            $this->returnMessage(0,'暂无数据',"");
    }
    /**
     * 退货返修--售后申请进度搜索
     */
    public function speed_checkSearch(){
        $keyword=I('post.keyword');
        $user_id=zhong_decrypt(I('post.app_user_id'));
        $where['a.user_id']=$user_id;
        if(is_numeric($keyword))
        {
            $where['b.order_sn_id']=array('like','%'.$keyword.'%');
            $r=M('order_return_goods as a')
                ->join(
                    'db_order as b on a.order_id=b.id'
                )
            ->where($where)
            ->field(
                'a.id,a.goods_id,a.number,a.status,a.create_time,a.price'
            )
            ->select();
        }else {
            $where['b.title'] = array('like', '%' . $keyword . '%');
            $r = M('order_return_goods as a')
                ->join(
                    'join db_goods as b on a.goods_id=b.id '
                )
                ->where($where)
                ->field(
                    'a.id,a.goods_id,a.number,a.status,a.create_time,a.price'
                )
                ->select();
        }
        $goods_model= M('goods');
        $goods_images_model=M('goods_images');
        foreach($r as $k=>$v){
            $goods=$goods_model
                ->where([
                    'id'=>$v['goods_id']
                ])
                ->field('id,title,p_id')
                ->find();
            if($goods['p_id'])
            {
                $image = $goods_images_model
                    ->where([
                        'goods_id'=>$goods['p_id']
                    ])
                    ->field('pic_url')
                    ->find();
            }else{
                $image="";
            }
            $r[$k]['title']=$goods['title'];
            $r[$k]['pic_url']=$image['pic_url'];
        }
        if($r)
            $this->returnMessage(1,'返回成功',$r);
        else
            $this->returnMessage(0,'暂没搜索到结果','');
    }


    /**
     * 售后进度查询详情
     *
     */
    public function speed_check_list(){
        $return_id=I('post.id');
        $list= M('order_return_goods')
            ->where(['id'=>$return_id])
            ->field(
                'order_id,price,goods_id,number,tuihuo_case,status,message,update_time,auditor,content'
            )
            ->select();
        $goods_model=M('goods');
        $image_model=M('goods_images');
        foreach($list as $k=>$v)
        {
             $goods= $goods_model
                 ->where([
                     'id'=>$v['goods_id']
                 ])
                 ->field('id,title,p_id')
                 ->select();
             if($goods)
             {
                 foreach($goods as $key=>$value)
                 {
                     $image =$image_model
                         ->where([
                         'goods_id'=>$value['p_id']
                     ])
                         ->field('pic_url')
                         ->find();
                     $list[$key]['pic_url']=$image['pic_url'];
                     $goods[$key]['update_time']=$v['update_time'];
                     $goods[$key]['content']=$v['content'];
                     $goods[$key]['auditor']=$v['auditor'];
                     $list[$key]['title']=$value['title'];
                     unset($goods[$key]['id']);
                     unset($goods[$key]['title']);
                     unset($goods[$key]['p_id']);
                 }
             }
            $list[$k]['examine']=$goods;
            unset($list[$k]['auditor']);
            unset($list[$k]['content']);
            unset($list[$k]['update_time']);
        }
        if(!empty($list))
            $this->returnMessage(1,'返回成功',$list);
        else
            $this->returnMessage(0,'暂无数据',"");
    }

    /**
     * 个人中心文章分类
     */
 public function article()
 {
     $article_category=M('article_category')
                        ->field('name,id')
                        ->select();
     $article=M('article');
     foreach($article_category as $k=>$v)
     {
         $r=$article->where([
             'article_category_id'=>$v['id']
         ])
             ->field('name,id')
             ->select();
         $category[$k]['name']=trim($v['name']);
         $category[$k]['value']=$r;
     }
$this->returnMessage(1,'返回成功',$category);
}
    /**
     * 个人中心文章详情
     */

    public function articleDetail(){
        $article_id=I('post.id');
       $r= M('article_content')
           ->where([
            'article_id'=>$article_id
        ])
            ->getField('content');
        $this->returnMessage(1,'返回成功',$r);
    }
    /**
     *
     * 文章分类搜索
     */
    public function articleSearch(){
        $keyword=I('post.keyword');
        $where['name']=array('like','%'.$keyword.'%');
        $r=M('article')
            ->where($where)
            ->field('id,name')
            -> select();
        $this->returnMessage(1,'返回成功',$r);
    }
    //页面防止重复提交，获取随机数
    public function getCheck(){
        $user_id=zhong_decrypt(I('post.app_user_id'));
        $check = mt_rand(0,1000000);
        S('check'.$user_id,$check);
        $this->returnMessage(1,'获取成功',$check);
    }

    //页面防止重复提交，检测随机数
    public function check(){
        $user_id=zhong_decrypt(I('post.app_user_id'));
        $check  = I('post.gcheck');
        $scheck = S('check'.$user_id);
        if($check == $scheck){
            S('check'.$user_id,null);
            $this->returnMessage(1,'','');
        }else{
            $this->returnMessage(0,'请勿多次提交','');
        }
    }
    /**
     * @description 返回用户的推荐二维码
     * 1. 二维码的命名为用户的手机号码
     */
    public function QrCode()
    {
        $userId = \zhong_decrypt( I( 'post.app_user_id/s' ) );
        $user = M( 'user' )->field( 'mobile,member_status' )->where( [ 'id' => $userId ] )->select()[0];
        if( empty($user['mobile']) ){
            $this->returnMessage( 0,'请先绑定手机号码',[] );
        }
        if( $user['member_status'] == 1){
            $this->returnMessage( 0,'您还没有推荐权限',[] );
        }
        $url2    = M( 'system_config' )->where( 'id=12' )->getField( 'config_value' );
        $url2    = unserialize( $url2 );
        $url    = 'http://'.$url2[ 'internet_url' ].'/mobile/index.html#/Register?reco_code=' . $userId . '&qrcode=1';

        $fileName = './Uploads/DistributionQrCode/' . $user['mobile'] . '.png';
        $data['url'] = $url;
        $data['path'] = $fileName;
        $data['id'] = $userId;
        $data['mobile'] = $user['mobile'];
        $data['yuming'] = 'http://'.$url2[ 'internet_url' ];
        if( \file_exists(  $fileName ) ){
            $this->returnMessage( 1,'操作成功',$data );
        }

        $file = $this->buildQrCode($data);
//        var_dump($file);die;
//        QrCode::png( $url,'./' . $fileName,'','','','aa' );
//        $this->returnMessage( 1,'操作成功',[ 'pngUrl' => $fileName,'url' => $url ] );
        $this->returnMessage( 1,'操作成功', $file );
    }
    /**
     * 生成二维码图片
     */
    protected function buildQrCode( array $post )
    {

        include_once COMMON_PATH . 'Tool/QRcode.class.php';
        $url = $post['url'];
        $path = $post['path'];

        QRcode::png( $url,$path,QR_ECLEVEL_H,4 );

        //添加logo
        $logo = M('user_header')->where(['user_id'=>$post['id']])->getField('user_header');
        $logo = $post['yuming'].$logo;
        if ($logo !== FALSE) {
            $QR = $path;
            $QR = imagecreatefromstring(file_get_contents($QR));
            $logo = imagecreatefromstring(file_get_contents($logo));
            $QR_width = imagesx($QR);//二维码图片宽度
            $QR_height = imagesy($QR);//二维码图片高度
            $logo_width = imagesx($logo);//logo图片宽度
            $logo_height = imagesy($logo);//logo图片高度
            $logo_qr_width = $QR_width / 5;
            $scale = $logo_width/$logo_qr_width;
            $logo_qr_height = $logo_height/$scale;
            $from_width = ($QR_width - $logo_qr_width) / 2;
            //重新组合图片并调整大小
            imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,
                $logo_qr_height, $logo_width, $logo_height);
        }
        //输出图片
        imagepng($QR,'./Uploads/DistributionQrCode/'.$post['mobile'].'.png' );
        $img = './Uploads/DistributionQrCode/'.$post['mobile'].'.png';

//        showData($img,1);
//        Tool::partten( $post[ 'path' ],UnlinkPicture::class );

        $post[ 'path' ] = substr( $img,1 );
        $save[ 'code_path' ] = $img;
        M( 'user' )->where( "id='%s'",$post[ 'id' ] )->save( $save );
        return $post;
    }

}