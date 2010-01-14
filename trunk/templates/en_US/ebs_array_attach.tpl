{include file="inc/header.tpl"}
	{include file="inc/table_header.tpl"}
    	{include file="inc/intable_header.tpl" intable_first_column_width="10%" header="Attach to instance" color="Gray"}
        <tr>
    		<td>Instance:</td>
    		<td>
    			{if !$iid}
	    			<select name="inststanceId" id="inststanceId" class="text">
	    			{section name=iid loop=$instances}
						<option {if $iid == $instances[iid].instance_id}selected{/if} value="{$instances[iid].instance_id}">{$instances[iid].instance_id} ({$instances[iid].role_name}) on '{$instances[iid].name}'</option>
					{/section}
					</select>
				{else}
					{$iid}
					<input type="hidden" name="inststanceId" value="{$iid}" />
				{/if}
    		</td>
    	</tr>
    	<tr>
    		<td colspan="2">&nbsp;</td>
    	</tr>
    	<tr>
    		<td colspan="2">
    			<input type="checkbox" style="vertical-align:middle;" name="attach_on_boot" value="1"> Always attach array <span id="arrayname" style="font-weight:bold;">{$array_name}</span>&nbsp;to this instance upon startup
    		</td>
    	</tr>
    	{include file="inc/intable_footer.tpl" color="Gray"}
    	{include file="inc/intable_header.tpl" intable_first_column_width="10%" header="Mount settings" color="Gray"}
        <tr>
    		<td>Mountpoint:</td>
    		<td>
    			<input type="text" class="text" name="mountpoint" value="/mnt/array" />
    		</td>
    	</tr>
    	{include file="inc/intable_footer.tpl" color="Gray"}
    	<input type="hidden" name="task" value="attach">
    	<input type="hidden" name="array_id" value="{$array_id}">
	{include file="inc/table_footer.tpl" button2=1 button2_name="Continue" cancel_btn=1}
{include file="inc/footer.tpl"}