{include file="inc/header.tpl"}
<link rel="stylesheet" href="css/SelectControl.css" type="text/css" />
<script type="text/javascript" src="js/class.SelectControl.js"></script>
	<br>
	{include file="inc/table_header.tpl" nofilter=1}
    	{include file="inc/intable_header.tpl" header="Actions" color="Gray"}
    	<tr>
    	   <td colspan="2"><img src="/images/add.png" style="vertical-align:middle;">&nbsp;<a href="script_templates.php?task=create">Create new script template</a></td>
    	</tr>
    	{include file="inc/intable_footer.tpl" color="Gray"}
	{include file="inc/table_footer.tpl" disable_footer_line=1}
	<br>
	{include file="inc/table_header.tpl"}
	<table class="Webta_Items" rules="groups" frame="box" width="100%" cellpadding="2" id="Webta_Items">
	<thead>
	<tr>
		<th>{t}Author{/t}</th>
		<th>{t}Name{/t}</th>
		<th width="100%">{t}Description{/t}</th>
		<th nowrap="nowrap">{t}Latest version{/t}</th>
		<th>{t}Updated on{/t}</th>
		<th width="1%">{t}Origin{/t}</th>
		<th width="1%" nowrap="nowrap">{t}Moderation phase{/t}</th>
		<th width="1">{t}Options{/t}</th>
	</tr>
	</thead>
	<tbody>
	{section name=id loop=$rows}
	<tr id='tr_{$smarty.section.id.iteration}'>
		<td class="Item" valign="top">
			{if $smarty.session.uid == 0}
				{if $rows[id].client.id}
				<a href="clients_view.php?clientid={$rows[id].client.id}">{$rows[id].client.email}</a>
				{else}
				Scalr
				{/if}
			{else}
				{if $rows[id].client.id}
					{if $rows[id].client.id == $smarty.session.uid}
						Me
					{else}
						{$rows[id].client.fullname}
					{/if}
				{else}
					Scalr
				{/if}
			{/if}
		</td>
		<td class="Item" valign="top" nowrap="nowrap"><a title="{t}View script information{/t}" href="script_info.php?id={$rows[id].id}">{$rows[id].name}</a></td>
		<td class="Item" valign="top">{$rows[id].description}</td>
		<td class="Item" valign="top" align="center">{$rows[id].version}</td>
		<td class="Item" valign="top" nowrap>{$rows[id].dtupdated}</td>
		<td class="Item" valign="top" nowrap="nowrap" align="center">
			{if $rows[id].origin == 'Shared'}
				<img src="/images/dhtmlxtree/csh_vista/icon_script.png" title="Contributed by Scalr">
			{elseif $rows[id].origin == 'Custom'}
				<img src="/images/dhtmlxtree/csh_vista/icon_script_custom.png" title="Custom">
			{else}
				<img src="/images/dhtmlxtree/csh_vista/icon_script_contributed.png" title="Contributed by {$rows[id].client.fullname}"> 
			{/if}
		</td>
		<td class="Item" valign="top" nowrap="nowrap" align="center">
			{if $rows[id].approval_state == 'Approved' || !$rows[id].approval_state}
				<img src="/images/true.gif" title="{t}Approved{/t}">
			{elseif $rows[id].approval_state == 'Pending'}
				<img src="/images/pending.gif" title="{t}Pending{/t}">
			{elseif $rows[id].approval_state == 'Declined'}
				<img src="/images/false.gif" title="{t}Declined{/t}">
			{/if}
		</td>
		<td class="ItemEdit" valign="top" width="1"><a id="control_{$rows[id].id}" href="javascript:void(0)">{t}Options{/t}</a></td>
	</tr>
	{if $rows[id].status == 0}
	<script language="Javascript" type="text/javascript">
    	var id = '{$rows[id].id}';
  	
    	var menu = [
    			{if $smarty.session.uid != 0 && ($rows[id].clientid == 0 || ($rows[id].clientid != 0 && $rows[id].clientid != $smarty.session.uid))}
    				{literal}{href: 'script_templates.php?task=fork&id='+id, innerHTML: 'Fork'},{/literal}
    				{literal}{type: 'separator'},{/literal}
    			{/if}
    	
    			{literal}{href: 'script_info.php?id='+id, innerHTML: 'View'}{/literal}
    			    			
    			{if ($rows[id].clientid != 0 && $rows[id].clientid == $smarty.session.uid) || $smarty.session.uid == 0}
    			{literal},{type: 'separator'},{/literal}
    			{if $rows[id].origin == 'Custom' && $smarty.session.uid != 0}
    				{literal}{href: 'script_templates.php?task=share&id='+id, innerHTML: 'Share'},{/literal}
    				{literal}{type: 'separator'},{/literal}
    			{/if}
    			
    			{literal}{href: 'script_templates.php?task=edit&id='+id, innerHTML: 'Edit'},{/literal}
    			{literal}{href: 'script_templates.php?id='+id+"&task=delete", innerHTML: 'Delete'}{/literal}
    			{/if}
        ];
        
        {literal}			
        var control = new SelectControl({menu: menu});
        control.attach('control_'+id);
        {/literal}
	</script>
	{/if}
	{sectionelse}
	<tr>
		<td colspan="8" align="center">{t}No script templates found{/t}</td>
	</tr>
	{/section}
	<tr>
		<td colspan="7" align="center">&nbsp;</td>
		<td class="ItemEdit" valign="top">&nbsp;</td>
	</tr>
	</tbody>
	</table>
	{include file="inc/table_footer.tpl" colspan=9 page_data_options_add_querystring="?task=create"}
{include file="inc/footer.tpl"}