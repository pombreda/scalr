<? 
	require("src/prepend.inc.php"); 
	
	if (!Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::SCALR_ADMIN))
	{
		$errmsg = _("You have no permissions for viewing requested page");
		UI::Redirect("index.php");
	}
	
	$display["title"] = "Nameservers&nbsp;&raquo;&nbsp;View";

	if ($_POST && $post_actionsubmit)
	{
		if ($_POST["action"] == "delete")
		{
			foreach ((array)$_POST["delete"] as $dd)
			{	
				$info = $db->GetRow("SELECT * FROM nameservers WHERE id='{$dd}'");
			    if ($info)
			    {
    			    $db->Execute("DELETE FROM nameservers WHERE id='{$dd}'");
    				$i++;
			    }
			}
			
			$okmsg = "{$i} Nameservers deleted";
			UI::Redirect("ns_view.php");
		}
	};
	
	$sql = "SELECT * FROM nameservers";
	
	// Rows
	$display["rows"] = $db->GetAll($sql);
	
	$display["page_data_options"] = array(array("name" => "Delete", "action" => "delete"));
	
	require("src/append.inc.php"); 
	
?>