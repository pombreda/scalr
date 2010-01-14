{include file="inc/header.tpl"}
<script language="javascript" type="text/javascript">
{literal}
	function CheckPrAdd(tp, id, val)
	{
		$("spf_link_"+id).style.display = 'none';
		$(tp+"_"+id+"_weight").style.display = 'none';
		$(tp+"_"+id+"_port").style.display = 'none';
		
		
		if (val == 'MX')
		{
			$(tp+"_"+id).style.display = '';
			$(tp+"_"+id).value = '10';
		}
		else if (val == 'SRV')
		{
			$(tp+"_"+id).style.display = '';
			
			$(tp+"_"+id+"_weight").style.display = '';
			$(tp+"_"+id+"_port").style.display = '';
		}
		else if (val == 'TXT')
		{
			$(tp+"_"+id).style.display = 'none';
			$(tp+"_"+id).value = '';
			$("spf_link_"+id).style.display = '';
		} 
		else
		{
			$(tp+"_"+id).style.display = 'none';
			$(tp+"_"+id).value = '';
		}
	}
{/literal}
</script>
<script type="text/javascript" src="js/class.NewPopup.js"></script>
<link href="css/popup.css" rel="stylesheet" type="text/css" />
<br>
{assign var=button2_name value="Save"}
{assign var=nofilter value="1"}
{assign var=nofilter value="1"}

{if $ezone && $domainname}
{include file="inc/table_header.tpl" nofilter=1}
	<div id="google_btn" style="padding-left: 5px; padding-top: 5px; padding-bottom: 5px;">
		<input style="vertical-align:middle;" type="button" onclick="location.href='dns_zone_config.php?zone={$ezone}'" name="DNS_zone_transfers" value="Configure DNS zone transfers" class="btn" />
		<span style="padding-left:10px"><input style="vertical-align:middle;" type="submit"  name="setup_google_apps_mx_records" value="Setup Google Apps MX records" class="btn" {if $disable_btn_setup_google_apps_mx == true}Disabled{/if} /></span>
	</div>
{include file="inc/table_footer.tpl" disable_footer_line=1 color="Gray"}
<br />
{/if}

