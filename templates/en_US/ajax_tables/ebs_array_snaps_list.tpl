{if $error == ''}
	{section name=id loop=$snaps}
	<tr id='tr_{$smarty.section.id.iteration}'>
		<td class="Item" valign="top">array-snap-{$snaps[id].id}</td>
		<td class="Item" valign="top">{$snaps[id].status}</td>
		<td class="Item" valign="top">{$snaps[id].dtcreated}</td>
		<td class="Item" valign="top">{$snaps[id].description}</td>
		<td class="Item" valign="top" width="1"><a id="control_{$snaps[id].id}" href="javascript:void(0)">{t}Options{/t}</a></td>
	</tr>
	<script language="Javascript" type="text/javascript">
		var sid = '{$snaps[id].id}';
	    	
		var menu = [
			{if $snaps[id].status == 'Completed'}
    		{literal}{href: 'ebs_array_create.php?snapid='+sid, innerHTML: '{/literal}{t}Create new array based on this snapshot{/t}{literal}'},{/literal}            
    		{literal}{type: 'separator'}{/literal},
    		{/if}
            {literal}{href: 'ebs_arrays.php?task=snap_delete&snapshotId='+sid, innerHTML: '{/literal}{t}Delete snapshot{/t}{literal}'}{/literal}
		];
	       
		{literal}			
		var control = new SelectControl({menu: menu});
		control.attach('control_'+sid);
		{/literal}
	</script>
	{sectionelse}
	<tr>
		<td colspan="10" align="center">{t}No snapshots found{/t}</td>
	</tr>
	{/section}
{else}
	<tr>
	  <td colspan="5">{$error}</td>
	</tr>
{/if}
