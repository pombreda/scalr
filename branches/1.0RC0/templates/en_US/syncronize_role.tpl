{include file="inc/header.tpl"}
	{include file="inc/table_header.tpl"}
		{include file="inc/intable_header.tpl" header="Syncronize role" color="Gray"}
    	<tr>
    		<td colspan="2" align="center">
   		   Yes, I made changes on instance <b>{$instance_id}</b>. I want these changes to propagate across other <b>{$rolename}</b> instances. For this, poller will rebundle this instance and will terminate old <b>{$rolename}</b> instances one-by-one. At the same time, new instances will be launched one-by-one, replacing old ones.
    		</td>
    	</tr>
    	<tr>
    	   <td colspan="2">&nbsp;</td>
    	</tr>
    	<tr>
    		<td width="20%">Role name:</td>
    		<td><input type="text" class="text" name="name" value="{$new_rolename}" /></td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
        <input type="hidden" name="iid" value="{$instance_id}" />
	{include file="inc/table_footer.tpl" button2=1 button2_name="Syncronize to all"}
{include file="inc/footer.tpl"}