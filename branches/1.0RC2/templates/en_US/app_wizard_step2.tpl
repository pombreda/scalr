{include file="inc/header.tpl"}
{include file="inc/table_header.tpl"}
    {include file="inc/intable_header.tpl" header="Step 2 - Farm information" color="Gray"}
	<tr valign="top">
		<td width="15%">New application will use the following roles:</td>
		<td colspan="6">
		  <table>
		      {section name=id loop=$roles}
		      <tr>
		          <td><input type="checkbox" {if @in_array($roles[id].ami_id, $amis)}checked{/if} name="amis[]" value="{$roles[id].ami_id}"></td>
		          <td>{$roles[id].name} ({$roles[id].roletype})</td>
		      </tr>
		      {/section}
		  </table>
		</td>
	</tr>
{include file="inc/intable_footer.tpl" color="Gray"}
<input type="hidden" name="step" value="3">
{include file="inc/table_footer.tpl" button2=1 button2_name='Next'}
{include file="inc/footer.tpl"}