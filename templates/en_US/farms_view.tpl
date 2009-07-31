{include file="inc/header.tpl"}
<link rel="stylesheet" href="css/grids.css" type="text/css" />
<div id="maingrid-ct" class="ux-gridviewer" style="padding: 5px;"></div>
<script type="text/javascript">
{literal}
Ext.onReady(function () {
	// create the Data Store
    var store = new Ext.ux.scalr.Store({
    	reader: new Ext.ux.scalr.JsonReader({
	        root: 'data',
	        successProperty: 'success',
	        errorProperty: 'error',
	        totalProperty: 'total',
	        id: 'id',
	        fields: [
				{name: 'id', type: 'int'},
				{name: 'clientid', type: 'int'},
	            {name: 'dtadded', type: 'date'},
	            'name', 'status', 'region', 'instances', 'roles', 'sites','client_email','havemysqlrole'
	        ]
	    }),
	    remoteSort: true,
		url: '/server/grids/farms_list.php?a=1{/literal}{$grid_query_string}{literal}',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });
	
    function renderStatus (value, p, record) {
    	var titles = {
			1: "Running",
    		0: "Terminated",	
    		2: "Terminating",
    		3: "Synchronizing"
    	};
    	var className;
    	if (value == 1) {
    		className = "status-ok";
    	} else if (value == 3) {
    		className = "status-ok-pending"
    	} else {
    		className = "status-fail";
    	}
    	
    	var title = titles[value] || value;
    	p.css += " "+className;
    	return title;
    }

	function rolesRenderer(value, p, record) {
		return value + ' [<a href="/roles_view.php?farmid='+record.data.id+'">View</a>]';
	}

	function instancesRenderer(value, p, record) {
		return value + ' [<a href="/instances_view.php?farmid='+record.data.id+'">View</a>]';
	}

	function sitesRenderer(value, p, record) {
		return value + ' [<a href="/sites_view.php?farmid='+record.data.id+'">View</a>]';
	}

	function clientsRenderer(value, p, record) {
		return '<a href="/clients_view.php?clientid='+record.data.clientid+'">'+record.data.client_email+'</a>';
	}
	
    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
	var grid = new Ext.ux.scalr.GridViewer({
        renderTo: "maingrid-ct",
        height: 500,
        title: "Farms",
        id: 'farms_list',
        store: store,
        maximize: true,
        viewConfig: { 
        	emptyText: "No farms found"
        },

        // Columns
        columns:[
			{header: "Farm ID", width: 25, dataIndex: 'id', sortable: true},
			{/literal}
				{if $smarty.session.uid == 0}
					{literal}
					{header: "Client", width: 50, dataIndex: 'client', renderer: clientsRenderer, sortable: false},
					{/literal}
				{/if}
			{literal}
			{header: "Farm Name", width: 50, dataIndex: 'name', sortable: true},
			{header: "Region", width: 30, dataIndex: 'region', sortable: true},
			{header: "Added", width: 50, dataIndex: 'dtadded', renderer: renderers.dateMjY, sortable: true}, 
			{header: "Roles", width: 30, dataIndex: 'roles', renderer: rolesRenderer, sortable: false},
			{header: "Instances", width: 30, dataIndex: 'instances', renderer: instancesRenderer, sortable: false},
			{header: "Applications", width: 30, dataIndex: 'sites', renderer: sitesRenderer, sortable: false},
			{header: "Status", width: 30, dataIndex: 'status', renderer: renderStatus, sortable: true}
		],
		
    	// Row menu
    	rowOptionsMenu: [
			{id: "option.viewMap", 		text:'View map', 			  	href: "/farm_map.php?id={id}"},
			new Ext.menu.Separator({id: "option.viewMapSep"}),
			
			{id: "option.privateKey", 	text: 'Download Private key', 	href: "/storage/keys/{id}/{name}.pem"},
			new Ext.menu.Separator({id: "option.privateKeySep"}),
			{id: "option.launchFarm", 	text: 'Launch', 				href: "/farms_control.php?farmid={id}"},
			{id: "option.terminateFarm",text: 'Terminate', 				href: "/farms_control.php?farmid={id}"},
			new Ext.menu.Separator({id: "option.controlSep"}),
			{id: "option.usageStats",	text: 'Usage statistics', 		href: "/farm_usage_stats.php?farmid={id}"},
			{id: "option.loadStats",	text: 'Load statistics', 		href: "/farm_stats.php?farmid={id}"},
			
			{id: "option.ebs",			text: 'EBS usage', 				href: "/ebs_manage.php?farmid={id}"},
			{id: "option.eip",			text: 'Elastic IPs usage', 		href: "/elastic_ips.php?farmid={id}"},
			{id: "option.events",		text: 'Events & Notifications', href: "/events_view.php?farmid={id}"},

			new Ext.menu.Separator({id: "option.mysqlSep"}),

			{id: "option.mysql",		text: 'MySQL status', 			href: "/farm_mysql_info.php?farmid={id}"},
			{id: "option.script",		text: 'Execute script', 		href: "/execute_script.php?farmid={id}"},

			{/literal}
			{if $rows[id].shortcuts|@count}
		   		{literal}
		   		new Ext.menu.Separator({id: "option.shortcutsSep"}),
		   		{/literal}
		   		{assign var=shortcuts value=$rows[id].shortcuts}
		   		{section name=sid loop=$shortcuts}
		   			{literal}
		   			{id: "option."+(Math.random()*100000),		text: 'Execute {/literal}&laquo;{$shortcuts[sid].name}&raquo;{literal}', href: "execute_script.php?farmid={id}&task=execute&script={/literal}{$shortcuts[sid].event_name}{literal}"},
		   			{/literal}
		   		{/section}
		   	{/if}
			{literal}
			new Ext.menu.Separator({id: "option.logsSep"}),
			{id: "option.logs",			text: 'View log', 				href: "/logs_view.php?farmid={id}"},
			new Ext.menu.Separator({id: "option.editSep"}),
			{id: "option.edit",			text: 'Edit', 					href: "/farms_add.php?id={id}"},
			{id: "option.delete",		text: 'Delete', 				href: "/farm_delete.php?id={id}"}
     	],
     	getRowOptionVisibility: function (item, record) {
			var data = record.data;

			if (item.id == "option.launchFarm")
				return (data.status == 0);

			if (item.id == "option.terminateFarm")
				return (data.status == 1);
			
			if (item.id == "option.viewMap" || 
					item.id == "option.viewMapSep" || 
					item.id == "option.loadStats" || 
					item.id == "option.mysqlSep" ||
					item.id == "option.mysql" ||
					item.id == "option.script"
				) {

				if (data.status == 0)
					return false;
				else
				{
					if (item.id != "option.mysql")
						return true;
					else
						return data.havemysqlrole;
				}
			}
			else
				return true;
		}
    });
    grid.render();
    store.load();

	return;
});
{/literal}
</script>
{include file="inc/footer.tpl"}