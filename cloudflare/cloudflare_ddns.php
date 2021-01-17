<?php

declare(strict_types=1);

// 参数设置
define('API_TOKEN', ''); // 本接口使用的 API Token
define('CLOUDFLARE_API', 'api.cloudflare.com'); // API 域名
define('CLOUDFLARE_TOKEN', ''); // Cloudflare API Token

// GET 参数上传 DOMAIN RECORDTYPE TTL IP TOKEN
if (!empty($_GET['token'])) {
	if (!hash_equals(API_TOKEN, $_GET['token'])) {
		exit("Error: Application Token Invalid");
	}
} else {
	exit("Error: Application Token Not Found.");
}

if (!empty($_GET['ip'])) {
	if (filter_var($_GET['ip'], FILTER_VALIDATE_IP)) {
		$ip = $_GET['ip'];
	} else {
		exit("Error: IP Address Invalid.");
	}
} else {
	exit("Error: IP Address Not Found.");
}

if (!empty($_GET['domain'])) {
	if (filter_var($_GET['domain'], FILTER_VALIDATE_DOMAIN)) {
		$domain = $_GET['domain'];
	} else {
		exit("Error: Domain Invalid.");
	}
} else {
	exit("Error: Domain Not Found.");
}

if (!empty($_GET['recordtype'])) {
	if (in_array($_GET['recordtype'], array('A', 'AAAA'))) {
		$record_type = $_GET['recordtype'];
	} else {
		exit("Error: Record Type A / AAAA Invalid.");
	}
} else {
	exit("Error: Record Type Not Found.");
}

if (!empty($_GET['ttl'])) {
	if ($_GET['ttl'] > 10) {
		$ttl = (int)$_GET['ttl'];
	} else {
		$ttl = 600;
	}
} else {
	$ttl = 600;
}

$dmarr = array_reverse(explode(".", $_GET['domain']));
$tld = $dmarr[1] . '.' . $dmarr[0]; // Todo: 形如 xxx.com.cn 的情况

// 获取 Zone 信息
$zone_req = Request("client/v4/zones?name=$tld&status=active&page=1&per_page=20&order=status&direction=desc&match=all", "GET");
$zone_id = $zone_req->result[0]->id;

// 获取是否存在记录
$record_req = Request("client/v4/zones/$zone_id/dns_records?&name=$domain&page=1&per_page=20&order=type&direction=desc&match=all", "GET");
foreach ($record_req->result as $res) {
	if ($res->type == $record_type) {
		$record_id = $res->id;
		$record_type = $res->type;
		break;
	}
}

// 存在记录则修改记录值，否则新增条目
if (isset($record_id)) {
	$post = "{\"id\":\"$record_id\",\"type\":\"$record_type\",\"name\":\"$domain\",\"content\":\"$ip\",\"data\":{}}";
	$record_req = Request("client/v4/zones/$zone_id/dns_records/$record_id", "PUT", $post);
} else {
	$post = "{\"type\":\"$record_type\",\"name\":\"$domain\",\"content\":\"$ip\",\"data\":{}}";
	$record_req = Request("client/v4/zones/$zone_id/dns_records", "POST", $post);
}

if ($record_req->success) {
	exit("Operation Successful.");
} else {
	exit("Error: Unexcpeted Error.");
}

function Request(string $url, string $request, string $post = '') {

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://' . CLOUDFLARE_API . '/' . $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request);

	if (!empty($post)) {
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	}

	$headers = array();
	$headers[] = "Authorization: Bearer " . CLOUDFLARE_TOKEN;
	$headers[] = "Content-Type: application/json";
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	$result = curl_exec($ch);
	if (curl_errno($ch)) {
		exit('Error: ' . curl_error($ch));
	} elseif (!json_decode($result)->success) {
		echo 'Error: ';
		foreach (json_decode($result, TRUE)['errors'] as $error) {
			echo 'Code: ' . $error['code'] . ', Error: ' . $error['message'] . '<br />';
		}
		exit();
	}
	curl_close($ch);

	return json_decode($result);

}
