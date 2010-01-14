{include file="inc/header.tpl"}
	{include file="inc/table_header.tpl"}
		{include file="inc/intable_header.tpl" header="Changing account type" color="Gray"}
		{if $active_subs.subscriptionid}
    	<tr>
    		<td colspan="2">You current plan is <b>{$package.name} ( ${$package.cost} / month)</b>.<br /> 
    		You should cancel your current PayPal subscription <b>{$active_subs.subscriptionid} before you will be able to select a new plan.</b> 
    		</td>
    	</tr>
    	{else}
    	<tr>
    		<td width="20%">New account type:</td>
    		<td>
    			<select class="text" name="new_pkgid" id="new_pkgid">
    				{section name=id loop=$packages}
    				<option value="{$packages[id].id}">{$packages[id].name} - ${$packages[id].cost} / month</option>
    				{/section}
    			</select>
    		</td>
    	</tr>
    	{/if}
        {include file="inc/intable_footer.tpl" color="Gray"}
	{include file="inc/table_footer.tpl" button2=1 button2_name="Continue"}
{include file="inc/footer.tpl"}