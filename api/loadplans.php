<?php
    require 'db.php';
    $stmt = $conn->prepare('select * from plans');
    $stmt->execute();
    $data = [];
    $result = $stmt->get_result();
    while($row=$result->fetch_assoc()){
        $data[] = [
            'plan_id' => $row['plan_id'],
            'name' => $row['name'],
            'cpus' => $row['cpus'],
            'ram' => $row['ram'],
            'drive' => $row['drive'],
            'price' => $row['price']
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
    echo json_encode(['success'=>true,'data'=>$data,'os'=>$os]);
    $conn->close();
    exit;
?>