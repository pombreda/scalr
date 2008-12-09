{include file="inc/header.tpl"}
	<script language="Javascript">
	
	var default_LA = new Array();
	
	{section name=id loop=$rows}
    	{if $rows[id]->LA}
    		default_LA['{$rows[id]->instancesSet->item->instanceId}'] = [{$rows[id]->LA.min},{$rows[id]->LA.max}];
    	{/if}	    
   	{/section}	
   	
   	{literal}
   	function SetLA(instance_id)
   	{
   		if (default_LA[instance_id])
   		{
   			$('default_minLA').value = default_LA[instance_id][0];
   			$('default_maxLA').value = default_LA[instance_id][1];
   		}
   	}
   	
   	Event.observe(window,'load', function(){
	   	SetLA($('instance_id').value);
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
    		<td width="20%">Default mimimum LA:</td>
    		<td><input type="text" class="text" name="default_minLA" id="default_minLA" value="{$default_minLA}" /></td>
    	</tr>
    	<tr>
    		<td width="20%">Default maximum LA:</td>
    		<td><input type="text" class="text" name="default_maxLA" id="default_maxLA" value="{$default_maxLA}" /></td>
    	</tr>
    	<tr>
    		<td width="20%">Based on instance:</td>
    		<td>
    		<select id="instance_id" name="instance_id" onChange="SetLA(this.value);">
    		{section name=id loop=$rows}
    		    <option value="{$rows[id]->instancesSet->item->instanceId}">{$rows[id]->instancesSet->item->instanceId} - {$rows[id]->Role} ({$rows[id]->instancesSet->item->imageId})</option>
    		{/section}
    		</select>
    		</td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
	{include file="inc/table_footer.tpl" button2=1 button2_name="Create custom role"}
{include file="inc/footer.tpl"}