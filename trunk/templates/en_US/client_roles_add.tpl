{include file="inc/header.tpl"}
	{include file="inc/table_header.tpl"}
		{include file="inc/intable_header.tpl" header="Role information" color="Gray"}
    	<tr>
    		<td width="20%">Role name:</td>
    		<td><input type="text" class="text" name="name" value="{$name}" /></td>
    	</tr>
    	<tr>
    		<td width="20%">Default mimimum LA for this role:</td>
    		<td><input type="text" class="text" name="default_minLA" value="{$default_minLA}" /></td>
    	</tr>
    	<tr>
    		<td width="20%">Default maximum LA for this role:</td>
    		<td><input type="text" class="text" name="default_maxLA" value="{$default_maxLA}" /></td>
    	</tr>
    	<tr>
    		<td width="20%">Based on instance:</td>
    		<td>
    		<select name="instance_id">
    		{section name=id loop=$rows}
    		    <option value="{$rows[id]->instancesSet->item->instanceId}">{$rows[id]->instancesSet->item->instanceId} - {$rows[id]->Role} ({$rows[id]->instancesSet->item->imageId})</option>
    		{/section}
    		</select>
    		</td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
	{include file="inc/table_footer.tpl" edit_page=1}
{include file="inc/footer.tpl"}