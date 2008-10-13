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
			<th>Owner</th>
			<th>Farms</th>
			<th>Stable</th>
			<th width="1%">Options</th>
		</tr>
	</thead>
	<tbody>
	{section name=id loop=$rows}
	<tr id='tr_{$smarty.section.id.iteration}'>
	    <td class="Item" valign="top">{$rows[id].name}</td>
		<td class="Item" valign="top">{$rows[id].ami_id}</td>
		<td class="Item" valign="top">{$rows[id].architecture}</td>
		<td class="Item" valign="top">{$rows[id].imageOwnerId}</td>
        <td class="Item" valign="top">{$rows[id].farmsCount}</td>
        <td class="Item" valign="top">{if $rows[id].isstable}<img src="images/true.gif">{else}<img src="images/false.gif">{/if}</td>
        <td class="ItemEdit" valign="top"><a id="control_{$rows[id].ami_id}" href="javascript:void(0)">Options</a></td>
	</tr>
	<script language="Javascript" type="text/javascript">
    	var id = '{$rows[id].ami_id}';
    	var arch = '{$rows[id].architecture}';
    	
    	var menu = [
    		{literal}{href: 'shared_roles_switch.php?ami_id='+id, innerHTML: 'Switch to new AMI'}{/literal},
        	{literal}{href: 'shared_roles_edit.php?ami_id='+id, innerHTML: 'Edit role'}{/literal},
        	{literal}{href: 'shared_roles.php?task=delete&ami_id='+id, innerHTML: 'Delete role'}{/literal}
        ];
        
        
        {literal}			
        var control = new SelectControl({menu: menu});
        control.attach('control_'+id);
        {/literal}
	
	</script>
	{sectionelse}
	<tr>
		<td colspan="7" align="center">No shared AMIs found</td>
	</tr>
	{/section}
	<tr>
		<td colspan="6" align="center">&nbsp;</td>
		<td class="ItemEdit" valign="top">&nbsp;</td>
	</tr>
	</tbody>
	</table>
	{include file="inc/table_footer.tpl" colspan=9}	
		{include file="inc/footer.tpl"}