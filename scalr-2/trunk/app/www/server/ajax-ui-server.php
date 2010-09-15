<?
    require("../src/prepend.inc.php");
    
    class AjaxUIServer
    {
    	public function __construct()
    	{
    		$this->DB = Core::GetDBInstance();
    		$this->Logger = Logger::getLogger(__CLASS__);
    	}
    	
    	public function GetServerLA($serverId)
    	{
    		$DBServer = DBServer::LoadByID($serverId);
    		if ($DBServer->clientId != $_SESSION['uid'] && $_SESSION['uid'] != 0)
    			throw new Exception ("Server not found");
    			
    		$snmpClient = new Scalr_Net_Snmp_Client();
    		
    		$port = 161;
    		if ($DBServer->GetProperty(SERVER_PROPERTIES::SZR_SNMP_PORT))
    			$port = $DBServer->GetProperty(SERVER_PROPERTIES::SZR_SNMP_PORT);
    		
    		$snmpClient->connect($DBServer->remoteIp, $port, $DBServer->GetFarmObject()->Hash);
    		
    		return $snmpClient->get('.1.3.6.1.4.1.2021.10.1.3.1');
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
				throw new Exception(_("This template being used and can't be deleted"));
			
			$this->DB->BeginTrans();
			
			// Delete tempalte and all revisions
			$this->DB->Execute("DELETE FROM farm_role_scripts WHERE scriptid=?", array($scriptID));
			$this->DB->Execute("DELETE FROM scripts WHERE id=?", array($scriptID));
			$this->DB->Execute("DELETE FROM script_revisions WHERE scriptid=?", array($scriptID));
			
			$this->DB->CommitTrans();
			
			return true;
    	}
    	
    	public function RemoveDNSZones(array $zones)
    	{
    		$this->DB->BeginTrans();
    		
		    foreach ($zones as $dd)
			{
				try
				{
					$zone = DBDNSZone::loadById($dd);
    				if ($_SESSION["uid"] != 0 && $zone->clientId != $_SESSION["uid"])
    					continue;
    					
    				$zone->status = DNS_ZONE_STATUS::PENDING_DELETE;
    				$zone->save();
				}
				catch(Exception $e)
				{
					$this->DB->RollbackTrans();
					Logger::getLogger("AjaxUIServer.RemoveApplications")->error("Exception thrown during application delete: {$e->getMessage()}");
					throw new Exception("Can't delete dns zone. Please try again later.");
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
    	
    	public function RebootServers(array $servers)
    	{	
    		foreach ($servers as $server_id)
    		{
    			try
    			{
	    			$DBServer = DBServer::LoadByID($server_id);
	    			if ($DBServer->clientId != $_SESSION['uid'] && $_SESSION['uid'] != 0)
	    				throw new Exception();
	    			
	    			PlatformFactory::NewPlatform($DBServer->platform)->RebootServer($DBServer);
    			}
    			catch (Exception $e)
    			{
					
    			}
    		}

    		return true;
    	}
    	
    	public function TerminateServers(array $servers, $decrease_mininstances_setting = false, $force_terminate = false)
    	{
			foreach ($servers as $server_id)
			{
				$DBServer = DBServer::LoadByID($server_id);
				if ($DBServer->clientId != $_SESSION['uid'] && $_SESSION['uid'] != 0)
					throw new Exception();
				try
				{
					if (!$force_terminate)
					{
						Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($farmid, 
							sprintf("Scheduled termination for server %s (%s). It will be terminated in 3 minutes.",
								$DBServer->serverId,
								$DBServer->remoteIp
						)
						));
					}
		            Scalr::FireEvent($DBServer->farmId, new BeforeHostTerminateEvent($DBServer, $force_terminate));
		            
		            $this->DB->Execute("UPDATE servers_history SET
						dtterminated	= NOW(),
						terminate_reason	= ?
						WHERE server_id = ?
					", array(
						sprintf("Terminated via user interface"),
						$DBServer->serverId
					));
				}
				catch (Exception $e)
				{
					$this->Logger->fatal(sprintf("Can't terminate %s: %s",
						$instanceinfo['instance_id'],
						$e->getMessage()
					));
				}
    		}

    		if ($decrease_mininstances_setting)
    		{
    			$DBServer = DBServer::LoadByID($servers[0]);
    			$DBFarmRole = $DBServer->GetFarmRoleObject();

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

    	public function LoadServers($farmId, $farm_roleId)
    	{
    		$serverNames = $this->DB->GetAll("SELECT server_id, remote_ip 
				FROM servers WHERE farm_id = ? AND farm_roleid = ? AND `status` = ?",
    			array($farmId, $farm_roleId, SERVER_STATUS::RUNNING)
    		);

    		print json_encode(array(
	    		"result"	=> "ok",
				"data"		=> $serverNames
	    	));
	    	
    		exit();
    				
    	}
    	
    	public function LoadFarmRoles($farmId, $sqlFilter)
    	{
    		$sql = "SELECT farm_roles.id, roles.name FROM farm_roles 
    		INNER JOIN roles ON roles.id = farm_roles.role_id WHERE farmid=? {$sqlFilter}";
			
    		$roleNames = $this->DB->GetAll($sql, 
    			array($farmId)
			);			
			
			print json_encode(array(
	    		"result"	=> "ok",
				"data"		=> $roleNames
	    	));
	    	
    		exit();    		
    	}
    	
    	public function LoadFarms()
    	{    
    		if ($_SESSION['uid'] != 0)
    		{	
	    		$farmsInfo = $this->DB->GetAll("SELECT id, name FROM `farms` WHERE clientid = ?",
	    			array($_SESSION['uid'])
	    		);
    		}
    		
    		print json_encode(array(
	    		"result"	=> "ok",
				"data"		=> $farmsInfo
	    	));
	    	
    		exit();
    	}
    	
    	public function LoadScripts()
    	{

			if ($_SESSION['uid'] != 0)
			{
				$script_filter_sql .= " AND ("; 
					// Show shared roles
					$script_filter_sql .= " origin='".SCRIPT_ORIGIN_TYPE::SHARED."'";

					// Show custom roles
					$script_filter_sql .= " OR (origin='".SCRIPT_ORIGIN_TYPE::CUSTOM."' 
							AND clientid='{$_SESSION['uid']}')";
					
					//Show approved contributed roles
					$script_filter_sql .= " OR (origin='".SCRIPT_ORIGIN_TYPE::USER_CONTRIBUTED."' 
							AND (scripts.approval_state='".APPROVAL_STATE::APPROVED."' 
							OR clientid='{$_SESSION['uid']}'))";
				$script_filter_sql .= ")";
				

			    $sql = "SELECT scripts.id, scripts.name, MAX(script_revisions.dtcreated) as dtupdated from scripts INNER JOIN script_revisions 
			    	ON script_revisions.scriptid = scripts.id WHERE 1=1 {$script_filter_sql} GROUP BY script_revisions.scriptid ORDER BY dtupdated DESC";
				
			    // Get list of scripts
			    $scripts = $this->DB->GetAll($sql);		
			   
			    foreach ($scripts as $script)
			    {
			    	if ($this->DB->GetOne("SELECT COUNT(*) FROM script_revisions WHERE approval_state=? AND scriptid=?", 
			    		array(APPROVAL_STATE::APPROVED, $script['id'])) > 0
			    	)
			    	$result[] = $script;
			    }
			}
		    return $result;
	    }

	    public function GetScriptArgs($scriptId)
	    {
	    	if ($_SESSION['uid'] != 0)
		    {
		    	$scriptId = (int)$scriptId;

	    		$dbversions = $this->DB->GetAll("SELECT * FROM script_revisions WHERE scriptid=? AND approval_state=? ORDER BY revision DESC", 
		        	array($scriptId, APPROVAL_STATE::APPROVED)
		        );

	    		$versions = array();
		        foreach ($dbversions as $version)
		        {
		        	$text = preg_replace('/(\\\%)/si', '$$scalr$$', $version["script"]);
		        	preg_match_all("/\%([^\%\s]+)\%/si", $text, $matches);
		        	$vars = $matches[1];
				    $data = array();
				    foreach ($vars as $var)
				    {
				    	if (!in_array($var, array_keys(CONFIG::$SCRIPT_BUILTIN_VARIABLES)))
				    		$data[$var] = ucwords(str_replace("_", " ", $var));
				    }
				    $data = json_encode($data);
		        	
		        	$versions[] = array("revision" => $version['revision'], "fields" => $data);
		        }
		    }
	        return $versions;
	    }
	     

	    public function LoadSecurityGroupsFromAWS()
	    {
	    	try
	    	{
				if ($_SESSION['uid'] != 0)
		    	{		    	
		    		$securityGroups = Modules_Platforms_Ec2_Helpers_Ec2::loadSecurityGroups();  	    	
		    	 
		    	 	if(!$securityGroups)		    	 	
		    	 		throw new Exception("No security groups");
		    	 		
		    		print json_encode(array(
	    			"result"	=> "ok",
					"data"		=> $securityGroups
	    			));
	    		
    				exit();    			
				}
				else
				  throw new Exception("You can't use it from admin's account"); 
			}
			catch(Exception $e)
		    {
				print json_encode(array(
	    			"result"	=> "error",
					"msg"		=> $e->getMessage()
	    			));
	    		
    				exit(); 
		    }     			
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

    	$result = $ReflectMethod->invokeArgs($AjaxUIServer, $args);

    	if(empty($result))    	
	    	throw new Exception("empty result");

    	print json_encode(array(
    		"result"	=> "ok",
			"data"		=> $result
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