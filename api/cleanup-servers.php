<?php 
	require 'db.php';
	$stmt = $conn->prepare("select * from servers where expires_at <= now()");
	$stmt->execute();
	$result = $stmt->get_result();
	$expired_servers = [];
	while($row = $result->fetch_assoc()){
		$expired_servers[] = [
			'name' => $row['name'],
			'host' => $row['host'],
			'server_id' => $row['server_id']
		];
	}
	if($result->num_rows > 0){
		foreach($expired_servers as $item){
			$connection = ssh2_connect('10.8.0.' . ($item['host'] + 1), 22);
			if (!$connection) {
					throw new Exception('SSH connection failed');
				}    
			if (ssh2_auth_password($connection, 'anryb0', $_ENV['SERVER_PASS'])) {
				$cmd = "/vms/vm-remove.sh " . escapeshellarg($item['name']);
				$stream = ssh2_exec($connection, $cmd);
				stream_set_blocking($stream, true);
				$output = stream_get_contents($stream);
				fclose($stream);
				$stmt2 = $conn->prepare("delete from servers where server_id = ?");
				$stmt2->bind_param('i', $item['server_id']);
				$stmt2->execute();
			}
			echo 'done';
		}
	}
?>