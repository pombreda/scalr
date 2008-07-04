<?php

	require_once("src/prepend.inc.php");
	
	$cpwd = $Crypto->Decrypt(@file_get_contents(dirname(__FILE__)."/../etc/.passwd"));
	
	foreach((array)$db->GetAll("SELECT * FROM nameservers WHERE host='ns2.scalr.net'") as $ns)
	{
		if ($ns["host"]!='')
		{
		   $Bind = new RemoteBIND($ns["host"], 
									$ns["port"],
									array("type" => "password", "login" => $ns["username"], "password" => $Crypto->Decrypt($ns["password"], $cpwd)),
									$ns["rndc_path"],
									$ns["namedconf_path"],
									$ns["named_path"], 
									CONFIG::$NAMEDCONFTPL
							  );
							  
			print "All OK on: {$ns["host"]}<br>";
			flush();
		}
	}
		
?>