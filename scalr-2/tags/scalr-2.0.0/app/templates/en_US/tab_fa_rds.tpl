<div id="itab_contents_rds_n" class="x-hide-display" style="padding:10px;">
	<table width="99%" cellspacing="4">
		<tbody>
   			<tr>
	     		<td width="250">Placement:</td>
	     		<td>
	     			<select id="rds.availability_zone" name="rds.availability_zone" class="role_settings text">
	             		{section name=zid loop=$avail_zones}
	             			{if $avail_zones[zid] == ""}
	             			<option {if $servers[id].avail_zone == ""}selected{/if} value="">Choose randomly</option>
	             			<option {if $servers[id].avail_zone == "x-scalr-diff"}selected{/if} value="x-scalr-diff">Place in different zones</option>
	             			{else}
	             			<option {if $servers[id].avail_zone == $avail_zones[zid]}selected{/if} value="{$avail_zones[zid]}">{$avail_zones[zid]}</option>
	             			{/if}
	             		{/section}
	             	</select>
	             </td>
	     	</tr>
	     	<tr>
	     		<td>Instances class:</td>
	     		<td>
	     			<select id="rds.instance_class" name="rds.instance_class" class="role_settings text">
		         		<option value="db.m1.small">db.m1.small</option>
		         		<option value="db.m1.large">db.m1.large</option>
		         		<option value="db.m1.xlarge">db.m1.xlarge</option>
		         		<option value="db.m2.2xlarge">db.m2.2xlarge</option>
		         		<option value="db.m2.4xlarge">db.m2.4xlarge</option>
	             	</select>
	     		</td>
	     	</tr>
	     	<tr>
	     		<td>Allocated storage (5-1024 GB):</td>
	     		<td>
	     			<input type="text" id="rds.storage" size="5" name="rds.storage" class="role_settings text" value="5" /> GB
	     		</td>
	     	</tr>
	     	<tr>
	     		<td>Engine:</td>
	     		<td>
	     			<select id="rds.engine" name="rds.engine" class="role_settings text">
	             		<option selected="selected" value="MySQL5.1">MySQL5.1</option>
	             	</select>
	     		</td>
	     	</tr>
	     	<tr>
	     		<td>Master username:</td>
	     		<td>
	     			<input type="text" id="rds.master-user" name="rds.master-user" class="role_settings text" value="root" />
	     		</td>
	     	</tr>
	     	<tr>
	     		<td>Master password:</td>
	     		<td>
	     			<input type="text" id="rds.master-pass" name="rds.master-pass" class="role_settings text" value="" />
	     		</td>
	     	</tr>
	     	<tr>
	     		<td>Enable <a target="_blank" href="http://aws.amazon.com/about-aws/whats-new/2010/05/18/announcing-multi-az-deployments-for-amazon-rds/">MultiAZ</a>:</td>
	     		<td>
	     			<input type="checkbox" id="rds.multi-az" name="rds.multi-az" class="role_settings" value="1" />
	     		</td>
	     	</tr>
	     	<tr>
	     		<td>Port:</td>
	     		<td>
	     			<input type="text" id="rds.port" size="5" name="rds.port" class="role_settings text" value="3306" />
	     		</td>
	     	</tr>
		</tbody>
	</table>
</div>