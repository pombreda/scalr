<?php

	class Scalr_Cronjob_RotateLogs extends Scalr_System_Cronjob {
    	static function getConfig () {
    		return array(
    			"description" => "Rotate logs table"
    		);
    	}
    	
        private $logger;
        
    	function __construct() {
        	$this->logger = LoggerManager::getLogger(__CLASS__);
        }
        
        function run ($options)
        {
            $db = Core::GetDBInstance();
            
            // Clear old instances log
            $oldlogtime = mktime(date("H"), date("i"), date("s"), date("m"), date("d")-CONFIG::$LOG_DAYS, date("Y"));
            $db->Execute("DELETE FROM logentries WHERE `time` < {$oldlogtime}");

            // Rotate syslog
            if ($db->GetOne("SELECT COUNT(*) FROM syslog") > 1000000)
            {
                $dtstamp = date("dmY");
                $db->Execute("CREATE TABLE syslog_{$dtstamp} (id INT NOT NULL AUTO_INCREMENT,
                              PRIMARY KEY (id))
                              ENGINE=MyISAM SELECT dtadded, message, severity, transactionid FROM syslog;");
                $db->Execute("TRUNCATE TABLE syslog");
                $db->Execute("OPTIMIZE TABLE syslog");
                $db->Execute("TRUNCATE TABLE syslog_metadata");
                $db->Execute("OPTIMIZE TABLE syslog_metadata");
                
                $this->logger->debug("Log rotated. New table 'syslog_{$dtstamp}' created.");
            }
        }
    }
