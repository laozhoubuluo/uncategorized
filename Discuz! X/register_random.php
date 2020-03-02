<?php

require './source/class/class_core.php';

$discuz = C::app();
$discuz->cachelist = $cachelist;
$discuz->init();

$i = $_GET['i'] ?? 0;
$num = $_GET['num'] ?? 100000;
$once = min($num-$i, 3000);

for($count=0; $count<$once; $count++) {
	$newusername = random(15);
	$result = register($newusername, '123456', $newusername.'@example.com');
	if ($result !== true) {
		exit($result.$i);
	}
}

$i = $i + $count;

if ($i >= $num) {
	exit('Success!');
} else {
	exit("Loading...... <script>location.href='?i=$i&num=$num';</script>");
}

function register($newusername, $newpassword, $newemail) {
	if(C::t('common_member')->fetch_uid_by_username($newusername) || C::t('common_member_archive')->fetch_uid_by_username($newusername)) {
		return -65535;
	}
	loaducenter();
	$uid = uc_user_register(addslashes($newusername), $newpassword, $newemail);
	if($uid <= 0) {
		return $uid;
	}
	$group = C::t('common_usergroup')->fetch(10);
	$profile = $verifyarr = array();
	loadcache('fields_register');
	$init_arr = explode(',', $_G['setting']['initcredits']);
	$password = md5(random(10));
	C::t('common_member')->insert($uid, $newusername, $password, $newemail, 'Manual Acting', $_GET['newgroupid'], $init_arr, 0);
	return true;
}
