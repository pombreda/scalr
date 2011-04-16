{
	create: function (loadParams, moduleParams) {
		var store = new Scalr.data.Store({
			baseParams: loadParams,
			reader: new Scalr.data.JsonReader({
				id: 'server_id',
				fields: [
					'cloud_server_id', 'isrebooting', 'excluded_from_dns', 'server_id', 'remote_ip', 'local_ip', 'status', 'platform', 'farm_name', 'role_name', 'index', 'role_id', 'farm_id', 'farm_roleid', 'uptime', 'ismaster'
				]
			}),
			remoteSort: true,
			url: '/servers/xListViewServers/'
		});

		var laGetFunction = {
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
					url: '/servers/xServerGetLa/',
					params: { serverId: elem.getAttribute('serverid') },
					success: function(response, options) {
						Ext.Ajax.resumeEvents();

						var result = Ext.decode(response.responseText), html = '';
						if (result.success == true) {
							html = result.la;
						} else {
							html = '<img src="/images/warn.png" title="' + result.error + '">';
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
		};

		return new Scalr.Viewers.ListView({
			title: 'Servers &raquo; View',
			scalrOptions: {
				'reload': false,
				'maximize': 'all'
			},
			scalrReconfigure: function (loadParams) {
				Ext.applyIf(loadParams, { farmId: '', roleId: '', farmRoleId: '', serverId: '' });
				Ext.apply(this.store.baseParams, loadParams);
				this.store.load();
			},
			store: store,
			stateId: 'listview-servers-view',
			tbar: [
				' ',
				{
					xtype:'checkbox',
					itemId: 'hide_terminated',
					boxLabel: 'Don\'t show terminated servers',
					style: 'margin: 0px',
					listeners: {
						check: function(item, checked) {
							store.baseParams.hideTerminated = checked ? 'true' : 'false';
							store.load();
						}
					}
				}
			],

			rowOptionsMenu: [
				{itemId: "option.cancel", text: 'Cancel', menuHandler: function (item) {
					Ext.Msg.wait("Please wait ...");

					Ext.Ajax.request({
						url: '/servers/xServerCancelOperation/',
						params: { serverId: item.currentRecordData.server_id },
						success: function (response) {
							var result = Ext.decode(response.responseText);
							if (result.success == true) {
								Scalr.Viewers.SuccessMessage(result.message);
								store.reload();
							} else if (result.error)
								Scalr.Viewers.ErrorMessage(result.error);

							Ext.Msg.hide();
						},
						failure: function() {
							Ext.Msg.hide();
						}
					});
				}},
				{ itemId: "option.info", iconCls: 'scalr-menu-icon-info', text: 'Extended instance information', href: "#/servers/{server_id}/extendedInfo" },
				{itemId: "option.loadStats", iconCls: 'scalr-menu-icon-stats', text: 'Load statistics', href: "/monitoring.php?farmid={farm_id}&role={farm_roleid}&server_index={index}"},
				new Ext.menu.Separator({itemId: "option.infoSep"}),
				{ itemId: "option.sync", text: 'Create server snapshot', href: "#/servers/{server_id}/createSnapshot" },
				new Ext.menu.Separator({itemId: "option.syncSep"}),
				{itemId: "option.editRole", iconCls: 'scalr-menu-icon-configure', text: 'Configure role in farm', href: "#/farms/{farm_id}/edit?roleId={role_id}"},
				new Ext.menu.Separator({itemId: "option.procSep"}), //TODO:
				{
					itemId: 'option.dnsEx',
					text: 'Exclude from DNS zone',
					request: {
						processBox: {
							type: 'action'
						},
						url: '/servers/xServerExcludeFromDns/',
						dataHandler: function (record) {
							return { serverId: record.get('server_id') };
						},
						success: function (data) {
							Scalr.Message.Success(data.message);
							store.reload();
						}
					}
				}, {
					itemId: 'option.dnsIn',
					text: 'Include in DNS zone',
					request: {
						processBox: {
							type: 'action'
						},
						url: '/servers/xServerIncludeInDns/',
						dataHandler: function (record) {
							return { serverId: record.get('server_id') };
						},
						success: function (data) {
							Scalr.Message.Success(data.message);
							store.reload();
						}
					}
				},
				new Ext.menu.Separator({itemId: "option.editRoleSep"}),
				{ itemId: "option.console", text: 'View console output', href: '#/servers/{server_id}/consoleoutput' },
				/*{ itemId: "option.process", text: 'View process list', href: '#/servers/{server_id}/processlist' },*/
				{itemId: "option.messaging",	text: 'Scalr internal messaging', href: "#/servers/{server_id}/messages"},
				/*
				new Ext.menu.Separator({itemId: "option.mysqlSep"}),
				{itemId: "option.mysql",		text: 'Backup/bundle MySQL data', href: "/farm_mysql_info.php?farmid={farm_id}"},
				*/
				new Ext.menu.Separator({itemId: "option.execSep"}),
				{itemId: "option.exec", iconCls: 'scalr-menu-icon-execute', text: 'Execute script', href: "#/scripts/execute?serverId={server_id}"},
				new Ext.menu.Separator({itemId: "option.menuSep"}),
				{
					itemId: 'option.reboot',
					text: 'Reboot',
					iconCls: 'scalr-menu-icon-reboot',
					request: {
						confirmBox: {
							type: 'reboot',
							msg: 'Reboot server "{server_id}"?'
						},
						processBox: {
							type: 'reboot',
							msg: 'Sending reboot command to the server. Please wait...'
						},
						url: '/servers/xServerRebootServers/',
						dataHandler: function (record) {
							return { servers: Ext.encode([ record.get('server_id') ]) };
						},
						success: function () {
							store.reload();
						}
					}
				},

				{ itemId: "option.term", iconCls: 'scalr-menu-icon-terminate', text: 'Terminate',
					handler: function(item) {
						var request = { descreaseMinInstancesSetting: 0, forceTerminate: 0 };

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
										url: '/servers/xServerTerminateServers/',
										success: function(response, options) {
											Ext.MessageBox.hide();

											var result = Ext.decode(response.responseText);
											if (result.success == true) {
												store.reload();
											} else {
												Scalr.Viewers.ErrorMessage(result.error);
											}
										},
										params: {
											descreaseMinInstancesSetting: request.descreaseMinInstancesSetting,
											forceTerminate: request.forceTerminate,
											servers: Ext.encode([item.currentRecordData.server_id])
										}
									});
								}
							}
						});

						if (Ext.MessageBox.isVisible()) {
							var el = Ext.MessageBox.getDialog().getEl();

							el.child('input.instance').on('click', function() {
								request.descreaseMinInstancesSetting = this.dom.checked ? 1 : 0;
							});

							el.child('input.force').on('click', function() {
								request.forceTerminate = this.dom.checked ? 1 : 0;
							});
						}
					}
				},
				new Ext.menu.Separator({id: "option.logsSep"}),
				{id: "option.logs", iconCls: 'scalr-menu-icon-logs', text: 'View logs', href: "#/logs/system?serverId={server_id}"}
			],
			getRowOptionVisibility: function (item, record) {
				var data = record.data;
				
				if (item.itemId == 'option.dnsEx' && data.excluded_from_dns)
					return false;
				
				if (item.itemId == 'option.dnsIn' && !data.excluded_from_dns)
					return false;
				
				if (item.itemId == 'option.console')
					return (data.platform == 'ec2');
				
				if (data.status == 'Importing' || data.status == 'Pending launch' || data.status == 'Temporary')
				{
					if (item.itemId == 'option.cancel' || item.itemId == 'option.messaging')
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
						'<tpl if="farm_id">Farm: <a href="#/farms/{farm_id}/view" title="Farm {farm_name}">{farm_name}</a>' +
							'<tpl if="farm_roleid">&nbsp;&rarr;&nbsp;<a href="#/farms/{farm_id}/roles/{farm_roleid}/view" title="Role {role_name}">{role_name}</a></tpl>' +
						'</tpl>' +
						'<tpl if="ismaster == 1"> (Master)</tpl>' +
						'<tpl if="! farm_id"><img src="/images/false.gif" /></tpl>'
					},
					{ header: "Server ID", width: '220px', dataIndex: 'server_id', sortable: true, hidden: 'no', tpl: new Ext.XTemplate(
						'<a href="#/servers/{server_id}/extendedInfo">{[this.serverId(values.server_id)]}</a>', {
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
					{ header: "DNS", width: '38px', dataIndex: 'excluded_from_dns', sortable: false, hidden: 'no', align: 'center', tpl:
						'<tpl if="excluded_from_dns"><img src="/images/false.gif" /></tpl><tpl if="!excluded_from_dns"><img src="/images/true.gif" /></tpl>'
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
					{ header: "Actions", width: '80px', dataIndex: 'id', sortable: false, align:'center', hidden: 'no', tpl: new Ext.XTemplate(
						'<tpl if="(status == &quot;Running&quot; || status == &quot;Initializing&quot;) && index != &quot;0&quot;">' +
							'<a style="float:left;margin-right:2px;margin-left:4px;" href="#/servers/{server_id}/sshConsole" target="_blank"><img style="margin-right:3px;" src="/images/terminal.png"></a>' +
							'<div style="float:left;margin-right:2px;cursor:pointer;" onClick="document.location.href =\'/monitoring.php?farmid={farm_id}&role={farm_roleid}&server_index={index}\';" class="scalr-menu-icon-stats">&nbsp;</div>' +
							'<div style="float:left;cursor:pointer;" onClick="document.location.href =\'#/scripts/execute?serverId={server_id}\';" class="scalr-menu-icon-execute">&nbsp;</div>' +
						'</tpl>' +
						'<tpl if="! ((status == &quot;Running&quot; || status == &quot;Initializing&quot;) && index != &quot;0&quot;)">' +
							'<img src="/images/false.gif">' +
						'</tpl>', {
							getServerId: function (serverId) {
								return serverId.replace(/-/g, '');
							}
						})
					}
				]
			},

			withSelected: {
				menu: [{
					text: 'Reboot',
					iconCls: 'scalr-menu-icon-reboot',
					request: {
						confirmBox: {
							type: 'reboot',
							msg: 'Reboot selected server(s)?'
						},
						processBox: {
							type: 'reboot',
							msg: 'Sending reboot command to the server(s). Please wait...'
						},
						url: '/servers/xServerRebootServers/',
						dataHandler: function (records) {
							var servers = [];
							for (var i = 0, len = records.length; i < len; i++) {
								servers[servers.length] = records[i].get('server_id');
							}

							return { servers: Ext.encode(servers) };
						}
					}
				}, {
					text: 'Terminate',
					iconCls: 'scalr-menu-icon-terminate',
					request: {
						confirmBox: {
							type: 'terminate',
							msg: 'Terminate selected server(s)?'
						},
						processBox: {
							type: 'terminate',
							msg: 'Terminating servers(s). Please wait...'
						},
						url: '/servers/xServerTerminateServers/',
						dataHandler: function (records) {
							var servers = [];
							for (var i = 0, len = records.length; i < len; i++) {
								servers[servers.length] = records[i].get('server_id');
							}

							return { servers: Ext.encode(servers) };
						}
					}
				}],
				renderer: function(data) {
					return (data.status == 'Running' || data.status == 'Initializing');
				}
			},

			listeners: {
				'render': function() {
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
	}
}