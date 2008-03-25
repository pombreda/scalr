<?
	class RotateLogsProcess implements IProcess
    {
        public $ThreadArgs;
        public $ProcessDescription = "Rotate logs table";
        
        public function OnStartForking()
        {
            $db = Core::GetDBInstance(null, true);
            
            // Clear old instances log
            $oldlogtime = mktime(date("H"), date("i"), date("s"), date("m"), date("d")-CF_LOG_DAYS, date("Y"));
            $db->Execute("DELETE FROM logentries WHERE `time` < {$oldlogtime}");
            
            // Rotate syslog
            if ($db->GetOne("SELECT COUNT(*) FROM syslog") > 100000)
            {
                $dtstamp = date("dmY");
                $db->Execute("CREATE TABLE syslog_{$dtstamp} (id INT NOT NULL AUTO_INCREMENT,
                              PRIMARY KEY (id))
                              ENGINE=MyISAM SELECT dtadded, message, severity, dtadded_time FROM syslog;");
                $db->Execute("TRUNCATE TABLE syslog");
                
                Log::Log("Log rotated. New table 'syslog_{$dtstamp}' created.", E_USER_NOTICE);
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