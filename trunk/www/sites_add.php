<? 
	require("src/prepend.inc.php"); 
	
	$display["title"] = "Applications&nbsp;&raquo;&nbsp;Add / Edit";
	
	if ($_POST) 
	{
		$ZoneControler = new DNSZoneControler();
	    
	    if ($post_ezone && $post_formadded)	
		{
		    if ($_SESSION["uid"] != 0)
    		  $zoneinfo = $db->GetRow("SELECT * FROM zones WHERE zone=? AND clientid='{$_SESSION['uid']}'", array($post_ezone));
    		else
    		  $zoneinfo = $db->GetRow("SELECT * FROM zones WHERE zone=?", array($post_ezone));
    		  
    		if (!$zoneinfo)
		      CoreUtils::Redirect("sites_view.php");
		    
		    $serial = AbstractDNSZone::RaiseSerial($zoneinfo["soa_serial"]);
			
		    $db->Execute("UPDATE zones SET soa_serial='{$serial}' WHERE id='{$zoneinfo['id']}'");
		    
			foreach ((array)$post_zone["records"] as $k=>$v)
			{
				if ($v["rkey"] != '' && $v["rvalue"] != '')
					$db->Execute("UPDATE records SET `rtype`=?, `ttl`=?, `rpriority`=?, `rvalue`=?, `rkey`=? WHERE id='".$k."'", array($v["rtype"], $v["ttl"], $v["rpriority"], $v["rvalue"], $v["rkey"]));
				else
					$db->Execute("DELETE FROM records WHERE id='".$k."'");
			}
			
			foreach ((array)$post_add as $k=>$v)
			{
				if ($v["rkey"] != '' && $v["rvalue"] != '')
					$db->Execute("INSERT INTO records SET zoneid=?, `rtype`=?, `ttl`=?, `rpriority`=?, `rvalue`=?, `rkey`=?", array($zoneinfo["id"], $v["rtype"], $v["ttl"], $v["rpriority"], $v["rvalue"], $v["rkey"]));
			}
													
			if (!$ZoneControler->Update($zoneinfo["id"]))
			    $errmsg = "Cannot update DNS zone. See syslog for details.";
			else 
			{
			    $okmsg = "Zone successfully updated";
			    CoreUtils::Redirect("sites_view.php");
			}
		}
		elseif ($post_domainname && $post_formadded)
		{		
			if ($_SESSION["uid"] == 0)
			 CoreUtils::Redirect("index.php");
		    
		    $status = false;
			$post_hostname = $post_domainname;

		    $records = array();
			$nss = $db->GetAll("SELECT * FROM nameservers");
			foreach ($nss as $ns)
                $records[] = array("rtype" => "NS", "ttl" => 14400, "rvalue" => "{$ns["host"]}.", "rkey" => "{$post_domainname}.", "issystem" => 1);
			 
                
			$instances = $db->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND ami_id=? AND state='Running'", array($post_farmid, $post_ami_id));
    		foreach ($instances as $instance)
    		    $records[] = array("rtype" => "A", "ttl" => CF_DYNAMIC_A_REC_TTL, "rvalue" => $instance["external_ip"], "rkey" => "@", "issystem" => 1);
    		    
    		$records = array_merge($records, $post_add);
    		
    		$db->Execute("insert into zones (`zone`, `soa_owner`, `soa_ttl`, `soa_parent`, `soa_serial`, `soa_refresh`, `soa_retry`, `soa_expire`, `min_ttl`, `dtupdated`, `farmid`, `ami_id`, `clientid`)
			values (?,'{$cfg["def_soa_owner"]}','14400','{$cfg["def_soa_parent"]}',?,'14400','7200','3600000','86400',NOW(), ?, ?, ?)", 
			array($post_domainname, date("Ymd")."01", $post_farmid, $post_ami_id, $_SESSION["uid"]));
			$zoneid = $db->Insert_ID();
			
			$zoneinfo = $db->GetRow("SELECT * FROM zones WHERE id='{$zoneid}'");
    		
			foreach ($records as $k=>$v)
			{
				if ($v["rkey"] != '' && $v["rvalue"] != '')
					$db->Execute("REPLACE INTO records SET zoneid=?, `rtype`=?, `ttl`=?, `rpriority`=?, `rvalue`=?, `rkey`=?, `issystem`=?", array($zoneinfo["id"], $v["rtype"], $v["ttl"], $v["rpriority"], $v["rvalue"], $v["rkey"], $v["issystem"] ? 1 : 0));
			}
				
			if (!$ZoneControler->Update($zoneid))
			{
			    $errmsg = "Cannot add new DNS zone: ".Core::GetLastWarning();
			    
			    $db->Execute("DELETE FROM zones WHERE id='{$zoneid}'");
			    $db->Execute("DELETE FROM records WHERE zoneid='{$zoneid}'");
			}
			else 
			{
			    $okmsg = "Zone successfully added";
			    CoreUtils::Redirect("sites_view.php");

			}
		}
	}
	
	if ($post_ezone || $req_ezone)
	{
		$zone = ($post_ezone) ? $post_ezone : $req_ezone;
		
		if ($_SESSION["uid"] != 0)
		  $zoneinfo = $db->GetRow("SELECT * FROM zones WHERE zone=? AND clientid='{$_SESSION['uid']}'", array($zone));
		else
		  $zoneinfo = $db->GetRow("SELECT * FROM zones WHERE zone=?", array($zone));
		  
		if (!$zoneinfo)
		  CoreUtils::Redirect("sites_view.php");
		
		$records = $db->GetAll("SELECT * FROM records WHERE zoneid='{$zoneinfo["id"]}'");
		
		$display["zone"] = $zoneinfo;
		$display["zone"]["records"] = $records;
		$display["domainname"] = $display["zone"]["zone"];
	}
	else
	{
		$display = array_merge($display, $_POST);
		$display["zone"] = $_POST;
		
		if ($post_domainname)
		{
			$db_chk = $db->GetRow("SELECT * FROM zones WHERE zone='{$post_domainname}'");
			if (!$db_chk["id"])
				$display["domainname"] = $post_domainname;	
			else
			{
				$display["domainname"] = false;
				$errmsg = "DNS zone for this domain already added!";
			}
		}
		
		$display["ami_id"] = $post_ami_id;
		
		if ($req_farmid)
		{
    		if ($_SESSION['uid'] != 0)
                $display["farm"] = $db->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?", array($req_farmid, $_SESSION['uid']));
    		else 
    		    $display["farm"] = $db->GetRow("SELECT * FROM farms WHERE id=?", array($req_farmid));  
    		
    		if (!$display["farm"])
    		{
    		    $errmsg = "Farm not found";
                CoreUtils::Redirect("farms_view.php");
    		}
		}
		else 
		{
		    $template_name = "site_add_step1.tpl";
		    
		    if ($_SESSION['uid'] != 0)
		      $display["farms"] = $db->GetAll("SELECT * FROM farms WHERE clientid=?", $_SESSION['uid']);  
		    else 
		      $display["farms"] = $db->GetAll("SELECT * FROM farms");  
		}
		
		$records = array();
		if (count($display["zone"]["records"]) == 0)
		{
    		$instances = $db->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND ami_id=? AND state='Running'", array($display["farm"]["id"], $post_ami_id));
    		foreach ($instances as $instance)
    		    $records[] = array("rtype" => "A", "ttl" => CF_DYNAMIC_A_REC_TTL, "rvalue" => $instance["external_ip"], "rkey" => "{$display["domainname"]}.", "issystem" => 1);
    		    
            $nss = $db->GetAll("SELECT * FROM nameservers");
            foreach ($nss as $ns)
                $records[] = array("rtype" => "NS", "ttl" => 14400, "rvalue" => "{$ns["host"]}.", "rkey" => "{$display["domainname"]}.", "issystem" => 1);
        }
    		    
		$display["zone"]["records"] = $records;
		
		$display["roles"] = $db->GetAll("SELECT * FROM ami_roles WHERE iscompleted='1' AND ami_id IN (SELECT ami_id FROM farm_amis WHERE farmid=?)", array($req_farmid));
	}
	
	$display["add"] = array(1, 2, 3, 4, 5);
	$display["def_sn"] = date("Ymd")."01";
	$display["ezone"] = ($_GET["ezone"]) ? $_GET["ezone"] : $_POST["ezone"];
	$display["def_soa_owner"] = $cfg["def_soa_owner"];
	$display["def_soa_parent"] = $cfg["def_soa_parent"];

		
	require("src/append.inc.php"); 
?>