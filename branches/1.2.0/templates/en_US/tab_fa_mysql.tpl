<div id="itab_contents_mysql_n" class="x-hide-display" style="padding:10px;">
	<table width="99%" cellspacing="4">
		<tbody>
          	<tr>
          		<td colspan="2"><input style="vertical-align:middle;" type="checkbox" name="mysql_bundle" id="mysql_bundle" value="1"> Bundle and save mysql data snapshot every <img style="vertical-align:middle;cursor:pointer;" title="Help" onclick="ShowHelp(event, 'mysql_help', this);" src="/images/icon_shelp.png">: <input type="text" size="3" class="text" id="mysql_rebundle_every" name="mysql_rebundle_every" value="{if $farminfo.mysql_rebundle_every}{$farminfo.mysql_rebundle_every}{else}48{/if}" /> hours</td>
          	</tr>
          	<tr>
          		<td colspan="2"><input style="vertical-align:middle;" type="checkbox" name="mysql_bcp" id="mysql_bcp" value="1"> Periodically backup databases every: <input type="text" size="3" class="text" id="mysql_bcp_every" name="mysql_bcp_every" value="{if $farminfo.mysql_bcp_every}{$farminfo.mysql_bcp_every}{else}180{/if}" /> minutes</td>
          	</tr>
          	<tr>
          		<td width="200">&nbsp;</td>
          		<td width="100%">&nbsp;</td>
          	</tr>
          	<tr>
          		<td width="200" nowrap="nowrap">Storage engine:&nbsp;&nbsp;</td>
          		<td>
          			<select onchange="CheckEBSSize(this.value);" id="mysql_data_storage_engine" name="mysql_data_storage_engine" class="text">
          				<option value="eph">Ephemeral device</option>
          				<option value="lvm">LVM</option>
          				<option value="ebs">EBS</option>
          			</select>
          		</td>
          	</tr>
          	<tbody id="mysql_ebs_size_tr">
	          	<tr>
	          		<td nowrap="nowrap" width="200">EBS size (max. 1000 GB):</td>
	          		<td>
	          			<input type="text" size="5" class="text" id="mysql_ebs_size" name="mysql_ebs_size" value="100" /> GB
	          		</td>
	          	</tr>
	          	<tr>
	          		<td colspan="2">&nbsp;</td>
	          	</tr>
	          	<tr>
	          		<td colspan="2">
	          			<input style="vertical-align:middle;" class="role_settings" type="checkbox" name="mysql.ebs.rotate_snaps" id="mysql.ebs.snaps_rotation_enabled" value="1">
	          			Snapshots are rotated  <input name="mysql.ebs.rotate" type="text" class="role_settings text" id="mysql.ebs.rotate" value="5" size="3"> times before being removed.
	          		</td>
	          	</tr>
          	</tbody>
		</tbody>
	</table>
	<script language="Javascript">
	{literal}
		function CheckEBSSize(storage_engine)
		{
			if (storage_engine == 'ebs') {
				$('mysql_ebs_size_tr').style.display = '';
			}
			else {
				$('mysql_ebs_size_tr').style.display = 'none';
			}
		}
                  	
		CheckEBSSize($('mysql_data_storage_engine').value);
    {/literal}
    </script>
</div>