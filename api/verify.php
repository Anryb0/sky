<?php
	if(!isset($_GET['t'])){
		echo 'Отсутствуют данные <a href="../">Перейти на главную страницу</a>';
		exit;
	}
	require 'db.php';
	$stmt = $conn->prepare('select user_id from users where confirmation_token = ?');
	$stmt->bind_param('s',$_GET['t']);
	$stmt->execute();
	$result = $stmt->get_result();
	if($result->num_rows == 0){
		$stmt->close();
		$conn->close();
		echo 'Ссылка недействительна. <a href="../">Перейти на главную страницу</a>';
		exit;
	}
	$stmt->close();
	$stmt = $conn->prepare('update users set confirmation_token = NULL where confirmation_token = ?');
	$stmt->bind_param('s',$_GET['t']);
	$stmt->execute();
	echo 'Учетная запись подтверждена. <a href="../">Перейти на главную страницу</a>';
	$stmt->close();
	$conn->close();
	exit;
?>