{
	create: function (loadParams, moduleParams) {
		return new Ext.Panel({
			scalrOptions: {
				'modal': true,
				'maximize': 'all'
			},
			tools: [{
				id: 'close',
				handler: function () {
					Scalr.Viewers.EventMessager.fireEvent('close');
				}
			}],
			title: 'Server "' + moduleParams.name + '" console output',
			html: moduleParams.content,
			autoScroll: true,
			frame: true
		});
	}
}
