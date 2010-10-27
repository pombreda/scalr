<?php
	$response = array();

	// AJAX_REQUEST;
	$context = 6;
	try
	{
		$enable_json = true;
		include("../../src/prepend.inc.php");		
		
		$req_show_all = true;	
		
		if (isset($req_show_all))
		{
			if ($req_show_all == 'true')
				$_SESSION['sg_show_all'] = true;
			else
				$_SESSION['sg_show_all'] = false;
		}
		
		$Client = Client::Load($_SESSION['uid']);
		
		$AmazonVPCClient = AmazonVPC::GetInstance(AWSRegions::GetAPIURL($_SESSION['aws_region'])); 
		$AmazonVPCClient->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
				
		// Rows	
		$aws_response = $AmazonVPCClient->DescribeDhcpOptions();	
		$rows = (array)$aws_response->dhcpOptionsSet;
		
		if ($rows["item"] instanceof stdClass)
			$rows["item"] = array($rows["item"]); // convert along subnet record to array
		
		$rowz = array();				
		foreach ($rows['item'] as $row)						
			$rowz[]=(array)$row;
		
		// conver soap response to structured data array (stdClass is preseneted)
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
   			
		// diplay list limits
		$start = $req_start ? (int) $req_start : 0;
		$limit = $req_limit ? (int) $req_limit : 20;
		
		$response['total'] = count($rowz);	
		$rowz = (count($rowz) > $limit) ? array_slice($rowz, $start, $limit) : $rowz;
		
		// descending sorting of requested result
		$response["data"] = array();	
		
		if ($req_sort)
		{
			$nrowz = array();
			foreach ($rowz as $row)				
				$nrowz[(string)$row['dhcpOptionsId']] = $row; 			
			
			ksort($nrowz);
			
			if ($req_dir == 'DESC')
				$rowz = array_reverse($nrowz);
			else
				$rowz = $nrowz;	
		}
		// Rows. Create final rows array for script		
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
						$options = $options.",";						
					
				}				
				$options = $options."; "; 				
			}
			$response["data"][] = array(
					"id"					=> (string)$row['dhcpOptionsId'], // have to call only like "id" for correct script work in template
					"options"			=> $options				
					);
		}
	
	}
	catch(Exception $e)
	{
		$response = array("error" => $e->getMessage(), "data" => array());
	}

	print json_encode($response);


?>