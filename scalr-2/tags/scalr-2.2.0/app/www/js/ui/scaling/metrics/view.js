{
	create: function (loadParams, moduleParams) {
		var store = new Scalr.data.Store({
			baseParams: loadParams,
			reader: new Scalr.data.JsonReader({
				id: 'id',
				fields: [ 'id','env_id','client_id','name','file_path','retrieve_method','calc_function' ]
			}),
			remoteSort: true,
			url: '/scaling/metrics/xListViewMetrics/'
		});

		return new Scalr.Viewers.ListView({
			title: 'Scaling &raquo; Metrics &raquo; View',
			scalrOptions: {
				'reload': false,
				'maximize': 'all'
			},
			scalrReconfigure: function (loadParams) {
				Ext.applyIf(loadParams, { metricId: ''});
				Ext.apply(this.store.baseParams, loadParams);
				this.store.load();
			},
			enableFilter: false,
			store: store,
			stateId: 'listview-scaling-metrics-view',

			tbar: [{
				icon: '/images/add.png',
				cls: 'x-btn-icon',
				tooltip: 'Create new scaling metric',
				handler: function() {
					document.location.href = '#/scaling/metrics/create';
				}
			}],

			// Row menu
			rowOptionsMenu: [
				{ itemId: "option.edit", 	text: 'Edit', 		href: "#/scaling/metrics/{id}/edit" } 
			],

			getRowMenuVisibility: function (data) {
				return (data.env_id != 0);
			},
			
			withSelected: {
				menu: [
					{
						text: 'Delete',
						params: {
							action: 'delete',
							with_selected: 1
						},
						confirmationMessage: 'Remove selected metric(s)?'
					}
				],
				renderer: function(data) {
					return (data.env_id != 0);
				}
			},

			listViewOptions: {
				emptyText: "No presets defined",
				columns: [
					{ header: "ID", width: 15, dataIndex: 'id', sortable:true, hidden: 'no' },
					{ header: "Name", width: 40, dataIndex: 'name', sortable:true, hidden: 'no' },
					{ header: "File path", width: 40, dataIndex: 'file_path', sortable: false, hidden: 'no' },
					{ header: "Retrieve method", width: 50, dataIndex: 'retrieve_method', sortable: false, hidden: 'no', tpl:
						'<tpl if="retrieve_method == \'read\'">File-Read</tpl>' +
						'<tpl if="retrieve_method == \'execute\'">File-Execute</tpl>'
					},
					{ header: "Calculation function", width: 50, dataIndex: 'calc_function', sortable: false, hidden: 'no', tpl:
						'<tpl if="calc_function == \'avg\'">Average</tpl>' +
						'<tpl if="calc_function == \'sum\'">Sum</tpl>'
					 }
				]
			}
		});
	}
}
