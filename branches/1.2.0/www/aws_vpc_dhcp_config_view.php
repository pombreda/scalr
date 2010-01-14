<?php 
	require("src/prepend.inc.php"); 
		
	if ($_SESSION["uid"] == 0)
	   UI::Redirect("index.php");
   
	   
	$Client = Client::Load($_SESSION['uid']);
	
	$display["title"] = _("Tools&nbsp;&raquo;&nbsp;Amazon Web Services&nbsp;&raquo;&nbsp;Amazon VPC&nbsp;&raquo;&nbsp;DHCP options set configuration");	
		
	try 
	{	
		
		$AmazonVPCClient = AmazonVPC::GetInstance(AWSRegions::GetAPIURL($_SESSION['aws_region'])); 
		$AmazonVPCClient->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
		
		// Rows	
		$dhcpConnection = new DescribeDhcpOptions(array($req_id)); // filter
		$aws_response = $AmazonVPCClient->DescribeDhcpOptions($dhcpConnection);	
		
		$rows = (array)$aws_response->dhcpOptionsSet;
		
		if ($rows["item"] instanceof stdClass)
			$rows["item"] = array($rows["item"]); // convert along subnet record to array
		
		$rowz = array();		
		
		foreach ($rows['item'] as $row)						
			$rowz[]=(array)$row;
		
		// convert soap response to structured data array (stdClass is preseneted)
		for($i = 0; $i<count($rowz); $i++)
			{										
				if ($rowz[$i]["dhcpConfigurationSet"]->item instanceof stdClass) // if recieve one element
				{			
					$rowz[$i]["dhcpConfigurationSet"]->item = array($rowz[$i]["dhcpConfigurationSet"]->item); // convert along  record to array
				}	
				$rowz[$i]["dhcpConfigurationSet"] = $rowz[$i]["dhcpConfigurationSet"]->item;  // item object to array
			
				foreach($rowz[$i]["dhcpConfigurationSet"] as $j => $item)
				{
					if ($item->valueSet->item instanceof stdClass)					
						$item->valueSet->item = array($item->valueSet->item);  // along element to array
										
					$rowz[$i]["dhcpConfigurationSet"][$j] = $item;					
		    	}	
			}
		$options = "";
		foreach ($rowz as $row)
			{ 	
				$options = "";			
				
					foreach($row['dhcpConfigurationSet'] as $set)
					{
						
						$options = $options."{$set->key} = ";				
					
						for($i = 0; $i<count($set->valueSet->item); $i++)
						{					
							$options = $options.$set->valueSet->item[$i]->value;					
							
							if(count($set->valueSet->item) == 1)
								continue;					
							elseif($i<count($set->valueSet->item)-1)	 // form string delivered by , or ;
							{						
								$options = $options.",";						
							}	
						}
						$options = $options."; ";
						
					}
					$display["id"] = $req_id;
					$display["options"] =  $options;
				
			}
	}
	catch(Exception $e)
	{					
		$err[] = $e->getMessage();//Incorrect DHCP ID %s: %s
		UI::Redirect("/aws_vpc_dhcp_view.php");
	}
	require("src/append.inc.php"); 

?>