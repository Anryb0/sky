<?php
	require 'db.php';
	$name = $_POST['name'];
	$status = 'Работает';
	$stmt = $conn->prepare('update servers set status = ? where name = ?');
	$stmt->bind_param('ii',$status,$name);
	$stmt->execute();
	$stmt->close();
	$conn->close();
?>