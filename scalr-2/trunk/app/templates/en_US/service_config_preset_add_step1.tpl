{include file="inc/header.tpl" noheader=1}
<script type="text/javascript" src="/js/ui-ng/data.js"></script>

<div id="form-config_preset-add"></div>

<script type="text/javascript">
{literal}
Ext.onReady(function () {
	Ext.override(Ext.form.Field, {
		getName: function () {
			return this.name || this.id || '';
		}
	});
	
	var form = new Ext.form.FormPanel({
		renderTo: "form-config_preset-add",
		title: "Add new config preset - Step 1",
		frame: true,
		fileUpload: false,
		labelWidth: 200,
		items: [
	        {
				xtype: 'fieldset',
				title: 'Preset details',
				labelWidth: 320,
				items:[
				       {
						xtype: 'textfield',
						name: 'name',
						fieldLabel: 'Name',
						width:200,
						value: '{/literal}{$preset_name}{literal}'
					   },
					   {
						xtype: 'combo',
						name: 'role_behavior',
						fieldLabel: 'Service',
						width:200,
						typeAhead:false,
						selectOnFocus:false,
						forceSelection:true,
						triggerAction:'all',
						editable:false,
						emptyText:'Please select service...',
						value: '{/literal}{if $role_behavior}{$role_behavior}{else}mysql{/if}{literal}',
						mode:'local',
						store:new Ext.data.ArrayStore({
							id:0,
							fields: ['rid','title'],
							data:[['mysql','MySQL'],['app','Apache'],['memcached','Memcached'],['cassandra','Cassandra'],['www','Nginx']]
						}),
						valueField:'rid',
						displayField:'title',
						hiddenName:'role_behavior'
					   }
				]
	        }
		],
		buttonAlign: 'center',
		standardSubmit:true,
		url: '/service_config_preset_add.php',
		buttons: [{
			type: 'submit',
			text: 'Continue',
			handler: function() {
				if (form.getForm().isValid()) {
					form.getForm().submit();
				}
			}
		}, {
			type: 'reset',
			text: 'Cancel',
			handler: function() {
				document.location.href = '/service_config_presets.php';
			}
		}]
	});
});
{/literal}
</script>
{include file="inc/footer.tpl"}
