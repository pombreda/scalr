Ext.ns("Scalr.Viewers");
Ext.ns("Scalr.Viewers.list");

Scalr.Viewers.list.ListView = Ext.extend(Ext.list.ListView, {
	columnOrderPlugin: false,
	overClass: 'viewers-listview-row-over',
	selectedClass: 'viewers-listview-row-selected',
	itemSelector: 'dl.viewers-listview-row',
	elementSelector: 'dt',

	initComponent: function() {
		this.colResizer = new Scalr.Viewers.list.ColumnResizer();
		this.colResizer.init(this);

		this.colSorter = new Scalr.Viewers.list.ListViewSorter();
		this.colSorter.init(this);

		this.hideColumn = new Scalr.Viewers.list.HideColumn();
		this.hideColumn.init(this);

		if (this.columnOrderPlugin) {
			this.columnOrderPlugin = new Scalr.Viewers.list.OrderColumnPlugin();
			this.columnOrderPlugin.init(this);
		}

		if (this.actionColumnPlugin) {
			this.actionColumnPlugin = new Scalr.Viewers.list.ActionColumnPlugin();
			this.actionColumnPlugin.init(this);
		}

		this.internalTpl = new Ext.XTemplate(
			'<div class="x-list-header">',
				'<div class="x-list-header-inner">',
					'<tpl for="columns">',
						'<div ',
							'<tpl if="typeof(values.hidden) == \'undefined\' || values.hidden == \'no\' || values.hidden == \'disabled\'">',
								'style="width:{values.widthPx}px; text-align:{align};"',
							'</tpl>',
							'<tpl if="values.hidden == \'yes\'">',
								'style="display: none; text-align: {align};"',
							'</tpl>',
						'><em unselectable="on">{header}</em></div>',
					'</tpl>',
					'<div class="x-clear"></div>',
				'</div>',
				'<div class="viewers-listview-columns-icon"><img src="/images/ui-ng/viewers/listview/popup_icon.gif"></div>',
			'</div>',
			'<div class="x-list-body"><div class="x-list-body-inner">',
			'</div></div>'
		);

		this.tpl = new Ext.XTemplate(
			'<tpl for="rows">',
				'<dl class="viewers-listview-row {[xindex % 2 === 0 ? "viewers-listview-row-alt" : ""]}">',
					'<tpl for="parent.columns">',
						'<dt dataindex="{dataIndex}" style="text-align: {align}; ',
							'<tpl if="typeof(values.hidden) == \'undefined\' || values.hidden == \'no\' || values.hidden == \'disabled\'">',
								'width:{values.widthPx}px;',
							'</tpl>',
							'<tpl if="values.hidden == \'yes\'">',
								'display: none;',
							'</tpl>',
						'">',
						'<em class="<tpl if="cls">{cls}</tpl> {[this.getRowClass(values)]}" <tpl if="style">style="{style}"</tpl> >',
							'{[values.tpl.apply(parent)]}',
						'</em></dt>',
					'</tpl>',
					'<div class="x-clear"></div>',
				'</dl>',
			'</tpl>', {
				getRowClass: this.getRowClass
			}
		);

		var cs = this.columns,
			allocatedWidth = 0,
			colsWithWidth = 0,
			len = cs.length,
			columns = [];

		for (var i = 0; i < len; i++) {
			var c = cs[i];
			if (!c.isColumn) {
				c.xtype = c.xtype ? (/^lv/.test(c.xtype) ? c.xtype : 'lv' + c.xtype) : 'lvscalrcolumn';
				c = Ext.create(c);
			}
			columns.push(c);
		}

		this.columns = columns;
		this.emptyText = '<div class="viewers-listview-empty">' + this.emptyText + '</div>';

		if (! this.singleSelect)
			this.onClick = Ext.emptyFn;

		Ext.list.ListView.superclass.initComponent.call(this);

		this.addEvents('refresh');
		Ext.apply(this, {
			refresh: this.refresh.createSequence(function() {
				this.fireEvent('refresh');
			}, this)
		});
	},

	getRowClass: function (data) {
		return '';
	},

	onRender : function() {
		this.autoEl = {
			cls: 'x-list-wrap'
		};
		Ext.list.ListView.superclass.onRender.apply(this, arguments);

		this.internalTpl.overwrite(this.el, {columns: this.columns});

		this.innerBody = Ext.get(this.el.dom.childNodes[1].firstChild);
		this.innerHd = Ext.get(this.el.dom.firstChild.firstChild);

		this.updateColumnWidth();
		this.setHdWidths();

		if(this.hideHeaders){
			this.el.dom.firstChild.style.display = 'none';
		}
	},

	// private
	onResize : function(w, h) {
		var bd = this.innerBody.dom;
		var hd = this.innerHd.dom;
		if (!bd) {
			return;
		}
		var bdp = bd.parentNode;

		if (Ext.isNumber(w)){
			var sw = w - 19; // width of columns-icon
			bd.style.width = sw + 'px';
			hd.style.width = sw + 'px';
		}

		if (Ext.isNumber(h) && h > 0){
			bdp.style.height = (h - hd.parentNode.offsetHeight) + 'px';
		}

		this.updateColumnWidth();
		this.setHdWidths();
		this.setBodyWidths();
		this.saveState();
	},

	updateIndexes : function() {
		Ext.list.ListView.superclass.updateIndexes.apply(this, arguments);
	},

	updateColumnWidth: function() {
		var columns = 0, fixedWidth = 0, allWidth = 0, availWidth = this.innerBody.getWidth(), averageWidth = 0;
		for (var i = 0, len = this.columns.length; i < len; i++) {
			if (this.columns[i].hidden && this.columns[i].hidden == 'yes')
				continue;

			if (Ext.isNumber(this.columns[i].width)) {
				columns++;
				allWidth += this.columns[i].width;
			} else {
				this.columns[i].widthPx = parseInt(this.columns[i].width);
				availWidth -= this.columns[i].widthPx;
			}
		}
		availWidth -= 2; // borders of viewers-listview-row

		if (columns > 0 && availWidth > 0) {
			averageWidth = Math.floor(availWidth / allWidth);
			for (var i = 0, len = this.columns.length; i < len; i++) {
				if (this.columns[i].hidden && this.columns[i].hidden == 'yes') {
					this.columns[i].widthPx = 0;
					continue;
				}

				if (Ext.isNumber(this.columns[i].width)) {
					var prepWidth = Math.floor(averageWidth * this.columns[i].width);
					if (columns == 1) {
						// last columns
						this.columns[i].widthPx = availWidth;
						break;
					} else {
						if ((availWidth - prepWidth) > 0) {
							this.columns[i].widthPx = prepWidth;
							availWidth -= prepWidth;
						} else {
							this.columns[i].widthPx = availWidth;
							availWidth = 0;
						}

						columns--;
					}
				}
			}
		}
	},

	setHdWidths: function() {
		var els = this.innerHd.dom.getElementsByTagName('div');
		for(var i = 0, cs = this.columns, len = cs.length; i < len; i++){
			els[i].style.width = (typeof(cs[i].widthPx) != "undefined" ? cs[i].widthPx : 0) + 'px';
			els[i].style.display = (cs[i].hidden != 'yes') ? 'block' : 'none';
		}
	},

	setBodyWidths: function() {
		var lines = this.innerBody.dom.getElementsByTagName('dl');
		for (var i = 0, len = lines.length; i < len; i++) {
			var dt = lines[i].getElementsByTagName('dt');
			for (var j = 0, cs = this.columns, lenCS = cs.length; j < lenCS; j++) {
				dt[j].style.width = (typeof(cs[j].widthPx) != "undefined" ? cs[j].widthPx : 0) + 'px';
				dt[j].style.display = (cs[j].hidden != 'yes') ? 'block' : 'none';
			}
		}
	}
});

