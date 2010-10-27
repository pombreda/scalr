<? 
	require("src/prepend.inc.php"); 
	$display["title"] = _("Shared roles&nbsp;&raquo;&nbsp;View");
	
	if ($_SESSION["uid"] != 0)
	   UI::Redirect("index.php");
	   
	if ($req_task == "add")
	{
		UI::Redirect("shared_roles_edit.php");
	}
	elseif ($req_task == "delete")
	{
	    $info = $db->GetRow("SELECT * FROM roles WHERE ami_id=? AND roletype=?", array($req_ami_id, ROLE_TYPE::SHARED));
	    if ($info)
	    {
	        $db->Execute("DELETE FROM roles WHERE id='{$info['id']}'");
	        $db->Execute("DELETE FROM security_rules WHERE roleid='{$info['id']}'");
	        
	        $okmsg = _("Role successfully unassigned from AMI");
	        UI::Redirect("shared_roles.php");
	    }
	    else 
	       $errmsg = _("Role not found");
	}
	
	$AmazonEC2 = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($_SESSION['aws_region'])); 
	$AmazonEC2->SetAuthKeys(
		APPPATH . "/etc/pk-".CONFIG::$AWS_KEYNAME.".pem", 
		APPPATH . "/etc/cert-".CONFIG::$AWS_KEYNAME.".pem", 
		true
	);
	
	$roles = array(
	'ec2' => 
		array(
			'app-apache',
			'app-apache64',
			'app-rails',
			'app-rails64',
			'mysqllvm',
			'mysqllvm64',
			'base',
			'base64',
			'memcached',
			'memcached64',
			'lb-nginx',
			'lb-nginx64',
			'app-tomcat',
			'app-tomcat6'
		),
	'rds' =>
		array(
			'empty-db'
		)
	);

	$os = array(
		'Ubuntu 8.04' 	=> '',
		'Ubuntu 10.04' 	=> '-ubuntu-10-04',
		'CentOS 5.4'		=> '-centos-5-4'
	);
	
	$platforms = array('ec2', 'rds');
	$aws_regions = array('us-east-1', 'us-west-1', 'eu-west-1', 'ap-southeast-1');
	
	$display['platform'] = ($req_platform) ? $req_platform : 'ec2';
	
	if (in_array($display['platform'], array('ec2','rds')))
		$display['region'] = $_SESSION['aws_region'];
	else
		$display['region'] = '';
		
	$display['roles'] = $roles[$display['platform']];
	$display['platforms'] = $platforms;
	$display['aws_regions'] = $aws_regions;
	$display['os'] = $os;
	
	foreach ($roles[$display['platform']] as $role)
	{
		foreach ($os as $name => $prefix)
		{
			$name = "{$role}{$prefix}";
			$image_id = $db->GetOne("SELECT ami_id FROM roles WHERE name=? AND platform=? AND region=?", array(
				$name, $display['platform'], $display['region']
			));
			$images[$name] = $image_id;
		}
	}
	
	$rules = array(
		'icmp:-1:-1:0.0.0.0/0',
		'tcp:22:22:0.0.0.0/0',
		'udp:161:162:0.0.0.0/0'
	);
	
	if ($_POST)
	{
		foreach ($roles[$display['platform']] as $role)
		{
			foreach ($os as $name => $prefix)
			{
				$name = "{$role}{$prefix}";
				if ($_POST[$name] != $images[$name])
				{
					if (!$images[$name] && $_POST[$name])
					{
						if (stristr($name, 'app-'))
							$alias = ROLE_ALIAS::APP;
						elseif (stristr($name, 'lb-nginx'))
							$alias = ROLE_ALIAS::WWW;
						elseif (stristr($name, 'mysql'))
							$alias = ROLE_ALIAS::MYSQL;
						elseif (stristr($name, 'base'))
							$alias = ROLE_ALIAS::BASE;
						elseif (stristr($name, 'memc'))
							$alias = ROLE_ALIAS::MEMCACHED;	
						
						if ($_POST[$name] == 'empty-db')
						{
							$alias = ROLE_ALIAS::MYSQL;
						}
							
						if (stristr($name, '64'))
						{
							$arch = 'x86_64';
							$itype = 'm1.large';
						}
						else
						{
							$arch = 'i386';
							$itype = 'm1.small';
						}
							
						$db->Execute("INSERT INTO roles SET
							ami_id		= ?,
							name		= ?,
							roletype	= ?,
							clientid	= '0',
							dtbuilt		= NOW(),
							description	= '',
							alias		= ?,
							instance_type	= ?,
							architecture	= ?,
							isstable	= '1',
							region		= ?,
							default_ssh_port	= '22',
							platform	= ?
						", array(
							$_POST[$name],
							$name,
							ROLE_TYPE::SHARED,
							$alias,
							$itype,
							$arch,
							$post_region,
							$post_platform
						));
						
						$id = $db->Insert_ID();
						
						if ($post_platform == SERVER_PLATFORMS::EC2)
						{
							foreach ($rules as $rule)
							{
								$db->Execute("INSERT INTO security_rules SET `roleid`=?, `rule`=?", array(
									$id, $rule
								));
							}
							
							if ($alias == ROLE_ALIAS::MYSQL)
							{
								$db->Execute("INSERT INTO security_rules SET `roleid`=?, `rule`=?", array(
									$id, 'tcp:3306:3306:0.0.0.0/0'
								));
							}
							elseif ($alias == ROLE_ALIAS::APP || $alias == ROLE_ALIAS::WWW)
							{
								$db->Execute("INSERT INTO security_rules SET `roleid`=?, `rule`=?", array(
									$id, 'tcp:80:80:0.0.0.0/0'
								));
								
								$db->Execute("INSERT INTO security_rules SET `roleid`=?, `rule`=?", array(
									$id, 'tcp:443:443:0.0.0.0/0'
								));
							}
	$val = '{literal}server { {/literal}
		  listen       443;
	        server_name  {$host} www.{$host} {$server_alias};
	
	        ssl                  on;
	        ssl_certificate      /etc/aws/keys/ssl/https.crt;
	        ssl_certificate_key  /etc/aws/keys/ssl/https.key;
	
	        ssl_session_timeout  10m;
	        ssl_session_cache    shared:SSL:10m;
	
	        ssl_protocols  SSLv2 SSLv3 TLSv1;
	        ssl_ciphers  ALL:!ADH:!EXPORT56:RC4+RSA:+HIGH:+MEDIUM:+LOW:+SSLv2:+EXP;
	        ssl_prefer_server_ciphers   on;
	{literal}
	        location / {
	            proxy_pass         http://backend;
	            proxy_set_header   Host             $host;
	            proxy_set_header   X-Real-IP        $remote_addr;
	            proxy_set_header   X-Forwarded-For  $proxy_add_x_forwarded_for;
	
	            client_max_body_size       10m;
	            client_body_buffer_size    128k;
	
	          
	            proxy_buffering on;
	            proxy_connect_timeout 15;
	            proxy_intercept_errors on;  
	        }
	    } {/literal}';						
							if ($alias == ROLE_ALIAS::WWW)
							{
								$db->Execute("INSERT INTO role_options SET
									name	= 'Nginx HTTPS Vhost Template',
									type	= 'textarea',
									isrequired	= '1',
									defval	= ?,
									allow_multiple_choice = '0',
									options	= '',
									ami_id	= ?,
									hash	= 'nginx_https_host_template',
									issystem= '1'
								", array(
									$val,
									$_POST[$name]
								));
							}
						}
					}
					else
					{
						
					}
				}
			}
		}
		
		UI::Redirect("/shared_roles.php");
	}
	
	$display['images'] = $images;
	
	/*
	$sql = "SELECT * FROM roles WHERE roletype='".ROLE_TYPE::SHARED."' AND clientid='0' AND region='".$_SESSION['aws_region']."'";
			
	$display["rows"] = $db->GetAll($sql);	

	// Generate DescribeImagesType object
	$DescribeImagesType = new DescribeImagesType();
	foreach ($display["rows"] as &$row)
	{
		if ($row['platform'] == SERVER_PLATFORMS::EC2)
			$DescribeImagesType->imagesSet->item[] = array("imageId" => $row['ami_id']);
	}

	// get information about shared AMIs
	try
	{
		$response = $AmazonEC2->describeImages($DescribeImagesType);
	}
	catch(Exception $e)
	{
		$errmsg = $e->getMessage();
	}
	
	foreach ($display["rows"] as &$row)
	{
		if ($response && $response->imagesSet && $response->imagesSet->item)
		{
			if (!is_array($response->imagesSet->item))
				$response->imagesSet->item = array($response->imagesSet->item);
			
			foreach($response->imagesSet->item as $item)
			{
				if ($item->imageId == $row["ami_id"])
				{
					$row["imageState"] = $item->imageState;
					$row["imageOwnerId"] = $item->imageOwnerId;
					break;
				}
			}
		}
		
		$row["type"] = ROLE_ALIAS::GetTypeByAlias($row['alias']);
		$row['farmsCount'] = $db->GetOne("SELECT COUNT(farmid) FROM farm_roles WHERE ami_id=?", array($row['ami_id']));
	}
	
	$display["page_data_options"] = array();
	
	$display["page_data_options_add"] = true;
	$display["page_data_options_add_querystring"] = "?task=add";
	*/
	
	require("src/append.inc.php"); 
	
?>