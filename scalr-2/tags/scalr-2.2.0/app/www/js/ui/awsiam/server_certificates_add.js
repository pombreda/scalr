{
	create: function (loadParams, moduleParams) {
			frame: true,
			fileUpload: true,
			autoScroll: true,
			items: [{
				xtype: 'fieldset',
				title: 'General information',
				labelWidth: 130,
				items: [{
					xtype: 'textfield',
					name: 'name',
					fieldLabel: 'Name',
					value: '',
					anchor: '-20'
				},{
					xtype: 'textfield',
					name: 'certificate',
					fieldLabel: 'Certificate',
					inputType: 'file',
					value: ''
				}, {
					xtype: 'textfield',
					name: 'privateKey',
					fieldLabel: 'Private key',
					inputType: 'file',
					value: ''
				},
				{
					xtype: 'textfield',
					name: 'certificateChain',
					fieldLabel: 'Certificate chain',
					inputType: 'file',
					value: ''
				}]
			}],
			buttonAlign: 'center',
			buttons: [{
				type: 'submit',
				text: 'Upload',
				handler: function() {
					if (form.getForm().isValid()) {
						Ext.Msg.wait('Please wait');
						form.getForm().submit({
							url: '/awsIam/serverCertificatesSave',
							success: function(form, action) {
								Ext.Msg.hide();
								if (action.result.success == true) {
									Scalr.data.SuccessMessage('Certificate successfully uploaded');
									this.close();
								}
								else
									Scalr.Viewers.ErrorMessage(action.result.error);
							},
							scope: this,
							failure: Scalr.data.ExceptionFormReporter
						});
					}
				},
				scope: this
			}, {
				type: 'reset',
				text: 'Cancel',
				handler: function() {
					this.close();
				},
				scope: this
			}]
		});

		this.win = new Ext.Window(Ext.apply({
			title: 'Amazon Web Services &raquo; Amazon IAM &raquo; Server Certificates &raquo; Add',
			layout: 'fit',
			width: 700,
			height: 300,
			border: false,
			items: {
				xtype: 'panel',
				layout: 'fit',
				border: false,
				autoScroll: true,
				items: form
			}
		}, windowParams));

		this.win.on('close', function() {
			this.win = null;
			document.location.href = '#/awsIam/serverCertificatesList';
		}, this);

		return this.win;
	}
}
