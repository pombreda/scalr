	function MainLoader(step)
	{
		if (step == 1)
		{
			//////////////////////////
			Ext.get('tab_rso').dom.style.display = 'none';
			
			window.RoleTabObject = new RoleTab();
			window.popup = new NewPopup('role_info_popup', {target: '', width: 270, height: 120, selecters: new Array()});
			window.popup_help = new NewPopup('popup_help', {target: '', width: 370, height: 120, selecters: new Array()});
			
			/////////////////////////
		}
		else if (step == 2)
		{
			/////////////////////////////////////////
			/************* Init roles tree *********/
			/////////////////////////////////////////
			window.tree = new dhtmlXTreeObject(Ext.get('inventory_tree').dom,"100%","100%",0);
			tree.setImagePath("images/dhtmlxtree/csh_vista/");
            tree.enableCheckBoxes(true);
            tree.enableThreeStateCheckboxes(true);            
            tree.enableDragAndDrop(false);
            tree.setXMLAutoLoading("farm_amis.xml?farmid="+FARM_ID+"&role_id="+SELECTED_ROLE_ID+"&region="+REGION);
            tree.loadXML("farm_amis.xml?farmid="+FARM_ID+"&role_id="+SELECTED_ROLE_ID+"&region="+REGION);
            tree.OnXMLLoaded = function() {
            	MainLoader(3);
            }
            
            tree.setOnSelectStateChange(selectTreeItem);
                 
            
            tree.attachEvent("onCheck", function(itemId, state) {            	                               	                               	                    
                tree.setCheck(itemId,state);
				    
			    //Get Top level item
			    var item = tree._globalIdStorageFind(itemId);
                while (tree.getLevel(item.id) > 1)
                    item = tree._globalIdStorageFind(tree.getParentId(item.id));
    
                if (tree.getUserData(itemId,"isFolder") == 1)
				{
				    var childs = tree.getAllSubItems(itemId).split(',');
				    
				    for (key in childs)
				    {
				        if (typeof(childs[key]) == "string")
				        {
                            if (tree.getUserData(childs[key],"isFolder") != 1)
                                ManageTable(childs[key], state)
				        }
				    }
				}
				else
				    ManageTable(itemId, state);
            });
			/////////////////////////////////////////
		}
		else if (step == 3)
		{
			/////////////////////////////////////////
			/************* Init events tree *********/
			/////////////////////////////////////////
			events_tree = new dhtmlXTreeObject(Ext.get('scripts_tree').dom,"100%","100%",0);
            events_tree.setImagePath("images/dhtmlxtree/csh_vista/");
            events_tree.enableCheckBoxes(true);
            events_tree.enableThreeStateCheckboxes(true);
            events_tree.enableDragAndDrop(true);
            events_tree.OnXMLLoaded = function() {
            	MainLoader(4);
            }  
            events_tree.setXMLAutoLoading("role_scripts_xml.php?farmid="+FARM_ID);
            events_tree.setDragHandler(ScriptsDragHandler);
            events_tree.loadXML("role_scripts_xml.php?farmid="+FARM_ID);	            	            
            events_tree.onDragEnd = function(itemId) {
				if (RoleTabObject.CurrentRoleObject && RoleTabObject.CurrentRoleObject) {
					for (var key in RoleTabObject.CurrentRoleObject.scripts) {
						if (typeof(key) == 'string')
            				RoleTabObject.CurrentRoleObject.scripts[key].order_index = events_tree.getIndexById(key);
					}
				}
			}
                	            	            
            events_tree.setOnSelectStateChange(function(itemId) {
            	if (events_tree.getUserData(itemId,"isFolder") == 1) {
					var state=events_tree._getOpenState(events_tree._globalIdStorageFind(itemId));
					if (state == -1)
						events_tree.openItem(itemId);
					else
						events_tree.closeItem(itemId);
					
					return false;
				}
					
				if (events_tree.getUserData(itemId,"description"))
					Ext.get('event_script_config_form_description').dom.innerHTML = events_tree.getUserData(itemId,"description");
				else
					Ext.get('event_script_config_form_description').dom.innerHTML = "";
					
				RoleTabObject.UpdateCurrentRoleScript();
					
				Ext.get('event_script_config_container').dom.innerHTML = '';
					
				var show_config_title = false;
				
				var event_name = events_tree.getParentId(itemId);
                var script_id = itemId.replace(event_name+"_", "");
            	
            	var descr = events_tree.getUserData(event_name,"eventDescription");
            	Ext.get('event_script_edescr').dom.innerHTML = descr;
				
				Ext.get('event_script_info').dom.style.display = '';
						    
				Ext.get('event_script_version').dom.style.display = 'none';
				
				Ext.get('event_script_config_title').dom.style.display = 'none';
				Ext.get('script_source_div').dom.style.display = 'none';
				Ext.get('event_script_target').dom.style.display = 'none';
				
				HideSource();
						                						
				if (events_tree.isItemChecked(itemId) != 1) {
					Ext.get('event_script_config_title').dom.style.display = 'none';	
            	}
            	else {	
                	Ext.get('script_source_div').dom.style.display = '';
                	
                	var vobj = Ext.get('script_version').dom;
                	vobj.innerHTML = "";
                	
					var opt = document.createElement("OPTION");
					opt.value = "latest";
					opt.innerHTML = "Latest";
					opt.selected = true;
					
					vobj.appendChild(opt);

                	eval("var versions = "+events_tree.getUserData(itemId,"versions")+";");
                	for(var key in versions) {
                		if (typeof(versions[key]) == 'object') {
							if (versions[key].revision != '') {
								var opt = document.createElement("OPTION");
								opt.value = versions[key].revision;
								opt.innerHTML = versions[key].revision;
								
								vobj.appendChild(opt);
							}
                		}
                	}
                	
                	Ext.get('event_script_version').dom.style.display = '';
                	Ext.get('event_script_target').dom.style.display = '';
                			            	                	
                	RoleTabObject.SetCurrentRoleScript(script_id, event_name, versions);
            	}
            			            	                			            	                		
            	return true;
            });
                        	                
            events_tree.attachEvent("onCheck", function(itemId, state) {            	                               	                               	                    
                events_tree.setCheck(itemId,state);
				                            
                if (state == 1) {
                	var event_name = events_tree.getParentId(itemId);
                	var script_id = itemId.replace(event_name+"_", "");
                	var timeout = events_tree.getUserData(itemId, "timeout")
                	
                	RoleTabObject.AddEventScript(script_id, event_name, timeout);
                	
                	events_tree._unselectItems();
                	events_tree._selectItem(events_tree._globalIdStorageFind(itemId), true);
                	Ext.get('script_source_div').dom.style.display = '';
                	Ext.get('event_script_target').dom.style.display = '';
                }
                else {
                	if (events_tree.getSelectedItemId() == itemId) {
						Ext.get('event_script_config_container').dom.innerHTML = '';
						Ext.get('script_source_div').dom.style.display = 'none';
																		
						events_tree._unselectItems();
					}
					
					Ext.get('event_script_config_title').dom.style.display = 'none';
					Ext.get('script_source_div').dom.style.display = 'none';
					Ext.get('event_script_target').dom.style.display = 'none';
					
					var event_name = events_tree.getParentId(itemId);
                	var script_id = itemId.replace(event_name+"_", "");
					
					RoleTabObject.RemoveEventScript(script_id, event_name);
                }
            });
            ////////////////////////////////////////////////////////
		}
		else if (step == 4)
		{
			///////////////////////////////////////////////////////
			Ext.get('roles_launch_order_'+FARM_ROLES_LAUNCH_ORDER).dom.checked = true;
			window.RoleTabObject.SetRolesLaunchOrder(FARM_ROLES_LAUNCH_ORDER);
			
			if (l_roles)
        	{
                Ext.each(l_roles, function(item){
                	var f = new FarmRole(null, null, null, null, null, null);
                	Ext.apply(f, item);
                	window.RoleTabObject.AddRoleToFarm(null, Ext.apply({}, f));
                });
            }
			
            afterLoad();
            
            SetActiveTab('general');
            
			window.LoadMask.hide();
			//////////////////////////////////////////////////////
		}
	}
	
	var afterLoad = function(){};
	
	var selectTreeItem = function(itemId, markitem) {	
		
		if (tree.getUserData(itemId,"isFolder") == 1) {
			var state=tree._getOpenState(tree._globalIdStorageFind(itemId));
			if (state == -1)
				tree.openItem(itemId);
			else
				tree.closeItem(itemId);

			return false;
		}
    	
    	if (typeof(tree.getUserData(itemId,"alias")) == 'undefined') {
    		
    		afterLoad = function(){
    			window.setTimeout('selectTreeItem("'+itemId+'", 1)', 200);
    		}
    		
			return true;
    	}

		if (tree._globalIdStorageFind(itemId).label == 'mysql' || tree._globalIdStorageFind(itemId).label == 'mysql64') {
			Ext.get('mysql_dep_warning').dom.style.display = '';
		}
		else {
			Ext.get('mysql_dep_warning').dom.style.display = 'none';
		}
	            	                			                					
		if (markitem)
			tree._markItem(tree._globalIdStorageFind(itemId));
			
		Ext.get('event_script_info').dom.style.display = 'none';
		Ext.get('script_source_div').dom.style.display = 'none';
			
		if (tree.isItemChecked(itemId) != 1) {
			RoleTabObject.SetCurrentRoleObject(itemId, false);
    		return true;
    	}
    	else {	
    		RoleTabObject.SetCurrentRoleObject(itemId, true);
    		return true;
    	}
    }

	function ManageTable(nodeid, state)
	{            	                    
		if (state == 1)
		{            					                   					        
			RoleTabObject.AddRoleToFarm(nodeid);
        					        
			tree._selectItem(tree._globalIdStorageFind(nodeid), true);
        	
        	RoleTabObject.SetCurrentRoleObject(nodeid, true);
        					        
			if (tree.getUserData(nodeid, 'alias') == "mysql")
			{
				SetMysqlRolesAccess(1, nodeid);
			}
		}
		else
		{            					        
			RoleTabObject.RemoveRoleFromFarm(nodeid);
        					        
			if (tree.getSelectedItemId() == nodeid)
			{
				tree._unselectItems();
				SetActiveTab('general');
				Ext.get('tab_roles').dom.style.display = 'none';
			}
        					           
			if (tree.getUserData(nodeid, 'alias') == "mysql")
			{
				SetMysqlRolesAccess(0, nodeid);
			}
		}
	}
    
    function GetRoleObjectFromTree(role_id)
    {
    	var arch = tree.getUserData(role_id, "Arch");
		var alias = tree.getUserData(role_id, 'alias');
		var description = tree.getUserData(role_id, 'description');
		var type = tree.getUserData(role_id, 'type');
		var author = tree.getUserData(role_id, 'author');
		var amiid = tree.getUserData(role_id, 'ami_id');
		var platform = tree.getUserData(role_id, 'platform');
       					        
		var name = tree._globalIdStorageFind(role_id).label;
       					        
		var retval = new FarmRole(name, role_id, alias, arch, description, type, author, amiid);
		retval.platform = platform;
		retval.options.scaling_algos = window.default_scaling_algos;
		
		return retval;
    }
        	                
	function SetMysqlRolesAccess(disabled, nodeid)
	{
		var childs = tree.getAllChildless().split(',');
            					    
		for (key in childs)
		{
			if (tree.getUserData(childs[key], "alias") == "mysql" && childs[key] != nodeid)
			{
				tree.disableCheckbox(childs[key], disabled);
			}
		}
	}
      
    function HideIntableTabs(skip_tab)
    {
    	for (i in window.RoleTabsPanel.items.items)
    	{
    		if (typeof(window.RoleTabsPanel.items.items[i]) == 'object')
    		{
    			if (window.RoleTabsPanel.items.items[i].id != skip_tab)
    				window.RoleTabsPanel.hideTabStripItem(window.RoleTabsPanel.items.items[i]);
    		}
    	}
    }
    
    
    function ShowIntableTabs()
    {
    	for (i in window.RoleTabsPanel.items.items)
    	{
    		if (typeof(window.RoleTabsPanel.items.items[i]) == 'object')
    		{
    			window.RoleTabsPanel.unhideTabStripItem(window.RoleTabsPanel.items.items[i]);
    		}
    	}
    }
        
    function OnTabChanged_i(id)
    {
    	if (id == 'scripts')
    		Ext.get('intable_top_empty_tr').dom.style.display = 'none';
    	else
    		Ext.get('intable_top_empty_tr').dom.style.display = '';
    }
        	                
	function OnTabChanged(current_active_tab)
	{
        	                	
	}
	
	function ScriptsDragHandler(idFrom,idTo)
    {
    	var pid = events_tree.getParentId(idFrom);

    	if (pid == idTo)
			return true;
		else
            return false;
    }
    		            	            
    function ShowEBSOptions(ischecked)
    {
    	if (!ischecked)
    	{
    		Ext.get('ebs_mount_options').dom.style.display = 'none';
    		Ext.get('aws.ebs_size').dom.disabled   = true;
    		Ext.get('aws.ebs_snapid').dom.disabled = true;
    	}
    	else
    	{
    		Ext.get('ebs_mount_options').dom.style.display = '';
    		Ext.get('aws.ebs_size').dom.disabled   = false;
    		Ext.get('aws.ebs_snapid').dom.disabled = false;
    	}
    	
    	Ext.get('aws.ebs_mountpoint').dom.disabled = !Ext.get('aws.ebs_mount').dom.checked;
    }
    
    function ShowMTAOptions(ischecked)    
    {
        if(!ischecked)
        {        
            Ext.get('mta_options').dom.style.display      = 'none';
            Ext.get('mta.gmail.login').dom.disabled       = true;
            Ext.get('mta.gmail.password').dom.disabled    = true;
        }
        else 
        {   
        	Ext.get('mta_options').dom.style.display      = '';
        	Ext.get('mta.gmail.login').dom.disabled       = false;
        	Ext.get('mta.gmail.password').dom.disabled    = false;
        }       
    }
    
    function ViewTemplateSource()
    {
    	var version = Ext.get('script_version').dom.value;
    	var scriptid = RoleTabObject.CurrentRoleScript;
    	
    	var scriptid = scriptid.replace(/[A-Za-z0-9]+_/gi, ""); 
    	
    	Ext.get('source_link').dom.innerHTML = "Hide source";
    	Ext.get('source_img').dom.src = '/images/dhtmlxtree/csh_vista/script_source_open.gif';
    	Ext.get('view_source_link').on('click',function()
    	{
    		HideSource();
    	});
    	
    	Ext.get('script_source_container').dom.innerHTML = '<img src="/images/snake-loader.gif" style="vertical-align:middle;"> Loading source. Please wait...';
    	Ext.get('script_source_container').dom.style.display = '';
    	
    	Ext.Ajax.request({
    		url:'/server/server.php?_cmd=get_script_template_source&version='+version+"&scriptid="+scriptid,
    		method:'GET',
    		contaner_name: 'script_source_container',
    		success:function(transport, options)
    		{
	    		try
				{
					var content = transport.responseText.replace(/&/gm, '&amp;').replace(/</gm, '&lt;').replace(/>/gm, '&gt;');
					Ext.get(options.contaner_name).dom.innerHTML = '<pre style="margin:0px;"><code>'+content+'</code></pre>';
					
					window.setTimeout('window.HighLight()', 200);
				}
				catch(e)
				{
					alert(e.message);
				}
    		}
    	});
    }
    
    function SetVersion(version)
    {
    	HideSource();
    	RoleTabObject.SetupConfigForm(version);
    }
    
    function HideSource()
    {
    	Ext.get('script_source_container').dom.innerHTML = '';
    	Ext.get('script_source_container').dom.style.display = 'none';
    	
    	Ext.get('source_img').dom.src = '/images/dhtmlxtree/csh_vista/script_source_closed.gif';
    	Ext.get('source_link').dom.innerHTML = "View source";
    	Ext.get('view_source_link').on('click',function()
    	{
    		ViewTemplateSource();
    	});
    }
    
    function HighLight()
    {
    	hljs.highlightBlock(document.getElementById('script_source_container').firstChild.firstChild);
    }