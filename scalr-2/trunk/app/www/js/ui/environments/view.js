{
	create: function (loadParams, moduleParams) {
		return new Scalr.Viewers.ListView({
			scalrOptions: {
				'reload': false,
				'maximize': 'all'
			},
			title: 'Environments &raquo; View',
			store: new Scalr.data.Store({
				reader: new Scalr.data.JsonReader({
					id: 'id',
					fields: [
						'id', 'name', 'dtAdded', 'isSystem','platforms'
					]
				}),
				url: '/environments/xListViewEnv/'
			}),
			enableFilter: false,
			enablePaging: false,
			stateId: 'listview-environments-view',
			listViewOptions: {
				emptyText: 'No environments found',
				columns: [
					{ header: _("Name"), width: '300px', dataIndex: 'name', sortable: true, hidden: 'no' },
					{ header: _("Enabled cloud platforms"), width: 30, dataIndex: 'platforms', sortable: true, hidden: 'no' },
					{ header: _("Date added"), width: '180px', dataIndex: 'dtAdded', sortable: true, hidden: 'no' },
					{ header: _("System"), width: "70px", dataIndex: 'isSystem', sortable: false, hidden: 'no', align: 'center', tpl:
						'<tpl if="isSystem == 1"><img src="/images/true.gif"></tpl>' +
						'<tpl if="isSystem != 1">-</tpl>'
					}
				]
			},
			rowOptionsMenu: [
				{ itemId: "option.details", text:'Edit', href: "#/environments/{id}/edit/" }
			]
		});
	}
}
