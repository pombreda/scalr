{include file="inc/header.tpl"}
<link rel="stylesheet" href="css/SelectControl.css" type="text/css" />
<script type="text/javascript" src="js/class.SelectControl.js"></script>
	{if !$view || $view == 'volumes'}
		<br>
		{include file="inc/table_header.tpl" nofilter=1}
	    	{include file="inc/intable_header.tpl" header="Actions" color="Gray"}
	    	<tr>
	    	   <td colspan="2"><img src="/images/add.png" style="vertical-align:middle;">&nbsp;<a href="ebs_manage.php?task=create_volume">Create new volume</a></td>
	    	</tr>
	    	{include file="inc/intable_footer.tpl" color="Gray"}
		{include file="inc/table_footer.tpl" disable_footer_line=1}
		<br>
	    {include file="inc/table_header.tpl" table_header_text="Volumes" nofilter=1}
	    <table class="Webta_Items Webta_Items_Multiple_Tables No_Resize" rules="groups" frame="box" cellpadding="4" id="Webta_Items_1" no_resize="1">
		<thead>
			<tr>
				<th>Farm name</th>
				<th>Role name</th>
				<th>Volume ID</th>
				<th>Size</th>
				<th>Snapshot ID</th>
				<th>Placement</th>
				<th>Status</th>
				<th>Instance ID</th>
				<th>Device</th>
				<th>Attachment status</th>
				<th width="1">Options</th>
			</tr>
		</thead>
		<tbody>
		{section name=id loop=$vols}
		<tr id='tr_{$smarty.section.id.iteration}'>
			<td class="Item" valign="top">
			{if $vols[id]->farmId}
				<a href="farms_view.php?id={$vols[id]->farmId}">{$vols[id]->farmName}</a>
			{else}
				Not used by Scalr
			{/if}
			</td>
			<td class="Item" valign="top">
			{if $rows[id]->dbInfo}
				<a href="roles_view.php?farmid={$vols[id]->farmId}">{$vols[id]->dbInfo.role_name}</a>
			{elseif $rows[id]->dbInstance}
				<a href="roles_view.php?farmid={$vols[id]->farmId}">{$vols[id]->dbInstance.role_name}</a>
			{else}
				Not used by Scalr
			{/if}
			</td>
			<td class="Item" valign="top">{$vols[id]->volumeId}</td>
			<td class="Item" valign="top">{$vols[id]->size} GB</td>
			<td class="Item" valign="top"><a href="ebs_manage.php?view=snapshots&snap_id={$vols[id]->snapshotId}">{$vols[id]->snapshotId}</a></td>
			<td class="Item" valign="top">{$vols[id]->availabilityZone}</td>
			<td class="Item" valign="top">{$vols[id]->status|@ucfirst}</td>
			<td class="Item" valign="top">
				{if $vols[id]->farmId}
					<a href="instances_view.php?farmid={$vols[id]->farmId}&iid={$vols[id]->attachmentSet->instanceId}">{$vols[id]->attachmentSet->instanceId}</a>
				{else}
					{$vols[id]->attachmentSet->instanceId}
				{/if}
			</td>
			<td class="Item" valign="top">{$vols[id]->attachmentSet->device}</td>
			<td class="Item" valign="top">{$vols[id]->attachmentSet->status|@ucfirst}</td>
			<td class="Item" valign="top"><a id="control_{$vols[id]->volumeId}" href="javascript:void(0)">Options</a></td>
		</tr>
		<script language="Javascript" type="text/javascript">
	    	var vid = '{$vols[id]->volumeId}';
	    	
	    	var menu = [
	            
	            {if $vols[id]->attachmentSet->instanceId}
	            	{literal}{href: 'ebs_manage.php?task=detach&volumeId='+vid, innerHTML: 'Detach'},{/literal}
	            {else}
	            	{literal}{href: 'ebs_manage.php?task=attach&volumeId='+vid, innerHTML: 'Attach'},{/literal}
	            {/if}
	            
	            {literal}{type: 'separator'}{/literal},
	            {literal}{href: 'ebs_manage.php?task=snap_create&volumeId='+vid, innerHTML: 'Create snapshot'},{/literal}
	            {literal}{href: 'ebs_manage.php?view=snapshots&volumeId='+vid, innerHTML: 'View snapshots'},{/literal}
	            {literal}{type: 'separator'}{/literal},
	            {literal}{href: 'ebs_manage.php?task=delete_volume&volumeId='+vid, innerHTML: 'Delete volume'}{/literal}
	        ];
	       
	        {literal}			
	        var control = new SelectControl({menu: menu});
	        control.attach('control_'+vid);
	        {/literal}
		</script>
		{sectionelse}
		<tr>
			<td colspan="11" align="center">No volumes found</td>
		</tr>
		{/section}
		<tr>
			<td colspan="11" align="center">&nbsp;</td>
		</tr>
		</tbody>
		</table>
		{include file="inc/table_footer.tpl" colspan=9 page_data_options_add_text="Create new volume" page_data_options_add_querystring="?task=create_volume"}
	{/if}
	
	{if !$view || $view == 'snapshots'}
		{if $snaps_header}
			{include file="inc/table_header.tpl" table_header_text=$snaps_header nofilter=1}
		{else}
			{include file="inc/table_header.tpl" table_header_text="Snapshots" nofilter=1}
		{/if}
	    <table class="Webta_Items Webta_Items_Multiple_Tables No_Resize" rules="groups" frame="box" cellpadding="4" id="Webta_Items_2" no_resize="1">
		<thead>
			<tr>
				<th>Snapshot ID</th>
				<th>Volume ID</th>
				<th>Status</th>
				<th>Local start time</th>
				<th>Completed</th>
			</tr>
		</thead>
		<tbody>
		{section name=id loop=$snaps}
		<tr id='tr_{$smarty.section.id.iteration}'>
			<td class="Item" valign="top">{$snaps[id]->snapshotId}</td>
			<td class="Item" valign="top"><a href="ebs_manage.php?view=volumes&volume_id={$vols[id]->volumeId}">{$snaps[id]->volumeId}</a></td>
			<td class="Item" valign="top">{$snaps[id]->status|@ucfirst}</td>
			<td class="Item" valign="top">{$snaps[id]->startTime}</td>
			<td class="Item" valign="top" style="width:200px;">
				<div style="width:auto;height:12px;font-size:1px;margin-left:-4px;padding:2px;" align="center">
				    <div style="float:right;width:200px;padding-top:2px;">
	    			    <div style="float:left;width:4px;vertical-align:middle;background-image:url(images/hdd/begin-{$snaps[id]->bar_begin}.gif); background-repeat:no-repeat;"><img style="float:left;" src="images/hdd/begin-{$snaps[id]->bar_begin}.gif" alt="Bar Begin" /></div>
	    				<div title="{$snaps[id]->progress}%" style="float:left;width:{$snaps[id]->used_percent_width}px;height:9px; background-image:url(images/hdd/fill.gif); background-repeat:repeat-x;font-size:9px;line-height:9px;">{$snaps[id]->progress}%</div>
	    				{if $snaps[id]->free > 0}<div title="{$snaps[id]->free}%" style="float:left; height:9px;width:{$snaps[id]->free_percent_width}px; background-image:url(images/hdd/empty.gif); background-repeat:repeat-x;">&nbsp;</div>{/if}
	    				<div style="float:left;background-image:url(images/hdd/end-{$snaps[id]->bar_end}.gif); background-repeat:no-repeat;"><img src="images/hdd/end-{$snaps[id]->bar_end}.gif" style="float:left;" alt="Bar End" /></div>
					</div>
				</div>
				<div style="clear:both;font-size:1px;"></div>
			</td>
		</tr>
		{sectionelse}
		<tr>
			<td colspan="5" align="center">No snapshots found</td>
		</tr>
		{/section}
		<tr>
			<td colspan="5" align="center">&nbsp;</td>
		</tr>
		</tbody>
		</table>
		{include file="inc/table_footer.tpl" colspan=9 disable_footer_line=1}
	{/if}	
{include file="inc/footer.tpl"}