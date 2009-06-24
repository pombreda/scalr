<?
	interface IMessageSerializer
	{
		public static function Unserialize($xml_message);
		public static function Serialize(ScalrMessage $message);
	}

	class XMLMessageSerializer implements IMessageSerializer
    {
    	public static function Unserialize($xml_message)
    	{
    		$DOMDocuemnt = new DOMDocument('1.0', 'utf-8');
    		$DOMDocuemnt->loadXML($xml_message);
    		
    		if (!class_exists("{$DOMDocuemnt->documentElement->tagName}ScalrMessage"))
    			throw new Exception(sprintf(_("Unknown message %s"), $DOMDocuemnt->documentElement->tagName));
    		
    		$Reflect = new ReflectionClass("{$DOMDocuemnt->documentElement->tagName}ScalrMessage");
    		$message = $Reflect->newInstanceArgs(array(null,null,null,null,null));
    		
    		foreach ($DOMDocuemnt->documentElement->childNodes as $node)
    			$message->{$node->nodeName} = $node->nodeValue; 	
    		    		
    		$message->MessageID = $DOMDocuemnt->documentElement->getAttribute('message-id');
    			
    		return $message;
    	}
    	
    	public static function Serialize(ScalrMessage $message)
    	{
			$Reflect = new ReflectionClass($message);
			$name = str_replace("ScalrMessage", "", $Reflect->getName());
			
			$DOMDocument = new DOMDocument('1.0','utf-8');
			$DOMDocument->loadXML("<{$name} message-id='{$message->MessageID}'></{$name}>");
			
			foreach ($Reflect->getProperties() as $prop)
			{
				
				if ($prop->name != 'MessageID')
				{
					$DOMDocument->documentElement->appendChild(
						$DOMDocument->createElement($prop->name, $Reflect->getProperty($prop->name)->getValue($message))
					);
				}
			}
			
			return $DOMDocument->saveXML();
    	}
    }
?>