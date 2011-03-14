{
	create: function (loadParams, moduleParams) {
		var store = new Scalr.data.Store({
			baseParams: loadParams,
			reader: new Scalr.data.JsonReader({
				id: 'messageid',
				fields: [
				         "messageid", "server_id", "status", "handle_attempts", "dtlasthandleattempt","message_type","type","isszr"
				]
			}),
			remoteSort: true,
			url: '/servers/xListViewMessages/'
		});

		return new Scalr.Viewers.ListView({
			title: 'Server ' + loadParams['serverId'] + ' &raquo; Messages',
			scalrOptions: {
				'maximize': 'all',
				'reload':false
			},
			tools: [{
				id: 'close',
				handler: function () {
					Scalr.Viewers.EventMessager.fireEvent('close');
				}
			}],
			scalrReconfigure: function (loadParams) {
				Ext.applyIf(loadParams, { serverId: ''});
				Ext.apply(this.store.baseParams, loadParams);
				this.store.load();
			},
			store: store,
			stateId: 'listview-server-messages-view',
			
			listViewOptions: {
				viewConfig: { 
		        	emptyText: "No messages found"
		        },
		        // Columns
		        columns:[
					{header: "Message ID", width: 50, dataIndex: 'messageid', sortable: true},
					{header: "Message type", width: 40, dataIndex: 'message_type', tpl:'{type} / {message_type}', sortable: false},
					{header: "Server ID", width: 30, dataIndex: 'server_id', tpl:'<a href="#/servers/{server_id}/extendedInfo">{server_id}</a>', sortable: true},
					{header: "Status", width: 30, dataIndex: 'isdelivered', tpl:''+
					'<tpl if="status == 1"><span style="color:green;">Delivered</span></tpl>'+
					'<tpl if="status == 0"><span style="color:orange;">Delivering...</span></tpl>'+
					'<tpl if="status == 2 || status == 3"><span style="color:red;">Failed</span></tpl>'
					, sortable: true},
					{header: "Attempts", width: '100px', dataIndex: 'handle_attempts', sortable: true}, 
					{header: "Last delivery attempt", width: '200px', dataIndex: 'dtlasthandleattempt', sortable: true}
				]
			},
			rowOptionsMenu: [
        		{id: "option.edit", text:'Re-send message', menuHandler: function(item) {
					Ext.MessageBox.show({
						progress: true,
						msg: 'Re-sending message. Please wait...',
						wait: true,
						width: 450,
						icon: 'scalr-mb-instance-rebooting'
					});

					Ext.Ajax.request({
						url: '/servers/' + item.currentRecordData.server_id + '/xResendMessage/',
						success: function(response, options) {
							Ext.MessageBox.hide();

							var result = Ext.decode(response.responseText);
							if (result.success == true) {
								Scalr.Viewers.SuccessMessage("Message successfully re-sent to the server");
								store.reload();
							} else {
								Scalr.Viewers.ErrorMessage(result.error);
							}
						},
						params: {
							messageId: item.currentRecordData.messageid
						}
					});
				}}
            ],

            getRowMenuVisibility: function (data) {
     			return (data.status == 2 || data.status == 3);
     		}
		});
	}
}
