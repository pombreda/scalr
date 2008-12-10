{include file="inc/header.tpl"}
   	{include file="inc/table_header.tpl" filter=0}
		<table class="Webta_Items" rules="groups" frame="box" width="100%" cellpadding="2" id="Webta_Items">
		<thead>
			<tr>
				<th>{t}ID{/t}</th>
				<th>{t}Severity{/t}</th>
				<th>{t}Date{/t}</th>
				<th>{t}Action{/t}</th>
			</tr>
		</thead>
		<tbody>
		{section name=id loop=$rows}
		<tr id='tr_{$smarty.section.id.iteration}' {if $rows[id].severity == 'FATAL' || $rows[id].severity == 'ERROR'}style="background-color:pink;"{/if}>
			<td class="Item" nowrap="nowrap" valign="top">{$rows[id].id}</td>
			<td class="Item" nowrap="nowrap" valign="top">{$rows[id].severity} {if $rows[id].severity == 'FATAL' || $rows[id].severity == 'ERROR'}(<a href="syslog_view_backtrace.php?logeventid={$rows[id].id}">View backtrace</a>){/if}</td>
			<td class="Item" nowrap="nowrap" valign="top">{$rows[id].dtadded}</td>
			<td class="Item" valign="top">{if $rows[id].transactionid != $rows[id].sub_transactionid}<a href="syslog_transaction_details.php?strnid={$rows[id].sub_transactionid}&trnid={$rows[id].transactionid}">{$rows[id].message}</a>{else}{$rows[id].message}{/if}</td>
		</tr>
		{sectionelse}
		<tr>
			<td colspan="4" align="center">{t}No log entries found{/t}</td>
		</tr>
		{/section}
		<tr>
			<td colspan="4" align="center">&nbsp;</td>
		</tr>
		</tbody>
		</table>
	{include file="inc/table_footer.tpl" colspan=9 allow_delete=0 disable_footer_line=1 add_new=0}
	<br>
{include file="inc/footer.tpl"}