{include file="inc/header.tpl"}
<link rel="stylesheet" href="css/SelectControl.css" type="text/css" />
<script type="text/javascript" src="js/class.SelectControl.js"></script>
    {include file="inc/table_header.tpl" table_header_text="S3 buckets" nofilter='1'}
    <table class="Webta_Items" rules="groups" frame="box" cellpadding="4" id="Webta_Items">
	<thead>
		<tr>
			<th nowrap="nowrap">Bucket name</th>
			<th>Cloudfront ID</th>
			<th nowrap="nowrap">Cloudfront URL</th>
			<th nowrap="nowrap">CNAME</th>
			<th>Status</th>
			<th>Enabled</th>
			<td width="1" nowrap></td>
		</tr>
	</thead>
	<tbody>
	{section name=id loop=$buckets}
	<tr id='tr_{$smarty.section.id.iteration}'>
		<td class="Item"  valign="top">{$buckets[id].name}</td>
		<td class="Item"  valign="top">{$buckets[id].cfid}</td>
		<td class="Item"  valign="top">{$buckets[id].cfurl}</td>
		<td class="Item"  valign="top">{$buckets[id].cname}</td>
		<td class="Item"  valign="top">{$buckets[id].status}</td>
		<td class="Item"  valign="top" align="center">{if $buckets[id].enabled}<img src="/images/{$buckets[id].enabled}.gif">{/if}</td>
		<td class="ItemDelete" width="1" valign="top">
			<a id="control_{$buckets[id].name}" href="javascript:void(0)">Options</a>
		</td>
	</tr>
	<script language="Javascript" type="text/javascript">
    	var name = '{$buckets[id].name}';
    	var did = '{$buckets[id].cfid}';
    	
    	var menu = [
    	    {if !$buckets[id].cfid}
				{literal}{href: 's3browser.php?task=create_dist&name='+name, innerHTML: 'Create distribution'},{/literal}
			{else}
				{if $buckets[id].enabled == 'true'}
					{literal}{href: 's3browser.php?task=disable_dist&id='+did, innerHTML: 'Disable distribution'},{/literal}
				{else}
					{literal}{href: 's3browser.php?task=enable_dist&id='+did, innerHTML: 'Enable distribution'},{/literal}
				{/if}
				{literal}{href: 's3browser.php?task=delete_dist&id='+did, innerHTML: 'Remove distribution'},{/literal}
			{/if}
			{literal}{type: 'separator'},{/literal}
            {literal}{href: 's3browser.php?task=delete_bucket&name='+name, innerHTML: 'Delete bucket'}{/literal}
        ];
        
        {literal}			
        var control = new SelectControl({menu: menu});
        control.attach('control_'+name);
        {/literal}
	
	</script>
	{sectionelse}
	<tr>
		<td colspan="7" align="center">No buckets found</td>
	</tr>
	{/section}
	<tr>
		<td colspan="6" align="center">&nbsp;</td>
		<td class="ItemDelete" valign="top">&nbsp;</td>
	</tr>
	</tbody>
	</table>
	{include file="inc/table_footer.tpl" colspan=9 disable_footer_line=1}
{include file="inc/footer.tpl"}