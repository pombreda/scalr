{include file="inc/header.tpl"}
	{if $new_ami_id}
		{include file="inc/table_header.tpl"}
			{include file="inc/intable_header.tpl" header="Confirmation" color="Gray"}
	    	<tr>
	    		<td colspan="2">
	    			The role <b>{$old_role_name}</b>, which is currently associated with AMI <b>{$new_ami_id}</b> will be <b>deleted</b> and you will no longer be able to use it in your farms. 
	    			AMI <b>{$new_ami_id}</b> will be associated with role <b>{$new_role_name}</b>.
	    		</td>
	    	</tr>
	        {include file="inc/intable_footer.tpl" color="Gray"}
	        <input type="hidden" name="ami_id" value="{$ami_id}">
	        <input type="hidden" name="new_ami_id" value="{$new_ami_id}">
	        <input type="hidden" name="confirm" value="1">
		{include file="inc/table_footer.tpl" button2=1 button2_name="Confirm"}
	{else}
		{include file="inc/table_header.tpl"}
			{include file="inc/intable_header.tpl" header="AMI information" color="Gray"}
	    	<tr>
	    		<td width="20%">{t}Current AMI:{/t}</td>
	    		<td>{$ami_id}<input type="hidden" name="ami_id" value="{$ami_id}"></td>
	    	</tr>
	    	<tr>
	    		<td width="20%">{t}New AMI:{/t}</td>
	    		<td>
	    			<select name="new_ami_id" class="text">
	    				{section name=id loop=$rows}
	    				<option value="{$rows[id].ami_id}">{$rows[id].name} ({$rows[id].ami_id})</option>
	    				{/section}
	    			</select>
	    			<span class="Webta_Ihelp">{t}Only AMIs that are not used in any of your farms are dislayed here.{/t}</span>
	    		</td>
	    	</tr>
	        {include file="inc/intable_footer.tpl" color="Gray"}
		{include file="inc/table_footer.tpl" button2=1 button2_name="Switch"}
	{/if}
{include file="inc/footer.tpl"}