<?
    require("src/prepend.inc.php"); 
    
    Core::Load("NET/SNMP");
	Core::Load("Data/RRD");
	
	require_once(dirname(__FILE__)."/../cron/watchers/class.SNMPWatcher.php");
	require_once(dirname(__FILE__)."/../cron/watchers/class.CPUSNMPWatcher.php");
	require_once(dirname(__FILE__)."/../cron/watchers/class.LASNMPWatcher.php");
	require_once(dirname(__FILE__)."/../cron/watchers/class.MEMSNMPWatcher.php");
	require_once(dirname(__FILE__)."/../cron/watchers/class.NETSNMPWatcher.php");
    
    if (Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::SCALR_ADMIN))
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($req_id));
    else 
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND env_id=?", 
        	array($req_id, Scalr_Session::getInstance()->getEnvironmentId())
        );

    if (!$farminfo)
        UI::Redirect("farms_view.php");

    // Check data folder for farm            
	if (!file_exists(APPPATH."/data/{$farminfo['id']}/graphics"))
		mkdir(APPPATH."/data/{$farminfo['id']}/graphics", 0777);
        
    try
    {
    	$Watcher = new SNMPWatcher("", $farminfo['id']);
    	$Watcher->PlotGraphic(strtoupper($req_type)."SNMP", $req_img);
    }
    catch(Exception $e)
    {
    	$Logger->fatal($e->getMessage());
    }
                
    header("Content-type: image/png");
    header('Pragma: private');
	header('Cache-control: private, must-revalidate');
    
    $req_img = preg_replace("/[^A-Za-z0-9-_]+/", "", $req_img);
    $req_type = preg_replace("/[^A-Za-z0-9-_]+/", "", $req_type);
    
    @readfile(APPPATH."/data/{$farminfo['id']}/graphics/{$req_img}/{$req_type}.gif");
?>