<? 
	require("src/prepend.inc.php"); 
	$display["title"] = _("Shared roles&nbsp;&raquo;&nbsp;View");
	
	if (!Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::SCALR_ADMIN))
	{
		$errmsg = _("You have no permissions for viewing requested page");
		UI::Redirect("index.php");
	}
	   
	if ($req_task == "add")
	{
		UI::Redirect("shared_roles_edit.php");
	}
	elseif ($req_task == "delete")
	{
	    //
	}
	
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
							$alias = ROLE_BEHAVIORS::APACHE;
						elseif (stristr($name, 'lb-nginx'))
							$alias = ROLE_BEHAVIORS::NGINX;
						elseif (stristr($name, 'mysql'))
							$alias = ROLE_BEHAVIORS::MYSQL;
						elseif (stristr($name, 'base'))
							$alias = ROLE_BEHAVIORS::BASE;
						elseif (stristr($name, 'memc'))
							$alias = ROLE_BEHAVIORS::MEMCACHED;	
						
						if ($_POST[$name] == 'empty-db')
						{
							$alias = ROLE_BEHAVIORS::MYSQL;
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
								$db->Execute("INSERT INTO role_security_rules SET `role_id`=?, `rule`=?", array(
									$id, $rule
								));
							}
							
							if ($alias == ROLE_BEHAVIORS::MYSQL)
							{
								$db->Execute("INSERT INTO role_security_rules SET `role_id`=?, `rule`=?", array(
									$id, 'tcp:3306:3306:0.0.0.0/0'
								));
							}
							elseif ($alias == ROLE_BEHAVIORS::NGINX || $alias == ROLE_BEHAVIORS::APACHE)
							{
								$db->Execute("INSERT INTO role_security_rules SET `role_id`=?, `rule`=?", array(
									$id, 'tcp:80:80:0.0.0.0/0'
								));
								
								$db->Execute("INSERT INTO role_security_rules SET `role_id`=?, `rule`=?", array(
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
							if ($alias == ROLE_BEHAVIORS::NGINX)
							{
								$db->Execute("INSERT INTO role_parameters SET
									name	= 'Nginx HTTPS Vhost Template',
									type	= 'textarea',
									isrequired	= '1',
									defval	= ?,
									allow_multiple_choice = '0',
									options	= '',
									role_id	= ?,
									hash	= 'nginx_https_host_template',
									issystem= '1'
								", array(
									$val,
									$id
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
	
	require("src/append.inc.php"); 
	
?>