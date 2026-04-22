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
			'name' => $decoded->data->userName,
			'role' => $decoded->data->role
		];
		echo json_encode($user);

	} catch (Exception $e) {
		echo json_encode([
			'authorized' => false,
			'error'=> $e->getMessage()
		]);
	}

?>
