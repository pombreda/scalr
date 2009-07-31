	function MainLoader(step)
	{
		if (step == 1)
		{
			//////////////////////////
			$('tab_rso').style.display = 'none';
			
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
			window.tree = new dhtmlXTreeObject($('inventory_tree'),"100%","100%",0);
			tree.setImagePath("images/dhtmlxtree/csh_vista/");
            tree.enableCheckBoxes(true);
            tree.enableThreeStateCheckboxes(true);            
            tree.enableDragAndDrop(false);
            tree.setXMLAutoLoading("farm_amis.xml?farmid="+FARM_ID+"&ami_id="+SELECTED_AMI_ID+"&region="+REGION);
            tree.loadXML("farm_amis.xml?farmid="+FARM_ID+"&ami_id="+SELECTED_AMI_ID+"&region="+REGION);
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
			events_tree = new dhtmlXTreeObject($('scripts_tree'),"100%","100%",0);
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
					$('event_script_config_form_description').innerHTML = events_tree.getUserData(itemId,"description");
				else
					$('event_script_config_form_description').innerHTML = "";
					
				RoleTabObject.UpdateCurrentRoleScript();
					
				$('event_script_config_container').innerHTML = '';
					
				var show_config_title = false;
				
				var event_name = events_tree.getParentId(itemId);
                var script_id = itemId.replace(event_name+"_", "");
            	
            	var descr = events_tree.getUserData(event_name,"eventDescription");
            	$('event_script_edescr').innerHTML = descr;
				
				$('event_script_info').style.display = '';
						    
				$('event_script_version').style.display = 'none';
				
				$('event_script_config_title').style.display = 'none';
				$('script_source_div').style.display = 'none';
				$('event_script_target').style.display = 'none';
				
				HideSource();
						                						
				if (events_tree.isItemChecked(itemId) != 1) {
					$('event_script_config_title').style.display = 'none';	
            	}
            	else {	
                	$('script_source_div').style.display = '';
                	
                	var vobj = $('script_version');
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
                	
                	$('event_script_version').style.display = '';
                	$('event_script_target').style.display = '';
                			            	                	
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
                	$('script_source_div').style.display = '';
                	$('event_script_target').style.display = '';
                }
                else {
                	if (events_tree.getSelectedItemId() == itemId) {
						$('event_script_config_container').innerHTML = '';
						$('script_source_div').style.display = 'none';
																		
						events_tree._unselectItems();
					}
					
					$('event_script_config_title').style.display = 'none';
					$('script_source_div').style.display = 'none';
					$('event_script_target').style.display = 'none';
					
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
			$('roles_launch_order_'+FARM_ROLES_LAUNCH_ORDER).checked = true;
			window.RoleTabObject.SetRolesLaunchOrder(FARM_ROLES_LAUNCH_ORDER);
			
			if (l_roles)
        	{
                l_roles.each(function(item){
                	var f = new FarmRole(null, null, null, null, null, null);
                	Object.extend(f, item);
                	window.RoleTabObject.AddRoleToFarm(null, Object.clone(f));
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
			$('mysql_dep_warning').style.display = '';
		}
		else {
			$('mysql_dep_warning').style.display = 'none';
		}
	            	                			                					
		if (markitem)
			tree._markItem(tree._globalIdStorageFind(itemId));
			
		$('event_script_info').style.display = 'none';
		$('script_source_div').style.display = 'none';
			
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
				$('tab_roles').style.display = 'none';
			}
        					           
			if (tree.getUserData(nodeid, 'alias') == "mysql")
			{
				SetMysqlRolesAccess(0, nodeid);
			}
		}
	}
    
    function GetRoleObjectFromTree(ami_id)
    {
    	var arch = tree.getUserData(ami_id, "Arch");
		var alias = tree.getUserData(ami_id, 'alias');
		var description = tree.getUserData(ami_id, 'description');
		var type = tree.getUserData(ami_id, 'type');
		var author = tree.getUserData(ami_id, 'author');
       					        
		var name = tree._globalIdStorageFind(ami_id).label;
       					        
		var retval = new FarmRole(name, ami_id, alias, arch, description, type, author);
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
    				window.RoleTabsPanel.hideTabStripItem(i);
    		}
    	}
    	
    	/*
    	window.RoleTabsPanel.items.each(function(item){
    		item.hideTabStripItem(item);
    	});
    	*/
    }
    
    
    function ShowIntableTabs()
    {
    	for (i in window.RoleTabsPanel.items.items)
    	{
    		if (typeof(window.RoleTabsPanel.items.items[i]) == 'object')
    		{
    			window.RoleTabsPanel.unhideTabStripItem(i);
    		}
    	}
    }
        
    function OnTabChanged_i(id)
    {
    	if (id == 'scripts')
    		$('intable_top_empty_tr').style.display = 'none';
    	else
    		$('intable_top_empty_tr').style.display = '';
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
    		            	            
    function ShowEBSOptions(ctype)
    {
    	if (ctype == 1)
    	{
    		$('ebs_mount_options').style.display = 'none';
    		$('ebs_size').disabled = true;
    		$('ebs_snapid').disabled = true;
    	}
    	else if (ctype == 2)
    	{
    		$('ebs_mount_options').style.display = '';
    		$('ebs_size').disabled = false;
    		$('ebs_snapid').disabled = true;
    	}
    	else if (ctype == 3)
    	{
    		$('ebs_mount_options').style.display = '';
    		$('ebs_size').disabled = true;
    		$('ebs_snapid').disabled = false;
    	}
    }
    
    function ViewTemplateSource()
    {
    	var version = $('script_version').value;
    	var scriptid = RoleTabObject.CurrentRoleScript;
    	
    	var scriptid = scriptid.replace(/[A-Za-z0-9]+_/gi, ""); 
    	
    	$('source_link').innerHTML = "Hide source";
    	$('source_img').src = '/images/dhtmlxtree/csh_vista/script_source_open.gif';
    	$('view_source_link').onclick = function()
    	{
    		HideSource();
    	};
    	
    	$('script_source_container').innerHTML = '<img src="/images/snake-loader.gif" style="vertical-align:middle;"> Loading source. Please wait...';
    	$('script_source_container').style.display = '';
    	
    	var url = '/server/server.php?_cmd=get_script_template_source&version='+version+"&scriptid="+scriptid;
    	new Ajax.Request(url,
		{ 
			method: 'get',
			contaner_name: 'script_source_container', 
			onSuccess: function(transport)
			{ 
				try
				{
					var content = transport.responseText.replace(/&/gm, '&amp;').replace(/</gm, '&lt;').replace(/>/gm, '&gt;');
					$(transport.request.options.contaner_name).innerHTML = '<pre style="margin:0px;"><code>'+content+'</code></pre>';
					
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
    	$('script_source_container').innerHTML = '';
    	$('script_source_container').style.display = 'none';
    	
    	$('source_img').src = '/images/dhtmlxtree/csh_vista/script_source_closed.gif';
    	$('source_link').innerHTML = "View source";
    	$('view_source_link').onclick = function()
    	{
    		ViewTemplateSource();
    	};
    }
    
    function HighLight()
    {
    	hljs.highlightBlock(document.getElementById('script_source_container').firstChild.firstChild);
    }