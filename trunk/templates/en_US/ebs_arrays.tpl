{include file="inc/header.tpl"}
<link rel="stylesheet" href="css/SelectControl.css" type="text/css" />
<script type="text/javascript" src="js/class.SelectControl.js"></script>
<script type="text/javascript" src="js/class.TableLoader.js"></script>
	<br>
	{include file="inc/table_header.tpl" nofilter=1}
    	{include file="inc/intable_header.tpl" header="Actions" color="Gray"}
    	<tr>
    	   <td colspan="2"><img src="/images/add.png" style="vertical-align:middle;">&nbsp;<a href="ebs_array_create.php">Create new array</a></td>
    	</tr>
    	{include file="inc/intable_footer.tpl" color="Gray"}
	{include file="inc/table_footer.tpl" disable_footer_line=1}
	<br>
    {include file="inc/table_header.tpl" table_header_text="EBS Arrays" nofilter=1}
    <table class="Webta_Items Webta_Items_Multiple_Tables No_Resize" rules="groups" frame="box" cellpadding="4" id="Webta_Items_1" no_resize="1">
	<thead>
		<tr>
			<th>{t}Name{/t}</th>
			<th>{t}Size{/t}</th>
			<th>{t}Status{/t}</th>
			<th>{t}Placement{/t}</th>
			<th>{t}EBS Volumes{/t}</th>
			<th>{t}Assigned to{/t}</th>
			<th>{t}Auto-snaphots{/t}</th>
			<th width="1">{t}Options{/t}</th>
		</tr>
	</thead>
	<tbody>
	{section name=id loop=$rows}
	<tr id='tr_{$smarty.section.id.iteration}'>
		<td class="Item" valign="top">{$rows[id].name}</td>
		<td class="Item" valign="top">{if $rows[id].size}{$rows[id].size} GB{else}{t}Unknown{/t}{/if}</td>
		<td class="Item" valign="top">{$rows[id].status}</td>
		<td class="Item" valign="top">{$rows[id].avail_zone}</td>
		<td class="Item" valign="top">{$rows[id].volumes} [<a href="ebs_manage.php?arrayid={$rows[id].id}">{t}View{/t}</a>]</td>
		<td class="Item" valign="top">
			{if $rows[id].instance_id}
				<a href="instances_view.php?farmid={$rows[id].farmid}&iid={$rows[id].instance_id}">{$rows[id].instance_id}</a>
			{else}
				<img src="/images/false.gif" />
			{/if}
		</td>
		<td class="Item" valign="top">
			{if $rows[id].autosnapshoting}
				<img alt="Enabled" src="/images/true.gif">
			{else}
				<img alt="Disabled" src="/images/false.gif">
			{/if}
		</td>
		<td class="Item" valign="top" width="1%"><a id="control_{$rows[id].id}" href="javascript:void(0)">{t}Options{/t}</a></td>
	</tr>
	<script language="Javascript" type="text/javascript">
    	var id = '{$rows[id].id}';
    	var name = '{$rows[id].name}';
		var region = '{$rows[id].region}';
    	
    	var menu = [
    		{if $rows[id].status != 'Corrupt'}
    		{if $rows[id].instance_id}
    			{literal}{href: 'ebs_array_detach.php?array_id='+id, innerHTML: 'Detach'},{/literal}
    		{else}
    			{literal}{href: 'ebs_array_attach.php?array_id='+id, innerHTML: 'Attach'},{/literal}

    			//{literal}{type: 'separator'}{/literal},
    			//{literal}{href: 'ebs_array_resize.php?array_id='+id, innerHTML: 'Resize'},{/literal}
    		{/if}
    	    {literal}{type: 'separator'}{/literal},
            {literal}{href: 'ebs_arrays.php?task=snap_create&array_id='+id, innerHTML: 'Create snapshot'},{/literal}
            {literal}{href: 'javascript:FilterSnapshots(0, "'+id+'", "'+name+'");', innerHTML: 'View snapshots'},{/literal}
            {literal}{type: 'separator'}{/literal},
        	{literal}{href: 'ebs_autosnaps.php?task=settings&array_id='+id+"&region="+region, innerHTML: 'Auto-snapshot settings'},{/literal}
            {literal}{type: 'separator'}{/literal},
            {else}
            {literal}{href: 'ebs_arrays.php?task=recreate&array_id='+id, innerHTML: 'Recreate array'},{/literal}
            {/if}
            {literal}{href: 'ebs_arrays.php?task=delete&array_id='+id, innerHTML: 'Delete array'}{/literal}
        ];
        
        {literal}			
        var control = new SelectControl({menu: menu});
        control.attach('control_'+id);
        {/literal}
	</script>
	{sectionelse}
	<tr>
		<td colspan="11" align="center">No EBS array found</td>
	</tr>
	{/section}
	<tr>
		<td colspan="11" align="center">&nbsp;</td>
	</tr>
	</tbody>
	</table>
	{include file="inc/table_footer.tpl" colspan=9 page_data_options_add_text="Create new array" page_data_options_add_querystring="?task=create_array"}
	
	<script language="Javascript">
	{literal}	
	var tb = new TableLoader();
	
	Event.observe(window, 'load', function(){
		tb.Load('table_body_list','_cmd=get_array_snapshots');
		
		$('table_title_text').innerHTML = "Snapshots";
	});
	
	function ReloadPage() {
		tb.Load('table_body_list','_cmd=get_array_snapshots');
		
		$('table_title_text').innerHTML = "Snapshots";
	};
	
	function FilterSnapshots(snapid, arrayid, arrayname)
	{
		if (arrayid != 0)
		{
			$('table_title_text').innerHTML = "Snapshots for: "+arrayname;
		}
		else
		{
			$('table_title_text').innerHTML = "Snapshots";
		}
		
		tb.Load('table_body_list','_cmd=get_array_snapshots&snapid='+snapid+"&arrayid="+arrayid);
	}
	
	</script>
	{/literal}
    {include filter=$snaps_filter paging=$snaps_paging file="inc/table_header.tpl"}
    <table class="Webta_Items Webta_Items_Multiple_Tables No_Resize" rules="groups" frame="box" cellpadding="4" id="Webta_Items_2" no_resize="1">
	<thead>
		<tr>
			<th>Snapshot ID</th>
			<th>Status</th>
			<th>Created at</th>
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