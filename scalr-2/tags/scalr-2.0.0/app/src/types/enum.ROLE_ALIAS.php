<?
	final class ROLE_ALIAS
	{
		const BASE 		= "base";
		const MYSQL 	= "mysql";
		const WWW	 	= "www";
		const APP 		= "app";
		const MEMCACHED  = "memcached";
		
		
		public function GetTypeByAlias($alias)
		{
			$types = array(
				"base"		 => _("Base images"),
				"mysql"		 => _("Database servers"),
				"app"		 => _("Application servers"),
				"www"		 => _("Load balancers"),
				"memcached"  => _("Caching servers")
			);
						
			return $types[$alias];
		}
	}
?>