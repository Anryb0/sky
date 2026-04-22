<?php
	require 'db.php';
	$host = $_POST['host'];
	exec("ping -c 1 -W 1 10.8.0." . $host, $output, $result);
	if ($result == 0)
		echo true;
	else
		echo false;
?>