Scalr.Viewers.list.Column = Ext.extend(Ext.list.Column, {
	style: ''
});
Ext.reg('lvscalrcolumn', Scalr.Viewers.list.Column);

Scalr.Viewers.list.OrderColumnPlugin = Ext.extend(Ext.util.Observable, {
	constructor: function (config) {
		Ext.apply(this, config);
		Scalr.Viewers.list.OrderColumnPlugin.superclass.constructor.call(this);
	},

	init: function(listView) {
		this.view = listView;

		this.view.columns.push({
			header: '&nbsp;',
			width: '50px',
			cls: 'viewers-listview-row-order-plugin',
			sortable: false,
			tpl: '<img src="/images/up_icon.png" class="up" style="cursor: pointer"> <img src="/images/down_icon.png" class="down" style="cursor: pointer">'
		});

		this.view.on('refresh', this.onRefresh, this);
	},

	onRefresh: function() {
		this.view.getTemplateTarget().select("img.up").each(function(el) {
			el.on('click', this.onClick, this.view);
		}, this);

		this.view.getTemplateTarget().select("img.down").each(function(el) {
			el.on('click', this.onClick, this.view);
		}, this);
	},

	onClick: function(e) {
		var item = e.getTarget(this.itemSelector, this.getTemplateTarget()), index = this.indexOf(item), el = e.getTarget(null, null, true);

		if (el.is('img.up') && index > 0) {
			var record = this.store.getAt(index);
			this.store.removeAt(index);
			this.store.insert(index - 1, record);
		} else if (el.is('img.down') && (index < this.store.getCount() - 1)) {
			var record = this.store.getAt(index);
			this.store.removeAt(index);
			this.store.insert(index + 1, record);
		}

		this.refresh();
	}
});

