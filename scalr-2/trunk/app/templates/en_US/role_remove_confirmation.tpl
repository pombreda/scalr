{include file="inc/header.tpl"}
{include file="inc/table_header.tpl"}
	{foreach key=ami_id item=item from=$roles}
	{assign var=role_name value=$item.name}
	{assign var=ami_id value=$item.ami_id}
	
    {include file="inc/intable_header.tpl" header="$role_name" color="Gray"}
	<tr>
		<td colspan="6">
			<input type="checkbox" name="remove_image[{$item.id}]" value='1' checked=checked />
			Remove image from cloud provider
		</td>
	</tr>
	<input type="hidden" name="id[]" value="{$item.id}">
	{include file="inc/intable_footer.tpl" color="Gray"}
	{/foreach}
	<input type="hidden" name="confirm" value="1">
	<input type="hidden" name="action" value="delete">
	<input type="hidden" name="actionsubmit" value="1">
{include file="inc/table_footer.tpl" button2=1 button2_name='Next'}
{include file="inc/footer.tpl"}