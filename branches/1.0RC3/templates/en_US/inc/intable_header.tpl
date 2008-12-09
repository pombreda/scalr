
<div {if $intableid}id="{$intableid}"{/if} {if $intable_classname}class="{$intable_classname}"{/if} style="display:{$visible};padding: {if $intablepadding}{$intablepadding}{else}7{/if}px;">
	<table border="0" cellpadding="0" cellspacing="0" width="100%">
	{if !$noheaderline}
	<tr>
		<td width="7"><div class="TableHeaderLeft_{$color}"></div></td>
		<td>
		<a name="{$ancor_name}"></a>
		<div id="webta_table_header{$header_id}" {if $header}onclick="webtacp.collapseSettings('Webta_InnerTable_{$header}', this);"{/if} class="SettingsHeader_{$color}">
			{if $header}<img src="images/sort{if $section_closed}d{else}a{/if}.gif" valign="middle" /> <strong>{$header}</strong>{/if}
		</div>
		</td>
		<td width="7"><div class="TableHeaderRight_{$color}"></div></td>
	</tr>
	{/if}
	<tr>
		<td width="7" class="TableHeaderCenter_{$color}"></td>
		<td class="Inner_{$color}">
			<table width="100%" cellspacing="0" cellpadding="2" id="Webta_InnerTable_{$header}" {if $section_closed}style="display: none;"{/if}>
			<tr>
				<td width="{if $intable_first_column_width}{$intable_first_column_width}{else}20%{/if}"></td>
				<td colspan="{if $intable_colspan}{$intable_colspan}{else}1{/if}" width="80%" style="height:15px;"></td>
			</tr>
			