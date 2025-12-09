<?php
	require 'vendor/autoload.php';
	use Firebase\JWT\JWT;
	use Firebase\JWT\Key;
	require 'db.php';
	header('Content-Type: application/json');

	$allowed = ['http://localhost:5173', 'https://anryb0.ru'];
	if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed)) {
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

		echo json_encode([
			'authorized' => true,
			'id' => $decoded->data->userId,
			'name' => $decoded->data->userName,
			'role' => $decoded->data->role
		]);

	} catch (Exception $e) {
		echo json_encode([
			'authorized' => false,
			'error'=> $e->getMessage()
		]);
	}

?>
