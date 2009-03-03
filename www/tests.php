<?php
	//require_once("../src/Tests/index.php");
	
	//preg_match("/([0-9]{2,3}-[0-9]{1,3}-[0-9]{1,3}-[0-9]{1,3})/si", "ec2-79-125-50-102.eu-west-1.compute.amazonaws.com", $matches);
	//$ip = str_replace("-", ".", $matches[1]);

	//var_dump($_SERVER)
	
	$tes = "oodusan@ghotek.com.ng:password@mxhub.ghotek.com:25";
	preg_match_all("/(.+):(.*)@([^:]+):?([0-9]+)?/", $tes, $matches);
	
	var_dump($matches);
	
?>