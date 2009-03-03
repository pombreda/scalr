{include file="inc/header.tpl"}
	<link rel="stylesheet" href="css/SelectControl.css" type="text/css" />
	<script type="text/javascript" src="js/class.SelectControl.js"></script>
    {include file="inc/table_header.tpl" show_region_filter=1}
    <table class="Webta_Items" rules="groups" frame="box" cellpadding="4" id="Webta_Items">
	<thead>
		<tr>
			<th>Farm name</th>
			<th>Role name</th>
			<th>IP address</th>
			<th>Used by role</th>
			<th>Instance</th>
			<th width="1%">Options</th>
		</tr>
	</thead>
	<tbody>
	{section name=id loop=$rows}
	<tr id='tr_{$smarty.section.id.iteration}'>
		<td class="Item" valign="top">
		{if $rows[id]->dbInfo || $rows[id]->dbInstance}
			<a href="farms_view.php?id={$rows[id]->farmId}">{$rows[id]->farmName}</a>
		{else}
			Not used by Scalr
		{/if}
		</td>
		<td class="Item" valign="top">
		{if $rows[id]->dbInfo}
			<a href="roles_view.php?farmid={$rows[id]->farmId}">{$rows[id]->dbInfo.role_name}</a>
		{elseif $rows[id]->dbInstance}
			<a href="roles_view.php?farmid={$rows[id]->farmId}">{$rows[id]->dbInstance.role_name}</a>
		{else}
			Not used by Scalr
		{/if}
		</td>
		<td class="Item" valign="top">{$rows[id]->publicIp}</td>
		<td class="Item" valign="top">
			{if $rows[id]->dbInfo}<img src="images/true.gif">{else}<img src="images/false.gif">{/if}
		</td>
		<td class="Item" valign="top">
		{if $rows[id]->dbInfo || $rows[id]->dbInstance}
			<a href="instances_view.php?iid={$rows[id]->instanceId}&farmid={$rows[id]->farmId}">{$rows[id]->instanceId}</a>
		{else}
			{$rows[id]->instanceId}
		{/if}
		</td>
		<td class="ItemEdit" valign="top"><a id="control_{$rows[id]->publicIp|@md5}" href="javascript:void(0)">Options</a></td>
	</tr>
	<script language="Javascript" type="text/javascript">
    	var ip = '{$rows[id]->publicIp}';
    	var ip_hash = '{$rows[id]->publicIp|@md5}';

    	var menu = [
            {literal}{href: 'elastic_ips.php?task=release&ip='+ip, innerHTML: 'Release'}{/literal}
        ];
        
        
        {literal}			
        var control = new SelectControl({menu: menu});
        control.attach('control_'+ip_hash);
        {/literal}
	
	</script>
	{sectionelse}
	<tr>
		<td colspan="6" align="center">No elastic IPs allocated</td>
	</tr>
	{/section}
	<tr>
		<td colspan="5" align="center">&nbsp;</td>
		<td class="ItemEdit" valign="top">&nbsp;</td>
	</tr>
	</tbody>
	</table>
	{include file="inc/table_footer.tpl" colspan=9 disable_footer_line=1}	
{include file="inc/footer.tpl"}