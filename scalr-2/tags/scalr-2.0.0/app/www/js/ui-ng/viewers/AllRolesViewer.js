Ext.ns("Scalr.Viewers");

Scalr.Viewers.AllRolesViewer = Ext.extend(Ext.Panel, {
	platforms: [['ec2', 'Amazon EC2'], ['rds', 'Amazon RDS']],
	roles: [
		[ "base", "Base images" ],
		[ "mysql", "Database servers" ],
		[ "app", "Application servers" ],
		[ "www", "Load balancers" ],
		[ "memcached", "Caching servers" ]
	],
	// [['www', 'WWW roles'], ...] - list of all available roles

	layout: 'fit',
	height: 200,

	initComponent: function() {
		this.tbar = [{
			itemId: 'stable',
			xtype: 'checkbox',
			inputValue: 1,
			boxLabel: 'Stable',
			checked: true,
			style: 'margin: 0px'
		}, '-', 'Origin:', {
			itemId: 'origin',
			title: 'Origin',
			xtype: 'combo',
			store: [ 'Shared', 'Custom', 'Community' ],
			value: 'Shared',
			mode: 'local',
			forceSelection: true,
			editable: false,
			triggerAction: 'all',
			emptyText: 'Select a origin...',
			width: 100
		}, '-', 'Platform:', {
			itemId: 'platform',
			title: 'Platform',
			xtype: 'combo',
			store: this.platforms,
			value: 'ec2',
			mode: 'local',
			forceSelection: true,
			editable: false,
			triggerAction: 'all',
			emptyText: 'Select a role...',
			width: 100
		}, '->', 'Filter:', {
			itemId: 'filter',
			xtype: 'textfield'
		}],

		this.dataView = new Ext.DataView({
			roles: this.roles,
			filterRoles: function() {
				var filters = [];

				filters[filters.length] = {
					property: 'stable',
					value: this.getTopToolbar().getComponent('stable').getValue() ? 'true' : 'false'
				};

				filters[filters.length] = {
					property: 'origin',
					value: this.getTopToolbar().getComponent('origin').getValue()
				};

				filters[filters.length] = {
					property: 'platform',
					value: this.getTopToolbar().getComponent('platform').getValue()
				};

				if (this.getTopToolbar().getComponent('filter').getValue()) {
					filters[filters.length] = {
						fn: function(record) {
							return (record.get('name').toLowerCase().search(this.getValue().toLowerCase()) != -1) ? true : false;
						},
						scope: this.getTopToolbar().getComponent('filter')
					};
				}

				this.dataView.getStore().filter(filters);
			},

			collectData: function(records, startIndex) {
				var groups = [];
				for (key in this.rolesInfo) {
					var el = this.rolesInfo[key];
					groups[el.index] = { title: el.name, groupid: key, status: el.status, records: [] };
				}

				for (var i = 0, len = records.length; i < len; i++) {
					var role = this.rolesInfo[records[i].get('type')];
					groups[role.index].records[groups[role.index].records.length] = records[i].data;
				}

				return groups;
			},

			refresh: function() {
				this.clearSelections(false, true);
				var el = this.getTemplateTarget();
				el.update("");
				var records = this.store.getRange();

				// always show groups
				this.tpl.overwrite(el, this.collectData(records, 0));
				this.all.fill(Ext.query(this.itemSelector, el.dom));
				this.updateIndexes(0);

				this.hasSkippedEmptyText = true;

				// update links
				this.addCollapseLinks();
				this.addDescLinks();
			},

			addDescLinks: function() {
				Ext.select("#viewers-addrolesviewer ul li").each(function(el) {
					el.on('click', function(e, t) {
						var ind = this.store.find("item_id", t.getAttribute("itemid"));
						if (ind != -1) {
							var rec = this.store.getAt(ind);
							

							this.getEl().child("div.roles").hide();
							var desc = this.getEl().child("div.description");
							desc.show();
							desc.update(rec.get('description'));

						}
						
						
					}, this);
				}, this);
			},
			
			addCollapseLinks: function() {
				Ext.select("#viewers-addrolesviewer div.title").each(function(el) {
					handler = function(e) {
						var el = e.getTarget("", 10, true).findParent("div.title", 10, true);
						var groupid = el.getAttribute("groupid");

						if (this.rolesInfo[groupid]) {
							this.rolesInfo[groupid].status = (this.rolesInfo[groupid].status == "contract") ? "" : "contract";
						}

						el.toggleClass("title-contract");
						var ul = el.next("ul");
						if (ul) {
							ul.toggleClass("hidden");
						}
					};

					if (! el.is("div.title-disabled")) {
						el.on('click', handler, this);
					}
				}, this);
			},

			id: 'viewers-addrolesviewer',
			store: this.store,
			autoScroll: true,
			tpl: new Ext.XTemplate(
				'<div class="description"></div>',
				'<div class="roles">',
				'<tpl for=".">',
					'<div class="block">',
					'<div groupid="{groupid}" class="title',
					'<tpl if="records.length &gt; 0 && status == \'contract\'"> title-contract</tpl>',
					'<tpl if="records.length == 0"> title-disabled</tpl>',
					'"><div><span>{title}</span></div></div>',
					
					'<tpl if="records.length">',
					'<ul',
					'<tpl if="status == \'contract\'"> class="hidden"</tpl>',
						'<tpl for="records">',
							'<li itemid="{item_id}">',
								'Name: <b>{name}</b><br />',
								'Image Id: {ami_id}<br />',
								'Architecture: {arch}<br />',
								'Dt build: {dt_build}',
							'</li>',
						'</tpl>',
					'</ul>',
					'</tpl>',
					'</div>',
				'</tpl>',
				'</div>'
			),
			itemSelector: 'li'
		});

		this.dataView.rolesInfo = {};
		for (var i = 0; i < this.dataView.roles.length; i++) {
			this.dataView.rolesInfo[this.dataView.roles[i][0]] = {status: "contract", name: this.dataView.roles[i][1], index: i};
		}

		this.dataView.on('afterrender', this.dataView.filterRoles, this);

		this.dataView.on('afterrender', function() {
			this.getTopToolbar().getComponent('stable').on('check', this.dataView.filterRoles, this);
			this.getTopToolbar().getComponent('origin').on('select', this.dataView.filterRoles, this);
			this.getTopToolbar().getComponent('platform').on('select', this.dataView.filterRoles, this);
			Scalr.fireOnInputChange(this.getTopToolbar().getComponent('filter').getEl(), this, this.dataView.filterRoles);

			this.dataView.getEl().child('div.roles').setVisibilityMode(Ext.Element.DISPLAY);
			this.dataView.getEl().child('div.description').setVisibilityMode(Ext.Element.DISPLAY);
			this.dataView.getEl().child('div.description').hide();

			this.dataView.getEl().child('div.description').on('click', function() {
				this.getEl().child('div.roles').show();
				this.getEl().child('div.description').hide();
			}, this.dataView);

		}, this);

		this.items = [this.dataView];

		Scalr.Viewers.AllRolesViewer.superclass.initComponent.call(this);
		
		this.addEvents(
			"addrole",
			"deleterole"
		);
	}
});
