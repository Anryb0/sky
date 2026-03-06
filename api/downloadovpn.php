<?php
	$filepath = "/network/" . 'openvpn-connect-3.8.0.4528_signed.msi';
	if (file_exists($filepath)) {
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($filepath));
		readfile($filepath);
		exit;
	} else {
			http_response_code(404);
			echo "Файл еще не готов или его не существует.";
	}

?>