<? 
	require("src/prepend.inc.php"); 
	
	$display["title"] = _("Dashboard");
	
	if ($_SESSION['uid'] == 0)
	{
		$display['clients'] = array(
			'total' 	=> (int)$db->GetOne("SELECT COUNT(*) FROM clients"),
			'active'	=> (int)$db->GetOne("SELECT COUNT(*) FROM clients WHERE isactive='1'"),
			'inactive'	=> (int)$db->GetOne("SELECT COUNT(*) FROM clients WHERE isactive='0'")
		);
		
		$display['farms'] = array(
			'total' 		=> (int)$db->GetOne("SELECT COUNT(*) FROM farms"),
			'running'		=> (int)$db->GetOne("SELECT COUNT(*) FROM farms WHERE status=?", array(FARM_STATUS::RUNNING)),
			'terminated'	=> (int)$db->GetOne("SELECT COUNT(*) FROM farms WHERE status=?", array(FARM_STATUS::TERMINATED))
		);
		
		$display['roles'] = array(
			'total' 		=> (int)$db->GetOne("SELECT COUNT(*) FROM ami_roles WHERE iscompleted='1'"),
			'shared'		=> (int)$db->GetOne("SELECT COUNT(*) FROM ami_roles WHERE iscompleted='1' AND clientid='0' AND roletype=?", array(ROLE_TYPE::SHARED)),
			'custom'		=> (int)$db->GetOne("SELECT COUNT(*) FROM ami_roles WHERE iscompleted='1' AND roletype=?", array(ROLE_TYPE::CUSTOM)),
			'approved' 		=> (int)$db->GetOne("SELECT COUNT(*) FROM ami_roles WHERE iscompleted='1' AND roletype=? AND approval_state=? AND clientid != 0", array(ROLE_TYPE::SHARED, APPROVAL_STATE::APPROVED)),
			'declined' 		=> (int)$db->GetOne("SELECT COUNT(*) FROM ami_roles WHERE iscompleted='1' AND roletype=? AND approval_state=? AND clientid != 0", array(ROLE_TYPE::SHARED, APPROVAL_STATE::DECLINED)),
			'pending' 		=> (int)$db->GetOne("SELECT COUNT(*) FROM ami_roles WHERE iscompleted='1' AND roletype=? AND approval_state=? AND clientid != 0", array(ROLE_TYPE::SHARED, APPROVAL_STATE::PENDING))
		);
		
		$display['scripts'] = array(
			'total'			=> 0,
			'shared'		=> 0,
			'custom'		=> 0,
			'approved'		=> 0,
			'pending'		=> 0,
			'declined'		=> 0
		);
		
		$scripts = $db->GetAll("SELECT * FROM scripts");
		
		foreach ($scripts as $script)
		{
			$display['scripts']['total']++;
			
			switch($script['origin'])
			{
				case SCRIPT_ORIGIN_TYPE::SHARED:
						$display['scripts']['shared']++;
					break;
					
				case SCRIPT_ORIGIN_TYPE::CUSTOM:
						$display['scripts']['custom']++;
					break;
					
				case SCRIPT_ORIGIN_TYPE::USER_CONTRIBUTED:
						
					$display['scripts']['approved'] += $db->GetOne("SELECT COUNT(*) FROM script_revisions WHERE scriptid=? AND approval_state=?",
						array($script['id'], APPROVAL_STATE::APPROVED)
					);
					
					
					$display['scripts']['pending'] += $db->GetOne("SELECT COUNT(*) FROM script_revisions WHERE scriptid=? AND approval_state=?",
						array($script['id'], APPROVAL_STATE::PENDING)
					);
					
					$display['scripts']['declined'] += $db->GetOne("SELECT COUNT(*) FROM script_revisions WHERE scriptid=? AND approval_state=?",
						array($script['id'], APPROVAL_STATE::DECLINED)
					);
					
					break;
			}
		}
	}
	
	require("src/append.inc.php"); 
?>
