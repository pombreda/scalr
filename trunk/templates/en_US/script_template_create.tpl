{include file="inc/header.tpl" form_action="script_templates.php"}
	<div style="position:relative;width:auto;">
		<div id="script_loader" align="center" style="display:none;position:absolute;top:7px;left:7px;background-color:#F0F0F0;right:7px;bottom:7px;vertical-align: middle;">
			<div align="center" style="position: absolute;left:50%; top: 50%;display: table-cell; vertical-align: middle;">
				<img style="vertical-align:middle;" src="/images/snake-loader.gif"> {t}Loading...{/t}
			</div>
		</div>
	{include file="inc/table_header.tpl" nofilter=1}
		<script language="Javascript">
			var scriptid = '{$id}';
			var latest_version = '{$latest_version}';
			var origin = '{$origin}'
			
			{literal}		
			function AddVarToScript(variable)
			{
				$('script_textarea').value += "%"+variable+"%";
			}
			
			function LoadTemplateVersion(version)
			{
				$('script_loader').style.display = '';
				
				new Ajax.Request('/server/server.php?_cmd=get_script_props&id='+scriptid+"&version="+version,
				{ 
					method: 'get',
					onSuccess: function(transport)
					{ 
						try
						{
							if (transport.responseText)
							{
								var data = transport.responseText.evalJSON();
							
								$('script_textarea').value = data.script; 
																
								if (parseInt(data.revision) != parseInt(latest_version) || origin == 'User-contributed')
								{
									$('cbtn_2').style.display = 'none';
								}
								else
								{
									$('cbtn_2').style.display = '';
								}
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
			{/literal}
		</script>
		{include file="inc/intable_header.tpl" header="General information" color="Gray"}
    	<tr>
    		<td>{t}Script name{/t}:</td>
    		<td><input type="text" name="name" value="{$name}" class="text" size="30"></td>
    	</tr>
    	<tr>
    		<td>{t}Description{/t}:</td>
    		<td><input type="text" name="description" id="script_description" value="{$description}" class="text" size="30"></td>
    	</tr>
    	{include file="inc/intable_footer.tpl" color="Gray"}
    	        
        {include file="inc/intable_header.tpl" intable_first_column_width="10%" header="Script template" color="Gray"}
        <tr>
    		<td width="10%">{t}Version{/t}:</td>
    		<td>
    			<select name="version" class="text" {if $smarty.session.uid == 0 && $origin != 'Shared'}disabled{/if} onChange="LoadTemplateVersion(this.value);">
    			{section name=id loop=$versions}
    				<option {if $selected_version == $versions[id]}selected{/if} value="{$versions[id]}">{$versions[id]}</option>
    			{/section}
    			</select>
    		</td>
    	</tr>
        <tr>
    		<td colspan="2">
    			<table>
    				<tr valign="top">
    					<td>
    						<p class="Webta_Ihelp">
    							{t}First line must contain shebang (#!/path/to/interpreter){/t}
    						</p>
    						<textarea id="script_textarea" name="script" class="text" cols="60" rows="10">{$script}</textarea>
    					</td>
    					<td width="5">&nbsp;</td>
    					<td>
    						<b>{t}Built-in variables{/t}:</b><br>
    						{section name=id loop=$sys_vars}
    						<span onclick="AddVarToScript('{$sys_vars[id]}');" title="Click to add variable to script" style="cursor:pointer;margin-right:5px;white-space:nowrap">%{$sys_vars[id]}%</span>
    						{/section}
    						<br><br>
    						{t}You may use own variables as %variable%. Variable values can be set for each role in farm settings.{/t} 
    					</td>
    				</tr>
    			</table>
    		</td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
        {if !$id}
        	<input type="hidden" name="task" value="create">
			{include file="inc/table_footer.tpl" button2=1 button2_name="Create template" cancel_btn=1}
		{else}
			<input type="hidden" name="id" value="{$id}">
			<input type="hidden" name="task" value="edit">
			{assign var="new_version" value=$latest_version+1}
			{include file="inc/table_footer.tpl" button2=1 button2_name="Save changes in current version" button3=1 button3_name="Save changes as new version ($new_version)" cancel_btn=1}
			
			{if $latest_version != $selected_version || ($origin == 'User-contributed' && $smarty.session.uid != 0)}
			<script>
				$('cbtn_2').style.display = 'none';
			</script>
			{/if}
		{/if}
	</div>
{include file="inc/footer.tpl"}