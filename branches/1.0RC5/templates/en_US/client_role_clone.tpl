{include file="inc/header.tpl"}
	{include file="inc/table_header.tpl"}
		{include file="inc/intable_header.tpl" header="Role information" color="Gray"}
    	<tr>
    		<td width="20%">Role name:</td>
    		<td><input type="text" class="text" name="name" value="{$name}" /></td>
    	</tr>
    	<tr>
    		<td width="20%">Current region:</td>
    		<td>{$current_region}</td>
    	</tr>
    	<tr>
    		<td width="20%">New region:</td>
    		<td>{$new_region}</td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
        <input type="hidden" name="id" value="{$id}" />
	{include file="inc/table_footer.tpl" button2=1 button2_name="Clone" cancel_btn=1}
{include file="inc/footer.tpl"}