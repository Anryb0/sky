<?php
require 'db.php';

use YooKassa\Model\Notification\NotificationSucceeded;
use YooKassa\Model\Notification\NotificationCancelled;
use YooKassa\Model\NotificationEventType;

$source = file_get_contents('php://input');
$requestBody = json_decode($source, true);

try {
    $eventType = $requestBody['event'];

    switch ($eventType) {
        case NotificationEventType::PAYMENT_SUCCEEDED:
            $notification = new NotificationSucceeded($requestBody);
            $payment = $notification->getObject();
            $paymentId = $payment->getId();

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
                exec("/etc/openvpn/client/client-vpn-generate.sh $name $maxip", $output, $return_var);
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
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));
            $encryptedPassword = openssl_encrypt($plainPassword, $cipher, $key, 0, $iv);
            $stmt = $conn->prepare('update servers set ip = ?, status = ?, password = ?, expires_at = DATE_ADD(NOW(), INTERVAL ? MONTH) where server_id = ?');
            $stmt->bind_param('issii',$maxip,$status,$encryptedPassword,$q,$server);
            $stmt->execute();
            $name = 'skyserver'.$maxip;
            $stmt->close();
            exec("/etc/openvpn/client/client-vpn-generate.sh $name $maxip", $output, $return_var);
            $stmt=$conn->prepare('select s.name, p.cpus, p.ram, p.drive, o.filename from servers s left join plans p on s.plan_id = p.plan_id left join operating_systems o on s.os_id=o.os_id where s.server_id=?');
            $stmt->bind_param('i',$server);
            #СЮДАААААААААААААААААА ПИСАТЬ АЛЛЛЛЛЛЛЛЛЛЛЛЛЛЛЛЛЕЕЕЕЕЕЕЕЕЕЕЕЕЕЕЕЕЕ
            $connection = ssh2_connect('10.8.0.2', 22);
            if (ssh2_auth_password($connection, 'anryb0', $_ENV['SERVER_PASS'])) {
                echo "ura!\n";
            }
            break;

        case NotificationEventType::PAYMENT_CANCELED:
            $notification = new NotificationCancelled($requestBody);
            $payment = $notification->getObject();
            $paymentId = $payment->getId();

            $stmt = $conn->prepare('select * from payments where payment_id = ?');
            $stmt->bind_param('s', $paymentId);
            $stmt->execute();

            break;

        default:
            break;
    }

    http_response_code(200);

} catch (\Exception $e) {
    http_response_code(400);
    file_put_contents('error_log.txt', date('Y-m-d H:i:s') . ' : ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
}