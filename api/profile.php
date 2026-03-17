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
		$stmt = $conn->prepare('select s.name, s.ip, s.status, o.name as oname, pl.link from servers s left join operating_systems o on s.os_id = o.os_id left join payments pl on pl.server_id = s.server_id where s.user_id = ?');
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
				'link' => $row['link']
			];
		}
		echo json_encode(['success'=>true,'servers'=>$servers]);
	}
	$conn->close();
?>

