{
	create: function (loadParams, moduleParams) {
		return new Ext.Panel({
			title: 'Farms &raquo; ' + moduleParams['farmName'] + ' &raquo; ' + moduleParams['roleName'] + ' &raquo; Extended information',
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
			items: moduleParams['form'],
			autoScroll: true,
			frame: true,
			autoHeight: true,
			padding: '0px 20px 0px 5px',
			width: 800
		});
	}
}
