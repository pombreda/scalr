{include file="inc/header.tpl"}
	<script language="Javascript">
	
	var role_info = new Array();
	
	{section name=id loop=$rows}
    	{if $rows[id]->RoleInfo}
    		role_info['{$rows[id]->instancesSet->item->instanceId}'] = [{$rows[id]->RoleInfo.min},{$rows[id]->RoleInfo.max}];
    	{/if}	    
   	{/section}	
   	
   	{literal}
   	function SetDefaultInfo(instance_id)
   	{
   		if (role_info[instance_id])
   		{
   			$('default_minLA').value = role_info[instance_id][0];
   			$('default_maxLA').value = role_info[instance_id][1];
   		}
   	}
   	
   	Event.observe(window,'load', function(){
	   	SetDefaultInfo($('instance_id').value);
   	})
   	{/literal}
	</script>
	{include file="inc/table_header.tpl"}
		{include file="inc/intable_header.tpl" header="Role information" color="Gray"}
    	<tr>
    		<td width="20%">Role name:</td>
    		<td><input type="text" class="text" name="name" value="{$name}" /></td>
    	</tr>
    	<tr>
    		<td width="20%">Based on instance:</td>
    		<td>
    		<select id="instance_id" name="instance_id" onChange="SetDefaultInfo(this.value);">
    		{section name=id loop=$rows}
    		    <option value="{$rows[id]->instancesSet->item->instanceId}">{$rows[id]->instancesSet->item->instanceId} - {$rows[id]->Role} ({$rows[id]->instancesSet->item->imageId})</option>
    		{/section}
    		</select>
    		</td>
    	</tr>
    	<tr>
    		<td width="20%">Default mimimum LA:</td>
    		<td><input type="text" class="text" name="default_minLA" id="default_minLA" value="{$default_minLA}" /></td>
    	</tr>
    	<tr>
    		<td width="20%">Default maximum LA:</td>
    		<td><input type="text" class="text" name="default_maxLA" id="default_maxLA" value="{$default_maxLA}" /></td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
	{include file="inc/table_footer.tpl" button2=1 button2_name="Create custom role"}
{include file="inc/footer.tpl"}