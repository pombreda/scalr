{include file="inc/header.tpl"}
<script language="javascript" type="text/javascript">
{literal}
	function CheckPrAdd(tp, id, val)
	{
		if (val == 'MX')
		{
			$(tp+"_"+id).style.display = '';
			$(tp+"_"+id).value = '10';
		}
		else
		{
			$(tp+"_"+id).style.display = 'none';
			$(tp+"_"+id).value = '';
		}
	}
{/literal}
</script>

<p class="placeholder">You can use a %hostname% tag, which will be replaced with full zone hostname.</p>

{include file="inc/table_header.tpl"}
<table cellpadding="4" cellspacing="0" width="100%" border="0">
	<tr>
		<td class="th" width="300">Domain</td>
		<td class="th" width="150">TTL</td>
		<td class="th" width="50">&nbsp;</td>
		<td class="th" width="150">Record Type</td>
		<td class="th" colspan="2">Record value</td>
	</tr>
	{section name=id loop=$zone.records}
	<tr>
		<td><input {if $zone.records[id].issystem == 1}disabled{/if} type="text" class="text" name="zone[records][{$zone.records[id].id}][rkey]" size=30 value="{$zone.records[id].rkey}"></td>
		<td><input {if $zone.records[id].issystem == 1}disabled{/if} type="text" class="text" name="zone[records][{$zone.records[id].id}][ttl]" size=6 value="{$zone.records[id].ttl}"></td>
		<td>IN</td>
		<td><select {if $zone.records[id].issystem == 1}disabled{/if} class="text" name="zone[records][{$zone.records[id].id}][rtype]" onchange="CheckPrAdd('ed', '{$zone.records[id].id}', this.value)">
				<option {if $zone.records[id].rtype == "A"}selected{/if} value="A">A</option>
				<option {if $zone.records[id].rtype == "CNAME"}selected{/if} value="CNAME">CNAME</option>
				<option {if $zone.records[id].rtype == "MX"}selected{/if} value="MX">MX</option>
				<option {if $zone.records[id].rtype == "NS"}selected{/if} value="NS">NS</option>
			</select>
		</td>
		<td colspan="2"> <input {if $zone.records[id].issystem == 1}disabled{/if} class="text" id="ed_{$zone.records[id].id}" style="display:{if $zone.records[id].rtype != "MX"}none{/if};" type=text name="zone[records][{$zone.records[id].id}][rpriority]" size=5 value="{$zone.records[id].rpriority}"> <input {if $zone.records[id].issystem == 1}disabled{/if} class="text" type=text name="zone[records][{$zone.records[id].id}][rvalue]" size=30 value="{$zone.records[id].rvalue}"></td>
	</tr>
	{sectionelse}
	<tr>
		<td colspan=6 align="center">No default DNS records found</td>
	</tr>
	{/section}
	<tr>
		<td colspan=6>&nbsp;</td>
	</tr>
	<tr>
		<td colspan=6 class="th">Add New Entries Below this Line</td>
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
			</select>
		</td>
		<td colspan="2"> <input id="ad_{$add[id]}" size="5" style="display:none;" type="text" class="text" name="add[{$add[id]}][rpriority]" value="10" size=30> <input type="text" class="text" name="add[{$add[id]}][rvalue]" size=30></td>
	</tr>
	{/section}
</table>
{include file="inc/table_footer.tpl" edit_page=1}
{include file="inc/footer.tpl"}