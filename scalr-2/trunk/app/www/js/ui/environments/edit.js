{
	create: function (loadParams, moduleParams) {
		var params = moduleParams['params'];
		var form = new Ext.form.FormPanel({
			scalrOptions: {
				'maximize': 'height'
			},
			width: 700,
			title: 'Environments &raquo; Edit &raquo; ' + moduleParams.env.name,
			frame: true,
			fileUpload: true,
			labelWidth: 200,
			autoScroll: true,
			padding: '0px 20px 0px 5px',
			plugins: [ new Scalr.Viewers.Plugins.findOne() ],
			buttonAlign: 'center',
			items: [{
				xtype: 'fieldset',
				title: 'API settings',
				collapsible: true,
				collapsed: true,
				deferredRender: false,
				forceLayout: true,
				labelWidth: 100,
				defaults: {
					anchor: '-20'
				},
				items: [{
					xtype: 'compositefield',
					hideLabel: true,
					items: [{
						xtype:'checkbox',
						name:'api.enabled',
						value: 1,
						checked: params['api.enabled']
					}, {
						xtype:'displayfield',
						cls: 'x-form-check-wrap',
						value:'Enable API for this environment'
					}]
				}, {
					xtype: 'textfield',
					name: 'api.keyid',
					fieldLabel: 'API Key ID',
					readOnly:true,
					width: 295,
					value: params['api.keyid']
				}, {
					xtype: 'textarea',
					name: 'api.access_key',
					fieldLabel: 'API Access Key',
					readOnly:true,
					width: 295,
					height:100,
					value: params['api.access_key']
				}, {
					xtype: 'compositefield',
					hideLabel: true,
					items: [{
						xtype:'displayfield',
						cls: 'x-form-check-wrap',
						value:'<br />API access whitelist (by IP address)<br />Example: 67.45.3.7, 67.46.*.*, 91.*.*.*'
					}]
				}, {
					xtype:'textarea',
					hideLabel: true,
					name:'api.allowed_ips',
					width: 500,
					height: 100,
					value: params['api.allowed_ips']
				}]
			}, {
				xtype: 'fieldset',
				title: 'System settings',
				defaults: {
					anchor: '-20'
				},
				labelWidth: 100,
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
				labelWidth: 100,
				title: 'Date & Time settings',
				items: [{
					anchor: '-20',
					xtype: 'combo',
					fieldLabel: 'Timezone',
					store: moduleParams.timezones,
					allowBlank: false,
					editable: true,
					name: 'timezone',
					value: params['timezone'],
					typeAhead: true,
					forceSelection: true,
					mode: 'local',
					triggerAction: 'all',
					selectOnFocus: false
				}]
			}, {
				xtype: 'tabpanel',
				activeTab: 0,
				deferredRender: false,
				forceLayout: true,
				defaults: {
					style: 'padding: 10px',
					autoHeight: true
				},
				items: [{
					title: 'AWS EC2',
					layout: 'form',
					defaults: {
						anchor: '-20'
					},
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
					defaults: {
						anchor: '-20'
					},
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
				}, {
					title: 'Rackspace',
					layout: 'form',
					defaults: {
						anchor: '-20'
					},
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
				}, {
					title: 'Nimbula',
					layout: 'form',
					defaults: {
						anchor: '-20'
					},
					items: [{
						xtype: 'checkbox',
						name: 'nimbula.is_enabled',
						checked: params['nimbula.is_enabled'],
						hideLabel: true,
						boxLabel: 'Enable platform',
						listeners: {
							'check': function (checkbox, checked) {
								if (checked) {
									form.getForm().findField('nimbula.username').enable();
									form.getForm().findField('nimbula.api_url').enable();
									form.getForm().findField('nimbula.password').enable();
								} else {
									form.getForm().findField('nimbula.username').disable();
									form.getForm().findField('nimbula.api_url').disable();
									form.getForm().findField('nimbula.password').disable();
								}
							}
						}
					}, {
						xtype: 'textfield',
						fieldLabel: 'Username',
						name: 'nimbula.username',
						value: params['nimbula.username'],
						disabled: !params['nimbula.is_enabled']
					}, {
						xtype: 'textfield',
						fieldLabel: 'Password',
						name: 'nimbula.password',
						value: params['nimbula.password'],
						disabled: !params['nimbula.is_enabled']
					}, {
						xtype: 'textfield',
						fieldLabel: 'API URL',
						name: 'nimbula.api_url',
						value: params['nimbula.api_url'],
						disabled: !params['nimbula.is_enabled']
					}]
				}, {
					title: 'Eucalyptus',
					itemId: 'eucalyptus',
					layout: 'card',
					labelWidth: 300,
					activeItem: 0,
					style: {
						'padding': '0px'
					},
					tbar: [{
						icon: '/images/add.png',
						itemId: 'add'
					}],
					items: new Scalr.Viewers.list.ListView({
						autoHeight: true,
						deferEmptyText: false,
						columnHide: false,
						columnSort: false,
						store: new Ext.data.JsonStore({
							idProperty: 'region',
							fields: [ 'region', 'account_id', 'ec2_url']
						}),
						itemId: 'eucalyptus-listview',
						emptyText: 'No cloud locations found',
						columns: [
							{ header: "Cloud Location", width: '130px', dataIndex: 'region', hidden: 'no' },
							{ header: "Account ID", width: 50, dataIndex: 'account_id', hidden: 'no' },
							{ header: "EC2 URL", width: 50, dataIndex: 'ec2_url', hidden: 'no' },
							{ header: "&nbsp;", width: '20px', dataIndex: 'region-edit', hidden: 'no', tpl: '<img src="/images/ui-ng/icons/edit_icon_16x16.png">',
								clickHandler: function (comp, store, record) {
									var c = comp.ownerCt.find("regionName", record.get('region'));
									comp.ownerCt.layout.setActiveItem(c[0]);
									form.el.mask();
								}
							},
							{ header: "&nbsp;", width: '20px', dataIndex: 'region-delete', hidden: 'no', tpl: '<img src="/images/ui-ng/icons/delete_icon_16x16.png">',
								clickHandler: function (comp, store, record) {
									Ext.Msg.confirm('Delete cloud location', 'Are you sure want to delete "' + record.get('region') + '" cloud location?', function (button) {
										if (button == 'yes') {
											var c = comp.ownerCt.find("regionName", record.get('region'));
											comp.ownerCt.remove(c[0]);
											store.remove(record);
										}
									});
								}
							}
						]
					}),
					addCardTab: function (region, values) {
						var store = this.getComponent('eucalyptus-listview').store, record = new store.recordType({
							region: region,
							account_id: values['eucalyptus.account_id'] || '',
							ec2_url: values['eucalyptus.ec2_url'] || ''
						});
						store.add(record);

						form.el.mask();

						return this.add({
							layout: 'form',
							style: {
								'z-index': 200,
								'position': 'relative',
								'padding': '10px'
							},
							defaults: {
								anchor: '-20'
							},
							regionName: region,
							autoHeight: true,
							items: [{
								xtype: 'textfield',
								readOnly: true,
								fieldLabel: 'Cloud Location',
								width: 320,
								value: region
							}, {
								xtype: 'textfield',
								fieldLabel: 'Account ID',
								width: 320,
								name: 'eucalyptus.account_id.' + region,
								value: values['eucalyptus.account_id'] || '',
								listeners: {
									change: function (field, newValue) {
										record.set('account_id', newValue);
									}
								}
							}, {
								xtype: 'textfield',
								fieldLabel: 'Access Key',
								width: 320,
								name: 'eucalyptus.access_key.' + region,
								value: values['eucalyptus.access_key'] || ''
							}, {
								xtype: 'textfield',
								fieldLabel: 'Secret Key',
								name: 'eucalyptus.secret_key.' + region,
								width: 320,
								value: values['eucalyptus.secret_key'] || ''
							}, {
								xtype: 'textfield',
								width:320,
								fieldLabel: 'EC2 URL (eg. http://192.168.1.1:8773/services/Eucalyptus)',
								name: 'eucalyptus.ec2_url.' + region,
								value: values['eucalyptus.ec2_url'] || '',
								listeners: {
									change: function (field, newValue) {
										record.set('ec2_url', newValue);
									}
								}
							}, {
								xtype: 'textfield',
								fieldLabel: 'S3 URL (eg. http://192.168.1.1:8773/services/Walrus)',
								width: 320,
								name: 'eucalyptus.s3_url.' + region,
								value: values['eucalyptus.s3_url'] || ''
							}, {
								xtype: 'textfield',
								inputType: 'file',
								fieldLabel: 'X.509 Certificate file',
								name: 'eucalyptus.certificate.' + region
							}, {
								xtype: 'textfield',
								inputType: 'file',
								fieldLabel: 'X.509 Private Key file',
								name: 'eucalyptus.private_key.' + region
							}, {
								xtype: 'textfield',
								inputType: 'file',
								fieldLabel: 'X.509 Cloud certificate file',
								name: 'eucalyptus.cloud_certificate.' + region
							}, {
								xtype: 'container',
								layout: 'column',
								style: 'padding-top: 5px',
								items: [{
									xtype: 'button',
									style: 'padding-right: 5px',
									text: 'Save',
									handler: function (comp) {
										var b = comp.ownerCt.find('text', 'Cancel'), f = this.find('itemId', 'eucalyptus-listview');
										b[0].hide(); // hide cancel button

										this.layout.setActiveItem(f[0]);
										form.el.unmask();
									},
									scope: this
								}, {
									xtype: 'button',
									text: 'Cancel',
									handler: function () {
										var f = this.find('regionName', region), f2 = this.find('itemId', 'eucalyptus-listview');
										store.remove(record);
										this.layout.setActiveItem(f2[0]);
										this.remove(f[0]);
										form.el.unmask();
									},
									scope: this
								}]
							}]
						});
					}
				}]
			}]
		});

		form.findOne('itemId', 'eucalyptus').on('afterrender', function (comp) {
			comp.getTopToolbar().getComponent('add').on('click', function() {
				Ext.Msg.prompt('Eucalyptus Cloud Location', 'Please specify cloud location name:', function (btn, text) {
					if (btn == 'ok') {
						// @TODO: check value [^-]+[A-Za-z0-9-]+[^-]

						var sc = this.findOne('itemId', 'eucalyptus'), c = sc.addCardTab(text, []);
						var tb = this.findOne('itemId', 'eucalyptus').getTopToolbar();
						//tb.getComponent('add').hide();
						//tb.getComponent('list').show();
						sc.layout.setActiveItem(c);
					}
				}, this);
			}, this);

			var c = form.findOne('itemId', 'eucalyptus');
			if (Ext.isObject(moduleParams['eucaParams'])) {
				for (var i in moduleParams['eucaParams'])
					c.addCardTab(i, moduleParams['eucaParams'][i]);
			}
			form.el.unmask();
		}, form);

		form.on('actionfailed', function (form, action) {
			try {
				var result = Ext.decode(action.response.responseText), message = 'The following fields are incorrect. Please check them and try again:';
				if (result.errors) {
					for (i in result.errors)
						message += '<br />&bull;&nbsp;&nbsp;' + result.errors[i];
				}
				Scalr.Viewers.ErrorMessage(message);
			} catch (e) { }
		}, form);

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

		form.addButton({
			type: 'submit',
			text: 'Save',
			handler: function() {
				//if (form.getForm().isValid()) {
					Ext.Msg.wait('Please wait ...', 'Saving ...');
					
					var data = [], records = form.findOne('itemId', 'eucalyptus-listview').store.getRange();
					for (var i = 0; i < records.length; i++)
						data[data.length] = records[i].get('region');

					form.getForm().submit({
						url: '/environments/save/',
						params: {
							envId: moduleParams.env.id,
							eucalyptusClouds: Ext.encode(data)
						},
						success: function(form, action) {
							Ext.Msg.hide();
							Scalr.Viewers.SuccessMessage('Environment saved');
							Scalr.Viewers.EventMessager.fireEvent('close');
						},
						failure: Scalr.data.ExceptionFormReporter
					});
				//} else {
					//Scalr.Viewers.ErrorMessage('The following fields are incorrect. Please check them and try again');
				//}
			}
		});

		form.addButton({
			type: 'reset',
			text: 'Cancel',
			handler: function() {
				Scalr.Viewers.EventMessager.fireEvent('close');
			}
		});

		return form;
	}
}
