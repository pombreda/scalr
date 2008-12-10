{include file="inc/header.tpl"}
<link rel="stylesheet" href="css/SelectControl.css" type="text/css" />
<script type="text/javascript" src="js/class.SelectControl.js"></script>
	{include file="inc/table_header.tpl"}
	<table class="Webta_Items" rules="groups" frame="box" width="100%" cellpadding="2" id="Webta_Items">
	<thead>
	<tr>
		<th>{t}Created by{/t}</th>
		<th>{t}Name{/t}</th>
		<th width="100%">{t}Description{/t}</th>
		<th nowrap="nowrap">{t}Version{/t}</th>
		<th>{t}Created at{/t}</th>
		<th width="1%">{t}Approval state{/t}</th>
		<th width="1">{t}Options{/t}</th>
	</tr>
	</thead>
	<tbody>
	{section name=id loop=$rows}
	<tr id='tr_{$smarty.section.id.iteration}'>
		<td class="Item" valign="top">{if $smarty.session.uid != 0 && $rows[id].client.id}<a href="clients_view.php?clientid={$rows[id].client.id}">{/if}{if $rows[id].client.email}{$rows[id].client.email}{else}Scalr{/if}{if $smarty.session.uid != 0}</a>{/if}</td>
		<td class="Item" valign="top" nowrap="nowrap">{$rows[id].name}</td>
		<td class="Item" valign="top">{$rows[id].description}</td>
		<td class="Item" valign="top" align="center">{$rows[id].revision}</td>
		<td class="Item" valign="top" nowrap>{$rows[id].dtadded}</td>
		<td class="Item" valign="top" align="center" nowrap>{if $rows[id].approval_state}{$rows[id].approval_state}{else}{t}Approved{/t}{/if}</td>
		<td class="ItemEdit" valign="top" width="1">{if ($rows[id].clientid != 0 && $rows[id].clientid == $smarty.session.uid) || $smarty.session.uid == 0}<a id="control_{$rows[id].id}" href="javascript:void(0)">{t}Options{/t}</a>{/if}</td>
	</tr>
	{if $rows[id].status == 0}
	<script language="Javascript" type="text/javascript">
    	var id = '{$rows[id].id}';
    	var scriptid = '{$rows[id].scriptid}';
    	var revision = '{$rows[id].revision}';
    	
		var menu = [
   				{literal}{href: 'script_info.php?id='+scriptid+'&revision='+revision, innerHTML: 'Moderate'}{/literal}
        ];
        
        {literal}			
        var control = new SelectControl({menu: menu});
        control.attach('control_'+id);
        {/literal}

	</script>
	{/if}
	{sectionelse}
	<tr>
		<td colspan="7" align="center">{t}No script templates found{/t}</td>
	</tr>
	{/section}
	<tr>
		<td colspan="6" align="center">&nbsp;</td>
		<td class="ItemEdit" valign="top">&nbsp;</td>
	</tr>
	</tbody>
	</table>
	{include file="inc/table_footer.tpl" colspan=9 disable_footer_line=1}
{include file="inc/footer.tpl"}