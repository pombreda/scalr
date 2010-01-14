<? 
	require("src/prepend.inc.php"); 
	
	$display["title"] = _("Custom roles&nbsp;&raquo;&nbsp;Clone to another region");
		
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = _("Requested page cannot be viewed from admin account");
		UI::Redirect("index.php");
	}
	
	$roleinfo = $db->GetRow("SELECT * FROM roles WHERE id=?", array($req_id));
	if (!$roleinfo || $roleinfo['roletype'] != ROLE_TYPE::CUSTOM || $roleinfo['clientid'] != $_SESSION['uid'] || $post_cancel)
		UI::Redirect("client_roles_view.php");
	
	$Client = Client::Load($_SESSION['uid']);
		
	$display['current_region'] = $roleinfo['region'];
	$display['new_region'] = ($roleinfo['region'] == AWSRegions::US_EAST_1) ? AWSRegions::EU_WEST_1 : AWSRegions::US_EAST_1;
	$display['name'] = $roleinfo['name'];
	$display['id'] = $roleinfo['id'];
	
	if ($_POST)
	{
		try
		{
			$db->Execute("INSERT INTO roles SET name=?, roletype=?, clientid=?, prototype_iid=?, 
				iscompleted='0', default_minLA=?, default_maxLA=?, alias=?, architecture=?, 
				instance_type=?, dtbuildstarted=NOW(), region=?", 
				array($post_name, ROLE_TYPE::CUSTOM, $_SESSION['uid'], "", 
				$roleinfo['default_minLA'], $roleinfo['default_maxLA'], $roleinfo['alias'], 
				$roleinfo['architecture'], $roleinfo['instance_type'], $display['new_region']
			));
			
			$roleid = $db->Insert_ID();
			
			$db->Execute("INSERT INTO security_rules (id, roleid, rule) SELECT null,'{$roleid}',`rule` WHERE roleid=?", array($roleinfo['id']));
			
			
		}
		catch(Exception $e)
		{
			
		}
	}
	
	require("src/append.inc.php"); 
?>