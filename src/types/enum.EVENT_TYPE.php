<?
	final class EVENT_TYPE
	{
		const HOST_UP 	= "HostUp";
		const HOST_DOWN	= "HostDown";
		const HOST_CRASH	= "HostCrash";
		const LA_OVER_MAXIMUM	= "LAOverMaximum";
		const LA_UNDER_MINIMUM	= "LAUnderMinimum";
		const REBUNDLE_COMPLETE	= "RebundleComplete";
		const REBUNDLE_FAILED	= "RebundleFailed";
		const REBOOT_BEGIN	= "RebootBegin";
		const REBOOT_COMPLETE	= "RebootComplete";
		
		const FARM_TERMINATED = "FarmTerminated";
		const FARM_LAUNCHED = "FarmLaunched";
		
		public static function GetEventDescription($event_type)
		{
			$descriptions = array(
				self::HOST_UP 			=> "Instance started and configured.",
				self::HOST_DOWN 		=> "Instance terminated.",
				self::HOST_CRASH 		=> "Instance crashed inexpectedly.",
				self::LA_OVER_MAXIMUM 	=> "Cumulative load average for a role is higher than maxLA setting.",
				self::LA_UNDER_MINIMUM 	=> "Cumulative LA for a role is lower than minLA setting.",
				self::REBUNDLE_COMPLETE => "\"Synchronize to all\" or custom role creation competed succesfully.",
				self::REBUNDLE_FAILED 	=> "\"Synchronize to all\" or custom role creation failed.",
				self::REBOOT_BEGIN 		=> "Instance being rebooted.",
				self::REBOOT_COMPLETE 	=> "Instance came up after reboot.",
				self::FARM_LAUNCHED 	=> "Farm has been launched.",
				self::FARM_TERMINATED 	=> "Farm has been terminated."
			);
			
			return $descriptions[$event_type];
		}
	}
?>