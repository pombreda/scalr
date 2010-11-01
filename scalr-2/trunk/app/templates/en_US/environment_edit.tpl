{include file="inc/header.tpl" noheader=1}
<script type="text/javascript" src="/js/ui-ng/data.js"></script>

<div id="form-environment-edit-view"></div>

<script type="text/javascript">
var title = '{$env->name}';
var id = '{$env->id}';
var params = eval({$envParams});
var timezones = eval({$timezones});

{literal}
Ext.onReady(function () {
	Ext.override(Ext.form.Field, {
		getName: function () {
			return this.name || this.id || '';
		}
	});

	var form = new Ext.form.FormPanel({
		renderTo: "form-environment-edit-view",
		title: title,
		frame: true,
		fileUpload: true,
		labelWidth: 200,
		items: [
			{
				xtype: 'fieldset',
				title: 'API settings',
				labelWidth: 200,
				items: [{
					xtype: 'compositefield',
					hideLabel: true,
					items: [ {
						xtype:'checkbox',
						name:'api.enabled',
						value:1,
						checked:params['api.enabled']

					}, {
						xtype:'displayfield',
						cls: 'x-form-check-wrap',
						value:'Enable API for this environment'
					}]
				}, {
					xtype: 'textfield',
					name: 'api.keyid',
					fieldLabel: 'API Key ID',
					allowBlank: false,
					readOnly:true,
					width: 295,
					value: params['api.keyid']
				}, {
					xtype: 'textarea',
					name: 'api.access_key',
					fieldLabel: 'API Access Key',
					allowBlank: false,
					readOnly:true,
					width: 295,
					height:100,
					value: params['api.access_key']
				}, {
					xtype: 'compositefield',
					hideLabel: true,
					items: [ {
						xtype:'displayfield',
						cls: 'x-form-check-wrap',
						value:'<br />Allow access to the API only from the following IPs (coma separated).<br />Example: 67.45.3.7, 67.46.*.*, 91.*.*.*'
					}]
				}, {
						xtype:'textarea',
						hideLabel: true,
						name:'api.allowed_ips',
						width:500,
						height:100,
						value:params['api.allowed_ips']
				}]
			},
			{
			xtype: 'fieldset',
			title: 'System settings',
			labelWidth: 200,
			items: [{
				xtype: 'compositefield',
				hideLabel: true,
				items: [{
					xtype:'displayfield',
					cls: 'x-form-check-wrap',
					value:'Automatically abort instance synchronization if it does not complete in'
				}, {
					xtype: 'textfield',
					name: 'sync_timeout',
					allowBlank: false,
					width: 50,
					value: params['sync_timeout']
				}, {
					xtype: 'displayfield',
					cls: 'x-form-check-wrap',
					value: 'minutes.'
				}]
			}, {
				xtype: 'compositefield',
				fieldLabel: 'Instances limit',
				items: [{
					xtype: 'textfield',
					name: 'client_max_instances',
					allowBlank: false,
					width: 50,
					value: params['client_max_instances']
				}, {
					html: '<img src="/images/ui-ng/icons/info_icon_16x16.png" style="padding: 2px; cursor: help;" id="feev-client-max-instances">'
				}]
			}, {
				xtype: 'compositefield',
				fieldLabel: 'Elastic IPs limit',
				items: [{
					xtype: 'textfield',
					name: 'client_max_eips',
					allowBlank: false,
					width: 50,
					value: params['client_max_eips']
				}, {
					html: '<img src="/images/ui-ng/icons/info_icon_16x16.png" style="padding: 2px; cursor: help;" id="feev-client-max-eips">'
				}]
			}]
		}, {
			xtype: 'fieldset',
			title: 'Date & Time settings',
			items: [{
				xtype: 'combo',
				fieldLabel: 'Timezone',
				store: timezones,
				allowBlank: false,
				editable: true,
				name: 'timezone',
				value: params['timezone'],
				typeAhead: true,
				forceSelection: true,
				mode: 'local',
				triggerAction: 'all',
				selectOnFocus: false,
				width: 300
			}]
		}, {
			xtype: 'tabpanel',
			activeTab: 0,
			deferredRender: false,
			defaults: {
				style: 'padding:10px',
				autoHeight: true
			},
			items: [{
				title: 'AWS EC2',
				layout: 'form',
				items: [{
					xtype: 'checkbox',
					name: 'ec2.is_enabled',
					checked: params['ec2.is_enabled'],
					hideLabel: true,
					boxLabel: 'Enable platform',
					listeners: {
						'check': function (checkbox, checked) {
							if (checked) {
								form.getForm().findField('ec2.account_id').enable();
								form.getForm().findField('ec2.access_key').enable();
								form.getForm().findField('ec2.secret_key').enable();
								form.getForm().findField('ec2.certificate').enable();
								form.getForm().findField('ec2.private_key').enable();
								if (form.getForm().findField('rds.is_enabled').checked)
									form.getForm().findField('rds.the_same_as_ec2').enable();
							} else {
								form.getForm().findField('ec2.account_id').disable();
								form.getForm().findField('ec2.access_key').disable();
								form.getForm().findField('ec2.secret_key').disable();
								form.getForm().findField('ec2.certificate').disable();
								form.getForm().findField('ec2.private_key').disable();
								form.getForm().findField('rds.the_same_as_ec2').setValue(false);
								form.getForm().findField('rds.the_same_as_ec2').disable();
							}
						}
					}
				}, {
					xtype: 'textfield',
					fieldLabel: 'Account ID',
					width: 320,
					name: 'ec2.account_id',
					value: params['ec2.account_id'],
					disabled: !params['ec2.is_enabled']
				}, {
					xtype: 'textfield',
					fieldLabel: 'Access Key',
					width: 320,
					name: 'ec2.access_key',
					value: params['ec2.access_key'],
					disabled: !params['ec2.is_enabled']
				}, {
					xtype: 'textfield',
					fieldLabel: 'Secret Key',
					width: 320,
					name: 'ec2.secret_key',
					value: params['ec2.secret_key'],
					disabled: !params['ec2.is_enabled']
				}, {
					xtype: 'textfield',
					inputType: 'file',
					fieldLabel: 'X.509 Certificate file',
					name: 'ec2.certificate',
					disabled: !params['ec2.is_enabled']
				}, {
					xtype: 'textfield',
					inputType: 'file',
					fieldLabel: 'X.509 Private Key file',
					name: 'ec2.private_key',
					disabled: !params['ec2.is_enabled']
				}]
			}, {
				title: 'AWS RDS',
				layout: 'form',
				items: [{
					xtype: 'checkbox',
					name: 'rds.is_enabled',
					checked: params['rds.is_enabled'] && params['ec2.is_enabled'],
					hideLabel: true,
					boxLabel: 'Enable platform',
					listeners: {
						'check': function (checkbox, checked) {
							if (checked && form.getForm().findField('ec2.is_enabled').checked)
								form.getForm().findField('rds.the_same_as_ec2').enable();
							else
								form.getForm().findField('rds.the_same_as_ec2').disable();

							if (checked && !form.getForm().findField('rds.the_same_as_ec2').checked) {
								form.getForm().findField('rds.account_id').enable();
								form.getForm().findField('rds.access_key').enable();
								form.getForm().findField('rds.secret_key').enable();
								form.getForm().findField('rds.certificate').enable();
								form.getForm().findField('rds.private_key').enable();
							} else {
								form.getForm().findField('rds.account_id').disable();
								form.getForm().findField('rds.access_key').disable();
								form.getForm().findField('rds.secret_key').disable();
								form.getForm().findField('rds.certificate').disable();
								form.getForm().findField('rds.private_key').disable();
							}
						}
					}
				}, {
					xtype: 'checkbox',
					name: 'rds.the_same_as_ec2',
					checked: params['rds.the_same_as_ec2'],
					hideLabel: true,
					boxLabel: 'Use the same as EC2',
					disabled: !params['rds.is_enabled'],
					listeners: {
						'check': function (checkbox, checked) {
							if (! checked) {
								form.getForm().findField('rds.account_id').enable();
								form.getForm().findField('rds.access_key').enable();
								form.getForm().findField('rds.secret_key').enable();
								form.getForm().findField('rds.certificate').enable();
								form.getForm().findField('rds.private_key').enable();
							} else {
								form.getForm().findField('rds.account_id').disable();
								form.getForm().findField('rds.access_key').disable();
								form.getForm().findField('rds.secret_key').disable();
								form.getForm().findField('rds.certificate').disable();
								form.getForm().findField('rds.private_key').disable();
							}
						}
					}
				}, {
					xtype: 'textfield',
					fieldLabel: 'Account ID',
					width: 320,
					name: 'rds.account_id',
					value: params['rds.account_id'],
					disabled: !params['rds.is_enabled'] || params['rds.the_same_as_ec2']
				}, {
					xtype: 'textfield',
					fieldLabel: 'Access Key',
					width: 320,
					name: 'rds.access_key',
					value: params['rds.access_key'],
					disabled: !params['rds.is_enabled'] || params['rds.the_same_as_ec2']
				}, {
					xtype: 'textfield',
					fieldLabel: 'Secret Key',
					width: 320,
					name: 'rds.secret_key',
					value: params['rds.secret_key'],
					disabled: !params['rds.is_enabled'] || params['rds.the_same_as_ec2']
				}, {
					xtype: 'textfield',
					inputType: 'file',
					fieldLabel: 'X.509 Certificate file',
					name: 'rds.certificate',
					disabled: !params['rds.is_enabled'] || params['rds.the_same_as_ec2']
				}, {
					xtype: 'textfield',
					inputType: 'file',
					fieldLabel: 'X.509 Private Key file',
					name: 'rds.private_key',
					disabled: !params['rds.is_enabled'] || params['rds.the_same_as_ec2']
				}]
			}, /*{
				title: 'Rackspace',
				layout: 'form',
				items: [{
					xtype: 'checkbox',
					name: 'rackspace.is_enabled',
					checked: params['rackspace.is_enabled'],
					hideLabel: true,
					boxLabel: 'Enable platform',
					listeners: {
						'check': function (checkbox, checked) {
							if (checked) {
								form.getForm().findField('rackspace.username').enable();
								form.getForm().findField('rackspace.api_key').enable();
							} else {
								form.getForm().findField('rackspace.username').disable();
								form.getForm().findField('rackspace.api_key').disable();
							}
						}
					}
				}, {
					xtype: 'textfield',
					fieldLabel: 'Username',
					name: 'rackspace.username',
					value: params['rackspace.username'],
					disabled: !params['rackspace.is_enabled']
				}, {
					xtype: 'textfield',
					fieldLabel: 'API Key',
					name: 'rackspace.api_key',
					value: params['rackspace.api_key'],
					disabled: !params['rackspace.is_enabled']
				}]
			},*/ {
				title: 'Eucalyptus',
				layout: 'form',
				items: [{
					xtype: 'checkbox',
					name: 'eucalyptus.is_enabled',
					checked: params['eucalyptus.is_enabled'],
					hideLabel: true,
					boxLabel: 'Enable platform',
					listeners: {
						'check': function (checkbox, checked) {
							if (checked) {
								form.getForm().findField('eucalyptus.account_id').enable();
								form.getForm().findField('eucalyptus.access_key').enable();
								form.getForm().findField('eucalyptus.ec2_url').enable();
								form.getForm().findField('eucalyptus.s3_url').enable();
								form.getForm().findField('eucalyptus.secret_key').enable();
								form.getForm().findField('eucalyptus.certificate').enable();
								form.getForm().findField('eucalyptus.cloud_certificate').enable();
								form.getForm().findField('eucalyptus.private_key').enable();
							} else {
								form.getForm().findField('eucalyptus.account_id').disable();
								form.getForm().findField('eucalyptus.access_key').disable();
								form.getForm().findField('eucalyptus.ec2_url').disable();
								form.getForm().findField('eucalyptus.s3_url').disable();
								form.getForm().findField('eucalyptus.secret_key').disable();
								form.getForm().findField('eucalyptus.certificate').disable();
								form.getForm().findField('eucalyptus.cloud_certificate').disable();
								form.getForm().findField('eucalyptus.private_key').disable();
							}
						}
					}
				}, {
					xtype: 'textfield',
					fieldLabel: 'Account ID',
					width:320,
					name: 'eucalyptus.account_id',
					value: params['eucalyptus.account_id'],
					disabled: !params['eucalyptus.is_enabled']
				}, {
					xtype: 'textfield',
					fieldLabel: 'Access Key',
					width:320,
					name: 'eucalyptus.access_key',
					value: params['eucalyptus.access_key'],
					disabled: !params['eucalyptus.is_enabled']
				}, {
					xtype: 'textfield',
					fieldLabel: 'Secret Key',
					name: 'eucalyptus.secret_key',
					width:320,
					value: params['eucalyptus.secret_key'],
					disabled: !params['eucalyptus.is_enabled']
				}, {
					xtype: 'textfield',
					width:320,
					fieldLabel: 'EC2 URL (eg. http://192.168.1.1:8773/services/Eucalyptus)',
					name: 'eucalyptus.ec2_url',
					value: params['eucalyptus.ec2_url'],
					disabled: !params['eucalyptus.is_enabled']
				}, {
					xtype: 'textfield',
					fieldLabel: 'S3 URL (eg. http://192.168.1.1:8773/services/Walrus)',
					width:320,
					name: 'eucalyptus.s3_url',
					value: params['eucalyptus.s3_url'],
					disabled: !params['eucalyptus.is_enabled']
				}, {
					xtype: 'textfield',
					inputType: 'file',
					fieldLabel: 'X.509 Certificate file',
					name: 'eucalyptus.certificate',
					disabled: !params['eucalyptus.is_enabled']
				}, {
					xtype: 'textfield',
					inputType: 'file',
					fieldLabel: 'X.509 Private Key file',
					name: 'eucalyptus.private_key',
					disabled: !params['eucalyptus.is_enabled']
				}, {
					xtype: 'textfield',
					inputType: 'file',
					fieldLabel: 'X.509 Cloud certificate file',
					name: 'eucalyptus.cloud_certificate',
					disabled: !params['eucalyptus.is_enabled']
				}]
			}]
		}],
		buttonAlign: 'center',
		buttons: [{
			type: 'submit',
			text: 'Save',
			handler: function() {
				if (form.getForm().isValid()) {
					form.getForm().submit({
						url: '/environment_edit.php',
						params: {
							env_id: id
						},
						success: function(form, action) {
							document.location.href = '/environments.php';
						},
						failure: Scalr.data.ExceptionFormReporter
					});
				}
			}
		}, {
			type: 'reset',
			text: 'Cancel',
			handler: function() {
				document.location.href = '/environments.php';
			}
		}]
	});

	form.getForm().getEl().select('input').each(function(el) {
		el.dom.name = 'var[' + el.dom.name + ']';
	});

	form.getForm().getEl().select('textarea').each(function(el) {
		el.dom.name = 'var[' + el.dom.name + ']';
	});
	
	new Ext.ToolTip({
		target: 'feev-client-max-instances',
		dismissDelay: 0,
		html: "You need to ask Amazon (aws@amazon.com) to increase instances limit for you before increasing this value."
	});

	new Ext.ToolTip({
		target: 'feev-client-max-eips',
		dismissDelay: 0,
		html: "By default, every AWS account can allocate maximum 5 Elastic IPs. If you're already using Elastic IPs outside Scalr, make sure to substract this amount, otherwise IPs will be reassigned to Scalr instances without any prompt."
	});
});
{/literal}
</script>
{include file="inc/footer.tpl"}
