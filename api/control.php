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
		echo json_encode(['success' => true,'maininfo'=>$maininfo,'resq'=>$resq]);
		exit;
		
	}



?>