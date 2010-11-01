{include file="inc/header.tpl" noheader=1}
<script type="text/javascript" src="/js/ui-ng/data.js"></script>

<div id="form-scaling-metric-edit"></div>

<script type="text/javascript">
var id = '{$metric->id}';
var metric_name = '{$metric->name}';
var metric_file_path = '{$metric->filePath}';
var retrieve_method = '{if $metric}{$metric->retrieveMethod}{else}read{/if}';
var calc_function = '{if $metric}{$metric->calcFunction}{else}avg{/if}';

{literal}
Ext.onReady(function () {
	Ext.override(Ext.form.Field, {
		getName: function () {
			return this.name || this.id || '';
		}
	});

	var form = new Ext.form.FormPanel({
		renderTo: "form-scaling-metric-edit",
		title: "Custom scaling metric",
		frame: true,
		fileUpload: false,
		labelWidth: 200,
		items: [
	        {
				xtype: 'fieldset',
				title: 'General information',
				labelWidth: 320,
				items:[
				       {
						xtype: 'textfield',
						name: 'name',
						width:200,
						fieldLabel: 'Name',
						value: metric_name
					   },
					   {
						xtype: 'textfield',
						name: 'file_path',
						width:200,
						fieldLabel: 'File path',
						value: metric_file_path
					   },
					   {
					    xtype: 'combo',
						name: 'retrieve_method',
						fieldLabel: 'Retrieve method',
						width:100,
						typeAhead:false,
						selectOnFocus:false,
						forceSelection:true,
						triggerAction:'all',
						editable:false,
						value: retrieve_method,
						mode:'local',
						store:new Ext.data.ArrayStore({
							id:0,
							fields: ['rid','title'],
							data:[['read','File-Read'],['execute','File-Execute']]
						}),
						valueField:'rid',
						displayField:'title',
						hiddenName:'retrieve_method'
					   },
					   {
					    xtype: 'combo',
						name: 'calc_function',
						fieldLabel: 'Calculation function',
						width:100,
						typeAhead:false,
						selectOnFocus:false,
						forceSelection:true,
						triggerAction:'all',
						editable:false,
						value: calc_function,
						mode:'local',
						store:new Ext.data.ArrayStore({
							id:0,
							fields: ['rid','title'],
							data:[['avg','Average'],['sum','Sum']]
						}),
						valueField:'rid',
						displayField:'title',
						hiddenName:'calc_function'
					   }
				]
	        }
		],
		buttonAlign: 'center',
		buttons: [{
			type: 'submit',
			text: 'Save',
			handler: function() {
				if (form.getForm().isValid()) {
					form.getForm().submit({
						url: '/scaling_metric_add.php',
						params: {
							metric_id: id
						},
						success: function(form, action) {
							document.location.href = '/scaling_metrics.php';
						},
						failure: Scalr.data.ExceptionFormReporter
					});
				}
			}
		}, {
			type: 'reset',
			text: 'Cancel',
			handler: function() {
				document.location.href = '/scaling_metrics.php';
			}
		}]
	});
});
{/literal}
</script>
{include file="inc/footer.tpl"}
