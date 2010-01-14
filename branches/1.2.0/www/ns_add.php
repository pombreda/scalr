<? 
	require("src/prepend.inc.php"); 
	
	if ($_SESSION["uid"] != 0)
	   UI::Redirect("index.php");
	   
	$display["title"] = "Nameservers&nbsp;&raquo;&nbsp;Add";
	
	if ($_POST) 
	{
		$Validator = new Validator();
		
		if (!$post_isproxy)
		{
			// Check FTP login
			if (!$Validator->IsAlpha($post_username))
				$err[] = "Username is invalid";
			
			// Check FTP Upload Bandwidth
			if (!$Validator->IsNumeric($post_port))
				$err[] = "Invalid Server port";
		}
		
		// Check hostname
		if (!$post_id)
		{
    		if (!preg_match("/^[A-Za-z0-9]+[A-Za-z0-9-\.]*[A-Za-z0-9]+\.[A-Za-z0-9-\.]+$/", $post_host))
    			$err[] = "Invalid server hostname";
		}
	
	    if (count($err) == 0)
	    {
    		if (!$post_id)
    		{
    			$db->Execute("INSERT INTO nameservers 
    				(host, port, username, password, rndc_path, named_path, namedconf_path, isproxy, isbackup, ipaddress) 
    				values (?,?,?,?,?,?,?,?,?,?)",
                    array(   $post_host, 
                             $post_port, 
                             $post_username, 
                             $Crypto->Encrypt($post_password, $_SESSION['cpwd']), 
                             $post_rndc_path, 
                             $post_named_path, 
                             $post_namedconf_path,
                             $post_isproxy ? 1 : 0,
                             $post_isbackup ? 1 : 0,
                             $post_ipaddress
                         )
    			);
    			     
    			if ($post_isbackup == 0)
    			{
	    			$zones = $db->GetAll("SELECT * FROM zones");
	                if (count($zones) > 0)
	                {
	                    $DNSZoneController = new DNSZoneControler();
	                    
	                    foreach ($zones as $zone)
	                    {
	                        if ($zone['id'])
	                        {
	                            $db->Execute("REPLACE INTO records SET zoneid='{$zone['id']}', 
	                            	rtype='NS', ttl=?, rvalue=?, rkey='@', issystem='1'", 
	                            	array(14400, "{$post_host}.")
	                            );
	                            
	                            if ($zone["status"] != ZONE_STATUS::DELETED && $zone["status"] != ZONE_STATUS::INACTIVE)
	                            {
	                                if (!$DNSZoneController->Update($zone["id"]))
	                                    $Logger->fatal("Cannot add NS record to zone '{$zone['zone']}'", E_ERROR);
	                                else 
	                                    $Logger->info("NS record for instance {$instanceinfo['instance_id']} with host {$post_host} added to zone '{$zone['zone']}'", E_USER_NOTICE);
	                            }
	                        }
	                    }
	                }
    			}
    			     	
    			$okmsg = "Nameserver successfully added";
    		
    			UI::Redirect("ns_view.php");
    			
    		}
    		else
    		{
    			$password = ($post_password != '******') ? "password='".$Crypto->Encrypt($post_password, $_SESSION['cpwd'])."'," : "";
    			
    			$db->Execute("UPDATE nameservers SET port=?, username=?, $password rndc_path=?, 
    				named_path=?, namedconf_path=?, isproxy=?, isbackup=?, ipaddress=? 
    				WHERE id=?",
    				array($post_port, $post_username, $post_rndc_path, $post_named_path, 
    				$post_namedconf_path, ($post_isproxy ? 1 : 0), $post_isbackup ? 1 : 0,
                    $post_ipaddress, $post_id)
    			);
    
    							
    			$okmsg = "Nameserver successfully updated";
    			UI::Redirect("ns_view.php");
    		}
	    }
	}
	
	if ($req_id)
	{
		$id = (int)$req_id;
		$display["ns"] = $db->GetRow("SELECT * FROM nameservers WHERE id='{$id}'");
		$display["id"] = $id;
	}
	else
		$display = array_merge($display, $_POST);
	
	require("src/append.inc.php"); 	
?>