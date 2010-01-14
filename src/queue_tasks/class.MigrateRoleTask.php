<?
	/**
	 * Migrate role from one region to another
	 *
	 */
	class MigrateRoleTask extends Task
	{
		public $RoleID;
		public $PrototypeRoleID;
		
		function __construct($roleid, $prototyperoleid)
		{
			$this->RoleID = $roleid;
			$this->PrototypeRoleID = $prototyperoleid;
			$this->DB = Core::GetDBInstance();
		}
		
		private function Log($message)
		{
			print $message."<br>";
			
			$this->DB->Execute("INSERT INTO rebundle_log SET roleid=?, dtadded=NOW(), message=?", array($this->RoleID, $message));
		}
		
		public function Run()
		{
			$DB = Core::GetDBInstance();
			
			$roleinfo = $DB->GetRow("SELECT * FROM roles WHERE id=?", array($this->RoleID));
			$proto_roleinfo = $DB->GetRow("SELECT * FROM roles WHERE id=?", array($this->PrototypeRoleID));

			$this->Log(sprintf(_("Migrating role %s to %s region"), $proto_roleinfo['name'], $roleinfo['region']));
			
			// Init base objects
			$Client = Client::Load($roleinfo['clientid']);
			$AmazonS3 = new AmazonS3($Client->AWSAccessKeyID, $Client->AWSAccessKey);
			$AmazoneEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($proto_roleinfo['region']));
			$AmazoneEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
			
			$eu_bucket = "scalr-images-eu-{$Client->AWSAccountID}";
			$us_bucket = "scalr-images-us-{$Client->AWSAccountID}";
			
			if ($roleinfo['region'] == AWSRegions::US_EAST_1)
			{
				$dest_bucket = $us_bucket;
				$iseu = false;
			}
			else
			{
				$dest_bucket = $eu_bucket;
				$iseu = true;
			}
			
			try
			{
				$AmazonS3->CreateBucket($dest_bucket, $iseu);
			}
			catch(Exception $e){ }
			
			try
			{
				// Get information about prototype role from Amazon
				$DescribeImagesType = new DescribeImagesType();
				$DescribeImagesType->imagesSet->item[] = array("imageId" => $proto_roleinfo['ami_id']);
				$image = $AmazoneEC2Client->DescribeImages($DescribeImagesType);
			}
			catch(Exception $e)
			{
				$msg = sprintf(_("Cannot get information about prototype role. %s"), $e->getMessage());
				
				$DB->Execute("UPDATE roles SET iscompleted='2', `replace`='', fail_details=? WHERE id=? AND iscompleted='0'", 
					array($msg, $this->RoleID)
				);
				
				$this->Log($msg);
				return true;
			}
			
			preg_match("/^([^\/]+)\/(.*?)$/si", $image->imagesSet->item->imageLocation, $matches);
			$source_bucket = $matches[1];
			
			$source_manifest_path = $matches[2];
			
			if (stristr($source_manifest_path, "/"))
				$prefix = dirname($source_manifest_path);	
			else
				$prefix = str_replace(".manifest.xml", "", $source_manifest_path);			
			
			try
			{
				$res = $AmazonS3->ListBucket($source_bucket, $prefix);
			}
			catch(Exception $e)
			{
				$msg = sprintf(_("Cannot get files list from S3. %s"), $e->getMessage());
				
				$DB->Execute("UPDATE roles SET iscompleted='2', `replace`='', fail_details=? WHERE id=? AND iscompleted='0'", 
					array($msg, $this->RoleID)
				);
				
				$this->Log($msg);
				return true;
			}
			
			$mappings_file = @file("http://s3.amazonaws.com/ec2-downloads/mappings.csv");
			$mappings = array();
			for ($i = 1; $i < count($mappings_file); $i++)
			{
				$chunks = explode(",", $mappings_file[$i]);
				$mappings[] = array("us-east-1" => $chunks[0], "eu-west-1" => $chunks[1]);
			}
			
		    foreach ($res as $file)
		    {
		    	if ($file->Key == $prefix)
		    		continue;
		    	
		    	if (stristr($file->Key, "manifest"))
		    	{
		    		$manifest = $AmazonS3->DownloadObject($file->Key, $source_bucket);
		    		$mhash = md5($manifest);
		    		
		    		foreach ($mappings as $mapping)
		    		{
		    			$repl_from[] = $mapping[$proto_roleinfo['region']];
		    			$repl_to[] = $mapping[$roleinfo['region']];
		    		}
		    		
		    		$manifest = str_replace($repl_from, $repl_to, $manifest);
		    		if (md5($manifest) != $mhash)
		    		{
		    			$sxml = simplexml_load_string($manifest);
			    		$mc = $sxml->xpath("//machine_configuration");
			    		if ($mc[0])
			    			$mc = $mc[0]->asXML();
			    		
			    		$im = $sxml->xpath("//image");
			    		$im = $im[0]->asXML();
			    		
			    		$data = $mc+$im;  		
			    		
			    		$pkeyid = openssl_get_privatekey($Client->AWSPrivateKey);
						
						// compute signature
						openssl_sign($data, &$signature, $pkeyid, OPENSSL_ALGO_SHA1);
						
						
						$signature = bin2hex($signature);
						
						// free the key from memory
						openssl_free_key($pkeyid);
			    		
						$manifest = preg_replace("/\<signature\>(.*?)\<\/signature\>/si", "<signature>{$signature}</signature>", $manifest);
		    		}
		    		
		    		$this->Log(sprintf(_("Creating manifest")));
		    		
		    		$full_path_to_manifest = "{$dest_bucket}/{$file->Key}";
		    		
		    		//$AmazonS3->CreateObject($file->Key, $dest_bucket, $manifest, "text/xml", "public-read", false);
		    	}
		    	else
		    	{
		    		$this->Log(sprintf(_("Copying {$file->Key}")));
		    		
		    		//$AmazonS3->CopyObject($file->Key, $source_bucket, $file->Key, $dest_bucket);
		    	}
		    }
		    
		    $this->Log(sprintf(_("Registering new AMI: {$full_path_to_manifest}")));
		    
		    // Create AmazonEC2 client for new region
		    $AmazoneEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($roleinfo['region']));
			$AmazoneEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
		    
		    $result = $AmazoneEC2Client->RegisterImage($full_path_to_manifest);
		    
		    $ami_id = $result->imageId;
		    
		    var_dump($result);
		}
	}
?>