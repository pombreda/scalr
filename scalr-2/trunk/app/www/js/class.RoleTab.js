var RoleTab = function(){
	this.initialize.apply(this, arguments);
};

RoleTab.prototype = {
	
	Roles:null,
	CurrentRoleObject:null,
	CurrentRoleScript:null,
	  	
	initialize:function()
	{
		this.Roles = new Array();
		this.RolesArchive = new Array();
		this.CurrentRoleObject = null;
		this.CurrentRoleScript = null;
		
		this.elements = {
			
			c_role_name:  Ext.get('c_role_name').dom,
			c_role_arch:  Ext.get('c_role_arch').dom,
			c_role_amiid: Ext.get('c_role_amiid').dom,
			c_role_descr: Ext.get('c_role_descr').dom,
			c_role_type:  Ext.get('c_role_type').dom,
			
			c_author:     Ext.get('c_author').dom,
			c_author_tr:  Ext.get('c_author_tr').dom,
			c_warning:    Ext.get('c_warning').dom,
						
			reboot_timeout: Ext.get('reboot_timeout').dom,
			launch_timeout: Ext.get('launch_timeout').dom//,
			//status_timeout: Ext.get('status_timeout').dom
		};
	},
	
	UpdateCurrentRoleObject: function()
	{                           			
		if (!this.CurrentRoleObject)
			return;
				
		/** Timeouts **/
		
		this.CurrentRoleObject.options.reboot_timeout = this.elements.reboot_timeout.value; 	
		//this.CurrentRoleObject.options.status_timeout = this.elements.status_timeout.value;
		this.CurrentRoleObject.options.launch_timeout = this.elements.launch_timeout.value;
		
		this.CurrentRoleObject.settings = {};
		
		/** New setting subsystem **/
		var elems = Ext.get('tab_contents_roles').select('.role_settings');
		elems.each(function(elem){
			
			var item = elem.dom;
			
			if (item.id)
			{
				if (item.tagName == 'INPUT' && (item.type == 'text' || item.type == 'hidden'))
				{
					this.CurrentRoleObject.settings[item.id] = item.value;
				}
				else if (item.tagName == 'INPUT' && item.type == 'checkbox')
				{
					this.CurrentRoleObject.settings[item.id] = (item.checked) ? 1 : 0;
				}
				else if (item.tagName == 'SELECT')
				{
					this.CurrentRoleObject.settings[item.id] = item.value;
				}
			}
			
		}, this);
		
		/** Scaling **/                           			
		this.CurrentRoleObject.options.scaling_algos = {};
		
		var elems = Ext.get('itab_contents_scaling_n').select('.scaling_options');
		elems.each(function(elem){
			
			var item = elem.dom;
			
			if (item.id)
			{
				if (item.tagName == 'INPUT' && (item.type == 'text' || item.type == 'hidden'))
				{
					this.CurrentRoleObject.options.scaling_algos[item.id] = item.value;
				}
				else if (item.tagName == 'INPUT' && item.type == 'checkbox')
				{
					this.CurrentRoleObject.options.scaling_algos[item.id] = (item.checked) ? 1 : 0;
				}
				else if (item.tagName == 'SELECT')
				{
					this.CurrentRoleObject.options.scaling_algos[item.id] = item.value;
				}
			}
		}, this);
		
		/** Time based scaling **/
		this.CurrentRoleObject.options.scaling_algos['scaling.time.periods'] = new Array();
		if (this.CurrentRoleObject.options.scaling_algos['scaling.time.enabled'] == 1)
		{
			var a = new Array();
			TSTimersStore.each(function(item, index, length)
			{
				a[a.length] = item.data.id;
				this.CurrentRoleObject.options.scaling_algos['scaling.time.periods'] = a;           								            									
			}, this);
		}                           
		                           			
		/** EBS **/
		ShowEBSOptions(Ext.get('aws.use_ebs').dom.checked);
		
		/*********/
		
		/*** Role params ****/
		var elems = Ext.get('itab_contents_params_n').select('#role_params');
		elems.each(function(item){
			
			item = item.dom;
			
			if (item.type != 'checkbox')
				this.CurrentRoleObject.params[item.name] = item.value;
			else
			{
				this.CurrentRoleObject.params[item.name] = item.checked ? 1 : 0;
			}
			
		}, this);
		
		/*******************/
		
		/** Update scripts **/
		this.UpdateCurrentRoleScript();
		/********************/
		
		/* Finish*/
	},
	
	SetScaling: function (scaling_type, isenabled)
	{
		Ext.get('scaling.'+scaling_type+'.enabled').dom.checked = isenabled;
		
		Ext.get(scaling_type+'_scaling_options').dom.style.display = (isenabled) ? '' : 'none';
		
		if (scaling_type == 'time')
		{
			this._tmp = isenabled;
			//Disable all other Scaling algos
			var elems = Ext.get('itab_contents_scaling_n').select('.scaling_algo_list');
			elems.each(function(item){
			
				item = item.dom;
				
				if (item.id && item.value != 'time')
				{
					this.SetScaling(item.value, false);
					Ext.get('scaling.'+item.value+'.enabled').dom.disabled = this._tmp;
				}
				
			}, this);
			
			Ext.get('scaling.max_instances').dom.disabled = this._tmp;
		}
	},
	
	SetRolesLaunchOrder: function (order)
	{
		if (order == 1)
		{
			Ext.get('tab_rso').dom.style.display = '';

			var roles = [];
			for(var role_id in this.Roles)
			{
				if (typeof(this.Roles[role_id]) == 'object')
				{
					roles[roles.length] = [role_id, this.Roles[role_id].name];
				}
			}
			window.RolesOrderTree.getStore().loadData(roles);
			//window.RolesOrderTree.getView().refresh();
		}
		else
			Ext.get('tab_rso').dom.style.display = 'none';
	},
	
	IntToDate: function (number)
	{
		var n = number.toString().split("");
		
		var retval = "";
		
		if (number < 1300)
		{
			
			if (number < 100)
			{
				retval = "12:"+n[0]+n[1]+" AM";
			}
			else if (number < 1000)
			{
				retval = n[0]+":"+n[1]+n[2]+" AM";
			}
			else
				retval = n[0]+n[1]+":"+n[2]+n[3]+" AM";
		}
		else
		{
			number = number-1200;
			var n = number.toString().split("");
			
			if (number < 1000)
			{
				retval = n[0]+":"+n[1]+n[2]+" PM";
			}
			else
				retval = n[0]+n[1]+":"+n[2]+n[3]+" PM";
		}
		
		return retval;
	},
	
	updateTabs: function(alias, platform)
	{
		items = window.RoleTabsPanel.items;
		for (i = 0; i < items.length; i++)
		{
			var x_display = items.items[i].x_display.toString();
			var chunks = x_display.split(':');
			var allowed_platforms = chunks[0].toString().split(',');
			
			if (chunks[1])
				var allowed_aliases = chunks[1].toString().split(',');
			else
				var allowed_aliases = ['all'];
			
			if (allowed_platforms.indexOf('all') != -1 || allowed_platforms.indexOf(platform) != -1)
			{
				if (allowed_aliases.indexOf('all') != -1 || allowed_aliases.indexOf(alias) != -1)
				{
					window.RoleTabsPanel.unhideTabStripItem(i);
				}
				else
					window.RoleTabsPanel.hideTabStripItem(i);
			}
			else
				window.RoleTabsPanel.hideTabStripItem(i);
		}
	},
	
	SetCurrentRoleObject: function(role_id, add_to_farm)
	{
		Ext.get('role_info_link').dom.href = "role_info.php?role_id="+role_id;
		                           			
		var nodeID = role_id;
		while(tree.getLevel(nodeID) != 1)
		{
			nodeID = tree.getParentId(nodeID);
		}
		
		if (nodeID == 'Custom')
		{
			Ext.get('comments_links_row').dom.style.display = 'none';
		}
		else
		{
			Ext.get('comments_links_row').dom.style.display = '';
			var ccount = tree.getUserData(role_id, 'comments_count');
			
			if (ccount > 0)
			{
				Ext.get('comments_link').dom.href = "role_info.php?role_id="+role_id+"#comments";
				Ext.get('comments_link').dom.innerHTML = 'Comments ('+ccount+')';
			}
			else
			{
				Ext.get('comments_link').dom.href = "role_info.php?role_id="+role_id+"#addcomment";
				Ext.get('comments_link').dom.innerHTML = 'Leave a comment';
			}
		}

		if (!add_to_farm)
		{
			var roleObject = GetRoleObjectFromTree(role_id);
			
			if (roleObject.name != roleObject.alias)
			{
				Ext.get('c_based_on_tr').dom.style.display = '';
				Ext.get('c_based_on').dom.innerHTML = roleObject.alias;
			}
			else
			{
				Ext.get('c_based_on_tr').dom.style.display = 'none';
			}
			                           				
			//TODO: Create Javascript class Enum.
			Ext.get('arch_i386').removeClass("ui_enum_selected");
			Ext.get('arch_x86_64').removeClass("ui_enum_selected");
			Ext.get('arch_'+roleObject.arch).addClass("ui_enum_selected");
			
			/* Setup tab*/
			this.elements.c_role_name.innerHTML = roleObject.name;
			this.elements.c_role_amiid.innerHTML = roleObject.ami_id;
			this.elements.c_role_arch.innerHTML = roleObject.arch;
			this.elements.c_role_descr.innerHTML = roleObject.description ? roleObject.description : "No description available for this role.";
			this.elements.c_role_type.innerHTML = roleObject.type;
			
			if (roleObject.author)
			{
				this.elements.c_author_tr.style.display = '';
				this.elements.c_author.innerHTML = roleObject.author;
				this.elements.c_warning.style.display = '';
			}
			else
			{
				this.elements.c_author_tr.style.display = 'none';
				this.elements.c_warning.style.display = 'none';
			}
			
			
			Ext.get('tab_roles').dom.style.display = '';
			Ext.get('tab_name_roles').dom.innerHTML = roleObject.name;
			SetActiveTab('roles');
			
			HideIntableTabs('role_t_about');
			
			window.RoleTabsPanel.setActiveTab(0);
		}
		else
		{
   			ShowIntableTabs();
   			
   			try
   			{
       			Ext.get('tab_roles').dom.style.display = '';
   				Ext.get('tab_name_roles').dom.innerHTML = this.Roles[role_id].name;
   				SetActiveTab('roles'); 
   				
   				window.RoleTabsPanel.setActiveTab(0);
   			}
   			catch(e)
   			{
   			
   			}
   			
   			this.UpdateCurrentRoleObject();
   			
   			if (this.CurrentRoleObject && this.CurrentRoleObject.role_id == role_id)
   			{
   				if (this.CurrentRoleObject.alias == 'mysql')
   				{   					
   					if (FARM_MYSQL_ROLE == role_id)
       				{
       					Ext.get('mysql.ebs_volume_size').dom.disabled = true;
       					Ext.get('mysql.data_storage_engine').dom.disabled = true;
       				}
       				else
       				{
       					Ext.get('mysql.ebs_volume_size').dom.disabled = false;
       					Ext.get('mysql.data_storage_engine').dom.disabled = true;
       					Ext.get('mysql.data_storage_engine').dom.value = 'ebs';
       				}
       				
       				CheckEBSSize(Ext.get('mysql.data_storage_engine').dom.value);
   				}
   				
   				this.updateTabs(this.CurrentRoleObject.alias, this.CurrentRoleObject.platform);
   				
   				return true;
   			}
   			
			this.CurrentRoleObject = false;
			
    		
    		// Setup loader...
    		Ext.get('role_parameters').dom.innerHTML = '<div align="center"><img src="/images/snake-loader.gif" style="vertical-align:middle;">&nbsp;Loading. Please wait...</div>';
    		
    		Ext.Ajax.request({
        		url:'/server/server.php?_cmd=get_role_params&farmid='+FARM_ID+'&role_id='+role_id,
        		method:'GET',
        		success:function(transport, options)
        		{
    				try
					{
						Ext.get('role_parameters').dom.innerHTML = "<table width='100%'><tr><td width='28%'></td><td></td></tr>"+transport.responseText+"</table>";
					}
					catch(e)
					{
						alert(e.message);
					}
        		}
        	});
    		
			//
			// Role params - end
			//
   				                           			
   				                           			
   			//Reset events tree
   			Ext.get('event_script_config_form_description').dom.innerHTML = "";
   			Ext.get('event_script_config_container').dom.innerHTML = "";
   			Ext.get('event_script_config_title').dom.style.display = "none";
			 
   			var elems = events_tree.getAllChecked().split(",");
   			for(var i = 0; i < elems.length; i++)
   			{
   				if (!elems[i])
   					continue;
   				
   				try
   				{
       				if (events_tree.getLevel(elems[i]) == 2)
       				{
       					events_tree.setCheck(elems[i], 0);
       				}
       			}
       			catch(e)
       			{
       				//
       			}	
   			}
   			events_tree.closeAllItems();
   			events_tree._unselectItems();
   			
   			if (this.Roles[role_id])
   			{
   				
   				this.CurrentRoleObject = this.Roles[role_id];
   				
   				/** Setup scripts **/
   				for(key in this.CurrentRoleObject.scripts)
   				{
   					if (events_tree._globalIdStorageFind(key) != 0)
   					{
   						events_tree.setCheck(key, 1);
   						
   						try
   						{
       						//moveNode: Node
       						var mn_n = events_tree._globalIdStorageFind(key);
       						var pid =  events_tree.getParentId(key);
       						var mn_t = events_tree._globalIdStorageFind(pid);
       						
       						var indexItem = events_tree.getItemIdByIndex(pid, this.CurrentRoleObject.scripts[key].order_index);
       						
       						var mn_b = events_tree._globalIdStorageFind(indexItem);
       						
       						events_tree._moveNodeTo(mn_n, mn_t, mn_b);
       						events_tree.openItem(pid);
   						}
   						catch(e) {}
   					}
   				}
   				/******************/
   				
   				if (this.CurrentRoleObject.name != this.CurrentRoleObject.alias)
   				{
   					Ext.get('c_based_on_tr').dom.style.display = '';
   					Ext.get('c_based_on').dom.innerHTML = this.CurrentRoleObject.alias;
   				}
   				else
   				{
   					Ext.get('c_based_on_tr').dom.style.display = 'none';
   				}
   				
   				//TODO: Create Javascript class Enum.
   				Ext.get('arch_i386').removeClass("ui_enum_selected");
   				Ext.get('arch_x86_64').removeClass("ui_enum_selected");
   				Ext.get('arch_'+this.CurrentRoleObject.arch).addClass("ui_enum_selected");
   				
   				/* Setup tab*/
   				this.elements.c_role_name.innerHTML = this.CurrentRoleObject.name;
   				this.elements.c_role_amiid.innerHTML = this.CurrentRoleObject.ami_id;
   				this.elements.c_role_arch.innerHTML = this.CurrentRoleObject.arch;
   				this.elements.c_role_descr.innerHTML = this.CurrentRoleObject.description ? this.CurrentRoleObject.description : "No description available for this role.";
   				this.elements.c_role_type.innerHTML = this.CurrentRoleObject.type;
   				
   				if (this.CurrentRoleObject.author)
   				{
   					this.elements.c_author_tr.style.display = '';
   					this.elements.c_author.innerHTML = this.CurrentRoleObject.author;
   					this.elements.c_warning.style.display = '';
   				}
   				else
   				{
   					this.elements.c_author_tr.style.display = 'none';
   					this.elements.c_warning.style.display = 'none';
   				}

   				this.updateTabs(this.CurrentRoleObject.alias, this.CurrentRoleObject.platform);
   				
   				
   				this.elements.reboot_timeout.value = this.CurrentRoleObject.options.reboot_timeout;
   				this.elements.launch_timeout.value = this.CurrentRoleObject.options.launch_timeout;
   				//this.elements.status_timeout.value = this.CurrentRoleObject.options.status_timeout;
   				
   				//TODO: Do this only if arch changed...
   				// Clear all types
   				var el = Ext.get('aws.instance_type').dom; 
				while(el.firstChild) { 
					el.removeChild(el.firstChild); 
				}
															
				for (var i = 0; i < i_types[this.CurrentRoleObject.arch].length; i ++)
				{
					var opt = document.createElement("OPTION");
					opt.value = i_types[this.CurrentRoleObject.arch][i];
					opt.innerHTML = i_types[this.CurrentRoleObject.arch][i];
					Ext.get('aws.instance_type').dom.appendChild(opt); 
				}
   				
   			
   				/** New setting subsystem **/
       			var elems = Ext.get('tab_contents_roles').select('.role_settings');
       			var defRole = new FarmRole(null, null, null, null, null, null, null);
       			
       			elems.each(function(item){
       				
       				var item = item.dom;
    				
    				if (item.id)
    				{
    					if (item.tagName == 'SELECT' || (item.tagName == 'INPUT' && (item.type == 'text' || item.type == 'hidden')))
    					{
    						if (this.CurrentRoleObject.settings[item.id])
    							item.value = this.CurrentRoleObject.settings[item.id];
    						else if (defRole.settings[item.id])
    							item.value = defRole.settings[item.id];
    						else
    							item.value = '';
    					}
        				else if (item.tagName == 'INPUT' && item.type == 'checkbox')
        				{
        					item.checked = (this.CurrentRoleObject.settings[item.id] == 1) ? true : false;
        				}
    				}
       				
       			}, this);
   				
       			if (this.CurrentRoleObject.alias == 'mysql')
   				{
   					if (FARM_MYSQL_ROLE == role_id)
       				{
       					Ext.get('mysql.ebs_volume_size').dom.disabled = true;
       					Ext.get('mysql.data_storage_engine').dom.disabled = true;
       				}
       				else
       				{
       					Ext.get('mysql.ebs_volume_size').dom.disabled = false;
       					Ext.get('mysql.data_storage_engine').dom.disabled = true;
       					Ext.get('mysql.data_storage_engine').dom.value = 'ebs';
       				}
   					
   					CheckEBSSize(Ext.get('mysql.data_storage_engine').dom.value);
   				}
       			
   				/** Time Scaling algo settings **/
   				TSTimersStore.loadData([]);
   				if (this.CurrentRoleObject.options.scaling_algos['scaling.time.enabled'])
    			{         				
    				var periods = this.CurrentRoleObject.options.scaling_algos['scaling.time.periods'];
    				for (key in periods)
					{
						if (typeof periods[key] != 'string')
    						continue;
						
						var chunks = periods[key].split(":");
						
						var recordData = {
							start_time:this.IntToDate(chunks[0]),
							end_time:this.IntToDate(chunks[1]),
							instances_count:chunks[3],
							week_days:chunks[2],
							id:periods[key]
      	        		};
      	        		TSTimersStore.add(new TSTimersStore.reader.recordType(recordData));
					}
    			} 
    			/********************************/
   				
       			/** Load balancing options **/									
       			LBListenersStore.loadData([]);
				
       			if (this.CurrentRoleObject.settings['lb.use_elb'] == 1)
       			{
       				Ext.get('lb_settings').removeClass('x-hide-display');
			
					(function()
					{
						for(setting_name in this.CurrentRoleObject.settings)
						{            										
							if (setting_name.indexOf('lb.role.listener.') != -1)
							{
								var listener = this.CurrentRoleObject.settings[setting_name];
								var listener_chunks = listener.split('#');
								
								var recordData = {
	         	            		protocol:listener_chunks[0],
	         	            		lb_port:listener_chunks[1], 
	         	            		instance_port:listener_chunks[2]
	          	        		};
								
								LBListenersStore.add(new LBListenersStore.reader.recordType(recordData));
							}
						}

						var clm_remove = LBListenersGrid.getColumnModel().getIndexById('remove');
					
						if (this.CurrentRoleObject.settings['lb.hostname'] != '')
						{
							LBListenersGrid.getColumnModel().setHidden(clm_remove, true);
							LBListenersGrid.getBottomToolbar().setDisabled(true);
						}
						else
						{
							LBListenersGrid.getColumnModel().setHidden(clm_remove, false);
							LBListenersGrid.getBottomToolbar().setDisabled(false);
						}
						
						
					}).defer(100, this);
				}
				else
				{
					Ext.get('lb_settings').addClass('x-hide-display');
				}
   				
   				/** Scaling **/
   				
   				for(key in this.CurrentRoleObject.options.scaling_algos)
   				{
   					if (typeof key == 'string')
   					{
       					var obj = Ext.get(key);
       					if (obj)
       					{
           					obj = obj.dom;
       						if (obj.tagName == 'SELECT' || (obj.tagName == 'INPUT' && (obj.type == 'text' || obj.type == 'hidden')))
           					{
           						if (this.CurrentRoleObject.options.scaling_algos[key])
           							obj.value = this.CurrentRoleObject.options.scaling_algos[key];
           						else
           							obj.value = window.default_scaling_algos[key];
           					}
           					else if (obj.tagName == 'INPUT' && obj.type == 'checkbox')
           					{
           						if (this.CurrentRoleObject.options.scaling_algos[key])
           						{
               						obj.checked = (this.CurrentRoleObject.options.scaling_algos[key] == 1) ? true : false;
           						}
           						else
           							obj.checked = (window.default_scaling_algos[key] == 1) ? true : false;
           							
       							if (key.endsWith("enabled"))
           						{
           							var algo_name = key.replace("scaling.", "").replace(".enabled", "");
           							this.SetScaling(algo_name, obj.checked);
           						}
           					}
       					}
   					}
   				}
   					                           				
       			trackbar.getObject('scaling.la', true).init({
					leftValue:  parseFloat(Ext.get('scaling.la.min').dom.value),
					rightValue: parseFloat(Ext.get('scaling.la.max').dom.value),
					onMove: function ()
					{
						Ext.get('scaling.la.min').dom.value = this.leftValue;
						Ext.get('scaling.la.max').dom.value = this.rightValue;
					} 
				}, 'scaling.la');
   				
   				/***** EBS *******/
       			ShowEBSOptions(Ext.get('aws.use_ebs').dom.checked);
   				/*****************/
   			}
   			else
   			{
   				alert('FAIL: setCurrentRoleObject();');
   			}
   		}
	},
	
	HidePageError: function()
	{
		Scalr.Viewers.ErrorMessage('', 'RoleTabError');
		//var err_obj = Ext.get('Webta_ErrMsg').dom;
		//err_obj.style.display = 'none';
	},
	
	ShowPageError: function(error)
	{
		Scalr.Viewers.ErrorMessage(error, 'RoleTabError');
		Ext.get('button_js').dom.disabled = false;
		
		//TODO: new Effect.Pulsate(err_obj);
	},
	
	SubmitForm: function()
	{
		this.UpdateCurrentRoleObject();
	   
		Ext.get('button_js').dom.disabled = true;
		
		Ext.MessageBox.show({
           msg: 'Building farm. Please wait...',
           progressText: 'Building...',
           width:350,
           wait:true,
           waitConfig: {interval:200},
           icon:'ext-mb-farm-building', //custom class in msg-box.html
           animEl: 'mb7'
       	});
	   								   								   
	   	var launch_order_type = Ext.get('roles_launch_order_0').dom.checked == true ? '0' : '1';
	   	
	   	// TODO: refactor
	    var url = '/server/farm_manager.php?action='+MANAGER_ACTION
	            +"&farm_name="+Ext.get('farm_name').dom.value
	            +"&farm_id="+FARM_ID
	            +"&launch_order_type="+launch_order_type
	            +"&farm_comments="+Ext.get('farm_comments').dom.value; 
		
	    var o_tree_ds = window.RolesOrderTree.getStore();
	    for (var ii = 0; ii < o_tree_ds.getCount(); ii++)
	    	this.Roles[o_tree_ds.getAt(ii).get('role_id')].launch_index = ii+1;								    
	    
		var postArray = [];
		for (role_id in this.Roles)
		{
			if (parseInt(role_id) > 0)
			{
				postArray[postArray.length] = this.Roles[role_id]; 
			}
		}
		
		var postBody = Ext.encode(postArray);
		
		this.HidePageError();
		
		Ext.Ajax.request({
    		url:url,
    		method:'POST',
    		jsonData: postBody,
    		success:function(transport, options)
    		{
				try
				{
					var response = Ext.decode(transport.responseText);
					if (response.result == 'error')
					{
						Ext.get('button_js').dom.disabled = false;
						Ext.MessageBox.hide();
						window.RoleTabObject.ShowPageError("Cannot build farm: "+response.data);
					}
					else
					{
						if (MANAGER_ACTION == 'create')
							window.location.href = '/farms_control.php?farmid='+response.data+"&new=1";
						else
						{
							if (RETURN_TO == 'instances_list')
								window.location.href = '/servers_view.php?farmid='+FARM_ID;
							else
								window.location.href = '/farms_view.php?code=1';
						}
					}
				}
				catch(e)
				{
					Ext.get('button_js').dom.disabled = false;
					Ext.MessageBox.hide();
					
					window.RoleTabObject.ShowPageError("Unexpected exception in javascript: " + e.message);
				}	
    		},
    		failure: function()
    		{
    			Ext.get('button_js').dom.disabled = false;
				Ext.MessageBox.hide();
				
				window.RoleTabObject.ShowPageError("Cannot proceed your request at this time. Please try again later.");
    		}
    	});  
	},
	
	AddRoleToFarm: function (role_id, role_object)
	{
		this.UpdateCurrentRoleObject();
		
		if (!this.Roles[role_id])
		{		
			if (role_id != null)
			{
				if (!this.RolesArchive[role_id])
					this.Roles[role_id] = GetRoleObjectFromTree(role_id);
				else
					this.Roles[role_id] = this.RolesArchive[role_id];
				
				var role_object = this.Roles[role_id];
			}
			else
			{
				this.Roles[role_object.role_id] = role_object;
			}
			
			if (window.RolesOrderTree && (window.RolesOrderTree.getStore().find('role_id', role_object.role_id) == -1))
			{
				var store = window.RolesOrderTree.getStore();
				store.add(new store.recordType({
					role_id: role_object.role_id,
					role_name: role_object.name
				}));
			}
		}
		else
		{
			alert('FAIL: AddRoleToFarm();');
		}	
	},
	
	RemoveRoleFromFarm: function(role_id)
	{
		if (this.Roles[role_id])
		{		
			if (this.CurrentRoleObject && this.CurrentRoleObject.role_id == role_id)
				this.CurrentRoleObject = false;

			this.RolesArchive[role_id] = this.Roles[role_id];
			this.Roles[role_id] = false;
			
			var ind = window.RolesOrderTree.getStore().find('role_id', role_id);
			if (ind != -1) {
				window.RolesOrderTree.getStore().removeAt(ind);
			}
		}
		else
		{
			alert('FAIL: RemoveRoleFromFarm();');
		}
	},
	
	UpdateCurrentRoleScript: function()
	{
		if (!this.CurrentRoleScript)
			return;

		if (!this.CurrentRoleObject || !this.CurrentRoleObject.scripts || !this.CurrentRoleObject.scripts[this.CurrentRoleScript])
			return;
		
		this.CurrentRoleObject.scripts[this.CurrentRoleScript].target = Ext.get('event_script_target_value').dom.value;
		this.CurrentRoleObject.scripts[this.CurrentRoleScript].version = Ext.get('script_version').dom.value;
		this.CurrentRoleObject.scripts[this.CurrentRoleScript].timeout = Ext.get('scripting_timeout').dom.value;
		
		this.CurrentRoleObject.scripts[this.CurrentRoleScript].order_index = events_tree.getIndexById(this.CurrentRoleScript);
		
		this.CurrentRoleObject.scripts[this.CurrentRoleScript].issync = Ext.get('issync_1').dom.checked ? 1 : 0;
		
		var elems = Ext.get('event_script_config_container').select('input.configopt');
		elems.each(function(item){
			
			item = item.dom;
			
			try
			{
				this.CurrentRoleObject.scripts[this.CurrentRoleScript].config[item.name] = item.value.replace("'", "\'");
			}
			catch(e){}
			
		}, this);
	},
	
	SetupConfigForm: function(current_version)
	{
	    var v = 0;
		var fields = [];
		var show_config_title = false;
		
		Ext.each(this.CurentRoleScriptVersions, function(item){
		    if (item.revision == current_version || (current_version == 'latest' && item.revision > v))
		    {
		        v = item.revision;
		        fields = item.fields;
		    }
		});
		
		try
		{
			eval("var fields = "+fields+";");
        	
        	for(var key in fields)
        	{
        		if (typeof(fields[key]) == 'string')
        		{
					Ext.get('event_script_config_container').insertHtml('beforeEnd',
						ConfigFieldTemplate.apply({name:key, title:fields[key]})
					);
					
					show_config_title = true;
        		}
        	}
        }
        catch(e){}	
    	
    	if (show_config_title)
    		Ext.get('event_script_config_title').dom.style.display = '';
    	else
    		Ext.get('event_script_config_title').dom.style.display = 'none';
	},
	
	SetCurrentRoleScript: function(script_id, event_name, versions)
	{
		if (!this.CurrentRoleObject)
			return;
		
		if (this.CurrentRoleObject.scripts[event_name+"_"+script_id])
		{
			this.CurrentRoleScript = event_name+"_"+script_id;
			
			/*
			Setup role script options
			*/
			this.CurentRoleScriptVersions = versions;
			
			//
			// Load config
			//
			this.SetupConfigForm(this.CurrentRoleObject.scripts[this.CurrentRoleScript].version);
													
			var elems = Ext.get('event_script_config_container').select('input.configopt');
			elems.each(function(item){
				
				item = item.dom;
				
				if (this.CurrentRoleObject.scripts[this.CurrentRoleScript].config[item.name])
					item.value = this.CurrentRoleObject.scripts[this.CurrentRoleScript].config[item.name];
				
			}, this);
			
			if (event_name == 'HostDown')
			{
				if (this.CurrentRoleObject.scripts[this.CurrentRoleScript].target == 'instance')
					this.CurrentRoleObject.scripts[this.CurrentRoleScript].target = 'role';
				
				if (Ext.get('event_script_target_value_instance'))
					Ext.get('event_script_target_value_instance').dom.parentNode.removeChild(Ext.get('event_script_target_value_instance').dom);
				
				if (!Ext.get('event_script_target_value_role'))
				{				
					Ext.get('event_script_target_value').createChild({
						tag: 'option', 
						value: 'role', 
						id: 'event_script_target_value_role', 
						html: 'All instances of the role'
					}, Ext.get('event_script_target_value_instance'));
				}
			}
			else if (event_name == 'DNSZoneUpdated')
			{
				if (this.CurrentRoleObject.scripts[this.CurrentRoleScript].target == 'instance' || this.CurrentRoleObject.scripts[this.CurrentRoleScript].target == 'role')
					this.CurrentRoleObject.scripts[this.CurrentRoleScript].target = 'farm';
					
				if (Ext.get('event_script_target_value_instance'))
					Ext.get('event_script_target_value_instance').dom.parentNode.removeChild(Ext.get('event_script_target_value_instance').dom);
					
				Ext.get('event_script_target_value_role').dom.parentNode.removeChild(Ext.get('event_script_target_value_role').dom);
			}
			else
			{
				if (!Ext.get('event_script_target_value_instance'))
				{
					Ext.get('event_script_target_value').createChild({
						tag: 'option', 
						value: 'instance', 
						id: 'event_script_target_value_instance', 
						html: 'That instance only'
					}, Ext.get('event_script_target_value_role'));
				}
				
				if (!Ext.get('event_script_target_value_role'))
				{				
					Ext.get('event_script_target_value').createChild({
						tag: 'option', 
						value: 'role', 
						id: 'event_script_target_value_role', 
						html: 'All instances of the role'
					}, Ext.get('event_script_target_value_instance'));
				}
			}
			
			Ext.get('event_script_target_value').dom.value = this.CurrentRoleObject.scripts[this.CurrentRoleScript].target;
			Ext.get('script_version').dom.value = this.CurrentRoleObject.scripts[this.CurrentRoleScript].version;
			Ext.get('scripting_timeout').dom.value = this.CurrentRoleObject.scripts[this.CurrentRoleScript].timeout;
			
			if (this.CurrentRoleObject.scripts[this.CurrentRoleScript].issync == 1)
			{
				Ext.get('issync_1').dom.checked = true;
			}
			else
			{
				Ext.get('issync_0').dom.checked = true;
			}
			 										 
			/*
			Finish
			*/
		}
		else
		{
			alert('FAIL: setCurrentRoleObject('+script_id+', '+event_name+');');
			return;
		}
	},
	
	AddEventScript: function (script_id, event_name, timeout)
	{
		if (!this.CurrentRoleObject)
			return;

		try
		{
			this.CurrentRoleObject.scripts[event_name+"_"+script_id] = 
			{
				'config':{}, 
				'target':'instance', 
				'version':'latest', 
				'timeout':timeout, 
				'issync':0
			};
		}
		catch(e)
		{
			alert('FAIL: AddEventScript('+script_id+', '+event_name+');');
		}
	},
	
	RemoveEventScript: function (script_id, event_name)
	{
		if (!this.CurrentRoleObject)
			return;
		
		try
		{
			this.CurrentRoleObject.scripts[event_name+"_"+script_id] = false;
		}
		catch(e)
		{
			alert('FAIL: RemoveEventScript('+script_id+', '+event_name+');');
		}
	}
};