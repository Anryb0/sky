<?php
	require 'vendor/autoload.php';
	use Firebase\JWT\JWT;
	use Firebase\JWT\Key;
	if(!isset($_POST['rlogin']) or !isset($_POST['rpass']) or !isset($_POST['remail']) or !isset($_POST['passcheck'])){
		echo json_encode(['success'=>false,'message'=>'Отсутствуют данные']);
		exit;
	}
	$login = htmlspecialchars(trim($_POST['rlogin']), ENT_QUOTES);
	$pass = $_POST['rpass'];
	$passcheck = $_POST['passcheck'];
	$email = $_POST['remail'];
	if($pass!=$passcheck){
		echo json_encode(['success'=>false,'message'=>'Пароли не совпадают']);
		exit;
	}
	if(strlen($pass) < 8){
		echo json_encode(['success'=>false,'message'=>'Пароль слишком короткий. Он должен быть не менее 8 символов.']);
		exit;
	}
	if(strlen($login) < 3){
		echo json_encode(['success'=>false,'message'=>'Логин слишком короткий. Он должен быть не менее 3 символов.']);
		exit;
	}
	if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
		echo json_encode(['success'=>false,'message'=>'Введите корректный e-mail.']);
		exit;
	}
	require 'db.php';
	$stmt = $conn->prepare("SELECT user_id FROM users WHERE login = ? OR email = ?");
	$stmt->bind_param("ss", $login, $email);
	$stmt->execute();
	$result = $stmt->get_result();

	if($result->num_rows > 0){
		echo json_encode(['success'=>false,'message'=>'Логин или Email уже занят']);
		exit;
	}
	$stmt->close();
	$confirmation_token = bin2hex(random_bytes(16));
	$hashedpass = password_hash($pass, PASSWORD_DEFAULT);
	$verification_link = "https://anryb0.ru/sky/api/verify.php?t=$confirmation_token";
	$stmt = $conn->prepare('insert into users(login,password,email,confirmation_token) values(?,?,?,?)');
	$stmt ->bind_param('ssss',$login,$hashedpass,$email,$confirmation_token);
	if (!$stmt->execute()) {
		echo json_encode(['success'=>false,'message'=>'Ошибка регистрации']);
		exit;
	}
	$user_id = $conn->insert_id;
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
	$payload = [
    'iat' => time(), 
    'exp' => time() + (60 * 60 * 24 * 30),
    'data' => [      
        'userId' => $user_id,
        'userName' => $login,
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
	$conn->close();
	exit;
?>