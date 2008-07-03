{include file="inc/header.tpl"}
    <link rel="stylesheet" href="css/SelectControl.css" type="text/css" />
    <script type="text/javascript" src="js/class.SelectControl.js"></script>
    {include file="inc/table_header.tpl"}
    <table class="Webta_Items" rules="groups" frame="box" width="100%" cellpadding="4" id="Webta_Items">
	<thead>
		<tr>
		    <th>Role</th>
			<th>ID</th>
			<th>Architecture</th>
			<th>Location</th>
			<th>State</th>
			<th>Owner</th>
			<th>Shared</th>
			<th>Farms</th>
			<th width="1%">Options</th>
		</tr>
	</thead>
	<tbody>
	{section name=id loop=$rows}
	<tr id='tr_{$smarty.section.id.iteration}'>
	    <td class="Item" valign="top">{if $rows[id]->roleName}{$rows[id]->roleName}{else}{/if}</td>
		<td class="Item" valign="top">{$rows[id]->imageId}</td>
		<td class="Item" valign="top">{$rows[id]->architecture}</td>
		<td class="Item" valign="top">{$rows[id]->imageLocation}</td>
		<td class="Item" valign="top">{$rows[id]->imageState}</td>
		<td class="Item" valign="top">{$rows[id]->imageOwnerId}</td>
		<td class="Item" align="center" valign="top"><img border="0" align="absmiddle" src="/images/{if $rows[id]->roleName}true{else}false{/if}.gif"></td>
        <td class="Item" valign="top">{$rows[id]->farmsCount}</td>
        <td class="ItemEdit" valign="top"><a id="control_{$rows[id]->imageId}" href="javascript:void(0)">Options</a></td>
	</tr>
	<script language="Javascript" type="text/javascript">
    	var id = '{$rows[id]->imageId}';
    	var arch = '{$rows[id]->architecture}';
    	
    	var menu = [
    	{if $rows[id]->roleName}
    		{literal}{href: 'shared_roles_switch.php?ami_id='+id, innerHTML: 'Switch to new AMI'}{/literal},
            {literal}{href: 'shared_roles_edit.php?ami_id='+id, innerHTML: 'Edit role'}{/literal},
            {literal}{href: 'shared_roles.php?task=delete&ami_id='+id, innerHTML: 'Delete role'}{/literal}
        {else}
            {literal}{href: 'shared_roles_edit.php?ami_id='+id+'&arch='+arch, innerHTML: 'Set role'}{/literal}
        {/if}
        ];
        
        
        {literal}			
        var control = new SelectControl({menu: menu});
        control.attach('control_'+id);
        {/literal}
	
	</script>
	{sectionelse}
	<tr>
		<td colspan="9" align="center">No images found</td>
	</tr>
	{/section}
	<tr>
		<td colspan="8" align="center">&nbsp;</td>
		<td class="ItemEdit" valign="top">&nbsp;</td>
	</tr>
	</tbody>
	</table>
	{include file="inc/table_footer.tpl" colspan=9 disable_footer_line=1}	
		{include file="inc/footer.tpl"}