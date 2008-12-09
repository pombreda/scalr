{include file="inc/header.tpl"}
{include file="inc/table_header.tpl"}
    {include file="inc/intable_header.tpl" header="Farm information" color="Gray"}
    <tr>
		<td width="20%"><input checked type="radio" name="createtype" value="1"> Run on existing farm:</td>
		<td colspan="6">
		  <select name="farmid" class="text" style="vertical-align:middle;">
		  {section name=id loop=$farms}
		      <option value="{$farms[id].id}">{$farms[id].name}</option>
		  {/section}
		  </select>
		</td>
	</tr>
	<tr>
		<td width="20%"><input type="radio" name="createtype" value="2"> Create new farm</td>
		<td colspan="6">
		</td>
	</tr>
    {include file="inc/intable_footer.tpl" color="Gray"}
{include file="inc/table_footer.tpl" button2=1 button2_name="Next"}
{include file="inc/footer.tpl"}