<?
    print @file_get_contents("http://stats.scalr.net/server/statistics.php?".http_build_query($_REQUEST));

	/*
	require(dirname(__FILE__)."/../../src/prepend.inc.php");
    Core::Load("IO/PCNTL/interface.IProcess.php"); 
	require(dirname(__FILE__)."/../../cron/class.RRDGraphProcess.php");
    	
    if ($req_task == "get_stats_image_url")
    {
    	$RRDGraphProcess = new RRDGraphProcess();
    	
    	$farmid = (int)$req_farmid;
    	$watchername = $req_watchername;
    	$graph_type = $req_graph_type;
    	$role_name = $req_role_name;
    	
    	$farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($farmid));
    	if ($farminfo["status"] != 1)
    		$result = array("type" => "error", "msg" => _("Statistics not available for terminated farm"));
    	else
    	{
	    	$graph_info = $RRDGraphProcess->GetGraphicInfo($graph_type);
	
	    	$image_path = APPPATH."/cache/stats/{$farmid}/{$role_name}.{$watchername}.{$graph_type}.gif";
	    	
	    	$farm_rrddb_dir = CONFIG::$RRD_DB_DIR."/{$farminfo['id']}";
	    	$rrddbpath = "{$farm_rrddb_dir}/{$role_name}/{$watchername}/db.rrd";
	    	
	    	if (file_exists($rrddbpath))
	    	{
	        	try
	        	{
	    			$RRDGraphProcess->GenerateGraph($farmid, $role_name, $rrddbpath, $watchername, $graph_type);
	    			
	    			$url = str_replace(array("%fid%","%rn%","%wn%"), array($farmid, $role_name, $watchername), CONFIG::$RRD_STATS_URL);
					$url = "{$url}{$graph_type}.gif";
	    			
	    			$result = array("type" => "ok", "msg" => $url);
	        	}
	        	catch(Exception $e)
	        	{
	        		$result = array("type" => "error", "msg" => $e->getMessage());
	        	}
	    	}
	    	else
	    		$result = array("type" => "error", "msg" => _("Statistics not available yet"));
    	}
    }
    
    print json_encode($result);
    */
?>