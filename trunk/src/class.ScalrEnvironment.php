<?
	abstract class ScalrEnvironment
    {
		/**
		 * Arguments
		 * @var array
		 */
    	private $Args;
    	protected $DB;
    	
    	public function __construct()
    	{
    		$this->DB = Core::GetDBInstance();
    	}
    	
    	/**
    	 * Verify Calling Instance
    	 */
    	private function VerifyCallingInstance()
    	{
    		$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array(
    			$this->GetArg('farmid')
    		));
    		
    		$instanceinfo = $this->DB->GetRow("SELECT * FROM farm_instances WHERE instance_id=?", array(
    			$this->GetArg('instanceid')
    		));
    		
    		if (!$instanceinfo || !$farminfo)
    			return false;
    		
    		if (!$farminfo || $farminfo['hash'] != $this->GetArg('authhash') || $instanceinfo['farmid'] != $farminfo['id'])
    		{
    			throw new Exception(sprintf(_("Cannot verify the instance you are making request from. Make sure that farmid (%s), instance-id (%s) and auth-hash (%s) pamaters are valid."),
    				$this->GetArg('farmid'), $this->GetArg('instanceid'), $this->GetArg('authhash')
    			));
    		}
    		
    		return true;
    	}
    	
    	/**
    	 * Query Environment object and return result;
    	 */
    	public function Query($operation, array $args)
		{
			// Set Args array
			$this->Args = array_change_key_case($args, CASE_LOWER);				
    	  	
			// Get Method name by operation
			$method_name = str_replace(" ", "", ucwords(str_replace("-", " ", $operation)));
			
			// Check method
			if (method_exists($this, $method_name))
			{
				//
				// Verify Calling Instance
				//
				if (!$this->VerifyCallingInstance())
					return false;
				
				// Call method
				try
				{
					$result = call_user_func(array($this, $method_name));
					if ($result instanceof DOMDocument)
					{
						return $result->saveXML();
					}
					else
						throw new Exception(sprintf("%s:%s() returns invalid response. DOMDocument expected.",
							get_class($this),
							$method_name
						));
				}
				catch(Exception $e)
				{
					throw new Exception(sprintf(_("Cannot retrieve environment by operation '%s': %s"), 
						$operation, 
						$e->getMessage()
					));
				}
			}
			else
				throw new Exception(sprintf(_("Operation '%s' not supported"), $operation));
		}
    	  
		protected function GetArg($name)
		{
			return $this->Args[strtolower($name)];
		}
		
		/**
		 * Create Base DOMDocument for response
		 * @return DOMDocument
		 */
		protected function CreateResponse()
		{
			$DOMDocument = new DOMDocument('1.0', 'utf-8');
			$DOMDocument->loadXML('<response></response>');
			
			return $DOMDocument;
		}
    }
?>