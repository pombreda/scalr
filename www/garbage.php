<? 
	require("src/prepend.inc.php"); 
	
	$display["title"] = "Delete unused objects";
	
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = "Requested page cannot be viewed from admin account";
		UI::Redirect("index.php");
	}
	
	if ($_POST)
	{
		$remove_items = serialize(array("buckets" => $_POST['buckets'], "keypairs" => $_POST["keypairs"]));
		
		$db->Execute("REPLACE INTO garbage_queue SET clientid=?, data=?", array($_SESSION['uid'], $remove_items));
		
		$okmsg = "Items removal has been scheduled. They will be deleted in approximatey 10 minutes.";
		UI::Redirect("index.php");
	}
			
	// Create AmazonEC2 cleint object
    $AmazonEC2Client = new AmazonEC2($_SESSION["aws_private_key"], $_SESSION["aws_certificate"]);
    
    // Create Amazon s3 client object
    $AmazonS3 = new AmazonS3($_SESSION['aws_accesskeyid'], $_SESSION['aws_accesskey']);
    
    // Get list of all user buckets
    $buckets = $AmazonS3->ListBuckets();
    foreach ($buckets as $bucket)
    {
    	// Check is bucket created by scarl all scarl buckets has name FARM-[FARMID]-[AWS_ACCOUNT_ID]
    	preg_match("/^FARM-([0-9]+)-{$_SESSION['aws_accountid']}$/", $bucket->Name, $matches);
    	if ($matches[1])
    	{
    		// Check is bucked used by scarl or no
    		$farmname = $db->GetOne("SELECT name FROM farms WHERE id=?", 
    			array($matches[1], $_SESSION["uid"])
    		);
    		
    		if (!$farmname)
    			$garbage_backets[] = array("name" => $bucket->Name);
    	}
    }
    
    // Get list of keypairs
    $key_pairs = $AmazonEC2Client->DescribeKeyPairs();
    if ($key_pairs instanceof stdClass && $key_pairs->keySet)
    {
    	$key_pairs = $key_pairs->keySet->item;
    	if ($key_pairs instanceof stdClass)
    		$key_pairs = array($key_pairs);
    	
    	foreach ($key_pairs as $key_pair)
    	{
    		preg_match("/^FARM-([0-9]+)$/", $key_pair->keyName, $matches);
	    	if ($matches[1])
	    	{
	    		// Check is bucked used by scarl or no
	    		$farmname = $db->GetOne("SELECT name FROM farms WHERE id=?", 
	    			array($matches[1], $_SESSION["uid"])
	    		);
	    		
	    		if (!$farmname)
    				$garbage_keypairs[] = array("name" => $key_pair->keyName);
	    	}
    	}
    }

   	$display["keypairs"] = $garbage_keypairs;
   	$display["buckets"] = $garbage_backets;
    	
   	$display["help"] = "This tool allows you to delete objects that are not used by any of your farms.";
   	
	require("src/append.inc.php");
?>