{include file="inc/table_header.tpl" nofilter=1}
{if !$domainname && !$ezone}
    {include file="inc/intable_header.tpl" header="Application information" color="Gray"}
    <tr>
		<td width="15%">{t}Farm{/t}:</td>
		<td colspan="6">{$farm.name} <input type='hidden' name='farmid' value="{$farm.id}" /></td>
	</tr>
	<tr>
		<td width="15%">{t}Traffic will go to{/t}:</td>
		<td colspan="6">
		  <select name="ami_id" class="text">
		  {section name=id loop=$roles}
		      <option value="{$roles[id].ami_id}">{$roles[id].name} ({$roles[id].ami_id})</option>
		  {/section}
		  </select>
		</td>
	</tr>
	<tr>
		<td width="15%">{t}Domain or subdomain name{/t}:</td>
		<td colspan="6"><input type="text" class="text" name="domainname" /></td>
	</tr>
    {include file="inc/intable_footer.tpl" color="Gray"}
    {assign var=button2_name value="Next"}
{else}
<table cellpadding="4" cellspacing="0" width="100%">
	<tr>
		<td>{$domainname}</td>
		<td><input type="text" class="text" disabled name="zone[soa_ttl]" size="6" value="{if $zone.soa_ttl}{$zone.soa_ttl}{else}14400{/if}"></td>
		<td>IN</td>
		<td>SOA</td>
		<td><input type="text" class="text" disabled name="zone[soa_parent]" size="30" value="{if $zone.soa_parent}{$zone.soa_parent}{else}{$def_soa_parent}{/if}"></td>
		<td><input type="text" class="text" name="zone[soa_owner]" size="30" value="{if $zone.soa_owner}{$zone.soa_owner}{else}{$def_soa_owner}{/if}"></td>
		<td></td>
	</tr>
	<tr>
		<td colspan=4></td>
		<td>Serial Number:</td>
		<td><input type="text" class="text" disabled name="zone[soa_serial]" size=12  value="{if $zone.soa_serial}{$zone.soa_serial}{else}{$def_sn}{/if}"></td>
	</tr>
	<tr>
		<td colspan=4></td>
		<td>Refresh:</td>
		<td><input type="text" class="text" disabled name="zone[soa_refresh]" size=12  value="{if $zone.soa_refresh}{$zone.soa_refresh}{else}14400{/if}"></td>
	</tr>
	<tr>
		<td colspan=4></td>
		<td>Retry:</td>
		<td><input type="text" class="text" disabled name="zone[soa_retry]" size=12  value="{if $zone.soa_retry}{$zone.soa_retry}{else}7200{/if}"></td>
	</tr>
	<tr>
		<td colspan=4></td>
		<td>Expire:</td>
		<td>
		<select name="zone[soa_expire]" class="text">
			<option {if $zone.soa_expire == 86400}selected{/if} value="86400">1 day</option>
			<option {if $zone.soa_expire == 259200}selected{/if} value="259200">3 days</option>
			<option {if $zone.soa_expire == 432000}selected{/if} value="432000">5 days</option>
			<option {if $zone.soa_expire == 604800}selected{/if} value="604800">1 week</option>
			<option {if $zone.soa_expire == 3024000 || $zone.soa_expire == 3600000}selected{/if} value="3024000">5 weeks</option>
			<option {if $zone.soa_expire == 6048000}selected{/if} value="6048000">10 weeks</option>
		</select>
		</td>
	</tr>
	<tr>
		<td colspan=4></td>
		<td>Minimum TTL:</td>
		<td><input type="text" class="text" disabled name="zone[min_ttl]" size=12 value="{if $zone.min_ttl}{$zone.min_ttl}{else}86400{/if}"></td>
		<td></td>
	</tr>
	<tr>
		<td class="th">{t}Domain{/t}</td>
		<td class="th">{t}TTL{/t}</td>
		<td class="th">&nbsp;</td>
		<td class="th">{t}Record Type{/t}</td>
		<td class="th" colspan=3>{t}Record value{/t}<td>
	</tr>
	{section name=id loop=$zone.records}
	<tr>
		<td><input {if $zone.records[id].issystem == 1 && $zone.allow_manage_system_records == 0}disabled{/if} type="text" class="text" name="zone[records][{$zone.records[id].id}][rkey]" size=30 value="{$zone.records[id].rkey}"></td>
		<td><input {if $zone.records[id].issystem == 1 && $zone.allow_manage_system_records == 0}disabled{/if} type="text" class="text" name="zone[records][{$zone.records[id].id}][ttl]" size=6 value="{$zone.records[id].ttl}"></td>
		<td>IN</td>
		<td><select {if $zone.records[id].issystem == 1 && $zone.allow_manage_system_records == 0}disabled{/if} class="text" name="zone[records][{$zone.records[id].id}][rtype]" onchange="CheckPrAdd('ed', '{$zone.records[id].id}', this.value)">
				<option {if $zone.records[id].rtype == "A"}selected{/if} value="A">A</option>
				<option {if $zone.records[id].rtype == "CNAME"}selected{/if} value="CNAME">CNAME</option>
				<option {if $zone.records[id].rtype == "MX"}selected{/if} value="MX">MX</option>
				<option {if $zone.records[id].rtype == "NS"}selected{/if} value="NS">NS</option>
				<option {if $zone.records[id].rtype == "TXT"}selected{/if} value="TXT">TXT</option>
				<option {if $zone.records[id].rtype == "SRV"}selected{/if} value="SRV">SRV</option>
			</select>
		</td>
		<td colspan="2"> 
			<input {if $zone.records[id].issystem == 1 && $zone.allow_manage_system_records == 0}disabled{/if} onclick="{literal}if (this.value == 'priority') { this.value=''; } {/literal}" id="ed_{$zone.records[id].id}" size="5" style="display:{if $zone.records[id].rtype != "MX" && $zone.records[id].rtype != "SRV"}none{/if};" type="text" class="text" name="zone[records][{$zone.records[id].id}][rpriority]" value="{$zone.records[id].rpriority}" size=30> 
			<input {if $zone.records[id].issystem == 1 && $zone.allow_manage_system_records == 0}disabled{/if} onclick="{literal}if (this.value == 'weight') { this.value=''; } {/literal}" id="ed_{$zone.records[id].id}_weight" size="5" style="display:{if $zone.records[id].rtype != "SRV"}none{/if};" type="text" class="text" name="zone[records][{$zone.records[id].id}][rweight]" value="{$zone.records[id].rweight}" size=30>
			<input {if $zone.records[id].issystem == 1 && $zone.allow_manage_system_records == 0}disabled{/if} onclick="{literal}if (this.value == 'port') { this.value=''; } {/literal}" id="ed_{$zone.records[id].id}_port" size="5" style="display:{if $zone.records[id].rtype != "SRV"}none{/if};" type="text" class="text" name="zone[records][{$zone.records[id].id}][rport]" value="{$zone.records[id].rport}" size=30>
			
			<input {if $zone.records[id].issystem == 1 && $zone.allow_manage_system_records == 0}disabled{/if} class="text" type=text id="zone[records][{$zone.records[id].id}][rvalue]" name="zone[records][{$zone.records[id].id}][rvalue]" size=30 value="{$zone.records[id].rvalue}">
			<span style="display:{if $zone.records[id].rtype != "TXT"}none{/if};vertical-align:middle;" id="spf_link_{$zone.records[id].id}">
				&nbsp;&nbsp;&nbsp;<input style="vertical-align:middle;" type="button" onclick="AddSPFRecord('{$zone.records[id].id}', this);" name="spf" value="SPF constructor" class="btn">
			</span>
		</td>
	</tr>
	{/section}
	<tr>
		<td colspan=7>&nbsp;</td>
	</tr>
	<tr>
		<td colspan=7 class="th">{t}Add New Entries Below this Line{/t}</td>
	</tr>
	{section name=id loop=$add}
	<tr>
		<td><input type="text" class="text" name="add[{$add[id]}][rkey]" size=30></td>
		<td><input type="text" class="text" name="add[{$add[id]}][ttl]" size=6 value="14400"></td>
		<td>IN</td>
		<td><select class="text" name="add[{$add[id]}][rtype]" onchange="CheckPrAdd('ad', '{$add[id]}', this.value)">
				<option selected value="A">A</option>
				<option value="CNAME">CNAME</option>
				<option value="MX">MX</option>
				<option value="TXT">TXT</option>
				<option value="NS">NS</option>
				<option value="SRV">SRV</option>
			</select>
		</td>
		<td colspan="2">
			<input onclick="{literal}if (this.value == 'priority') { this.value=''; } {/literal}" id="ad_{$add[id]}" size="5" style="display:none;" type="text" class="text" name="add[{$add[id]}][rpriority]" value="priority" size=30> 
			<input onclick="{literal}if (this.value == 'weight') { this.value=''; } {/literal}" id="ad_{$add[id]}_weight" size="5" style="display:none;" type="text" class="text" name="add[{$add[id]}][rweight]" value="weight" size=30>
			<input onclick="{literal}if (this.value == 'port') { this.value=''; } {/literal}" id="ad_{$add[id]}_port" size="5" style="display:none;" type="text" class="text" name="add[{$add[id]}][rport]" value="port" size=30>
			
			<input type="text" class="text" id="add[{$add[id]}][rvalue]" name="add[{$add[id]}][rvalue]" size=30>
			<span style="display:none;vertical-align:middle;" id="spf_link_{$add[id]}">
				&nbsp;&nbsp;&nbsp;<input style="vertical-align:middle;" type="button" onclick="AddSPFRecord('{$add[id]}', this);" name="spf" value="SPF constructor" class="btn">	
			</span>
		</td>
	</tr>
	{/section}
</table>
<input type="hidden" name="domainname" value="{$domainname}" />
<input type="hidden" name="ezone" value="{$ezone}" />
<input type="hidden" name="formadded" value="true" />
<input type='hidden' name='farmid' value="{$farm.id}" />
<input type='hidden' name='ami_id' value="{$ami_id}" />
{include file="inc/spf_constructor.tpl"}
{/if}
{include file="inc/table_footer.tpl" button2=1}
{include file="inc/footer.tpl"}