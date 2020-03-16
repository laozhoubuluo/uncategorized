<?php

require './source/class/class_core.php';

$discuz = C::app();
$discuz->cachelist = $cachelist;
$discuz->init();

include_once libfile('function/forum');

$i = $_GET['i'] ?? 0;
$num = $_GET['num'] ?? 100000;
$once = min($num-$i, 3000);

for($count=0; $count<$once; $count++) {
	thread();
}

$i = $i + $count;

if ($i >= $num) {
	exit('Success!');
} else {
	exit("Loading...... <script>location.href='?i=$i&num=$num';</script>");
}

function thread() {

	// Based on https://yaseng.org/discuz-attachment-and-posting.html

	$discuz_uid = 1;
	$discuz_user = 'admin';
	$fid = 2;
	$typeid = 0;
	$subject = random(80);
	$message = random(10000);
	$timestamp = $_G['timestamp'];
	$onlineip = $_G['clientip'];
	$ismobile = 4;

	$newthread = array(
		'fid' => $fid,
		'posttableid' => 0,
		'typeid' => $typeid,
		'readperm' => '0',
		'price' => '0',
		'author' => $discuz_user,
		'authorid' => $discuz_uid,
		'subject' => $subject,
		'dateline' => $timestamp,
		'lastpost' => $timestamp,
		'lastposter' => $discuz_user
	);
	$tid = C::t('forum_thread')->insert($newthread, true);

	$subject = addslashes($subject);
	$message = addslashes($message);
	$pid = insertpost(array(
		'fid' => $fid,
		'tid' => $tid,
		'first' => '1',
		'author' => $discuz_user,
		'authorid' => $discuz_uid,
		'subject' => $subject,
		'dateline' => $timestamp,
		'message' => $message,
		'useip' => $_G['clientip']
	));

	DB::query("UPDATE pre_forum_forum SET lastpost='$timestamp', threads=threads+1, posts=posts+1, todayposts=todayposts+1 WHERE fid='$fid'", 'UNBUFFERED');
	DB::query("UPDATE pre_common_member_count SET threads=threads+1 WHERE uid='$discuz_uid'", 'UNBUFFERED');
	DB::query("UPDATE pre_common_member_status SET lastpost='$timestamp' WHERE uid='$discuz_uid'", 'UNBUFFERED');

	return true;

}
