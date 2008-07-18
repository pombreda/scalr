<?	
	class CustomException extends Exception
	{ 
		
		protected $message = "Sorry, an error occurred. Application administrator was notified.";
		
		/**
		* Constructor
		* @access public
		* @var string $message Exception message
		* @var int $code 0 - Error, 1 - Warning
		* @var bool $dolog Either exception should be logged or not. Used to prevent infinite loops inside Log.
		* @return void
		*/
 		function __construct($message, $code)
 		{
 			parent::__construct($message, $code);
 		    
 		    $Smarty = Core::GetSmartyInstance();
		
 			if (CONFIG::$DEBUG_APP)
 				$bt = $this->Backtrace();

 		    if ($Smarty && !defined("NO_TEMPLATES"))
 		    {
 			    $Smarty->assign(array("backtrace" => $bt, "message" => $message, "lang" => LOCALE));
			    $Smarty->display("exception.tpl");
 		    }
 		    
 		    exit();
		}
		
		/**
		* Return result of debug_backtrace
		* @access public
		* @return string HTML'ed backtrace
		*/
		protected function Backtrace()
		{
			
			$backtrace = debug_backtrace();
			foreach ($backtrace as $bt) 
			{
				$args = '';
				foreach ($bt['args'] as $a) 
				{
					if (!empty($args)) {
						$args .= ', ';
					}
					switch (gettype($a)) {
					case 'integer':
					case 'double':
						$args .= $a;
						break;
					case 'string':
						$a = htmlspecialchars(substr($a, 0, 64)).((strlen($a) > 64) ? '...' : '');
						$args .= "\"$a\"";
						break;
					case 'array':
						$args .= 'Array('.count($a).')';
						break;
					case 'object':
						$args .= 'Object('.get_class($a).')';
						break;
					case 'resource':
						$args .= 'Resource('.strstr($a, '#').')';
						break;
					case 'boolean':
						$args .= $a ? 'True' : 'False';
						break;
					case 'NULL':
						$args .= 'Null';
						break;
					default:
						$args .= 'Unknown';
					}
				}
				if ($bt['file'])
					$output .= "
								<li>{$bt['file']}:{$bt['line']}
								<br>{$bt['class']}{$bt['type']}{$bt['function']}($args)
								</li>
								";
				else
					$output .= "
								<li>
								<br>{$bt['class']}{$bt['type']}{$bt['function']}($args)
								</li>
								";
			}
			return "<ul class='backtrace'>$output</ul>";

		}
	}	 
?>