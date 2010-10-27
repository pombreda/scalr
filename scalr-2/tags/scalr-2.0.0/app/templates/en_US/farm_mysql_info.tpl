{include file="inc/header.tpl"}
	{include file="inc/table_header.tpl"}
		{include file="inc/intable_header.tpl" intable_colspan=2 header="PHPMyAdmin" intable_first_column_width="150" color="Gray"}
		<tr>
			<td colspan="3">
				{if $mysql_pma_credentials}
					<input class="btn" type="submit" name="pma_launch" value="Launch PHPMyAdmin" />
				{elseif $mysql_pma_processing_access_request}
					MySQL access details for PMA requested. Please refresh this page in a couple minutes...
				{else}
					<input class="btn" type="submit" name="pma_request_credentials" value="Setup PHPMyAdmin access" />
				{/if}
			</td>
		</tr>
		{include file="inc/intable_footer.tpl" color="Gray"} 
	
		{include file="inc/intable_header.tpl" intable_colspan=2 header="MySQL backups and data bundles" intable_first_column_width="150" color="Gray"}
		<tr>
			<td colspan="3">
				<table>
				<tr>
					<td width="150">Last backup: </td>
					<td width="300">
						{if $mysql_bcp_running}
						In progress on <a href="/server_view_extended_info.php?server_id={$mysql_bcp_server_id}">{$mysql_bcp_server_id}</a> server...
						{else}
							{if $mysql_last_backup}{$mysql_last_backup}{else}never{/if}
						{/if}
					</td>
					<td>
						<input type="submit" name="run_bcp" class="btn" value="Create backup now" />
					</td>
				</tr>
				<tr>
					<td>Last data bundle: </td>
					<td>
						{if $mysql_bundle_running}
						In progress on <a href="/server_view_extended_info.php?server_id={$mysql_bundle_server_id}">{$mysql_bundle_server_id}</a> server...
						{else}
							{if $mysql_last_bundle}{$mysql_last_bundle}{else}never{/if}
						{/if}
					</td>
					<td>
						<input type="submit" {if $mysql_bundle_running}disabled{/if} name="run_bundle" class="btn" value="Bundle mysql data now" />
					</td>
				</tr>
				{if $mysql_data_storage_engine == 'ebs'}
				<tr>
					<td colspan="3">&nbsp;</td>
				</tr>
				<tr>
					<td>MySQL EBS VolumeID: </td>
					<td>
						<input type="text" name="mysql_master_ebs" class="text" value="{$mysql_master_ebs_volume_id}" />
					</td>
					<td>
						<input type="submit" name="update_volumeid" class="btn" value="Change" />
					</td>
				</tr>
				{/if}
				<!-- 
				<tr>
					<td colspan="10">&nbsp;</td>
				</tr>
				<tr>
					<td colspan="10">&nbsp;</td>
				</tr>
				<tr>
					<td colspan="10">
						<input style="color:red;" type="submit" name="remove_mysql_data_bundle" class="btn" value="Remove MySQL bundle" />
					</td>
				</tr>
				 -->
				</table>
			</td>
		</tr>
		{include file="inc/intable_footer.tpl" color="Gray"}                   		
		{include file="inc/mysql_replication_status.tpl"}
	{include file="inc/table_footer.tpl" disable_footer_line=1}
{include file="inc/footer.tpl"}