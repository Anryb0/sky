<?php
	require 'db.php';
	use Firebase\JWT\JWT;
	use Firebase\JWT\Key;
	if (!isset($_COOKIE['accessToken'])) {
		echo json_encode(['success'=> false, 'authorized' => false]);
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

	} catch (Exception $e) {
		echo json_encode([
			'success' => false,
			'authorized' => false,
			'error'=> $e->getMessage()
		]);
	}
	$stmt = $conn->prepare('select ip from users where user_id = ?');
	$stmt->bind_param('i',$user['id']);
	$stmt->execute();
	$result = $stmt->get_result();
	$row = $result->fetch_assoc();
	$filename='skyuser'.$row['ip'].'.ovpn';
	$filepath = "/network/configs/" . $filename;
	if (file_exists($filepath)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/x-openvpn-profile');
    header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filepath));
    readfile($filepath);
    exit;
	} else {
			http_response_code(404);
			echo "Файл еще не готов или его не существует.";
	}
?>