<?php
	require 'vendor/autoload.php';
	use Firebase\JWT\JWT;
	use Firebase\JWT\Key;
	require 'db.php';
	if (!isset($_COOKIE['accessToken']) || !isset($_POST['mode']) || !isset($_POST['server_id'])) {
		echo json_encode(['success' => false,'message'=>'Нет корректных данных']);
		exit;
	}
	$jwt = $_COOKIE['accessToken'];
	$secretKey = $_ENV['JWT_SECRET'];

	try {
		$decoded = JWT::decode($jwt, new Key($secretKey, 'HS256'));

		$user = [
			'authorized' => true,
			'id' => $decoded->data->userId,
		];

	} catch (Exception $e) {
		echo json_encode([
			'authorized' => false,
			'error'=> $e->getMessage()
		]);
		exit;
	}
	$mode = $_POST['mode'];
	$server_id = $_POST['server_id'];
	if($mode == 0){
		$stmt = $conn->prepare("select * from servers where server_id = ?");
		$stmt->bind_param('i', $server_id);
		$stmt->execute();
		$result = $stmt->get_result();
		$row = $result->fetch_assoc();
		if(!$row){
			echo json_encode(['success' => false,'message'=>'Сервер не найден']);
			$stmt->close();
			$conn->close();
			exit;
		}
		if ($row['user_id'] != $user['id']){
			echo json_encode(['success' => false,'message'=>'Доступ запрещен']);
			$stmt->close();
			$conn->close();
			exit;
		}
		$maininfo = $row;
		$stmt->close();
		
		$hostavail = false;
		exec("ping -c 1 -W 1 10.8.0." . $row['host'] + 1, $output, $res);
		if($res == 0){
			$hostavail = true;
		}
		$avail = false;
		if($hostavail){
			exec("ping -c 1 -W 1 10.8.0." . $row['ip'], $output, $res);
			if($res == 0){
				$avail = true;
				$tmp = "Работает";
				$stmt = $conn->prepare("update servers set status = ? where server_id = ?");
				$stmt->bind_param('si', $tmp, $server_id);
				$stmt->execute();
				$maininfo['status'] = $tmp;
			}
		}
		if(!$avail && $maininfo['status'] != "Устанавливается"){
			$tmp = "Выключен";
			$stmt = $conn->prepare("update servers set status = ? where server_id = ?");
			$stmt->bind_param('si',$tmp, $server_id);
			$stmt->execute();
			$maininfo['status'] = $tmp;
		}
		$resq = [];
		$stmt = $conn->prepare("select sr.q, r.name from servers_resources sr
		left join resources r on r.resource_id = sr.resource_id 
		where sr.server_id = ?");
		$stmt->bind_param('i', $server_id);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row = $result->fetch_assoc()){
			$resq[] = [
				'name' => $row['name'],
				'q' => $row['q'],
			];
		}
		$stmt->close();
		$conn->close();
		echo json_encode(['success' => true,'maininfo'=>$maininfo,'resq'=>$resq, 'hostavail'=>$hostavail,'avail'=>$avail]);
		exit;		
	}
	if($mode == 1){
		$stmt = $conn->prepare("select user_id, name, host from servers where server_id = ?");
		$stmt->bind_param('i', $server_id);
		$stmt->execute();
		$result = $stmt->get_result();
		$row = $result->fetch_assoc();
		if ($row['user_id'] != $user['id']){
			echo json_encode(['success' => false,'message'=>'Доступ запрещен']);
			$stmt->close();
			$conn->close();
			exit;
		}
		$connection = ssh2_connect('10.8.0.' . ($row['host'] +  1), 22);
		if (!$connection) {
                throw new Exception('SSH connection failed');
            }    
        if (ssh2_auth_password($connection, 'anryb0', $_ENV['SERVER_PASS'])) {
			$cmd = "/vms/vm-start.sh " . escapeshellarg($row['name']);
			$stream = ssh2_exec($connection, $cmd);
			stream_set_blocking($stream, true);
			$output = stream_get_contents($stream);
			fclose($stream);
		}
		echo 'done';
	}
	if($mode == 2){
		$stmt = $conn->prepare("select user_id, name, host from servers where server_id = ?");
		$stmt->bind_param('i', $server_id);
		$stmt->execute();
		$result = $stmt->get_result();
		$row = $result->fetch_assoc();
		if ($row['user_id'] != $user['id']){
			echo json_encode(['success' => false,'message'=>'Доступ запрещен']);
			$stmt->close();
			$conn->close();
			exit;
		}
		$connection = ssh2_connect('10.8.0.' . ($row['host'] + 1), 22);
		if (!$connection) {
                throw new Exception('SSH connection failed');
            }    
        if (ssh2_auth_password($connection, 'anryb0', $_ENV['SERVER_PASS'])) {
			$cmd = "/vms/vm-stop.sh " . escapeshellarg($row['name']);
			$stream = ssh2_exec($connection, $cmd);
			stream_set_blocking($stream, true);
			$output = stream_get_contents($stream);
			fclose($stream);
			$tmp = "Выключен"; 
			$stmt = $conn->prepare("update servers set status = ? where server_id = ?");
			$stmt->bind_param('si', $tmp, $server_id);
			$stmt->execute();
		}
		echo 'done';
	}
	if($mode == 3){
		$stmt = $conn->prepare("select user_id, name, host from servers where server_id = ?");
		$stmt->bind_param('i', $server_id);
		$stmt->execute();
		$result = $stmt->get_result();
		$row = $result->fetch_assoc();
		if ($row['user_id'] != $user['id']){
			echo json_encode(['success' => false,'message'=>'Доступ запрещен']);
			$stmt->close();
			$conn->close();
			exit;
		}
		$connection = ssh2_connect('10.8.0.' . ($row['host'] + 1), 22);
		if (!$connection) {
                throw new Exception('SSH connection failed');
            }    
        if (ssh2_auth_password($connection, 'anryb0', $_ENV['SERVER_PASS'])) {
			$cmd = "/vms/vm-remove.sh " . escapeshellarg($row['name']);
			$stream = ssh2_exec($connection, $cmd);
			stream_set_blocking($stream, true);
			$output = stream_get_contents($stream);
			fclose($stream);
			$stmt2 = $conn->prepare("delete from servers where server_id = ?");
			$stmt2->bind_param('i', $server_id);
			$stmt2->execute();
		}
		echo 'done';
	}
?>