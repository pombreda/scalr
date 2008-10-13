<?php
	class RESTEventObserver
	{
		private $Config;
		public $ObserverName = 'REST';
		
		function __construct()
		{
			$this->DB = Core::GetDBInstance(null, true);
		}

		public function SetConfig($config)
		{
			$this->Config = $config;
		}
		
		public static function GetConfigurationForm()
		{
			$ConfigurationForm = new DataForm();
			$ConfigurationForm->SetInlineHelp("");
			
			$ConfigurationForm->AppendField( new DataFormField("IsEnabled", FORM_FIELD_TYPE::CHECKBOX, "Enabled"));
			
			$ReflectionInterface = new ReflectionClass("IEventObserver");
			$events = $ReflectionInterface->getMethods();
			
			$ConfigurationForm->AppendField(new DataFormField("", FORM_FIELD_TYPE::SEPARATOR, "Request URL on following events:"));
			
			foreach ($events as $event)
			{
				$name = substr($event->getName(), 2);
				
				$ConfigurationForm->AppendField( new DataFormField(
					"{$event->getName()}NotifyURL", 
					FORM_FIELD_TYPE::TEXT, 
					"{$name} URL",
					false,
					array(),
					null,
					null,
					EVENT_TYPE::GetEventDescription($name))
				);
			}
			
			return $ConfigurationForm;
		}
		
		public function __call($method, $args)
		{
			// If observer enabled
			if (!$this->Config || $this->Config->GetFieldByName("IsEnabled")->Value == 0)
				return;

			$url = $this->Config->GetFieldByName("{$method}NotifyURL"); 

			if (!$url || $url->Value == '')
				return;
				
			// Event message
			$message = urlencode($args[0]);
			
			$ch = @curl_init();

			// set URL and other appropriate options
			@curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
			@curl_setopt($ch, CURLOPT_URL, $url->Value);
			@curl_setopt($ch, CURLOPT_HEADER, false);
			@curl_setopt($ch, CURLOPT_POST, true);
			@curl_setopt($ch, CURLOPT_POSTFIELDS,"event={$method}&message={$message}");
			
			// grab URL and pass it to the browser
			@curl_exec($ch);
			
			$error = curl_error();
			
			if ($error)
				Logger::getLogger(__CLASS__)->error($error);
			
			// close cURL resource, and free up system resources
			@curl_close($ch);
		}
	}
?>