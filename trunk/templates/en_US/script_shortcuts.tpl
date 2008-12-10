{include file="inc/header.tpl"}
<link rel="stylesheet" href="css/SelectControl.css" type="text/css" />
<script type="text/javascript" src="js/class.SelectControl.js"></script>
    {include file="inc/table_header.tpl"}
    <table class="Webta_Items" rules="groups" frame="box" cellpadding="4" id="Webta_Items">
	<thead>
		<tr>
			<th>Target</th>
			<th>Script</th>
			<th width="1">Options</th>
			<td class="th" width="1%" nowrap><input type="checkbox" name="checkbox" value="checkbox" onClick="checkall()"></th>
		</tr>
	</thead>
	<tbody>
	{section name=id loop=$rows}
	<tr id='tr_{$smarty.section.id.iteration}'>
		<td class="Item" valign="top" nowrap>
			<a href="farms_view.php?id={$rows[id].farmid}">{$rows[id].farmname}</a>
			{if $rows[id].ami_id}
			&rarr;
			<a href="roles_view.php?farmid={$rows[id].farmid}&ami_id={$rows[id].ami_id}">{$rows[id].rolename}</a>
			{/if}&nbsp;&nbsp;&nbsp;
		</td>
		<td width="100%" class="Item" valign="top" nowrap>{$rows[id].scriptname}</td>
		<td class="ItemEdit" valign="top" width="1"><a id="control_{$rows[id].id}" href="javascript:void(0)">Options</a></td>
        <td class="ItemDelete">
			<span>
				<input type="checkbox" id="delete[]" name="delete[]" value="{$rows[id].id}">
			</span>
		</td>
	</tr>
	<script language="Javascript" type="text/javascript">
    	var scriptid = '{$rows[id].event_name}';
    	var farmid = '{$rows[id].farmid}';
    	var id = '{$rows[id].id}';
    	
    	var menu = [
            {literal}{href: 'execute_script.php?script='+scriptid+"&task=edit&farmid="+farmid, innerHTML: 'Edit'}{/literal}
        ];
        
        
        {literal}			
        var control = new SelectControl({menu: menu});
        control.attach('control_'+id);
        {/literal}
	
	</script>
	{sectionelse}
	<tr>
		<td colspan="14" align="center">No shortcuts found</td>
	</tr>
	{/section}
	<tr>
		<td colspan="2" align="center">&nbsp;</td>
		<td class="ItemEdit" valign="top">&nbsp;</td>
		<td class="ItemDelete" valign="top">&nbsp;</td>
	</tr>
	</tbody>
	</table>
	{include file="inc/table_footer.tpl" colspan=9}
{include file="inc/footer.tpl"}