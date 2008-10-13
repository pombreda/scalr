<?
    require("src/prepend.inc.php"); 

    $display["experimental"] = true;
    
    if ($_SESSION['uid'] == 0)
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($req_farmid));
    else 
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?", array($req_farmid, $_SESSION['uid']));

    if (!$farminfo)
        UI::Redirect("farms_view.php");
        
    
	$display["farminfo"] = $farminfo;
	$display["title"] = "Events for farm {$farminfo['name']}";
	
	$sql = "SELECT * from events WHERE farmid='{$farminfo['id']}'";

	$paging = new SQLPaging();
			
	//
	//Paging
	//
	$paging->SetSQLQuery($sql);
	$paging->AdditionalSQL = "ORDER BY id DESC";
	$paging->ApplyFilter($_POST["filter_q"], array("message", "type", "dtadded"));
	$paging->ApplySQLPaging();
	$paging->ParseHTML();
	$display["filter"] = $paging->GetPagerHTML("inc/table_filter.tpl");;
	$display["paging"] = $paging->GetPagerHTML("inc/paging.tpl");


	//
	// Rows
	//
	$display["rows"] = $db->GetAll($paging->SQL);
	/*
	foreach ($display["rows"] as &$row)
	{
		//
	}
	*/
	require_once("src/append.inc.php");
?>