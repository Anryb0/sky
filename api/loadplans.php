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
    echo json_encode(['success'=>true,'data'=>$data]);
    $stmt->close();
    $conn->close();
    exit;
?>