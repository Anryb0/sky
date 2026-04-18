<?php 
	require 'checkauth.php';
	$stmt = $conn->prepare('select ip from hosts');
	$stmt->execute();
	$result = $stmt->get_result();
	$ips[] = [];
	while($row = $result->fetch_assoc()){
		$ips[] = [
			$row['ip']
		] 
	}
	
?>