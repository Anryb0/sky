<?php
$allowed = ['http://localhost:5173', 'https://anryb0.ru'];
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed)) {
    header("Access-Control-Allow-Origin: ".$_SERVER['HTTP_ORIGIN']);
}
header("Access-Control-Allow-Credentials: true");

if (isset($_COOKIE['accessToken'])) {
    setcookie('accessToken', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'None'
    ]);
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false,'message'=>'Вы не авторизованы']);
}
exit;
