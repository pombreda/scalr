<?php

class Scalr_UI_Controller_Farms extends Scalr_UI_Controller
{
	const CALL_PARAM_NAME = 'farmId';

	public function defaultAction()
	{
		$this->viewAction();
	}

	public function getList()
	{
		$retval = array();
		$s = $this->db->execute("SELECT id, name FROM farms WHERE env_id = ?", array($this->session->getEnvironmentId()));
		while ($farm = $s->fetchRow()) {
			$retval[$farm['id']] = $farm;
		}

		return $retval;
	}

	public function viewAction()
	{
		$this->response->setJsonResponse(array(
			'success' => true,
			'module' => $this->response->template->fetchJs('farms/view.js')
		));
	}

	public function xLaunchAction()
	{
		$this->request->defineParams(array(
			'farmId' => array('type' => 'int')
		));
		
		$dbFarm = DBFarm::LoadByID($this->getParam('farmId'));
		$this->session->getAuthToken()->hasAccessEnvironmentEx($dbFarm->EnvID);
		
		Scalr::FireEvent($dbFarm->ID, new FarmLaunchedEvent(true));
        
		$this->response->setJsonResponse(array('success' => true));
		
        //$okmsg = sprintf(_("Farm %s is now launching. It will take few minutes to start all servers."), $farminfo['name']);
	}
	
	public function xRemoveAction()
	{
		$this->request->defineParams(array(
			'farmId' => array('type' => 'int')
		));
		
		$dbFarm = DBFarm::LoadByID($this->getParam('farmId'));
		$this->session->getAuthToken()->hasAccessEnvironmentEx($dbFarm->EnvID);
		
		if ($dbFarm->Status != FARM_STATUS::TERMINATED)
	    	throw new Exception(_("Cannot delete a running farm. Please terminate a farm before deleting it."));
	
	    $servers = $this->db->GetOne("SELECT COUNT(*) FROM servers WHERE farm_id=? AND status!=?", array($dbFarm->ID, SERVER_STATUS::TERMINATED));
	    if ($servers != 0)
		    throw new Exception(sprintf(_("Cannot delete a running farm. %s server are still running on this farm."), $servers));
		    
		$this->db->BeginTrans();
    		
    	try
    	{
	    	$this->db->Execute("DELETE FROM farms WHERE id=?", array($dbFarm->ID));
	    	$this->db->Execute("DELETE FROM farm_role_settings WHERE farm_roleid IN (SELECT id FROM farm_roles WHERE farmid=?)", array($dbFarm->ID));
    		$this->db->Execute("DELETE FROM farm_roles WHERE farmid=?", array($dbFarm->ID));
    		$this->db->Execute("DELETE FROM logentries WHERE farmid=?", array($dbFarm->ID));
    		$this->db->Execute("DELETE FROM elastic_ips WHERE farmid=?", array($dbFarm->ID));
    		$this->db->Execute("DELETE FROM events WHERE farmid=?", array($dbFarm->ID));
    		$this->db->Execute("DELETE FROM ec2_ebs WHERE farm_id=?", array($dbFarm->ID));
    		$this->db->Execute("DELETE FROM apache_vhosts WHERE farm_id=?", array($dbFarm->ID));
    		
    		$this->db->Execute("DELETE FROM farm_role_options WHERE farmid=?", array($dbFarm->ID));
    		$this->db->Execute("DELETE FROM farm_role_scripts WHERE farmid=?", array($dbFarm->ID));
    		$this->db->Execute("DELETE FROM ssh_keys WHERE farm_id=?", array($dbFarm->ID));
	    		
    		//TODO: Remove servers
    		$this->db->Execute("DELETE FROM servers WHERE farm_id=?", array($dbFarm->ID));
    		
    		// Clean observers
    		$observers = $this->db->Execute("SELECT * FROM farm_event_observers WHERE farmid=?", array($dbFarm->ID));
    		while ($observer = $observers->FetchRow())
    		{
    			$this->db->Execute("DELETE FROM farm_event_observers WHERE id=?", array($observer['id']));
    			$this->db->Execute("DELETE FROM farm_event_observers_config WHERE observerid=?", array($observer['id']));
    		}
    		
    		$this->db->Execute("UPDATE dns_zones SET farm_id='0', farm_roleid='0' WHERE farm_id=?", array($dbFarm->ID));
    	}
    	catch(Exception $e)
    	{
    		$this->db->RollbackTrans();
    		throw new Exception(_("Cannot delete farm at the moment ({$e->getMessage()}). Please try again later."));
    	}
    		
	    $this->db->CommitTrans();
	    
	    $this->response->setJsonResponse(array('success' => true));
	}
	
	public function xListViewFarmsAction()
	{
		$this->request->defineParams(array(
			'clientId' => array('type' => 'int'),
			'farmId' => array('type' => 'int'),
			'sort' => array('type' => 'string', 'default' => 'id'),
			'dir' => array('type' => 'string', 'default' => 'ASC')
		));
		
		$sql = "SELECT clientid, id, name, status, dtadded FROM farms WHERE 1=1";

		if (!$this->session->getAuthToken()->hasAccess(Scalr_AuthToken::SCALR_ADMIN))
			$sql .= " AND env_id='".$this->session->getEnvironmentId()."'";

		if ($this->getParam('farmId'))
			$sql .= " AND id=".$this->db->qstr($this->getParam('farmId'));

		if ($this->getParam('clientId'))
			$sql .= " AND clientid=".$this->db->qstr($this->getParam('clientId'));

		if ($this->getParam('status') != '')
			$sql .= " AND status=".$this->db->qstr($this->getParam('status'));;

		$response = $this->buildResponseFromSql($sql, array("name", "id", "comments"));

		foreach ($response["data"] as &$row) {
			$row["servers"] = $this->db->GetOne("SELECT COUNT(*) FROM servers WHERE farm_id='{$row['id']}'");
			$row["roles"] = $this->db->GetOne("SELECT COUNT(*) FROM farm_roles WHERE farmid='{$row['id']}'");
			$row["zones"] = $this->db->GetOne("SELECT COUNT(*) FROM dns_zones WHERE farm_id='{$row['id']}'");

			$row['dtadded'] = date("M j, Y H:i:s", strtotime($row["dtadded"]));

			if ($this->session->getAuthToken()->hasAccess(Scalr_AuthToken::SCALR_ADMIN))
				$row['client_email'] = $this->db->GetOne("SELECT email FROM clients WHERE id='{$row['clientid']}'");

			$row["havemysqlrole"] = (bool)$this->db->GetOne("SELECT id FROM farm_roles WHERE role_id IN (SELECT role_id FROM role_behaviors WHERE behavior=?) AND farmid=? AND platform != ?",
				array(ROLE_BEHAVIORS::MYSQL, $row['id'], SERVER_PLATFORMS::RDS)
			);

			$row['status_txt'] = FARM_STATUS::GetStatusName($row['status']);

			if ($row['status'] == FARM_STATUS::RUNNING)
			{
				$row['shortcuts'] = $this->db->GetAll("SELECT * FROM farm_role_scripts WHERE farmid=? AND (farm_roleid IS NULL OR farm_roleid='0') AND ismenuitem='1'",
					array($row['id'])
				);
				foreach ($row['shortcuts'] as &$shortcut)
					$shortcut['name'] = $this->db->GetOne("SELECT name FROM scripts WHERE id=?", array($shortcut['scriptid']));
			}
		}

		$this->response->setJsonResponse($response);
	}
}