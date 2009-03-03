{include file="inc/header.tpl" form_action="script_templates.php"}
	<br />
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
			
			{literal}		
			function AddVarToScript(variable)
			{
				$('script_textarea').value += "%"+variable+"%";
			}
			{/literal}
		</script>
		{include file="inc/intable_header.tpl" header="General information" color="Gray"}
    	<tr>
    		<td>{t}Template name{/t}:</td>
    		<td><input type="text" name="name" value="{$name}" class="text" size="30"></td>
    	</tr>
    	<tr>
    		<td>{t}Template description{/t}:</td>
    		<td><input type="text" name="description" id="script_description" value="{$description}" class="text" size="30"></td>
    	</tr>
    	<tr>
    		<td>{t}Version{/t}:</td>
    		<td>
    			<select name="version" class="text" disabled onChange="LoadTemplateVersion(this.value);">
    			{section name=id loop=$versions}
    				<option {if $selected_version == $versions[id]}selected{/if} value="{$versions[id]}">{$versions[id]}</option>
    			{/section}
    			</select>
    		</td>
    	</tr>
    	{include file="inc/intable_footer.tpl" color="Gray"}
    	        
        {include file="inc/intable_header.tpl" header="Script template" color="Gray"}
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
        
        {include file="inc/intable_header.tpl" header="Sharing comments" color="Gray"}
        <tr>
    		<td colspan="2">
    			<table>
    				<tr valign="top">
    					<td>
    						<p class="Webta_Ihelp">
    							{t}This field is visible for Scalr team only.{/t}
    						</p>
    						<textarea id="sharing_comments" name="sharing_comments" class="text" cols="60" rows="10">{$sharing_comments}</textarea>
    					</td>
    				</tr>
    			</table>
    		</td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
        
        {include file="inc/intable_header.tpl" header="Confirmation" color="Gray"}
        <tr>
    		<td colspan="2">
    			<input type="checkbox" onclick="$('cbtn_2').disabled = !this.checked;" name="confirm" value="1" style="vertical-align:middle;"> {t}Yes, I want to contribute my script and make it available to other Scalr.net users.{/t}
    		</td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
        <input type="hidden" name="task" value="share">
        <input type="hidden" name="id" value="{$id}">
		{include file="inc/table_footer.tpl" button2=1 button2_name="Save & Share" cancel_btn=1}
		<script>
			$('cbtn_2').disabled = true;
		</script>
	</div>
{include file="inc/footer.tpl"}