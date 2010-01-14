{include file="inc/header.tpl"}
	{include file="inc/table_header.tpl"}
		{include file="inc/intable_header.tpl" header="Instance" color="Gray"}
        <tr>
            <td nowrap="nowrap" width="20%">Instance:</td>
            <td>
            	<select name="instanceid" class="text">
            		{section name=id loop=$rows}
						<option value="{$rows[id]->instancesSet->item->instanceId}">{$rows[id]->instancesSet->item->instanceId} ({$rows[id]->instancesSet->item->imageId})</option>
    				{/section}
            	</select>
            </td>
	    </tr>
		{include file="inc/intable_footer.tpl" color="Gray"}
		
		{include file="inc/intable_header.tpl" header="Credentials" color="Gray"}
        <tr valign="top">
            <td nowrap="nowrap" width="20%">Private key:</td>
            <td><textarea cols="45" rows="8" name="pk" class="text"></textarea></td>
	    </tr>
	    <tr valign="top">
            <td nowrap="nowrap" width="20%">Certificate:</td>
            <td><textarea cols="45" rows="8" name="cert" class="text"></textarea></td>
	    </tr>
		{include file="inc/intable_footer.tpl" color="Gray"}
	{include file="inc/table_footer.tpl" button2=1 button2_name="Next" cancel_btn=1}
{include file="inc/footer.tpl"}