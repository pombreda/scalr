{include file="inc/header.tpl"}
<link rel="stylesheet" href="css/SelectControl.css" type="text/css" />
<script type="text/javascript" src="js/class.SelectControl.js"></script>
<script type="text/javascript" src="js/class.TableLoader.js"></script>
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
				<th width="1"></th>
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
			<td class="Item" valign="top" align="center">
			{if $vols[id]->roleName}
				<a href="roles_view.php?farmid={$vols[id]->farmId}">{$vols[id]->roleName}</a>
			{else}
				<img src="/images/false.gif">
			{/if}
			</td>
			<td class="Item" valign="top">{$vols[id]->volumeId}</td>
			<td class="Item" valign="top">{$vols[id]->size} GB</td>
			<td class="Item" valign="top"><a href="javascript:void(0);" onclick="FilterSnapshots('{$vols[id]->snapshotId}', 0);">{$vols[id]->snapshotId}</a></td>
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
	            {literal}{href: 'javascript:FilterSnapshots(0, "'+vid+'");', innerHTML: 'View snapshots'},{/literal}
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
	
	<script language="Javascript">
	{literal}	
	var tb = new TableLoader();
	
	Event.observe(window, 'load', function(){
		tb.Load('table_body_list','_cmd=get_snapshots_list');
		
		$('table_title_text').innerHTML = "Snapshots";
	});
	
	function ReloadPage() {
		tb.Load('table_body_list','_cmd=get_snapshots_list');
		
		$('table_title_text').innerHTML = "Snapshots";
	};
	
	function FilterSnapshots(snapid, volumeid)
	{
		if (volumeid != 0)
		{
			$('table_title_text').innerHTML = "Snapshots for: "+volumeid;
		}
		else
		{
			$('table_title_text').innerHTML = "Snapshots";
		}
		
		tb.Load('table_body_list','_cmd=get_snapshots_list&snapid='+snapid+"&volumeid="+volumeid);
	}
	
	</script>
	{/literal}
    {include filter=$snaps_filter paging=$snaps_paging file="inc/table_header.tpl"}
    <table class="Webta_Items Webta_Items_Multiple_Tables No_Resize" rules="groups" frame="box" cellpadding="4" id="Webta_Items_2" no_resize="1">
	<thead>
		<tr>
			<th>Snapshot ID</th>
			<th>Created on</th>
			<th>Status</th>
			<th>Local start time</th>
			<th>Completed</th>
			<th>Comment</th>
			<th width="1"></th>
		</tr>
	</thead>
	<tbody id="table_body_list">
		<tr id="table_loader">
			<td colspan="30" align="center">
				<img style="vertical-align:middle;" src="/images/snake-loader.gif"> Loading snapshots list. Please wait...
			</td>
		</tr>
	</tbody>
	</table>
	{include file="inc/table_footer.tpl" colspan=9 disable_footer_line=1}
{include file="inc/footer.tpl"}