Scalr.Viewers.list.ActionColumnPlugin = Ext.extend(Ext.util.Observable, {
	constructor: function (config) {
		Ext.apply(this, config);
		Scalr.Viewers.list.ActionColumnPlugin.superclass.constructor.call(this);
	},

	init: function(listView) {
		listView.on('afterrender', function () {
			var cache = {};
			for (var i = 0; i < this.columns.length; i++) {
				if (this.columns[i].clickHandler)
					cache[this.columns[i].dataIndex] = this.columns[i].clickHandler;
			}

			this.getTemplateTarget().on('click', function (e) {
				var column = e.getTarget(this.elementSelector, this.getTemplateTarget(), true).getAttribute('dataindex');
				if (column && cache[column]) {
					var item = e.getTarget(this.itemSelector, this.getTemplateTarget()), index = this.indexOf(item), record = this.store.getAt(index);
					cache[column].call(this, this, this.store, record);
				}
			}, this);
		}, listView);
	}
});

Scalr.Viewers.list.ColumnResizer = Ext.extend(Ext.list.ColumnResizer, {
	onBeforeStart: function(e) {
		this.dragHd = this.activeHd;
		if (this.dragHd) {
			var hdIndex = this.view.findHeaderIndex(this.dragHd), index = hdIndex, len = this.view.columns.length;
			this.hdRealIndex = -1;
			this.hdRealNextIndex = -1;
			this.hdRealDiff = 0; // diff between first and next columns (columns with persist width)
			this.hdRealNextDiff = 0;

			while (index >= 0) {
				if (this.view.columns[index].hidden && this.view.columns[index].hidden == 'yes') {
					index--;
					continue;
				}

				if (Ext.isNumber(this.view.columns[index].width)) {
					this.hdRealIndex = index;
				} else {
					this.hdRealDiff += parseInt(this.view.columns[index].width);
					index--;
					continue;
				}
				break;
			}

			if (this.hdRealIndex == -1) {
				return false;
			}

			index = hdIndex + 1;
			while (index < len) {
				if (this.view.columns[index].hidden && this.view.columns[index].hidden == 'yes') {
					index++;
					continue;
				}

				if (Ext.isNumber(this.view.columns[index].width)) {
					this.hdRealNextIndex = index;
				} else {
					this.hdRealNextDiff += parseInt(this.view.columns[index].width);
					index++;
					continue;
				}
				break;
			}

			if (this.hdRealNextIndex == -1) {
				return false;
			}

			// replace dragHd and activeHd with hdRealIndex (error left size for hidden columns)
			this.dragHd = this.activeHd = Ext.get(this.view.innerHd.dom.childNodes[this.hdRealIndex]);
			return true;
		}
		return false;
	},

	onEnd: function(e) {
		/* calculate desired width by measuring proxy and then remove it */
		var nw = this.proxy.getWidth();
		this.proxy.remove();

		var vw = this.view,
			cs = vw.columns,
			len = cs.length,
			w = this.view.innerHd.getWidth(),
			curw = cs[this.hdRealIndex].widthPx,
			nextw = cs[this.hdRealNextIndex].widthPx,
			allw = 0,
			wp = 0;

		cs[this.hdRealIndex].widthPx = Math.min(Math.max(20, nw - this.hdRealDiff), (curw + nextw - 20));
		cs[this.hdRealNextIndex].widthPx = nextw + curw - cs[this.hdRealIndex].widthPx;

		// calculate summary
		for (var i = 0; i < len; i++) {
			if (cs[i].hidden && cs[i].hidden == 'yes')
				continue;

			if (Ext.isNumber(cs[i].width)) {
				allw += cs[i].widthPx;
			}
		}

		// restore width percentages
		for (var i = 0; i < len; i++) {
			if (cs[i].hidden && cs[i].hidden == 'yes')
				continue;

			if (Ext.isNumber(cs[i].width)) {
				this.view.columns[i].width = cs[i].widthPx / allw * 100;
			}
		}

		delete this.dragHd;
		vw.setHdWidths();
		vw.setBodyWidths();
		vw.saveState();

		setTimeout(function(){
			vw.disableHeaders = false;
		}, 100);
	}
});

