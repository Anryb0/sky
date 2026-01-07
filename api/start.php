<?php
  require 'db.php';
  use YooKassa\Client;
  use Firebase\JWT\JWT;
	use Firebase\JWT\Key;
  if (!isset($_COOKIE['accessToken'])) {
		echo json_encode(['success'=>false, 'message' => 'Вы не авторизованы']);
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
			'authorized' => false,
			'error'=> $e->getMessage()
		]);
	}
  $stmt = $conn->prepare('select confirmation_token, email from users where user_id = ?');
  $stmt->bind_param('i', $user['id']);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  if($row['confirmation_token'] != null){
    echo json_encode(['success'=>false,'message'=>'Перед оформлением аренды необходимо подтвердить свою учетную запись. Проверьте email: '.$row['email'].' или перейдите в личный кабинет чтобы отправить новое письмо.']);
    $stmt->close();
    $conn->close();
    exit;
  }
  $stmt->close();
  $status = 'Ждёт оплаты';
  $plan_id = (int)$_POST['plan_id'];
  $q = (int)$_POST['q'];
  $os_id = (int)$_POST['os_id'];
  if(!($q > 0 && $q < 21)){
    exit;
  }
  $stmt = $conn->prepare('select price from plans where plan_id = ?');
  $stmt->bind_param('i',$plan_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  $sum = $row['price']*$q;
  $stmt->close();
  $randomSuffix = bin2hex(random_bytes(2));
  $userName = preg_replace("/[^a-zA-Z0-9]/", "", $user['name']);
  $name = $userName.'-'.$randomSuffix;
  $stmt=$conn->prepare('insert into servers (status, plan_id,user_id,name,os_id) values (?,?,?,?,?)');
  $stmt->bind_param('siisi',$status,$plan_id,$user['id'], $name,$os_id);
  $stmt->execute();
  $server=$conn->insert_id;
  $client = new Client();
  $client->setAuth($_ENV['U_ID'], $_ENV['U_SECRET']);
  $payment = $client->createPayment(
        array(
            'amount' => array(
                'value' => $sum,
                'currency' => 'RUB',
            ),
            'confirmation' => array(
                'type' => 'redirect',
                'return_url' => 'https://anryb0.ru/sky/profile',
            ),
            'capture' => true,
            'description' => 'Заказ №'.$server,
        ),
        uniqid('', true)
    );
  $paymentId = $payment->id;
  $paymentUrl = $payment->getConfirmation()->getConfirmationUrl();
  $stmt=$conn->prepare('insert into payments (payment_id, server_id, q,link) values (?,?,?,?)');
  $stmt->bind_param('siis',$paymentId,$server,$q,$paymentUrl);
  $stmt->execute();
  $stmt->close();
  $conn->close();
  echo json_encode(['success'=>true,'authorized'=>true,'url'=>$paymentUrl]);
?>