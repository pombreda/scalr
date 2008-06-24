{include file="inc/header.tpl"}
{include file="inc/table_header.tpl"}
    {include file="inc/intable_header.tpl" header="Farm information" color="Gray"}
    <tr>
		<td width="15%">Farm:</td>
		<td colspan="6">
		  <select name="farmid" class="text">
		  {section name=id loop=$farms}
		      <option value="{$farms[id].id}">{$farms[id].name}</option>
		  {/section}
		  </select>
		</td>
	</tr>
    {include file="inc/intable_footer.tpl" color="Gray"}
{include file="inc/table_footer.tpl" edit_page=1}
{include file="inc/footer.tpl"}