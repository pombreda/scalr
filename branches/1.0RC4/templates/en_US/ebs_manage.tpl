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
	    {include file="inc/table_header.tpl" show_region_filter=1 show_region_filter_title="Volumes in"}
	    <table class="Webta_Items Webta_Items_Multiple_Tables No_Resize" rules="groups" frame="box" cellpadding="4" id="Webta_Items_1" no_resize="1">
		<thead>
			<tr>
				<th>{t}Used by{/t}</th>
				<th>{t}Volume ID{/t}</th>
				<th>{t}Size{/t} ({t}GB{/t})</th>
				<th>{t}Snapshot ID{/t}</th>
				<th>{t}Placement{/t}</th>
				<th>{t}Status{/t}</th>
				<th>{t}Instance ID{/t}</th>
				<th>{t}Device{/t}</th>
				<th>{t}Auto-snaphots{/t}</th>
				<th width="1">{t}Options{/t}</th>
			</tr>
		</thead>
		<tbody>
		{section name=id loop=$vols}
		<tr id='tr_{$smarty.section.id.iteration}'>
			<td class="Item" valign="top">
			{if $vols[id]->Scalr->FarmID && !$vols[id]->Scalr->ArrayID}
				{t}Farm{/t}: <a href="farms_view.php?id={$vols[id]->Scalr->FarmID}" title="Farm {$vols[id]->Scalr->FarmName}">{$vols[id]->Scalr->FarmName}</a>
				{if $vols[id]->Scalr->RoleName}
					&nbsp;&rarr;&nbsp;<a href="roles_view.php?farmid={$vols[id]->Scalr->FarmID}" title="Role {$vols[id]->Scalr->RoleName}">{$vols[id]->Scalr->RoleName}</a>
				{/if}
			{elseif $vols[id]->Scalr && $vols[id]->Scalr->ArrayID}
				{t}Array{/t}: <a href="ebs_arrays.php?id={$vols[id]->Scalr->ArrayID}">{$vols[id]->Scalr->ArrayName}</a>
				&nbsp;&rarr;&nbsp;{t}Part{/t} #{$vols[id]->Scalr->ArrayPartNo}
			{else}
				<img src="/images/false.gif" />
			{/if}
			</td>
			<td class="Item" valign="top">{$vols[id]->volumeId}</td>
			<td class="Item" valign="top">{$vols[id]->size}</td>
			<td class="Item" valign="top"><a href="javascript:void(0);" onclick="FilterSnapshots('{$vols[id]->snapshotId}', 0);">{$vols[id]->snapshotId}</a></td>
			<td class="Item" valign="top">{$vols[id]->availabilityZone}</td>
			<td class="Item" valign="top">{$vols[id]->status|@ucfirst}{if $vols[id]->attachmentSet->status} / {$vols[id]->attachmentSet->status|@ucfirst}{/if}</td>
			<td class="Item" valign="top">
				{if $vols[id]->Scalr && $vols[id]->Scalr->FarmID}
					<a href="instances_view.php?farmid={$vols[id]->Scalr->FarmID}&iid={$vols[id]->attachmentSet->instanceId}">{$vols[id]->attachmentSet->instanceId}</a>
				{else}
					{$vols[id]->attachmentSet->instanceId}
				{/if}
			</td>
			<td class="Item" valign="top">{$vols[id]->attachmentSet->device}</td>
			<td class="Item" valign="top" align="center">
				{if $vols[id]->Scalr->AutoSnapshoting}
					<img alt="Enabled" src="/images/true.gif">
				{else}
					<img alt="Disabled" src="/images/false.gif">
				{/if}
			</td>
			<td class="Item" valign="top">
				{if !$vols[id]->Scalr->ArrayID}
					<a id="control_{$vols[id]->volumeId}" href="javascript:void(0)">{t}Options{/t}</a>
				{/if}
			</td>
		</tr>
		{if !$vols[id]->Scalr->ArrayID}
		<script language="Javascript" type="text/javascript">
	    	var vid = '{$vols[id]->volumeId}';
			var region = '{$smarty.session.aws_region}';
	    	
	    	var menu = [
	            
	            {if $vols[id]->attachmentSet->instanceId}
	            	{literal}{href: 'ebs_manage.php?task=detach&volumeId='+vid, innerHTML: 'Detach'},{/literal}
	            {else}
	            	{literal}{href: 'ebs_manage.php?task=attach&volumeId='+vid, innerHTML: 'Attach'},{/literal}
	            {/if}

	            {literal}{type: 'separator'}{/literal},
            	{literal}{href: 'ebs_autosnaps.php?task=settings&volumeId='+vid+"&region="+region, innerHTML: 'Auto-snapshot settings'},{/literal}
	            
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
		{/if}
		{sectionelse}
		<tr>
			<td colspan="12" align="center">{t}No volumes found{/t}</td>
		</tr>
		{/section}
		<tr>
			<td colspan="12" align="center">&nbsp;</td>
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

		{/literal}
		$('table_title_text').innerHTML = "Snapshots in {$smarty.session.aws_region} region";
		{literal}
	});
	
	function ReloadPage() {
		tb.Load('table_body_list','_cmd=get_snapshots_list');
		
		{/literal}
		$('table_title_text').innerHTML = "Snapshots in {$smarty.session.aws_region} region";
		{literal}
	};
	
	function FilterSnapshots(snapid, volumeid)
	{
		if (volumeid != 0)
		{
			$('table_title_text').innerHTML = "Snapshots for: "+volumeid;
		}
		else
		{
			{/literal}
			$('table_title_text').innerHTML = "Snapshots in {$smarty.session.aws_region} region";
			{literal}
		}
		
		tb.Load('table_body_list','_cmd=get_snapshots_list&snapid='+snapid+"&volumeid="+volumeid);
	}
	
	</script>
	{/literal}
    {include filter=$snaps_filter paging=$snaps_paging file="inc/table_header.tpl"}
    <table class="Webta_Items Webta_Items_Multiple_Tables No_Resize" rules="groups" frame="box" cellpadding="4" id="Webta_Items_2" no_resize="1">
	<thead>
		<tr>
			<th>{t}Snapshot ID{/t}</th>
			<th>{t}Created on{/t}</th>
			<th>{t}Status{/t}</th>
			<th>{t}Local start time{/t}</th>
			<th>{t}Completed{/t}</th>
			<th>{t}Comment{/t}</th>
			<th width="1"></th>
		</tr>
	</thead>
	<tbody id="table_body_list">
		<tr id="table_loader">
			<td colspan="30" align="center">
				<img style="vertical-align:middle;" src="/images/snake-loader.gif"> {t}Loading snapshots list. Please wait...{/t}
			</td>
		</tr>
	</tbody>
	</table>
	{include file="inc/table_footer.tpl" colspan=9 disable_footer_line=1}
{include file="inc/footer.tpl"}