{include file="inc/header.tpl"}
	{include file="inc/table_header.tpl"}
		{include file="inc/intable_header.tpl" header="Role information" color="Gray"}
    	<tr>
    		<td width="20%">{t}Current AMI{/t}:</td>
    		<td>{$ami_id}<input type="hidden" name="ami_id" value="{$ami_id}"></td>
    	</tr>
    	<tr>
    		<td width="20%">{t}New AMI{/t}:</td>
    		<td>
    			<select name="new_ami_id" class="text">
    				{section name=id loop=$rows}
    				<option value="{$rows[id]->imageId}">{$rows[id]->imageId}</option>
    				{/section}
    			</select>
    		</td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
	{include file="inc/table_footer.tpl" button2=1 button2_name="Switch"}
{include file="inc/footer.tpl"}