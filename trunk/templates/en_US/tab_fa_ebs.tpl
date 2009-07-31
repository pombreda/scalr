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
				<td colspan="2"><input onclick="ShowEBSOptions(this.value);" type="radio" name="ebs_ctype" checked value="1" style="vertical-align:middle;"> Do not use EBS</td>
			</tr>
			<tr>
				<td colspan="2">
					<div style="float:left;">
						<input onclick="ShowEBSOptions(this.value);" type="radio" name="ebs_ctype" value="2" style="vertical-align:middle;"> Attach empty volume with size:
						<input style="vertical-align:middle;" type="text" id="ebs_size" name="ebs_size" value="1" class="text" size="3"> GB
					</div>							    			
				</td>
			</tr>
			<tr>
				<td colspan="2"><input onclick="ShowEBSOptions(this.value);" type="radio" {if $snapshots|@count == 0}disabled{/if} name="ebs_ctype" value="3" style="vertical-align:middle;"> Attach volume from snapshot:
					<select {if $snapshots|@count == 0}disabled{/if} style="vertical-align:middle;" id="ebs_snapid" name="ebs_snapid" class="text">
					{section name=sid loop=$snapshots}
					<option {if $snapId == $snapshots[sid]}selected{/if} value="{$snapshots[sid]}">{$snapshots[sid]}</option>
					{sectionelse}
					<option value="">No snapshots found</option>
					{/section}
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<div id="ebs_mount_options" style="display:none;">
					<br />
					<input type="checkbox" onclick="$('ebs_mountpoint').disabled = !this.checked;" id="ebs_mount" style="vertical-align:middle;"> Automatically mount device to <input type="text" class="text" id="ebs_mountpoint" disabled size="10" value="/mnt/storage"> mount point.
					</div>
				</td>
			</tr>
		</tbody>
	</table>
</div>