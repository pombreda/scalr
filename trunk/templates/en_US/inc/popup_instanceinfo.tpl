<table style="font-size:11px;font-family:Verdana;" width="100%" cellpadding="2" cellspacing="2">
	<tr>
		<td>{t}Instance ID:{/t}</td>
		<td>{$i.instance_id}</td>
	</tr>
	<tr>
		<td>{t}Status:{/t}</td>
		<td style="color:{if $i.state == 'Running'}green{else}red{/if};">{$i.state}</td>
	</tr>
	<tr>
		<td>{t}Load averages:{/t}</td>
		<td>{$i.LA}</td>
	</tr>
	<tr>
		<td>{t}Public IP:{/t}</td>
		<td>{if $i.IsElastic}<span style="color:green;vertical-align:middle;">{/if}{$i.external_ip}{if $i.IsElastic}</span>&nbsp;<img src="/images/icon_shelp.gif" style="vertical-align:middle;" title="{t}Elastic IP{/t}">{/if}</td>
	</tr>
	<tr>
		<td>{t}Internal IP:{/t}</td>
		<td>{$i.internal_ip}</td>
	</tr>
	<tr>
		<td>{t}Type:{/t}</td>
		<td>{$i.type}</td>
	</tr>
	<tr>
		<td>{t}Placement:{/t}</td>
		<td>{$i.placement}</td>
	</tr>
	<tr>
		<td>{t}Launch time:{/t}</td>
		<td>{$i.launchtime}</td>
	</tr>
	<tr>
		<td>{t}AMI ID:{/t}</td>
		<td>{$i.ami_id}</td>
	</tr>
	<tr>
		<td>{t}Role:{/t}</td>
		<td>{$i.role_name}</td>
	</tr>
	{if $i.role_alias == 'mysql'}
	<tr>
		<td>{t}Replication role:{/t}</td>
		<td>{if $i.isdbmaster == 1}{t}Master{/t}{else}{t}Slave{/t}{/if}</td>
	</tr>
	{/if}
</table>