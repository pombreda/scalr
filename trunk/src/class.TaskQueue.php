<?
	class TaskQueue
	{
		/**
		 * Queue instances
		 *
		 * @var array
		 */
		private static $Instances = array();
		
		/**
		 * Queue name
		 *
		 * @var string
		 */
		private $QueueName;
		
		/**
		 * Intsance of ReflectionClass("QueueTask");
		 *
		 * @var ReflectionClass
		 */
		private $ReflectionTask;
		
		/**
		 * Attach to queue with specified name
		 *
		 * @param string $queue_name
		 * @return TaskQueue
		 */
		public static function Attach ($queue_name)
		{
			if (self::$Instances[$queue_name] === null)
			{
				self::$Instances[$queue_name] = new TaskQueue($queue_name);
			}
			return self::$Instances[$queue_name];
		}
		
		/**
		 * Constructor
		 *
		 * @param unknown_type $queue_name
		 */
		public function __construct($queue_name)
		{
			$this->QueueName = $queue_name;
			$this->DB = Core::GetDBInstance(null, true);
			$this->ReflectionTask = new ReflectionClass("Task");
		}
		
		/**
		 * Returns queue size.
		 *
		 * @return integer
		 */
		public function Size()
		{
			return $this->DB->GetOne("SELECT COUNT(*) FROM task_queue WHERE queue_name=?", 
						array($this->QueueName)
					);
		}
		
		/**
		 * Inserts the specified element into this queue, if possible.
		 *
		 * @return bool
		 */
		public function Put(Task $Task)
		{
			return $this->DB->Execute("INSERT INTO task_queue SET
				queue_name	= ?,
				data		= ?,
				dtadded		= NOW()
			", array($this->QueueName, serialize($Task)));
		}
		
		/**
		 * Retrieves and removes the head of this queue, or null if this queue is empty.
		 *
		 * @return Task
		 */
		public function Poll()
		{
			$Task = $this->Peek();
			if ($Task === NULL)
				return NULL;
			
			$this->DB->Execute("DELETE FROM task_queue WHERE id=?", array($Task->ID));
			
			return $Task;
		}
		
		/**
		 * Retrieves and removes the head of this queue. 
		 * This method differs from the poll method in that it throws an exception if this queue is empty. 
		 *
		 * @return Task
		 */
		public function Remove()
		{
			$Task = $this->Poll();
			if ($Task === NULL)
				throw new Exception("Queue '{$this->QueueName}' is empty");
			
			return $Task;
		}
		
		/**
		 * Retrieves, but does not remove, the head of this queue, returning null if this queue is empty.
		 *
		 * @return Task
		 */
		public function Peek()
		{
			$dbtask = $this->DB->GetRow("SELECT * FROM task_queue WHERE queue_name=? ORDER BY id ASC",
				array($this->QueueName)
			);
			if (!$dbtask)
				return NULL;
				
			$Task = unserialize($dbtask['data']);
			$Task->ID = $dbtask["id"];
			
			return $Task;
		}
		
		/**
		 * Retrieves, but does not remove, the head of this queue. 
		 * This method differs from the peek method only in that 
		 * it throws an exception if this queue is empty. 
		 *
		 * @return Task
		 */
		public function Element()
		{
			$Task = $this->Peek();
			if ($Task === NULL)
				throw new Exception("Queue '{$this->QueueName}' is empty");
			
			return $Task;
		}
	}
	
	/**
	 * Task for fire deffered event
	 *
	 */
	class FireDeferredEventTask extends Task
	{
		public $EventID;
		
		function __construct($eventid)
		{
			$this->EventID = $eventid;
		}
	}
	
	/**
	 * Task for DNS zone creation
	 *
	 */
	class CreateDNSZoneTask extends Task
	{
		public $ZoneID;
		
		function __construct($zoneid)
		{
			$this->ZoneID = $zoneid;
		}
	}
	
	/**
	 * Task for DNS zone deletion
	 *
	 */
	class DeleteDNSZoneTask extends Task
	{
		public $ZoneID;
		
		function __construct($zoneid)
		{
			$this->ZoneID = $zoneid;
		}
	}
	
	/**
	 * Abstract task
	 *
	 */
	abstract class Task
	{		
		/**
		 * Task ID
		 *
		 * @var integer
		 */
		public $ID;		
	}
?>