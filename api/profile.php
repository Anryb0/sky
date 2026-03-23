<?php
	require 'vendor/autoload.php';
	use Firebase\JWT\JWT;
	use Firebase\JWT\Key;
	require 'db.php';
	if (!isset($_COOKIE['accessToken'])) {
		echo json_encode(['authorized' => false]);
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
	if($mode == 0){
		$stmt = $conn->prepare('select confirmation_token,email,ip from users where user_id = ?');
		$stmt->bind_param('i',$user['id']);
		$stmt->execute();
		$result = $stmt->get_result();
		$row = $result->fetch_assoc();
		$confirmed = true;
		if($row['confirmation_token'] != null){
			$confirmed = false;
		}
		$ip = $row['ip'];
		$email = $row['email'];
		$stmt->close();
		echo json_encode(['success'=>true,'confirmed'=>$confirmed,'email'=>$email,'ip'=>$ip]);
	}
	if($mode == 1){
		$stmt = $conn->prepare('select s.name, s.ip, s.status, o.name as oname, h.name as hname, s.server_id, pl.link from servers s left join operating_systems o on s.os_id = o.os_id left join payments pl on pl.server_id = s.server_id
		left join hosts h on h.host_id = s.host
		where s.user_id = ?');
		$stmt->bind_param('i',$user['id']);
		$stmt->execute();
		$servers = [];
		$result = $stmt->get_result();
		while($row = $result->fetch_assoc()){
			$servers[] = [
				'name' => $row['name'],
				'ip' => $row['ip'],
				'status' => $row['status'],
				'oname' => $row['oname'],
				'link' => $row['link'],
				'hname' => $row['hname'],
				'server_id' => $row['server_id']
			];
		}
		echo json_encode(['success'=>true,'servers'=>$servers]);
	}
	if($mode == 2){
		$serverId = $_POST['serverId'];
		$stmt = $conn->prepare('select name, user_id from servers where server_id = ?');
		$stmt->bind_param('i',$serverId);
		$stmt->execute();
		$result = $stmt->get_result();
		if($result->num_rows == 0){
			echo json_encode(['success'=>false]);
			$conn->close();
			exit();
		}
		$row = $result->fetch_assoc();
		$data = $row;
		$stmt->close();
		if($data['user_id'] != $user['id']){
			echo json_encode(['success'=>false]);
			$conn->close();
			exit();
		}
		else{
			echo json_encode(['success'=>true,'data'=>$data]);
		}
	}
	if($mode == 3){
		$stmt = $conn->prepare('SELECT ip FROM users WHERE user_id = ?');
		$stmt->bind_param('i', $user['id']);
		$stmt->execute();
		$result = $stmt->get_result();
		$row = $result->fetch_assoc();
		$userip = $row['ip'];
		$stmt->close();
		
		if ($userip == null) {
			$stmt = $conn->prepare('SELECT IFNULL(MAX(ip), 0) as maxip FROM users');
			$stmt->execute();
			$result = $stmt->get_result();
			$row = $result->fetch_assoc();
			$maxip = ($row['maxip'] != 0) ? $row['maxip'] + 1 : 129;
			$stmt->close();
			$userip = $maxip;
			$stmt = $conn->prepare('UPDATE users SET ip = ? WHERE user_id = ?');
			$stmt->bind_param('ii', $userip, $user['id']);
			$stmt->execute();
			$stmt->close();
		}
		$name = 'skyuser' . $userip;
        exec("/network/client-vpn-generate.sh $name $userip", $output, $return_var);
		echo json_encode(['success'=>true]);
	}
	$conn->close();
?>

