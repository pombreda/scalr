{include file="inc/header.tpl"}
<style>
	{literal}
		.td_with_padding
		{		
			padding-bottom: 2px;
			padding-top: 2px;
			margin: 0px;
		}
		.ScriptArgItem label
		{	
			width: 20%;
			font-size:10pt;
			padding-right:0;
			padding-bottom: 2px;
			padding-top: 2px;
			margin:0px;	
		}
		.ScriptArgItem input
		{	
			padding-left:0%;
		}
		.hideIfTerminate
		{
			display='none';
		}
		.hideFarm
		{
			display='';
		}
		.hideRole
		{
			display='none';
		}
		.hideServer
		{
			display='none';
		}
		.PanelItem
		{
			padding-bottom: 2px;
			padding-top: 2px;
		}
		input#issync_1 
		{
		 margin: 5px;
		}
		input#issync_0
		{
		 margin: 3px;
		}
		.loadmask
		{		
			float:left;
			padding-left: 20px;	
			background-image:url("../images/extjs-default/grid/loading.gif");
			display='';
			background-repeat: no-repeat;
		}
		  				
	{/literal}
	</style>
	<script src="/js/farm_role_server_loader.js"></script> 
	<script language="javascript">
	var serverCombo 		= null;
	var farmsCombo 			= null;
	var roleCombo 			= null;
	var scrtipCombo 		= null;
	var scriptRevisionCombo = null;
	var scriptFieldsPanel 	= null;
	var farmsStore			= null;
	var roleStore			= null;
	var serverStore			= null;
	var scriptStore 		= null;
	var scriptRevisionStore	= null;	
	var loadingMask 		= null;
	
	var loadedScriptId 			= {if $scriptid}	{$scriptid}		{else}''{/if};
	var loadedScriptRevision	= {if $version} 	{$version}		{else}0{/if};
	var loadedfarm_roleid		= {if $farm_roleid} {$farm_roleid}	{else}0{/if};
	var loadedfarmid			= {if $farmid}		{$farmid}		{else}0{/if};
	var loadedserver_id 		= {if $server_id}	'{$server_id}'	{else}''{/if};

	var loadedScriptArgs		= {if $values} 		{$values}		{else}''{/if};	
	var timeout					= {if $timeout} 	{$timeout}		{else}0{/if};
	var issync					= {if $issync} 		{$issync} 		{else}0{/if};
	var task					= {if $task}		'{$task}'		{else}''{/if};
	var target					= {if $target}		'{$target}'		{else}''{/if};
	{literal}
	
	
	Ext.onReady(function () 
	{
		HideComboByClassName('Role',false,false);
		HideComboByClassName('Server',false,false);				
				
		loadingMask = new Ext.LoadMask(Ext.getBody(), {msg:"Script execution request sending. Please wait..."});
		
//// Script revision
		scriptRevisionCombo = FarmRoleServerHelper.newScriptRevisionCombo(
				{				
					listeners:
					{
						select:function(combo, record, index)
						{	
							var la = Ext.get("loadArguments");
					
							if(la)
								la.dom.style.display = '';

							var fields = Ext.decode(record.data.fields);

				       		// if script asks for arguments
			       			if(Ext.isEmpty(fields) == false)
			       			{	
				       			LoadRevisionArguments(combo,fields,loadedScriptArgs);			       			
				    		}
				       		else
				       		{	     				
				       			if(scriptFieldsPanel) 									
									scriptFieldsPanel.removeAll();
			       			}
			       			
			       			if(la)
								la.dom.style.display = 'none';
		       						   				
						}
					}					
				},
				// storeConfig
				{
					listeners:
					{
						load:function(params, reader, callback, scope, arg)
						{				
							if((scriptCombo.value == loadedScriptId))
							{
								// select the latest script revision
								if(loadedScriptRevision == 0)
								{
									loadedScriptRevision = GetLatestRevision(scriptRevisionStore.data.items);
								}
									
								SelectComboByValue(scriptRevisionCombo,scriptRevisionStore,loadedScriptRevision,"revision");										
							}
							
							if(Ext.get("loadVersion"))
								Ext.get("loadVersion").dom.style.display = 'none';
						}
					}
				}

				);		
					
		scriptRevisionStore = scriptRevisionCombo.store;
		
//// scripts
		scriptCombo = FarmRoleServerHelper.newScriptCombo(
				{
					listeners:
					{
						select:function(combo, record, index)
						{								
							// load scipt's versions of the selected script by its id
							if(scriptRevisionCombo)
							{
			   					scriptRevisionStore.setBaseParam('scriptId',record.data.id);				   						    		
			   					scriptRevisionStore.load();
							}
			   				
			   				
							if(Ext.get("loadVersion"))
								Ext.get("loadVersion").dom.style.display = '';
								  							
							//  reset selected text in scriptRevisionCombo
			   				if(scriptRevisionCombo)
			   					scriptRevisionCombo.clearValue();
		   					
			   				if(scriptFieldsPanel) 								
								scriptFieldsPanel.removeAll();								
						}
					}	
				},
				{
					listeners:
					{
						load:function(params, reader, callback, scope, arg)
						{ 	
					 		SelectComboByValue(scriptCombo,scriptStore,loadedScriptId,"id");
							
					 		if(Ext.get("loadVersion"))
								Ext.get("loadVersion").dom.style.display = 'none';
						}	
					}
				}
			);
			
			scriptStore = scriptCombo.store;
			serverCombo = FarmRoleServerHelper.newServerCombo(
					{
						hiddenName: 'server_id',
						listeners:
						{
							select: function(combo,record,index)
							{
								if(Ext.get('server_radio'))
									Ext.get('server_radio').dom.checked = true;	

								if(Ext.get('create_menu_link'))
									Ext.get('create_menu_link').dom.disabled = true;

								HideAdditionalSettins(true);
										
							}							
						}
					},
					// serverStore
					{
						listeners:
						{							
							load:function(params, reader, callback, scope, arg)
							 { 	
						 		if(Ext.get("loadServer"))						 		
									Ext.get("loadServer").dom.style.display='none';
								
								if(params.data.length > 0)
								{ 
									// display servers if it's not empty									
									HideComboByClassName('Server',true,false);
									
									if(loadedserver_id)
				   						SelectComboByValue(serverCombo,serverStore,loadedserver_id,"id");
								}
								else
								 	// hide servers
									HideComboByClassName('Server',false,true);	
							 }							
						}
					}
				);
				serverStore = serverCombo.store;

//// Roles 
				roleCombo = FarmRoleServerHelper.newRoleCombo(
					{
						hiddenName: 'farm_roleid',
						listeners:
						{
							select:function(combo, record, index)
							{	
							 	if(Ext.get("loadServer"))
									Ext.get("loadServer").dom.style.display = '';
									
								if(serverStore && task != 'edit')
								{									
					    			serverStore.baseParams.farm_roleId = record.data.id;
					    			serverStore.baseParams.farmId = farmsCombo.value;						    		
					    			serverStore.load();
								}
								else
								{
									if(Ext.get("loadServer"))
										Ext.get("loadServer").dom.style.display = 'none';
								}
						    				    		
					    		HideComboByClassName('Server',false,false);	

								if(task == 'edit' && target != 'role')
								{																					    		
					    			Ext.get('role_radio').dom.checked = false;
								}								
								else
								{
									Ext.get('role_radio').dom.checked = true;
								}								
				    			
					    		//  reset selected text in instanceCombo
				   				if(serverCombo)
				   					serverCombo.clearValue();
			   					
				   				HideAdditionalSettins(false);				   					
							}
						}
					},
					
					// roleStore:
					{	
						listeners:
						{
							load:function(params, reader, callback, scope, arg)
							 {
								 // show roles field only if it's not empty		
								 if(Ext.get("loadRole"))						
									Ext.get("loadRole").dom.style.display='none'; 
									
								if(params.data.length > 0)
			       				{
			       					if(Ext.get('role_target_combo'))			       					
			       						HideComboByClassName('Role',true,false);
		       						
			       					if(loadedfarm_roleid && farmsCombo.value == loadedfarmid)
				   						SelectComboByValue(roleCombo,roleStore,loadedfarm_roleid,"id");				       					
			       				}
			       				else
			       				{
				       				if(Ext.get('role_target_combo'))		       						
			       						HideComboByClassName('Role',false,true);		       							
			       				}
							  }
						}
					}
				);
				roleStore = roleCombo.store;
				
//// Farms
				farmsCombo = FarmRoleServerHelper.newFarmsCombo(
					{
						hiddenName: 'farmid',
						listeners:
						{
							select:function(combo, record, index)
							{									
								HideComboByClassName('Role',false,false);
								HideComboByClassName('Server',false,false);	
								
								// load roles and show them only for "script_exec" task type	
								if(Ext.get("loadRole"))							
									Ext.get("loadRole").dom.style.display='';									
								// load farm roles of selected farm by farmId ( from farmsStore comboBox)			
				       			roleStore.baseParams.farmId = record.data.id; 
				       				roleStore.load();
			    						
								//  reset selected text in roleCombo
				   				if(roleCombo)
				   					roleCombo.clearValue();	
				   				
				   				if(Ext.get('farm_radio'))
									Ext.get('farm_radio').dom.checked = true;	

				   				HideAdditionalSettins(false);
				   			} 
						}
					},
					{
						listeners:
						{
							load:function(params, reader, callback, scope, arg)
							 {
								if(Ext.get("loadFarm"))
									Ext.get("loadFarm").dom.style.display='none';
								 // show farms field only if it's not empty
								if(params.data.length > 0)
			       				{
			       					if(Ext.get('farm_target_combo'))			       					
			       						HideComboByClassName('Farm',true,false);	

			       					if(loadedfarmid)
			       						SelectComboByValue(farmsCombo,farmsStore,loadedfarmid,"id");	 
			       				}
			       				else
			       				{
			       					if(Ext.get('farm_target_combo'))
			       						HideComboByClassName('Farm',false,true);
			       				}
							 }
						}
					}
				);
				farmsStore = farmsCombo.store;
				
				farmsStore.load();
				if(Ext.get("loadFarm"))
					Ext.get("loadFarm").dom.style.display='';
								
				if(scriptStore)
					scriptStore.load();
			
	});
	function SubmitTask(value)
	{			
		loadingMask.show();

		if(Ext.get('button_js'))
			Ext.get('button_js').dom.disabled = true;		
		
		document.forms[1].submit();		
	}
	
	function HideAdditionalSettins(hide)
	{
		if(hide)
		{
			if(Ext.get('addon_settings'))
				Ext.get('addon_settings').dom.style.display = 'none';
			
			if(Ext.get('create_menu_link'))
				Ext.get('create_menu_link').dom.disabled = true;	
		}
		else
		{
			if(Ext.get('addon_settings'))
				Ext.get('addon_settings').dom.style.display = '';
			
			if(Ext.get('create_menu_link'))
				Ext.get('create_menu_link').dom.disabled = false;
		}		
		
	}
	
	
	{/literal}	
	</script>
	
	<br />				 
	<div style="position:relative;width:auto;">
	
			<div id="script_loader" align="center" style="display:none;z-index:1000;position:absolute;top:7px;left:7px;background-color:#F0F0F0;right:7px;bottom:7px;vertical-align: middle;">
				<div align="center" style="position: absolute;left:50%; top: 50%;display: table-cell; vertical-align: middle;">
					<img style="vertical-align:middle;" src="/images/snake-loader.gif"> {t}Loading...{/t}
				</div>
			</div>
			
	{include file="inc/table_header.tpl" nofilter=1}
	
	<!--  Target  -->	
	{include file="inc/intable_header.tpl" header="Target" color="Gray"}
		<tr class="hideFarm" style="height:18px; display:">			
			<td class="td_with_padding" style="width:200px;">		
				<input type="radio" id="farm_radio" name="target" value="farm" onClick="HideAdditionalSettins(false)" style="vertical-align:middle;"  checked>
				On all servers of this farm : 
			</td>				
			<td class="td_with_padding">
				<div id="farm_target_combo" style="float:left; width:200px;"></div><div class="loadmask" id="loadRole" style="display:none;">Loading roles...</div><div class="loadmask" id="loadFarm" style="display:none;">Loading farms...</div>
			</td>
		</tr>
		<tr class="emptyFarm" style="height:18px; display:none">						
			<td class="td_with_padding" style="width:200px;">
				On all servers of this farm : 
			</td>				
			<td class="td_with_padding">
				<div id="farm_target_combo" style="float:left; width:200px;">Farms are not available</div>
			</td>
		</tr>	
				
		<tr class="hideRole" style="height:18px; display:none;">	
			<td class="td_with_padding">
				<input type="radio" id="role_radio" name="target" value="role" style="vertical-align:middle; display='none'" onClick="HideAdditionalSettins(false)"> 
				 On all servers of this role:
			 </td>
			 <td class="td_with_padding">
				<div id="role_target_combo"  style="float:left; width:200px;"></div><div class="loadmask" id="loadServer" style="display:none;">Loading servers...</div>											
			 </td>
		</tr>
		<tr class="emptyRole" style="height:18px; display:none;">
			<td class="td_with_padding">
			On all servers of this role:
			 </td>
			 <td class="td_with_padding">
				<div id="role_target_combo" style="float:left; width:400px;">There is no roles assigned to selected farm</div><div class="loadmask" id="loadServer" style="display:none;">Loading data...</div>
			 </td>
		</tr>		

    	<tr class="hideServer" style="height:18px; display:none;">      		
    		<td class="td_with_padding">
    			<input type="radio"  id="server_radio" name="target" value="instance" style="vertical-align:middle;" onClick="HideAdditionalSettins(true)">
    			On server:
    		</td>
    		<td class="td_with_padding">
    			<div id="server_target_combo" style="float:left; width:200px;"></div>
			</td>
    	</tr>
    	<tr class="emptyServer" style="height:18px; display:none;">
			<td class="td_with_padding">
				On server:
			 </td>
			 <td class="td_with_padding">
				<div id="role_target_combo" style="float:left; width:400px;">There is no running servers on selected role</div>
			 </td>
		</tr>		    	
		{include file="inc/intable_footer.tpl" color="Gray"}
		
