{include file="inc/header.tpl"}
	{include file="inc/table_header.tpl"}
        {include file="inc/intable_header.tpl" header="General" color="Gray"}
        <tr>
			<td width="20%">Farm ID:</td>
			<td>{$farmrole->FarmID}</td>
		</tr>
		<tr>
			<td width="20%">Role ID:</td>
			<td>{$farmrole->RoleID}</td>
		</tr>
		<tr>
			<td width="20%">Role Name:</td>
			{assign var=role value=$farmrole->GetRoleObject()}
			<td>{$role->name}</td>
		</tr>
		<tr>
			<td width="20%">Platform:</td>
			<td>{$farmrole->Platform}</td>
		</tr>
		{include file="inc/intable_footer.tpl" color="Gray"}
		
		{include file="inc/intable_header.tpl" header="Scaling details" color="Gray"}
		{section name=id loop=$metrics}
        <tr>
			<td width="20%">{$metrics[id].name}:</td>
			<td>{if $metrics[id].last_value != null}Checked at {$metrics[id].date}. Value: {$metrics[id].last_value}{else}Never checked{/if}</td>
		</tr>
		{sectionelse}
		<tr>
			<td colspan="2">Scaling disabled for this role</td>
		</tr>
		{/section}
		{include file="inc/intable_footer.tpl" color="Gray"}
			
		<!-- 	
		{include file="inc/intable_header.tpl" header="Platfrom specific details" color="Gray"}
		{if $info}
			{foreach key=name item=value from=$info}
			<tr>
				<td width="20%">{$name}:</td>
				<td>{$value}</td>
			</tr>
			{/foreach}
		{else}
		<tr>
			<td colspan='2'>Platform specific details not available for this server.</td>
		</tr>
		{/if}
        {include file="inc/intable_footer.tpl" color="Gray"}
        -->
        
        {include file="inc/intable_header.tpl" header="Scalr internal properties" color="Gray"}
		{foreach key=name item=value from=$props}
			<tr>
				<td width="20%">{$name}:</td>
				<td>{$value}</td>
			</tr>
		{/foreach}
		{include file="inc/intable_footer.tpl" color="Gray"}
	{include file="inc/table_footer.tpl" disable_footer_line=1}
{include file="inc/footer.tpl"}