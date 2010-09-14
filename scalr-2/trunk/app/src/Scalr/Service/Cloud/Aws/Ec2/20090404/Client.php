<?php 

	require_once dirname(__FILE__) . '/DescribeImagesResponse.php';
	require_once dirname(__FILE__) . '/AllocateAddressResponse.php';
	require_once dirname(__FILE__) . '/DescribeAddressesResponse.php';
	require_once dirname(__FILE__) . '/DescribeSecurityGroupsResponse.php';
	require_once dirname(__FILE__) . '/CreateKeyPairResponse.php';
	require_once dirname(__FILE__) . '/DescribeKeyPairsResponse.php';
	require_once dirname(__FILE__) . '/RunInstancesResponse.php';
	require_once dirname(__FILE__) . '/DescribeAvailabilityZonesResponse.php';
	require_once dirname(__FILE__) . '/DescribeInstancesResponse.php';
	
	require_once dirname(__FILE__) . '/../../Transports/Query.php';

	abstract class Scalr_Service_Cloud_Aws_Ec2_20090404_Client extends Scalr_Service_Cloud_Aws_Transports_Query
	{
		function __construct()
		{
			$this->apiVersion = '2009-04-04';
		}
		
		///
		// Other
		///
		
		public function describeAvailabilityZones($zoneName = null)
		{
			$request_args = array(
				"Action" => "DescribeAvailabilityZones", 
			);
			
			if ($zoneName)
				$request_args['ZoneName'] = $zoneName;
				
			$response = $this->Request("GET", "/", $request_args);
			
			return new Scalr_Service_Cloud_Aws_Ec2_20090404_DescribeAvailabilityZonesResponse($response);
		}
		
		
		///
		// Instances 
		///
		
		public function describeInstances($instanceIds = array())
		{
			$request_args = array(
				"Action" => "DescribeInstances", 
			);
			foreach ((array)$instanceIds as $i=>$n)
				$request_args['InstanceId.'.($i+1)] = $n;
				
			$response = $this->Request("GET", "/", $request_args);
			
			return new Scalr_Service_Cloud_Aws_Ec2_20090404_DescribeInstancesResponse($response);
		}
		
		public function runInstances($imageId, $instanceType, $keyName = null, $availZone = null, $securityGroup = array(), $userData = "", 
			$minCount = 1, $maxCount = 1, $kernelId = null, $ramdiskId = null, $monitoring = false)
		{
			
			$request_args = array(
				"Action" 		=> "RunInstances",
				"ImageId"		=> $imageId,
				"MinCount"		=> $minCount,
				"MaxCount"		=> $maxCount,
				"InstanceType"	=> $instanceType
			);
			
			if ($availZone)
				$request_args['Placment.AvailabilityZone'] = $availZone;
			if ($keyName)
				$request_args['KeyName'] = $keyName;
			if ($kernelId)
				$request_args['KernelId']	= $kernelId;
			if ($ramdiskId)
				$request_args['RamdiskId']	= $ramdiskId;
			
			if (!empty($securityGroup))
			{
				$n = 0;
				foreach ((array)$securityGroup as $sg)
				{
					$n++;
					$request_args['SecurityGroup.'.$n]	= $sg;
				}
			}
			
			if ($userData)
			{				
				$request_args["UserData"]	= base64_encode($userData);
				$request_args["Version"]	= "1.0";
				$request_args["Encoding"]	= "base64";
			}
			
			$response = $this->Request("GET", "/", $request_args);
			
			return new Scalr_Service_Cloud_Aws_Ec2_20090404_RunInstancesResponse($response);
		}
		
		
		///
		// Key pairs
		///
		
		public function deleteKeyPair($keyName)
		{
			$request_args = array(
				"Action" => "DeleteKeyPair",
				"KeyName"	=> $keyName 
			);
			
			$response = $this->Request("GET", "/", $request_args);
			
			return ((string)$response->return == 'true') ? true : false;
		}
		
		public function describeKeyPairs($keys = array())
		{
			$request_args = array(
				"Action" => "DescribeKeyPairs", 
			);
			foreach ((array)$keys as $i=>$n)
				$request_args['KeyName.'.($i+1)] = $n;
				
			$response = $this->Request("GET", "/", $request_args);
			
			return new Scalr_Service_Cloud_Aws_Ec2_20090404_DescribeKeyPairsResponse($response);
		}
		
		public function createKeyPair($keyName)
		{
			$request_args = array(
				"Action" => "CreateKeyPair",
				"KeyName"	=> $keyName 
			);
			
			$response = $this->Request("GET", "/", $request_args);
			
			return new Scalr_Service_Cloud_Aws_Ec2_20090404_CreateKeyPairResponse($response);
		}
		
		
		///
		// Security groups
		///
		
		public function authorizeSecurityGroupIngress($groupName, $ipProtocol = null, $fromPort = null, $toPort = null, $cidrIp = null, $sourceSecurityGroupName = null, $sourceSecurityGroupOwnerId = null)
		{
			$request_args = array(
				"Action" => "AuthorizeSecurityGroupIngress",
				"GroupName"	=> $groupName 
			);
			
			if ($cidrIp)
			{
				$request_args['CidrIp'] = $cidrIp;
				$request_args['IpProtocol'] = $ipProtocol;
				$request_args['FromPort'] = $fromPort;
				$request_args['ToPort'] = $toPort;
			}
			else
			{
				$request_args['SourceSecurityGroupName'] = $sourceSecurityGroupName;
				$request_args['SourceSecurityGroupOwnerId'] = $sourceSecurityGroupOwnerId;
			}
				
			$response = $this->Request("GET", "/", $request_args);
			
			return ((string)$response->return == 'true') ? true : false;
		}
		
		public function describeSecurityGroups($groups = array())
		{
			$request_args = array(
				"Action" => "DescribeSecurityGroups", 
			);
			foreach ((array)$groups as $i=>$n)
				$request_args['GroupName.'.($i+1)] = $n;
				
			$response = $this->Request("GET", "/", $request_args);
			
			return new Scalr_Service_Cloud_Aws_Ec2_20090404_DescribeSecurityGroupsResponse($response);
		}
		
		public function createSecurityGroup($name, $description)
		{
			$request_args = array(
				"Action" => "CreateSecurityGroup",
				"GroupName" => $name,
				"GroupDescription" => $description 
			);
				
			$response = $this->Request("GET", "/", $request_args);
			
			return ((string)$response->return == 'true') ? true : false;
		}
		
		
		///
		// Elastic IP addresses
		///
		
		public function releaseAddress($ip)
		{
			$request_args = array(
				"Action" => "ReleaseAddress",
				"PublicIp" => $ip 
			);
				
			$response = $this->Request("GET", "/", $request_args);
			
			return ((string)$response->return == 'true') ? true : false;
		}
		
		public function describeAddresses($ips = array())
		{
			$request_args = array(
				"Action" => "DescribeAddresses", 
			);
			foreach ((array)$ips as $i=>$n)
				$request_args['PublicIp.'.($i+1)] = $n;
				
			$response = $this->Request("GET", "/", $request_args);
			
			return new Scalr_Service_Cloud_Aws_Ec2_20090404_DescribeAddressesResponse($response);
		}
		
		public function allocateAddress()
		{
			$request_args = array(
				"Action" => "AllocateAddress", 
			);
				
			$response = $this->Request("GET", "/", $request_args);
			return new Scalr_Service_Cloud_Aws_Ec2_20090404_AllocateAddressResponse($response);
		}
		
		
		///
		// Images
		///
		
		public function describeImages($executableBy = array(), $imageId = array(), $owner = array()) 
		{
			$request_args = array(
				"Action" => "DescribeImages", 
			);
			foreach ((array)$executableBy as $i=>$n)
				$request_args['ExecutableBy.'.($i+1)] = $n;
			foreach ((array)$imageId as $i=>$n)
				$request_args['ImageId.'.($i+1)] = $n;
			foreach ((array)$owner as $i=>$n)
				$request_args['Owner.'.($i+1)] = $n;
				
			$response = $this->Request("GET", "/", $request_args);
			
			return new Scalr_Service_Cloud_Aws_Ec2_20090404_DescribeImagesResponse($response);
		}
	}

?>