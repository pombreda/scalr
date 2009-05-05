<? 
	require("src/prepend.inc.php"); 
	
	$display["title"] = _("Script templates");
	$display["help"] = _("Scalr can execute scripts on instances upon various events. Your script templates along with parameters, will appear on farm/role configuration page under Scripting tab.");
	
	if (isset($post_cancel))
		UI::Redirect("script_templates.php");
	
	if ($req_task == 'fork')
	{
		// Get template infor from database
		$template = $db->GetRow("SELECT * FROM scripts WHERE id=?", array($req_id));
		
		if ($template['origin'] == SCRIPT_ORIGIN_TYPE::USER_CONTRIBUTED || $template['origin'] == SCRIPT_ORIGIN_TYPE::SHARED)
		{
			$script = $db->GetRow("SELECT * FROM script_revisions WHERE scriptid=? AND approval_state=? ORDER BY revision DESC", 
				array($template['id'], APPROVAL_STATE::APPROVED)
			);
			
			$chk = $db->GetOne("SELECT id FROM scripts WHERE name=? AND clientid=?", array($template['name'], $_SESSION['uid']));
			if ($chk)
				$name = "{$template['name']} #".rand(1000, 9999);
			else
				$name = $template['name'];
			
			if ($script)
			{
				$db->Execute("INSERT INTO scripts SET
					name = ?,
					description = ?,
					origin = ?,
					dtadded = NOW(),
					clientid = ?,
					approval_state = ?
				", array(
					$name,
					$template['description'],
					SCRIPT_ORIGIN_TYPE::CUSTOM,
					$_SESSION['uid'],
					APPROVAL_STATE::APPROVED
				));
				
				$scriptid = $db->Insert_ID();
				
				$db->Execute("INSERT INTO script_revisions SET
					scriptid	= ?,
					revision    = ?,
					script      = ?,
					dtcreated   = NOW(),
					approval_state = ?
				", array(
					$scriptid,
					1,
					$script['script'],
					APPROVAL_STATE::APPROVED
				));
				
				$okmsg = _("Script successfully forked");
			}
		}
		
		UI::Redirect("script_templates.php");
	}
	elseif ($req_task == "delete")
	{
		// Get template infor from database
		$template = $db->GetRow("SELECT * FROM scripts WHERE id=?", array($req_id));
		
		// Check permissions
		if (!$template || ($template['clientid'] == 0 && $_SESSION['uid'] != 0) ||
			($template['clientid'] != 0 && $_SESSION['uid'] != 0 && $_SESSION['uid'] != $template['clientid'])
		) {
			$errmsg = _("You don't have permissions to edit this template");
			UI::Redirect("script_templates.php");
		}
		
		// Check template usage
		$roles_count = $db->GetOne("SELECT COUNT(*) FROM farm_role_scripts WHERE scriptid=? AND event_name NOT LIKE 'CustomEvent-%'",
			array($req_id)
		);
		
		// If script used redirect and show error
		if ($roles_count > 0)
		{
			$errmsg = _("This template being used and cannot be deleted");
			UI::Redirect("script_templates.php");
		}
		
		// Delete tempalte and all revisions
		$db->Execute("DELETE FROM farm_role_scripts WHERE scriptid=?", array($req_id));
		$db->Execute("DELETE FROM scripts WHERE id=?", array($req_id));
		$db->Execute("DELETE FROM script_revisions WHERE scriptid=?", array($req_id));
		
		$okmsg = _("Script template successfully removed");
		UI::Redirect("script_templates.php");
	}
	elseif ($req_task == 'create' || $req_task == 'edit' || $req_task == 'share')
	{
		if ($req_id)
		{
			// Get template info from database
			$template = $db->GetRow("SELECT * FROM scripts WHERE id=?", array($req_id));
			
			if (!$template || ($template['clientid'] == 0 && $_SESSION['uid'] != 0) ||
				($template['clientid'] != 0 && $_SESSION['uid'] != 0 && $_SESSION['uid'] != $template['clientid'])
			) {
				$errmsg = _("You don't have permissions to edit this template");
				UI::Redirect("script_templates.php");
			}

			// Get list of all revisions
			$dbversions = $db->GetAll("SELECT revision FROM script_revisions WHERE scriptid=?", array($template['id']));
	        $versions = array();
	        foreach ($dbversions as $version)
	        {
	        	$versions[] = $version['revision'];
	        }
			
	        // Calculate current revision
			$display = array_merge($display, $template);
			$display['versions'] = $versions;
			$display['latest_version'] = max($versions);
			$display['selected_version'] = ($req_version) ? $req_version : max($versions);
			
			// Get script for current revision
			$display['script'] = $db->GetOne("SELECT script FROM script_revisions WHERE scriptid=? AND revision=?",
				array($template['id'], $display['selected_version'])
			);
		}
		else
		{
			$display['versions'] = array(1);
		}
		
		if ($_POST)
		{
			// Validate input data
			$Validator = new Validator();
			if (!$Validator->IsNotEmpty($post_name) && !$post_id)
			{
				$err[] = _("Script name required");
			}
			else
			{
				if (isset($post_cbtn_3) && $req_id)
				{
					$display['latest_version']++;
					$okmsg = _("Script template successfully saved. New version created.");
				}
				else
				{
					if ($req_id)
						$version = $display['selected_version'];
					else
						$version = 1;
					
					$okmsg = _("Script template successfully created");
				}
			}

			if (!$Validator->IsNotEmpty($post_name))
				$err[] = _("Script name required");
			
			if (!$Validator->IsNotEmpty($post_description))
				$err[] = _("Script description required");
				
			if (!$Validator->IsNotEmpty($post_script))
				$err[] = _("Script content required");
				
			if (count($err) == 0)
			{
				if ($req_task == "edit")
				{
					$db->Execute("UPDATE scripts SET
						name		= ?,
						description = ?
						WHERE id 	= ?
					", array(
						htmlspecialchars($post_name),
						htmlspecialchars($post_description),
						$req_id
					));
					
					if ($template['origin'] == SCRIPT_ORIGIN_TYPE::USER_CONTRIBUTED)
						$approval_state = APPROVAL_STATE::PENDING;
					else
						$approval_state = APPROVAL_STATE::APPROVED;
					
					$okmsg = _("Script template successfully updated");
				}
				
				if ($req_id && !isset($post_cbtn_3))
				{
					if ($req_task == "share")
					{
						$approval_state = APPROVAL_STATE::PENDING;
						
						$db->Execute("UPDATE scripts SET
							name	 	= ?,
							description = ?,
							origin = ?,
							approval_state = ?
							WHERE id = ?
						", array(
							htmlspecialchars($post_name),
							htmlspecialchars($post_description),
							SCRIPT_ORIGIN_TYPE::USER_CONTRIBUTED,
							$approval_state,
							$req_id
						));
						
						$okmsg = _("Thank you for contributing your script to Scalr community! After passing moderation, it will become available to other Scalr.net users.");
						
						//
						$client = $db->GetRow("SELECT * FROM clients WHERE id=?", array($template['clientid']));
						$script = array("name" => htmlspecialchars($post_name), "version" => $display['selected_version']);
						$send_email_to_team = true;						
						//
					}
					
					$db->Execute("UPDATE script_revisions SET
						script = ?, approval_state = ?
						WHERE scriptid = ? AND revision = ?
					", array(
						str_replace("\r\n", "\n", $post_script), $approval_state,
						$req_id, $display['selected_version']
					));
				}
				else
				{
					if ($template && $template['origin'] == SCRIPT_ORIGIN_TYPE::USER_CONTRIBUTED)
					{
						$origin = SCRIPT_ORIGIN_TYPE::USER_CONTRIBUTED;
						$approval_state = APPROVAL_STATE::PENDING;
						
						$okmsg = _("The script has been successfully updated. It will become available to other Scalr.net users after moderation.");
						
						//
						$client = $db->GetRow("SELECT * FROM clients WHERE id=?", array($template['clientid']));
						$script = array("name" => $template['name'], "version" => $display['selected_version']+1);
						$send_email_to_team = true;						
						//
					}
					else
					{
						if (!$template)
						{
							if ($_SESSION['uid'] != 0)
								$origin = SCRIPT_ORIGIN_TYPE::CUSTOM;
							else
								$origin = SCRIPT_ORIGIN_TYPE::SHARED;
								
							$approval_state = APPROVAL_STATE::APPROVED;
							$clientid = $_SESSION['uid'];
						}
						else
						{
							$approval_state = $template['approval_state'];
							$origin = $template['origin'];
							$clientid = $template['clientid'];
						}
					}
					
					if ($post_id)
					{
						$post_name = htmlspecialchars_decode($template['name']);
						$scriptid = $template["id"]; 
					}
					else
					{
						$db->Execute("INSERT INTO scripts SET
							name = ?,
							description = ?,
							origin = ?,
							dtadded = NOW(),
							clientid = ?,
							approval_state = ?
						", array(
							htmlspecialchars($post_name),
							htmlspecialchars($post_description),
							$origin,
							$clientid,
							$approval_state
						));
						
						$scriptid = $db->Insert_ID();
					}
					
					$revision = $db->GetOne("SELECT IF(MAX(revision), MAX(revision), 0) FROM script_revisions WHERE scriptid=?",
						array($scriptid)
					);
					
					$db->Execute("INSERT INTO script_revisions SET
						scriptid	= ?,
						revision    = ?,
						script      = ?,
						dtcreated   = NOW(),
						approval_state = ?
					", array(
						$scriptid,
						$revision+1,
						str_replace("\r\n", "\n", $post_script),
						$approval_state
					));
				}
				
				if ($send_email_to_team)
				{
					if (strlen($post_sharing_comments) > 0)
					{
						$db->Execute("INSERT INTO comments SET 
							clientid		= ?,
							object_owner	= ?,
							dtcreated		= NOW(),
							object_type		= ?,
							comment			= ?,
							objectid		= ?,
							isprivate		= '1'
						", array(
							$_SESSION['uid'],
							$_SESSION['uid'],
							COMMENTS_OBJECT_TYPE::SCRIPT,
							htmlspecialchars($post_sharing_comments),
							$scriptid
						));
					}
					
					//
					// Send mail to admins
					//	
					$count = $db->GetOne("SELECT COUNT(*) FROM script_revisions WHERE approval_state=?",
						array(APPROVAL_STATE::PENDING)
					);
					
					$link = "http://{$_SERVER['HTTP_HOST']}/contrib_script_templates.php?approval_state=Pending";
					
					$emails = explode("\n", CONFIG::$TEAM_EMAILS);
					if (count($emails) > 0)
					{
						foreach ($emails as $email)
						{
							$email = trim($email);
							
							$Mailer->ClearAddresses();
							$res = $Mailer->Send("emails/contributed_script.eml", 
								array("client" => $client, "script" => $script, "comments" => $post_sharing_comments, "count" => $count, "link" => $link), 
								$email, 
								""
							);
							
							$Logger->info("Sending 'emails/contributed_script.eml' email to '{$email}'. Result: {$res}");
							if (!$res)
								$Logger->error($Mailer->ErrorInfo);
						}
					}
				}
				
				UI::Redirect("script_templates.php");
			}
			else
				$display["err"] = $err;
		}
		
				
		$display["sys_vars"] = CONFIG::$SCRIPT_BUILTIN_VARIABLES;
		$display["script_timeout"] = CONFIG::$SYNCHRONOUS_SCRIPT_TIMEOUT;
		
		$Smarty->assign($display);
		
		if ($req_task == 'share')
			$Smarty->display('script_template_share.tpl');
		else
			$Smarty->display('script_template_create.tpl');
		exit();
	}
	
	$paging = new SQLPaging();
	
	if ($_SESSION['uid'] != 0)
	{
		$filter_sql .= " AND ("; 
			// Show shared roles
			$filter_sql .= " origin='".SCRIPT_ORIGIN_TYPE::SHARED."'";
		
			// Show custom roles
			$filter_sql .= " OR (origin='".SCRIPT_ORIGIN_TYPE::CUSTOM."' AND clientid='{$_SESSION['uid']}')";
			
			//Show approved contributed roles
			$filter_sql .= " OR (origin='".SCRIPT_ORIGIN_TYPE::USER_CONTRIBUTED."' AND (scripts.approval_state='".APPROVAL_STATE::APPROVED."' OR clientid='{$_SESSION['uid']}'))";
		$filter_sql .= ")";
	}
	
    $sql = "select scripts.*, MAX(script_revisions.dtcreated) as dtupdated from scripts INNER JOIN script_revisions 
    	ON script_revisions.scriptid = scripts.id WHERE 1=1 {$filter_sql}";

    if (isset($req_origin))
    {
    	$origin = preg_replace("/[^A-Za-z0-9-]+/", "", $req_origin);
    	$filter_sql .= " AND origin='{$origin}'";
    	$paging->AddURLFilter("origin", $origin);
    }
    
    if (isset($req_approval_state))
    {
    	$approval_state = preg_replace("/[^A-Za-z0-9-]+/", "", $req_approval_state);
    	$filter_sql .= " AND scripts.approval_state='{$approval_state}'";
    	$paging->AddURLFilter("approval_state", $approval_state);
    }
    
	$paging->SetSQLQuery($sql);
	$paging->AdditionalSQL = "GROUP BY script_revisions.scriptid ORDER BY dtupdated DESC";
	$paging->ApplyFilter($_POST["filter_q"], array("name", "description"));	
	$paging->ApplySQLPaging();
	$paging->ParseHTML();
	$display["filter"] = $paging->GetFilterHTML("inc/table_filter.tpl");
	$display["paging"] = $paging->GetPagerHTML("inc/paging.tpl");
	
	// Rows
	$display["rows"] = $db->GetAll($paging->SQL);	
	foreach ($display["rows"] as &$row)
	{
		if ($row['clientid'] != 0)
			$row["client"] = $db->GetRow("SELECT * FROM clients WHERE id = ?", array($row['clientid']));
			
		$row['version'] = $db->GetOne("SELECT MAX(revision) FROM script_revisions WHERE scriptid=?",
			array($row['id'])
		);
	}
	
	$display["page_data_options"] = array();
	$display["page_data_options_add"] = true;
		
	require("src/append.inc.php");
?>