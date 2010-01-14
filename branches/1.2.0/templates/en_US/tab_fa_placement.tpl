<div id="itab_contents_placement_n" class="x-hide-display" style="padding:10px;">
	<table width="99%" cellspacing="4">
		<tbody>
   			<tr>
	     		<td width="150">Placement:</td>
	     		<td>
	     			<select id="aws.availability_zone" name="aws.availability_zone" class="role_settings text">
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
	     		<td>Instances type:</td>
	     		<td>
	     			<select id="aws.instance_type" name="aws.instance_type" class="role_settings text">
	             		{section name=zid loop=$servers[id].types}
	             			<option value="{$servers[id].types[zid]}">{$servers[id].types[zid]}</option>
	             		{/section}
	             	</select>
	     		</td>
	     	</tr>
		</tbody>
	</table>
</div>