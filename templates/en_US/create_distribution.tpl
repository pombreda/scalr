{include file="inc/header.tpl"}
{include file="inc/table_header.tpl"}
    {include file="inc/intable_header.tpl" header="Distribution information" color="Gray"}
	<tr>
		<td width="15%">S3 Bucket:</td>
		<td colspan="6">
			{$bucket_name}
			<input type="hidden" name="bucket_name" value="{$bucket_name}" />
		</td>
	</tr>
	<tr>
		<td width="15%">Domain name:</td>
		<td colspan="6"><input type="text" class="text" name="domainname" value="{$domainname}" style="vertical-align: middle;" />.<select name="zone" style="vertical-align: middle;">
			{section name=id loop=$zones}
				<option value="{$zones[id].zone}">{$zones[id].zone}</option>
			{/section}
		</select></td>
	</tr>
	<tr valign="top">
		<td width="15%">Comment:</td>
		<td colspan="6">
			<textarea class="text" cols="40" rows="4" name="comment"></textarea>
		</td>
	</tr>
{include file="inc/intable_footer.tpl" color="Gray"}
<input type="hidden" name="task" value="create_dist" />
<input type="hidden" name="confirm" value="1" />
{include file="inc/table_footer.tpl" button2=1 button2_name='Create distribution' cancel_btn=1}
{include file="inc/footer.tpl"}