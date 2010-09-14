{include file="inc/header.tpl"}
<script src="/js/farm_role_server_loader.js"></script>
<script type="text/javascript">
{literal}
/*
Ext.onReady(function () {
	var farmCombo = FarmRoleServerHelper.newFarmsCombo({
		renderTo: 'farm_combo',
		mode: 'remote',
		name: 'farmid',
		readOnly: true
	});
});
function toggleFarmCombo (checkbox) {
	Ext.get('farm-combo-row').setStyle('display', checkbox.checked ? '' : 'none');
	
}
*/
{/literal}
</script>


	{include file="inc/table_header.tpl"}
	{include file="inc/intable_header.tpl" header="Import server &mdash; Step 1 (Server details)" color="Gray"}
	    <tr>
			<td width="20%">Platform:</td>
			<td colspan="6">
				<select name="platform" class="text">
					<option value="ec2" {if $platform == "ec2"}selected{/if}>Amazon EC2</option>
				</select>
			</td>
		</tr>
	    <tr>
			<td width="20%">Behavior:</td>
			<td colspan="6">
				<select name="behaviour" class="text">
					{html_options options=$behaviours selected=$behaviour}
				</select>
			</td>
		</tr>
		
		<tr>
			<td width="20%">Server IP address:</td>
			<td colspan="6">
				<input type='text' class='text' name='remote_ip' value="{$remote_ip}" /> <span style="color:red;font-size:11px;">(Please make sure that TCP port 8013 is opened in security groups for this server)</span>
			</td>
		</tr>
		<tr>
			<td width="20%">Role name:</td>
			<td colspan="6">
				<input type='text' name="role_name" class="text" value="{$role_name}" />
			</td>
		</tr>
		<!-- 
		<tr>
			<td colspan="2" height="27"><input type="checkbox" name="add2farm" value="1" onclick="toggleFarmCombo(this)"> Add this role to farm</td>
		</tr>
		<tr id="farm-combo-row" style="display: none;">
			<td width="20%">Farm:</td>
			<td colspan="6">
				<div id="farm_combo" style="padding-left:5px;"></div>
			</td>
		</tr>
		 -->
		
		<input type="hidden" name="step" value="1"/>
    {include file="inc/intable_footer.tpl" color="Gray"}
    {include file="inc/table_footer.tpl" button2=1 button2_name="Next"}

{include file="inc/footer.tpl"}