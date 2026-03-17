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
  $resources = json_decode($_POST['resources'], true);
  $q = (int)$_POST['q'];
  $os_id = (int)$_POST['os_id'];
  if(!($q > 0 && $q < 21)){
    echo json_encode(['success'=>false,'message'=>'Недопустимый срок аренды']);
    $conn->close();
    exit;
  }
  $stmt = $conn->prepare('select * from resources');
  $stmt->execute();
  $result = $stmt->get_result();
  $resources_prices = [];
  while($row = $result->fetch_assoc()){
	$resources_prices [] = [
		'name' => $row['name'],
		'price' => $row['price'],
		'min_value' => $row['min_value'],
		'max_value' => $row['max_value']
	];
  }
  $stmt->close();
  $price_for_month = 0;
  forEach($resources as $resource){
	  forEach($resources_prices as $resprice){
		  if($resource['name'] == $resprice['name']){
			  if(!($resource['q'] >= $resprice['min_value'] && $resource['q'] <= $resprice['max_value'])){
				echo json_encode(['success'=>false,'message'=>'Недопустимое количество ресурсов']);
				$conn->close();
				exit;
			  }
			  $price_for_month = $price_for_month + ($resource['price']*$resource['q']);
		  }
		  
	  }
  }
  $totalprice = $price_for_month*$q;
  if($totalprice == 0){
	  echo json_encode(['success'=>false,'message'=>$resources]);
	  exit;
  }
  
  $randomSuffix = bin2hex(random_bytes(2));
  $userName = preg_replace("/[^a-zA-Z0-9]/", "", $user['name']);
  $name = $userName.'-'.$randomSuffix;
  $host = $_POST['host'];
  $stmt=$conn->prepare('insert into servers (status, user_id,name,os_id,host) values (?,?,?,?,?)');
  $stmt->bind_param('sisii',$status,$user['id'], $name,$os_id,$host);
  $stmt->execute();
  $server=$conn->insert_id;
  $stmt->close();
    //сохранение информации о кол-ве ресурсов
  forEach($resources as $resource){
	$stmt = $conn->prepare('insert into servers_resources (resource_id, server_id, q) values (?,?,?)');
	$stmt->bind_param('iii',$resource['resource_id'], $server, $resource['q']);
	$stmt->execute();
	$stmt->close();
  }
  $client = new Client();
  $client->setAuth($_ENV['U_ID'], $_ENV['U_SECRET']);
  $payment = $client->createPayment(
        array(
            'amount' => array(
                'value' => $totalprice,
                'currency' => 'RUB',
            ),
            'confirmation' => array(
                'type' => 'redirect',
                'return_url' => 'https://anryb0.ru/sky/profile?createdserver='.$server,
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