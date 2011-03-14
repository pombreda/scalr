{
	create: function (loadParams, moduleParams) {
		
		var scriptId = moduleParams['scriptId'] | loadParams['scriptId'];
		
		
		var form = new Ext.form.FormPanel({
			scalrOptions: {
				'maximize': 'maxHeight'
			},
			width: 700,
			title: 'Execute script',
			frame: true,
			labelWidth: 200,
			autoScroll: true,
			padding: '0px 20px 0px 5px',
			plugins: [ new Scalr.Viewers.Plugins.findOne() ],
			buttonAlign: 'center',
			items: [{
				xtype: 'fieldset',
				title: 'Execution target',
				layout: 'column',
				itemId: 'executionTarget',
				items: [{
					xtype: 'combo',
					hideLabel: true,
					name: 'farmId',
					hiddenName: 'farmId',
					store: Scalr.data.createStore(moduleParams['farms'], { idProperty: 'id', fields: [ 'id', 'name' ]}),
					valueField: 'id',
					displayField: 'name',
					emptyText: 'Select a farm',
					columnWidth: .32,
					editable: false,
					forceSelection: true,
					value: moduleParams['farmId'],
					mode: 'local',
					triggerAction: 'all',
					selectOnFocus: false,
					listeners: {
						select: function (combo, record) {
							form.el.mask('Loading farm roles ...');

							Ext.Ajax.request({
								url: '/scripts/getFarmRoles/',
								params: { farmId: record.get('id') },
								success: function (response) {
									var result = Ext.decode(response.responseText), field = form.findOne('name', 'farmRoleId');
									if (result.success) {
										field.show();
										if (Ext.isObject(result.farmRoles)) {
											field.emptyText = 'Select a role';
											field.reset();
											field.store.loadData(result.farmRoles);
											field.setValue(0);
											field.enable();
										} else {
											field.store.removeAll();
											field.emptyText = 'No roles';
											field.reset();
											field.disable();
										}
										form.findOne('name', 'serverId').hide();
									} else {
										Scalr.Viewers.ErrorMessage(result.error);
									}
									form.el.unmask();
									form.findOne('itemId', 'executionTarget').doLayout();
								}
							});
						}
					}
				}, {
					columnWidth: .01,
					html: '&nbsp;'
				}, {
					xtype: 'combo',
					hideLabel: true,
					name: 'farmRoleId',
					hiddenName: 'farmRoleId',
					store: Scalr.data.createStore(moduleParams['farmRoles'], { idProperty: 'id', fields: [ 'id', 'name', 'platform', 'role_id' ]}),
					valueField: 'id',
					displayField: 'name',
					emptyText: 'Select a role',
					columnWidth: .32,
					editable: false,
					forceSelection: true,
					value: moduleParams['farmRoleId'],
					mode: 'local',
					triggerAction: 'all',
					selectOnFocus: false,
					listeners: {
						select: function (combo, record) {
							if (! combo.getValue()) {
								form.findOne('name', 'serverId').hide();
								return;
							}

							form.el.mask('Loading servers ...');

							Ext.Ajax.request({
								url: '/scripts/getServers/',
								params: { farmRoleId: record.get('id') },
								success: function (response) {
									var result = Ext.decode(response.responseText), field = form.findOne('name', 'serverId');
									if (result.success) {
										field.show();
										if (Ext.isObject(result.servers)) {
											field.emptyText = 'Select a server';
											field.reset();
											field.store.loadData(result.servers);
											field.setValue(0);
											field.enable();
										} else {
											field.emptyText = 'No running servers';
											field.reset();
											field.disable();
										}
									} else {
										Scalr.Viewers.ErrorMessage(result.error);
									}
									form.el.unmask();
									form.doLayout();
								}
							});
						}
					}
				}, {
					columnWidth: .01,
					html: '&nbsp;'
				}, {
					xtype: 'combo',
					hideLabel: true,
					name: 'serverId',
					hiddenName: 'serverId',
					store: Scalr.data.createStore(moduleParams['servers'], { idProperty: 'id', fields: [ 'id', 'name' ]}),
					valueField: 'id',
					displayField: 'name',
					emptyText: 'Select a server',
					columnWidth: .32,
					editable: false,
					forceSelection: true,
					value: moduleParams['serverId'],
					mode: 'local',
					triggerAction: 'all',
					selectOnFocus: false
				}]
			}, {
				xtype: 'fieldset',
				title: 'Execution options',
				labelWidth: 100,
				defaults: {
					width: 150
				},
				items: [{
					xtype: 'combo',
					fieldLabel: 'Script',
					name: 'scriptId',
					hiddenName: 'scriptId',
					store: Scalr.data.createStore(moduleParams['scripts'], { idProperty: 'id', fields: [ 'id', 'name', 'description', 'issync', 'timeout', 'revisions' ]}),
					valueField: 'id',
					displayField: 'name',
					emptyText: 'Select a script',
					editable: false,
					forceSelection: true,
					value: scriptId,
					mode: 'local',
					triggerAction: 'all',
					selectOnFocus: false,
					listeners: {
						select: function () {
							var f = form.findOne('name', 'scriptId'), s = f.getValue(), r = f.store.getById(s), fR = form.findOne('name', 'scriptVersion');
							
							fR.store.loadData(r.get('revisions'));
							fR.store.sort('revision', 'DESC');
							
							if (!moduleParams['eventName'] || scriptId != f.getValue()) {
								fR.setValue(fR.store.getAt(0).get('revision'));
								
								form.findOne('name', 'scriptTimeout').setValue(r.get('timeout'));
								form.findOne('name', 'scriptIsSync').setValue(r.get('issync'));
							}
							
							fR.fireEvent('select');
						}
					}
				}, {
					xtype: 'combo',
					store: [ ['1', 'Synchronous'], ['0', 'Asynchronous']],
					editable: false,
					mode: 'local',
					name: 'scriptIsSync',
					value: moduleParams['scriptIsSync'],
					hiddenName: 'scriptIsSync',
					triggerAction: 'all',
					fieldLabel: 'Execution mode'
				}, {
					xtype: 'textfield',
					fieldLabel: 'Timeout',
					value: moduleParams['scriptTimeout'],
					name: 'scriptTimeout'
				},{
					xtype: 'combo',
					store: Scalr.data.createStore([], { idProperty: 'revision', fields: [ 'revision', 'fields' ]}),
					valueField: 'revision',
					displayField: 'revision',
					editable: false,
					mode: 'local',
					name: 'scriptVersion',
					value: moduleParams['scriptVersion'],
					triggerAction: 'all',
					fieldLabel: 'Version',
					listeners: {
						select: function () {
							var f = form.findOne('name', 'scriptVersion');
							var s = f.getValue();
							var r = f.store.getById(s);							
							var fields = r.get('fields'), fieldset = form.findOne('itemId', 'scriptOptions');

							fieldset.removeAll();
							if (Ext.isObject(fields)) {
								for (var i in fields) {
									fieldset.add({
										xtype: 'textfield',
										fieldLabel: fields[i],
										name: 'scriptOptions[' + i + ']',
										value:moduleParams['scriptOptions'][i],
										width: 300
									});
								}
								fieldset.show();
								fieldset.doLayout();
							} else {
								fieldset.hide();
							}
						}
					}
				}]
			}, {
				xtype: 'fieldset',
				title: 'Script options',
				itemId: 'scriptOptions',
				labelWidth: 100,
				hidden: true,
				defaults: {
					width: 150
				}
			}, {
				xtype: 'fieldset',
				title: 'Additional settings',
				labelWidth: 100,
				items: [{
					xtype: 'checkbox',
					hideLabel: true,
					boxLabel: 'Add a shortcut in Options menu for roles. It will allow me to execute this script with the above parameters with a single click.',
					name: 'createMenuLink',
					inputValue: 1,
					checked: loadParams['isShortcut'],
					disabled: loadParams['isShortcut'] || loadParams['eventName']
				}]
			}]
		});

		form.addButton({
			type: 'submit',
			text: (loadParams['isShortcut']) ? 'Save' : 'Execute',
			handler: function() {
				//if (form.getForm().isValid()) {
				// validation

				if (1) {
					Ext.Msg.wait('Please wait ...', 'Executing ...');

					form.getForm().submit({
						url: '/scripts/xExecute/',
						params: {isShortcut:loadParams['isShortcut']},
						success: function(form, action) {
							Ext.Msg.hide();
							Scalr.Viewers.SuccessMessage('Script executed');
							//document.location.href = '#/environments/view';
						},
						failure: Scalr.data.ExceptionFormReporter
					});
				} else {
					
				}
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
		
		form.on('afterlayout', function () {
			if (! moduleParams['farmId'])
				form.findOne('name', 'farmRoleId').hide();

			if (! moduleParams['farmRoleId'])
				form.findOne('name', 'serverId').hide();
			
			if (scriptId)
				form.findOne('name', 'scriptId').fireEvent('select');
			
			if (moduleParams['scriptVersion'])
				form.findOne('name', 'scriptVersion').fireEvent('select');
			
		}, form, { single: true });

		return form;
	}
}
