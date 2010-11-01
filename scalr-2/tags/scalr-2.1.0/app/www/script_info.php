<? 
	require("src/prepend.inc.php"); 
	
	$id = (int)$req_id;
	
	$display['title'] = _("Script information");
	
	// Get script information
	$script_info = $db->GetRow("SELECT * FROM scripts WHERE id=?", array($id));
	if (!$script_info)
		UI::Redirect("script_templates.php");
	
	// Check permissions
	if (Scalr_Session::getInstance()->getClientId() != 0)
	{
		if ($script_info['origin'] == SCRIPT_ORIGIN_TYPE::CUSTOM && $script_info['clientid'] != Scalr_Session::getInstance()->getClientId())
			UI::Redirect("script_templates.php");
			
		if ($script_info['origin'] == SCRIPT_ORIGIN_TYPE::USER_CONTRIBUTED && 
			$script_info['clientid'] != Scalr_Session::getInstance()->getClientId() && 
			$script_info['approval_state'] != APPROVAL_STATE::APPROVED
		)
			UI::Redirect("script_templates.php");
	}
		
	if ($script_info['clientid'])
		$script_info['client'] = $db->GetRow("SELECT * FROM clients WHERE id=?", array($script_info['clientid']));
	
	$display["script_info"] = $script_info;
	
	if (Scalr_Session::getInstance()->getClientId() != 0 && Scalr_Session::getInstance()->getClientId() != $script_info['clientid'])
	{
		$dbversions = $db->GetAll("SELECT * FROM script_revisions WHERE scriptid=? AND approval_state=?", 
	        array($script_info['id'], APPROVAL_STATE::APPROVED)
	    );
	}
	else
	{
		$dbversions = $db->GetAll("SELECT * FROM script_revisions WHERE scriptid=?", 
	        array($script_info['id'])
	    );
	}
    
    $display["versions"] = array();
    foreach ($dbversions as $version)
        $display["versions"][] = $version['revision'];
	
    sort($display["versions"]);
        
    $display['selected_version'] = ($req_revision && in_array($req_revision, $display["versions"])) ? $req_revision : max($display["versions"]);
    
    $revision_info = $db->GetRow("SELECT * FROM script_revisions WHERE scriptid=? AND revision=?",
    	array($script_info['id'], $display['selected_version'])
    );
    
    $display["script_info"]['approval_state'] = $revision_info['approval_state'];
    
    $display["content"] = htmlspecialchars($revision_info['script']);

    if ($script_info['origin'] != SCRIPT_ORIGIN_TYPE::CUSTOM)
    {	    
	    $entity_name = COMMENTS_OBJECT_TYPE::SCRIPT;
    	$object_owner = $script_info['clientid'];
	    $redir_link = "script_info.php?id={$script_info['id']}&revision={$display['selected_version']}";
    	$object_name = $script_info['name'];
	    
	    $display["allow_moderation"] = ($script_info['origin'] == SCRIPT_ORIGIN_TYPE::USER_CONTRIBUTED) ? true : false;
	    if ($display["allow_moderation"])
    		$display["approval_state"] = $revision_info['approval_state'];
    		
		$display["comments_enabled"] = true;
	    
	    include ("src/comments.inc.php");
    }
    
	$display["id"] = $id;
	
	require("src/append.inc.php"); 
?>