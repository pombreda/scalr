{include file="inc/header.tpl"}
	{include file="inc/table_header.tpl"}
		{include file="inc/intable_header.tpl" intable_colspan=2 header="Backups history" intable_first_column_width="150" color="Gray"}
		<tr>
			<td colspan="3">
				<table>
				<tr>
					<td width="150">Last backup: </td>
					<td width="200">
						{if $mysql_last_backup}{$mysql_last_backup}{else}never{/if}
					</td>
					<td>
						<input type="submit" name="run_bcp" class="btn" value="Create backup now">
					</td>
				</tr>
				<tr>
					<td>Last bundle: </td>
					<td>
						{if $mysql_last_bundle}{$mysql_last_bundle}{else}never{/if}
					</td>
					<td>
						<input type="submit" name="run_bundle" class="btn" value="Bundle mysql data now">
					</td>
				</tr>
				</table>
			</td>
		</tr>
		{include file="inc/intable_footer.tpl" color="Gray"}                   		
		{include file="inc/mysql_replication_status.tpl"}
	{include file="inc/table_footer.tpl" disable_footer_line=1}
{include file="inc/footer.tpl"}