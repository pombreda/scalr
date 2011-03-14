{
	create: function (loadParams, moduleParams) {
		return new Ext.Panel({
			title: 'Server "' + loadParams['serverId'] + '" extended information',
			scalrOptions: {
				'modal': true,
				'maximize': 'maxHeight'
			},
			tools: [{
				id: 'close',
				handler: function () {
					Scalr.Viewers.EventMessager.fireEvent('close');
				}
			}],
			items: moduleParams,
			autoScroll: true,
			frame: true,
			autoHeight: true,
			padding: '0px 20px 0px 5px',
			width: 800
		});
	}
}
