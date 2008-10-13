{include file="inc/intable_header.tpl" header="DNS Zones" color="Gray"}
<tr>
	<td colspan="2">
		<input style="vertical-align:middle;" checked type="checkbox" name="deleteDNS" value="1"> Delete DNS zone from nameservers. It will be recreated when the farm is launched.
	</td>
</tr>
{include file="inc/intable_footer.tpl" color="Gray"}
    	
{if $elastic_ips > 0}
{include file="inc/intable_header.tpl" header="Elastic IPs" color="Gray"}
<tr>
	<td colspan="2">
		<div style="margin-top:10px;margin-left:-2px;">
			<input type="radio" style="vertical-align:middle;" checked="checked" name="keep_elastic_ips" value="0">
			<span style="vertical-align:middle;">Release the static IP adresses that are allocated for this farm. When you start the farm again, new IPs will be allocated.</span>
		</div>
		<div style="margin-top:10px;margin-left:-2px;">
			<input type="radio" style="vertical-align:middle;" name="keep_elastic_ips" value="1">
			<span style="vertical-align:middle;">Keep the static IP adresses that are allocated for this farm. Amazon will keep billing you for them even when the farm is stopped.</span>
		</div>
	</td>
</tr>
{include file="inc/intable_footer.tpl" color="Gray"}
{/if}