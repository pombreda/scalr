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
	       $farminfo = $db->GetRow("SELECT id, clientid FROM farms WHERE id=? AND clientid=?", array($req_farmid, $_SESSION['uid']));
	    else
	       $farminfo = $db->GetRow("SELECT id, clientid FROM farms WHERE id=?", array($req_farmid));
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
	
	$used_mysql = $db->GetOne("SELECT ami_id FROM farm_amis WHERE ami_id IN (SELECT ami_id FROM ami_roles WHERE alias='mysql') AND farmid=?",
    	array($farminfo['id'])
    );
		
	$top_level_nodes = array(
		'shared'  => array("title" => _("Shared Roles"), "type" => "Shared", "subnodes" => array()),
		'custom'  => array("title" => _("Custom Roles"), "type" => "Custom", "subnodes" => array()),
		'contrib' => array("title" => _("Community Roles"), "type" => "Contributed", "subnodes" => array())
	);
	
	foreach ($top_level_nodes as &$node)
	{
		$node['DOMNode'] = $tree->createElement("item");
		
		$node['DOMNode']->setAttribute("text", $node['title']);
	    $node['DOMNode']->setAttribute("id", $node['type']);
		$node['DOMNode']->setAttribute("im0", "folder{$node['type']}Closed.gif");
	    $node['DOMNode']->setAttribute("im1", "folder{$node['type']}Open.gif");
	    $node['DOMNode']->setAttribute("im2", "folder{$node['type']}Closed.gif");
	    $node['DOMNode']->setAttribute("hidecheck", "1");
	    
	    $userData = $tree->createElement("userdata", "1");
	    $userData->setAttribute("name", "isFolder");
	    $node['DOMNode']->appendChild($userData);   
	    $node['DOMNode']->setAttribute("child", "0");
	}  
	
    
    foreach ((array)$amis as $ami)
    {
    	if ($farminfo)
    		$used_on_farm = $db->GetOne("SELECT id FROM farm_amis WHERE ami_id=? AND farmid=?", array($ami["ami_id"], $farminfo["id"]));
        
    	
    	if ($ami['roletype'] == ROLE_TYPE::SHARED && $ami['clientid'] != 0 && $_SESSION['uid'] != 0)
        {
        	if ($ami['clientid'] != $farminfo['clientid']  && $ami['approval_state'] != APPROVAL_STATE::APPROVED && !$used_on_farm)
        		continue;
        }
    	
    	$open_parent = false;
        
    	$idomNode = $tree->createElement("item");
        $idomNode->setAttribute("text", $ami["name"]);
        $idomNode->setAttribute("id", $ami["ami_id"]);
        $idomNode->setAttribute("im0", "icon_hardware.gif");
        $idomNode->setAttribute("im1", "icon_hardware.gif");
        $idomNode->setAttribute("im2", "icon_hardware.gif");
        
        $roletype = ROLE_ALIAS::GetTypeByAlias($ami["alias"]);
        
        $item_attributes = array("child" => 0);
        
        // Selected role
        if ($ami['ami_id'] == $req_ami_id)
        	$item_attributes["select"] = "1";
        
        // Is mysql already checked disable allanother mysql items
        if ($ami['alias'] == 'mysql' && $used_mysql && $used_mysql != $ami['ami_id'])
        	$item_attributes["disabled"] = "1";
        
        $user_data = array(
        	"Arch"			=> $ami["architecture"],
        	"type"			=> $roletype,
        	"alias"			=> $ami["alias"],
        	"description"	=> htmlspecialchars($ami["description"])
        );

        if ($ami['roletype'] == ROLE_TYPE::SHARED)
        {
        	if ($ami['clientid'] != 0)
        	{
        		$author_info = $db->GetRow("SELECT fullname FROM clients WHERE id=?", array($ami['clientid']));
        		$user_data['author'] = ($author_info['fullname']) ? $author_info['fullname'] : _('Scalr user');
        	}
        	
        	$user_data['comments_count'] = $db->GetOne("SELECT COUNT(*) FROM comments WHERE object_type=? AND objectid=? AND isprivate='0'",
        		array(COMMENTS_OBJECT_TYPE::ROLE, $ami['id'])
        	);
        }
                
        // If role unstable
    	if ($ami["isstable"] != 1)
		{
        	$user_data['unstable'] = '1';
        	$item_attributes["unstable"] = 1;
        }
        
        // Is role used by farm
    	if ($used_on_farm)
        {
        	$item_attributes["checked"] = 1;
			$open_parent = true;
		}
        
        foreach ($user_data as $name => $value)
        {
	        $userData = $tree->createElement("userdata", $value);
	    	$userData->setAttribute("name", $name);
	    	$idomNode->appendChild($userData);
        }
    	        
        foreach ($item_attributes as $name=>$value)
        	$idomNode->setAttribute($name, $value);
        
        // Select top level node
        if ($ami["roletype"] == ROLE_TYPE::SHARED)
        {
			if ($ami['clientid'] == 0)
	        	$top_level_node = &$top_level_nodes['shared'];
			else
				$top_level_node = &$top_level_nodes['contrib'];
        }
        else
        	$top_level_node = &$top_level_nodes['custom'];
        
        // Add item to top level node	
		if (!$top_level_node['subnodes'][$roletype])
		{
        	$top_level_node['subnodes'][$roletype] = $tree->createElement("item");
	        $top_level_node['subnodes'][$roletype]->setAttribute("text", $roletype);
	        $top_level_node['subnodes'][$roletype]->setAttribute("id", "{$top_level_node['type']}_{$roletype}_{$ami["alias"]}");
	        
	        $userData = $tree->createElement("userdata", "1");
		    $userData->setAttribute("name", "isFolder");
		    $top_level_node['subnodes'][$roletype]->appendChild($userData);   
	        
	        $type_image = preg_replace("/[^A-Za-z0-9]+/", "_", strtolower($roletype));
	        
	        $top_level_node['subnodes'][$roletype]->setAttribute("im0", "/images/farm_tree/icons/{$type_image}_closed.gif");
		    $top_level_node['subnodes'][$roletype]->setAttribute("im1", "/images/farm_tree/icons/{$type_image}_open.gif");
		    $top_level_node['subnodes'][$roletype]->setAttribute("im2", "/images/farm_tree/icons/{$type_image}_closed.gif");
			
		    
		    $top_level_node['subnodes'][$roletype]->setAttribute("hidecheck", "1");
		    
	        $top_level_node['DOMNode']->appendChild($top_level_node['subnodes'][$roletype]);
		}
        
		$top_level_node['subnodes'][$roletype]->appendChild($idomNode);
		
		if ($open_parent && !$add_open[$top_level_node['type']])
        	$add_open[] = &$top_level_node['subnodes'][$roletype];
    }
    
    foreach ($add_open as &$elem)
    	$elem->setAttribute("open", 1);
    
    foreach ($top_level_nodes as &$node)
    	$tree->documentElement->appendChild($node['DOMNode']);

    print $tree->saveXML();
?>