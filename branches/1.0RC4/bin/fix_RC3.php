<?php
	require_once(dirname(__FILE__).'/../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$ScalrUpdate = new ScalrUpdate();
	$ScalrUpdate->Run();
	
	class ScalrUpdate
	{
		function Run()
		{
			$this->UpdateVhosts();
		}
		
		function UpdateVhosts()
		{
			global $db;
			
			$apache_http_host_template = '
<VirtualHost *:80>
	ServerAlias www.{$host} {$host} {$server_alias}
	ServerAdmin {$server_admin}
	DocumentRoot {$document_root}
	ServerName www.{$host}
	CustomLog {$logs_dir}/http-{$host}-access.log combined
	ScriptAlias /cgi-bin/ {$document_root}/cgi-bin/
</VirtualHost>
	';
	
	$apache_https_host_template = '
<IfModule mod_ssl.c>
        <VirtualHost *:443>
			ServerName {$host}
			ServerAlias www.{$host} {$host} {$server_alias}
			ServerAdmin {$server_admin}
			DocumentRoot {$document_root}
			CustomLog {$logs_dir}/http-{$host}-access.log combined

			ErrorLog {$logs_dir}/http-{$host}-error.log
			LogLevel warn

			SSLEngine on

			SSLProtocol all -SSLv2
			SSLCipherSuite ALL:!ADH:!EXPORT:!SSLv2:RC4+RSA:+HIGH:MEDIUM:+LOW
			SSLCertificateFile /etc/aws/keys/ssl/https.crt
			SSLCertificateKeyFile /etc/aws/keys/ssl/https.key

			ScriptAlias /cgi-bin/ /var/www/cgi-bin/
			SetEnvIf User-Agent ".*MSIE.*" nokeepalive ssl-unclean-shutdown
        </VirtualHost>
</IfModule>
	';
	
	$nginx_https_host_template = '
{literal}server { {/literal}
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
    } {/literal}
	';
	
			// Add params to app roles
			$roles = $db->Execute("SELECT * FROM ami_roles WHERE alias=? AND iscompleted='1'", array(ROLE_ALIAS::APP));
			while ($role = $roles->FetchRow())
			{
				$db->Execute("INSERT INTO role_options SET 
		    		name=?, type=?, isrequired=?, defval=?, allow_multiple_choice=?, options=?, ami_id=?, hash=?
		    	", array(
		    		// For insertion
		    		"Apache HTTP Vhost Template",
		    		"textarea",
		    		1,
		    		trim($apache_http_host_template),
		    		0,
		    		"",
		    		$role['ami_id'],
		    		'apache_http_vhost_template',
		    	));
		    	
		    	$db->Execute("INSERT INTO role_options SET 
		    		name=?, type=?, isrequired=?, defval=?, allow_multiple_choice=?, options=?, ami_id=?, hash=?
		    	", array(
		    		// For insertion
		    		"Apache HTTPS Vhost Template",
		    		"textarea",
		    		1,
		    		trim($apache_https_host_template),
		    		0,
		    		"",
		    		$role['ami_id'],
		    		'apache_https_vhost_template',
		    	));
			}
			
			// Add params to www roles
			$roles = $db->Execute("SELECT * FROM ami_roles WHERE alias=? AND iscompleted='1'", array(ROLE_ALIAS::WWW));
			while ($role = $roles->FetchRow())
			{
				$db->Execute("INSERT INTO role_options SET 
		    		name=?, type=?, isrequired=?, defval=?, allow_multiple_choice=?, options=?, ami_id=?, hash=?
		    	", array(
		    		// For insertion
		    		"Nginx HTTPS Vhost Template",
		    		"textarea",
		    		1,
		    		trim($nginx_https_host_template),
		    		0,
		    		"",
		    		$role['ami_id'],
		    		'nginx_https_host_template',
		    	));
			}			
		}
	}
?>