{include file="inc/header.tpl" upload_files=1}
	{include file="inc/table_header.tpl"}
        {include file="inc/intable_header.tpl" header="General" color="Gray"}
        <tr>
    		<td width="20%">Instance ID:</td>
    		<td>{$info->instancesSet->item->instanceId}</td>
    	</tr>
    	<tr>
    		<td width="20%">State:</td>
    		<td>{$info->instancesSet->item->instanceState->name}</td>
    	</tr>
    	<tr>
    		<td width="20%">CloudWatch monitoring:</td>
    		<td>{if $info->instancesSet->item->monitoring->state == 'enabled'}
    				<a href="/aws_cw_monitor.php?ObjectId={$info->instancesSet->item->instanceId}&Object=InstanceId&NameSpace=AWS/EC2">{$info->instancesSet->item->monitoring->state}</a>
    				&nbsp;(<a href="aws_ec2_cw_manage.php?action=Disable&iid={$info->instancesSet->item->instanceId}&region={$smarty.request.region}">Disable</a>)
    			{else}
    				{$info->instancesSet->item->monitoring->state}
    				&nbsp;(<a href="aws_ec2_cw_manage.php?action=Enable&iid={$info->instancesSet->item->instanceId}&region={$smarty.request.region}">Enable</a>)
    			{/if}
    		</td>
    	</tr>
    	<tr>
    		<td width="20%">AMI ID:</td>
    		<td>{$info->instancesSet->item->imageId}</td>
    	</tr>
    	<tr>
    		<td width="20%">Private DNS name:</td>
    		<td>{$info->instancesSet->item->privateDnsName}</td>
    	</tr>
    	<tr>
    		<td width="20%">Public DNS name:</td>
    		<td>{$info->instancesSet->item->dnsName}</td>
    	</tr>
    	<tr>
    		<td width="20%">Key Name:</td>
    		<td>{$info->instancesSet->item->keyName}</td>
    	</tr>
    	<tr>
    		<td width="20%">AMI Launch Index:</td>
    		<td>{$info->instancesSet->item->amiLaunchIndex}</td>
    	</tr>
    	<tr>
    		<td width="20%">Instance Type:</td>
    		<td>{$info->instancesSet->item->instanceType}</td>
    	</tr>
    	<tr>
    		<td width="20%">Launch Time:</td>
    		<td>{$info->instancesSet->item->launchTime}</td>
    	</tr>
    	<tr>
    		<td width="20%">Kernel ID:</td>
    		<td>{$info->instancesSet->item->kernelId}</td>
    	</tr>
    	<tr>
    		<td width="20%">Ramdisk ID:</td>
    		<td>{$info->instancesSet->item->ramdiskId}</td>
    	</tr>
    	<tr>
    		<td width="20%">Private IP Address:</td>
    		<td>{$info->instancesSet->item->privateIpAddress}</td>
    	</tr>
    	<tr>
    		<td width="20%">Public IP Address:</td>
    		<td>{$info->instancesSet->item->ipAddress}</td>
    	</tr>
    	<tr>
    		<td width="20%">Root Device Type:</td>
    		<td>{$info->instancesSet->item->rootDeviceType}</td>
    	</tr>
    	<tr>
    		<td width="20%">Instance Lifecycle:</td>
    		<td>{$info->instancesSet->item->instanceLifecycle}</td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
	{include file="inc/table_footer.tpl" disable_footer_line=1}
{include file="inc/footer.tpl"}