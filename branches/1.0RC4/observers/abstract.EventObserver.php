<?php

	abstract class EventObserver implements IEventObserver
	{
		/**
		 * Farm ID
		 *
		 * @var integer
		 */
		protected $FarmID;
		
		/**
		 * Logger instance
		 *
		 * @var Logger
		 */
		protected $Logger;
		
		/**
		 * ADODB instance
		 *
		 * @var ADODB
		 */
		protected $DB;
		
		/**
		 * Constructor
		 *
		 */
		function __construct()
		{
			$this->DB = Core::GetDBInstance();
			$this->Logger = Logger::getLogger(__CLASS__);
		}
		
		/**
		 * Set FARM ID
		 *
		 * @param integer $farmid
		 */
		public function SetFarmID($farmid)
		{
			$this->FarmID = $farmid;
		}
		
		public function OnHostInit(HostInitEvent $event) {}
		
		public function OnHostUp(HostUpEvent $event) {}
		
		public function OnHostDown(HostDownEvent $event) {}
		
		public function OnHostCrash(HostCrashEvent $event)
		{
			$event->InstanceInfo['isrebootlaunched'] = 0;
			
			$HostDownEvent = new HostDownEvent($event->InstanceInfo);
			
			$this->OnHostDown($HostDownEvent);
		}
				
		public function OnLAOverMaximum(LAOverMaximumEvent $event) {}
		
		public function OnLAUnderMinimum(LAUnderMinimumEvent $event) {}
		
		public function OnRebundleComplete(RebundleCompleteEvent $event) {}
		
		public function OnRebundleFailed(RebundleFailedEvent $event) {}
		
		public function OnRebootBegin(RebootBeginEvent $event) {}
		
		public function OnRebootComplete(RebootCompleteEvent $event) {}
		
		public function OnFarmLaunched(FarmLaunchedEvent $event) {}
		
		public function OnFarmTerminated(FarmTerminatedEvent $event) {}
		
		public function OnNewMysqlMasterUp(NewMysqlMasterUpEvent $event) {}
		
		public function OnMysqlBackupComplete(MysqlBackupCompleteEvent $event) {}
		
		public function OnMysqlBackupFail(MysqlBackupFailEvent $event) {}
		
		public function OnIPAddressChanged(IPAddressChangedEvent $event) {}
		
		public function OnMySQLReplicationFail(MySQLReplicationFailEvent $event) {}
		
		public function OnMySQLReplicationRecovered(MySQLReplicationRecoveredEvent $event) {}
		
		public function OnEBSVolumeMounted(EBSVolumeMountedEvent $event) {}
	}
?>