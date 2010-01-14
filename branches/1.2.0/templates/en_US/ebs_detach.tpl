{include file="inc/header.tpl"}
	{include file="inc/table_header.tpl"}
    	{include file="inc/intable_header.tpl" intable_first_column_width="10%" header="Detach EBS volume" color="Gray"}
        <tr>
    		<td>Volume:</td>
    		<td>
				{$volumeId}
				<input type="hidden" name="volumeId" value="{$volumeId}" />
				<input type="hidden" name="task" value="detach" />
    		</td>
    	</tr>
    	{if $instanceID}
    	<tr>
    		<td>Instance:</td>
    		<td>
				{$instanceID}
    		</td>
    	</tr>
    	{/if}
    	<tr>
    		<td colspan="2">&nbsp;</td>
    	</tr>
    	{if $attach_on_boot}
    	<tr>
    		<td colspan="2">
    			<input type="checkbox" style="vertical-align:middle;" name="detach_on_boot" value="1"> Do not attach this volume to this instance anymore
    		</td>
    	</tr>
    	{/if}
    	<tr>
    		<td colspan="2">
    			<div style="float:left;vertical-align:middle;padding-top:12px;">
    				<input type="checkbox" style="vertical-align:middle;" name="force" value="1"> Force detach.
    			</div> 
    			<div class="Webta_ExperimentalMsg" style="font-size:12px;width:auto;margin-left:135px;">
						Can lead to data loss or a corrupted file system
				</div>
    		</td>
    	</tr>
    	{include file="inc/intable_footer.tpl" color="Gray"}
	{include file="inc/table_footer.tpl" button2=1 button2_name="Continue" cancel_btn=1}
{include file="inc/footer.tpl"}