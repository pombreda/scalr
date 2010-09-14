{include file="inc/header.tpl"}
<link rel="stylesheet" href="css/grids.css" type="text/css" />
{literal}
<style>
	.x-grid3-cell-inner { white-space:normal !important; }
</style>
{/literal}
<div id="search-ct"></div> 
<div id="maingrid-ct" class="ux-gridviewer grid-padding-fix"></div>
<script type="text/javascript">
{literal}
Ext.onReady(function () {

	var farms_store = new Ext.data.SimpleStore({
	    fields: ['value', 'text'],
	    data : {/literal}{$farms}{literal}
	});
	
	// ---- Init search form
	var searchPanel = new Ext.FormPanel({
		style: 'margin-bottom:5px;margin-top:5px;',
		renderTo: document.body,
        labelWidth: 150,
        frame:true,
        title: 'Search',
        bodyStyle:'padding:5px 5px 0',
        defaultType: 'textfield',	
        
		items: [{
			width: 500,
			name: 'query',
			fieldLabel: 'Search string'
		}, {
			xtype: 'checkboxgroup',
			width: 500,
			fieldLabel: 'Severity',
			columns: 3,
            items: {/literal}{$severities}{literal},
			listeners: {
				render: {
					fn: function (cmp) {
						if (Ext.isIE) {
							cmp.el.select('.x-form-element').setStyle('width', '166px');
						}
					},
					delay: 20
				}
			}
		}, new Ext.form.ComboBox({
			id: 'farmid',
			allowBlank: true,
			editable: false, 
			valueField:'value',
			displayField:'text',
	        store: farms_store,
	        fieldLabel: 'Farm',
	        typeAhead: true,
	        mode: 'local',
	        triggerAction: 'all',
	        selectOnFocus:false
	    })],
		listeners: {
			render: {
				fn:	function () {
					// XXX: Direct renderTo: search-ct doesn't works with FormPanel
					Ext.get("search-ct").appendChild(this.el);
				},
				delay: Ext.isIE ? 20 : 0
			}
		},
		buttons: [
			{text: 'Filter', handler: doFilter},
			{text: 'Download Log', handler: doDownloadLog}
		]
		
	});
	
	function doDownloadLog ()
	{
		var filterParams = searchPanel.getForm().getValues(false); // get field with params
		var farmid = searchPanel.getForm().findField('farmid').value;
		if (farmid) 
		{
			filterParams.farmid = farmid;
		}		
		window.location = Ext.urlEncode(filterParams,"/logs_view.php?action=download&");// form string for get requst
			
		
	}
	
	
	function doFilter () {
		Ext.apply(store.baseParams, searchPanel.getForm().getValues(false));
		var farmid = searchPanel.getForm().findField('farmid').value;	
		store.baseParams.farmid = (farmid) ? farmid : '';

		var farm_clm_index = grid.getColumnModel().findColumnIndex('farm_name');

		if (farmid)
			grid.getColumnModel().setHidden(farm_clm_index, true);
		else
			grid.getColumnModel().setHidden(farm_clm_index, false);
		
		store.load();
	}
	
	// ---- Init grid
	
	// create the Data Store
    var store = new Ext.ux.scalr.Store({
        reader: new Ext.ux.scalr.JsonReader({
            root: 'data',
            successProperty: 'success',
            errorProperty: 'error',
            totalProperty: 'total',
            id: 'id',
            fields: [
				'id','serverid','message','severity','time','source','farmid','servername','farm_name', 's_severity'
            ]
        }),
        baseParams: {
        	sort: 'id',
        	dir: 'DESC'        	
        },
    	remoteSort: true,
		url: 'server/grids/event_log_list.php?a=1{/literal}{$grid_query_string}{literal}&showLog=1',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });
	Ext.apply(store.baseParams, Ext.ux.parseQueryString(window.location.href));
	
	function callerRenderer (value, p, record) {
		if (record.data.servername)
			return '<a href="/servers_view.php?server_id='+record.data.servername+'&farmid='+record.data.farmid+'">'+record.data.servername+'</a>/'+record.data.source;
		else
			return value;				
	}

	function farmRenderer (value, p, record) {
		return '<a href="farms_view.php?id='+record.data.farmid+'">'+value+'</a>';
	}


	function severityRenderer(value, p, record) {

		var img = '';
		if (value == '1')
			img = '/images/log_icons/debug.png';
		else if (value == '2')
			img = '/images/log_icons/info.png';
		else if (value == '3')
			img = '/images/log_icons/warning.png';
		else if (value == '4')
			img = '/images/log_icons/error.png';
		else if (value == '5')
			img = '/images/log_icons/fatal_error.png';

		return '<img src="'+img+'" title="'+record.data.s_severity+'">';
	}
	
    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
	var grid = new Ext.ux.scalr.GridViewer({
        //renderTo: "maingrid-ct",
        id: 'logs_list_'+GRID_VERSION,
        height: 500,
        store: store,
        title:'',
        maximize: false,
        enableFilter: false,
        sm: new Ext.grid.RowSelectionModel({singleSelect: true}),
        viewConfig: { 
        	emptyText: "No logs found",
        	getRowClass: function (record, index) {
        		if (record.data.severity > 3) {
        			return 'ux-row-red';
        		}

        		return '';
        	},
        	forceFit: true
        },
        split: true,
		region: 'north',
                
	    // Columns
        columns:[
			{header: "", width: 10, dataIndex: 'severity', renderer:severityRenderer, sortable: false, align:'center'},
			{header: "Time", width: 45, dataIndex: 'time', sortable: false},
			{header: "Farm", width: 25, dataIndex: 'farm_name', renderer:farmRenderer, sortable: false},
			{header: "Caller", width: 40, dataIndex: 'source', renderer:callerRenderer, sortable: false},
			{header: "Message", width: 160, dataIndex: 'message', sortable: false, css: 'white-space: normal !important;'}
		]
    });

	var farm_clm_index = grid.getColumnModel().findColumnIndex('farm_name');
	grid.getColumnModel().setHidden(farm_clm_index, false);
	
	// define a template to use for the detail view
	var TplMarkup = [
		'{message}'
	];
	var TplMessage = new Ext.Template(TplMarkup);

	var ct = new Ext.Panel({
		renderTo: 'maingrid-ct',
		frame: true,
		title: "Event Log {/literal}({$table_title_text}){literal}",
		maximize: true,
		height: 570,
		layout: 'border',
		items: [
			grid,
			{
				id: 'detailPanel',
				region: 'center',
				bodyStyle: {
					background: '#ffffff',
					padding: '7px'
				},
				html: ''
			}
		]
	})
	grid.getSelectionModel().on('rowselect', function(sm, rowIdx, r) {
		var detailPanel = Ext.getCmp('detailPanel');
		TplMessage.overwrite(detailPanel.body, r.data);
	});
	
    store.load();
});
{/literal}
</script>
{include file="inc/footer.tpl"}