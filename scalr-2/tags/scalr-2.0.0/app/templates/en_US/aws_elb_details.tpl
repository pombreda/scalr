{include file="inc/header.tpl" upload_files=1}
	{include file="inc/table_header.tpl"}
        {include file="inc/intable_header.tpl" header="General" color="Gray"}
        <tr>
    		<td width="20%">Name:</td>
    		<td>{$elb->LoadBalancerName}</td>
    	</tr>
    	<tr>
    		<td width="20%">DNS Name:</td>
    		<td>{$elb->DNSName}</td>
    	</tr>
    	<tr>
    		<td width="20%">Created at:</td>
    		<td>{$elb->CreatedTime}</td>
    	</tr>
    	<tr>
    		<td colspan="2">&nbsp;</td>
    	</tr>
    	<tr>
    		<td width="20%">Availability Zones:</td>
    		<td>
    			{foreach name=az item=i from=$elb->AvailabilityZones->member}
    				{$i}{if !$smarty.foreach.az.last},{/if}
    			{/foreach}
    		</td>
    	</tr>
    	<tr>
    		<td colspan="2">&nbsp;</td>
    	</tr>
    	<tr>
    		<td width="20%">Instances:</td>
    		<td>
    			{foreach name=ai item=i from=$elb->Instances->member}
    				<a title="View instance health status" href="aws_elb_instance_health.php?lb={$elb->LoadBalancerName}&iid={$i->InstanceId}">{$i->InstanceId}</a>{if !$smarty.foreach.ai.last},{/if}
    			{foreachelse}
    				There are no instances registered on this load balancer
    			{/foreach}
    		</td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
        
        {include file="inc/intable_header.tpl" header="HealthCheck settings" color="Gray"}
        <tr>
    		<td width="20%">Interval:</td>
    		<td>{$elb->HealthCheck->Interval} seconds</td>
    	</tr>
    	<tr>
    		<td width="20%">Target:</td>
    		<td>{$elb->HealthCheck->Target}</td>
    	</tr>
    	<tr>
    		<td width="20%">Healthy Threshold:</td>
    		<td>{$elb->HealthCheck->HealthyThreshold}</td>
    	</tr>
    	<tr>
    		<td width="20%">Timeout:</td>
    		<td>{$elb->HealthCheck->Timeout} seconds</td>
    	</tr>
    	<tr>
    		<td width="20%">UnHealthy Threshold:</td>
    		<td>{$elb->HealthCheck->UnhealthyThreshold}</td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
        
        {include file="inc/intable_header.tpl" header="Listeners" color="Gray"}
        <tr>
    		<td colspan="2">
    			<table cellpadding="4" cellspacing="4">
    				<tr style="font-weight:bold;">
    					<td>Protocol</td>
    					<td>LoadBalancer Port</td>
    					<td>Instance Port</td>
    				</tr>
    				{foreach name=al item=i from=$elb->Listeners->member}
	    				<tr>
	    					<td>{$i->Protocol}</td>
	    					<td>{$i->LoadBalancerPort}</td>
	    					<td>{$i->InstancePort}</td>
	    				</tr>
	    			{/foreach}
    			</table>
    		</td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
	{include file="inc/table_footer.tpl" disable_footer_line=1}
{include file="inc/footer.tpl"}