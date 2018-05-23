<?php
/* 
 * userLogin用户登陆     
 */
require_once('./Config/Config.php');
try{
	$connect = Db::getInstance()->dbConnect();
}catch (Exception $e){
	return Response::show('400','数据库链接失败');
}
/*
 * 分页代码 需要传递两个参数    
 *
 *		$page 当前页数    
 *		$pagesize 每页显示的最大条数
 */
$page = isset($_GET['page'])?$_GET['page']:1;  //当前页码
$pageSize = isset($_GET['pagesize'])?$_GET['pagesize']:10; //每页显示的数量
$offset = ($page - 1) * $pageSize;
$action = $_REQUEST['action'];	//接口名称
switch($action){
	// 用户登陆
	case 'user_login':
		$mobile = $_REQUEST['mobile'];
		$reg_id = $_REQUEST['registrationId'];
		$password = md5($_REQUEST['password']);
		//file_put_contents('dl.txt',$_REQUEST);
		$sql = "SELECT a.id,b.grade_name FROM db_user as a LEFT JOIN vip_member as b ON a.id = b.id WHERE a.mobile = $mobile AND a.password = '$password' LIMIT 1";
		$query = @mysql_query($sql,$connect);
		while($result = @mysql_fetch_assoc($query)){
			$data[] = $result;
		}
		if(empty($data)){
			return Response::show('400','登陆失败');
		}
		$sql = "UPDATE db_user SET reg_id = '$reg_id' WHERE mobile='$mobile'";
		$query = @mysql_query($sql,$connect);
		
		return Response::show('200','登陆成功',$data);
		break;
	
	//找回密码 -> 验证手机号是否存在
	case 'check_mobile':
		$mobile = $_REQUEST['mobile'];
		$sql = "SELECT id FROM db_user WHERE mobile = $mobile LIMIT 1";
		$query = @mysql_query($sql,$connect);
		while($result = @mysql_fetch_assoc($query)){
			$data[] = $result;
		}
		if(empty($data)){
			return Response::show('400','验证失败');
		}
		return Response::show('200','验证成功',$data);

		break;
	//重置密码.
	case 'rest_password':
		$mobile = $_REQUEST['mobile'];
		$sms_code = $_REQUEST['sms_code'];
		$password = md5(trim($_REQUEST['password']));

		$sql = "SELECT sms_code FROM db_user_sms WHERE mobile = $mobile LIMIT 1";
		$query = @mysql_query($sql, $connect);
		$result = @mysql_fetch_assoc($query);

		//判断验证码是否正确
		if ($sms_code != implode('', $result)) {
			return Response::show('400', '抱歉，请输入正确的验证码');
		}

		$sql = "UPDATE db_user SET password = '$password' WHERE mobile='$mobile'";
		$query = mysql_query($sql,$connect);
		if($query == false){
			return Response::show('400','重置失败');
		}

		return Response::show('200','修改成功');

		break;
	//发短信接口
	case 'get_sms':
		//$mobile
		$mobile = trim($_REQUEST['mobile']);
		if(!is_numeric($mobile)){
			return Response::show('400','抱歉，请输入正确的手机号');
		}
		$data[] = Library::send_newsms($mobile);
		$sms_code = $data[0]['sms_code'];
		$time = time();
		//判断数据库中是否有此手机验证过
		$sql = "SELECT id FROM db_user_sms WHERE mobile = $mobile LIMIT 1";
		$query = @mysql_query($sql,$connect);
		if(!$result = @mysql_fetch_assoc($query)){
			$sql = "INSERT INTO db_user_sms (sms_code,mobile,create_time) VALUES ('$sms_code','$mobile','$time')";
		}else{
			$sql = "UPDATE db_user_sms SET sms_code='$sms_code' where mobile='$mobile'";
		}
		$query = @mysql_query($sql,$connect);
		return Response::show('200','短信已发送，请注意查看手机',$data);
		break;
	//注册
	case 'user_reg':
		$mobile = trim($_REQUEST['mobile']);
		$password = md5(trim($_REQUEST['password']));
		$sms_code = $_REQUEST['sms_code'];
		$idcard = $_REQUEST['idcard'];
		$realname = $_REQUEST['realname'];
		$pid = $_REQUEST['id'];

		$sql = "SELECT id FROM db_user WHERE mobile = $mobile";
		$query = @mysql_query($sql,$connect);
		$result = @mysql_num_rows($query);
			if($result>0){
				return Response::show('400','账号已经被注册过');
			}

			$sql = "SELECT sms_code FROM db_user_sms WHERE mobile = $mobile LIMIT 1";
			$query = @mysql_query($sql, $connect);
			$result = @mysql_fetch_assoc($query);

			//判断验证码是否正确
			if ($sms_code != implode('', $result)) {
				return Response::show('400', '抱歉，请输入正确的验证码');
			}

			//找到推荐人的id 如果没有,不允许注册.
			$sql = "SELECT id FROM vip_member WHERE id = $pid LIMIT 1";
			$query = @mysql_query($sql, $connect);
			if($res = @mysql_fetch_assoc($query)){

				//查找推荐人的path等级关系
				$sql = "SELECT path FROM vip_member WHERE id = '$pid'";
				$query3 = @mysql_query($sql, $connect);
				$res = @mysql_fetch_assoc($query3);
				$path_pid = implode(' ', $res);
				$time =time();
				//添加用户到user表
				$sql = "INSERT INTO db_user (id,mobile,realname,idcard,password,create_time,status) VALUES (null,'$mobile','$realname','$idcard','$password',$time,1)";
				$query2 = @mysql_query($sql, $connect);
				if (!$query2) {
					return Response::show('400', '添加失败');
				}
				//添加用户到VIP表

				$sql = "INSERT INTO vip_member(id,pid,mobile,grade_name,true_name,card_id,create_time,status) VALUES (null,'$pid','$mobile','游客','$realname','$idcard','$time',1)";
				$query1 = @mysql_query($sql, $connect);

				if (!$query1 ) {
					return Response::show('400', '添加VIP失败');
				}

				//找到添加后的用户VIP表中的userid;
				$path_id = mysql_insert_id();
				$path=$path_pid.$path_id.'-';
				//修改VIP表中的层级关系字段path
				$sql = "UPDATE vip_member SET path='$path',user_id='$path_id' WHERE id = '$path_id'";
				$query = @mysql_query($sql,$connect);
				if(!$query){
					return Response::show('400','修改失败');
				}

			}else{
				return Response::show('400', '抱歉, 推荐人ID不正确');
			}
		return Response::show('200','注册成功');
		break;

	//登陆后的个人中心
	case 'person_center':
		$id = $_REQUEST['id'];
		$sql = "SELECT a.id,a.mobile,a.integral,b.grade_name FROM db_user as a LEFT JOIN vip_member as b ON a.id = b.id WHERE a.id = '$id'";
		$query = @mysql_query($sql,$connect);
		while($result = @mysql_fetch_assoc($query)){
			$data[] = $result;
		}
		//var_dump($data);
		if(empty($data)){
			return Response::show('400','查询失败');
		}

		return Response::show('200','访问成功',$data);
	break;


	//个人信息
	case 'person_information':
		$id = $_REQUEST['id'];

		$sql = "SELECT a.account_balance,a.realname,a.idcard,a.sex,a.birthday,a.mobile,b.id as addressid,b.realname as addressrealname,b.mobile as addressmobile,b.prov,b.city,b.dist,b.address,b.status,b.zipcode FROM db_user as a LEFT JOIN db_user_address as b ON  b.user_id = a.id WHERE a.id = '$id'";
		$query = @mysql_query($sql,$connect);
		while($result = @mysql_fetch_assoc($query)){
			$data[] = $result;
		}

		if(empty($data)){
			return Response::show('400','查询失败');
		}

		return Response::show('200','访问成功',$data);
		break;



	//修改姓名
	case 'change_name':
		$id = $_REQUEST['id'];
		$realname = $_REQUEST['realname'];
		$sql = "UPDATE db_user SET realname='$realname' WHERE id = '$id'";
		$query = @mysql_query($sql,$connect);
		if(!$query){
			return Response::show('400','修改失败');
		}
		return Response::show('200','修改成功');
		break;



	//修改身份证号
	case 'change_idcard':
		$id = $_REQUEST['id'];
		//身份证 判断是否符合格式  如果不符合 不允许注册
		$idcard = $_REQUEST['idcard'];
		if (!preg_match("/^(\d{18,18}|\d{15,15}|\d{17,17}x)$/", $idcard)) {
			//15位,18位验证
			return Response::show('400', '抱歉，请输入正确的身份证号');
		}
		$sql = "UPDATE db_user SET idcard='$idcard' WHERE id = '$id'";

		$query = @mysql_query($sql,$connect);
		if(!$query){
			return Response::show('400','修改失败');
		}
		return Response::show('200','修改成功');
		break;



	//修改性别
	case 'change_sex':
		$id = $_REQUEST['id'];
		//身份证 判断是否符合格式  如果不符合 不允许注册
		$sex = $_REQUEST['sex'];
		if($sex == 'm'){
			$sex = '男';
		}else{
			$sex = '女';
		}
		$sql = "UPDATE db_user SET sex='$sex' WHERE id = '$id'";
		$query = @mysql_query($sql,$connect);
		if(!$query){
			return Response::show('400','修改失败');
		}
		return Response::show('200','修改成功');
		break;

	//修改生日
	case 'change_birthday':
		$id = $_REQUEST['id'];
		//身份证 判断是否符合格式  如果不符合 不允许注册
		$birthday = $_REQUEST['birthday'];

		$sql = "UPDATE db_user SET birthday='$birthday' WHERE id = '$id'";
		$query = @mysql_query($sql,$connect);
		if(!$query){
			return Response::show('400','修改失败');
		}
		return Response::show('200','修改成功');
		break;
	//修改手机号
	case 'change_phone':
		$id = $_REQUEST['id'];
		//身份证 判断是否符合格式  如果不符合 不允许注册
		$mobile = $_REQUEST['mobile'];

		$sql = "UPDATE db_user SET mobile='$mobile' WHERE id = '$id'";
		$sql1 ="UPDATE vip_member SET mobile='$mobile' WHERE user_id = '$id'";
		$query = @mysql_query($sql,$connect);
		$query1 = @mysql_query($sql1,$connect);
		if(!$query){
			return Response::show('400','修改失败');
		}
		return Response::show('200','修改成功');
		break;

	//修改密码
	case 'change_password':
		$id = $_REQUEST['id'];
		$password = md5(trim($_REQUEST['password']));
		$newpassword = md5(trim($_REQUEST['newpassword']));
		$newpasswordtwo = md5(trim($_REQUEST['newpasswordtwo']));
		//空值加密后的值
		if($password =='d41d8cd98f00b204e9800998ecf8427e'){
			return Response::show('400','原密码不能为空');
		}
		if($newpassword =='d41d8cd98f00b204e9800998ecf8427e'){
			return Response::show('400','新密码不能为空');
		}
		//先查询原密码是否正确
		$sql = "SELECT password FROM db_user WHERE id = $id LIMIT 1";
		$query = @mysql_query($sql,$connect);
		$result = @mysql_fetch_assoc($query);

		if($password != implode(' ',$result)){
			return Response::show('400','原密码不正确');
		}

		//修改数据库
		$sql = "UPDATE db_user SET password='$newpassword' WHERE id = '$id'";
		$query = @mysql_query($sql,$connect);
		if(!$query){
			return Response::show('400','密码修改失败');
		}
		return Response::show('200','密码修改成功');
		break;


		//修改地址
	case 'change_address':
		$addressid = $_REQUEST['addressid'];
		$addressrealname = $_REQUEST['addressrealname'];
		$addressmobile = $_REQUEST['addressmobile'];
		$address = $_REQUEST['address'];
		$prov = $_REQUEST['prov'];
		$city = $_REQUEST['city'];
		$dist = $_REQUEST['dist'];
		$zipcode = $_REQUEST['zipcode'];
		$user_id = $_REQUEST['user_id'];
        $time  =time();
		//直接修改数据表中的信息
		if(empty($_REQUEST['status'])){
			$status=0;//不默认值为0;
		}else{
			$status=1;//默认值为1;
		}
		$sql = "UPDATE db_user_address SET realname='$addressrealname',mobile='$addressmobile',prov='$prov',city='$city',dist='$dist',address='$address',status='$status',zipcode='$zipcode',update_time='$time' WHERE id = '$addressid'";
		$query = @mysql_query($sql,$connect);
		if(!$query){
			return Response::show('400','修改失败');
		 }
		//修改数据表其他的默认地址
		if($_REQUEST['status']==1){
			$sql = "UPDATE db_user_address SET status=0 WHERE user_id = '$user_id' AND id<>'$addressid'";
			$query = @mysql_query($sql,$connect);
			if(!$query){
				return Response::show('400','修改失败');
			}
		}
		return Response::show('200','修改成功');
		break;

	// 添加新地址
	case 'address_add':
		//会员id
		$id = $_REQUEST['id'];
		$addressrealname = $_REQUEST['addressrealname'];
		$addressmobile = $_REQUEST['addressmobile'];
		$address = $_REQUEST['address'];
		$prov = $_REQUEST['prov'];
		$city = $_REQUEST['city'];
		$dist = $_REQUEST['dist'];
		$zipcode = $_REQUEST['zipcode'];
		if(empty($_REQUEST['status'])){
			$status=0;//不默认值为0;
		}else{
			$status=1;//默认值为0;
			$sql = "update  db_user_address set status=0 where user_id ='$id'";
			$query = @mysql_query($sql,$connect);
		}
		$time=time();
		$sql = "INSERT INTO db_user_address (id,realname,mobile,user_id,create_time,prov,city,dist,address,status,zipcode) VALUES (null,'$addressrealname','$addressmobile','$id','$time','$prov','$city','$dist','$address','$status','$zipcode')";
		$query = @mysql_query($sql,$connect);

		if(!$query){
			return Response::show('400','添加失败1');
		}
		$addressid=mysql_insert_id();
		//修改数据表其他的默认地址
		if($_REQUEST['status']==1){
			$sql = "UPDATE db_user_address SET status=0 WHERE user_id = '$id' AND id<>'$addressid'";
			$query = @mysql_query($sql,$connect);
			if(!$query){
				return Response::show('400','修改失败');
			}
		}
		return Response::show('200','添加成功');
		break;
		//删除地址
		case 'del_address':
			$addressid = $_REQUEST['addressid'];

			$sql = "DELETE FROM db_user_address WHERE id='$addressid'";
			$query = @mysql_query($sql,$connect);
			if(!$query){
				return Response::show('400','删除失败');
			}
			return Response::show('200','删除成功');
			break;

	//账户管理
	case 'person_manage':
		//会员id
		$id = $_REQUEST['id'];
		$sql = "SELECT count(*) as num FROM db_bank_card WHERE user_id='$id'";
		$query = @mysql_query($sql,$connect);
		while($result = @mysql_fetch_assoc($query)){
			$data['num'] = $result['num'];
		}
		$sql = "SELECT account_balance,integral,add_jf_currency,add_jf_limit FROM db_user WHERE id='$id'";
		$query = @mysql_query($sql,$connect);
		while($result = @mysql_fetch_assoc($query)){
			$data['account_balance'] = $result['account_balance'];
			$data['integral'] = $result['integral'];
			$data['add_jf_currency'] = $result['add_jf_currency'];
			$data['add_jf_limit'] = $result['add_jf_limit'];
		}


		if(empty($data)){
			return Response::show('400','查询失败');
		}

		return Response::show('200','访问成功',$data);
		break;


	//银行卡管理
	case 'person_bankcard':
		$id = $_REQUEST['id'];
		$sql = "SELECT id,belong,type,card_num  FROM  db_bank_card  WHERE user_id='$id'";
		$query = @mysql_query($sql,$connect);
		while($result = @mysql_fetch_assoc($query)){
			$data[] = $result;
		}

		if(empty($data)){
			return Response::show('400','查询失败');
		}

		return Response::show('200','访问成功',$data);
		break;

	// 添加新银行卡
	case 'bankcard_add':
		//会员id
		$id = $_REQUEST['id'];
		$realname = $_REQUEST['realname'];
		$id_card = $_REQUEST['id_card'];
		$type = $_REQUEST['type'];
		$belong = $_REQUEST['belong'];
		$card_num = $_REQUEST['card_num'];
		$mobile = $_REQUEST['mobile'];
		$time = time();
		$sql = "INSERT INTO db_bank_card (id,user_id,realname,id_card,type,create_time,belong,card_num,mobile) VALUES (null,'$id','$realname','$id_card','$type','$time','$belong','$card_num','$mobile')";
		$query = @mysql_query($sql,$connect);
		if(!$query){
			return Response::show('400','添加失败');
		}
		return Response::show('200','添加成功');
		break;

	//银行卡解绑
	case 'del_bankcard':
		//银行卡id
		$id = $_REQUEST['id'];
		$sql = "DELETE FROM db_bank_card WHERE id='$id'";
		$query = @mysql_query($sql,$connect);
		if(!$query){
			return Response::show('400','删除失败');
		}
		return Response::show('200','删除成功');
		break;
	//会员等级:
	case 'vip':
		$id = $_REQUEST['id'];
		$sql = "SELECT grade_name,vip_end FROM vip_member WHERE id='$id'";
		$query = @mysql_query($sql,$connect);
		while($result = @mysql_fetch_assoc($query)){
			$name =$result['grade_name'];
			$data[] = $result;
		}

		if($name == '合伙人'){
			$data=array();
			$data[]=array('grade_name'=>'合伙人');
		}
	return Response::show('200','查询成功',$data);
	break;
	//我的收藏
	case 'person_collect':
		//会员的id
		$id = $_REQUEST['id'];
		$sql = "SELECT id,goods_id,pic_url,price_new,price_old,detail_title,is_type FROM  db_goods_shoucang  WHERE user_id='$id' ";
		$query = @mysql_query($sql,$connect);
		while($result = @mysql_fetch_assoc($query)){
			$data[] = $result;
		}
		if(empty($data)){
			return Response::show('400','查询失败');
		}

		return Response::show('200','查询成功',$data);
		break;
	//添加收藏
	case'add_collect':
		/* $goods_id=$_REQUEST['goods_id'];
		$user_id =$_REQUEST['user_id']; */
		if(isset($_REQUEST['goods_id'])){
			$goods_id=$_REQUEST['goods_id'];
		}else{
			return Response::show('400','添加失败');
		}
		if(isset($_REQUEST['user_id'])){
			$user_id=$_REQUEST['user_id'];
		}else{
			return Response::show('400','添加失败2');
		}
		/* if($user_id=='' || $goods_id==''){
			return Response::show('400','添加失败');
		} */
		$is_type=$_REQUEST['is_type'];
		$create_time=time();
		$sql = "SELECT * FROM db_goods WHERE id=".$goods_id;
		$query = @mysql_query($sql,$connect);
		while($result = @mysql_fetch_assoc($query)){
			$goods_info = $result;
		}
		$detail_title=$goods_info['title'];
		$detail_title=str_replace('"','\"',$detail_title);
		$detail_title=str_replace("'","\'",$detail_title);//转义特殊字符
		$pic_url=$goods_info['pic_url'];
		$price_old=$goods_info['price_old'];
		$price_new=$goods_info['price_new'];
		//判断是否是已经收藏了
		$sql="select id from db_goods_shoucang where user_id='$user_id' AND goods_id='$goods_id'";
		$query = @mysql_query($sql,$connect);
		$result = @mysql_fetch_assoc($query);
		if(!empty($result)){
			return Response::show('400','已收藏');
		}
		$sql = "INSERT INTO db_goods_shoucang VALUES (null,'$goods_id','$user_id','$create_time','$pic_url','$price_old','$detail_title','$price_new','$is_type')";
		$query = @mysql_query($sql,$connect);
		if(!$query){
			return Response::show('400','添加失败');
		}
		return Response::show('200','添加成功');
		break;
	//我收藏的删除
	case 'del_collect':
		$id = $_REQUEST['id'];//ID
		$sql = "DELETE FROM db_goods_shoucang WHERE id ='$id' ";

		$query = @mysql_query($sql,$connect);
		if(!$query){
			return Response::show('400','删除失败');
		}
		return Response::show('200','删除成功');
		break;
		//清空收藏
	case 'empty_collect':
		$id = $_REQUEST['id'];//会员id
		$sql = "DELETE FROM db_goods_shoucang WHERE user_id ='$id' ";

		$query = @mysql_query($sql,$connect);
		if(!$query){
			return Response::show('400','清空失败');
		}
		return Response::show('200','清空成功');
		break;


	//我的足迹
	case 'person_footprint':
	//会员的id
	$id = $_REQUEST['id'];
	$sql = "SELECT id,gid,goods_pic,goods_name,goods_price,is_type FROM  db_foot_print  WHERE uid='$id' ORDER BY create_time desc ";
	$query = @mysql_query($sql,$connect);
	while($result = @mysql_fetch_assoc($query)){
		$data[] = $result;
	}
	if(empty($data)){
		return Response::show('400','查询失败');
	}

	return Response::show('200','查询成功',$data);
	break;
//清空足迹
	case 'empty_footprint':
		$user_id = $_REQUEST['id'];//用户ID
		$sql = "DELETE FROM db_foot_print WHERE uid ='$user_id' ";

		$query = @mysql_query($sql,$connect);
		if(!$query){
			return Response::show('400','清空失败');
		}
		return Response::show('200','清空成功');
		break;
//删除单个足迹
	case 'del_footprint':
		$id = $_REQUEST['id'];//足迹ID
		$sql = "DELETE FROM db_foot_print WHERE id ='$id' ";

		$query = @mysql_query($sql,$connect);
		if(!$query){
			return Response::show('400','删除失败');
		}
		return Response::show('200','删除成功');
		break;
//订单管理(全部)
	case 'order_list':
		//会员的id
		$id = $_REQUEST['id'];
		//orders_status    0 订单默认状态   2  已发货      3 已签收    4 已申请退货   5 已完成

		$sql = "SELECT id,orders_num,price_shiji,create_time,orders_status,order_type,pay_status,price_sum,use_jf_currency,use_jf_limit FROM  db_goods_orders  WHERE user_id='$id' AND orders_status != -1  ORDER by create_time DESC LIMIT ". $offset. " , " .$pageSize;
		$query = @mysql_query($sql,$connect);
		while($result = @mysql_fetch_assoc($query)){
			$data[] = $result;
		}
		if(empty($data)){
			return Response::show('400','查询失败');
		}
		foreach ($data as $key => $value) {
			$orders_id = $value['id'];//订单ID
			$sql = "SELECT id,goods_id,goods_title,goods_num,price_new,pic_url,chufa_date,chufa_price,goods_orders_id,chufa_price_et,goods_num_et FROM  db_goods_orders_record WHERE goods_orders_id='$orders_id' ";
			$query = @mysql_query($sql,$connect);
			while($result = @mysql_fetch_assoc($query)){
				$data[$key]['goods'][] = $result;
			}
		}
		//var_dump($data);
		return Response::show('200','查询成功',$data);
		break;

	//订单管理(待付款)
	case 'payment_list':
		//会员的id
		$id = $_REQUEST['id'];

		$sql = "SELECT id,orders_num,price_shiji,create_time,orders_status,order_type,pay_status,price_sum,use_jf_currency,use_jf_limit FROM  db_goods_orders  WHERE user_id='$id' AND orders_status = 0 AND pay_status =0  ORDER by create_time DESC LIMIT ". $offset. " , " .$pageSize;
		$query = @mysql_query($sql,$connect);
		while($result = @mysql_fetch_assoc($query)){
			$data[] = $result;
		}
		if(empty($data)){
			return Response::show('400','查询失败');
		}
		foreach ($data as $key => $value) {

			$orders_id = $value['id'];//订单ID
			$sql = "SELECT id,goods_id,goods_title,goods_num,price_new,pic_url,chufa_date,chufa_price,goods_orders_id,chufa_price_et,goods_num_et FROM  db_goods_orders_record WHERE goods_orders_id='$orders_id' ";
			$query = @mysql_query($sql,$connect);
			while($result = @mysql_fetch_assoc($query)){
				$data[$key]['goods'][] = $result;
			}
		}
		return Response::show('200','查询成功',$data);
		break;
	//订单管理(待收货)

	case 'receipt_goods':
		//会员的id
		$id = $_REQUEST['id'];
		$sql = "SELECT id,orders_num,price_shiji,create_time,orders_status,order_type,pay_status,price_sum,use_jf_currency,use_jf_limit FROM  db_goods_orders  WHERE user_id='$id' AND orders_status = 2 and pay_status=1  ORDER by create_time DESC LIMIT ". $offset. " , " .$pageSize;
		$query = @mysql_query($sql,$connect);
		while($result = @mysql_fetch_assoc($query)){
			$data[] = $result;
		}
		if(empty($data)){
			return Response::show('400','查询失败');
		}
		foreach ($data as $key => $value) {

			$orders_id = $value['id'];//订单ID
			$sql = "SELECT id,goods_id,goods_title,goods_num,price_new,pic_url,chufa_date,chufa_price,goods_orders_id,chufa_price_et,goods_num_et FROM  db_goods_orders_record WHERE goods_orders_id='$orders_id' ";
			$query = @mysql_query($sql,$connect);
			while($result = @mysql_fetch_assoc($query)){
				$data[$key]['goods'][] = $result;
			}
		}
		return Response::show('200','查询成功',$data);
		break;
	//订单管理(待评价)
	case 'payments_waite':
		//会员的id
		$id = $_REQUEST['id'];
		//LEFT JOIN db_goods_orders as b    AND  b.orders_status=3
		//AND pingjia_content is null  AND  a.orders_status=3
		// 3 已签收 且  评价内容为空 为待评价
		$sql = "SELECT id,orders_num,price_shiji,create_time,orders_status,order_type,pay_status,price_sum,use_jf_currency,use_jf_limit FROM  db_goods_orders  WHERE user_id='$id' AND pingjia_time is null  AND orders_status=3 and pay_status=1  ORDER by create_time DESC LIMIT ". $offset. " , " .$pageSize;
		$query = @mysql_query($sql,$connect);
		while($result = @mysql_fetch_assoc($query)){
			$data[] = $result;
		}
		if(empty($data)){
			return Response::show('400','查询失败');
		}
		foreach ($data as $key => $value) {

			$orders_id = $value['id'];//订单ID
			$sql = "SELECT id,goods_id,goods_title,goods_num,price_new,pic_url,chufa_date,chufa_price,goods_orders_id,chufa_price_et,goods_num_et FROM  db_goods_orders_record WHERE goods_orders_id='$orders_id' ";
			$query = @mysql_query($sql,$connect);
			while($result = @mysql_fetch_assoc($query)){
				$data[$key]['goods'][] = $result;
			}
		}

		return Response::show('200','查询成功',$data);
		break;

	//订单管理(待完成)
	case 'payments_ok':
		//会员的id
		$id = $_REQUEST['id'];
		//LEFT JOIN db_goods_orders as b    AND  b.orders_status=3
		// 3 已签收 且  评价内容为空 为待评价
		$sql="select b.goods_id,b.goods_title,b.goods_num,b.price_new,b.pic_url,b.chufa_date,b.chufa_price,b.goods_orders_id,b.taocan_name,a.orders_num,a.orders_status,a.shouhuo_time,b.chufa_price_et,b.goods_num_et,a.price_sum,a.use_jf_currency,a.use_jf_limit  from   db_goods_orders_record as b  LEFT JOIN db_goods_orders as a  on  b.goods_orders_id=a.id where b.user_id='$id'  AND  a.orders_status=3 ";
		$query = @mysql_query($sql,$connect);
		while($result = @mysql_fetch_assoc($query)){
			$data[] = $result;
		}

		if(empty($data)){
			return Response::show('400','查询失败');
		}

		return Response::show('200','查询成功',$data);
		break;

	//取消订单
	case 'cancel_order':
		$orders_num = $_REQUEST['orders_num'];//订单id
		//查找有无此订单
		$sql = "SELECT user_id,use_jf_currency,use_jf_limit,add_jf_currency,add_jf_limit 
		FROM db_goods_orders  
        LEFT JOIN db_user ON db_goods_orders.user_id=db_user.id
		WHERE orders_num='$orders_num' AND orders_status='0'";
		$query = @mysql_query($sql,$connect);
		while($result = @mysql_fetch_assoc($query)){
			$data[] = $result;
		}
		if(empty($data)){
			return Response::show('400','没找到订单,或者已经支付过了');
		}
		//修改订单表中的此订单,-1 取消状态 orders_status
		$sql = "UPDATE db_goods_orders SET orders_status=-1 WHERE orders_num='$orders_num'";
		$query = @mysql_query($sql,$connect);
		if(!$query){
			return Response::show('400','订单状态更新失败');
		}

        foreach ($data as $key => $value) {
        	$user_id = $value['user_id'];
        	$add_jf_limit = $value['add_jf_limit']+$value['use_jf_limit'];
        	$add_jf_currency = $value['add_jf_currency']+$value['use_jf_currency'];
        	$integral = $add_jf_limit+$add_jf_currency;
        	$sql = "UPDATE db_user SET add_jf_limit='$add_jf_limit',add_jf_currency='$add_jf_currency',integral='$integral' WHERE id='$user_id'";
            $query = @mysql_query($sql,$connect);
			if(!$query){
				return Response::show('400','修改用户积分失败');
			}
        }

		//如果状态已经是修改了的就不能执行下面的代码 多增加库存了;
		$sql = "SELECT id FROM db_goods_orders WHERE orders_num='$orders_num' AND orders_status=-1";
		$query = @mysql_query($sql,$connect);
		$result = @mysql_fetch_assoc($query);
		if(!$result){
			return Response::show('400','此订单已经取消了');
		}

		//修改商品表库存
		$id = implode(' ',$result);
		//var_dump($id);
		//查找订单中的商品id 和商品数量
		$sql = "SELECT goods_id,goods_num FROM db_goods_orders_record WHERE goods_orders_id='$id' ";
		$query = @mysql_query($sql,$connect);
		while($result = @mysql_fetch_assoc($query)){
			$res[] =$result;
		}
		//var_dump($res);
		foreach($res as $goods) {

			$goods_id=isset($goods['goods_id'])?$goods['goods_id']:"";
			//var_dump($goods_id);
			$goods_num=isset($goods['goods_num'])?$goods['goods_num']:"";
			//修改goods表
			//查找出原来的库存
			$sql = "SELECT kucun FROM db_goods WHERE id='$goods_id' ";
			$query = @mysql_query($sql,$connect);
			$result = @mysql_fetch_assoc($query);
			//var_dump($result);exit;
			//增加库存
			$sql = "UPDATE db_goods SET kucun=".$result['kucun']."+$goods_num WHERE id ='$goods_id'";
			$query = @mysql_query($sql,$connect);
			if(!$query){
				return Response::show('400','订单状态更新失败');
			}
		}

		return Response::show('200','更新成功');
		break;
	//确认密码
	case 'password':
		$password=md5(trim($_REQUEST['password']));
		$id=$_REQUEST['id'];
		//和用户表中的密码做比对
		$sql = "SELECT password FROM db_user WHERE id = $id LIMIT 1";
		$query = @mysql_query($sql,$connect);
		$result = @mysql_fetch_assoc($query);

		if($password != implode(' ',$result)){
			return Response::show('400','密码错误,请输入正确的密码');
		}

		return Response::show('200','验证成功');
		break;
	//确认收货
	case 'confirm':
		$id = $_REQUEST['id'];
		$password = md5($_REQUEST['password']);
		$orders_num = $_REQUEST['orders_num'];//订单id

		$sql = "SELECT mobile  FROM db_user  WHERE id = '$id' AND password = '$password' LIMIT 1";
		//file_put_contents('ss.txt',$sql);
		$query = @mysql_query($sql,$connect);
		while($result = @mysql_fetch_assoc($query)){
			$data[] = $result;
		}
		if(empty($data)){
			return Response::show('400','密码错误');
		}

		//shouhuo_time  确认收货时间  //orders_status    0 订单默认状态  1  已支付 2  已发货      3 已签收    4 已申请退货   5 已完成

		//查找这个订单是不是待收获
		$sql = "SELECT user_id,id,fanli_jifen,order_type FROM db_goods_orders WHERE orders_num='$orders_num' AND orders_status>=2 ";
		//file_put_contents("ce.txt",$sql);
		$query = @mysql_query($sql,$connect);
		$data = @mysql_fetch_assoc($query);

		if(!$data){
			return Response::show('400','订单的状态不正确');
		}
		$time =time();
		//修改订单状态  已签收
		$sql = "UPDATE db_goods_orders SET orders_status=3,shouhuo_time='$time' WHERE orders_num ='$orders_num'";
		$query = @mysql_query($sql,$connect);
		if(!$query){
			return Response::show('400','订单状态更新失败');
		}
		//加积分
		//$data['fanli_jifen'];$data['order_type'];
		$user_id=$data['user_id'];
		$sql="SELECT integral,add_jf_currency,add_jf_limit FROM db_user WHERE id=$user_id";
		$query = @mysql_query($sql,$connect);
		$result = @mysql_fetch_assoc($query);
		$A=$result['add_jf_limit']+$data['fanli_jifen'];
		$B=$result['add_jf_currency']+$data['fanli_jifen'];
		$integral=$result['integral']+$data['fanli_jifen'];

		//商品
		if($data['order_type']==0){
			$sql="UPDATE db_user SET integral='$integral',add_jf_limit='$A' WHERE id=$user_id";
			$query = @mysql_query($sql,$connect);
			
		}
		//旅游
		if($data['order_type']==1){
			$sql="UPDATE db_user SET integral='$integral',add_jf_currency='$B' WHERE id=$user_id";
			$query = @mysql_query($sql,$connect);
		}

		file_get_contents("http://www.zzumall.com/index.php/api/index/done_order/pwd/isvip/user_id/".$user_id."/orders_id/".$data['id']);
		return Response::show('200','更新成功');
		break;

	//退货
	case 'tuihuo':
		$orders_num = $_REQUEST['orders_num'];//订单id
		$tuihuo_case = $_REQUEST['tuihuo_case'];//t退货理由
		//先判断退货订单是不是已收货的
		$sql = "SELECT id FROM db_goods_orders WHERE orders_num='$orders_num' AND pay_status=1  ";
		$query = @mysql_query($sql,$connect);
		while($result = @mysql_fetch_assoc($query)){
			$data[]  = $result;
		}
		if(!$data){
		return Response::show('400','订单的状态不正确');
		}
		$time =time();
		//修改订单状态
		$sql = "UPDATE db_goods_orders SET tuihuo_case='$tuihuo_case',tuihuo_time='$time',orders_status=4 WHERE orders_num ='$orders_num'";
		$query = @mysql_query($sql,$connect);
		if(!$query){
			return Response::show('400','订单状态更新失败');
		}
		return Response::show('200','查看成功');
		break;


	//查看物流(返回快递单号)

	case 'kuaidi':
		$orders_num = $_REQUEST['orders_num'];//订单id

		//先查看是否有这个订单 , 状态是不是已发货
		$sql = "SELECT kuaidi_num  FROM db_goods_orders WHERE orders_num='$orders_num' AND orders_status>=2";
		$query = @mysql_query($sql,$connect);
		while($result = @mysql_fetch_assoc($query)){
			$data[]  = $result;
		}
		if(!$data){
			return Response::show('400','订单的状态不正确');
		}

		return Response::show('200','查看成功',$data);
		break;

		//评价列表
		case 'wait_pingjia':
		$user_id=$_REQUEST['user_id'];
		$sql = "SELECT a.id,a.goods_id,a.goods_title,a.pic_url,a.taocan_name,a.goods_num,a.price_new,
		a.shoper_id,a.chufa_price_et,a.goods_num_et,a.chufa_date,a.chufa_address,
		b.orders_num,a.user_id,b.id as goods_orders_id,b.create_time,b.order_type 
		FROM db_goods_orders_record as a 
		left join db_goods_orders as b on a.goods_orders_id=b.id  
		WHERE a.pingjia_content is null and b.orders_status=3 and a.user_id='$user_id'  
		ORDER by b.create_time 
		DESC LIMIT ". $offset. " , " .$pageSize;
		if($_REQUEST['format']=='array'){
			echo $sql;
		}
		$query = @mysql_query($sql,$connect);
		while($result = @mysql_fetch_assoc($query)){
			$data[]  = $result;
		}
		if(!$data){
			return Response::show('400','没有更多数据');
		}

		return Response::show('200','查看成功',$data);
		break;
		//我的订单(评价)
	case 'pingjia':
		//rate_status  0 未评价   1评价     orders_status 3待评价
		//pingjia_content 评价内容
		//pingjia_status 1 好评 2 中评 3 差评
		//pingjia_time 评价时间
		$user_id=$_REQUEST['user_id'];
		$id=$_REQUEST['id'];//订单商品详情表id
		$goods_orders_id=$_REQUEST['goods_orders_id'];//订单id
		$pingjia_status = $_REQUEST['pingjia_status'];
		$pingjia_content = $_REQUEST['pingjia_content'];
		//判断订单对应的商品是否评价过;
		$sql = "SELECT pingjia_status FROM db_goods_orders_record WHERE id='$id' and user_id='$user_id'";
		$query = @mysql_query($sql,$connect);
		$result = @mysql_fetch_assoc($query);
		$data = $result['pingjia_status'];
		if($data!=0){
			return Response::show('201','已评价过了');
		}
		$time=time();
		//修改订单对应的商品评价;
		$sql ="UPDATE db_goods_orders_record SET pingjia_content='$pingjia_content',pingjia_status='$pingjia_status',pingjia_time='$time' WHERE id='$id' and user_id='$user_id'";
		$query = @mysql_query($sql,$connect);
		if(!$query){
			return Response::show('400','商品评价失败');
		}
		//判断订单中的所有商品是否都评价过
		$data=array();
		$sql = "SELECT pingjia_status FROM db_goods_orders_record WHERE goods_orders_id='$goods_orders_id'";
		$query = @mysql_query($sql,$connect);
		while($result = @mysql_fetch_assoc($query)){
			$data[] =$result;
		}

		foreach($data as $status){
			if($status['pingjia_status'] == null){
				return Response::show('202','商品评价成功,但订单的中还有商品未评价过');
			}
		}
		//若都评价过,修改订单表中的状态
		$sql ="UPDATE db_goods_orders SET rate_status=1 WHERE orders_num='$orders_num'";
		$query = @mysql_query($sql,$connect);
		if(!$query){
			return Response::show('207','更新失败');
		}


		return Response::show('200','评价成功');
		break;
	//我的订单(评价)
	case 'pingjia2':
		//rate_status  0 未评价   1评价     orders_status 3待评价
		//pingjia_content 评价内容
		//pingjia_status 1 好评 2 中评 3 差评
		//pingjia_time 评价时间
		$user_id=$_REQUEST['user_id'];
		$goods_id=$_REQUEST['goods_id'];//订单对应的商品ID
		$goods_orders_id=$_REQUEST['goods_orders_id'];//订单id
		$orders_num = $_REQUEST['orders_num'];//订单号
		$pingjia_status = $_REQUEST['pingjia_status'];
		$pingjia_content = $_REQUEST['pingjia_content'];
		//判断订单对应的商品是否评价过;
		$sql = "SELECT pingjia_status FROM db_goods_orders_record WHERE goods_orders_id='$goods_orders_id'AND goods_id='$goods_id' and user_id='$user_id'";
		$query = @mysql_query($sql,$connect);
		while($result = @mysql_fetch_assoc($query)){
			$data = $result['pingjia_status'];
		}
		$arr=array('1','2','3');
		if(in_array($data,$arr)){
			return Response::show('400','订单的中的此商品已评价过');
		}
		$time=time();
		//修改订单对应的商品评价;
		$sql ="UPDATE db_goods_orders_record SET pingjia_content='$pingjia_content',pingjia_status='$pingjia_status',pingjia_time='$time' WHERE goods_orders_id='$goods_orders_id'AND goods_id='$goods_id' and user_id='$user_id'";
		$query = @mysql_query($sql,$connect);
		if(!$query){
			return Response::show('400','商品评价失败');
		}
		//判断订单中的所有商品是否都评价过
		$data=array();
		$sql = "SELECT pingjia_status FROM db_goods_orders_record WHERE goods_orders_id='$goods_orders_id'";
		$query = @mysql_query($sql,$connect);
		while($result = @mysql_fetch_assoc($query)){
			$data[] =$result;
		}

		foreach($data as $status){
			if($status['pingjia_status'] == null){
				return Response::show('200','商品评价成功,但订单的中还有商品未评价过');
			}
		}
		//若都评价过,修改订单表中的状态
		$sql ="UPDATE db_goods_orders SET rate_status=1 WHERE orders_num='$orders_num'";
		$query = @mysql_query($sql,$connect);
		if(!$query){
			return Response::show('400','更新失败');
		}


		return Response::show('200','更新成功');
		break;

/**---蒋清风--
	/*判断用户是否具有购买资格*/
	case 'can_buy_vip':
		if(isset($_REQUEST['id'])){
			$id=$_REQUEST['id'];
		}else{
			if(isset($_REQUEST['mobile'])){
				$mobile=$_REQUEST['mobile'];
				//查询出用户id
				$sql='select id from db_user where mobile='.$mobile;
				$query = @mysql_query($sql,$connect);
				$result = mysql_fetch_assoc($query);
				if(empty($result)){
					return Response::show('400','不存在该用户');
					break;
				}
				$id=$result['id'];
			}else{
				return Response::show('400','未定义的用户id');
				break;
			}

		}
		$sql="select `grade_name` from `vip_member` where id=".$id.' limit 1';
		//dump($sql);exit;
		$query = @mysql_query($sql,$connect);
		$result = mysql_fetch_assoc($query);
		if($result['grade_name']=="会员" || $result['grade_name']=="合伙人"){
			return Response::show('41','已经具有会员资格,无需再次购买');
			break;
		}
		$sql="select `pid` from `vip_member` where id=".$id.' limit 1';
		$query = @mysql_query($sql,$connect);
		$result = mysql_fetch_assoc($query);
		$sql="select `grade_name` from `vip_member` where id=".$result['pid'].' limit 1';
		$query = @mysql_query($sql,$connect);
		$result = mysql_fetch_assoc($query);
		$data=array();
		if($result['grade_name']=="会员"){

			return Response::show('201','只能购买会员');
		}elseif($result['grade_name']=="合伙人"){

			return Response::show('200','可任意购买');
		}else{

			return Response::show('42','上级不具有会员资格,不能进行购买');
		}
		break;

	/*用户购买会员创建订单*/
	case 'create_buy_vip_order':
		if(isset($_REQUEST['id'])){
			$user_id=$_REQUEST['id'];
		}else{
			return Response::show('401','未定义的用户id');
			break;
		}
		if(isset($_REQUEST['money'])){
			$hf_money=$_REQUEST['money'];
			if($hf_money!=365 && $hf_money!=30000){
				return Response::show('402','购买金额错误');
				break;
			}
		}else{
			return Response::show('403','未定义的购买金额');
			break;
		}
		$pay_status=0;
		$str=rand(100000,999999);
		$orders_num='hui'.time().$str;
		$sql='insert into db_user_huifei (user_id,hf_money,orders_num) values('.$user_id.','.$hf_money.',"'.$orders_num.'")';
		//echo $sql;exit;
		$query = @mysql_query($sql,$connect);
		if(!$query){
			return Response::show('404','创建订单失败');
		}
		$data=array();
		$data['orders_num']=$orders_num;
		$data['user_id']=$user_id;
		$data['hf_money']=$hf_money;
		$data['pay_status']=0;
		return Response::show('200','创建订单成功',$data);
		break;

	//支付宝支付接口
	case 'update_pay':
		//接收要用的支付信息
		//订单号:
		$out_trade_no = $_REQUEST['orders_num'];//订单id
		$subject = $_REQUEST['orders_num'];//订单名称
		$total_amount = $_REQUEST['price_shiji'];//实付价格
		$body = $_REQUEST['goods_title'];//商品描述
		//在支付接口中返回要用的支付数据
		//$alipayNotify = new AopClient();
		if($total_amount==0){
			//查询出来订单价格
			$sql = "SELECT price_sum,use_jf_currency,use_jf_limit FROM db_goods_orders  WHERE orders_num = ".$out_trade_no."  LIMIT 1";
			//file_put_contents('ss.txt',$sql);
			$query = @mysql_query($sql,$connect);
			$row= @mysql_fetch_assoc($query);
			//$total_amount=$row['price_sum']-$row['use_jf_currency']-$row['use_jf_limit'];
			$total_amount=$row['price_sum'];
		}
		//组装系统参数
		$data = array(
			"app_id"        =>  '2016092801992020' ,//appid
			"version"		=> "1.0",
			"format"		=> "json",
			"method"		=>"alipay.trade.app.pay",
			"timestamp"		=>date("Y-m-d H:i:s",time()),
			"charset"		=>"utf-8",
			"sign_type"     => "RSA", //无需修改
			"notify_url"	=>"http://www.zzumall.com/api2/alipay_notify.php",//回调地址
			"biz_content"	=> json_encode(array(
				"subject" 		=>$subject,//商品名称
				"out_trade_no"	=>$out_trade_no,//商户网站唯一订单号
				"total_amount"	=>$total_amount,//总金额
				"seller_id"		=>"2088421281806684",//支付宝账号
				"product_code"	=>"QUICK_MSECURITY_PAY",
				"timeout_express" =>"60m",
			)),
		);
		$privateKey = file_get_contents(ACCESS);
//		$alipayNotify = new \AopClient();
//		$data['sign'] = $alipayNotify->rsaSign($data);
//		//$data['sign_type']="RSA";//RSA验证签名
//		$data = createLinkstring($data);
		ksort( $data );
		//重新组装参数
		$params = array();
		foreach($data as $key => $value){
			//生成加密的签名参数
			$params[] = $key .'='. rawurlencode($value);
			// 生成未加密的签名参数  用此参数去签名
			$signparams[] = $key .'='. $value;
		}
		//2种参数 都用&符合拼接
		$data = implode('&', $params);
		$signString = implode('&', $signparams);

		$res = openssl_get_privatekey($privateKey);

		openssl_sign($signString, $sign, $res,OPENSSL_ALGO_SHA1);

		openssl_free_key($res);

		$sign = urlencode(base64_encode($sign));
		$data.='&sign='.$sign;
		$result = array(
			'code'=>200,
			'message'=>urlencode('123'),
			'data'=>$data
		);
		echo json_encode($result);
		break;

	//微信支付接口
	case 'wxpay':
		//微信签名
// STEP 0. 账号帐户资料
//更改商户把相关参数后可测试
		$APP_ID="wxc60cf9d8efdbbd50"; //APPID
		$APP_SECRET="2e12d8c57e9a7066b92d3c83c83c1400";//appsecret
//商户号，填写商户对应参数
		$MCH_ID="1396980502";
//商户API密钥，填写相应参数
		$PARTNER_ID="zzuzzu88zzuzzu88zzuzzu88zzuzzu88";
//支付结果回调页面
		$NOTIFY_URL= 'www.zzumall.com/api/wx_notify.php';
		if($_REQUEST['total_fee']==0){
			//查询出来商品价格
			$sql = "SELECT price_sum,use_jf_currency,use_jf_limit FROM db_goods_orders  WHERE orders_num = ".$_REQUEST['out_trade_no']."  LIMIT 1";
			//file_put_contents('ss.txt',$sql);
			$query = @mysql_query($sql,$connect);
			$row= @mysql_fetch_assoc($query);
			//$_REQUEST['total_fee']=$row['price_sum']-$row['use_jf_currency']-$row['use_jf_limit'];
			$_REQUEST['total_fee']=$row['price_sum'];
		}
//STEP 1. 构造一个订单。
		$order=array(
			"body" => $_REQUEST['body'],
			"appid" => $APP_ID,
			"mch_id" => $MCH_ID,
			"nonce_str" => mt_rand(),
			"notify_url" => $NOTIFY_URL,
			"out_trade_no" => $_REQUEST['out_trade_no'],
			"spbill_create_ip" => $_SERVER['REMOTE_ADDR'],
			"total_fee" => ($_REQUEST['total_fee'] *100),//坑！！！这里的最小单位时分，跟支付宝不一样。1就是1分钱。只能是整形。
			"trade_type" => "APP"
		);
		//file_put_contents("wxtest.txt",json_encode($order));
		ksort($order);
		
//STEP 2. 签名
		$sign="";
		foreach ($order as $key => $value) {
			if($value&&$key!="sign"&&$key!="key"){
				$sign.=$key."=".$value."&";
			}
		}
		$sign.="key=".$PARTNER_ID;
		$sign=strtoupper(md5($sign));
//STEP 3. 请求服务器
		$xml="<xml>\n";
		foreach ($order as $key => $value) {
			$xml.="<".$key.">".$value."</".$key.">\n";
		}
		$xml.="<sign>".$sign."</sign>\n";
		$xml.="</xml>";
		$opts = array(
			'http' =>
				array(
					'method' => 'POST',
					'header' => 'Content-type: text/xml',
					'content' => $xml
				),
			"ssl"=>array(
				"verify_peer"=>false,
				"verify_peer_name"=>false,
			)
		);
		$context = stream_context_create($opts);
		$result = file_get_contents('https://api.mch.weixin.qq.com/pay/unifiedorder', false, $context);
		$result = simplexml_load_string($result,null, LIBXML_NOCDATA);
//在此打印出 result 可以看出各项参数是否正确
//使用$result->nonce_str和$result->prepay_id。再次签名返回app可以直接打开的链接。
		$data=array(
			"noncestr"=>"".$result->nonce_str,
			"prepayid"=>"".$result->prepay_id,//上一步请求微信服务器得到nonce_str和prepay_id参数。
			"appid"=>$APP_ID,
			"package"=>"Sign=WXPay",
			"partnerid"=>$MCH_ID,
			"timestamp"=>time(),
		);
		ksort($data);
		$sign="";
		foreach ($data as $key => $value) {
			if($value&&$key!="sign"&&$key!="key"){
				$sign.=$key."=".$value."&";
			}
		}
		$sign.="key=".$PARTNER_ID;
		$sign=strtoupper(md5($sign));
		$data['sign']=$sign;

		return Response::show('200','成功',$data);
		break;


	//银联支付
	case 'yl_pay':
		//file_put_contents('123.txt',1234);
		if($_REQUEST['price_shiji']==0){
			//查询出来订单价格
			$sql = "SELECT price_sum,use_jf_currency,use_jf_limit FROM db_goods_orders  WHERE orders_num = ".$_REQUEST['orders_num']."  LIMIT 1";
			//file_put_contents('ss.txt',$sql);
			$query = @mysql_query($sql,$connect);
			$row= @mysql_fetch_assoc($query);
			//$_REQUEST['price_shiji']=$row['price_sum']-$row['use_jf_currency']-$row['use_jf_limit'];
			$_REQUEST['price_shiji']=$row['price_sum'];
		}
		$params = array(
			'merId' =>'802310054110819',		//商户代码，请改自己的测试商户号，此处默认取demo演示页面传递的参数
			'orderId' => $_REQUEST['orders_num'],	//商户订单号，8-32位数字字母，不能含“-”或“_”，此处默认取demo演示页面传递的参数，可以自行定制规则
			'txnTime' => date("YmdHis"),	//订单发送时间，格式为YYYYMMDDhhmmss，取北京时间，此处默认取demo演示页面传递的参数
			'txnAmt' => $_REQUEST['price_shiji']*100,	//交易金额，单位分，此处默认取demo演示页面传递的参数
// 		'reqReserved' =>'透传信息',        //请求方保留域，透传字段，查询、通知、对账文件中均会原样出现，如有需要请启用并修改自己希望透传的数据
			//以下信息非特殊情况不需要改动
			'version' => '5.0.0',                 //版本号
			'encoding' => 'utf-8',				  //编码方式
			'txnType' => '01',				      //交易类型
			'txnSubType' => '01',				  //交易子类
			'bizType' => '000201',				  //业务类型
			'frontUrl' =>  com\unionpay\acp\sdk\SDK_FRONT_NOTIFY_URL,  //前台通知地址
			'backUrl' => com\unionpay\acp\sdk\SDK_BACK_NOTIFY_URL,	  //后台通知地址
			'signMethod' => '01',	              //签名方法
			'channelType' => '08',	              //渠道类型，07-PC，08-手机
			'accessType' => '0',		          //接入类型
			'currencyCode' => '156',	          //交易币种，境内商户固定156
		);

		com\unionpay\acp\sdk\AcpService::sign ( $params ); // 签名
		$url = com\unionpay\acp\sdk\SDK_App_Request_Url;
		$data = com\unionpay\acp\sdk\AcpService::post( $params, $url);
		if(count($data)<=0) { //没收到200应答的情况
			printResult ( $url, $params, "" );
			return Response::show('400','失败',$data);
		}
		return Response::show('200','成功',$data);
		break;


	case 'liuyan':
	    $_REQUEST['content']=str_replace(' ','',$_REQUEST['content']);
		if(isset($_REQUEST['content']) && $_REQUEST['content']!=''){
			$content=$_REQUEST['content'];
		}else{
			return Response::show('201','留言内容不能为空');
			break;
		}
		if(isset($_REQUEST['mobile'])){
			$mobile=$_REQUEST['mobile'];
		}else{
			$mobile=0;
		}
		if(isset($_REQUEST['app_type'])){
			$app_type=$_REQUEST['app_type'];
		}else{
			$app_type=0;
		}
		if ($HTTP_SERVER_VARS["HTTP_X_FORWARDED_FOR"]){
			$ip = $HTTP_SERVER_VARS["HTTP_X_FORWARDED_FOR"];
		}elseif ($HTTP_SERVER_VARS["HTTP_CLIENT_IP"]){
			$ip = $HTTP_SERVER_VARS["HTTP_CLIENT_IP"];
		}elseif ($HTTP_SERVER_VARS["REMOTE_ADDR"]) {
			$ip = $HTTP_SERVER_VARS["REMOTE_ADDR"];
		}elseif (getenv("HTTP_X_FORWARDED_FOR")){
			$ip = getenv("HTTP_X_FORWARDED_FOR");
		}elseif (getenv("HTTP_CLIENT_IP")){
			$ip = getenv("HTTP_CLIENT_IP");
		}elseif (getenv("REMOTE_ADDR")){
			$ip = getenv("REMOTE_ADDR");
		}else{
			$ip = "Unknown";
		}
		//$ip=$_SERVER["REMOTE_ADDR"];
		//$app_type='pc';
		$create_time=time();
		$sql="insert into db_advise (mobile,content,create_time,ip,app_type,is_show) values('{$mobile}','{$content}','{$create_time}','{$ip}','{$app_type}',1)";
		//echo $sql;exit;
		$query = @mysql_query($sql,$connect);
		if(!$query){
			return Response::show('400','留言失败');
		}
		return Response::show('200','留言成功');
		break;
//消息
	case 'news':
		$user_id = $_REQUEST['id'];//会员id
		$sql="SELECT * FROM `db_news` WHERE `sendto` IN ('0','$user_id') ORDER BY create_time desc ";
		$query = @mysql_query($sql,$connect);
		while($result = @mysql_fetch_assoc($query)){
			$data[]  = $result;
		}
		foreach($data as $k=>$v){
			$data[$k]['create_time']=date("Y-m-d H:i:s",$v['create_time']);
		}
		
		return Response::show('200','消息',$data);
		break;

	default:
		return Response::show('400','没有该请求方法');

}


