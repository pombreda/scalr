{include file="inc/header.tpl"}
	{include file="inc/table_header.tpl"}
		{include file="inc/intable_header.tpl" header="AMI information" color="Gray"}
    	<tr>
    		<td width="20%">Current AMI:</td>
    		<td>{$ami_id}</td>
    	</tr>
    	<tr>
    		<td width="20%">New AMI:</td>
    		<td><input type="text" name="new_ami_id" class="text" value="" /></td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
        <input type="hidden" name="id" value="{$id}" />
	{include file="inc/table_footer.tpl" button2=1 button2_name="Switch" cancel_btn=1}
{include file="inc/footer.tpl"}