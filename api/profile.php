<?php
	require 'vendor/autoload.php';
	use Firebase\JWT\JWT;
	use Firebase\JWT\Key;
	require 'db.php';
	$allowed = ['http://localhost:5173', 'https://anryb0.ru'];
	if (in_array($_SERVER['HTTP_ORIGIN'], $allowed)) {
		header("Access-Control-Allow-Origin: ".$_SERVER['HTTP_ORIGIN']);
	}
	header("Access-Control-Allow-Credentials: true");
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
	$stmt = $conn->prepare('select confirmation_token,email from users where user_id = ?');
	$stmt->bind_param('i',$user['id']);
	$stmt->execute();
	$result = $stmt->get_result();
	$row = $result->fetch_assoc();
	$confirmed = true;
	if($row['confirmation_token'] != null){
		$confirmed = false;
	}
	$email = $row['email'];
	$stmt->close();
	$conn->close();
	echo json_encode(['confirmed'=>$confirmed,'email'=>$email]);
?>
