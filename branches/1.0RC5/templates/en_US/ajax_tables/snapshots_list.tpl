{if $error == ''}
	{section name=id loop=$snaps}
	<tr id='tr_{$smarty.section.id.iteration}'>
		<td class="Item" valign="top">{$snaps[id]->snapshotId}</td>
		<td class="Item" valign="top"><a href="ebs_manage.php?view=volumes&volume_id={$snaps[id]->volumeId}">{$snaps[id]->volumeId}</a></td>
		<td class="Item" valign="top">{$snaps[id]->status|@ucfirst}</td>
		<td class="Item" valign="top">{$snaps[id]->startTime}</td>
			<td class="Item" valign="top" style="width:130px;">
				<div style="width:auto;height:12px;font-size:1px;margin-left:-4px;padding:2px;" align="center">
				    <div style="float:right;width:130px;padding-top:2px;">
	    			    <div style="float:left;width:4px;vertical-align:middle;background-image:url(images/hdd/begin-{$snaps[id]->bar_begin}.png); background-repeat:no-repeat;"><img style="float:left;" src="images/hdd/begin-{$snaps[id]->bar_begin}.png" alt="{t}Bar Begin{/t}" /></div>
	    				<div title="{$snaps[id]->progress}%" style="float:left;width:{$snaps[id]->used_percent_width}px;height:9px; background-image:url(images/hdd/fill.png); background-repeat:repeat-x;font-size:9px;line-height:9px;">{$snaps[id]->progress}%</div>
	    				{if $snaps[id]->free > 0}<div title="{$snaps[id]->free}%" style="float:left; height:9px;width:{$snaps[id]->free_percent_width}px; background-image:url(images/hdd/empty.png); background-repeat:repeat-x;">&nbsp;</div>{/if}
	    				<div style="float:left;background-image:url(images/hdd/end-{$snaps[id]->bar_end}.png); background-repeat:no-repeat;"><img src="images/hdd/end-{$snaps[id]->bar_end}.png" style="float:left;" alt="{t}Bar End{/t}" /></div>
					</div>
				</div>
				<div style="clear:both;font-size:1px;"></div>
			</td>
			<td class="Item" valign="top">{$snaps[id]->comment}</td>
		<td class="Item" valign="top" width="1">{if !$snaps[id]->is_array_snapshot}<a id="control_{$snaps[id]->snapshotId}" href="javascript:void(0)">{t}Options{/t}</a>{/if}</td>
	</tr>
	{if !$snaps[id]->is_array_snapshot}
	<script language="Javascript" type="text/javascript">
		var sid = '{$snaps[id]->snapshotId}';
	    	
		var menu = [
    		{literal}{href: 'ebs_manage.php?task=create_volume&snapid='+sid, innerHTML: '{/literal}{t}Create new volume based on this snapshot{/t}{literal}'},{/literal}            
    		{literal}{type: 'separator'}{/literal},
            {literal}{href: 'ebs_manage.php?task=snap_delete&snapshotId='+sid, innerHTML: '{/literal}{t}Delete snapshot{/t}{literal}'}{/literal}
		];
	       
		{literal}			
		var control = new SelectControl({menu: menu});
		control.attach('control_'+sid);
		{/literal}
	</script>
	{/if}
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
