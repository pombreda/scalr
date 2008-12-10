<?php

	define('AWS_ACCESS_KEY_ID', '08CMX5VPJ671D0KG6QR2');
	define('AWS_SECRET_ACCESS_KEY', 'VuHbB+LC4EOCHraBSsH+T2D8mdIfQ3hjhkW/cQ6Z');
	
	for ($i=1; $i<$argc; $i++)
	{
		$arg = $argv[$i];
		switch ($arg) {
			case '-q':
			case '--queue':
				$queueName = $argv[$i+1];
				$i++;
				break;
				
			case '-r':
			case '--rem':
				$removeMsg = true;
				break;
		}
	}
	if (!$queueName) {
		die("Queue not set\n");
	}

	$include_path = get_include_path();
	$include_path .= PATH_SEPARATOR . dirname(__FILE__) . '/src/Lib/NET/API';
	set_include_path($include_path);

	require_once 'Amazon/SQS/Client.php';
	$sqsClient = new Amazon_SQS_Client(AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, array('Debug' => true));
	require_once 'src/class.SqsReader.php';
	require_once 'src/class.ScalrMsg.php';
	$reader = new SqsReader($sqsClient, $queueName);
	
	
	try {
		$msg = $reader->peek();
		var_dump($msg);
		if ($msg && $removeMsg) {
			$reader->remove($msg->getReceiptHandle());
		}
	} catch (Amazon_SQS_Exception $e) {
		print $e->getTraceAsString();
	}

?>