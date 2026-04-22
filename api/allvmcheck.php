<?php 
	require 'login_check.php'; 
	$hostsavail = $_POST['hostsavail'];
	$servers = json_decode($hostsavail, true);
	$stmt = $conn->prepare("select ip, host, status from servers where user_id = ?");
	$stmt->bind_param('i',$user['id']);
	$stmt->execute();
	$result = $stmt->get_result();
	echo $user['id'];
	$vds = [];
	while($row = $result->fetch_assoc()){
		$vds[] = [
			'ip' => $row['ip'],
			'host' => $row['host'],
			'status' => $row['status']
		];
	}
	foreach($vds as $item){
		$check = true;
		foreach($hostsavail as $host){
			if($host['host_id'] == $item['host'] && $host['avail'] == false){
				$check = false;
			}
		}
		if($check){
			$av = false;
			$ipAddr = escapeshellarg($item['ip']);
			exec("ping -c 1 -W 1 10.8.0." . $ipAddr, $output, $result);
			if ($result == 0)
				$av = true;
			if(!$av and $item['status'] != "Устанавливается"){
				$tmp = "Выключен";
				$stmt = $conn->prepare("update servers set status = ? where ip = ?");
				$stmt->bind_param("si",$tmp,$item['ip']);
				$stmt->execute();
			}
			else if($item['status'] != "Устанавливается"){
				$tmp = "Работает";
				$stmt = $conn->prepare("update servers set status = ? where ip = ?");
				$stmt->bind_param("si",$tmp,$item['ip']);
				$stmt->execute();
			}
		}
		else{
			$tmp = "Выключен";
			$stmt = $conn->prepare("update servers set status = ? where ip = ?");
			$stmt->bind_param("si",$tmp,$item['ip']);
			$stmt->execute();
		}
	}
	echo 'success';
?>