<?
	class ScriptingLogMessage
    {
    	public $FarmID;
    	public $EventName;
    	public $InstanceID;
    	public $Message;
    	
    	function __construct($farmid, $event_name, $instance_id, $message)
    	{
    		$this->FarmID = $farmid;
    		$this->EventName = $event_name;
    		$this->InstanceID = $instance_id;
    		$this->Message = $message;
    	}
    }
?>