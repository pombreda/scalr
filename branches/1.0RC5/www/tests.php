<?php
	include '../../src/prepend.inc.php';
	
	TaskQueue::Attach(QUEUE_NAME::EBS_MOUNT)->AppendTask(new EBSMountTask('vol-6500e50c'));
	TaskQueue::Attach(QUEUE_NAME::EBS_MOUNT)->AppendTask(new EBSMountTask('vol-1d2eca74'));
	TaskQueue::Attach(QUEUE_NAME::EBS_MOUNT)->AppendTask(new EBSMountTask('vol-b401e4dd'));
?>