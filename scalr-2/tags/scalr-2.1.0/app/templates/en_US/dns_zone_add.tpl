{include file="inc/header.tpl"}
<script language="javascript" type="text/javascript">
{literal}
	function CheckPrAdd(tp, id, val)
	{
		Ext.get(tp+"_"+id+"_weight").dom.style.display = 'none';
		Ext.get(tp+"_"+id+"_port").dom.style.display = 'none';
		
		
		if (val == 'MX')
		{
			Ext.get(tp+"_"+id).dom.style.display = '';
			Ext.get(tp+"_"+id).dom.value = '10';
		}
		else if (val == 'SRV')
		{
			Ext.get(tp+"_"+id).dom.style.display = '';
			
			Ext.get(tp+"_"+id+"_weight").dom.style.display = '';
			Ext.get(tp+"_"+id+"_port").dom.style.display = '';
		}
		else if (val == 'TXT')
		{
			Ext.get(tp+"_"+id).dom.style.display = 'none';
			Ext.get(tp+"_"+id).dom.value = '';
		} 
		else
		{
			Ext.get(tp+"_"+id).dom.style.display = 'none';
			Ext.get(tp+"_"+id).dom.value = '';
		}
	}
	
	Ext.onReady(function () 
	{
		if (Ext.get('farm_target_combo'))
		{
		
			var farmsStore = new Ext.data.Store(
			{
				url: "/server/ajax-ui-server.php",
				baseParams: {action: "LoadFarms"},			
				reader: new Ext.ux.scalr.JsonReader(
				{
					root: 'data', // from php file: array("data" => $result);
					id: 'id',
					fields:['id','name']
				})
			});
	      
			var roleStore = new Ext.data.Store(
			{ 
				url: "/server/ajax-ui-server.php",
				baseParams: {action: "LoadFarmRoles"},			
				reader: new Ext.ux.scalr.JsonReader(
				{
					root: 'data', // from php file: array("data" => $result);
					id: 'id',					        	
					fields:['id','name']
				})
			});
			
			farmsCombo = new Ext.form.ComboBox(
			{
				renderTo:		'farm_target_combo',
				allowBlank:		true,
				width:			150,							
				hiddenName:		'farmid',
				store:			farmsStore,
				displayField:	'name',
				valueField:	    'id',
				typeAhead:		true,
				mode:			'local',
				triggerAction:	'all',
				emptyText:		'',
				
				selectOnFocus:	true,
				listeners:{
					select: function(combo, record, index)
					{	
						//  reset selected text in roleCombo
						if(roleCombo)
							roleCombo.clearValue();			   								
						
						// load farm roles of selected farm by farmId ( from farmsStore comboBox)			
			   			roleStore.baseParams.farmId = record.data.id; 
			   				roleStore.load();
			   				
			   			Ext.get('farmid').dom.value = record.data.id;
		   			}   
		   		}
			});
		
			roleCombo = new Ext.form.ComboBox(
			{
				renderTo:		'role_target_combo',
				allowBlank:		true,
				width:			150,							
				hiddenName:		'farm_roleid',
				valueField:		'id',
				store:			roleStore,
				displayField:	'name',
				typeAhead:		true,
				farmsCombo:		farmsCombo,
				triggerAction:	'all',
				emptyText:		'',
				selectOnFocus:	true,
				listeners:{
					select:function(combo, record, index){
						Ext.get('farm_roleid').dom.value = record.data.id;
					}
				}
			});
			
			farmsStore.load();
		}
	});
	
{/literal}
</script>
<script type="text/javascript" src="js/class.NewPopup.js"></script>
<link href="css/popup.css" rel="stylesheet" type="text/css" />


