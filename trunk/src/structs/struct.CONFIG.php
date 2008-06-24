<?		
	final class CONFIG
	{
		public static $DB_DRIVER;
		public static $DB_HOST;
		public static $DB_NAME;
		public static $DB_USER;
		public static $DB_PASS;
		
		public static $DEBUG_PROFILING;
		public static $DEBUG_APP;
		public static $DEBUG_LEVEL;
		public static $DEBUG_DB;


		/**
		 * Encrypted registrar CP password
		 *
		 * @staticvar string
		 */
		public static $ADMIN_PASSWORD;
		
		public static $ADMIN_LOGIN;
		
		public static $CRYPTOKEY;

		public static $CRYPTO_ALGO;
		
		public static $PAGING_ITEMS;
		
		public static $EMAIL_ADDRESS;
		
		public static $EMAIL_NAME;
		
		public static $AWS_ACCOUNTID;
		
		public static $AWS_KEYNAME;
		
		public static $DEF_SOA_OWNER;
		
		public static $EVENTHANDLER_URL;
		
		public static $SECGROUP_PREFIX;
		
		public static $EMAIL_DSN;
		
		public static $REBOOT_TIMEOUT;
		
		public static $DEF_SOA_PARENT;
		
		public static $NAMEDCONFTPL;
		
		public static $DYNAMIC_A_REC_TTL;
		
		public static $S3CFG_TEMPLATE;
		
		public static $SNMPTRAP_PATH;
		
		public static $LOG_DAYS;
		
		public static $LAUNCH_TIMEOUT;
		
		public static $CLIENT_MAX_INSTANCES;
		
		
		public static $PRICE;
		public static $PAYPAL_BUSINESS;
		public static $PAYPAL_RECEIVER;
		public static $PAYPAL_ISDEMO;
		public static $PAYMENT_TERM;
		public static $PAYMENT_DESCRIPTION;
		
		public static $IPNURL;
		public static $PDTURL;
		
		/**
		 * List all available properties through reflection
		 * FIXME: Move to parent class Struct, when php will have late static binding
		 *
		 * @return Array or names
		 */
		public static function GetKeys()
		{ 
			$retval = array();
			$ReflectionClassThis = new ReflectionClass(__CLASS__);
			foreach($ReflectionClassThis->getStaticProperties() as $Property)
			{
				$retval[] = $Property->name;
			}
			return($retval);
		}
		
		/**
		 * Get all values
		 * FIXME: Move to superclass, when php will have late static binding
		 *
		 * @param  $key Key name
		 * @return array Array or values
		 */
		public static function GetValues($key)
		{
			return get_class_vars(__CLASS__);
		}
		
		/**
		 * Get value of property by it's name
		 * FIXME: Move to parent class Struct, when php will have late static binding
		 *
		 * @param  $key Key name
		 * @return string
		 */
		public static function GetValue($key)
		{
			//property_exists
			$ReflectionClassThis = new ReflectionClass(__CLASS__);
			if ($ReflectionClassThis->hasProperty($key))
			{
				return $ReflectionClassThis->getStaticPropertyValue($key);
			}
			else 
			{
				throw new Exception(sprintf(_("Called %s::GetValue('{$key}') for non-existent property {$key}"), __CLASS__));
			}
		}
	}
	
?>