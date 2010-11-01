<?
    require("src/prepend.inc.php"); 
    
    if (Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::SCALR_ADMIN))
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($req_farmid));
    else 
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND env_id=?", 
        	array($req_farmid, Scalr_Session::getInstance()->getEnvironmentId())
        );

    header("Content-type: text/xml");
    
    //
    // Check cache
    //
    $timeline_cache_path = CACHEPATH."/timeline.{$req_farmid}.cxml";
    if (file_exists($timeline_cache_path))
    {
        clearstatcache();
        $time = filemtime($timeline_cache_path);
        
        if ($time > time()-CONFIG::$EVENTS_TIMELINE_CACHE_LIFETIME)
        {
        	readfile($timeline_cache_path);
        	exit();
        }
    }
    
    $DOM = new DOMDocument('1.0', 'UTF-8');
	$DOM->loadXML("<data></data>");
	$data = $DOM->documentElement;
    
    $events = $db->Execute("SELECT * FROM events WHERE farmid=? ORDER BY id DESC", array($farminfo["id"]));
    while ($event = $events->FetchRow())
    {
  		$date = gmdate("M d Y H:i:s T", strtotime($event["dtadded"]));
  		
  		$devent = $DOM->createElement("event", $event['message']);
  		$devent->setAttribute('title', $event['short_message']);
  		$devent->setAttribute('start', $date);
  		
  		if ($event['type'] == EVENT_TYPE::FARM_LAUNCHED)
  			$devent->setAttribute('icon', '/images/timeline/farm_launched.png');
  		elseif ($event['type'] == EVENT_TYPE::FARM_TERMINATED)
  			$devent->setAttribute('icon', '/images/timeline/farm_terminated.png');
  		elseif ($event['type'] == EVENT_TYPE::HOST_UP)
  			$devent->setAttribute('icon', '/images/timeline/host_up.png');
  		elseif ($event['type'] == EVENT_TYPE::HOST_DOWN)
  			$devent->setAttribute('icon', '/images/timeline/host_down.png');
  		else
  			$devent->setAttribute('icon', '/images/timeline/other.png');
  			
  		$data->appendChild($devent);
    }
	
    $contents = $DOM->saveXML();
    
    @file_put_contents($timeline_cache_path, $contents);
    
    print $contents;
?>
