<?php 
    require 'db.php';
	$stmt = $conn->prepare('select * from hosts');
	$stmt->execute();
	$hosts = [];
	$result = $stmt->get_result();
	while($row=$result->fetch_assoc()){
        $hosts[] = [
            'host_id' => $row['host_id'],
			'ip' => $row['ip'],
			'name' => $row['name']
        ]; 
    }
	$stmt->close();
	$hosts_avail = [];
	forEach($hosts as $host){
		$avail = False;
		exec("ping -c 1 -W 1 10.8.0." . $host['ip'], $output, $result);
		if ($result == 0)
			$avail = True;
		$hosts_avail[] = [
		    'host_id' => $host['host_id'],
			'ip' => $host['ip'],
			'name' => $host['name'],
			'avail' => $avail
		];
	}
	$conn->close();
	echo json_encode(['success'=>true,'hosts_avail'=>$hosts_avail]);
?>