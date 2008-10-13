<? 
	require("src/prepend.inc.php"); 
	
	$display["title"] = "IP access&nbsp;&raquo;&nbsp;Manage allowed hosts";

	if ($_POST && $post_actionsubmit)
	{
		if ($_POST["action"] == "delete")
		{
			foreach ((array)$_POST["delete"] as $dd)
			{	
				$info = $db->GetRow("SELECT * FROM ipaccess WHERE id=?", array($dd));
				if ($info)
				{
    			    $db->Execute("DELETE FROM ipaccess WHERE id=?", array($dd));
    				
    				$i++;
				}
			}
			$mess = "{$i} IP addresses deleted";
			UI::Redirect("ipaccess_view.php");
		}
	};
	
	$sql = "select * from ipaccess";

    if ($get_id)
    {
        $id = (int)$get_id;
    	$sql .= " WHERE id='{$id}'";
    }
	
	//Paging
	$paging = new SQLPaging($sql);
	$paging->ApplyFilter($_POST["filter_q"], array("comment", "ipaddress"));
	$paging->ApplySQLPaging();
	$paging->ParseHTML();
	$display["filter"] = $paging->GetFilterHTML("inc/table_filter.tpl");
	$display["paging"] = $paging->GetPagerHTML("inc/paging.tpl");

	
	// Rows
	$display["rows"] = $db->GetAll($paging->SQL);
	foreach($display["rows"] as &$row)
	{
	    
	}
	
	$display["page_data_options"] = array(array("name" => "Delete", "action" => "delete"));
	
	require("src/append.inc.php"); 
	
?>