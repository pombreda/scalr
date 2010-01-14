<?php
    	
	require_once (dirname(__FILE__) . "/../../src/class.XmlMenu.php");
	$Menu = new XmlMenu();
    
    if ($_SESSION["uid"] == 0)
    	$Menu->LoadFromFile(dirname(__FILE__)."/../../etc/admin_nav.xml");
    else
    	$Menu->LoadFromFile(dirname(__FILE__)."/../../etc/client_nav.xml");
    	
    $display["menuitems"] = json_encode($Menu->GetExtJSMenuItems());
    
?>