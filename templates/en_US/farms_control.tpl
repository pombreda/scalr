{include file="inc/header.tpl"}
	{include file="inc/table_header.tpl"}
		{include file="inc/intable_header.tpl" header="$action farm" color="Gray"}
    	<tr>
    		<td colspan="2">
    		{if $action == 'Launch'}
    		  {if $new}Farm succesfully built. {/if}Would you like to launch '{$farminfo.name}' now? {if $num > 0}This will launch <b>{$num}</b> new instance(s).{/if}
    		  <br>
    		  <br>
    		  {if $num > 0}
    		  	<input style="vertical-align:middle;margin-left:-4px;" checked type="checkbox" name="mark_active" value="1"> Activate DNS zone now
    		  {/if}
    		{else}
    		  Do you really want to terminate farm '{$farminfo.name}'? {if $num > 0}All <b>{$num}</b> instance(s) in this farm will be terminated.{/if}
    		  <br>
    		  {if $sync_count > 0}
    		  <br />
    		  You have <b>{$sync_count}</b> roles that currently being synchronized. Synchronization will be terminated and your changes on instance(s) will be lost.
    		  <br />
    		  {/if}
    		  <br>
    		  <input style="vertical-align:middle;margin-left:-4px;" checked type="checkbox" name="deleteDNS" value="1"> Delete DNS zone from nameservers. It will be recreated when the farm is launched.
    		{/if}
    		</td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
        <input type="hidden" name="action" value="{$action}" />
	{include file="inc/table_footer.tpl" button2=1 button2_name="Yes, $action farm now" button3=1 button3_name="$action farm later"}
{include file="inc/footer.tpl"}