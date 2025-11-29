<?php
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;
	if(!isset($_POST['rlogin']) or !isset($_POST['rpass']) or !isset($_POST['remail']) or !isset($_POST['passcheck'])){
		echo json_encode(['success'=>false,'message'=>'Отсутствуют данные']);
		exit;
	}
	$login = htmlspecialchars(trim($_POST['rlogin']), ENT_QUOTES);
	$pass = $_POST['rpass'];
	$passcheck = $_POST['passcheck'];
	$remail = $_POST['remail'];
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
	if(!filter_var($remail, FILTER_VALIDATE_EMAIL)){
		echo json_encode(['success'=>false,'message'=>'Введите корректный e-mail.']);
		exit;
	}
	require 'db.php';
	$stmt = $conn->prepare("SELECT user_id FROM users WHERE login = ? OR email = ?");
	$stmt->bind_param("ss", $login, $remail);
	$stmt->execute();
	$result = $stmt->get_result();

	if($result->num_rows > 0){
		echo json_encode(['success'=>false,'message'=>'Логин или Email уже занят']);
		exit;
	}
	$stmt->close();
	$confirmation_token = bin2hex(random_bytes(16));
	$hashedpass = password_hash($pass, PASSWORD_DEFAULT);
	$verification_link = "https://anryb0.ru/sky/verify.php?t=$confirmation_token";
	$stmt = $conn->prepare('insert into users(login,password,email,confirmation_token) values(?,?,?,?)');
	$stmt ->bind_param('ssss',$login,$hashedpass,$remail,$confirmation_token);
	$stmt->execute();
	$stmt->close();
	require 'vendor/autoload.php';
	$mail = new PHPMailer(true);
	try {
		$mail->isSMTP();
		$mail->Host = $_ENV['SMTP_HOST'];
		$mail->SMTPAuth = true;
		$mail->Username = $_ENV['SMTP_USER']; 	
		$mail->Password = $_ENV['SMTP_PASS'];
		$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
		$mail->Port = $_ENV['SMTP_PORT'];

		$mail->setFrom($_ENV['SMTP_USER'], 'Sky');
		$mail->addAddress($remail);

		$mail->Subject = 'Подтверждение регистрации';
		$mail->Body    = "Перейдите по ссылке для активации аккаунта: $verification_link";

		$mail->send();
		echo json_encode(['success'=>true]);
		$conn->close();
	} catch (Exception $e) {
		echo json_encode(['success'=>афдыу,'message'=>$mail->ErrorInfo]);
		$conn->close();
	}
?>