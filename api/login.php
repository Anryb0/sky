<?php
	require 'vendor/autoload.php';
	use Firebase\JWT\JWT;
	use Firebase\JWT\Key;
	$allowed = ['http://localhost:5173', 'https://anryb0.ru'];
	if (in_array($_SERVER['HTTP_ORIGIN'], $allowed)) {
		header("Access-Control-Allow-Origin: ".$_SERVER['HTTP_ORIGIN']);
	}
	header("Access-Control-Allow-Credentials: true");
	if(!isset($_POST['llogin']) or !isset($_POST['lpass'])){
		echo json_encode(['success'=>false,'message'=>'Отсутствуют данные']);
		exit;
	}
	$login = htmlspecialchars(trim($_POST['llogin']), ENT_QUOTES);
	$pass = $_POST['lpass'];
	require 'db.php';
	$stmt = $conn->prepare("select user_id, password, login from users where login=?");
	$stmt->bind_param("s", $login);
	$stmt->execute();
	$result = $stmt->get_result();

	if($result->num_rows === 0){
		echo json_encode(['success'=>false,'message'=>'Пользователь не найден']);
		exit;
	}

	$user = $result->fetch_assoc();
	if(!password_verify($pass, $user['password'])){
		echo json_encode(['success'=>false,'message'=>'Неверный пароль']);
		exit;
	}
	$payload = [
    'iat' => time(), 
    'exp' => time() + (60 * 60 * 24 * 30),
    'data' => [      
        'userId' => $user['user_id'],
        'userName' => $user['login'],
        'role' => 'user'
		]
	];
	$secretKey = $_ENV['JWT_SECRET'];
	$jwt = JWT::encode($payload, $secretKey, 'HS256');
	$cookie_options = [
		'expires' => time() + (60 * 60 * 24 * 30),
		'path' => '/', 
		'secure' => true, 
		'httponly' => true,
		'sameSite' => 'None'];
	$cookieSet = setcookie('accessToken', $jwt, $cookie_options);
	$cookieR = $cookieSet ? 'Cookie was set successfully' : 'Failed to set cookie';
	echo json_encode(['success'=>true,'cookieR'=>$cookieR]);	
	exit;
?>