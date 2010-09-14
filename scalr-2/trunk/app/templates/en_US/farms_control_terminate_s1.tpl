{include file="inc/intable_header.tpl" header="Confirmation" color="Gray"}
<tr>
	<td colspan="2">
	{if $outdated_farm_roles|@count == 0}
		Do you really want to terminate farm '{$farminfo.name}'? {if $num > 0}All <b>{$num}</b> instance(s) in this farm will be terminated.{/if}
	{else}
		You haven't saved your servers since the last time the farm was launched. 
		If you made any changes on any server, you should save your changes. 
		Otherwise, all changes will be lost upon farm termination.
		<br />
		<br />
		<div style="background-color:#F9F9F9;padding:10px;">
		<div style="font-weight:bold;">Save my changes on the following servers (using Synchronize to all):</div>
		<br />
			{section name=id loop=$outdated_farm_roles}
			<div style="margin-bottom:10px;">
				<div style="width:100%;">
					<div style="float:left;line-height:40px;">
					<input {if $outdated_farm_roles[id]->IsBundleRunning}checked disabled{/if} onclick="SetSyncChecked('{$outdated_farm_roles[id]->ID}', this.checked);" type="checkbox" name="sync[]" value="{$outdated_farm_roles[id]->ID}" style="vertical-align:middle;"> 
					{$outdated_farm_roles[id]->GetRoleName()} ({$outdated_farm_roles[id]->GetImageId()}) &nbsp;&nbsp;Last synchronization: {if $outdated_farm_roles[id]->dtLastSync}{$outdated_farm_roles[id]->dtLastSync}{else}Never{/if}
					</div>
					{if $outdated_farm_roles[id]->GetRoleAlias() == 'mysql'}
					<div class="Webta_ExperimentalMsg" style="float:left;margin-left:15px;padding-right:15px;font-size:12px;">
						The bundle will not include MySQL data. <a href='farm_mysql_info.php?farmid={$farminfo.id}'>Click here if you wish to bundle and save MySQL data</a>.
					</div> 
					{/if}
					<div style="clear:both;font-size:1px;"></div>
				</div>
				{if !$outdated_farm_roles[id]->IsBundleRunning}
				<div id="i_{$outdated_farm_roles[id]->ID}" style="margin-left:20px;display:none;">
					{assign var=servers value=$outdated_farm_roles[id]->RunningServers}
					{section name=iid loop=$servers}
						<input {if $smarty.section.iid.first}checked{/if} style="vertical-align:middle;" type="radio" name="sync_i[{$outdated_farm_roles[id]->ID}]" value="{$servers[iid]->serverId}"> {$servers[iid]->serverId} ({$servers[iid]->remoteIp})
						<br />
					{sectionelse}
						No running servers found on this role.
					{/section}
				</div>
				{else}
				<div id="i_{$outdated_farm_roles[id]->ID}" style="margin-left:20px;">
					Synchronization for this role is already running... 
				</div>
				{/if}
			</div>
			{/section}
			<div id="sync_opts" style="display:none;">
			<br />
				<input style="vertical-align:middle;" type="checkbox" name="untermonfail" value="1"> Do not terminate a farm if synchronization fail on any role.
			</div>
		</div>
	{/if}
	</td>
</tr>
{include file="inc/intable_footer.tpl" color="Gray"}