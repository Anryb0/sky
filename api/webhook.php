<?php
require 'db.php';

// Убираем лишний use NotificationCancelled
use YooKassa\Model\Notification\NotificationSucceeded;
use YooKassa\Model\Notification\NotificationFactory; 
use YooKassa\Model\NotificationEventType;

$source = file_get_contents('php://input');
$requestBody = json_decode($source, true);

try {
    // Используем фабрику, чтобы не гадать с названиями классов
    $factory = new NotificationFactory();
    $notification = $factory->factory($requestBody);
    $payment = $notification->getObject();
    $paymentId = $payment->getId();
    
    $eventType = $requestBody['event'];

    switch ($eventType) {
        case NotificationEventType::PAYMENT_SUCCEEDED:
            // Твой код обработки успешного платежа
            $stmt = $conn->prepare('update payments set link = null where payment_id = ?');
            $stmt->bind_param('s', $paymentId);
            $stmt->execute();
            $stmt->close();
            
            $stmt = $conn->prepare('select p.server_id, s.user_id, p.q from payments p left join servers s on p.server_id = s.server_id where payment_id = ?');
            $stmt->bind_param('s', $paymentId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $server = $row['server_id'];
            $user = $row['user_id'];
            $q = $row['q'];
            $stmt->close();

            $stmt = $conn->prepare('select ip from users where user_id = ?');
            $stmt->bind_param('i',$user);
            $stmt->execute();
            $result=$stmt->get_result();
            $row=$result->fetch_assoc();
            $userip = $row['ip'];
            $stmt->close();

            if($userip == null){
                $maxip = 129;
                $stmt = $conn->prepare('select IFNULL(MAX(ip), 0) as maxip from users');
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                if($row['maxip'] != 0){
                    $maxip = $row['maxip'] + 1;
                }
                $name = 'skyuser'.$maxip;
                $stmt->close();
                exec("/network/client-vpn-generate.sh $name $maxip", $output, $return_var);
                $stmt = $conn->prepare('update users set ip = ? where user_id = ?');
                $stmt->bind_param('ii',$maxip,$user);
                $stmt->execute();
                $stmt->close();
            }

            $maxip = 11;
            $stmt = $conn->prepare('select IFNULL(MAX(ip), 0) as maxip from servers');
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            if($row['maxip'] != 0){
                $maxip = $row['maxip'] + 1;
            }
            $status = 'Устанавливается';
            $key = $_ENV['SERVER_PASS'];
            $cipher = "aes-256-cbc";
            $plainPassword = bin2hex(random_bytes(4));
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));
            $encryptedPassword = openssl_encrypt($plainPassword, $cipher, $key, 0, $iv);
            $stmt = $conn->prepare('update servers set ip = ?, status = ?, password = ?, expires_at = DATE_ADD(NOW(), INTERVAL ? MONTH) where server_id = ?');
            $stmt->bind_param('issii',$maxip,$status,$encryptedPassword,$q,$server);
            $stmt->execute();
            $name = 'skyserver'.$maxip;
            $stmt->close();

            exec("/network/client-vpn-generate.sh $name $maxip", $output, $return_var);
            $stmt=$conn->prepare('select s.name, p.cpus, p.ram, p.drive, o.filename from servers s left join plans p on s.plan_id = p.plan_id left join operating_systems o on s.os_id=o.os_id where s.server_id=?');
            $stmt->bind_param('i',$server);
            $stmt->execute();
            $result=$stmt->get_result();
            $row = $result->fetch_assoc();
            
            $cmd = "/vms/vm-create.sh {$row['name']} {$row['cpus']} {$row['ram']} {$row['drive']} {$row['filename']}";
            $local_path = "/network/configs/skyserver{$maxip}.ovpn";
            $remote_path = "/network/{$row['name']}.ovpn";
            
            $connection = ssh2_connect('10.8.0.2', 22);
            if (ssh2_auth_password($connection, 'anryb0', $_ENV['SERVER_PASS'])) {
                ssh2_scp_send($connection, $local_path, $remote_path, 0644);
                $stream = ssh2_exec($connection, $cmd);
                stream_set_blocking($stream, true);
                $output = stream_get_contents($stream);
            }
            break;

        case NotificationEventType::PAYMENT_CANCELED:
            $stmt = $conn->prepare('select server_id from payments where payment_id = ?');
            $stmt->bind_param('s', $paymentId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $server_id = $row['server_id'];
                $stmt->close();
                $stmt = $conn->prepare('delete from servers where server_id = ?');
                $stmt->bind_param('i', $server_id);
                $stmt->execute();
                $stmt->close();
            }
            break;
    }

    http_response_code(200);

} catch (\Exception $e) {
    http_response_code(400);
    file_put_contents('error_log.txt', date('Y-m-d H:i:s') . ' : ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
}
