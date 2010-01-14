{include file="inc/header.tpl"}
    {if $smarty.session.uid == 0}
	<table width="100%" border="0">
		<tr valign="top">
			<td>
				{include file="inc/table_header.tpl" nofilter=1}
					{include file="inc/intable_header.tpl" header="Clients" color="Gray"}
						<tr>
							<td>Total:</td>
							<td>{$clients.total} [<a href="clients_view.php">View</a>]</td>
						</tr>
						<tr>
							<td>Active:</td>
							<td>{$clients.active} [<a href="clients_view.php?isactive=1">View</a>]</td>
						</tr>
						<tr>
							<td>Inactive:</td>
							<td>{$clients.inactive} [<a href="clients_view.php?isactive=0">View</a>]</td>
						</tr>
					{include file="inc/intable_footer.tpl" color="Gray"}
					
					{include file="inc/intable_header.tpl" header="Farms" color="Gray"}
						<tr>
							<td>Total:</td>
							<td>{$farms.total} {if $farms.total > 0}[<a href="farms_view.php">View</a>]{/if}</td>
						</tr>
						<tr>
							<td>Running:</td>
							<td>{$farms.running} {if $farms.running > 0}[<a href="farms_view.php?status=1">View</a>]{/if}</td>
						</tr>
						<tr>
							<td>Terminated:</td>
							<td>{$farms.terminated} {if $farms.terminated > 0}[<a href="farms_view.php?status=0">View</a>]{/if}</td>
						</tr>
					{include file="inc/intable_footer.tpl" color="Gray"}
					
					{include file="inc/intable_header.tpl" header="Scripts" color="Gray"}
						<tr>
							<td>Total:</td>
							<td>{$scripts.total} {if $scripts.total > 0}[<a href="script_templates.php">View</a>]{/if}</td>
						</tr>
						<tr>
							<td>Shared:</td>
							<td>{$scripts.shared} {if $scripts.shared > 0}[<a href="script_templates.php?origin=Shared">View</a>]{/if}</td>
						</tr>
						<tr>
							<td>Custom:</td>
							<td>{$scripts.custom} {if $scripts.custom > 0}[<a href="script_templates.php?origin=Custom">View</a>]{/if}</td>
						</tr>
						<tr>
							<td>Contributed (Approved):</td>
							<td>{$scripts.approved} {if $scripts.approved > 0}[<a href="contrib_script_templates.php?approval_state=Approved">View</a>]{/if}</td>
						</tr>
						<tr>
							<td>Contributed (Pending):</td>
							<td>{$scripts.pending} {if $scripts.pending > 0}[<a href="contrib_script_templates.php?approval_state=Pending">View</a>]{/if}</td>
						</tr>
						<tr>
							<td>Contributed (Declined):</td>
							<td>{$scripts.declined} {if $scripts.declined > 0}[<a href="contrib_script_templates.php?approval_state=Declined">View</a>]{/if}</td>
						</tr>
					{include file="inc/intable_footer.tpl" color="Gray"}
					
					{include file="inc/intable_header.tpl" header="Roles" color="Gray"}
						<tr>
							<td>Total active:</td>
							<td>{$roles.total} {if $roles.total > 0}[<a href="client_roles_view.php">View</a>]{/if}</td>
						</tr>
						<tr>
							<td>Shared:</td>
							<td>{$roles.shared} {if $roles.shared > 0}[<a href="client_roles_view.php?type=SHARED">View</a>]{/if}</td>
						</tr>
						<tr>
							<td>Custom:</td>
							<td>{$roles.custom} {if $roles.custom > 0}[<a href="client_roles_view.php?type=CUSTOM">View</a>]{/if}</td>
						</tr>
						<tr>
							<td>Contributed (Approved):</td>
							<td>{$roles.approved} {if $roles.approved > 0}[<a href="client_roles_view.php?type=SHARED&approval_state=Approved">View</a>]{/if}</td>
						</tr>
						<tr>
							<td>Contributed (Pending):</td>
							<td>{$roles.pending} {if $roles.pending > 0}[<a href="client_roles_view.php?type=SHARED&approval_state=Pending">View</a>]{/if}</td>
						</tr>
						<tr>
							<td>Contributed (Declined):</td>
							<td>{$roles.declined} {if $roles.declined > 0}[<a href="client_roles_view.php?type=SHARED&approval_state=Declined">View</a>]{/if}</td>
						</tr>
					{include file="inc/intable_footer.tpl" color="Gray"}
				{include file="inc/table_footer.tpl" disable_footer_line=1}
			</td>
		</tr>
	</table>
	{/if}
{include file="inc/footer.tpl"}
