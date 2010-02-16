<?
    require("../src/prepend.inc.php");
    
    class AjaxUIServer
    {
    	public function __construct()
    	{
    		$this->DB = Core::GetDBInstance();
    		$this->Logger = Logger::getLogger(__CLASS__);
    	}
    	
    	public function RemoveScript($scriptID)
    	{
    		// Get template infor from database
			$template = $this->DB->GetRow("SELECT * FROM scripts WHERE id=?", array($scriptID));
			
			// Check permissions
			if (!$template || ($template['clientid'] == 0 && $_SESSION['uid'] != 0) ||
				($template['clientid'] != 0 && $_SESSION['uid'] != 0 && $_SESSION['uid'] != $template['clientid'])
			) {
				throw new Exception(_("You don't have permissions to edit this template"));
			}
			
			// Check template usage
			$roles_count = $this->DB->GetOne("SELECT COUNT(*) FROM farm_role_scripts WHERE scriptid=? AND event_name NOT LIKE 'CustomEvent-%'",
				array($scriptID)
			);
			
			// If script used redirect and show error
			if ($roles_count > 0)
				throw new Exception(_("This template being used and cannot be deleted"));
			
			$this->DB->BeginTrans();
			
			// Delete tempalte and all revisions
			$this->DB->Execute("DELETE FROM farm_role_scripts WHERE scriptid=?", array($scriptID));
			$this->DB->Execute("DELETE FROM scripts WHERE id=?", array($scriptID));
			$this->DB->Execute("DELETE FROM script_revisions WHERE scriptid=?", array($scriptID));
			
			$this->DB->CommitTrans();
			
			return true;
    	}
    	
    	public function RemoveApplications(array $zones)
    	{
    		$ZoneControler = new DNSZoneControler();
		    
    		$this->DB->BeginTrans();
    		
		    foreach ($zones as $dd)
			{				 
				try
				{
					if ($_SESSION["uid"] != 0)
						$zone = $this->DB->GetRow("SELECT * FROM zones WHERE id=? AND clientid=?", array($dd, $_SESSION["uid"]));
					else 
						$zone = $this->DB->GetRow("SELECT * FROM zones WHERE id=?", array($dd));
					
				    if ($zone)
					{
	    				$ZoneControler->Delete($zone["id"]);
	    				$i++;
					}
				}
				catch(Exception $e)
				{
					$this->DB->RollbackTrans();
					Logger::getLogger("AjaxUIServer.RemoveApplications")->error("Exception thrown during application delete: {$e->getMessage()}");
					throw new Exception("Cannot delete application '{$zone['name']}'. Please try again later.");
				}
			}
			
			$this->DB->CommitTrans();
			
			return true;
    	}
    	
    	public function RemoveVolume($volume_id, $region = "")
    	{
    		$Client = Client::Load($_SESSION['uid']);
    		
    		$region = ($region) ? $region : $_SESSION['aws_region'];
    		
            $AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($region));
			$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
    		$AmazonEC2Client->DeleteVolume($volume_id);
    		    		
    		return true;
    	}
    	
    	public function RemoveSnapshots(array $snapshots)
    	{
    		$Client = Client::Load($_SESSION['uid']);
            $AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($_SESSION['aws_region']));
			$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
    		foreach ($snapshots as $snapshot)
    		{
    			$AmazonEC2Client->DeleteSnapshot($snapshot);
    		}
    		
    		return true;
    	}
    	
    	public function RebootInstances(array $instances, $farmid)
    	{
    		if ($farmid)
    		{
	    		$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($farmid));
	            if (!$farminfo || ($_SESSION['uid'] != 0 && $farminfo['clientid'] != $_SESSION['uid']))
	            	throw new Exception("Farm not found in database");
				
	            $clientid = $farminfo['clientid'];
	            $region = $farminfo['region']; 
    		}
    		else
    		{
    			$clientid = $_SESSION['uid'];
	            $region = $_SESSION['aws_region'];
    		}
    		
    		$Client = Client::Load($clientid);
            $AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($region));
			$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
		
    		$AmazonEC2Client->RebootInstances($instances);
    		
    		return true;
    	}
    	
    	public function TerminateInstances(array $instances, $farmid, $decrease_mininstances_setting = false, $force_terminate = false)
    	{
    		if ($farmid)
    		{
	    		$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($farmid));
	            if (!$farminfo || ($_SESSION['uid'] != 0 && $farminfo['clientid'] != $_SESSION['uid']))
	            	throw new Exception("Farm not found in database");
				
	            $clientid = $farminfo['clientid'];
	            $region = $farminfo['region']; 
    		}
    		else
    		{
    			$clientid = $_SESSION['uid'];
	            $region = $_SESSION['aws_region'];
    		}
			
    		if ($force_terminate)
    		{
            	$Client = Client::Load($clientid);
            	$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($region));
				$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
				
				$AmazonEC2Client->TerminateInstances($instances);
    		}
    		else
    		{
    			foreach ($instances as $instance_id)
    			{
	    			$instanceinfo = $this->DB->GetRow("SELECT * FROM farm_instances WHERE instance_id=?", array($instance_id));
    				try
					{
						Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($farmid, 
							sprintf("Scheduled termination for instance %s (%s). It will be terminated in 3 minutes.",
	                        	$instanceinfo["instance_id"],
	                        	$instanceinfo["external_ip"]
	                    	)
						));
			            Scalr::FireEvent($farmid, new BeforeHostTerminateEvent(DBInstance::LoadByID($instanceinfo['id']), false));
					}
					catch (Exception $e)
					{
						$this->Logger->fatal(sprintf("Cannot terminate %s: %s",
							$instanceinfo['instance_id'],
							$e->getMessage()
						));
					}
    			}
    		}
			
    		
    		
    		if ($decrease_mininstances_setting && $farmid)
    		{
    			$instance_info = $this->DB->GetRow("SELECT * FROM farm_instances WHERE instance_id=?", array($instances[0]));
    			$DBFarmRole = DBFarmRole::LoadByID($instance_info['farm_roleid']);
    						
    			$min_instances = $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES);
    			if ($min_instances > 1)
    			{
	    			$DBFarmRole->SetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES, 
	    				$min_instances-1
	    			);
    			}
    		}
    		
    		return true;
    	}
    }

    // Run
    try
    {
    	$AjaxUIServer = new AjaxUIServer();
    	
    	$Reflect = new ReflectionClass($AjaxUIServer);
    	if (!$Reflect->hasMethod($req_action))
    		throw new Exception(sprintf("Unknown action: %s", $req_action));
    		
    	$ReflectMethod = $Reflect->getMethod($req_action);
    		
    	$args = array();
    	foreach ($ReflectMethod->getParameters() as $param)
    	{
    		if (!$param->isArray())
    			$args[$param->name] = $_REQUEST[$param->name];
    		else
    			$args[$param->name] = json_decode($_REQUEST[$param->name]);
    	}	
    	
    	$ReflectMethod->invokeArgs($AjaxUIServer, $args);
    	
    	print json_encode(array(
    		"result"	=> "ok"
    	));
    }
    catch(Exception $e)
    {
    	print json_encode(array(
    		"result"	=> "error",
    		"msg"		=> $e->getMessage()
    	));
    }
        
    exit();
?>