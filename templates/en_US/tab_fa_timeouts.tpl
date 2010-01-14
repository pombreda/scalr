<div id="itab_contents_timeouts_n" class="x-hide-display" style="padding:10px;">
	<table width="99%" cellspacing="4">
		<tbody>
			<tr>
				<td colspan="2">Terminate instance if it will not send 'rebootFinish' event after reboot in <input name="reboot_timeout" type="text" class="text" id="reboot_timeout" value="" size="3"> seconds.</td>
			</tr>
			<tr>
				<td colspan="2">Terminate instance if it will not send 'hostUp' or 'hostInit' event after launch in <input name="launch_timeout" type="text" class="text" id="launch_timeout" value="" size="3"> seconds.</td>
			</tr>
			<tr>
			
								
			</tr>
			<tr>
				<td colspan="2"> 
				<input type="checkbox" class="role_settings" style="vertical-align:middle;" name="health.terminate_if_snmp_fails" id="health.terminate_if_snmp_fails" value="1" />
					<select size="1" class="role_settings" style="vertical-align:middle;" name="health.terminate_action_if_snmp_fails" id="health.terminate_action_if_snmp_fails" >
						<option selected value = "reboot">Reboot</option>
						<option value    = "terminate">Terminate</option>
					</select>  instance if cannot retrieve it's status in 
					<input name="status_timeout" type="text" class="text" id="status_timeout" value="" size="3"> minutes.
				</td>
			</tr>
		</tbody>
	</table>
</div>
