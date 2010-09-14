var FarmRole = function(){
	this.initialize.apply(this, arguments);
};

	FarmRole.prototype = {
		role_id: null,
		ami_id: null,
		alias: null,
		name: null,
		arch: null,
		type:null,
		description: null,
		options: null,
		author: null,
		settings: null,
		platform: null,
		
		initialize: function(name, role_id, alias, arch, description, type, author, ami_id) 
		{
			this.role_id = role_id;
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
								
				'aws.instance_type':'',
				'aws.availability_zone':'',
				'aws.ebs_size':10,
				
				'mysql.ebs.rotate':5,
				'mysql.ebs.rotate_snaps':0,
				
				'mysql.pbw1_hh':'00',
				'mysql.pbw2_hh':'23',
				'mysql.pbw1_mm':'01',
				'mysql.pbw2_mm':'01',
				
				'mysql.bundle_every':48,
				'mysql.bcp_every':180,
				'mysql.enable_bundle':true,
				'mysql.enable_bcp':false,
				'mysql.data_storage_engine':'ebs',
				'mysql.ebs_volume_size':100
			};
			
			/* DEPRACATED */
			this.options = { 
					
				reboot_timeout: 300,
				status_timeout: 20,
				launch_timeout: (alias == 'mysql') ? 2400 : 600,
						
				scaling_algos: {}
			};
	  	}
	};