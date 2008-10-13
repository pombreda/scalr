{include file="inc/header.tpl"}
	{include file="inc/table_header.tpl"}
    	{include file="inc/intable_header.tpl" intable_first_column_width="10%" header="Attach to instance" color="Gray"}
        <tr>
    		<td>Instance:</td>
    		<td>
    			<select name="inststanceId" class="text">
    			{section name=iid loop=$instances}
					<option {if $iid == $instances[iid]}selected{/if} value="{$instances[iid].instance_id}">{$instances[iid].instance_id} ({$instances[iid].role_name}) on '{$instances[iid].name}'</option>
				{/section}
				</select>
    		</td>
    	</tr>
    	{include file="inc/intable_footer.tpl" color="Gray"}
    	<input type="hidden" name="task" value="attach">
    	<input type="hidden" name="volumeId" value="{$volumeId}">
	{include file="inc/table_footer.tpl" button2=1 button2_name="Continue" cancel_btn=1}
{include file="inc/footer.tpl"}