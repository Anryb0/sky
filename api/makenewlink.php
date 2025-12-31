<?php
  require "db.php";
  require 'vendor/autoload.php';
  use Firebase\JWT\JWT;
	use Firebase\JWT\Key;
	header('Content-Type: application/json');
	
  if (!isset($_COOKIE['accessToken'])) {
		echo json_encode(['success' => false,'message'=>'Вы не авторизованы']);
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
			'success' => false,'message'=>'Вы не авторизованы',
			'error'=> $e->getMessage()
		]);
	}
  $stmt = $conn->prepare("select confirmation_token, email, login from users where user_id = ?");
  $stmt->bind_param('i',$user['id']);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  $email = $row['email'];
  $login = $row['login'];
  if($row['confirmation_token'] == null){
    echo json_encode([
			'success' => false,
      'message' => 'Ваша учетная запись уже подтверждена',
			'error'=> $e->getMessage()
		]);
    exit;
  }
  $stmt->close();
	$confirmation_token = bin2hex(random_bytes(16));
	$verification_link = "https://anryb0.ru/sky/api/verify.php?t=$confirmation_token";
	$stmt = $conn->prepare('update users set confirmation_token = ? where user_id = ?');
	$stmt ->bind_param('si',$confirmation_token,$user['id']);
	if (!$stmt->execute()) {
		echo json_encode(['success'=>false,'message'=>'Ошибка отправки']);
		exit;
	}
  $stmt->close();
	$resend = Resend::client($_ENV['API_MAIL']);
	$html = "<h2>Уважаемый $login, </h2><br>
	<p>Спасибо за регистрацию на нашем сайте. Надеюсь, у Вас останутся только положительные эмоции от использования. Пожалуйста, перейдите по ссылке ниже чтобы подтвердить свою личность =)</p><br>
	<p><b>$verification_link</b></p><br><p>На письмо отвечать не нужно. Контакты поддержки можно найти по адресу: https://anryb0.ru/sky/support</p>";

	$resend->emails->send([
	  'from' => $_ENV['MAIL'],
	  'to' => $email,
	  'subject' => 'Подтверждение регистрации в сервисе Sky Host',
	  'html' => $html
	  ]);
	  
	echo json_encode(['success'=>true]);
	$conn->close();
  exit;
?>