{if (!$step || $step == 1) && !$edit}
	{include file="inc/table_header.tpl" nofilter=1}
    {include file="inc/intable_header.tpl" header="DNS zone information" color="Gray"}
    <tr>
		<td colspan="2" style="padding:5px;"><input style="vertical-align:middle;" type="radio" name="dns_domain_type" value="scalr" /> {t}Use domain automatically generated and provided by Scalr{/t}</td>
	</tr>
    <tr>
		<td style="padding:5px;"><input style="vertical-align:middle;" checked="checked" type="radio" name="dns_domain_type" value="own" /> {t}Use own domain name{/t}:</td>
		<td style="padding:5px;"><input type="text" class="text" name="domainname" style="width:310px;" /></td>
	</tr>
	<tr valign="top">
		<td style="padding:5px;">{t}Farm:{/t}</td>
		<td style="padding:5px;padding-left:10px;">
			<div id="farm_target_combo" style="float:left;margin-right:10px;"></div>
			<div style="padding-top:3px;font-style:italic;">Each server in this farm will add int-rolename ext-rolename records. Leave blank if you don't need such records.</div>
		</td>
	</tr>
	<tr valign="top">
		<td style="padding:5px;">{t}Role:{/t}</td>
		<td style="padding:5px;padding-left:10px;">
			<div id="role_target_combo" style="float:left;margin-right:10px;"></div>
			<div style="padding-top:3px;font-style:italic;">Servers of this role will create root records. Leave blank to add root records manually.</div>
		</td>
	</tr>
    {include file="inc/intable_footer.tpl" color="Gray"}
    {assign var=button2_name value="Next"}
{elseif $step == 2 || $edit }
	{include file="inc/table_header.tpl" nofilter=1}
	{include file="inc/intable_header.tpl" header="Application information" color="Gray"}
	<tr>
		<td style="padding:5px;">Domain name:</td>
		<td style="padding:5px;">{$domainname}</td>
	</tr>
	<tr>
		<td style="padding:5px;">SOA retry:</td>
		<td style="padding:5px;">
		<select name="zone[soa_retry]">
			<option {if $zone.soa_retry == 1800}selected{/if} value="1800">30 minutes</option>
			<option {if $zone.soa_retry == 3600}selected{/if} value="3600">1 hour</option>
			<option {if $zone.soa_retry == 7200 || !$zone.soa_retry}selected{/if} value="7200">2 hours</option>
			<option {if $zone.soa_retry == 14400}selected{/if} value="14400">4 hours</option>
			<option {if $zone.soa_retry == 28800}selected{/if} value="28800">8 hours</option>
			<option {if $zone.soa_retry == 86400}selected{/if} value="86400">1 day</option>
		</select>
		</td>
	</tr>
	<tr>
		<td style="padding:5px;">SOA refresh:</td>
		<td style="padding:5px;">
		<select name="zone[soa_refresh]">
			<option {if $zone.soa_refresh == 3600}selected{/if} value="3600">1 hour</option>
			<option {if $zone.soa_refresh == 7200}selected{/if} value="7200">2 hours</option>
			<option {if $zone.soa_refresh == 14400 || !$zone.soa_refresh}selected{/if} value="14400">4 hours</option>
			<option {if $zone.soa_refresh == 28800}selected{/if} value="28800">8 hours</option>
			<option {if $zone.soa_refresh == 86400}selected{/if} value="86400">1 day</option>
		</select>
		</td>
	</tr>
	<tr>
		<td style="padding:5px;">SOA expire:</td>
		<td style="padding:5px;">
		<select name="zone[soa_expire]">
			<option {if $zone.soa_expire == 86400}selected{/if} value="86400">1 day</option>
			<option {if $zone.soa_expire == 259200}selected{/if} value="259200">3 days</option>
			<option {if $zone.soa_expire == 432000}selected{/if} value="432000">5 days</option>
			<option {if $zone.soa_expire == 604800}selected{/if} value="604800">1 week</option>
			<option {if $zone.soa_expire == 3024000 || $zone.soa_expire == 3600000}selected{/if} value="3024000">5 weeks</option>
			<option {if $zone.soa_expire == 6048000}selected{/if} value="6048000">10 weeks</option>
		</select>
		</td>
	</tr>
	{include file="inc/intable_footer.tpl" color="Gray"}
	<table cellpadding="0" cellspacing="0" width="100%">
	<tr>
		<td width="20%" class="th" style="padding-left:26px;">{t}Domain{/t}</td>
		<td width="6%" class="th" style="padding-left:16px;">{t}TTL{/t}</td>
		<td width="6%" nowrap="nowrap" class="th">{t}Record Type{/t}</td>
		<td class="th" colspan="3" style="padding-left:16px;">{t}Record value{/t}<td>
	</tr>
	</table>
	{include file="inc/intable_header.tpl" header="DNS records" color="Gray"}
	{section name=id loop=$zone.records}
	<tr>
		<td width="20%"><input {if $zone.records[id].issystem == 1 && $zone.allow_manage_system_records == 0}disabled{/if} type="text" class="text" name="records[{$smarty.section.id.iteration}][name]" size=30 value="{$zone.records[id].name}"></td>
		<td width="6%"><input {if $zone.records[id].issystem == 1 && $zone.allow_manage_system_records == 0}disabled{/if} type="text" class="text" name="records[{$smarty.section.id.iteration}][ttl]" size=6 value="{$zone.records[id].ttl}"></td>
		<td width="6%"><select {if $zone.records[id].issystem == 1 && $zone.allow_manage_system_records == 0}disabled{/if} class="text" name="records[{$smarty.section.id.iteration}][type]" onchange="CheckPrAdd('ed', '{$smarty.section.id.iteration}', this.value)">
				<option {if $zone.records[id].type == "A"}selected{/if} value="A">A</option>
				<option {if $zone.records[id].type == "CNAME"}selected{/if} value="CNAME">CNAME</option>
				<option {if $zone.records[id].type == "MX"}selected{/if} value="MX">MX</option>
				<option {if $zone.records[id].type == "NS"}selected{/if} value="NS">NS</option>
				<option {if $zone.records[id].type == "TXT"}selected{/if} value="TXT">TXT</option>
				<option {if $zone.records[id].type == "SRV"}selected{/if} value="SRV">SRV</option>
			</select>
		</td>
		<td colspan="2"> 
			<input {if $zone.records[id].issystem == 1 && $zone.allow_manage_system_records == 0}disabled{/if} onclick="{literal}if (this.value == 'priority') { this.value=''; } {/literal}" id="ed_{$smarty.section.id.iteration}" size="5" style="display:{if $zone.records[id].type != "MX" && $zone.records[id].type != "SRV"}none{/if};" type="text" class="text" name="records[{$smarty.section.id.iteration}][priority]" value="{$zone.records[id].priority}" size=30> 
			<input {if $zone.records[id].issystem == 1 && $zone.allow_manage_system_records == 0}disabled{/if} onclick="{literal}if (this.value == 'weight') { this.value=''; } {/literal}" id="ed_{$smarty.section.id.iteration}_weight" size="5" style="display:{if $zone.records[id].type != "SRV"}none{/if};" type="text" class="text" name="records[{$smarty.section.id.iteration}][weight]" value="{$zone.records[id].weight}" size=30>
			<input {if $zone.records[id].issystem == 1 && $zone.allow_manage_system_records == 0}disabled{/if} onclick="{literal}if (this.value == 'port') { this.value=''; } {/literal}" id="ed_{$smarty.section.id.iteration}_port" size="5" style="display:{if $zone.records[id].type != "SRV"}none{/if};" type="text" class="text" name="records[{$smarty.section.id.iteration}][port]" value="{$zone.records[id].port}" size=30>
			<input {if $zone.records[id].issystem == 1 && $zone.allow_manage_system_records == 0}disabled{/if} class="text" type=text id="zone[records][{$smarty.section.id.iteration}][rvalue]" name="records[{$smarty.section.id.iteration}][value]" size=60 value="{$zone.records[id].value}">
		</td>
	</tr>
	{sectionelse}
	<tr>
		<td colspan="20" align="center">No DNS records defined</td>
	</tr>
	{/section}
	{include file="inc/intable_footer.tpl" color="Gray"}
	{include file="inc/intable_header.tpl" header="Add New DNS Records" color="Gray"}
	{section name=new_records start=1 loop=6 step=1}
	{assign var=new_record_id value=$smarty.section.new_records.index}
	<tr>
		<td width="20%"><input type="text" class="text" name="records[n{$new_record_id}][name]" size=30></td>
		<td width="6%"><input type="text" class="text" name="records[n{$new_record_id}][ttl]" size=6 value="14400"></td>
		<td width="6%"><select class="text" name="records[n{$new_record_id}][type]" onchange="CheckPrAdd('ad', '{$new_record_id}', this.value)">
				<option selected value="A">A</option>
				<option value="CNAME">CNAME</option>
				<option value="MX">MX</option>
				<option value="TXT">TXT</option>
				<option value="NS">NS</option>
				<option value="SRV">SRV</option>
			</select>
		</td>
		<td width="75%">
			<input onclick="{literal}if (this.value == 'priority') { this.value=''; } {/literal}" id="ad_{$new_record_id}" size="5" style="display:none;" type="text" class="text" name="records[n{$new_record_id}][priority]" value="priority" size=30> 
			<input onclick="{literal}if (this.value == 'weight') { this.value=''; } {/literal}" id="ad_{$new_record_id}_weight" size="5" style="display:none;" type="text" class="text" name="records[n{$new_record_id}][weight]" value="weight" size=30>
			<input onclick="{literal}if (this.value == 'port') { this.value=''; } {/literal}" id="ad_{$new_record_id}_port" size="5" style="display:none;" type="text" class="text" name="records[n{$new_record_id}][port]" value="port" size=30>
			<input type="text" class="text" id="records[{$new_record_id}][rvalue]" name="records[n{$new_record_id}][value]" size=60 />
		</td>
	</tr>
	{/section}
	{include file="inc/intable_footer.tpl" color="Gray"}
	<input type="hidden" name="step" value="2" />
	
	{if !$edit}
		{assign var=button2_name value="Create DNS zone"}
	{else}
		{assign var=button2_name value="Save"}
	{/if}
{/if}

	
{include file="inc/table_footer.tpl" button2=1}
{include file="inc/footer.tpl"}