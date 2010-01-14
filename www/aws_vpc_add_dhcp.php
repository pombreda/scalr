<?php

	require("src/prepend.inc.php"); 
	
	if ($_SESSION["uid"] == 0)
	   UI::Redirect("index.php");
	
	$Client = Client::Load($_SESSION['uid']);
	
	$display["title"] = _("Tools&nbsp;&raquo;&nbsp;Amazon Web Services&nbsp;&raquo;&nbsp;Amazon VPC&nbsp;&raquo;&nbsp;Create DHCP options set");	
		
	$AmazonVPCClient = AmazonVPC::GetInstance(AWSRegions::GetAPIURL($_SESSION['aws_region'])); 
	$AmazonVPCClient->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
		    
	if($_POST)
	{	
		try
		{	
			$options = array();
			if($req_domainName)
			{
				$req_domainName= trim($req_domainName);
				$dhcpConfigurationDomainName = new DhcpConfigurationItemType("domain-name",array($req_domainName));
				$options[] = $dhcpConfigurationDomainName;
			}
			if($req_domainNameServers)
			{
				$valueSetDomainNameServers = FormValueSetFromString($req_domainNameServers);
				$dhcpConfigurationDomainNameServers = new DhcpConfigurationItemType("domain-name-servers",$valueSetDomainNameServers);
				$options[] = $dhcpConfigurationDomainNameServers;
			}
			if($req_ntpServers)
			{		
				$valueSetNtpServers = FormValueSetFromString($req_ntpServers);
				$dhcpConfigurationNtpServers = new DhcpConfigurationItemType("ntp-servers",$valueSetNtpServers);
				$options[] = $dhcpConfigurationNtpServers;
			}
			if($req_netBiosNameServers)
			{			
				$valueSetNetBiosNameServers = FormValueSetFromString($req_netBiosNameServers);
				$dhcpConfigurationNetBiosNameServers = new DhcpConfigurationItemType("netbios-name-servers",$valueSetNetBiosNameServers);
				$options[] = $dhcpConfigurationNetBiosNameServers;
			}
			if($req_netBiosType)
			{			
				$valueSetNetBiosType = FormValueSetFromString($req_netBiosType);
				$dhcpConfigurationNetBiosType = new DhcpConfigurationItemType("netbios-node-type",$valueSetNetBiosType);
				$options[] = $dhcpConfigurationNetBiosType;
			}
			
			if(count($options)<1)
			{	
				$errmsg = "Enter one or more parameters";
				UI::Redirect("aws_vpc_add_dhcp.php");
			}
		
			$AmazonVPCClient->CreateDhcpOptions(new CreateDhcpOptions($options));	

			$okmsg = "DHCP options set created successfully";	
			UI::Redirect("aws_vpc_dhcp_view.php");
		}
		catch(Exception $e)
		{
			$err[] = $e->getMessage();//Cannot create DHCP options set: %s
			UI::Redirect("aws_vpc_dhcp_view.php");
		}
	}	
	
	require("src/append.inc.php"); 	

	// function FormValueSetFromString(string) converts 
	// string with Ips to array of Ips for valueSet
	
	function FormValueSetFromString($string)
	{	
		$valueSet = array();
		$valueSet = explode(',', $string);
				for($i = 0; $i<count($valueSet); $i++)
				{									
					$valueSet[$i] = trim($valueSet[$i]);					
				}
		return $valueSet;
	}
	
?>


 