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
	$stmt = $conn->prepare('select s.name, s.ip, s.status, p.name as pname, o.name as oname from servers s left join plans p on s.plan_id = p.plan_id left join operating_systems o on s.os_id = o.os_id where s.user_id = ?');
	$stmt->bind_param('i',$user['id']);
	$stmt->execute();
	$servers = [];
	$result = $stmt->get_result();
	while($row = $result->fetch_assoc()){
		$servers[] = [
			'name' => $row['name'],
			'ip' => $row['ip'],
			'status' => $row['status'],
			'pname' => $row['pname'],
			'oname' => $row['oname']
		];
	}
	$conn->close();
	echo json_encode(['confirmed'=>$confirmed,'email'=>$email,'ip'=>$ip,'servers'=>$servers]);
?>
