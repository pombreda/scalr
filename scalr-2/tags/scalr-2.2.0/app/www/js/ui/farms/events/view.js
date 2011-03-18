{
	create: function (loadParams, moduleParams) {
		var store = new Scalr.data.Store({
			baseParams: loadParams,
			reader: new Scalr.data.JsonReader({
				id: 'id',
				fields: [
			         	'id','dtadded', 'type', 'message'
				]
			}),
			remoteSort: true,
			url: '/farms/' + loadParams['farmId'] + '/events/xListViewEvents'
		});

		return new Scalr.Viewers.ListView({
			title: 'Farms &raquo; ' + moduleParams['farmName'] + ' &raquo; Events',
			scalrOptions: {
				'reload': false,
				'maximize': 'all'
			},
			
			bbar: [ '->', new Scalr.Toolbar.TimeItem({ time: moduleParams['time'], timeOffset: moduleParams['timeOffset'] })],
			
			tbar: [{
				text: 'Configure event notifications',
				//iconCls: 'x-btn-download-icon',
				handler: function () {
					document.location.href='/configure_event_notifications.php?farmid='+loadParams['farmId'];
				}
			}],
			
			scalrReconfigure: function (loadParams) {
				Ext.applyIf(loadParams, { farmId: '' });
				Ext.apply(this.store.baseParams, loadParams);
				this.store.load();
			},
			store: store,
			stateId: 'listview-farm-events-view',

			listViewOptions: {
				emptyText: "No events found",
				columns: [
					{header: "Date", width: 80, dataIndex: 'dtadded', sortable: false},
					{header: "Event", width: 50, dataIndex: 'type', sortable: false},
					{header: "Description", width: 300, dataIndex: 'message', sortable: false}
			]}
		});
	}
}
