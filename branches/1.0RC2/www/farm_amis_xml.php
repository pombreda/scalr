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
	if ($_SESSION['uid'] != 0)
		$amis = $db->GetAll("SELECT * FROM ami_roles WHERE iscompleted='1' AND (roletype = ? OR (roletype = ? AND clientid=?))", 
			array(ROLE_TYPE::SHARED, ROLE_TYPE::CUSTOM, $_SESSION['uid'])
		);
	else
		$amis = $db->GetAll("SELECT * FROM ami_roles WHERE iscompleted='1'");
	
    $sharedNode = $tree->createElement("item");
    $sharedNode->setAttribute("text", "Shared Roles (Stable)");
    $sharedNode->setAttribute("id", "default");
	$sharedNode->setAttribute("im0", "folderClosed.gif");
    $sharedNode->setAttribute("im1", "folderOpen.gif");
    $sharedNode->setAttribute("im2", "folderClosed.gif");
    $sharedNode->setAttribute("hidecheck", "1");
    
    $userData = $tree->createElement("userdata", "1");
    $userData->setAttribute("name", "isFolder");
    $sharedNode->appendChild($userData);   
    $sharedNode->setAttribute("child", "0");
    
    $used_mysql = $db->GetOne("SELECT ami_id FROM farm_amis WHERE ami_id IN (SELECT ami_id FROM ami_roles WHERE alias='mysql') AND farmid=?",
    	array($farminfo['id'])
    );
    
    $sharedNodeUnstable = $tree->createElement("item");
    $sharedNodeUnstable->setAttribute("text", "Shared Roles (Unstable)");
    $sharedNodeUnstable->setAttribute("id", "default2");
	$sharedNodeUnstable->setAttribute("im0", "folderClosed.gif");
    $sharedNodeUnstable->setAttribute("im1", "folderOpen.gif");
    $sharedNodeUnstable->setAttribute("im2", "folderClosed.gif");
    $sharedNodeUnstable->setAttribute("hidecheck", "1");
    
    $userData = $tree->createElement("userdata", "1");
    $userData->setAttribute("name", "isFolder");
    $sharedNodeUnstable->appendChild($userData);   
    $sharedNodeUnstable->setAttribute("child", "0");
    
    
    
    $customNode = $tree->createElement("item");
    $customNode->setAttribute("text", "Custom Roles");
    $customNode->setAttribute("id", "custom");
	$customNode->setAttribute("im0", "folderClosed.gif");
    $customNode->setAttribute("im1", "folderOpen.gif");
    $customNode->setAttribute("im2", "folderClosed.gif");
    $customNode->setAttribute("hidecheck", "1");
    
    $userData = $tree->createElement("userdata", "1");
    $userData->setAttribute("name", "isFolder");
    $customNode->appendChild($userData);   
    $customNode->setAttribute("child", "0");
    
    
    foreach ((array)$amis as $ami)
    {
        $open_parent = false;
        
    	$idomNode = $tree->createElement("item");
        $idomNode->setAttribute("text", $ami["name"]);
        $idomNode->setAttribute("id", $ami["ami_id"]);
        $idomNode->setAttribute("im0", "icon_hardware.gif");
        $idomNode->setAttribute("im1", "icon_hardware.gif");
        $idomNode->setAttribute("im2", "icon_hardware.gif");
        
        
        if ($ami['alias'] == 'mysql' && $used_mysql && $used_mysql != $ami['ami_id'])
        	$idomNode->setAttribute("disabled", 1);
        
        $userData = $tree->createElement("userdata", $ami["architecture"]);
    	$userData->setAttribute("name", "Arch");
    	$idomNode->appendChild($userData);
        
    	$userData = $tree->createElement("userdata", $ami["alias"]);
    	$userData->setAttribute("name", "alias");
    	$idomNode->appendChild($userData);
    	
    	$userData = $tree->createElement("userdata", str_replace(array("&", "<", ">"), array("&amp;", "&lt;", "&gt;"), $ami["description"]));
    	$userData->setAttribute("name", "description");
    	$idomNode->appendChild($userData);
    	
        if ($farminfo)
        {
            if ($db->GetOne("SELECT id FROM farm_amis WHERE ami_id=? AND farmid=?", array($ami["ami_id"], $farminfo["id"])))
            {
               $idomNode->setAttribute("checked", 1);
               $open_parent = true;
            }
        }
        
        $idomNode->setAttribute("child", "0");
        
        if ($ami["roletype"] == ROLE_TYPE::SHARED)
        {
			if ($ami["isstable"] == 1)
			{
        		$sharedNode->appendChild($idomNode);
        		if ($open_parent && !$add_open['SHARED'])
        			$add_open['SHARED'] = &$sharedNode; 
			}
        	else
        	{
        		$sharedNodeUnstable->appendChild($idomNode);
        		if ($open_parent && !$add_open['UNSTABLE'])
        			$add_open['UNSTABLE'] = &$sharedNodeUnstable;
        	}
        }
        else
        { 
            $customNode->appendChild($idomNode);
            if ($open_parent && !$add_open['CUSTOM'])
				$add_open['CUSTOM'] = &$customNode;
        }
    }
    
    foreach ($add_open as &$elem)
    	$elem->setAttribute("open", 1);
    
    $tree->documentElement->appendChild($sharedNode);
    $tree->documentElement->appendChild($sharedNodeUnstable);
    $tree->documentElement->appendChild($customNode);


    print $tree->saveXML();
?>