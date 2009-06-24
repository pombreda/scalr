{include file="inc/header.tpl"}
<br>
<link rel="stylesheet" href="css/grids.css" type="text/css" />
<div id="maingrid-ct" class="ux-gridviewer" style="padding: 5px;"></div>
<script type="text/javascript">
{literal}
Ext.onReady(function () {

	Ext.QuickTips.init();
	
	// create the Data Store
    var store = new Ext.ux.scalr.Store({
    	reader: new Ext.ux.scalr.JsonReader({
	        root: 'data',
	        successProperty: 'success',
	        errorProperty: 'error',
	        totalProperty: 'total',
	        id: 'id',
	        remoteSort: true,
	
	        fields: [
				{name: 'id', type: 'int'},
				'email', 'aws_accountid', 'farms', 'roles', 'apps', 'payments', 'isactive', 'farms_limit','fullname', 'comments'
	        ]
    	}),
		url: '/server/grids/clients_list.php?a=1{/literal}{$grid_query_string}{literal}',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });
	
	function farmRenderer(value, p, record) {
		return record.data.farms+' [<a href="/farms_view.php?clientid='+record.data.id+'">View</a>]';
	}

	function commentRenderer(value, p, record)
	{
		if (value && value.length != 0)
			return '<img ext:qtip="'+value.replace('"', '\"')+'" src=\'/images/comments.png\' />';
		else
			return '<img src=\'/images/false.gif\' />';
	}
	
	function roleRenderer(value, p, record) {
		return record.data.roles+' [<a href="/client_roles_view.php?clientid='+record.data.id+'">View</a>]';
	}

	function appRenderer(value, p, record) {
		return record.data.apps+' [<a href="/sites_view.php?clientid='+record.data.id+'">View</a>]';
	}

	function isactiveRenderer(value, p, record) {
		return (record.data.isactive == 1) ? '<img src=\'/images/true.gif\' />' : '<img src=\'/images/false.gif\' />';
	}
	
	function limitRenderer(value, p, record) {
		return (record.data.farms_limit == 0) ? 'Unlimited' : record.data.farms_limit;
	}
    	
    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
	var grid = new Ext.ux.scalr.GridViewer({
        renderTo: "maingrid-ct",
        height: 500,
        title: "Clients",
        id: 'clients_list1',
        store: store,
        maximize: true,
        viewConfig: { 
        	emptyText: "No clients defined"
        },

        // Columns
        columns:[
			{header: "E-mail", width: 120, dataIndex: 'email', sortable: true},
			{header: "Name", width: 120, dataIndex: 'fullname', sortable: true, hidden: true},
			{header: "AWS Account ID", width: 100, dataIndex: 'aws_accountid', sortable: true},
			{header: "Farms", width: 70, dataIndex: 'farms', renderer:farmRenderer, sortable: false},
			{header: "Custom roles", width: 70, dataIndex: 'roles', renderer:roleRenderer, sortable: false},
			{header: "Applications", width: 70, dataIndex: 'apps', renderer:appRenderer, sortable: false},
			{header: "Farms limit", width: 70, dataIndex: 'farms_limit', renderer: limitRenderer, sortable: false, hidden:true},
			{header: "Comment", width: 50, dataIndex: 'comments', renderer:commentRenderer, sortable: false, hidden:true, align:'center'},
			{header: "Active", width: 70, dataIndex: 'isactive', renderer:isactiveRenderer, sortable: false, align:'center'}
		],

		//TODO: Hide option for non-active rows
		
    	// Row menu
    	rowOptionsMenu: [
			{id: "option.edit", 		text:'Edit', 			  	href: "/clients_add.php?id={id}"},
			'-',
			{id: "option.login", 		text: 'Log in to Client CP', 	href: "/login.php?id={id}&isadmin=1"}
     	],

     	getRowOptionVisibility: function (item, record) {
			var data = record.data;

			return true;
		},

		getRowMenuVisibility: function (record) {
			return true;
		},
		// With selected options
		withSelected: {
			menu: [
				{text: "Activate", value: "activate"},
				{text: "Deactivate", value: "deactivate"},
				'-',
				{text: "Delete", value: "delete"}
			],
			hiddens: {with_selected : 1},
			action: "act"
		}
    });
    grid.render();
    store.load();

	return;
});
{/literal}
</script>
{include file="inc/footer.tpl"}
