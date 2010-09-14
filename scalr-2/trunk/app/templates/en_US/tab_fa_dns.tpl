<div id="itab_contents_dns_n" class="x-hide-display" style="padding:10px;">
	<table width="99%" cellspacing="4">
		<tbody>
			<tr>
				<td colspan="2">
					<p class="placeholder">
						Scalr adds/deletes DNS records for all Applications(domains), hosted on this farm, when new instances get launched and terminated. 
						Excluded role will be ignored.
                 	</p>
                 </td>
			</tr>
			<tr>
				<td colspan="2">
	         	<input type="checkbox" id="dns.exclude_role" class="role_settings" name="dns.exclude_role" value="1" style="vertical-align:middle;" />
	         	Exclude role from DNS zone
				</td>
			</tr>
			<tr>
				<td colspan="2">
	         		Create <input type="text" name="dns.int_record_alias" id="dns.int_record_alias" class="role_settings text"> records instead of <b>int-%rolename%</b> ones.
				</td>
			</tr>
			<tr>
				<td colspan="2">
	         		Create <input type="text" name="dns.ext_record_alias" id="dns.ext_record_alias" class="role_settings text"> records instead of <b>ext-%rolename%</b> ones.
				</td>
			</tr>
		</tbody>
	</table>
</div>