<!-- Script settings -->
		{include intableid="script_options" file="inc/intable_header.tpl" header="Script options" color="Gray"}
			<tr>
				<td class="td_with_padding" style="width:200px;">Script:</td>
				<td class="td_with_padding">					
					<div id="script_target_combo" style="float:left; width:200px;"></div><div class="loadmask" id="loadVersion" style="display:none;">Loading revision...</div>
			    </td>
			</tr>
	        <tr>
				<td class="td_with_padding">Execution mode:</td>
				<td class="td_with_padding">
					<input type="radio" name="issync" value="1" id="issync_1" {if $issync == '1'}checked{/if} style="vertical-align:middle;"> {t}Synchronous{/t} &nbsp;&nbsp;
					<input type="radio" name="issync" value="0" id="issync_0" {if $issync != '1'}checked{/if} style="vertical-align:middle;"> {t}Asynchronous{/t} 
				</td>
				<td></td>
			</tr>		
			<tr>
				<td class="td_with_padding">Version:</td>
				<td class="td_with_padding">
					<div id="version_target_combo" style="float:left; width:200px;"></div><div class="loadmask" id="loadArguments" style="display:none;">Loading arguments...</div>
			    </td>
			</tr>	
			<tr>
				<td class="td_with_padding">Timeout:</td>
				<td class="td_with_padding">				
				<div><input type='text' class='text' id="timeout" name="timeout" value="{if $timeout}{$timeout}{else}1000{/if}" style="margin:0px; width:188px;" > seconds</div>
				</td>
				<td></td>
			</tr>
			<tr>
			<td colspan="2" >
				<div id="event_script_config_container">				
				</div>
			</td>	
			</tr>	
		{include file="inc/intable_footer.tpl" color="Gray"}
		
		<input type='hidden' name="script" value="{$event_name}">
		{if $task != 'edit'}
			{include intableid="addon_settings" file="inc/intable_header.tpl" header="Additional settings" color="Gray"}
			<tr>
				<td colspan="2">
					<input type="checkbox" name="create_menu_link" id="create_menu_link" value="1">
					Add a shortcut in Options menu for <span id="mess_target">roles</span>. It will allow me to execute this script with the above parameters with a single click.
				</td>	
			</tr>
			{include file="inc/intable_footer.tpl" color="Gray"}
			{include file="inc/table_footer.tpl" button_js=1 show_js_button=1 button_js_name="Execute script" button_js_action="SubmitTask('execute');"}
				
		{else}			
			<input type='hidden' name="task" value="update_event">			
			{include file="inc/table_footer.tpl" button_js=1 show_js_button=1 button_js_name="Save changes" button_js_action="SubmitTask('save');"}
		{/if}
{include file="inc/footer.tpl"}