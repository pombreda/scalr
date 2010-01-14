	var FarmRole = Class.create();
	FarmRole.prototype = {
		ami_id: null,
		alias: null,
		name: null,
		arch: null,
		type:null,
		description: null,
		options: null,
		author: null,
		settings: null,
		
		initialize: function(name, ami_id, alias, arch, description, type, author) 
		{
			this.ami_id = ami_id;
			this.alias = alias;
			this.name = name;
			this.arch = arch;
			this.type = type;
			this.author = author;
			this.description = description;
			this.launch_index = 0;
			this.scripts = {};
			this.params = {};
						
			this.settings = {
					'scaling.min_instances': 1,
					'scaling.max_instances': 2,
					'scaling.polling_interval': 1,
					
					'dns.exclude_role':0,
					
					'lb.use_elb':0,
					'lb.hostname':'',
					'lb.healthcheck.timeout':5,
					'lb.healthcheck.target':'',
					'lb.healthcheck.interval':30,
					'lb.healthcheck.unhealthythreshold':5,
					'lb.healthcheck.healthythreshold':3,
					'lb.healthcheck.hash':'',
					
					'health.terminate_if_snmp_fails':1,
					
					'mysql.ebs.rotate':5,
					'mysql.ebs.rotate_snaps':0,
					
					'aws.instance_type':'',
					'aws.availability_zone':''
					
			};
			
			/* DEPRACATED */
			this.options = { 
				mysql_bundle_every: 48,
  				mysql_make_backup: false,
  				mysql_bundle: true,
  				mysql_make_backup_every: 180,
  				
  				mysql_data_storage_engine: 'lvm',
  				mysql_ebs_size: 100,
  				
				reboot_timeout: 300,
				status_timeout: 20,
				launch_timeout: (alias == 'mysql') ? 2400 : 600,
						
				scaling_algos: {}
			};
	  	}
	};