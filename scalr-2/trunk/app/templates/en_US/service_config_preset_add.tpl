{include file="inc/header.tpl" noheader=1}
<script type="text/javascript" src="/js/ui-ng/data.js"></script>

<div id="form-config_preset-edit"></div>

<script type="text/javascript">
var id = '{$preset_id}';
var preset_name = '{$preset_name}';
var preset_role_behavior = '{$preset_role_behavior}';

{literal}
Ext.onReady(function () {
	Ext.override(Ext.form.Field, {
		getName: function () {
			return this.name || this.id || '';
		}
	});

	var form = new Ext.form.FormPanel({
		renderTo: "form-config_preset-edit",
		title: "Preset details",
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
						value: preset_name
					   },
					   {
						xtype: 'displayfield',
						name: 'service',
						fieldLabel: 'Service',
						value: preset_role_behavior
					   }
				]
	        },
			{
				xtype: 'fieldset',
				title: 'Config variables',
				labelWidth: 320,
				items: {/literal}{$extjs_form_items}{literal}
			}
		],
		buttonAlign: 'center',
		buttons: [{
			type: 'submit',
			text: 'Save',
			handler: function() {
				if (form.getForm().isValid()) {
					form.getForm().submit({
						url: '/service_config_preset_add.php',
						params: {
							preset_id: id
						},
						success: function(form, action) {
							document.location.href = '/service_config_presets.php';
						},
						failure: Scalr.data.ExceptionFormReporter
					});
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

	form.getForm().getEl().select('input').each(function(el) {

		if (el.dom.name != 'name')
			el.dom.name = 'var[' + el.dom.name + ']';
	});
});
{/literal}
</script>
{include file="inc/footer.tpl"}
