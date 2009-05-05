{include file="inc/header.tpl"}
	<link rel="stylesheet" href="css/SelectControl.css" type="text/css" />
	<script type="text/javascript" src="js/class.SelectControl.js"></script>
    {include file="inc/table_header.tpl" show_region_filter=1}
    <table class="Webta_Items" rules="groups" frame="box" cellpadding="4" id="Webta_Items">
	<thead>
		<tr>
			<th>{t}Role name{/t}</th>
			<th>{t}Owner{/t}</th>
			<th>{t}Category{/t}</th>
			<th>{t}AMI{/t}</th>
			<th>{t}Arch{/t}</th>
			<th>{t}Status{/t}</th>
			<th nowrap="nowrap">{t}Last Build{/t}</th>
			<th>{t}Contributed{/t}</th>
			<th nowrap="nowrap">{t}Moderation phase{/t}</th>
			<th></th>
			<td width="1%" nowrap><input type="checkbox" name="checkbox" value="checkbox" onClick="webtacp.checkall()"></td>
		</tr>
	</thead>
	<tbody>
	{section name=id loop=$rows}
	<tr id='tr_{$smarty.section.id.iteration}'>
		<td class="Item" valign="top">
			{if !$rows[id].isreplaced && $rows[id].iscompleted == 1}
				<a href="role_info.php?id={$rows[id].id}">{$rows[id].name}</a>
			{else}
				{$rows[id].name}
			{/if}
		</td>
		<td class="Item" valign="top">{if $smarty.session.uid == 0 && $rows[id].client.id}<a href="clients_view.php?clientid={$rows[id].client.id}">{/if}{$rows[id].client.fullname}{if $smarty.session.uid == 0}</a>{/if}</td>
		<td class="Item" valign="top">{$rows[id].type}</td>
		<td class="Item" valign="top">{$rows[id].ami_id}</td>
		<td class="Item" valign="top">{$rows[id].architecture}</td>
		<td class="Item" valign="top">
		{if $rows[id].isreplaced && $rows[id].iscompleted != 2}
		  {t}Synchronizing{/t}&#x2026;
		{else}
		  {if $rows[id].iscompleted == 1}Active{elseif $rows[id].iscompleted == 0}{t}Bundling...{/t}{else}{if $rows[id].fail_details}<a href="custom_roles_failed_details.php?id={$rows[id].id}">{/if}{t}Failed{/t}{if $rows[id].fail_details}</a>{/if}{/if}
		{/if}
		{if $rows[id].abort_id}(<a href="client_roles_view.php?task=abort&id={$rows[id].abort_id}">{t}Abort{/t}</a>){/if}
		</td>
		<td class="Item" valign="top">{$rows[id].dtbuilt}</td>
		<td class="Item" valign="top" align="center"><img src="/images/{if $rows[id].roletype == 'SHARED'}true{else}false{/if}.gif"></td>
		<td class="Item" valign="top" align="center">
			{if $rows[id].approval_state == 'Approved'}
				<img src="/images/true.gif" title="{t}Approved{/t}">
			{elseif $rows[id].approval_state == 'Pending'}
				<img src="/images/pending.gif" title="{t}Pending{/t}">
			{elseif $rows[id].approval_state == 'Declined'}
				<img src="/images/false.gif" title="{t}Declined{/t}">
			{/if}
		</td>
		<td class="Item" valign="top" width="40" align="center"><a id="control_{if $rows[id].isreplaced}{$rows[id].isreplaced}{else}{$rows[id].id}{/if}" href="javascript:void(0)">{t}Options{/t}</a></td>
		<td class="ItemDelete" valign="top">
			<span>
				<input type="checkbox" {if $rows[id].iscompleted != 0 || $rows[id].isreplaced}{else}disabled{/if} id="delete[]" name="delete[]" value="{$rows[id].id}">
			</span>
		</td>
	</tr>
	<script language="Javascript" type="text/javascript">
    	var id = '{if $rows[id].isreplaced}{$rows[id].isreplaced}{else}{$rows[id].id}{/if}';
    	
    	var menu = [
    		{if $rows[id].iscompleted == 1 && !$rows[id].isreplaced}
    			{literal}{href: 'role_info.php?id='+id, innerHTML: 'View'}{/literal}
    		{/if}
    		{if $rows[id].roletype == 'CUSTOM'}
	    		
	    		{if $smarty.session.uid != 0}
		    		{if $rows[id].iscompleted == 1 && !$rows[id].isreplaced}
		    			,{literal}{type:'separator'}{/literal},
		    			{literal}{href: 'client_role_edit.php?id='+id, innerHTML: 'Edit'},{/literal}
		    			{literal}{type:'separator'},{/literal}
		    			{literal}{href: 'client_role_edit.php?task=share&id='+id, innerHTML: 'Share this role'},{/literal}			    		
			    		{literal}{type:'separator'},{/literal}
						/*
				    	{literal}{href: 'client_role_clone.php?id='+id, innerHTML: 'Clone this role to another region'},{/literal}			    		
			    		{literal}{type:'separator'},{/literal}
				    	*/
		    		{/if}
	            {else}
	            	{if $rows[id].iscompleted == 1 && !$rows[id].isreplaced}
	            	,
	            	{/if}
	            {/if}
	            {literal}{href: 'custom_role_log.php?id='+id, innerHTML: 'View bundle log'}{/literal}
	        {/if}
        ];
        
        {literal}			
        var control = new SelectControl({menu: menu});
        control.attach('control_'+id);
        {/literal}
	</script>
	{sectionelse}
	<tr>
		<td colspan="12" align="center">{t region=$smarty.session.aws_region}No roles found in '%1' region{/t}</td>
	</tr>
	{/section}
	<tr>
		<td colspan="10" align="center">&nbsp;</td>
		<td class="ItemDelete" valign="top">&nbsp;</td>
	</tr>
	</tbody>
	</table>
	{include file="inc/table_footer.tpl" colspan=9}
{include file="inc/footer.tpl"}