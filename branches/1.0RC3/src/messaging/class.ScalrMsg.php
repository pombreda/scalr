<?php

	class ScalrMsgException extends Exception { }

	class ScalrMsg {
		private
			$messageId,
			$receiptHandle; 
			
		private
			$senderType,
			$senderId,
			$timestamp;
		
		public function __construct ($messageId, $receiptHandle) {
			$this->messageId = $messageId;
			$this->receiptHandle = $receiptHandle;
		}
			
		public function __set ($name, $value) {
			switch ($name) {
				case "timestamp":
					$this->timestamp = strtotime($value);
					break;
				
				default:
					$this->{$name} = $value;
			}
		}

		public function getMessageId () {
			return $this->messageId;
		}
		
		public function getReceiptHandle () {
			return $this->receiptHandle;
		}
		
		public function getSenderType () {
			return $this->senderType;
		}
		
		public function getSenderId () {
			return $this->senderId;
		}
		
		public function getTimestamp () {
			return $this->timestamp;
		}
	}
	
	/**
	 * TODO: description
	 */
	class HostInitMsg extends  ScalrMsg {
		protected $sshPublicKey;
		
		protected $privateIP;
		
		public function getSshPublicKey () {
			return $this->sshPublicKey;
		}
		
		public function getPrivateIP () {
			return $this->privateIP;
		}
	}
	
	/**
	 * TODO: description
	 */
	class HostUpMsg extends ScalrMsg {
	}
	
	/**
	 * TODO: description
	 */
	class QueryEnvironmentMsg extends ScalrMsg {
		
		protected $queryId;
		
		protected $keys;
		
		public function getQueryId () {
			return $this->queryId;
		}
		
		public function getKeys () {
			return $this->keys;
		}
	}

?>