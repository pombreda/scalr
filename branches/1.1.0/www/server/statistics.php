<?
    require(dirname(__FILE__)."/../../src/prepend.inc.php");
    Core::Load("IO/PCNTL/interface.IProcess.php"); 
	require(dirname(__FILE__)."/../../cron/class.RRDGraphProcess.php");
    	
	$a = 1024*1024*1024*10;
	$b = 1024*1024*1024*10;
	$large_number =  $a+$b;
	var_dump($large_number);
		
	$c1 = pow(2, 30)+rand(1024, 2048);
	$c2 = pow(2, 32)+rand(1024, 2048);
	$r = round(($c1+$c2)/2, 0);
	
	var_dump($c1);
	
	//$RRD = new RRD(dirname(__FILE__)."/../../temp/db.rrd");
	
    //$RRD->Update(array($c1, $c1));
	    
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
	    	
	    	$image_path = dirname(__FILE__)."/../temp/test.gif";
	    	
	    	$farm_rrddb_dir = CONFIG::$RRD_DB_DIR."/{$farminfo['id']}";
	    	$rrddbpath = "{$farm_rrddb_dir}/{$role_name}/{$watchername}/db.rrd";
	    	
	    	$rrddbpath = dirname(__FILE__)."/../../temp/db.rrd";
	    	CONFIG::$RRD_GRAPH_STORAGE_TYPE = RRD_STORAGE_TYPE::LOCAL_FS;
	    	
	    	if (file_exists($rrddbpath))
	    	{
	        	try
	        	{
	    			$RRDGraphProcess->GenerateGraph($farmid, $role_name, $rrddbpath, $watchername, $graph_type, $image_path);
	    			
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
?>