<?php

	require_once 'Amazon/SQS/Model/ReceiveMessage.php';
	require_once 'Amazon/SQS/Model/DeleteMessage.php'; 
	require_once 'Amazon/SQS/Model/ListQueues.php';

	class SqsReader
	{
		/**
		 * @var Amazon_SQS_Client
		 */
		private $sqsClient;
		
		private $queueName;
		
		private $queueUrl;
		
		private $maxMsgNumber;
		
		private $buffer;
		
		public function __construct(Amazon_SQS_Client $sqsClient, $queueName, $maxMsgNumber = 10) {
			$this->sqsClient = $sqsClient;
			$this->maxMsgNumber = $maxMsgNumber;
			$this->queueName = $queueName;
			$this->buffer = array();
		}

		/**
		 * @return ScalrMsg
		 * @throws Amazon_SQS_Exception
		 */
		public function peek () {
			if (count($this->buffer) == 0) {
				// Give another portion of messages
				$request = new Amazon_SQS_Model_ReceiveMessage();
				$request->setQueueName($this->queueName);
				$request->setMaxNumberOfMessages($this->maxMsgNumber);
				
				$response = $this->sqsClient->receiveMessage($request);
				$result = $response->getReceiveMessageResult();
				foreach ($result->getMessage() as $sqsMsg) {
					try {
						$scalrMsg = $this->decode($sqsMsg);
						$this->buffer[] = $scalrMsg;
					} catch (ScalrMsgDecodingException $e) {
						// TODO: log here
						printf("Cannot decode message <%s>\n", $sqsMsg->getMessageId());
						$this->remove($sqsMsg->getMessageId());
					}
					
				}
			}
			
			return $this->buffer[0];
		}
		
		/**
		 * @param ScalrMsg $Msg
		 * @throws Amazon_SQS_Exception
		 */
		public function remove ($receiptHandle) {
			$request = new Amazon_SQS_Model_DeleteMessage();
			$request->setQueueName($this->queueName);
			$request->setReceiptHandle($receiptHandle);
			
			// Delete from SQS
			$this->sqsClient->deleteMessage($request);
			
			// Delete from buffer
			foreach ($this->buffer as $i => $msg) {
				if ($msg->getReceiptHandle() == $receiptHandle) {
					array_splice($this->buffer, $i, 1);
					break;
				}
			}
		}
		
		/**
		 * Decodes Amazon message body, and marshall it to scalr message 
		 *
		 * @param Amazon_SQS_Model_Message $msg
		 */
		private function decode (Amazon_SQS_Model_Message $msg) {
			if (false === ($body = base64_decode($msg->getBody()))) {
				throw new ScalrMsgDecodingException("Failed to base64 decode message body");
			}
			print "\n\n$body\n\n";
			if (false === ($body = json_decode($body, true))) {
				throw new ScalrMsgDecodingException("Failed to json decode message body");
			}
			if (!class_exists($body['type'])) {
				throw new ScalrMsgDecodingException(sprintf("Class %s not found", $body['type']));
			}
			
			$ref = new ReflectionClass($body['type']);
			$scalrMsg = $ref->newInstanceArgs(array($msg->getMessageId(), $msg->getReceiptHandle()));
			
			unset($body['type']);
			foreach ($body as $property => $value) {
				$scalrMsg->{$property} = $value;
			}
			
			return $scalrMsg;
		}
	}
	
	class ScalrMsgDecodingException extends Exception { }
?>