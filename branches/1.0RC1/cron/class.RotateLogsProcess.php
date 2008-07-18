<?
	class RotateLogsProcess implements IProcess
    {
        public $ThreadArgs;
        public $ProcessDescription = "Rotate logs table";
        public $Logger;
        
    	public function __construct()
        {
        	// Get Logger instance
        	$this->Logger = LoggerManager::getLogger(__CLASS__);
        }
        
        public function OnStartForking()
        {
            $db = Core::GetDBInstance(null, true);
            
            // Clear old instances log
            $oldlogtime = mktime(date("H"), date("i"), date("s"), date("m"), date("d")-CONFIG::$LOG_DAYS, date("Y"));
            $db->Execute("DELETE FROM logentries WHERE `time` < {$oldlogtime}");
            
            // Rotate syslog
            if ($db->GetOne("SELECT COUNT(*) FROM syslog") > 1000000)
            {
                $dtstamp = date("dmY");
                $db->Execute("CREATE TABLE syslog_{$dtstamp} (id INT NOT NULL AUTO_INCREMENT,
                              PRIMARY KEY (id))
                              ENGINE=MyISAM SELECT dtadded, message, severity, dtadded_time FROM syslog;");
                $db->Execute("TRUNCATE TABLE syslog");
                
                $this->Logger->debug("Log rotated. New table 'syslog_{$dtstamp}' created.");
            }
        }
        
        public function OnEndForking()
        {
            
        }
        
        public function StartThread($farminfo)
        {
            
        }
    }
?>