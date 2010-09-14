<div id="itab_contents_mysql_n" class="x-hide-display" style="padding:10px;">
	<table width="99%" cellspacing="4">
		<tbody>
          	<tr>
          		<td colspan="2">
          			<input style="vertical-align:middle;" type="checkbox" class="role_settings" name="mysql.enable_bundle" id="mysql.enable_bundle" value="1"> Bundle and save mysql data snapshot every <img style="vertical-align:middle;cursor:pointer;" title="Help" onclick="ShowHelp(event, 'mysql_help', this);" src="/images/icon_shelp.png">: 
          			<input type="text" size="3" class="role_settings text" id="mysql.bundle_every" name="mysql.bundle_every" value="48" /> hours
          		</td>
          	</tr>
          	<tr>
	    		<td colspan="2">Preferred bundle window:&nbsp;&nbsp;
	    			<input class="role_settings text" type="text" size="3" name="mysql.pbw1_hh" id="mysql.pbw1_hh" value="05" />:<input class="role_settings text" type="text" size="3" id="mysql.pbw1_mm" name="mysql.pbw1_mm" value="00" />
	    			-
	    			<input class="role_settings text" type="text" size="3" name="mysql.pbw2_hh" id="mysql.pbw2_hh" value="09" />:<input class="role_settings text" type="text" size="3" id="mysql.pbw2_mm" name="mysql.pbw2_mm" value="00" />
	    			<br />
	    			<span style="font-size:10px;font-style:italic;">Format: hh24:mi - hh24:mi</span>
	    		</td>
	    	</tr>
	    	<tr>
          		<td width="200">&nbsp;</td>
          		<td width="100%">&nbsp;</td>
          	</tr>
          	<tr>
          		<td colspan="2"><input style="vertical-align:middle;" class="role_settings" type="checkbox" name="mysql.enable_bcp" id="mysql.enable_bcp" value="1"> Periodically backup databases every: <input type="text" size="3" class="role_settings text" id="mysql.bcp_every" name="mysql.bcp_every" value="360" /> minutes</td>
          	</tr>
          	<tr>
          		<td width="200">&nbsp;</td>
          		<td width="100%">&nbsp;</td>
          	</tr>
          	<tr>
          		<td width="200" nowrap="nowrap">Storage engine:&nbsp;&nbsp;</td>
          		<td>
          			<select onchange="CheckEBSSize(this.value);" id="mysql.data_storage_engine" name="mysql.data_storage_engine" class="role_settings text">
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
	          			<input type="text" size="5" class="role_settings text" id="mysql.ebs_volume_size" name="mysql.ebs_volume_size" value="100" /> GB
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
				Ext.get('mysql_ebs_size_tr').dom.style.display = '';
			}
			else {
				Ext.get('mysql_ebs_size_tr').dom.style.display = 'none';
			}
		}
                  	
		CheckEBSSize(Ext.get('mysql.data_storage_engine').dom.value);
    {/literal}
    </script>
</div>