<?php

require_once(LOG4PHP_DIR . '/helpers/LoggerOptionConverter.php');
require_once(LOG4PHP_DIR . '/LoggerFilter.php');

class LoggerFilterCategoryMatch extends LoggerFilter {
		
    /**
     * @var boolean
     */
    var $acceptOnMatch = true;

    /**
     * @var string
     */
    var $stringToMatch = null;
   
    /**
     * @return boolean
     */
    function getAcceptOnMatch() {
        return $this->acceptOnMatch;
    }
    
    /**
     * @param mixed $acceptOnMatch a boolean or a string ('true' or 'false')
     */
    function setAcceptOnMatch($acceptOnMatch) {
        $this->acceptOnMatch = is_bool($acceptOnMatch) ? 
            $acceptOnMatch : 
            (bool)(strtolower($acceptOnMatch) == 'true');
    }
    
    /**
     * @return string
     */
    function getStringToMatch() {
        return $this->stringToMatch;
    }
    
    /**
     * @param string $s the string to match
     */
    function setStringToMatch($s) {
        $this->stringToMatch = $s;
    }

    /**
     * @return integer a {@link LOGGER_FILTER_NEUTRAL} is there is no string match.
     */
    function decide($event) {
        $category = $event->getLoggerName();
        
        if ($category === null or  $this->stringToMatch === null) {
            return LOG4PHP_LOGGER_FILTER_NEUTRAL;
        }
       
        if (preg_match($this->stringToMatch, $category)) {
            return $this->acceptOnMatch ? LOG4PHP_LOGGER_FILTER_ACCEPT : LOG4PHP_LOGGER_FILTER_NEUTRAL; 
        } else {
        	return LOG4PHP_LOGGER_FILTER_DENY;
        }
    }	
} 