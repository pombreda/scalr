{include file="inc/header.tpl"}
	{literal}
	<script language="Javascript">

	function SetPlatform(platform)
	{
		document.location = 'shared_roles.php?platform='+platform;
	}

	function SetRegion(region)
	{
		document.location = 'shared_roles.php?platform='+Ext.get('platform').dom.value+'&region='+region;
	}
	
	</script>
	{/literal}
	{include file="inc/table_header.tpl"}
	&nbsp;&nbsp;&nbsp;Platform: <select id="platform" name="platform" onChange="SetPlatform(this.value)" class="text" style="vertical-align:middle;">
		{section name=id loop=$platforms}
			<option {if $platform == $platforms[id]}selected="selected"{/if} value="{$platforms[id]}">{$platforms[id]}</option>
		{/section}
	</select>
	{if $platform == 'ec2' || $platform == 'rds'}
	<select name="region" id="region" class="text" onChange="SetRegion(this.value)" style="vertical-align:middle;">
		{section name=id loop=$aws_regions}
			<option {if $region == $aws_regions[id]}selected="selected"{/if} value="{$aws_regions[id]}">{$aws_regions[id]}</option>
		{/section}
	</select>
	{/if}
	{include file="inc/table_footer.tpl" disable_footer_line=1}

	{include file="inc/table_header.tpl"}
	<table cellpadding="4" cellspacing="0" border="1">
		<tr>
			<td style="width:190px;">&nbsp;</td>
			{foreach key=name item=prefix from=$os}
				<td align="center" style="width:250px;">{$name} ({$prefix})</td>
			{/foreach}
		</tr>
		{section name=id loop=$roles}
			<tr>
				<td>{$roles[id]}</td>
				{foreach key=name item=prefix from=$os}
				{assign var=nname value=$roles[id]$prefix}
				<td style="text-align:center;" align="center">
					<input type="text" class="text" size="15" name="{$nname}" value="{$images[$nname]}" />
				</td>
				{/foreach}
			</tr>
			<tr>
				<td colspan="20">&nbsp;</td>
			</tr>
		{/section}
	</table>
	{include file="inc/table_footer.tpl" edit_page=1 cancel_btn="1"}
{include file="inc/footer.tpl"}