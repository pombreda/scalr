<?php
	class MailEventObserver
	{
		private $Mailer;
		private $Config;
		public $ObserverName = 'Mail';
		
		function __construct()
		{
			$this->Mailer = Core::GetPHPMailerInstance();
			$this->Mailer->From 		= CONFIG::$EMAIL_ADDRESS;
			$this->Mailer->FromName 	= CONFIG::$EMAIL_NAME;
			
			$this->DB = Core::GetDBInstance(null, true);
		}

		public function SetConfig($config)
		{
			$this->Config = $config;
			
			$this->Mailer->ClearAddresses();
			$this->Mailer->AddAddress($this->Config->GetFieldByName("EventMailTo")->Value);
		}
		
		public static function GetConfigurationForm()
		{
			$ConfigurationForm = new DataForm();
			$ConfigurationForm->SetInlineHelp("");
			
			$ConfigurationForm->AppendField( new DataFormField("IsEnabled", FORM_FIELD_TYPE::CHECKBOX, "Enabled"));
			$ConfigurationForm->AppendField( new DataFormField("EventMailTo", FORM_FIELD_TYPE::TEXT, "E-mail"));
			
			$ReflectionInterface = new ReflectionClass("IEventObserver");
			$events = $ReflectionInterface->getMethods();
			
			$ConfigurationForm->AppendField(new DataFormField("", FORM_FIELD_TYPE::SEPARATOR, "Notify about following events:"));
			
			foreach ($events as $event)
			{
				$name = substr($event->getName(), 2);
				
				$ConfigurationForm->AppendField( new DataFormField(
					"{$event->getName()}Notify", 
					FORM_FIELD_TYPE::CHECKBOX, 
					"{$name}",
					false,
					array(),
					null,
					null,
					EVENT_TYPE::GetEventDescription($name)
					)
				);
			}
			
			return $ConfigurationForm;
		}
		
		public function __call($method, $args)
		{
			// If observer enabled
			if (!$this->Config || $this->Config->GetFieldByName("IsEnabled")->Value == 0)
				return;
				
			$enabled = $this->Config->GetFieldByName("{$method}Notify");
				
			if (!$enabled || $enabled->Value == 0)
				return;
				
			// Event name
			$name = substr($method, 2);
				
			// Event message
			$message = $args[0];
			
			// Set subject
			$this->Mailer->Subject = "{$name} event notification";
			
			// Set body
			$this->Mailer->Body = $message;
			
			// Send mail
			$this->Mailer->Send();
		}
	}
?>