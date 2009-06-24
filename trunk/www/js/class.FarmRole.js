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
					'dns.exclude_role':0
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
						
				placement: "",
				i_type: "",
				use_elastic_ips: false,
				use_ebs: false,
				ebs_size: 0,
				ebs_snapid: '',
				ebs_mount: false,
				ebs_mountpoint: '/mnt/storage',
				scaling_algos: {}
			};
	  	}
	};