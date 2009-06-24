<?
	require_once('src/prepend.inc.php');
    $display['load_extjs'] = true;	    
	
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = _("Requested page cannot be viewed from admin account");
		UI::Redirect("index.php");
	}
	
	if ($req_farmid)
	{
		$display['grid_query_string'] .= "&farmid={$req_farmid}";
	}
	
	$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($_SESSION['aws_region'])); 
	$AmazonEC2Client->SetAuthKeys($_SESSION["aws_private_key"], $_SESSION["aws_certificate"]);

	if ($req_task == 'release')
	{
		try
		{
			$AmazonEC2Client->ReleaseAddress($req_ip);
			$db->Execute("DELETE FROM elastic_ips WHERE ipaddress=?", array($req_ip));
		}
		catch(Exception $e)
		{
			$errmsg = sprintf(_("Cannot release elastic IP: %s"), $e->getMessage());
		}
		
		if (!$errmsg)
		{
			$okmsg = _("Elastic IP successfully released");
			UI::Redirect("elastic_ips.php");
		}
	}
	
	$display['title'] = 'Elastic IPs';
		
	require_once ("src/append.inc.php");
?>