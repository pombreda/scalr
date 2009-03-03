{include file="inc/header.tpl"}
{include file="inc/table_header.tpl"}
    {include file="inc/intable_header.tpl" header="Step 1 - Region information" color="Gray"}
	<tr>
		<td width="15%">Region:</td>
		<td colspan="6">
			<select name="region" id="region" style="vertical-align:middle;">
				{section name=id loop=$regions}
					<option {if $region == $regions[id]}selected{/if} value="{$regions[id]}">{$regions[id]}</option>
				{/section}
			</select>
		</td>
	</tr>
{include file="inc/intable_footer.tpl" color="Gray"}
{include file="inc/table_footer.tpl" button2=1 button2_name='Next'}
{include file="inc/footer.tpl"}