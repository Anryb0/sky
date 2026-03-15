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
            $stmt = $conn->prepare('UPDATE payments SET link = NULL WHERE payment_id = ?');
            $stmt->bind_param('s', $paymentId);
            $stmt->execute();
            $stmt->close();
            
            $stmt = $conn->prepare('SELECT p.server_id, s.user_id, p.q, h.ip FROM payments p LEFT JOIN servers s ON p.server_id = s.server_id left join hosts h on h.host_id = s.hosts WHERE p.payment_id = ?');
            $stmt->bind_param('s', $paymentId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if (!$row) {
                throw new Exception('Payment not found');
            }
            
            $server = $row['server_id'];
            $user = $row['user_id'];
            $q = $row['q'];
			$host = $row['ip'];
            $stmt->close();

            $stmt = $conn->prepare('SELECT ip FROM users WHERE user_id = ?');
            $stmt->bind_param('i', $user);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $userip = $row['ip'];
            $stmt->close();

            if ($userip == null) {
                $stmt = $conn->prepare('SELECT IFNULL(MAX(ip), 0) as maxip FROM users');
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $maxip = ($row['maxip'] != 0) ? $row['maxip'] + 1 : 129;
                $stmt->close();
                
                $name = 'skyuser' . $maxip;
                exec("/network/client-vpn-generate.sh $name $maxip", $output, $return_var);
                
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
            $key = $_ENV['SERVER_PASS'];
            $cipher = "aes-256-cbc";
            $plainPassword = bin2hex(random_bytes(4));
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));
            $encryptedPassword = openssl_encrypt($plainPassword, $cipher, $key, 0, $iv);
            
            $stmt = $conn->prepare('UPDATE servers SET ip = ?, status = ?, password = ?, expires_at = DATE_ADD(NOW(), INTERVAL ? MONTH) WHERE server_id = ?');
            $stmt->bind_param('issii', $maxip, $status, $encryptedPassword, $q, $server);
            $stmt->execute();
            $stmt->close();
            
            $name = 'skyserver' . $maxip;
            exec("/network/client-vpn-generate.sh $name $maxip", $output, $return_var);
            
            if ($return_var !== 0) {
                throw new Exception('Failed to generate VPN for server');
            }

            $stmt = $conn->prepare('SELECT s.name, p.cpus, p.ram, p.drive, o.filename FROM servers s LEFT JOIN plans p ON s.plan_id = p.plan_id LEFT JOIN operating_systems o ON s.os_id = o.os_id WHERE s.server_id = ?');
            $stmt->bind_param('i', $server);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            if (!$row) {
                throw new Exception('Server details not found');
            }
            
            $cmd = "/vms/vm-create.sh {$row['name']} {$row['cpus']} {$row['ram']} {$row['drive']} {$row['filename']}";
            $local_path = "/network/configs/skyserver{$maxip}.ovpn";
            $remote_path = "/network/{$row['name']}.ovpn";
            
			
			
            $connection = ssh2_connect('10.8.0.'.$host, 22);
            if (!$connection) {
                throw new Exception('SSH connection failed');
            }
            
            if (ssh2_auth_password($connection, 'anryb0', $_ENV['SERVER_PASS'])) {
                if (!ssh2_scp_send($connection, $local_path, $remote_path, 0644)) {
                    throw new Exception('SCP transfer failed');
                }
                
                $stream = ssh2_exec($connection, $cmd);
                stream_set_blocking($stream, true);
                $output = stream_get_contents($stream);
                fclose($stream);
            } else {
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

    http_response_code(200);
    echo json_encode(['status' => 'ok']);

} catch (Exception $e) {
    http_response_code(400);
    $error_message = date('Y-m-d H:i:s') . ' : ' . $e->getMessage() . PHP_EOL;
    file_put_contents('error_log.txt', $error_message, FILE_APPEND);
    
    if (isset($data)) {
        file_put_contents('error_log.txt', 'Request data: ' . json_encode($data) . PHP_EOL, FILE_APPEND);
    }
}