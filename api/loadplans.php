<?php
    require 'db.php';
	$stmt = $conn->prepare('select resource_id, name, price, min_value, max_value from resources');
    $stmt->execute();
    $resources = [];
    $result = $stmt->get_result();
	while($row=$result->fetch_assoc()){
        $resources[] = [
            'resource_id' => $row['resource_id'],
            'name' => $row['name'],
			'price' => $row['price'],
			'min_value' => $row['min_value'],
			'max_value' => $row['max_value']
        ]; 
    }
	$stmt->close();
    $stmt = $conn->prepare('select os_id, name from operating_systems');
    $stmt->execute();
    $os = [];
    $result = $stmt->get_result();
    while($row=$result->fetch_assoc()){
        $os[] = [
            'os_id' => $row['os_id'],
            'name' => $row['name'],
        ]; 
    }
	$stmt->close();
    echo json_encode(['success'=>true,'resources'=>$resources,'os'=>$os]);
    $conn->close();
    exit;
?>