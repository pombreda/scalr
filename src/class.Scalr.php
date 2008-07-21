<?php

	class Scalr
	{
		private static $Observers = array();
		private static $ConfigsCache = array();
		
		/**
		 * Attach observer
		 *
		 * @param EventObserver $observer
		 */
		public static function AttachObserver ($observer)
		{
			if (array_search($observer, self::$Observers) !== false)
				throw new Exception(_('Observer already attached to class <Scalr>'));
				
			self::$Observers[] = $observer;
		}
		
		/**
		 * Return observer configuration for farm
		 *
		 * @param string $farmid
		 * @param EventObserver $observer
		 * @return DataForm
		 */
		private static function GetFarmNotificationsConfig($farmid, $observer)
		{
			$DB = Core::GetDBInstance(NULL, true);
			
			Logger::getLogger(__CLASS__)->info("GetFarmNotificationsConfig({$farmid})");
			
			// Reconfigure farm settings if changes made
			$farms = $DB->GetAll("SELECT farms.id as fid FROM farms INNER JOIN client_settings ON client_settings.clientid = farms.clientid WHERE client_settings.`key` = 'reconfigure_event_daemon' AND client_settings.`value` = '1'");
			if (count($farms) > 0)
			{
				Logger::getLogger(__CLASS__)->info("Found ".count($farms)." with new settings. Cleaning cache.");
				foreach ($farms as $cfarmid)
				{
					Logger::getLogger(__CLASS__)->info("Cache for farm {$cfarmid["fid"]} cleaned.");
					self::$ConfigsCache[$cfarmid["fid"]] = false;
				}
			}
				
			// Update reconfig flag
			$DB->Execute("UPDATE client_settings SET `value`='0' WHERE `key`='reconfigure_event_daemon'");
				
			Logger::getLogger(__CLASS__)->info("Get config for farm: {$farmid}");
			
			// Check config in cache
			if (!self::$ConfigsCache[$farmid] || !self::$ConfigsCache[$farmid][$observer->ObserverName])
			{
				// Get configuration form
				self::$ConfigsCache[$farmid][$observer->ObserverName] = $observer->GetConfigurationForm();
				
				// Get farm observer id
				$farm_observer_id = $DB->GetOne("SELECT * FROM farm_event_observers 
					WHERE farmid=? AND event_observer_name=?",
					array($farmid, $observer->ObserverName)
				);
				
				// Get Configuration values
				if ($farm_observer_id)
				{
					$config_opts = $DB->Execute("SELECT * FROM farm_event_observers_config 
						WHERE observerid=?", array($farm_observer_id)
					);
					
					// Set value for each config option
					while($config_opt = $config_opts->FetchRow())
					{
						$field = &self::$ConfigsCache[$farmid][$observer->ObserverName]->GetFieldByName($config_opt['key']);
						if ($field)
							$field->Value = $config_opt['value'];
					}
				}
				else
					return false;
			}
			
			return self::$ConfigsCache[$farmid][$observer->ObserverName];
		}
		
		/**
		 * Fire event
		 *
		 * @param integer $farmid
		 * @param string $event_name
		 * @param string $event_message
		 */
		public static function FireEvent ($farmid, $event_type, $event_message)
		{
			try
			{
				// Notify class observers
				foreach (self::$Observers as $observer)
				{
					// Get observer config for farm
					$config = self::GetFarmNotificationsConfig($farmid, $observer);
					
					// If observer configured -> set config and fire event
					if ($config)
					{
						$observer->SetConfig($config);
						call_user_func(array($observer, "On{$event_type}"), $event_message);
					}
				}
			}
			catch(Exception $e)
			{
				Logger::getLogger(__CLASS__)->fatal("Exception thrown in Scalr::FireEvent(): ".$e->getMessage());
			}
				
			return;
		}
		
		/**
		 * Store event in database
		 *
		 * @param integer $farmid
		 * @param string $event_name
		 */
		public static function StoreEvent($farmid, $event_type /* args1, args2 ... argN */)
		{
			try
			{
				$DB = Core::GetDBInstance();
				
				$ReflectionInterface = new ReflectionClass("IEventObserver");
				$event = $ReflectionInterface->getMethod("On{$event_type}");
				$props = $event->getParameters();
				$vars = array();
				
				// Get list of arguments
				$args = func_get_args();
				
				// Remove first argument - farmid
				array_shift($args);
				
				// Remove second argument - event_type
				array_shift($args);
				
				// Get farm infor from database
				$farminfo = $DB->GetRow("SELECT * FROM farms WHERE id=?", array($farmid));
				if (!$farminfo)
					return;
				else
					$vars["farm"] = $farminfo;
				
				// Generate template vars array
				foreach ($props as $prop)
					$vars[$prop->name] = array_shift($args);
				
				// Get Smarty object
				$Smarty = Core::GetSmartyInstance();
				
				// Assign vars
				$Smarty->assign($vars);
				
				// Generate event message 
				$message = $Smarty->fetch("event_messages/{$event_type}.tpl");
				$short_message = $Smarty->fetch("event_messages/{$event_type}.short.tpl");
					
				// Store event in database
				$DB->Execute("INSERT INTO events SET 
					farmid	= ?, 
					type	= ?, 
					dtadded	= NOW(), 
					message	= ?,
					short_message = ?
					",
					array($farmid, $event_type, $message, $short_message)
				);
			}
			catch(Exception $e)
			{
				Logger::getLogger(__CLASS__)->fatal("Cannot store event in database: ".$e->getMessage());
			}
		}
	}
?>