Scalr.Viewers.list.HideColumn = Ext.extend(Ext.util.Observable, {
	constructor: function(config){
		Ext.apply(this, config);
		Scalr.Viewers.list.HideColumn.superclass.constructor.call(this);
	},

	init: function(listView) {
		this.view = listView;
		this.view.addEvents('columnhide', 'columnshow');
		this.view.setHiddenColumn = this.setHiddenColumn;
		listView.on('afterrender', this.initEvents, this.view);
	},

	initEvents: function(view) {
		this.columnsMenu = new Ext.menu.Menu();

		for (var i = 0, len = this.columns.length; i < len; i++) {
			if (this.columns[i].hidden) {
				this.columnsMenu.addItem(
					new Ext.menu.CheckItem({
						text: this.columns[i].header,
						dataIndex: this.columns[i].dataIndex,
						checked: (this.columns[i].hidden == 'no') ? true : false,
						disabled: (this.columns[i].hidden == 'disabled') ? true : false,
						hideOnClick: false,
						listeners: {
							'checkchange': function(item, checked) {
								var column = null;
								for (var i = 0, len = this.columns.length; i < len; i++) {
									if (this.columns[i].dataIndex == item.dataIndex) {
										this.columns[i].hidden = this.columns[i].hidden == 'no' ? 'yes' : 'no';
										column = this.columns[i];
									}
								}
								this.updateColumnWidth();
								this.setHdWidths();
								this.setBodyWidths();
								this.saveState();

								if (column)
									this.fireEvent(column.hidden == 'no' ? 'columnshow' : 'columnhide', column);
							},
							scope: this
						}
					})
				);
			}
		}

		view.on('refresh', function() {
			this.getEl().child('div.viewers-listview-columns-icon').on('click', function(e) {
				this.columnsMenu.showAt(e.getXY());
				e.stopEvent();
			}, this);
		}, this);
	},

	setHiddenColumn: function (dataIndex, hidden) {
		for (var i = 0, len = this.columns.length; i < len; i++) {
			if (this.columns[i].dataIndex == dataIndex) {
				this.columns[i].hidden = hidden ? 'yes' : 'no';
				break;
			}
		}
		this.updateColumnWidth();
		this.setHdWidths();
		this.setBodyWidths();
		this.saveState();
	}
});

