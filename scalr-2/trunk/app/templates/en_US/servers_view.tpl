{include file="inc/header.tpl"}
<script type="text/javascript" src="/js/ui-ng/data.js"></script>
<script type="text/javascript" src="/js/ui-ng/viewers/ListView.js"></script>

<div id="listview-servers-view"></div>
<script type="text/javascript">

var FarmID = '{$smarty.get.farmid}';

var regions = [
{foreach from=$regions name=id key=key item=item}
	['{$key}','{$item}']{if !$smarty.foreach.id.last},{/if}
{/foreach}
];

var region = '{$smarty.session.aws_region}';

{literal}
Ext.onReady(function () {
	var store = new Scalr.data.Store({
		reader: new Scalr.data.JsonReader({
			root: 'data',
			successProperty: 'success',
			errorProperty: 'error',
			totalProperty: 'total',
			id: 'server_id',

			fields: [
				'cloud_server_id', 'isrebooting', 'dns', 'server_id', 'remote_ip', 'local_ip', 'status', 'platform', 'farm_name', 'role_name', 'index', 'role_id', 'farm_id', 'farm_roleid', 'uptime', 'ismaster'
			]
		}),
		remoteSort: true,
		url: '/server/grids/servers_list.php?a=1{/literal}{$grid_query_string}{literal}'
	});

	Scalr.Viewers.laGetFunction = Ext.extend(Ext.util.Observable, {
		cache: {},
		currentRequestId: 0,

		getCache: function(serverId) {
			var dt = new Date();
			return (typeof(this.cache[serverId]) != "undefined" && this.cache[serverId].dt > dt) ? this.cache[serverId].html : null;
		},

		updateCache: function(elem, html) {
			this.cache[elem.getAttribute('serverid')] = { html: html, dt: new Date().add(Date.MINUTE, 3) };
		},

		waitHtml: function(elem) {
			var el = Ext.get(elem);
			if (el)
				el.update('<img src="/images/snake-loader.gif">');
		},
		
		updateHtml: function(elem) {
			var el = Ext.get(elem);
			if (el) {
				if (this.cache[elem.getAttribute('serverid')]) {
					el.parent().update(this.cache[elem.getAttribute('serverid')].html); // replace <span>
				} else {
					el.update(''); // clear cell
				}
			}
		},

		getNext: function() {
			return (this.indexId < this.elements.length) ? this.elements[this.indexId++] : null;
		},

		getLA: function() {
			var elem = this.getNext();

			if (! elem)
				return;

			Ext.Ajax.suspendEvents();
			this.waitHtml(elem);

			this.currentRequestId = Ext.Ajax.request({
				url: '/server/ajax-ui-server.php?' + Ext.urlEncode({ action: 'GetServerLA', serverId: elem.getAttribute('serverid') }),
				success: function(response, options) {
					Ext.Ajax.resumeEvents();

					var result = Ext.decode(response.responseText), html = '';

					if (result.result == 'ok') {
						html = result.data;
					} else {
						html = '<img src="/images/warn.png" title="' + result.msg + '">';
					}

					this.func.updateCache(this.elem, html);
					this.func.updateHtml(this.elem);
					this.func.getLA();
				},
				failure: function(response) {
					Ext.Ajax.resumeEvents();

					if (response.isAbort) {
						this.func.updateHtml(this.elem);
					} else {
						this.func.updateCache(this.elem, '<img src="/images/warn.png" title="Cannot proceed request">');
						this.func.updateHtml(this.elem);
						this.func.getLA();
					}
				},
				scope: {
					func: this,
					elem: elem
				}
			});
		},

		startUpdate: function(elem) {
			this.elements = elem.query('span.la');
			this.indexId = 0;

			this.getLA();
		},
		
		stopUpdate: function() {
			if (this.currentRequestId && Ext.Ajax.isLoading(this.currentRequestId)) {
				this.updateHtml(this.elements[this.indexId]); // abort doesn't handle by event 'failure'
				Ext.Ajax.abort(this.currentRequestId);
			}
		}
	});
	var laGetFunction = new Scalr.Viewers.laGetFunction();

	new Scalr.Viewers.ListView({
		renderTo: "listview-servers-view",
		autoRender: true,
		store: store,
		savePagingSize: true,
		savePagingNumber: true,
		saveFilter: true,
		stateId: 'listview-servers-view',
		stateful: true,
		title: 'Servers',

		tbar: [
			' ',
			{
				xtype:'checkbox',
				itemId: 'hide_terminated',
				boxLabel: 'Don\'t show terminated servers',
				style: 'margin: 0px',
				listeners: {
					check: function(item, checked) {
						store.baseParams.hide_terminated = checked ? 'true' : 'false'; 
						store.load();
					}
				}
			}
		],

		getLocalState: function() {
			var it = {};
			it.hide_terminated = this.getTopToolbar().getComponent('hide_terminated').getValue();
			return it;
		},

		rowOptionsMenu: [
			{itemId: "option.cancel",		text: 'Cancel', 							href: "/server_action.php?action=cancel&server_id={server_id}"},
			
			{itemId: "option.info",			text: 'Extended instance information', 		href: "/server_view_extended_info.php?server_id={server_id}"},
			{itemId: "option.loadStats",	text: 'Load statistics', 					href: "/server_view_monitoring_info.php?farmid={farm_id}&role={farm_roleid}&server_index={index}"},
			new Ext.menu.Separator({itemId: "option.infoSep"}),
			{itemId: "option.sync",			text: 'Create server snapshot', 			href: "/server_create_image.php?server_id={server_id}"},        	
			new Ext.menu.Separator({itemId: "option.syncSep"}),
			{itemId: "option.editRole",		text: 'Configure role in farm', 			href: "/farms_builder.php?id={farm_id}&role_id={role_id}"},        				
			new Ext.menu.Separator({itemId: "option.procSep"}), //TODO:
			{itemId: "option.dnsEx",		text: 'Exclude from DNS zone', 				href: "/server_action.php?action=exclude_from_dns&server_id={server_id}"},
			{itemId: "option.dnsIn",		text: 'Include in DNS zone', 				href: "/server_action.php?action=include_in_dns&server_id={server_id}"},
			new Ext.menu.Separator({itemId: "option.editRoleSep"}),
			{itemId: "option.console",		text: 'View console output', 				href: "/server_view_console_output.php?server_id={server_id}"},
			{itemId: "option.process",		text: 'View process list', 					href: "/server_view_process_list.php?server_id={server_id}"},
			{itemId: "option.messaging",	text: 'Scalr internal messaging', 			href: "/scalr_i_messages.php?server_id={server_id}"},
			/*
			new Ext.menu.Separator({itemId: "option.mysqlSep"}),
			{itemId: "option.mysql",		text: 'Backup/bundle MySQL data', 			href: "/farm_mysql_info.php?farmid={farm_id}"},
			*/
			new Ext.menu.Separator({itemId: "option.execSep"}),
			{itemId: "option.exec",			text: 'Execute script', 					href: "/execute_script.php?farmid={farm_id}&server_id={server_id}"},
			new Ext.menu.Separator({itemId: "option.menuSep"}),
			{itemId: "option.reboot",		text: 'Reboot', confirmationMessage: 'Reboot selected server(s)?',
				menuHandler: function(item) {
					Ext.MessageBox.show({
						progress: true,
						msg: 'Sending reboot command to the server(s). Please wait...',
						wait: true,
						width: 450,
						icon: 'scalr-mb-instance-rebooting'
					});

					Ext.Ajax.request({
						url: '/server/ajax-ui-server.php',
						success: function(response, options) {
							Ext.MessageBox.hide();

							var result = Ext.decode(response.responseText);
							if (result.result == 'ok') {
								store.load();
							} else {
								Scalr.Viewers.ErrorMessage(result.msg);
							}
						},
						params: {
							action: 'RebootServers',
							servers: Ext.encode([item.currentRecordData.server_id])
						}
					});
				}
			},

			{itemId: "option.term", text: 'Terminate',
				handler: function(item) {
					var request = { decrease_mininstances_setting: 0, force_terminate: 0 };

					Ext.MessageBox.show({
						title: 'Confirm',
						msg: 
							'Terminate selected servers(s)?'+
							'<br \><br \>'+
							'<input type="checkbox" class="instance"> Decrease \'Mininimum servers\' setting<br \>' +
							'<input type="checkbox" class="force"> Forcefully terminate selected server(s)<br \>',

						buttons: Ext.Msg.YESNO,
						fn: function(btn) {
							if (btn == 'yes') {
								Ext.MessageBox.show({
									progress: true,
									msg: 'Terminating server(s). Please wait...',
									wait: true,
									width: 450,
									icon: 'scalr-mb-instance-terminating'
								});

								Ext.Ajax.request({
									url: '/server/ajax-ui-server.php',
									success: function(response, options) {
										Ext.MessageBox.hide();

										var result = Ext.decode(response.responseText);
										if (result.result == 'ok') {
											store.load();
										} else {
											Scalr.Viewers.ErrorMessage(result.msg);
										}
									},
									params: {
										action: 'TerminateServers',
										decrease_mininstances_setting: request.decrease_mininstances_setting,
										force_terminate: request.force_terminate,
										servers: Ext.encode([item.currentRecordData.server_id])
									}
								});
							}
						}
					});
					
					if (Ext.MessageBox.isVisible()) {
						var el = Ext.MessageBox.getDialog().getEl();

						el.child('input.instance').on('click', function() {
							request.decrease_mininstances_setting = this.dom.checked ? 1 : 0;
						});

						el.child('input.force').on('click', function() {
							request.force_terminate = this.dom.checked ? 1 : 0;
						});
					}
				}
			},
			new Ext.menu.Separator({id: "option.logsSep"}),
			{id: "option.logs",			text: 'View logs', 							href: "/logs_view.php?server_id={server_id}"}
		],
		getRowOptionVisibility: function (item, record) {
			var data = record.data;
			
			if (data.status == 'Importing' || data.status == 'Pending launch')
			{
				if (item.itemId == 'option.cancel')
					return true;
				else
					return false;
			}
			else
			{
				if (item.itemId == 'option.cancel')
					return false;
				
				if (data.status == 'Terminated')
					return false;
				else
					return true;
			}
		},

		getRowMenuVisibility: function (data) {
			return (data.status != 'Terminated');
		},

		listViewOptions: {
			emptyText: "No servers found",
			columns: [
				{ header: "Platform", width: '85px', dataIndex: 'platform', sortable: true, hidden: 'no' },
				{ header: "Farm & Role", width: 60, dataIndex: 'farm_id', sortable: true, hidden: 'no', tpl:
					'<tpl if="farm_id">Farm: <a href="farms_view.php?id={farm_id}" title="Farm {farm_name}">{farm_name}</a>' +
						'<tpl if="farm_roleid">&nbsp;&rarr;&nbsp;<a href="farm_roles_view.php?farm_roleid={farm_roleid}&farmid={farm_id}" title="Role {role_name}">{role_name}</a></tpl>' +
					'</tpl>' +
					'<tpl if="ismaster == 1"> (Master)</tpl>' +
					'<tpl if="! farm_id"><img src="/images/false.gif" /></tpl>'
				},
				{ header: "Server ID", width: '220px', dataIndex: 'server_id', sortable: true, hidden: 'no', tpl: new Ext.XTemplate(
					'<a href="server_view_extended_info.php?server_id={server_id}">{[this.serverId(values.server_id)]}</a>', {
						serverId: function(id) {
							var values = id.split('-');
							return values[0] + '-...-' + values[values.length - 1];
						}
					})
				},
				{ header: "Cloud Server ID", width: 60, dataIndex: 'cloud_server_id', sortable: false, hidden: 'yes', tpl:
					'{cloud_server_id}'
				},
				{ header: "Status", width: 30, dataIndex: 'status', sortable: true, hidden: 'no', tpl:
					'{status} <tpl if="isrebooting == 1"> (Rebooting ...)</tpl>'
				},
				{ header: "Remote IP", width: 30, dataIndex: 'remote_ip', sortable: true, hidden: 'no', tpl:
					'<tpl if="remote_ip">{remote_ip}</tpl>'
				},
				{ header: "Local IP", width: 30, dataIndex: 'local_ip', sortable: true, hidden: 'no', tpl:
					'<tpl if="local_ip">{local_ip}</tpl>'
				},
				{ header: "Uptime", width: 30, dataIndex: 'uptime', sortable: false, hidden: 'no' },
				{ header: "DNS", width: '38px', dataIndex: 'dns', sortable: false, hidden: 'no', align: 'center', tpl:
					'<tpl if="! dns"><img src="/images/false.gif" /></tpl><tpl if="dns"><img src="/images/true.gif" /></tpl>'	
				},
				{ header: "LA", width: '50px', dataIndex: 'server_la', sortable: false, hidden: 'yes', align: 'center',
					tpl: new Ext.XTemplate(
						'<tpl if="status == &quot;Running&quot;">' +
							'<tpl if="this.laGetFunction.getCache(values.server_id)">{[this.laGetFunction.getCache(values.server_id)]}</tpl>' +
							'<tpl if="!this.laGetFunction.getCache(values.server_id)"><span class="la" serverid="{server_id}"></span></tpl>' +
						'</tpl>' +
						'<tpl if="status != &quot;Running&quot;">-</tpl>', { laGetFunction: laGetFunction }
					)
				},
				{ header: "SSH", width: '38px', dataIndex: 'id', sortable: false, align:'center', hidden: 'no', tpl:
					'<tpl if="(status == &quot;Running&quot; || status == &quot;Initializing&quot;) && index != &quot;0&quot;">' +
						'<a href="server_ssh_console.php?server_id={server_id}" target="_blank" ><img style="margin-right:3px;" src="images/terminal.png"></a>' +
					'</tpl>' +
					'<tpl if="! ((status == &quot;Running&quot; || status == &quot;Initializing&quot;) && index != &quot;0&quot;)">' +
						'<img src="/images/false.gif">' +
					'</tpl>'
				}
			]
		},

		withSelected: {
			menu: [
				{
					text: "Reboot",
					method: 'ajax',
					params: {
						action: 'RebootServers'
					},
					confirmationMessage: 'Reboot selected instance(s)?',
					progressMessage: 'Sending reboot command to servers(s). Please wait...',
					progressIcon: 'scalr-mb-instance-rebooting',
					url: '/server/ajax-ui-server.php',
					dataHandler: function(records) {

						var servers = [];
						for (var i = 0, len = records.length; i < len; i++) {
							servers[servers.length] = records[i].get('server_id');
						}
						
						return { servers: Ext.encode(servers) };
					}
				}, {
					text: "Terminate",
					method: 'ajax',
					params: {
						action: 'TerminateServers'
					},
					confirmationMessage: 'Terminate selected instance(s)?',
					progressMessage: 'Terminating servers(s). Please wait...',
					progressIcon: 'scalr-mb-instance-terminating',
					url: '/server/ajax-ui-server.php',
					dataHandler: function(records) {
						var servers = [];
						for (var i = 0, len = records.length; i < len; i++) {
							servers[servers.length] = records[i].get('server_id');
						}
						return { servers: Ext.encode(servers) };
					}
				}
			],
			renderer: function(data) {
				return (data.status == 'Running' || data.status == 'Initializing');
			}
		},

		listeners: {
			'render': function() {
				if (this.state && this.state.hide_terminated) {
					this.getTopToolbar().getComponent('hide_terminated').setValue(this.state.hide_terminated);
					this.store.baseParams.hide_terminated = this.state.hide_terminated;
				}

				this.listView.on('columnhide', function(column) {
					if (column.dataIndex == 'server_la') {
						laGetFunction.stopUpdate();
					}
				});

				this.listView.on('columnshow', function(column) {
					if (column.dataIndex == 'server_la') {
						laGetFunction.startUpdate(this.innerBody);
					}
				}, this.listView);

				this.listView.on('refresh', function() {
					for (var i = 0, len = this.columns.length; i < len; i++) {
						var column = this.columns[i];
						if (column.dataIndex == 'server_la' && column.hidden == 'no') {
							laGetFunction.stopUpdate();
							laGetFunction.startUpdate(this.innerBody);
						}
					}
				}, this.listView);
			}
		}
	});
});
	
{/literal}
</script>
{include file="inc/footer.tpl"}