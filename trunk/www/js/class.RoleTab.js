var RoleTab = Class.create();
                         	RoleTab.prototype = {
                         		
                         		Roles:null,
								CurrentRoleObject:null,
                           		CurrentRoleScript:null,
                           		  	
                           		initialize:function()
                           		{
                           			this.Roles = new Array();
                           			this.CurrentRoleObject = null;
                           			this.CurrentRoleScript = null;
                           			
                           			this.elements = {
                           				
                           				c_role_name:  $('c_role_name'),
                           				c_role_arch:  $('c_role_arch'),
                           				c_role_amiid: $('c_role_amiid'),
                           				c_role_descr: $('c_role_descr'),
                           				c_role_type:  $('c_role_type'),
                           				
                           				c_author:     $('c_author'),
                           				c_author_tr:  $('c_author_tr'),
                           				c_warning:    $('c_warning'),
                           				
                           				mysql_bundle_every : $('mysql_rebundle_every'),
                           				mysql_make_backup : $('mysql_bcp'),
                           				mysql_bundle : $('mysql_bundle'),
                           				mysql_make_backup_every : $('mysql_bcp_every'),
                           				
                           				reboot_timeout: $('reboot_timeout'),
                           				launch_timeout: $('launch_timeout'),
                           				status_timeout: $('status_timeout'),
                           				
                           				scal_min_instances : $('minCount'),
                           				scal_max_instances : $('maxCount'),
										scaling : 0,
										                            				
                           				pt_placement: $('availZone'),
                           				pt_type	: $('iType'),
                           				
                           				elastic_ips : $('use_elastic_ips'),
                           				
                           				ebs_snapid: $('ebs_snapid'),
                           				ebs_size: $('ebs_size'),
                           				ebs_mount: $('ebs_mount'),
                           				ebs_mountpoint: $('ebs_mountpoint')
                           			};
                           		},
                           		
                           		UpdateCurrentRoleObject: function()
                           		{
                           			if (!this.CurrentRoleObject)
                           				return;
                           			
                           			/* Update object */
                           			if (this.CurrentRoleObject.alias == 'mysql')
                           			{
                         				this.CurrentRoleObject.options.mysql_bundle_every = this.elements.mysql_bundle_every.value;
                           				this.CurrentRoleObject.options.mysql_bundle = this.elements.mysql_bundle.checked;
                           				
                           				this.CurrentRoleObject.options.mysql_make_backup = this.elements.mysql_make_backup.checked; 
                           				this.CurrentRoleObject.options.mysql_make_backup_every = this.elements.mysql_make_backup_every.value;
                           			}
                           				
                           			this.CurrentRoleObject.options.reboot_timeout = this.elements.reboot_timeout.value; 	
                           			this.CurrentRoleObject.options.status_timeout = this.elements.status_timeout.value;
                           			
                           			this.CurrentRoleObject.options.launch_timeout = this.elements.launch_timeout.value;
                           				
                           			this.CurrentRoleObject.options.min_instances = this.elements.scal_min_instances.value; 	
                           			this.CurrentRoleObject.options.max_instances = this.elements.scal_max_instances.value;
                           				                             				
                           			this.CurrentRoleObject.options.placement = this.elements.pt_placement.value; 
                           			this.CurrentRoleObject.options.i_type = this.elements.pt_type.value;
                           				
                           			this.CurrentRoleObject.options.use_elastic_ips = this.elements.elastic_ips.checked;
                           			
                           			/** EBS **/
                           			this.CurrentRoleObject.options.use_ebs = false;
                           			this.CurrentRoleObject.options.ebs_size = '';
                           			this.CurrentRoleObject.options.ebs_snapid = '';
                           			
                 					this.CurrentRoleObject.options.ebs_mount = this.elements.ebs_mount.checked;
                 					this.CurrentRoleObject.options.ebs_mountpoint = this.elements.ebs_mountpoint.value;
                 					                           			
                           			var elems = $('itab_contents_ebs').select('[name="ebs_ctype"]');
                           			for (var i = 0; i < elems.length; i++)
                           			{
                           				if (elems[i].checked == true)
                           				{
                           					if (elems[i].value == '2')
                           					{
                           						this.CurrentRoleObject.options.use_ebs = true;
                           						this.CurrentRoleObject.options.ebs_size = this.elements.ebs_size.value;
                           					}
                           					else if (elems[i].value == '3')
                           					{
                           						this.CurrentRoleObject.options.use_ebs = true;
                           						this.CurrentRoleObject.options.ebs_snapid = this.elements.ebs_snapid.value;
                           					}
                           				}
                           			}
                           			
                           			/*********/
                           			
                           			/*** Role params ****/
                           			var params = $('itab_contents_params').select('[id="role_params"]');
                           			
                           			for (var i = 0; i < params.length; i++)
                           			{
                           				if (params[i])
                           				{
                           					if (params[i].type != 'checkbox')
                           						this.CurrentRoleObject.params[params[i].name] = params[i].value;
                           					else
                           					{
                       							this.CurrentRoleObject.params[params[i].name] = params[i].checked ? 1 : 0;
                           					}
                           				}
                           			}
                           			
                           			/*******************/
                           			
                           			/** Update scripts **/
                           			this.UpdateCurrentRoleScript();
                           			/********************/
                           			
                         			/* Finish*/
                           		},
                           		
                           		SetCurrentRoleObject: function(ami_id, add_to_farm)
                           		{
                           			$('role_info_link').href = "role_info.php?ami_id="+ami_id;
                           			                           			
                           			var nodeID = ami_id;
                           			while(tree.getLevel(nodeID) != 1)
                           			{
                           				nodeID = tree.getParentId(nodeID);
                           			}
                           			
                           			if (nodeID == 'Custom')
                           			{
                           				$('comments_links_row').style.display = 'none';
                           			}
                           			else
                           			{
                           				$('comments_links_row').style.display = '';
                           				var ccount = tree.getUserData(ami_id, 'comments_count');
                           				
                           				if (ccount > 0)
                           				{
                           					$('comments_link').href = "role_info.php?ami_id="+ami_id+"#comments";
                           					$('comments_link').innerHTML = 'Comments ('+ccount+')';
                           				}
                           				else
                           				{
                           					$('comments_link').href = "role_info.php?ami_id="+ami_id+"#addcomment";
                           					$('comments_link').innerHTML = 'Leave a comment';
                           				}
                           			}

                           			if (!add_to_farm)
                           			{
                           				var roleObject = GetRoleObjectFromTree(ami_id);
                           				
                           				if (roleObject.name != roleObject.alias)
                           				{
                           					$('c_based_on_tr').style.display = '';
                           					$('c_based_on').innerHTML = roleObject.alias;
                           				}
                           				else
                           				{
                           					$('c_based_on_tr').style.display = 'none';
                           				}
                           				                           				
                           				//TODO: Create Javascript class Enum.
                           				$('arch_i386').removeClassName("ui_enum_selected");
                           				$('arch_x86_64').removeClassName("ui_enum_selected");
                           				$('arch_'+roleObject.arch).addClassName("ui_enum_selected");
                           				
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
                           				
                           				
                           				$('tab_roles').style.display = '';
                           				$('tab_name_roles').innerHTML = roleObject.name;
                           				SetActiveTab('roles');
                           				HideIntableTabs('info');
                           				SetActiveTab_i('info');
                           			}
                           			else
                           			{
	                           			ShowIntableTabs();
	                           			
	                           			try
	                           			{
		                           			$('tab_roles').style.display = '';
	                           				$('tab_name_roles').innerHTML = this.Roles[ami_id].name;
	                           				SetActiveTab('roles'); 
	                           				SetActiveTab_i('info');
	                           			}
	                           			catch(e)
	                           			{
	                           			
	                           			}
	                           			
	                           			if (this.CurrentRoleObject && this.CurrentRoleObject.ami_id == ami_id)
	                           			{
	                           				if (this.CurrentRoleObject.alias == 'mysql')
	                           				{
	                           					this.elements.mysql_bundle_every.value = this.CurrentRoleObject.options.mysql_bundle_every;
	                           					this.elements.mysql_bundle.checked = this.CurrentRoleObject.options.mysql_bundle;
	                           					
	                           					this.elements.mysql_make_backup.checked = this.CurrentRoleObject.options.mysql_make_backup;
	                           					this.elements.mysql_make_backup_every.value = this.CurrentRoleObject.options.mysql_make_backup_every;
	                           					
	                           					$('itab_mysql').style.display = '';
	                           				}
	                           				else
	                           				{
	                           					$('itab_mysql').style.display = 'none';
	                           				}
	                           				
	                           				return true;
	                           			}
	                           			
										this.UpdateCurrentRoleObject();
										this.CurrentRoleObject = false;
										
										//
										// Load role params
										//
	            	            		var container = $('role_parameters');
	            	            		
	            	            		// Setup loader...
	            	            		container.innerHTML = '<div align="center"><img src="/images/snake-loader.gif" style="vertical-align:middle;">&nbsp;Loading. Please wait...</div>';
	            	            		
	            	            		// Request Dataform from server...
	            	            		new Ajax.Request('/server/server.php?_cmd=get_role_params&farmid='+FARM_ID+'&ami_id='+ami_id,
										{ 
											method: 'get',
											onSuccess: function(transport)
											{ 
												try
												{
													$('role_parameters').update("<table width='100%'><tr><td width='28%'></td><td></td></tr>"+transport.responseText+"</table>");
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
	                           			$('event_script_config_form_description').innerHTML = "";
	                					$('event_script_config_container').innerHTML = "";
	                					$('event_script_config_title').style.display = "none";
	                					 
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
	                           			
	                           			if (this.Roles[ami_id])
	                           			{
	                           				
	                           				this.CurrentRoleObject = this.Roles[ami_id];
	                           				
	                           				/** Setup scripts **/
	                           				for(key in this.CurrentRoleObject.scripts)
	                           				{
	                           					if (events_tree._globalIdStorageFind(key) != 0)
	                           					{
	                           						events_tree.setCheck(key, 1);
	                           						
	                           						//moveNode: Node
	                           						var mn_n = events_tree._globalIdStorageFind(key);
	                           						var pid =  events_tree.getParentId(key);
	                           						var mn_t = events_tree._globalIdStorageFind(pid);
	                           						
	                           						var indexItem = events_tree.getItemIdByIndex(pid, this.CurrentRoleObject.scripts[key].order_index);
	                           						
	                           						var mn_b = events_tree._globalIdStorageFind(indexItem);
	                           						
	                           						events_tree._moveNodeTo(mn_n, mn_t, mn_b);
	                           						
	                           						//_moveNodeTo=function(itemObject,targetObject,beforeNode)
	                           						
	                           						events_tree.openItem(pid);
	                           					}
	                           				}
	                           				/******************/
	                           				
	                           				if (this.CurrentRoleObject.name != this.CurrentRoleObject.alias)
	                           				{
	                           					$('c_based_on_tr').style.display = '';
	                           					$('c_based_on').innerHTML = this.CurrentRoleObject.alias;
	                           				}
	                           				else
	                           				{
	                           					$('c_based_on_tr').style.display = 'none';
	                           				}
	                           				
	                           				//TODO: Create Javascript class Enum.
	                           				$('arch_i386').removeClassName("ui_enum_selected");
	                           				$('arch_x86_64').removeClassName("ui_enum_selected");
	                           				$('arch_'+this.CurrentRoleObject.arch).addClassName("ui_enum_selected");
	                           				
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
	                           				
	                           				if (this.CurrentRoleObject.alias == 'mysql')
	                           				{
	                           					this.elements.mysql_bundle_every.value = this.CurrentRoleObject.options.mysql_bundle_every;
	                           					this.elements.mysql_bundle.checked = this.CurrentRoleObject.options.mysql_bundle;
	                           					
	                           					this.elements.mysql_make_backup.checked = this.CurrentRoleObject.options.mysql_make_backup;
	                           					this.elements.mysql_make_backup_every.value = this.CurrentRoleObject.options.mysql_make_backup_every;
	                           					
	                           					$('itab_mysql').style.display = '';
	                           				}
	                           				else
	                           				{
	                           					$('itab_mysql').style.display = 'none';
	                           				}	
	                           				
	                           				this.elements.reboot_timeout.value = this.CurrentRoleObject.options.reboot_timeout;
	                           				this.elements.launch_timeout.value = this.CurrentRoleObject.options.launch_timeout;
	                           				this.elements.status_timeout.value = this.CurrentRoleObject.options.status_timeout;
	                           				
	                           				this.elements.scal_min_instances.value = this.CurrentRoleObject.options.min_instances;
	                           				this.elements.scal_max_instances.value = this.CurrentRoleObject.options.max_instances;
	                           				
	                           				trackbar.getObject('scaling_trackbar', true).init({
												leftValue: parseFloat(this.CurrentRoleObject.options.min_LA),
												rightValue: parseFloat(this.CurrentRoleObject.options.max_LA),
												onMove: function ()
												{
													window.RoleTabObject.OnLAChanged(this.leftValue, this.rightValue);
												} 
											}, 'scaling_trackbar');
	                           				
	                           				this.elements.pt_placement.value = this.CurrentRoleObject.options.placement;
	                           				
	                           				//TODO: Do this only if arch changed...
	                           				// Clear all types
	                           				var el = this.elements.pt_type; 
											while(el.firstChild) { 
												el.removeChild(el.firstChild); 
											}
																						
											for (var i = 0; i < i_types[this.CurrentRoleObject.arch].length; i ++)
											{
												var opt = document.createElement("OPTION");
												opt.value = i_types[this.CurrentRoleObject.arch][i];
												opt.innerHTML = i_types[this.CurrentRoleObject.arch][i];
												this.elements.pt_type.appendChild(opt); 
											}
	                           				
	                           				if (this.CurrentRoleObject.options.i_type)
	                           					this.elements.pt_type.value = this.CurrentRoleObject.options.i_type;
	                           				else
	                           					this.CurrentRoleObject.options.i_type = this.elements.pt_type.value;
	                           					
	                           				this.elements.elastic_ips.checked = this.CurrentRoleObject.options.use_elastic_ips;
	                           				
	                           				/***** EBS *******/
	                           				var elems = $('itab_contents_ebs').select('[name="ebs_ctype"]');
		                           			for (var i = 0; i < elems.length; i++)
		                           			{
		                           				if (elems[i].value == 1)
		                           				{
		                           					if (!this.CurrentRoleObject.options.use_ebs)
		                           					{
		                           						elems[i].checked = true;
		                           						ShowEBSOptions(1);
		                           					}
		                           				}
		                           				else if (elems[i].value == 2)
		                           				{
		                           					if (this.CurrentRoleObject.options.use_ebs && this.CurrentRoleObject.options.ebs_size != '')
		                           					{
		                           						this.elements.ebs_size.value = this.CurrentRoleObject.options.ebs_size;
		                           						elems[i].checked = true;
		                           						ShowEBSOptions(2);
		                           					}
		                           				}
		                           				else if (elems[i].value == 3)
		                           				{
		                           					if (this.CurrentRoleObject.options.use_ebs && this.CurrentRoleObject.options.ebs_snapid != '')
		                           					{
		                           						this.elements.ebs_snapid.value = this.CurrentRoleObject.options.ebs_snapid;
		                           						elems[i].checked = true;
		                           						ShowEBSOptions(3);
		                           					}
		                           				}
		                           			}
		                           			
		                           			this.elements.ebs_mount.checked = this.CurrentRoleObject.options.ebs_mount;
		                           			
		                           			if (this.CurrentRoleObject.options.ebs_mountpoint)
		                           				this.elements.ebs_mountpoint.value = this.CurrentRoleObject.options.ebs_mountpoint;
		                           				
		                           			this.elements.ebs_mountpoint.disabled = !this.elements.ebs_mount.checked;
	                           				/*****************/
	                           			}
	                           			else
	                           			{
	                           				alert('FAIL: setCurrentRoleObject();');
	                           			}
	                           		}
                           		},
                           		
                           		OnLAChanged: function(leftValue, rightValue)
								{
									try
									{
										if (this.CurrentRoleObject)
										{
											this.CurrentRoleObject.options.min_LA = parseFloat(leftValue);
											this.CurrentRoleObject.options.max_LA = parseFloat(rightValue);
										}
									}
									catch(e)
									{
										alert(e);
									}
								},
								
								HidePageError: function()
								{
									var err_obj = $('Webta_ErrMsg');
									err_obj.style.display = 'none';
								},
								
								ShowPageError: function(error)
								{
									var err_obj = $('Webta_ErrMsg');
									err_obj.innerHTML = error;
									err_obj.style.display = '';
									
									$('button_js').disabled = false;
									$('btn_loader').style.display = 'none';
									
									new Effect.Pulsate(err_obj);
								},
								
								SubmitForm: function()
								{
									this.UpdateCurrentRoleObject();
								   
								   	$('button_js').disabled = true;
								   	$('btn_loader').style.display = '';
								   
								    var url = '/server/farm_manager.php?action='+MANAGER_ACTION+"&farm_name="+$('farm_name').value+"&farm_id="+FARM_ID; 
									
									var postArray = [];
									for (ami_id in this.Roles)
									{
										if (ami_id.substring(0, 4) == 'ami-')
										{
											postArray[postArray.length] = this.Roles[ami_id]; 
										}
									}
																			
									var postBody = postArray.toJSON();
									
									this.HidePageError();
									
									new Ajax.Request(url,
									{ 
										method: 'post',
										postBody: postBody, 
										onSuccess: function(transport)
										{ 
												try
												{
 												var response = transport.responseText.evalJSON(true);
 												if (response.result == 'error')
 												{
 													window.RoleTabObject.ShowPageError("Cannot build farm: "+response.data);
 												}
 												else
 												{
 													if (MANAGER_ACTION == 'create')
 														window.location.href = '/farms_control.php?farmid='+response.data+"&new=1";
 													else
 														window.location.href = '/farms_view.php?code=1';
 												}
 											}
 											catch(e)
 											{
 												window.RoleTabObject.ShowPageError("Unexpected exception in javascript:" + e.message);
 											}	
										},
										
										onException: function()
										{
											window.RoleTabObject.ShowPageError("Cannot proceed your request at this time. Please try again later.");
										},
										
										onFailure: function()
										{
											window.RoleTabObject.ShowPageError("Cannot proceed your request at this time. Please try again later.");
										}
									});  
								},
								
								AddRoleToFarm: function (ami_id, role_object)
								{
									this.UpdateCurrentRoleObject();
									
									if (!this.Roles[ami_id])
									{		
										if (ami_id != null)
											this.Roles[ami_id] = GetRoleObjectFromTree(ami_id);
										else
											this.Roles[role_object.ami_id] = role_object;
									}
									else
									{
										alert('FAIL: AddRoleToFarm();');
									}	
								},
								
								RemoveRoleFromFarm: function(ami_id)
								{
									if (this.Roles[ami_id])
									{		
										if (this.CurrentRoleObject && this.CurrentRoleObject.ami_id == ami_id)
											this.CurrentRoleObject = false;
										
										this.Roles[ami_id] = false;
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
									
									this.CurrentRoleObject.scripts[this.CurrentRoleScript].target = $('event_script_target_value').value;
									this.CurrentRoleObject.scripts[this.CurrentRoleScript].version = $('script_version').value;
									this.CurrentRoleObject.scripts[this.CurrentRoleScript].timeout = $('scripting_timeout').value;
									
									this.CurrentRoleObject.scripts[this.CurrentRoleScript].order_index = events_tree.getIndexById(this.CurrentRoleScript);
									
									this.CurrentRoleObject.scripts[this.CurrentRoleScript].issync = $('issync_1').checked ? 1 : 0;
									
									var config_container = $('event_script_config_container');
									var elems = config_container.select('input.configopt');
									
									try
									{
										for (var i = 0; i < elems.length; i++)
										{
											this.CurrentRoleObject.scripts[this.CurrentRoleScript].config[elems[i].name] = elems[i].value.replace("'", "\'");										
										}
									}
									catch(e)
									{
									
									}	
								},
								
								SetupConfigForm: function(current_version)
								{
								    var v = 0;
									var fields = [];
									var show_config_title = false;
									this.CurentRoleScriptVersions.each(function(item){
									    if (item.revision == current_version || (current_version == 'latest' && item.revision > v))
									    {
									        v = item.revision;
									        fields = item.fields;
									    }
									});
									
									$('event_script_config_container').innerHTML = '';
									
									try
									{
    									eval("var fields = "+fields+";");
                                    	
                                    	for(var key in fields)
                                    	{
                                    		if (typeof(fields[key]) == 'string')
                                    		{
    											$('event_script_config_container').insert(
    												ConfigFieldTemplate.evaluate({name:key, title:fields[key]})
    											);
    											
    											show_config_title = true;
                                    		}
                                    	}
                                    }
                                    catch(e){}	
                                	
                                	if (show_config_title)
            	                		$('event_script_config_title').style.display = '';
            	                	else
            	                		$('event_script_config_title').style.display = 'none';
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
																				
										
										var config_container = $('event_script_config_container');
										var elems = config_container.select('input.configopt');
										
										for (var i = 0; i < elems.length; i++)
										{
											if (this.CurrentRoleObject.scripts[this.CurrentRoleScript].config[elems[i].name])
												elems[i].value = this.CurrentRoleObject.scripts[this.CurrentRoleScript].config[elems[i].name];										
										}
										
										if (event_name == 'HostDown')
										{
											if (this.CurrentRoleObject.scripts[this.CurrentRoleScript].target == 'instance')
												this.CurrentRoleObject.scripts[this.CurrentRoleScript].target = 'role';
											
											$('event_script_target_value_instance').parentNode.removeChild($('event_script_target_value_instance'));
										}
										else
										{
											if (!$('event_script_target_value_instance'))
											{
												var opt = document.createElement('OPTION');
												opt.value = 'instance';
												opt.innerHTML = 'That instance only';
												opt.id = 'event_script_target_value_instance';
												
												$('event_script_target_value').insertBefore(opt, $('event_script_target_value_role'));
											}
										}
										
										$('event_script_target_value').value = this.CurrentRoleObject.scripts[this.CurrentRoleScript].target;
										$('script_version').value = this.CurrentRoleObject.scripts[this.CurrentRoleScript].version;
										$('scripting_timeout').value = this.CurrentRoleObject.scripts[this.CurrentRoleScript].timeout;
										
										if (this.CurrentRoleObject.scripts[this.CurrentRoleScript].issync == 1)
										{
											$('issync_1').checked = true;
										}
										else
										{
											$('issync_0').checked = true;
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