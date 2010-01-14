{include file="inc/header.tpl"}
<link rel="stylesheet" href="css/grids.css" type="text/css" />
{literal}
<style>
	.x-grid3-cell-inner { white-space:normal !important; }
		
	.icon_bg {
		background-image: url(/images/dashboard/icon_bg.png); 
		width:74px; 
		height:74px;
	}
</style>
{/literal}
<div>
	<div style="float:left;width:35%;">
		<div id="dashboard-acctinfo" class="ux-gridviewer"></div>
	</div>
	<div style="width:5px;float:left;"></div>
	<div style="float:right;width:64%;">
		<div id="dashboard-ql" class="ux-gridviewer grid-padding-fix"></div>
	</div>
</div>


<div style="clear:both;"></div>
<br />
<div id="dashboard-errors" class="ux-gridviewer"></div>

<script type="text/javascript">
{literal}
Ext.onReady(function () {
	
	var acctinfo = [
		{/literal}
    	'<table width="100%" style="background-color:#f9faff;" cellpadding="8" cellspacing="8">',
	    	'<tr>',
				'<td width="30%">Logged in as:</td>',
				'<td>{$client.email} [<a href="profile.php">Profile</a>]</td>',
			'</tr>',
    	'</table>'
	   	{literal}
    ];

	var shortcuts = [
		{/literal}
		'<table width="100%" height="100%" style="background-color:#f9faff;" cellpadding="8" cellspacing="8">',
	    	'<tr>',
		    	'<td width="14%" align="center">',
					'<div onmouseout="this.style.backgroundImage = \'url(/images/dashboard/icon_bg.png)\';" onmouseover="this.style.backgroundImage = \'url(/images/dashboard/icon_bg_hover.png)\';" class="icon_bg">',
						'<img src="/images/dashboard/icons/app_wizard.png" alt="Application wizard" title="Application wizard" onclick="document.location=\'/app_wizard.php\';" style="cursor:pointer;margin:5px;" />',
					'</div>',
				'</td>',
				'<td width="14%" align="center">',
					'<div onmouseout="this.style.backgroundImage = \'url(/images/dashboard/icon_bg.png)\';" onmouseover="this.style.backgroundImage = \'url(/images/dashboard/icon_bg_hover.png)\';" class="icon_bg">',
						'<img src="/images/dashboard/icons/farms.png" alt="Farms" title="Farms" onclick="document.location=\'/farms_view.php\';" style="cursor:pointer;margin:5px;" />',
					'</div>',
				'</td>',
				'<td width="14%" align="center">',
					'<div onmouseout="this.style.backgroundImage = \'url(/images/dashboard/icon_bg.png)\';" onmouseover="this.style.backgroundImage = \'url(/images/dashboard/icon_bg_hover.png)\';" class="icon_bg">',
						'<img title="Manage roles" alt="Manage roles" onclick="document.location=\'/client_roles_view.php\';" src="/images/dashboard/icons/roles.png" style="margin:5px; cursor:pointer;">&nbsp;</div>',
					'</div>',
				'</td>',
				'<td width="14%" align="center">',
					'<div onmouseout="this.style.backgroundImage = \'url(/images/dashboard/icon_bg.png)\';" onmouseover="this.style.backgroundImage = \'url(/images/dashboard/icon_bg_hover.png)\';" class="icon_bg">',
						'<img title="Logs" alt="Logs" onclick="document.location=\'/logs_view.php\';" src="/images/dashboard/icons/logs.png" style="margin:5px; cursor:pointer;">&nbsp;</div>',
					'</div>',
				'</td>',
				'<td width="14%" align="center">',
					'<div onmouseout="this.style.backgroundImage = \'url(/images/dashboard/icon_bg.png)\';" onmouseover="this.style.backgroundImage = \'url(/images/dashboard/icon_bg_hover.png)\';" class="icon_bg">',
						'<img title="EBS Volumes & Snapshots" alt="EBS Volumes & Snapshots" onclick="document.location=\'/ebs_manage.php\';" src="/images/dashboard/icons/ebs.png" style="margin:5px;cursor:pointer;" />',
					'</div>',
				'</td>',
				'<td width="14%" align="center">',
					'<div onmouseout="this.style.backgroundImage = \'url(/images/dashboard/icon_bg.png)\';" onmouseover="this.style.backgroundImage = \'url(/images/dashboard/icon_bg_hover.png)\';" class="icon_bg">',
						'<img title="Manage Elastic IPs" alt="Manage Elastic IPs" onclick="document.location=\'/elastic_ips.php\';" src="/images/dashboard/icons/eip.png" style="margin:5px; cursor:pointer;">&nbsp;</div>',
					'</div>',
				'</td>',
				'<td width="14%" align="center">',
					'<div onmouseout="this.style.backgroundImage = \'url(/images/dashboard/icon_bg.png)\';" onmouseover="this.style.backgroundImage = \'url(/images/dashboard/icon_bg_hover.png)\';" class="icon_bg">',
						'<img title="System settings" alt="System settings" onclick="document.location=\'/system_settings.php\';" src="/images/dashboard/icons/settings.png" style="margin:5px; cursor:pointer;">&nbsp;</div>',
					'</div>',
				'</td>',
			'</tr>',
    	'</table>'
		{literal}
	];
	
	var p3 = new Ext.Panel({
        title: 'Shortcuts',
        collapsible:false,
        renderTo: 'dashboard-ql',
        height: 140,
        bodyStyle: "background: #F9FAFF",        
       	html: shortcuts.join('')
    });
	
	var p = new Ext.Panel({
        title: 'Account information',
        collapsible:false,
        renderTo: 'dashboard-acctinfo',
        height: 140,
        bodyStyle: "background: #F9FAFF",
        html: acctinfo.join('')
    });
	
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
		url: 'server/grids/event_log_list.php?a=1&severity[]=3&severity[]=4&severity[]=5',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });
	Ext.apply(store.baseParams, Ext.ux.parseQueryString(window.location.href));
	
	function callerRenderer (value, p, record) {
		if (record.data.servername)
			return '<a href="/instances_view.php?iid='+record.data.servername+'&farmid='+record.data.farmid+'">'+record.data.servername+'</a>/'+record.data.source;
		else
			return value;				
	}

	function farmRenderer (value, p, record) {
		return '<a href="farms_view.php?id='+record.data.farmid+'">'+value+'</a>';
	}


	function severityRenderer(value, p, record) {

		var img = '';
		if (value == '0')
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
        renderTo: "dashboard-errors",
        id: 'd_logs_list_'+GRID_VERSION,
        height: 450,
        title: "Latest errors & warnings {/literal}({$table_title_text}){literal}",
        store: store,
        maximize: true,
        enablePaging:false,
        enableFilter: false,
        sm: new Ext.grid.RowSelectionModel({singleSelect: true}),
        viewConfig: { 
        	emptyText: "No errors found",
        	forceFit: true
        },
        split: true,
		region: 'north',
                
	    // Columns
        columns:[
			{header: "", width: 10, dataIndex: 'severity', renderer:severityRenderer, sortable: false, align:'center'},
			{header: "Time", width: 35, dataIndex: 'time', sortable: false},
			{header: "Farm", width: 25, dataIndex: 'farm_name', renderer:farmRenderer, sortable: false},
			{header: "Caller", width: 30, dataIndex: 'source', renderer:callerRenderer, sortable: false},
			{header: "Message", width: 160, dataIndex: 'message', sortable: false, css: 'white-space: normal !important;'}
		]
    });

    store.load();
});
{/literal}
</script>

   
{include file="inc/footer.tpl"}
