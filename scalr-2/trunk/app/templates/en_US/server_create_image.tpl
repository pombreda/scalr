{include file="inc/header.tpl"}
	{include file="inc/table_header.tpl"}
		{include file="inc/intable_header.tpl" intable_first_column_width="200" header="Server details" color="Gray"}
    	<tr>
			<td style="padding:5px;">{t}Server ID{/t}:</td>
			<td colspan="2" style="padding:5px;">{$DBServer->serverId}</td>
    	</tr>
    	<tr>
			<td style="padding:5px;">{t}Farm ID{/t}:</td>
			<td colspan="2" style="padding:5px;">{$DBServer->farmId}</td>
    	</tr>
    	<tr>
			<td style="padding:5px;">{t}Farm name{/t}:</td>
			<td colspan="2" style="padding:5px;">{$DBServer->farmName}</td>
    	</tr>
    	<tr>
			<td style="padding:5px;">{t}Role name{/t}:</td>
			<td colspan="2" style="padding:5px;">{$DBServer->roleName}</td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
		{include file="inc/intable_header.tpl" header="Replacement options" color="Gray"}
    	<tr>
			<td colspan="2" style="padding:5px;"><input style="vertical-align:middle;" type="radio" name="replace_type" value="no_replace"> <b>DO NOT REPLACE</b> any roles on any farms, just create new one.</td>
    	</tr>
		<tr>
			<td colspan="2" style="padding:5px;"><input style="vertical-align:middle;" type="radio" name="replace_type" value="replace_farm"> Replace role '{$DBServer->roleName}' with new one <b>ONLY</b> on current farm '{$DBServer->farmName}'</td>
    	</tr>
    	<tr>
			<td colspan="2" style="padding:5px;"><input style="vertical-align:middle;" checked="checked" type="radio" name="replace_type" value="replace_all"> Replace role '{$DBServer->roleName}' with new one on <b>ALL MY FARMS</b> <span style="font-style:italic;font-size:11px;">(You will be able to bundle role with the same name. Old role will be renamed.)</span></td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
        {include file="inc/intable_header.tpl" intable_first_column_width="200" header="Role options" color="Gray"}
    	<tr>
			<td style="padding:5px;">{t}Role name{/t}:</td>
			<!-- {$rolename} -->
			<td style="padding:5px;"><input type="text" class="text" style="width:400px;" name="rolename" value="{$DBServer->roleName}" /></td>
    	</tr>
    	<tr valign="top">
			<td style="padding:5px;">{t}Description{/t}:</td>
			<td style="padding:5px;">
				<textarea class="text" name="description" id="r_description" style="width:400px;height:100px;"></textarea>
			</td>
		</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
        <input type="hidden" name="server_id" value="{$DBServer->serverId}" />
	{include file="inc/table_footer.tpl" button2=1 button2_name="Create role" cancel_btn=1}
{include file="inc/footer.tpl"}