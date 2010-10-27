<? 

	require("src/prepend.inc.php"); 
	$display["title"] = _("Event log&nbsp;&raquo;&nbsp;View");
	$display['load_extjs'] = true;
    	
	if ($req_farmid)
	{
		$farmid = (int)$_REQUEST["farmid"];
		$display["grid_query_string"] = "&farmid={$farmid}";
	}
	
	if ($req_iid)
	{
		$iid = (int)$_REQUEST["iid"];
		$display["grid_query_string"] = "&iid={$iid}";
	}

		//download log file to user PC
	if ($get_action == 'download')	
	{		

		$response = include ('server/grids/event_log_list.php');	 // get event records from DB
		
		$fileContent = array();
		$fileContent[] = "Type;Time;Farm;Caller;Message\r\n";
	
		foreach($response['data'] as $data)
		{
			$data['message'] = str_replace("<br />","",$data['message']);
			$data['message'] = str_replace("\n","",$data['message']);
			
			$fileContent[] = "{$data['s_severity']};{$data['time']};{$data['farm_name']};{$data['source']};{$data['message']}"; 
		}		
		
		header('Content-Encoding: utf-8');
		header('Content-Type: text/csv');
		header('Expires: Mon, 10 Jan 1997 08:00:00 GMT');
		header('Pragma: no-cache');		
		header('Cache-Control: no-store, no-cache, must-revalidate' );
		header('Cache-Control: post-check=0, pre-check=0', false );  
		header('Content-Disposition: attachment; filename='."EventLog_".date("M_j_Y_H:i:s").".csv"); // file name and it's type

		echo implode("\n", $fileContent);
		exit();
		
	}

	$display["table_title_text"] = sprintf(_("Current time: %s"), date("M j, Y H:i:s"));
	
	$severities = array(
		array('hideLabel' => true, 'boxLabel'=> 'Fatal error', 'name' => 'severity[]', 'inputValue' => 5, 'checked'=> true),
		array('hideLabel' => true, 'boxLabel'=> 'Error', 'name' => 'severity[]','inputValue'=> 4, 'checked'=> true),
		array('hideLabel' => true, 'boxLabel'=> 'Warning', 'name' => 'severity[]', 'inputValue'=> 3, 'checked'=> true),
		array('hideLabel' => true, 'boxLabel'=> 'Information','name' => 'severity[]', 'inputValue'=> 2, 'checked'=> true),
		array('hideLabel' => true, 'boxLabel'=> 'Debug', 'name' => 'severity[]', 'inputValue'=> 1, 'checked'=> false)
	);
	$display["severities"] = json_encode($severities);

	if (!$_SESSION["uid"])
		$farms = $db->GetAll("SELECT id, name FROM farms");
	else
		$farms = $db->GetAll("SELECT id, name FROM farms WHERE clientid='{$_SESSION['uid']}'");
	
	$disp_farms = array(array('',''));
	foreach ($farms as $farm)
	{
		$disp_farms[] = array($farm['id'], $farm['name']);
	}
		
	$display['farms'] = json_encode($disp_farms);
	
	require("src/append.inc.php"); 
	
?>