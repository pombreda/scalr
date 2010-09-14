<div id="itab_contents_eips_n" class="x-hide-display" style="padding:10px;">
	<table width="99%" cellspacing="4">
		<tbody>
			<tr>
				<td colspan="2">
					<p class="placeholder">
						If this option is enabled, 
						Scalr will assign Elastic IPs to all instances of this role. It usually takes few minutes for IP to assign.
						The amount of allocated IPs increases when new instances start, 
						but not decreases when instances terminated.
						Elastic IPs are assigned after instance initialization. 
						This operation takes few minutes to complete. During this time instance is not available from 
						the outside and not included in application DNS zone.
                 	</p>
                 </td>
			</tr>
			<tr>
				<td>
					<input type="checkbox" class="role_settings" id="aws.use_elastic_ips" name="aws.use_elastic_ips" value="1"> Use Elastic IPs
				</td>
			</tr>
		</tbody>
	</table>
</div>