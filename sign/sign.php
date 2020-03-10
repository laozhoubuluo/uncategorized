<?php
if($_POST['list'] ?? 0) {
	$array = array('01' => 'Name');
	$list = explode(',', $_POST['list']);
	foreach ($list as $name) {
		$result = array_search($name, $array);
		if ($result !== false) {
			unset($array[$result]);
		} else {
			coutinue;
		}
	}
	echo "未到：";
	print_r($array);
}
?>
<form action="" method="POST">
  <p>签到名单: <input type="text" name="list" /></p>
  <input type="submit" value="Submit" />
</form>
