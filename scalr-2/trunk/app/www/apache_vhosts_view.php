<?php
	require("src/prepend.inc.php"); 
		
	if ($_SESSION["uid"] == 0)
	   UI::Redirect("index.php");	
	   
	$Client = Client::Load($_SESSION['uid']);	
	$display["title"] = _("Apache vhosts view");	

	if($req_action)
	{
		$Validator = new Validator();
		
		if (!is_array($req_id))
			$req_id = array($req_id);
		
		foreach ($req_id as $vhost_id)
		{
			if(!$Validator->IsNumeric($vhost_id))
				continue;
							
			if($req_action == "delete")
			{
				$db->Execute("DELETE FROM apache_vhosts WHERE id = ? AND client_id = ?",
					array($vhost_id, $_SESSION['uid'])
				);
				
				$okmsg = _("Selected virtual host(s) successfully removed");
			}
		}
	}
	
	if ($req_farm_id)
	{
		$farm_id = (int)$req_farm_id;
		$display["grid_query_string"] = "&farm_id={$farm_id}";
	}
	
	require("src/append.inc.php"); 