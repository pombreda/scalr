<?php

	interface IPlatformModule
	{
		public function LaunchServer(DBServer $DBServer);
				
		public function TerminateServer(DBServer $DBServer);
		
		public function RebootServer(DBServer $DBServer);
		
		public function CreateServerSnapshot(BundleTask $BundleTask);
		
		public function CheckServerSnapshotStatus(BundleTask $BundleTask);
		
		public function RemoveServerSnapshot(DBRole $DBRole);
		
		public function GetServerExtendedInformation(DBServer $DBServer);
		
		public function GetServerConsoleOutput(DBServer $DBServer);
		
		public function GetServerRealStatus(DBServer $DBServer);
		
		public function GetServerIPAddresses(DBServer $DBServer);
		
		public function IsServerExists(DBServer $DBServer);
		
		public function PutAccessData(DBServer $DBServer, Scalr_Messaging_Msg $message);
		
		public function GetServerID(DBServer $DBServer);
	}
?>