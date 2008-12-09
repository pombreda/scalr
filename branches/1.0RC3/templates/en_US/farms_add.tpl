{include file="inc/header.tpl" noheader=1}
	<div id="role_info_popup" align="left" style="display:none;">
    	<div id="popup_contents" style="margin-top:5px;margin-left:5px;">
    		
    	</div>
	</div>
	<div id="popup_help" align="left" style="display:none;owerflow:hidden;">
    	<div id="popup_help_contents" style="padding-top:0px;padding-left:30px;background: url(/images/icon_ihelp.gif) #ffffff no-repeat 0px 0px;">

    	</div>
	</div>
    <link rel="STYLESHEET" type="text/css" href="/css/dhtmlXTree.css">
    <link href="css/popup.css" rel="stylesheet" type="text/css" />
    <script language="javascript" type="text/javascript" src="/js/dhtmlxtree/dhtmlXTree.js"></script>
    <script language="javascript" type="text/javascript" src="/js/dhtmlxtree/dhtmlXCommon.js"></script>
        
    <link rel="stylesheet" type="text/css" href="/js/FC_TrackBar/trackbar.css" />
	<script type="text/javascript" src="/js/FC_TrackBar/trackbar.js"></script>
	<script type="text/javascript" src="js/class.NewPopup.js"></script>
     
    <br />
    <table width="100%" cellpadding="10" cellspacing="0" border="0" style="height:100%">
        <tr valign="top">
            <td width="250" valign="top">
                {include file="inc/table_header.tpl" nofilter=1}
                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                	<tr valign="top">
                		<td>
                		    <div style="padding:5px;">
                		      <div id="inventory_tree" style="width:250px;height:500px;"></div>
                		    </div>                            
                            <div id="AttachLoader" style="display:none;text-align:center;padding-top:250px;background-color:#f4f4f4;position:absolute;top:94px;left:23px;width:400px;height:262px;">
                		      <img src="/images/snake-loader.gif" style="vertical-align:middle;display:;"> Processing...
                		    </div>
                            <script language="Javascript" type="text/javascript">
                            	var i_types = new Array();
                            	i_types['i386'] = new Array();
                            	{section name=id loop=$32bit_types}
                            	i_types['i386'][i_types['i386'].length] = '{$32bit_types[id]}';
                            	{/section}
                            	
                            	i_types['x86_64'] = new Array();
                            	{section name=id loop=$64bit_types}
                            	i_types['x86_64'][i_types['x86_64'].length] = '{$64bit_types[id]}';
                            	{/section}
                                
                                var MANAGER_ACTION = '{if $id}edit{else}create{/if}';
                                var FARM_ID		   = '{$id}';
                                                                
                            	{literal}
                            	var RoleTab = Class.create();
                            	RoleTab.prototype = {
                            		
                            		Roles:null,
									CurrentRoleObject:null,
                            		  	
                            		initialize:function()
                            		{
                            			this.Roles = new Array();
                            			this.CurrentRoleObject = null;
                            			
                            			this.elements = {
                            				
                            				c_role_name: $('c_role_name'),
                            				c_role_arch: $('c_role_arch'),
                            				c_role_amiid: $('c_role_amiid'),
                            				
                            				mysql_bundle_every : $('mysql_rebundle_every'),
                            				mysql_make_backup : $('mysql_bcp'),
                            				mysql_bundle : $('mysql_bundle'),
                            				mysql_make_backup_every : $('mysql_bcp_every'),
                            				
                            				reboot_timeout: $('reboot_timeout'),
                            				launch_timeout: $('launch_timeout'),
                            				
                            				scal_min_instances : $('minCount'),
                            				scal_max_instances : $('maxCount'),
											scaling : 0,
											                            				
                            				pt_placement: $('availZone'),
                            				pt_type	: $('iType'),
                            				
                            				elastic_ips : $('use_elastic_ips')
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
                            			this.CurrentRoleObject.options.launch_timeout = this.elements.launch_timeout.value;
                            				
                            			this.CurrentRoleObject.options.min_instances = this.elements.scal_min_instances.value; 	
                            			this.CurrentRoleObject.options.max_instances = this.elements.scal_max_instances.value;
                            				                             				
                            			this.CurrentRoleObject.options.placement = this.elements.pt_placement.value; 
                            			this.CurrentRoleObject.options.i_type = this.elements.pt_type.value;
                            				
                            			this.CurrentRoleObject.options.use_elastic_ips = this.elements.elastic_ips.checked;
                            			
                          				/* Finish*/
                            		},
                            		
                            		SetCurrentRoleObject: function(ami_id)
                            		{
                            			this.UpdateCurrentRoleObject();
                            			
                            			if (this.Roles[ami_id])
                            			{
                            				this.CurrentRoleObject = this.Roles[ami_id];
                            				
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
                            				
                            				if (this.CurrentRoleObject.alias == 'mysql')
                            				{
                            					this.elements.mysql_bundle_every.value = this.CurrentRoleObject.options.mysql_bundle_every;
                            					this.elements.mysql_bundle.checked = this.CurrentRoleObject.options.mysql_bundle;
                            					
                            					this.elements.mysql_make_backup.checked = this.CurrentRoleObject.options.mysql_make_backup;                            					
                            					this.elements.mysql_make_backup_every.value = this.CurrentRoleObject.options.mysql_make_backup_every;
                            					
                            					$('mysql_settings_section').style.display = '';
                            				}
                            				else
                            				{
                            					$('mysql_settings_section').style.display = 'none';
                            				}	
                            				
                            				this.elements.reboot_timeout.value = this.CurrentRoleObject.options.reboot_timeout;
                            				this.elements.launch_timeout.value = this.CurrentRoleObject.options.launch_timeout;
                            				
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
                            				
                            				/* Finish*/
                            				
                            				$('tab_roles').style.display = '';
                            				$('tab_name_roles').innerHTML = this.CurrentRoleObject.name;
                            				SetActiveTab('roles'); 
                            			}
                            			else
                            			{
                            				alert('FAIL: setCurrentRoleObject();');
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
									
									AddRoleToFarm: function (ami_id, alias, arch, name, role_object)
									{
										this.UpdateCurrentRoleObject();
										
										if (!this.Roles[ami_id])
										{		
											if (name != null && ami_id != null && alias != null && arch != null)
												this.Roles[ami_id] = new FarmRole(name, ami_id, alias, arch);
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
											this.Roles[ami_id] = false;
										}
										else
										{
											alert('FAIL: RemoveRoleFromFarm();');
										}
									}
                            	};
                            	
                            	var FarmRole = Class.create();
								FarmRole.prototype = {
									ami_id: null,
									alias: null,
									name: null,
									arch: null,
									options: null,
									
									initialize: function(name, ami_id, alias, arch) 
									{
										this.ami_id = ami_id;
										this.alias = alias;
										this.name = name;
										this.arch = arch;
										this.options = {
											mysql_bundle_every: 48,
											mysql_bundle: true,
                            				mysql_make_backup: false,
                            				mysql_make_backup_every: 180,
											min_instances: 1,
											max_instances: 2,
											reboot_timeout: 300,
											launch_timeout: (alias == 'mysql') ? 1200 : 300,
											min_LA: 2,
											max_LA: 5,
											placement: "",
											i_type: "",
											use_elastic_ips: false
										};
								  	}
								};
								
								{/literal}
								
								{if $roles}
                                var l_roles = {$roles};
                                {/if}
                                
								{literal}
								Event.observe(window, 'load', function(){
									window.RoleTabObject = new RoleTab();
									window.popup = new NewPopup('role_info_popup', {target: '', width: 270, height: 120, selecters: new Array()});
									
									window.popup_help = new NewPopup('popup_help', {target: '', width: 370, height: 120, selecters: new Array()});
									
									if (l_roles)
                                	{
		                                l_roles.each(function(item){
		                                	var f = new FarmRole(null, null, null, null);
		                                	Object.extend(f, item);
		                                	window.RoleTabObject.AddRoleToFarm(null, null, null, null, Object.clone(f));
		                                }); 
		                            }
								});
                            	{/literal}
                            	  
                            	tree = new dhtmlXTreeObject($('inventory_tree'),"100%","100%",0);
                                tree.setImagePath("images/dhtmlxtree/csh_vista/");
                                tree.enableCheckBoxes(true);
                                tree.enableThreeStateCheckboxes(true);
                                
                                sid = '{$sid}';
                                stype = '{$stype}';
                                
                                tree.enableDragAndDrop(false);
                                tree.setXMLAutoLoading("farm_amis.xml?farmid={$id}");
            	                tree.loadXML("farm_amis.xml?farmid={$id}");
            	                            	                
            	                {literal}
            	                
            	                tree.setOnSelectStateChange(function(itemId){
            	                	
            	                	if (tree.getUserData(itemId,"isFolder") == 1)
                						return false;
                						
                					if (tree.isItemChecked(itemId) != 1)
            	                		return false;
            	                		
            	                	RoleTabObject.SetCurrentRoleObject(itemId);
            	                		
            	                	return true;
            	                });
            	                            	                
            	                tree.attachEvent("onCheck", function(itemId, state)
            	                {            	                               	                               	                    
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
            	                
            	                function ManageTable(nodeid, state)
            	                {            	                    
            	                    if (state == 1)
            					    {            					           
            					        var arch = tree.getUserData(nodeid, "Arch");
            					        var alias = tree.getUserData(nodeid, 'alias');
            					        var name = tree._globalIdStorageFind(nodeid).label;
            					        
            					        RoleTabObject.AddRoleToFarm(nodeid, alias, arch, name);
            					        
            					        tree._selectItem(tree._globalIdStorageFind(nodeid), true);
            					        
            					        if (alias == "mysql")
            					        {
            					            SetMysqlRolesAccess(1, nodeid);
            					        }
            					    }
            					    else
            					    {            					        
                                        RoleTabObject.RemoveRoleFromFarm(nodeid);
            					        
            					        if (tree.getSelectedItemId() == nodeid)
            					        {
            					        	tree._unselectItem(tree._globalIdStorageFind(nodeid));
            					        	SetActiveTab('general');
            					        	$('tab_roles').style.display = 'none';
            					        }
            					           
            					        if (tree.getUserData(nodeid, 'alias') == "mysql")
            					        {
            					            SetMysqlRolesAccess(0, nodeid);
            					        }
            					    }
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
            	                
            	                function OnTabChanged(current_active_tab)
            	                {
            	                	
            	                }            	                            	                
            	                {/literal}
                            </script>
                		</td>
                	</tr>
                </table>
                {include file="inc/table_footer.tpl" disable_footer_line=1}
            </td>
            <td style="padding:0px;margin:0px;" valign="top">
                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="padding:0px;margin:0px;">
                    <tr>
                        <td style="padding:0px;margin:0px;padding-top:10px;">
                           {include file="inc/table_header.tpl" nofilter=1 tabs=1}
								{include intable_classname="tab_contents" intableid="tab_contents_general" visible="" file="inc/intable_header.tpl" header="Farm information" color="Gray"}
                           		<tr>
                            		<td width="20%">Name:</td>
                            		<td><input type="text" class="text" name="farm_name" id="farm_name" value="{$farminfo.name}" /></td>
                            	</tr>
                           		{include file="inc/intable_footer.tpl" color="Gray"}
                           		
                           		<div id="tab_contents_roles" class="tab_contents" style="display:none;">
                           			{include file="inc/intable_header.tpl" header="Role information" color="Gray"}
                           			<tr>
	                            		<td>Role name:</td>
	                            		<td id="c_role_name"></td>
	                            	</tr>
	                            	<tr id="c_based_on_tr" style="display:none;">
	                            		<td>Based on role:</td>
	                            		<td id="c_based_on"></td>
	                            	</tr>
	                            	<tr>
	                            		<td>AMI ID:</td>
	                            		<td id="c_role_amiid"></td>
	                            	</tr>
	                            	<tr>
	                            		<td>Architecture:<span id="c_role_arch" style="display:none;"></span></td>
	                            		<td>
	                            			<span id="arch_i386" class="ui_enum">i386</span>&nbsp;&nbsp;<span id="arch_x86_64" class="ui_enum">x86_64</span>
	                            		</td>
	                            	</tr>
	                            	{include file="inc/intable_footer.tpl" color="Gray"}
                           			
                           			{include intableid="mysql_settings_section" file="inc/intable_header.tpl" header="MySQL Backup settings" color="Gray"}
	                            	<tr>
	                            		<td colspan="2"><input style="vertical-align:middle;" type="checkbox" {if $farminfo.mysql_bundle == 1}checked{/if} name="mysql_bundle" id="mysql_bundle" value="1"> Bundle and save mysql data snapshot every <img style="vertical-align:middle;cursor:pointer;" title="Help" onclick="ShowHelp(event, 'mysql_help', this);" src="/images/icon_shelp.gif">: <input type="text" size="3" class="text" id="mysql_rebundle_every" name="mysql_rebundle_every" value="{if $farminfo.mysql_rebundle_every}{$farminfo.mysql_rebundle_every}{else}48{/if}" /> hours</td>
	                            	</tr>
	                            	<tr>
	                            		<td colspan="2"><input style="vertical-align:middle;" type="checkbox" {if $farminfo.mysql_bcp == 1}checked{/if} name="mysql_bcp" id="mysql_bcp" value="1"> Periodically backup databases every: <input type="text" size="3" class="text" id="mysql_bcp_every" name="mysql_bcp_every" value="{if $farminfo.mysql_bcp_every}{$farminfo.mysql_bcp_every}{else}180{/if}" /> minutes</td>
	                            	</tr>
	                           		{include file="inc/intable_footer.tpl" color="Gray"}
                           			
                           			{include file="inc/intable_header.tpl" header="Scaling options" color="Gray"}
                           			<tr>
	                            		<td>Minimum instances <img style="vertical-align:middle;cursor:pointer;" title="Help" onclick="ShowHelp(event, 'mini_help', this);" src="/images/icon_shelp.gif">:</td>
	                            		<td><input type="text" size="2" class="text" id="minCount" name="minCount[{$servers[id].id}]" value="{$servers[id].min_count}"></td>
	                            	</tr>
	                            	<tr>
	                            		<td>Maximum instances <img style="vertical-align:middle;cursor:pointer;" title="Help" onclick="ShowHelp(event, 'maxi_help', this);" src="/images/icon_shelp.gif">:</td>
	                            		<td><input type="text" size="2" class="text" id="maxCount" name="maxCount[{$servers[id].id}]" value="{$servers[id].max_count}"></td>
	                            	</tr>
	                            	<tr valign="middle">
	                            		<td>Scaling load averages <img style="vertical-align:middle;cursor:pointer;" title="Help" onclick="ShowHelp(event, 'scaler_help', this);" src="/images/icon_shelp.gif">:</td>
	                            		<td style="padding-left:10px;">
	                            			<div id="scaling_trackbar">

											</div>
											<input type='hidden' id='minLA_{$servers[id].id}' name='minLA[{$servers[id].id}]' value='{$servers[id].min_LA}'>
											<input type='hidden' id='maxLA_{$servers[id].id}' name='maxLA[{$servers[id].id}]' value='{$servers[id].max_LA}'>
	                            		</td>
	                            	</tr>
                           			{include file="inc/intable_footer.tpl" color="Gray"}
                           			                           			
                           			{include file="inc/intable_header.tpl" header="Placement and type" color="Gray"}
                           			<tr>
	                            		<td>Placement:</td>
	                            		<td>
	                            			<select id="availZone" name="availZone[{$servers[id].id}]" class="text">
	                                    		{section name=zid loop=$avail_zones}
	                                    			{if $avail_zones[zid] == ""}
	                                    			<option {if $servers[id].avail_zone == ""}selected{/if} value="">Choose randomly</option>
	                                    			{else}
	                                    			<option {if $servers[id].avail_zone == $avail_zones[zid]}selected{/if} value="{$avail_zones[zid]}">{$avail_zones[zid]}</option>
	                                    			{/if}
	                                    		{/section}
	                                    	</select>
	                                    </td>
	                            	</tr>
	                            	<tr>
	                            		<td>Instances type:</td>
	                            		<td>
	                            			<select id="iType" name="iType[{$servers[id].id}]" class="text">
	                                    		{section name=zid loop=$servers[id].types}
	                                    			<option {if $servers[id].instance_type == $servers[id].types[zid]}selected{/if} value="{$servers[id].types[zid]}">{$servers[id].types[zid]}</option>
	                                    		{/section}
	                                    	</select>
	                            		</td>
	                            	</tr>
                           			{include file="inc/intable_footer.tpl" color="Gray"}
                           			
                           			{include file="inc/intable_header.tpl" header="Elastic IPs" color="Gray"}
                           			<tr>
                           				<td>Use Elastic IPs <img style="vertical-align:middle;cursor:pointer;" title="Help" onclick="ShowHelp(event, 'eips_help', this);" src="/images/icon_shelp.gif">:</td>
                           				<td>
	                                    	<input {if $servers[id].use_elastic_ips == 1}checked{/if} {if $farminfo.status == 1 && $servers[id].use_elastic_ips == 1}disabled{/if} type="checkbox" id="use_elastic_ips" name="use_elastic_ips[{$servers[id].id}]" value="1">
	                                    	{if $servers[id].use_elastic_ips == 1 && $farminfo.status == 1}<input type="hidden" name="use_elastic_ips[{$servers[id].id}]" value="1" />{/if}
                           				</td>
                           			</tr>
                           			{include file="inc/intable_footer.tpl" color="Gray"}
                           			
                           			{include file="inc/intable_header.tpl" header="Timeouts" color="Gray"}
                           			<tr>
										<td colspan="2">Terminate instance if it will not send 'rebootFinish' event after reboot in <input name="reboot_timeout" type="text" class="text" id="reboot_timeout" value="" size="3"> seconds.</td>
									</tr>
									<tr>
										<td colspan="2">Terminate instance if it will not send 'hostUp' or 'hostInit' event after launch in <input name="launch_timeout" type="text" class="text" id="launch_timeout" value="" size="3"> seconds.</td>
									</tr>
                           			{include file="inc/intable_footer.tpl" color="Gray"}
							{include file="inc/table_footer.tpl" disable_footer_line=1}
							<br>
							{include file="inc/table_header.tpl" nofilter=1}
                           	{include
								file="inc/table_footer.tpl" 
								colspan=9 
								button_js_name='Save' 
								button_js=1 
								button_js_action='window.RoleTabObject.SubmitForm();' 
								loader='Building farm. Please wait...'
 							} 
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
        
    <script language="Javascript">
    $('button_js').style.display='';
	var NoCloseButton = false;
	
	{literal}
	function ShowDescriptionPopup(event, id, obj)
	{
		var event = event || window.event;
		
		var pos = Position.positionedOffset(obj);
		
		pos[0] = Event.pointerX(event);
		pos[1] = Event.pointerY(event);
		
		$('popup_contents').innerHTML = $('role_description_tooltip_'+id).innerHTML;
		
		popup.raisePopup(pos);
	}
	
	function ShowHelp(event, content_id, obj)
	{
		var event = event || window.event;
		
		var pos = Position.positionedOffset(obj);
		
		pos[0] = Event.pointerX(event)+50;
		pos[1] = Event.pointerY(event);
		
		$('popup_help_contents').innerHTML = $(content_id).innerHTML;
		
		popup_help.raisePopup(pos);
	}
	{/literal}
	
	$('tab_roles').style.display = 'none';
	
	SetActiveTab('general');
	</script>
	
	{section name=id loop=$roles_descr}
		{if $roles_descr[id].description}
		<span id="role_description_tooltip_{$roles_descr[id].ami_id}" style="display:none;">
			<div><span style="color:#888888;">Role name:</span> {$roles_descr[id].name}</div>
			<div><span style="color:#888888;">AMI ID:</span> {$roles_descr[id].ami_id}</div>
			<div><span style="color:#888888;">Description:</span><br> {$roles_descr[id].description}</div>
		</span>
		{/if}
	{/section}
	<span id="mini_help" style="display:none;">
		Always keep at least this many running instances	
	</span>
	<span id="maxi_help" style="display:none;">
		Scalr will not launch more instances	
	</span>
	<span id="eips_help" style="display:none;">
		If this option is enabled, 
		Scalr will assign Elastic IPs to all instances of this role. It usually takes few minutes for IP to assign.
		The amount of allocated IPs increases when new instances start, 
		but not decreases when instances terminated.
		Elastic IPs are assigned after instance initialization. 
		This operation takes up to 10 minutes. During this time instance is not available from 
		the outside and not included in application DNS zone.
	</span>
	<span id="mysql_help" style="display:none;">
		MySQL snapshots contain a hotcopy of mysql data directory, file that holds binary log position and debian.cnf
		<br>
		When farm starts:<br> 
		1. MySQL master dowloads and extracts a snapshot from S3<br>
		2. When data is loaded and master starts, slaves download and extract a snapshot as well.<br>
		3. Slaves are syncing with master for some time
	</span>
	<span id="scaler_help" style="display:none;">
		Agenda:<span style="font-size:6px;"><br /><br /></span> 
		<span style="padding:2px;padding-left:0px;margin-bottom:2px;"><span style="color:#9ef19c;background-color:#9ef19c;line-height:10px;width:10px;height:10px;">&nbsp;&nbsp;&nbsp;</span>&nbsp;&nbsp;- normal<br /></span>
		<span style="padding:2px;padding-left:0px;margin-bottom:2px;"><span style="color:#f7f9c8;background-color:#f7f9c8;line-height:10px;width:10px;">&nbsp;&nbsp;&nbsp;</span>&nbsp;&nbsp;- recommended only if you do not expect frequent load spikes/drops<br /></span>
		<span style="padding:2px;padding-left:0px;margin-bottom:2px;"><span style="color:#f7b8b8;background-color:#f7b8b8;line-height:10px;width:10px;">&nbsp;&nbsp;&nbsp;</span>&nbsp;&nbsp;- the difference is too small. Scaling may act unexpectedly (instances will be launched and terminated too frequently)<br /></span>
	</span>
{include file="inc/footer.tpl"}
