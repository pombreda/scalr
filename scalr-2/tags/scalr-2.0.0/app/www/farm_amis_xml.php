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
	    $DBFarm = DBFarm::LoadByID($req_farmid);
	    if ($_SESSION["uid"] != 0 && $_SESSION['uid'] != $DBFarm->ClientID)
	    	throw new Exception("Farm not found");
	    	
	    $used_mysql = $db->GetOne("SELECT role_id FROM farm_roles WHERE role_id IN (SELECT id FROM roles WHERE alias=?) AND farmid=?",
	    	array(ROLE_ALIAS::MYSQL, $DBFarm->ID)
	    );
	    
	    $region = $DBFarm->Region;
	}
	else
		$region = $req_region;

	$platforms = array(SERVER_PLATFORMS::EC2 => 'Amazon EC2', SERVER_PLATFORMS::RDS => 'Amazon RDS');
	
	$top_level_nodes_proto = array(
		'shared'  => array("title" => _("Shared Roles"), "type" => "Shared", "subnodes" => array()),
		'custom'  => array("title" => _("Custom Roles"), "type" => "Custom", "subnodes" => array()),
		'contrib' => array("title" => _("Community Roles"), "type" => "Contributed", "subnodes" => array())
	);
	
	foreach ($top_level_nodes_proto as &$node)
	{
		$node['DOMNode'] = $tree->createElement("item");
		
		$node['DOMNode']->setAttribute("text", $node['title']);
	    $node['DOMNode']->setAttribute("id", "origin_{$node['type']}");
		$node['DOMNode']->setAttribute("im0", "folder{$node['type']}Closed.gif");
	    $node['DOMNode']->setAttribute("im1", "folder{$node['type']}Open.gif");
	    $node['DOMNode']->setAttribute("im2", "folder{$node['type']}Closed.gif");
	    $node['DOMNode']->setAttribute("hidecheck", "1");
	    $node['DOMNode']->setAttribute("open", "1");
	    
	    $userData = $tree->createElement("userdata", "1");
	    $userData->setAttribute("name", "isFolder");
	    $node['DOMNode']->appendChild($userData);   
	    $node['DOMNode']->setAttribute("child", "0");
	}
	
	foreach ($platforms as $platform => $name)
	{
		$args = array();
		
		$top_level_nodes = array();
		foreach ($top_level_nodes_proto as $t => $tlnp)
		{
			$top_level_nodes[$t] = array('title' => $tlnp['title'], 'type' => $tlnp['type'], 'subnodes' => array());
			$top_level_nodes[$t]['DOMNode'] = $tlnp['DOMNode']->cloneNode(); 
			$top_level_nodes[$t]['DOMNode']->setAttribute("id", "{$platform}_origin_{$tlnp['type']}");
		}
		
		$add_open = array();
		
		$platformNode[$platform] = $tree->createElement("item");
			
		$platformNode[$platform]->setAttribute("text", $name);
	    $platformNode[$platform]->setAttribute("id", "platform_{$platform}");
		$platformNode[$platform]->setAttribute("im0", "folderClosed.gif");
	    $platformNode[$platform]->setAttribute("im1", "folderOpen.gif");
	    $platformNode[$platform]->setAttribute("im2", "folderClosed.gif");
	    $platformNode[$platform]->setAttribute("hidecheck", "1");
	    
	    $userData = $tree->createElement("userdata", "1");
	    $userData->setAttribute("name", "isFolder");
	    $platformNode[$platform]->appendChild($userData);   
	    $platformNode[$platform]->setAttribute("child", "1");
		
	    $tree->documentElement->appendChild($platformNode[$platform]);
	    
	    $roles_sql = "SELECT id, roletype, ami_id, approval_state, name, clientid, platform, alias, architecture, description, isstable, region FROM roles WHERE platform=?";
		$args[] = $platform;
		if ($_SESSION['uid'] != 0)
		{
			$roles_sql .= " AND (roletype = ? OR (roletype = ? AND clientid=?))";
			$args[] = ROLE_TYPE::SHARED;
			$args[] = ROLE_TYPE::CUSTOM;
			$args[] = $_SESSION['uid'];
		}
			
		$roles = $db->GetAll($roles_sql, $args);
		
		foreach ($roles as $role)
		{
			if ($DBFarm)
				$used_on_farm = $db->GetOne("SELECT id FROM farm_roles WHERE role_id=? AND farmid=?", array($role['id'], $DBFarm->ID));
				
			if ($role['roletype'] == ROLE_TYPE::SHARED && $role['clientid'] != 0 && $_SESSION['uid'] != 0)
	        {
	        	if ($role['clientid'] != $DBFarm->ClientID  && $role['approval_state'] != APPROVAL_STATE::APPROVED && !$used_on_farm)
	        		continue;
	        }
	    	
	        if (($role['name'] == 'mysql' || $role['name'] == 'mysql64') && (!$used_on_farm || !$DBFarm))
	        	continue;
	        	
	        if (in_array($role['platform'], array(SERVER_PLATFORMS::EC2, SERVER_PLATFORMS::RDS)))
	        {
	        	if ($region != $role['region'])
	        		continue;
	        }
	        
	        $open_parent = false;
	        
	    	$idomNode = $tree->createElement("item");
	        $idomNode->setAttribute("text", $role["name"]);
	        $idomNode->setAttribute("id", "{$role["id"]}");
	        $idomNode->setAttribute("im0", "icon_hardware.gif");
	        $idomNode->setAttribute("im1", "icon_hardware.gif");
	        $idomNode->setAttribute("im2", "icon_hardware.gif");
	        
	        $roletype = ROLE_ALIAS::GetTypeByAlias($role["alias"]);
	        $item_attributes = array("child" => 0);
	        
	        if ($role['id'] == $req_role_id)
	        	$item_attributes["select"] = "1";
	        	
	        if ($role['alias'] == ROLE_ALIAS::MYSQL && $used_mysql && $used_mysql != $role['id'])
	        	$item_attributes["disabled"] = "1";
	        	
	        $user_data = array(
	        	"Arch"			=> $role["architecture"],
	        	"type"			=> $roletype,
	        	"alias"			=> $role["alias"],
	        	"description"	=> htmlspecialchars($role["description"])
	        );
	        
	        if ($role['platform'] == SERVER_PLATFORMS::EC2)
	        {
	        	$user_data['ami_id'] = $role['ami_id'];
	        }
	        
			if ($role['roletype'] == ROLE_TYPE::SHARED)
	        {
	        	if ($role['clientid'] != 0)
	        	{
	        		$author_info = $db->GetRow("SELECT fullname FROM clients WHERE id=?", array($role['clientid']));
	        		$user_data['author'] = ($author_info['fullname']) ? $author_info['fullname'] : _('Scalr user');
	        	}
	        	
	        	$user_data['comments_count'] = $db->GetOne("SELECT COUNT(*) FROM comments WHERE object_type=? AND objectid=? AND isprivate='0'",
	        		array(COMMENTS_OBJECT_TYPE::ROLE, $role['id'])
	        	);
	        }
	        
	        $user_data['platform'] = $platform;
	        
			if ($role["isstable"] != 1)
			{
	        	$user_data['unstable'] = '1';
	        	$item_attributes["unstable"] = 1;
	        }
	        
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
	        	
	        if ($role["roletype"] == ROLE_TYPE::SHARED)
	        {
				if ($role['clientid'] == 0)
		        	$top_level_node = &$top_level_nodes['shared'];
				else
					$top_level_node = &$top_level_nodes['contrib'];
	        }
	        else
	        	$top_level_node = &$top_level_nodes['custom'];
	        	
			if (!$top_level_node['subnodes'][$roletype])
			{
	        	$top_level_node['subnodes'][$roletype] = $tree->createElement("item");
		        $top_level_node['subnodes'][$roletype]->setAttribute("text", $roletype);
		        $top_level_node['subnodes'][$roletype]->setAttribute("id", "{$platform}_{$top_level_node['type']}_{$roletype}_{$role["alias"]}");
		        
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
	    	$platformNode[$platform]->appendChild($node['DOMNode']);
	} 

    $xml = $tree->saveXML(); 
    header("Content-length: ".strlen($xml));
    print $xml;
    exit();
?>