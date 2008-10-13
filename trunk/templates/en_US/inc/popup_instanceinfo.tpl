<table style="font-size:11px;font-family:Verdana;" width="100%" cellpadding="2" cellspacing="2">
	<tr>
		<td>Instance ID:</td>
		<td>{$i.instance_id}</td>
	</tr>
	<tr>
		<td>Status:</td>
		<td style="color:{if $i.state == 'Running'}green{else}red{/if};">{$i.state}</td>
	</tr>
	<tr>
		<td>Load averages:</td>
		<td>{$i.LA}</td>
	</tr>
	<tr>
		<td>Public IP:</td>
		<td>{if $i.IsElastic}<span style="color:green;vertical-align:middle;">{/if}{$i.external_ip}{if $i.IsElastic}</span>&nbsp;<img src="/images/icon_shelp.gif" style="vertical-align:middle;" title="Elastic IP">{/if}</td>
	</tr>
	<tr>
		<td>Internal IP:</td>
		<td>{$i.internal_ip}</td>
	</tr>
	<tr>
		<td>Type:</td>
		<td>{$i.type}</td>
	</tr>
	<tr>
		<td>Placement:</td>
		<td>{$i.placement}</td>
	</tr>
	<tr>
		<td>Launch time:</td>
		<td>{$i.launchtime}</td>
	</tr>
	<tr>
		<td>AMI ID:</td>
		<td>{$i.ami_id}</td>
	</tr>
	<tr>
		<td>Role:</td>
		<td>{$i.role_name}</td>
	</tr>
</table>