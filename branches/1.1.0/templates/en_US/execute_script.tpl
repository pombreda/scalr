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
	{/literal}
	</style>
	<script language="Javascript">
	{literal}
	
		function SetOptions(opt_value)
		{
			$('ami_id').disabled = true;
			
			if ($('iid'))
				$('iid').disabled = true;
			
			if ($('addon_settings'))
			{
				$('addon_settings').style.display = '';
				$('mess_target').innerHTML = opt_value+"s";
			}
			
			if (opt_value == 'role')
				$('ami_id').disabled = false;
				
			if (opt_value == 'instance')
			{
				$('iid').disabled = false;
				$('addon_settings').style.display = 'none';
			}
		}
	
		 Event.observe(window, 'load', function(){
		 	SetOptions('{/literal}{$target}{literal}');
		 	ReloadScript(false);
		 });
		 
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
					for(var key in fields)
					{
						if (typeof(fields[key]) == 'string')
						{
							var val = "";
							
							if (values)
								var val = values[key];								
							
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
		{include file="inc/intable_header.tpl" header="Target" color="Gray"}
        <tr>
			<td colspan="2"><input onclick="SetOptions(this.value);" type="radio" name="target" {if $target == 'farm'}checked{/if} value="farm" style="vertical-align:middle;"> 
			On all instances of this farm
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<input type="radio" onclick="SetOptions(this.value);" name="target" {if $target == 'role'}checked{/if} value="role" style="vertical-align:middle;"> 
				 On all instances of this role
				 <select id="ami_id" name="ami_id" class="text" style="vertical-align:middle;">
				 	{section name=id loop=$roles}
				 		<option {if $ami_id == $roles[id].ami_id}selected{/if} value="{$roles[id].ami_id}">{$roles[id].name} ({$roles[id].ami_id})</option>
				 	{/section}
				 </select>
			</td>
		</tr>
		{if $instances|@count > 0 && $task != 'edit'}
    	<tr>
    		<td colspan="2">
    			<input type="radio" onclick="SetOptions(this.value);" {if $target == 'instance'}checked{/if} name="target" value="instance" style="vertical-align:middle;">
    			On this instance only
    			<select id="iid" name="iid" class="text" style="vertical-align:middle;">
				 	{section name=id loop=$instances}
				 		<option {if $iid == $instances[id].instance_id}selected{/if} value="{$instances[id].instance_id}">{$instances[id].external_ip} - {$instances[id].name} ({$instances[id].instance_id})</option>
				 	{/section}
				</select>
    		</td>
    	</tr>
    	{/if}
		{include file="inc/intable_footer.tpl" color="Gray"}
		
		{include file="inc/intable_header.tpl" header="Script" color="Gray"}
		<tr>
			<td>Script:</td>
			<td>
				<select name="scriptid" id="scriptid" class="text" onchange="ReloadScript(false);">
					{section name=id loop=$scripts}
						<option {if $scripts[id].id == $scriptid}selected{/if} value="{$scripts[id].id}">{$scripts[id].name}</option>
					{/section}
				</select>
			</td>
		</tr>
        <tr>
			<td>Execution mode:</td>
			<td>
				<input type="radio" name="issync" {if $issync !== 0 || $issync == 1}checked{/if} value="1" id="issync_1" style="vertical-align:middle;"> {t}Synchronous{/t} &nbsp;&nbsp;
				<input type="radio" name="issync" {if $issync === '0'}checked{/if} value="0" id="issync_0" style="vertical-align:middle;"> {t}Asynchronous{/t} 
			</td>
		</tr>
		<tr>
			<td>Timeout:</td>
			<td><input style='vertical-align:middle;' type='text' name='scripting_timeout' id='scripting_timeout' value="{if $timeout}{$timeout}{else}600{/if}" class="text" size="5"> seconds</td>
		</tr>
		<tr>
			<td>Version:</td>
			<td>
				<select style="vertical-align:middle;" onchange="ReloadScript(true);" name="script_version" id="script_version" class="text">
					
				</select>
			</td>
		</tr>
		{include file="inc/intable_footer.tpl" color="Gray"}
		
		{include intableid="scripts_arg_section" file="inc/intable_header.tpl" header="Script arguments" color="Gray"}
		<tr>
			<td colspan="2">
				<div id="event_script_config_container">
				
				</div>
			</td>	
		</tr>
		{include file="inc/intable_footer.tpl" color="Gray"}
		
		{if $task != 'edit'}
			{include intableid="addon_settings" file="inc/intable_header.tpl" header="Additional settings" color="Gray"}
			<tr>
				<td colspan="2">
					<input type="checkbox" name="create_menu_link" value="1">
					Add a shortcut in Options menu for <span id="mess_target">roles</span>. It will allow me to execute this script with the above parameters with a single click.
				</td>	
			</tr>
			{include file="inc/intable_footer.tpl" color="Gray"}
		{/if}
		
	{if $task != 'edit'}
		{include file="inc/table_footer.tpl" button_js=1 show_js_button=1 button_js_name="Execute script" button_js_action="SubmitExecScriptForm();"}
	{else}
		<input type='hidden' name="task" value="update_event">
		<input type='hidden' name="script" value="{$event_name}">
		{include file="inc/table_footer.tpl" button_js=1 show_js_button=1 button_js_name="Save changes" button_js_action="SubmitExecScriptForm();"}
	{/if}
	</div>
{include file="inc/footer.tpl"}