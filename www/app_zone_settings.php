<?php 
	require("src/prepend.inc.php"); 

	if ($_SESSION["uid"] == 0)
	{
		$errmsg = "Requested page cannot be viewed from admin account";
		UI::Redirect("index.php");
	}
	
	// checking correct zone id (present in database) and user permissions for this zoneid
	$zoneinfo = $db->GetRow("SELECT zone, allow_manage_system_records, id FROM zones WHERE clientid = ? AND id = ?", array($_SESSION['uid'], $req_zoneid));
		
	if(!$zoneinfo)
	{	
		UI::Redirect("index.php");
	}		
	
    $display["title"] = "Application&nbsp;&raquo;&nbsp;Zone settings";
    

	if ($_POST) 
	{		                     
		try
		{		
			// set new value (variable allow_manage_system_records) for current zone
			$db->Execute("UPDATE zones SET allow_manage_system_records = ? WHERE id = ?",
				array((int)$req_allow_manage_system_records, $req_zoneid)
			);
		}
		catch (Exception $e)
		{
			throw new ApplicationException($e->getMessage(), E_ERROR);
		}

		$okmsg = "System settings successfully saved";				
		UI::Redirect("sites_view.php");				
	}
	
	$display['zone'] = $zoneinfo;
	
	require("src/append.inc.php"); 
?>