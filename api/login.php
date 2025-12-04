<?php
	require 'vendor/autoload.php';
	if(!isset($_POST['llogin']) or !isset($_POST['lpass'])){
		echo json_encode(['success'=>false,'message'=>'Отсутствуют данные']);
		exit;
	}
?>