{include file="inc/header.tpl"}
	<style>
	{literal}												
		.s_field_container
		{
			margin-bottom:10px;
			width:400px;
		}
		
		.s_field_name
		{
			width:150px;
			float:left;
			vertical-align:middle;
		}
		
		.td_with_padding
		{
			padding-bottom: 2px;
			padding-top: 2px;
			padding-left: 4px;
			margin:4px;	
		}
		.hideIfTerminate
		{
			display='none';
		}
		input#issync_1 
		{
		 margin: 5px;
		}
		input#issync_0
		{
		 margin: 3px;
		}
				
	{/literal}
	</style>
	<script language="Javascript">
	{literal}


		function SetOptions(opt_value)
		{
			if($('farm_roleid'))
				$('farm_roleid').disabled = true;
			
			if ($('iid'))
				$('iid').disabled = true;
			
			if ($('addon_settings'))
				$('mess_target').innerHTML = opt_value+"s";
			

			//  enables  farm_roleid dropdown list if target is role 
			if (opt_value == 'role' && $('farm_roleid'))
				$('farm_roleid').disabled = false;


			if (opt_value == 'instance')			
				$('iid').disabled = false;		
			
			
		}
			
		function HideAllSettings()
		{ 
			// hides all options menues
			$$('.hideIfTerminate').each(function (el) {
					el.style.display = 'none';
				});					
			
			$('scripts_arg_section').style.display = 'none';
			$('script_options').style.display = 'none';
			$('terminate_options').style.display = 'none';
			
			
			
		}
		function SetTaskType(task_type)
		{ 
			// displays options menus depending from task_type		
			if (task_type == 'script_exec')
			{
				// hide all options (for other script types)								
				HideAllSettings();
				
				// show_script_option				
				$('scripts_arg_section').style.display = '';
				$('script_options').style.display = '';	
				
				$$('.hideIfTerminate').each(function (el) {
					el.style.display = '';
				});	
						
				ReloadScript(false);
			}
			if(task_type == 'terminate_farm')
			{
				// hide all options (for other script types)				
				HideAllSettings();
				
				// show farm_term options
				$('terminate_options').style.display = '';
				
			}
			if(task_type == 'launch_farm')
			{
				// hide all options (for other script types)
				HideAllSettings();

			}
		}
			
		 Event.observe(window, 'load', function()
			{				
				SetOptions('{/literal}{$target}{literal}');								
				HideAllSettings();
				SetTaskType($('task_type').value)
			}
			);
		 
		 window.scriptInfo = new Array();
		 
		 {/literal}
		 var values = {if $values}{$values};{else}new Array();{/if}
		 var current_version = {if $version}{$version}{else}false{/if};
		 {literal}
		
		 function ReloadScript(only_args)
		 {
		 	var scriptid = $('scriptid').value;
		 	var version = $('script_version').value;
		 	
		 	
		 	if (!only_args || window.scriptInfo.length == 0)
		 	{
			 	$('script_loader').style.display = '';
			 	
			 	new Ajax.Request('/server/server.php?_cmd=get_script_args&scriptid='+scriptid,
				{ 
					method: 'get',
					onSuccess: function(transport)
					{ 
						try
						{
							if (transport.responseText)
							{
								var data = transport.responseText.evalJSON();
								
								window.scriptInfo = data;
								
								var el = $('script_version'); 
								while(el.firstChild) { 
									el.removeChild(el.firstChild); 
								}
																			
								for (var i = 0; i < window.scriptInfo.length; i ++)
								{
									var opt = document.createElement("OPTION");
									opt.value = window.scriptInfo[i].revision;
									opt.innerHTML = window.scriptInfo[i].revision;
									
									if (current_version && current_version == window.scriptInfo[i].revision)
									{
										opt.selected = true;
									}									
									$('script_version').appendChild(opt); 
								}	
								ReloadScript(true);
							}
						}
						catch(e)
						{
							alert(e.message);
						}
						
						$('script_loader').style.display = 'none';					 
					} 
				});
			}
			else
			{				
				$('event_script_config_container').innerHTML = '';
				
				// get scriptInfo filed where revision == version
				for (var i = 0; i < window.scriptInfo.length; i ++)
				{
					if (window.scriptInfo[i].revision == version)
					{
						var fields = window.scriptInfo[i].fields;
						break;
					}
				}
				
				show_config = false;
				
				try
				{
					eval("var fields = "+fields+";");					
					
					 var script_args = {/literal} {$script_args} {literal};					 
					
					for(var key in fields)
					{					
						if (typeof(fields[key]) == 'string')
						{
							var val = "";
							
							if (values)
								val = values[key];

							if(script_args)
							{			
								for(var i=0; i<script_args.length; i++)
								{	
									if(script_args[i]["fieldname"] == key)
									{
										val = script_args[i]["value"];
										break;
									}								
								}
							}
							
							$('event_script_config_container').insert(
								ConfigFieldTemplate.evaluate({name:key, title:fields[key], value:val})
							);
    											
							show_config = true;
						}
					}
				}
				catch(e){}	
                                	
				if (show_config)
					$('scripts_arg_section').style.display = '';
				else
					$('scripts_arg_section').style.display = 'none';
			}
		 }
		 
		 /*
		 Setup script events tree
		 */
		var ConfigFieldTemplate = new Template('<div class="s_field_container"> '+
			'<div class="s_field_name">#{title}:</div>'+
			'<div style="float:left;"><input id="script_configopt_#{name}" type=\'text\' name=\'config[#{name}]\' value=\'#{value}\' class=\'text configopt\'></div>'+
			'<div style="clear:both;"></div>'+
			'</div>'
		); 
				 
		function SubmitExecScriptForm()
		{
			$('button_js').disabled = true;
			
			$('event_script_config_container').select('input').each(function(item){
				
				var inp = document.createElement('INPUT');
				inp.type = 'hidden';
				inp.name = item.name;
				inp.value = item.value;
				
				document.forms[1].appendChild(inp);
			});
			
			document.forms[1].submit();
			
			return false;
		}
		
		function SelectFarms(farmid,task)
		{		
			var task_type = $('task_type').value;		
			document.location = "scheduler_task_add.php?farmid="+farmid+"&task="+task+"&task_type="+task_type;
		}
	{/literal}
	</script>
	<br />
	<div style="position:relative;width:auto;">
		<div id="script_loader" align="center" style="display:none;position:absolute;top:7px;left:7px;background-color:#F0F0F0;right:7px;bottom:7px;vertical-align: middle;">
			<div align="center" style="position: absolute;left:50%; top: 50%;display: table-cell; vertical-align: middle;">
				<img style="vertical-align:middle;" src="/images/snake-loader.gif"> {t}Loading...{/t}
			</div>
		</div>
	{include file="inc/table_header.tpl" nofilter=1}
	
	<!--  Task -->
	{include file="inc/intable_header.tpl" header="Task" color="Gray"}
		<tr style="height:40px;">
			<td class="td_with_padding" style="width:200px;">Task name:</td>
			<td class="td_with_padding"><input type="text" name="task_name" id="task_name" class="text" value="{$taskinfo.task_name}" style="vertical-align:middle;width:132px;">							
			</td>
		</tr>
		<tr style="height:40px;">
			<td class="td_with_padding">Task type:</td>
			<td class="td_with_padding">
				<select id="task_type" name="task_type" onchange="SetTaskType(this.value);" class="text" style="vertical-align:middle; width:137px;">						
					{foreach from=$taskTypes item=descr key=SelectType}
			 			<option  {if $SelectType == $task_type} selected {/if} value="{$SelectType}">{$descr}</option>
			 		{/foreach}
				</select>	
			</td>
		</tr>
	{include file="inc/intable_footer.tpl" color="Gray"}
	
	<!--  Target  -->	
	{include file="inc/intable_header.tpl" header="Target" color="Gray"}
			
		{if $farminfo} 
			<tr style="height:40px;">					
				{if $task == 'edit'}
					<td class="td_with_padding" style="width:200px;">On all instances of farm:</td>					
					<td class="td_with_padding">{$farminfo.name}</td>
					
				{else}						
					<td class="td_with_padding" style="width:200px;">										
						<input onclick="SetOptions(this.value);" type="radio" name="target_type" {if $target == 'farm'}checked{/if} value="farm" style="vertical-align:middle;">
						On all instances of farm:
					</td>					      
					<td class="td_with_padding">
						<select id="farm_target" name="farm_target" class="text" onchange="SelectFarms(this.value,'{$task}');" style="vertical-align:middle; width:132px;">							
				 			{section name=id loop=$farms}
				 			<option {if $farmid == $farms[id].id}selected{/if} value="{$farms[id].id}">{$farms[id].name}</option>
				 			{/section}
						</select>					
					</td>
				{/if}
				
			{else}					
				<td class="td_with_padding" style="width:200px;">		
					<input onclick="SetOptions(this.value);" type="radio" name="target_type" {if $target == 'farm'}checked{/if} value="farm" style="vertical-align:middle;">
					On all instances of farm:
				</td>				
				<td class="td_with_padding">
					<select id="farm_target" name="farm_target" class="text" onchange="document.location='scheduler_task_add.php?farmid='+this.value+'&task={$task}'" style="vertical-align:middle;">
			 		{section name=id loop=$farms}
			 			<option {if $farmid == $farms[id].id}selected{/if} value="{$farms[id].id}">{$farms[id].name}</option>
			 		{/section}
					</select>				 
				</td>					
			</tr>
		{/if}
		
		
		{if $roleinfo}		
		<tr  class="hideIfTerminate" style="height:40px; display:none;">	
			<!-- farm_role -->
			<td class="td_with_padding" style="width:200px;">On all instances of this role: </td>
			<td class="td_with_padding">{$roleinfo.ami_id}</td>
		</tr>		
		
		{elseif !$roleinfo && $task != 'edit'}
		
		<tr class="hideIfTerminate" style="height:40px; display:none;">	
			<td class="td_with_padding">
				<input type="radio" onclick="SetOptions(this.value);" name="target_type" {if $target == 'role'}checked{/if} value="role" style="vertical-align:middle; display='none'"> 
				 On all instances of this role:
			 </td>
			 <td class="td_with_padding">
				 <select id="farm_roleid" name="farm_roleid" class="text" style="vertical-align:middle;">
			 		{section name=id loop=$roles}
			 			<option {if $ami_id == $roles[id].ami_id || $farm_roleid == $roles[id].id}selected{/if} value="{$roles[id].id}">{$roles[id].name} ({$roles[id].ami_id})</option>
			 		{/section}
				 </select>
			 </td>			
		</tr>		
		{/if}	
						
		{if $instances|@count > 0 && $task != 'edit'}
    	<tr class="hideIfTerminate" style="height:40px; display:none;">      		
    		<td class="td_with_padding">
    			<input type="radio" onclick="SetOptions(this.value);" {if $target == 'instance'}checked{/if} name="target_type" value="instance" style="vertical-align:middle;">
    			On instance:
    		</td>
    		<td class="td_with_padding">
    			<select id="iid" name="iid" class="text" style="vertical-align:middle;">
				 	{section name=id loop=$instances}
				 		<option {if $iid == $instances[id].id}selected{/if} value="{$instances[id].id}">{$instances[id].external_ip} - {$instances[id].name} ({$instances[id].instance_id})</option>
				 	{/section}
				</select>
    		</td>    		
    	{/if}   
    	     		
    	{if $instanceInfo}
    		<td class="td_with_padding" >
    			On instance:    			
    		</td>
    		<td class="td_with_padding">
    			{$instanceInfo.external_ip} - {$instanceInfo.instance_id} ({$instanceInfo.id})
    		 </td>    	
    	</tr>
    	{/if}
		{include file="inc/intable_footer.tpl" color="Gray"}	
		
	<!--  Settings  -->	
		{include intableid="task_settings" file="inc/intable_header.tpl" header="Task settings" color="Gray"}		
		<tr>
			<td class="td_with_padding">Start time</td>
			<td class="td_with_padding">
				<div id="StartDate" class="td_with_padding">   							
				</div>    						
				<script type="text/javascript">
				{literal}	
				df = new Ext.form.DateField(
				{
					renderTo: 'StartDate',
					format: "Y-m-d H:i:s",	
					width: 142,							
					name: 'startDateTime',
					value: '{/literal}{$taskinfo.start_time_date}{literal}'
				})
				df.render();
				{/literal}
				</script>    
			</td>
			
		</tr>
		<tr>
			<td class="td_with_padding">End time</td>
			<td class="td_with_padding">
				<div id="EndDate" class="td_with_padding">   							
				</div>    						
				<script type="text/javascript">
				{literal}	
				df = new Ext.form.DateField(
				{
					renderTo: 'EndDate',
					format: "Y-m-d H:i:s",	
					width: 142,							
					name: 'endDateTime',
					value: '{/literal}{$taskinfo.end_time_date}{literal}'
				})
				df.render();
				{/literal}
				</script>    
			</td>
			
		</tr>    				
		<tr>
			<td class="td_with_padding">Restart every:</td>
			<td class="td_with_padding"><input style='vertical-align:middle; width:132px;' type='text' name='restart_timeout' id='restart_timeout' value="{if $taskinfo.restart_every}{$taskinfo.restart_every}{else}1440{/if}" class="text" size="5">  minutes.
			0 - task will be executed only once.
			</td>
		</tr>
		<tr>
			<td class="td_with_padding">Priority:</td>
			<td class="td_with_padding">
				<input type='text' class='text'  name="order_index" value="{$taskinfo.order_index}" style="width:132px;" >   0 - the highest priority
			</td>
		</tr>
		{include file="inc/intable_footer.tpl" color="Gray"}	
		
	<!--  Script settings -->
		{include intableid="script_options" file="inc/intable_header.tpl" visible="none" header="Script settings" color="Gray"}		
		
		<tr>
			<td class="td_with_padding" style="width:200px;">Script:</td>
			<td class="td_with_padding">
				<select style="width:140px;" name="scriptid" id="scriptid" class="text" onchange="ReloadScript(false);">
					{section name=id loop=$scripts}
						<option {if $scripts[id].id == $scriptid}selected{/if} value="{$scripts[id].id}">{$scripts[id].name}</option>
					{/section}
				</select>
			</td>
		</tr>
        <tr>
			<td class="td_with_padding">Execution mode:</td>
			<td class="td_with_padding">
				<input type="radio" name="issync" {if $issync !== 0 || $issync == 1}checked{/if} value="1" id="issync_1" style="vertical-align:middle;"> {t}Synchronous{/t} &nbsp;&nbsp;
				<input type="radio" name="issync" {if $issync === '0'}checked{/if} value="0" id="issync_0" style="vertical-align:middle;"> {t}Asynchronous{/t} 
			</td>
		</tr>		
		<tr>
			<td class="td_with_padding">Version:</td>
			<td class="td_with_padding">
				<select style="vertical-align:middle;width:135px;" onchange="ReloadScript(true);" name="script_version" id="script_version" class="text">	
				</select>
			</td>
		</tr>	
		<tr>
			<td class="td_with_padding">Timeout:</td>
			<td class="td_with_padding">				
				<input type='text' class='text'  name="timeout" value="{$timeout}" style="width:132px;" >seconds
			</td>
		</tr>	
		{include file="inc/intable_footer.tpl" color="Gray"}
		
 <!--  Terminate settings -->
		{include intableid="terminate_options" file="inc/intable_header.tpl" visible="none" header="Terminate settings" color="Gray"}		
		<tr>
			<td class="td_with_padding" colspan="2">
				<input type="checkbox"  style="vertical-align:middle;" {if $deleteDNS}checked="checked"{/if} name="deleteDNS" value="1">
				<span style="vertical-align:middle;">Delete DNS zone from nameservers. It will be recreated when the farm is launched.</span>
				<br>
				<br>
			</td>			
		</tr>
		
		<tr>	
			<td class="td_with_padding" colspan="2">
				<input type="radio" style="vertical-align:middle;" {if !$keep_elastic_ips}checked="checked"{/if} name="keep_elastic_ips" value="0">				
				<span style="vertical-align:middle;">Release the static IP adresses that are allocated for this farm. When you start the farm again, new IPs will be allocated.</span>
			</td>
		</tr>
		<tr>	
			<td class="td_with_padding" colspan="2"> 
				<input type="radio" style="vertical-align:middle;" {if $keep_elastic_ips}checked="checked"{/if} name="keep_elastic_ips" value="1">				
				<span style="vertical-align:middle;">Keep the static IP adresses that are allocated for this farm. Amazon will keep billing you for them even when the farm is stopped.</span>
				{$keep_elastic_ips}
				<br>
				<br>
			</td>			
		</tr>
		<tr>			
			<td class="td_with_padding" colspan="2">
				<input type="radio" style="vertical-align:middle;" {if !$keep_ebs}checked="checked"{/if}  name="keep_ebs" value="0">				
				<span style="vertical-align:middle;">Release the EBS volumes created for this farm. When you start the farm again, new EBS volumes will be created.</span>
				{$keep_ebs}
			</td>				
		</tr>
		<tr>
			<td class="td_with_padding" colspan="2">
				<input type="radio" style="vertical-align:middle;" {if $keep_ebs}checked="checked"{/if} name="keep_ebs" value="1">				
				<span style="vertical-align:middle;">Keep the EBS volumes that are created for this farm. Amazon will keep billing you for them even when the farm is stopped.</span>
			</td>			
		</tr>
		{include file="inc/intable_footer.tpl" color="Gray"}
		  		
		
<!-- Script args   -->
	
		{include intableid="scripts_arg_section" visible="none"  file="inc/intable_header.tpl" header="Script arguments" color="Gray"}
		<tr>
			<td colspan="2">
				<div id="event_script_config_container">				
				</div>
			</td>	
		</tr>
		{include file="inc/intable_footer.tpl" color="Gray"}		
				
		{if $task != 'edit'}
			{include file="inc/table_footer.tpl" button_js=1 show_js_button=1 button_js_name="Add task" button_js_action="SubmitExecScriptForm();"}
		{else}
			<input type='hidden' name="task" value="edit">
			<input type='hidden' name="script" value="{$event_name}">
			{include file="inc/table_footer.tpl" button_js=1 show_js_button=1 button_js_name="Save changes" button_js_action="SubmitExecScriptForm();"}
		{/if}
		
	</div>
	
{include file="inc/footer.tpl"}