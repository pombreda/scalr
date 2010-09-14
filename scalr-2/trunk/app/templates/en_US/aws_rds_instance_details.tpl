{include file="inc/header.tpl" upload_files=1}
	{literal}
	<style>
		#Webta_InnerTable_General TD
		{
			padding: 4px;	
		}
	</style>
	{/literal}
	{include file="inc/table_header.tpl"}
        {include file="inc/intable_header.tpl" header="General" color="Gray"}
        <tr>
    		<td width="20%">Name:</td>
    		<td>{$dbinstance->DBInstanceIdentifier}</td>
    	</tr>
    	<tr>
    		<td width="20%">Engine:</td>
    		<td>{$dbinstance->Engine}</td>
    	</tr>
    	<tr>
    		<td width="20%">DNS Name:</td>
    		<td>{$dbinstance->Endpoint->Address}</td>
    	</tr>
    	<tr>
    		<td width="20%">Port:</td>
    		<td>{$dbinstance->Endpoint->Port}</td>
    	</tr>
    	<tr>
    		<td width="20%">Created at:</td>
    		<td>{$dbinstance->InstanceCreateTime}</td>
    	</tr>
    	<tr>
    		<td width="20%">Status:</td>
    		<td>{$dbinstance->DBInstanceStatus}</td>
    	</tr>
    	<tr>
    		<td colspan="2">&nbsp;</td>
    	</tr>
    	<tr>    		    		
    		<td width="20%">Availability Zone:</td>
    		<td>{$dbinstance->AvailabilityZone}</td>
    	</tr>
    	<tr>    		    		
    		<td width="20%">MultiAZ:</td>
    		<td>{if $dbinstance->MultiAZ == 'true'}Enabled{else}Disabled{/if}</td>
    	</tr>
    	<tr>
    		<td width="20%">Type:</td>
    		<td>{$dbinstance->DBInstanceClass}</td>
    	</tr>
    	<tr>
    		<td width="20%">Allocated storage:</td>
    		<td>{$dbinstance->AllocatedStorage} GB</td>
    	</tr>
    	<tr>
    		<td colspan="2">&nbsp;</td>
    	</tr>
    	<tr>
    		<td width="20%">Security groups:</td>
    		<td>
    			{foreach name=id key=key item=item from=$sec_groups}
    				{if !$smarty.foreach.id.last}
    					{$item.DBSecurityGroupName} ({$item.Status}), 
    				{else}
    					{$item.DBSecurityGroupName} ({$item.Status})
    				{/if}
    			{/foreach}
    		</td>
    	</tr>
    	<tr>
    		<td colspan="2">&nbsp;</td>
    	</tr>
    	<tr>
    		<td width="20%">Parameter groups:</td>
    		<td>
    			{foreach name=id key=key item=item from=$param_groups}
    				{if !$smarty.foreach.id.last}
    					{$item.DBParameterGroupName} ({$item.ParameterApplyStatus}), 
    				{else}
    					{$item.DBParameterGroupName} ({$item.ParameterApplyStatus})
    				{/if}
    			{/foreach}
    		</td>
    	</tr>
    	<tr>
    		<td colspan="2">&nbsp;</td>
    	</tr>
    	<tr>
    		<td width="20%">Preferred maintenance window:</td>
    		<td>{$dbinstance->PreferredMaintenanceWindow}</td>
    	</tr>
    	<tr>
    		<td width="20%">Preferred backup window:</td>
    		<td>{$dbinstance->PreferredBackupWindow}</td>
    	</tr>
    	<tr>
    		<td width="20%">Backup retention period:</td>
    		<td>{$dbinstance->BackupRetentionPeriod}</td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
	    {if $pending_values}
	    	{include file="inc/intable_header.tpl" header="Pending Modified Values" color="Gray"}
	    	{foreach item=item key=key from=$pending_values}
	    	<tr>
	    		<td width="20%">{$key}:</td>	    		
	    		<td>{$item}</td>
	    	</tr>
	    	{/foreach}
	    	{include file="inc/intable_footer.tpl" color="Gray"}
	    {/if}
	{include file="inc/table_footer.tpl" disable_footer_line=1}
{include file="inc/footer.tpl"}