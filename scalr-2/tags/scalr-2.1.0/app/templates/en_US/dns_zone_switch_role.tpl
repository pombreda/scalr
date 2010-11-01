{include file="inc/header.tpl"}
<script language="javascript" type="text/javascript">
	{literal}
	Ext.onReady(function () 
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
			width:			150,							
			hiddenName:		'farmid',
			store:			farmsStore,
			displayField:	'name',
			valueField:	    'id',
			typeAhead:		true,
			mode:			'local',
			triggerAction:	'all',
			emptyText:		'Select farm...',
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
			width:			150,							
			hiddenName:		'farm_roleid',
			valueField:		'id',
			store:			roleStore,
			displayField:	'name',
			typeAhead:		true,
			farmsCombo:		farmsCombo,
			triggerAction:	'all',
			emptyText:		'Select role...',
			selectOnFocus:	true,
			listeners:{
				select:function(combo, record, index){
					Ext.get('farm_roleid').dom.value = record.data.id;
				}
			}
		});
		
		farmsStore.load();
	});
	{/literal}
</script>

	{include file="inc/table_header.tpl"}
		{include file="inc/intable_header.tpl" header="General" color="Gray"}
    	<tr>
    		<td width="20%" style="padding:5px;">{t}DNS zone{/t}:</td>
    		<td style="padding:5px;">{$zone_name}</td>
    	</tr>
    	{if $farm_name}
    	<tr>
    		<td width="20%" style="padding:5px;">{t}Current Farm{/t}:</td>
    		<td style="padding:5px;">{$farm_name}</td>
    	</tr>
    	<tr>
    		<td width="20%" style="padding:5px;">{t}Current Role{/t}:</td>
    		<td style="padding:5px;">{$role_name}</td>
    	</tr>
    	{/if}
    	<tr>
    		<td colspan="2" style="padding:5px;">&nbsp;</td>
    	</tr>
    	<tr valign="top">
			<td style="padding:5px;">{t}New Farm:{/t}</td>
			<td style="padding:5px;padding-left:10px;">
				<div id="farm_target_combo" style="float:left;margin-right:10px;"></div>
				<div style="padding-top:3px;font-style:italic;">Each server in this farm will add int-rolename ext-rolename records. Leave blank if you don't need such records.</div>
			</td>
		</tr>
		<tr valign="top">
			<td style="padding:5px;">{t}New Role:{/t}</td>
			<td style="padding:5px;padding-left:10px;">
				<div id="role_target_combo" style="float:left;margin-right:10px;"></div>
				<div style="padding-top:3px;font-style:italic;">Servers of this role will create root records. Leave blank to add root records manually.</div>
			</td>
		</tr>
	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
        <input type="hidden" name="zone_id" value="{$zone_id}" />
	{include file="inc/table_footer.tpl" button2=1 button2_name="Switch"}
{include file="inc/footer.tpl"}