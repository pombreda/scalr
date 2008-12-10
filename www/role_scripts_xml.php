<?
    require("src/prepend.inc.php"); 
	
    header('Pragma: private');
	header('Cache-control: private, must-revalidate');
    
	if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) 
  		header("Content-type: application/xhtml+xml"); 
	else 
  		header("Content-type: text/xml");
	
  		
	$tree = new DOMDocument('1.0', 'utf-8');
	$tree->loadXML('<tree id="0"></tree>');
	
	if ($req_farmid)
	{
	    if ($_SESSION["uid"] != 0)
	       $farminfo = $db->GetRow("SELECT id FROM farms WHERE id=? AND clientid=?", array($req_farmid, $_SESSION['uid']));
	    else
	       $farminfo = $db->GetRow("SELECT id FROM farms WHERE id=?", array($req_farmid));
	}
		   
	//
	// Default AMIs
	//				
	
	$filter_sql .= " AND ("; 
		// Show shared roles
		$filter_sql .= " origin='".SCRIPT_ORIGIN_TYPE::SHARED."'";
	
		if ($_SESSION['uid'] != 0)
		{
			// Show custom roles
			$filter_sql .= " OR (origin='".SCRIPT_ORIGIN_TYPE::CUSTOM."' AND clientid='{$_SESSION['uid']}')";
		}
		
		//Show approved contributed roles
		$filter_sql .= " OR (origin='".SCRIPT_ORIGIN_TYPE::USER_CONTRIBUTED."' AND (approval_state='".APPROVAL_STATE::APPROVED."' OR clientid='{$_SESSION['uid']}'))";
	$filter_sql .= ")";
	
    $sql = "select * from scripts WHERE 1=1 {$filter_sql}";
	
    $templates = $db->GetAll($sql);
    
	$events = array(
		EVENT_TYPE::HOST_UP, 
		EVENT_TYPE::HOST_INIT,
		EVENT_TYPE::HOST_DOWN,
		EVENT_TYPE::REBOOT_COMPLETE, 
		EVENT_TYPE::INSTANCE_IP_ADDRESS_CHANGED,
		EVENT_TYPE::NEW_MYSQL_MASTER
	);	
		
	foreach ($events as $event)
	{
	    $eventNode = $tree->createElement("item");
	    $eventNode->setAttribute("text", "On{$event}");
	    $eventNode->setAttribute("id", $event);
		$eventNode->setAttribute("im0", "folderClosed_scripting.gif");
	    $eventNode->setAttribute("im1", "folderOpen_scripting.gif");
	    $eventNode->setAttribute("im2", "folderClosed_scripting.gif");
	    $eventNode->setAttribute("hidecheck", "1");
    	
	    $userData = $tree->createElement("userdata", "1");
	    $userData->setAttribute("name", "isFolder");
	    $eventNode->appendChild($userData);

	    $userData = $tree->createElement("userdata", EVENT_TYPE::GetEventDescription($event));
	    $userData->setAttribute("name", "eventDescription");
	    $eventNode->appendChild($userData);
	    
	    $eventNode->setAttribute("child", "0");
	    
	    foreach ($templates as $template)
	    {
	    	
	    	
	    	$idomNode = $tree->createElement("item");
	        $idomNode->setAttribute("text", $template["name"]);
	        $idomNode->setAttribute("id", "{$event}_{$template["id"]}");
	        
	        if ($template['origin'] == SCRIPT_ORIGIN_TYPE::CUSTOM)
	        {
		        $idomNode->setAttribute("im0", "icon_script_custom.png");
		        $idomNode->setAttribute("im1", "icon_script_custom.png");
		        $idomNode->setAttribute("im2", "icon_script_custom.png");
	        }
	        elseif ($template['origin'] == SCRIPT_ORIGIN_TYPE::USER_CONTRIBUTED)
	        {
	        	$idomNode->setAttribute("im0", "icon_script_contributed.png");
		        $idomNode->setAttribute("im1", "icon_script_contributed.png");
		        $idomNode->setAttribute("im2", "icon_script_contributed.png");
	        }
	        else
	        {
	        	$idomNode->setAttribute("im0", "icon_script.png");
		        $idomNode->setAttribute("im1", "icon_script.png");
		        $idomNode->setAttribute("im2", "icon_script.png");
	        }
	        
	        
	        $idomNode->setAttribute("child", "0");
	        
	        $dbversions = $db->GetAll("SELECT * FROM script_revisions WHERE scriptid=? AND (approval_state=? OR revision IN (SELECT version FROM farm_role_scripts WHERE scriptid=? AND farmid=?))", 
	        	array($template['id'], APPROVAL_STATE::APPROVED, $template['id'], $req_farmid)
	        );
	        $versions = array();
	        foreach ($dbversions as $version)
	        {
	        	$vars = GetCustomVariables($version["script"]);
			    $data = array();
			    foreach ($vars as $var)
			    {
			    	if (!in_array($var, CONFIG::$SCRIPT_BUILTIN_VARIABLES))
			    		$data[$var] = ucwords(str_replace("_", " ", $var));
			    }
			    $data = json_encode($data);
	        	
	        	$versions[] = array("revision" => $version['revision'], "fields" => $data);
	        }
	        
	        $userData = $tree->createElement("userdata", $template["description"]);
		    $userData->setAttribute("name", "description");
		    $idomNode->appendChild($userData);
		    
		    $timeout = ($template['issync'] == 1) ? CONFIG::$SYNCHRONOUS_SCRIPT_TIMEOUT : CONFIG::$ASYNCHRONOUS_SCRIPT_TIMEOUT;
		    
		    $userData = $tree->createElement("userdata", $timeout);
		    $userData->setAttribute("name", "timeout");
		    $idomNode->appendChild($userData);
		    
		    $userData = $tree->createElement("userdata", json_encode($versions));
		    $userData->setAttribute("name", "versions");
		    $idomNode->appendChild($userData);		    
	        
	    	$eventNode->appendChild($idomNode);    
	    }
	    
	    $tree->documentElement->appendChild($eventNode);
	}

	//TODO: Move it to class
	function GetCustomVariables($template)
	{
		preg_match_all("/\%([^\%]+)\%/si", $template, $matches);
		return $matches[1];		
	}
	
    print $tree->saveXML();
?>