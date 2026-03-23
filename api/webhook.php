<?php
require 'db.php';

use YooKassa\Model\Notification\NotificationEventType;
use YooKassa\Model\Notification\NotificationFactory;

$source = file_get_contents('php://input');
$data = json_decode($source, true);

try {
    $factory = new NotificationFactory();
    $notificationObject = $factory->factory($data);
    $responseObject = $notificationObject->getObject();
    
    $paymentId = $responseObject->getId();
    $eventType = $notificationObject->getEvent();

    switch ($eventType) {
        case NotificationEventType::PAYMENT_SUCCEEDED:
            file_put_contents('./error_log.txt', "///////////////////////////" . PHP_EOL, FILE_APPEND);
            file_put_contents('./error_log.txt', 'Request data: ' . json_encode($data) . PHP_EOL, FILE_APPEND);
			
			$stmt = $conn->prepare('select link from payments where payment_id = ?');
			$stmt->bind_param('s', $paymentId);
            $stmt->execute();
			$result = $stmt->get_result();
			$row = $result->fetch_assoc();
			
			if(!$row || $row['link'] == null){
				file_put_contents('./error_log.txt', 'отправили код 200, платеж уже обработан или его нет' . PHP_EOL, FILE_APPEND);
				http_response_code(200);
				exit;
			}
			
            $stmt->close();
		
            $stmt = $conn->prepare('UPDATE payments SET link = NULL WHERE payment_id = ?');
            $stmt->bind_param('s', $paymentId);
            $stmt->execute();
            $stmt->close();
            
            $stmt = $conn->prepare('
                SELECT p.server_id, s.user_id, p.q, h.ip 
                FROM payments p 
                LEFT JOIN servers s ON p.server_id = s.server_id 
                LEFT JOIN hosts h ON h.host_id = s.host 
                WHERE p.payment_id = ?
            ');
            $stmt->bind_param('s', $paymentId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            if (!$row) {
                throw new Exception('Payment not found');
            }
            
            $server = $row['server_id'];
            $user = $row['user_id'];
            $q = $row['q'];
            $host = $row['ip'];
            
            $stmt = $conn->prepare('SELECT ip FROM users WHERE user_id = ?');
            $stmt->bind_param('i', $user);
            $stmt->execute();
            $result = $stmt->get_result();
            $rowUser = $result->fetch_assoc();
            $userip = $rowUser['ip'];
            $stmt->close();

            if ($userip === null) {
                $stmt = $conn->prepare('SELECT IFNULL(MAX(ip), 0) as maxip FROM users');
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $maxip = ($row['maxip'] != 0) ? $row['maxip'] + 1 : 129;
                $stmt->close();
                
                $name = 'skyuser' . $maxip;

                $escapedName = escapeshellarg($name);
                $escapedIp = escapeshellarg($maxip);
                exec("/network/client-vpn-generate.sh $escapedName $escapedIp", $output, $return_var);
                
                if ($return_var === 0) {
                    $stmt = $conn->prepare('UPDATE users SET ip = ? WHERE user_id = ?');
                    $stmt->bind_param('ii', $maxip, $user);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    throw new Exception('Failed to generate VPN for user');
                }
            }
            
            $stmt = $conn->prepare('SELECT IFNULL(MAX(ip), 0) as maxip FROM servers');
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $maxip = ($row['maxip'] != 0) ? $row['maxip'] + 1 : 11;
            $stmt->close();
            
            $status = 'Устанавливается';
            $plainPassword = bin2hex(random_bytes(4));
            
            $stmt = $conn->prepare('UPDATE servers SET ip = ?, status = ?, password = ?, expires_at = DATE_ADD(NOW(), INTERVAL ? MONTH) WHERE server_id = ?');
            $stmt->bind_param('issii', $maxip, $status, $plainPassword, $q, $server);

            file_put_contents('./error_log.txt', "обновление статуса сервера $server, $maxip" . PHP_EOL, FILE_APPEND);
            $stmt->execute();
            $stmt->close();
            
            $name = 'skyserver' . $maxip;
            $escapedName = escapeshellarg($name);
            $escapedIp = escapeshellarg($maxip);
            exec("/network/client-vpn-generate.sh $escapedName $escapedIp", $output, $return_var);
            
            if ($return_var !== 0) {
                throw new Exception('Failed to generate VPN for server');
            }
            
            $stmt = $conn->prepare('SELECT s.name, o.filename FROM servers s LEFT JOIN operating_systems o ON s.os_id = o.os_id WHERE s.server_id = ?');
            $stmt->bind_param('i', $server);
            $stmt->execute();
            $result = $stmt->get_result();
            $basic_server_info = $result->fetch_assoc();
            $stmt->close();
            if (!$basic_server_info) {
                throw new Exception('Server details not found');
            }
            
            for ($i = 1; $i <= 3; $i++) {
                $stmt = $conn->prepare('SELECT q FROM servers_resources WHERE server_id = ? AND resource_id = ?');
                $stmt->bind_param('ii', $server, $i);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                if (!$row) {
                    throw new Exception('Server resources not found for resource_id ' . $i);
                }
                switch ($i) {
                    case 1:
                        $cpus = $row['q'];
                        break;
                    case 2:
                        $ram = $row['q'];
                        break;
                    case 3:
                        $drive = $row['q'];
                        break;
                }
                $stmt->close();
            }
            
            $cmd = "/vms/vm-create.sh " . escapeshellarg($basic_server_info['name']) . " " . escapeshellarg($cpus) . " " . escapeshellarg($ram) . " " . escapeshellarg($drive) . " " . escapeshellarg($basic_server_info['filename']) . " " . escapeshellarg($plainPassword);
            file_put_contents($cmd. PHP_EOL, FILE_APPEND);
            $remote_path = "/network/" . $basic_server_info['name'] . ".ovpn";
            $local_path = "/network/configs/skyserver{$maxip}.ovpn";
			file_put_contents('./error_log.txt', 'remote path:'.$remote_path . PHP_EOL, FILE_APPEND);
            file_put_contents('./error_log.txt', 'local_path:'.$local_path . PHP_EOL, FILE_APPEND);
			
            if (!file_exists($local_path)) {
                throw new Exception("Local VPN config not found: $local_path");
            }
			else{
				file_put_contents('файл есть'. PHP_EOL, FILE_APPEND);
			}
            
            $connection = ssh2_connect('10.8.0.' . $host, 22);
            if (!$connection) {
                throw new Exception('SSH connection failed');
            }
            
            if (ssh2_auth_password($connection, 'anryb0', $_ENV['SERVER_PASS'])) {
				file_put_contents('./error_log.txt', 'подключение по ssh успешно к 10.8.0.'.$host . PHP_EOL, FILE_APPEND);
                if (!ssh2_scp_send($connection, $local_path, $remote_path, 0644)) {
                    throw new Exception('SCP transfer failed');
                }
                
                $stream = ssh2_exec($connection, $cmd);
                stream_set_blocking($stream, true);
                $output = stream_get_contents($stream);
                fclose($stream);
                
            } else {
				file_put_contents('не подключился=('. PHP_EOL, FILE_APPEND);
                throw new Exception('SSH authentication failed');
            }
            
            ssh2_disconnect($connection);
            break;

        case NotificationEventType::PAYMENT_CANCELED:
            $stmt = $conn->prepare('SELECT server_id FROM payments WHERE payment_id = ?');
            $stmt->bind_param('s', $paymentId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $server_id = $row['server_id'];
                $stmt->close();
                
                $stmt = $conn->prepare('DELETE FROM servers WHERE server_id = ?');
                $stmt->bind_param('i', $server_id);
                $stmt->execute();
                $stmt->close();
            } else {
                $stmt->close();
            }
            break;
            
        default:
            break;
    }
	file_put_contents('./error_log.txt', 'отправили код 200' . PHP_EOL, FILE_APPEND);
    http_response_code(200);
    echo json_encode(['status' => 'ok']);

} catch (Exception $e) {
	file_put_contents('./error_log.txt', 'отправили код 400' . PHP_EOL, FILE_APPEND);
    http_response_code(400);
    $error_message = date('Y-m-d H:i:s') . ' : ' . $e->getMessage() . PHP_EOL;
    file_put_contents('./error_log.txt', $error_message, FILE_APPEND);
    
    if (isset($data)) {
        file_put_contents('./error_log.txt', 'Request data: ' . json_encode($data) . PHP_EOL, FILE_APPEND);
    }
}