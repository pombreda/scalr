<div id="itab_contents_ebs_n" class="x-hide-display" style="padding:10px;">
	<table width="99%" cellspacing="4">
		<tbody>
			<tr>
				<td colspan="2">
					<p class="placeholder">
						When new instance initialized, Scalr will<br>
						1. Attach a first detached volume, left by terminated or crashed instance or create a new EBS volume, attach it, and create an ext3 filesystem on it.<br />
						2. If "Automatically mount device" option selected, volume will be mounted.<br>
                 	</p>
                 </td>
			</tr>
			<tr>
				<td colspan="2">When instance based on this role boots up:</td>
			</tr>
			<tr>
				<td colspan="2"><input onclick="ShowEBSOptions(this.checked);" class="role_settings" type="checkbox" id="aws.use_ebs" name="aws.use_ebs" checked value="1" style="vertical-align:middle;"> Automatically attach EBS volume with the following options:</td>
			</tr>
			<tr>
				<td width="15%">Size: </td>
				<td>
					<div style="float:left;">
						<input style="vertical-align:middle;" type="text" id="aws.ebs_size" disabled name="aws.ebs_size" value="1" class="role_settings text" size="3"> GB
					</div>							    			
				</td>
			</tr>
			<tr>
				<td>Snapshot: </td>
				<td>
					<select disabled style="vertical-align:middle;" id="aws.ebs_snapid" name="aws.ebs_snapid" class="role_settings text">
					<option value=""></option>
					{foreach from=$snapshots key=key item=item}
						<option {if $snapId == $key}selected{/if} value="{$key}">{$key} (Created: {$item.createdat} Size: {$item.size}GB)</option>
					{foreachelse}
						<option value="">No snapshots found</option>
					{/foreach}
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<div id="ebs_mount_options" style="display:none;">
					<br />
					<input type="checkbox" class="role_settings" onclick="Ext.get('aws.ebs_mountpoint').dom.disabled = !this.checked;" id="aws.ebs_mount" name="aws.ebs_mount" style="vertical-align:middle;"> Automatically mount device to <input type="text" class="role_settings text" id="aws.ebs_mountpoint" name="aws.ebs_mountpoint" disabled size="10" value="/mnt/storage"> mount point.
					</div>
				</td>
			</tr>
		</tbody>
	</table>
</div>