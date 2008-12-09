{include file="inc/header.tpl"}
<link rel="stylesheet" href="css/SelectControl.css" type="text/css" />
<script type="text/javascript" src="js/class.SelectControl.js"></script>
	{include file="inc/table_header.tpl"}
	<table class="Webta_Items" rules="groups" frame="box" width="100%" cellpadding="2" id="Webta_Items">
	<thead>
	<tr>
		<th>Application</th>
		<th>Farm</th>
		<th>Role</th>
		<th width="180">DNS Zone status</th>
		<th width="1">Options</th>
		<th nowrap width="1%"><input type="checkbox" name="checkbox" value="checkbox" onClick="checkall()"></th>
	</tr>
	</thead>
	<tbody>
	{section name=id loop=$rows}
	<tr id='tr_{$smarty.section.id.iteration}'>
		<td class="Item" valign="top">{$rows[id].zone}</td>
		<td class="Item" valign="top"><a href="farms_view.php?farmid={$rows[id].farm.id}">{$rows[id].farm.name}</a></td>
		<td class="Item" valign="top"><a href="roles_view.php?farmid={$rows[id].farm.id}&ami_id={$rows[id].role.ami_id}">{$rows[id].role.name}</a></td>
		<td class="Item" valign="top">{$rows[id].string_status}</td>
		<td class="ItemEdit" valign="top" width="1">{if $rows[id].status == 0}<a id="control_{$rows[id].zone}" href="javascript:void(0)">Options</a>{/if}</td>
		<td class="ItemDelete">
			<span>
				<input type="checkbox" id="delete[]" {if $rows[id].status > 1}disabled{/if} name="delete[]" value="{$rows[id].id}">
			</span>
		</td>
	</tr>
	{if $rows[id].status == 0}
	<script language="Javascript" type="text/javascript">
    	var zone = '{$rows[id].zone}';
    	
    	/* TODO */
    	
    	var menu = [
            {literal}{href: 'sites_add.php?ezone='+zone, innerHTML: 'Edit DNS zone'}{/literal}
            
            {if $rows[id].role_alias == 'app' && $vhost_enabled},{literal}{href: 'vhost.php?name='+zone, innerHTML: 'Configure apache virtual host'}{/literal}{/if}
        ];
        
        
        {literal}			
        var control = new SelectControl({menu: menu});
        control.attach('control_'+zone);
        {/literal}
	
	</script>
	{/if}
	{sectionelse}
	<tr>
		<td colspan="8" align="center">No applications found</td>
	</tr>
	{/section}
	<tr>
		<td colspan="4" align="center">&nbsp;</td>
		<td class="ItemEdit" valign="top">&nbsp;</td>
		<td class="ItemDelete" valign="top">&nbsp;</td>
	</tr>
	</tbody>
	</table>
	{include file="inc/table_footer.tpl" colspan=9}
{include file="inc/footer.tpl"}