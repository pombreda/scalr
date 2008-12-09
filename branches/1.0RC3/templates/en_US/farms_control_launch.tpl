{include file="inc/intable_header.tpl" header="Confirmation" color="Gray"}
	<tr>
		<td colspan="2">
			{if $new}Farm succesfully built. {/if}Would you like to launch '{$farminfo.name}' now? {if $num > 0}This will launch <b>{$num}</b> new instance(s).{/if}
		</td>
	</tr>
{include file="inc/intable_footer.tpl" color="Gray"}
{if $show_dns}
{include file="inc/intable_header.tpl" header="DNS Zones" color="Gray"}
	<tr>
		<td colspan="2">
			<input style="vertical-align:middle;margin-left:-4px;" checked type="checkbox" name="mark_active" value="1"> Activate DNS zone now
		</td>
	</tr>
{include file="inc/intable_footer.tpl" color="Gray"}
{/if}