Scalr.Viewers.list.ListViewSorter = Ext.extend(Ext.list.Sorter, {
	onHdClick: function(e) {
		var hd = e.getTarget('em', 3);
		if (hd && !this.view.disableHeaders) {
			var index = this.view.findHeaderIndex(hd);
			if (this.view.columns[index].sortable && this.view.columns[index].sortable == true) {
				this.view.store.sort(this.view.columns[index].dataIndex);
				this.view.saveState();
			}
		}
	}
});

Scalr.Viewers.ListView = Ext.extend(Ext.Panel, {
	messages: {
		pageSize: "{0} items per page",
		options: "Options",
		tickTrue: "Yes",
		tickFalse: "No",
		withSelected: "With selected",
		blankSelection: "Please select at least one item",
		filter: "Filter"
	},
	linkTplsCache: {},

	listViewOptions: {},

	defaultListViewOptions: {
		emptyText: 'No records to display',
		autoScroll: true
	},

	stateful: false,
	savePagingSize: false, // save only paging size
	savePagingNumber: false, // save paging number, column's sort, column's visibility (save paging size too)
	saveFilter: false, // save filter settings

	enableFilter: true,
	enablePaging: true,
	enableAutoLoad: true,
	maximize: true,

	defaultPageSize: 10,
	pageSizes: [10, 15, 25, 50, 100],

	initComponent: function() {
		Ext.applyIf(this.listViewOptions, this.defaultListViewOptions);
		Ext.apply(this.listViewOptions, {
			store: this.store
		});

		// create paging toolbar
		if (this.enablePaging) {
			this.pagingToolbar = new Ext.PagingToolbar({
				onLoad: function (store, r, o) {
					if (!this.rendered) {
						this.dsLoaded = [store, r, o];
						return;
					}
					var p = this.getParams(), total = this.store.getTotalCount();
					var cursor = (o.params && o.params[p.start]) ? o.params[p.start] : 0;
					// also require bugfix on server side
					// like this *** if ($response['total'] && $start > $response['total']) $start = floor($response['total'] / $limit) * $limit; ***
					if (cursor > total && this.pageSize) {
						this.cursor = Math.floor(total / this.pageSize) * this.pageSize;
					} else {
						this.cursor = cursor;
					}

					var d = this.getPageData(), ap = d.activePage, ps = d.pages;

					this.afterTextItem.setText(String.format(this.afterPageText, d.pages));
					this.inputItem.setValue(ap);
					this.first.setDisabled(ap == 1);
					this.prev.setDisabled(ap == 1);
					this.next.setDisabled(ap == ps);
					this.last.setDisabled(ap == ps);
					this.refresh.enable();
					this.updateInfo();
					this.fireEvent('change', this, d);
				},
				pageSize: this.defaultPageSize,
				store: this.store
			});
			this.bbar = this.pagingToolbar;
		}

		// create options menu
		if (this.rowOptionsMenu) {
			this.rowOptionsMenu = new Ext.menu.Menu({
				items: this.rowOptionsMenu,
				listeners: {
					itemclick: function(item, e) {
						if (typeof(item.confirmationMessage) != "undefined") {
							Ext.MessageBox.show({
								title: item.confirmationTitle || 'Confirm',
								msg: item.confirmationMessage,
								buttons: Ext.Msg.YESNO,
								fn: function(btn) {
									if (btn == 'yes') {
										if (typeof(item.menuHandler) == "function") {
											item.menuHandler(item);
										} else {
											document.location.href = item.el.dom.href;
										}
									}
								}
							});
							e.preventDefault();
						} else {
							if (typeof(item.menuHandler) == "function") {
								item.menuHandler(item);
								e.preventDefault();
							}
						}
					}
				}
			});

			// Add options column
			this.listViewOptions.columns.push({
				header: '&nbsp;',
				width: '116px',
				resizable: false,
				align: 'center',
				cls: 'viewers-listview-row-options',
				tpl:
					new Ext.XTemplate(
						'<tpl if="this.getVisible(values)"><div class="viewers-listview-row-options-btn">Options<div class="viewers-listview-row-options-trigger"></div></div></tpl>', {
							getVisible: this.getRowMenuVisibility
						}
					)
			});
		}

		// with selected menu
		if (this.withSelected) {
			var withSelectedMenu = new Ext.menu.Menu(this.withSelected.menu);
			var items = [ '->', { itemId: 'withselectedmenu', text: this.messages.withSelected, menu: withSelectedMenu } ];
			if (this.pagingToolbar) {
				this.pagingToolbar.add(items);
			} else {
				this.bbar = items;
			}

			withSelectedMenu.on('click', this.withSelectedMenuHandler, this);

			this.listViewOptions.multiSelect = true;
			this.listViewOptions.columns.push({
				header: '<input type="checkbox" class="withselected" />',
				width: '25px',
				resizable: false,
				sortable: false,
				tpl:
					new Ext.XTemplate('<input type="checkbox" <tpl if="!this.getVisible(values)">disabled="true"</tpl> class="withselected" />', {
						getVisible: ((typeof(this.withSelected.renderer) == "function") ? this.withSelected.renderer : function(data) { return true; })
					})
			});
		}

		if (this.enableFilter) {
			this.filterField = new Scalr.Viewers.FilterField({
				store: this.store
			});

			var tbitems = [ this.messages.filter +': ', this.filterField ];
			if (this.tbar) {
				tbitems.push('-');
				this.tbar.unshift(tbitems);
			} else {
				this.tbar = tbitems;
			}
		}

		Scalr.Viewers.ListView.superclass.initComponent.call(this);

		if (this.maximize) {
			var autoSize = new Scalr.Viewers.autoSize();
			autoSize.init(this);
		}
	},

	withSelectedMenuHandler: function(menu, item, e) {
		if (this.listView.getSelectionCount()) {
			var store = this.store, records = this.listView.getSelectedRecords();
			var idProperty = store.idProperty || store.reader.meta.id || "id";
			var method = item.method || "post";
			var url = item.url || "";

			var proccessMenuHandler = function() {
				item.params = item.params || {};
				if (typeof(item.dataHandler) == "function") {
					Ext.apply(item.params, item.dataHandler(records));
				}

				if (method == "get" || method == "post") {
					var form = Ext.getBody().createChild({ tag: 'form', method: method, action: url, style: 'display: none' });

					if (typeof(item.dataHandler) != "function") {
						for (var i = 0, len = records.length; i < len; i++) {
							form.createChild({ tag: 'input', type: 'hidden', value: records[i].id, name: idProperty + '[]' });
						}
					}

					if (item.params) {
						for (key in item.params) {
							form.createChild({ tag: 'input', type: 'hidden', value: item.params[key], name: key });
						}
					}

					form.dom.submit();
				} else if (method == "ajax") {

					if (typeof (item.progressMessage) != "undefined") {
						Ext.MessageBox.show({
							progress: true,
							msg: item.progressMessage,
							wait: true,
							width: 450,
							icon: item.progressIcon || ''
						});
					}

					Ext.Ajax.request({
						url: url,
						success: function(response, options) {
							Ext.MessageBox.hide();

							var result = Ext.decode(response.responseText);
							if (result.result == 'ok') {
								store.load();
							} else {
								Scalr.Viewers.ErrorMessage(result.msg);
							}
						},
						params: Ext.urlEncode(item.params)
					});
				}
			}

			if (typeof(item.confirmationMessage) != "undefined") {

				Ext.MessageBox.show({
					title: item.confirmationTitle || 'Confirm',
					msg: item.confirmationMessage,
					buttons: Ext.Msg.YESNO,
					fn: function(btn) {
						if (btn == 'yes')
							proccessMenuHandler();
					}
				});
			} else {
				proccessMenuHandler();
			}
		} else {
			Ext.Msg.alert('Notice', this.messages.blankSelection);
		}
	},

	applyState: function(state) {
		this.state = state;
	},

	// for override
	getLocalState: function() {
		return {};
	},

	getState: function() {
		var result = this.getLocalState();

		result['pageSize'] = this.pagingToolbar ? this.pagingToolbar.pageSize : this.defaultPageSize;
		result['pageStart'] = this.pagingToolbar ? this.pagingToolbar.cursor : 0;
		result['pageSortState'] = this.store.getSortState();
		result['pageFilter'] = this.filterField ? this.filterField.getValue() : '';

		result['pageColumns'] = [];
		for (var i = 0, len = this.listView.columns.length; i < len; i++) {
			result['pageColumns'][i] = {};
			if (this.listView.columns[i].width)
				result['pageColumns'][i].width = this.listView.columns[i].width;

			if (this.listView.columns[i].hidden)
				result['pageColumns'][i].hidden = this.listView.columns[i].hidden;
		}

		return result;
	},

	onRender: function(container, position) {
		if (this.enablePaging && this.savePagingSize && this.state && this.state.pageSize) {
			this.defaultPageSize = this.state.pageSize;
		}

		if (this.savePagingSize && this.state && this.state.pageSortState) {
			this.store.setDefaultSort(this.state.pageSortState.field, this.state.pageSortState.direction);
		}

		// restore column's width
		if (this.savePagingNumber && this.state && this.state.pageColumns) {
			for (var i = 0, len = this.listViewOptions.columns.length; i < len; i++) {
				if (this.listViewOptions.columns[i] && this.state.pageColumns[i]) {
					if (! Ext.isNumber(this.listViewOptions.columns[i].width)) {
						continue; // don't overwrite fixed width
					}

					if (this.listViewOptions.columns[i].width && this.state.pageColumns[i].width)
						this.listViewOptions.columns[i].width = this.state.pageColumns[i].width;

					if (this.listViewOptions.columns[i].hidden && this.state.pageColumns[i].hidden)
						this.listViewOptions.columns[i].hidden = this.state.pageColumns[i].hidden;
				}
			}
		}

		if (this.saveFilter && this.state && this.state.pageFilter) {
			this.filterField.setValue(this.state.pageFilter);
		}

		// create listview
		this.listView = new Scalr.Viewers.list.ListView(this.listViewOptions);
		this.listView.saveState = this.saveState.createDelegate(this);

		if (this.rowOptionsMenu) {
			this.listView.on('refresh', function() {
				this.listView.innerBody.select('div.viewers-listview-row-options-btn').on('click', this.showOptions, this);
			}, this);
		}

		if (this.withSelected) {
			this.listView.on('refresh', function() {
				this.innerHd.child('input.withselected').dom.checked = false;
				this.innerBody.select('input.withselected').on('click', function(ev, el) {
					if (el.checked) {
						this.select(ev.getTarget("dl.viewers-listview-row"), true);
					} else {
						this.deselect(ev.getTarget("dl.viewers-listview-row"));
						this.innerHd.child('input.withselected').dom.checked = false;
					}
				}, this);
			}, this.listView);

			this.listView.on('afterrender', function() {
				this.innerHd.select('input.withselected').on('click', function(ev, el) {
					this.innerBody.select('input.withselected').each(function(elem) {
						if (el.checked) {
							if (! elem.dom.disabled) {
								elem.dom.checked = el.checked;
								this.select(elem.parent("dl.viewers-listview-row"), true);
							}
						} else {
							elem.dom.checked = el.checked;
						}
					}, this);

					if (! el.checked) {
						this.clearSelections();
					}
				}, this);
			}, this.listView);
		}

		// Render row options menu when needed
		if (this.rowOptionsMenu) {
			this.rowOptionsMenu.render();
		}

		// call super
		Scalr.Viewers.ListView.superclass.onRender.call(this, container, position);

		this.loadMask = new Ext.LoadMask(this.getEl(), { store: this.store });

		this.on('bodyresize', function(p, width, height) {
			width = width - p.body.getBorderWidth('lr');
			height = height - p.body.getBorderWidth('tb');

			this.listView.setSize(width, height);
		});

		this.add(this.listView);
	},

	afterRender: function() {
		Scalr.Viewers.ListView.superclass.afterRender.call(this);

		if (this.pagingToolbar) {
			this.loadMask.show();

			if (! (this.savePagingSize && typeof(this.state) != "undefined" && typeof(this.state.pageSize) != "undefined")) {
				// try to discover optimal PageSize
				var height = this.body.getHeight() - 26; // header's height
				var num = Math.floor(height / 26); // row's height

				for (var i = 0, len = this.pageSizes.length; i < len; i++) {
					if (num > this.pageSizes[i]) {
						this.defaultPageSize = this.pageSizes[i];
					} else {
						break;
					}
				}
			}

			this.pagingToolbar.pageSize = this.defaultPageSize;
			this.store.setBaseParam("limit", this.pagingToolbar.pageSize);

			var menu = [];
			for (var i = 0; i < this.pageSizes.length; i++) {
				menu.push({
					group: 'pagesize',
					text: this.pageSizes[i].toString(),
					checked: this.pageSizes[i] == this.defaultPageSize,
					handler: this.changePageSize,
					scope: this
				});
			}

			this.pagingToolbar.insert(11, '-');
			this.pageSizeBtn = this.pagingToolbar.insert(12, {
				text: String.format(this.messages.pageSize, this.defaultPageSize),
				menu: menu
			});

			// check for saved page
			if (this.savePagingNumber && this.state && this.state.pageStart) {
				this.pagingToolbar.doLoad(this.state.pageStart);
			} else {
				this.pagingToolbar.doLoad(0);
			}

			this.pagingToolbar.on('change', this.saveState, this);
		} else {
			if (this.enableAutoLoad)
				this.store.load();
		}
	},

	showOptions: function (ev) {
		var i = this.listView.indexOf(ev.getTarget("dl.viewers-listview-row"));
		var record = this.store.getAt(i), data = record.data;
    	this.fireEvent("beforeshowoptions", this, record, this.rowOptionsMenu, ev);

    	this.rowOptionsMenu.items.each(function (item) {
    		var display = this.getRowOptionVisibility(item, record);
			item.currentRecordData = record.data; // save for future use
			item[display ? "show" : "hide"]();
    		if (display && item.href) { // Update item link
    			if (!this.linkTplsCache[item.id]) {
    				this.linkTplsCache[item.id] = new Ext.Template(item.href).compile();
    			}
    			var tpl = this.linkTplsCache[item.id];
    			item.el.dom.href = tpl.apply(record.data);
    		}
    	}, this);

     	var btnEl = Ext.get(ev.getTarget('div.viewers-listview-row-options-btn'));
    	var xy = btnEl.getXY();
    	this.rowOptionsMenu.showAt([xy[0] - (this.rowOptionsMenu.getEl().getWidth() - btnEl.getWidth()), xy[1] + btnEl.getHeight()]);
	},

	getRowMenuVisibility: function (data) {
		return true;
	},

	getRowOptionVisibility: function (menuItem, record) {
		return true;
	},

	changePageSize: function(cmp) {
		this.pagingToolbar.pageSize = Number(cmp.text);
		this.pageSizeBtn.setText(String.format(this.messages.pageSize, this.pagingToolbar.pageSize));
		this.store.setBaseParam("limit", this.pagingToolbar.pageSize);
		this.pagingToolbar.changePage(0);

		if (this.savePagingSize) {
			this.saveState();
		}
	}
});
