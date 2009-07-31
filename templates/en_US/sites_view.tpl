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
	            'zone', 'string_status', 'status', 'role_alias', 'role_name', 'farmid', 'farm_name', 'ami_id'
	        ]
    	}),
    	remoteSort: true,
		url: '/server/grids/apps_list.php?a=1{/literal}{$grid_query_string}{literal}',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });
	
    function renderStatus (value, p, record) {
    	var className;
    	if (record.data.status == 0) {
    		className = "status-ok";
    	} else if (record.data.status == 3) {
    		className = "status-ok-pending"
    	} else {
    		className = "status-fail";
    	}

    	p.css += " "+className;
    	return value;
    }

	function farmRenderer(value, p, record) {
		return '<a href="/farms_view.php?farmid='+record.data.farmid+'">'+record.data.farm_name+'</a>';
	}

	function roleRenderer(value, p, record) {
		return '<a href="/roles_view.php?farmid='+record.data.farmid+'&ami_id='+record.data.ami_id+'">'+record.data.role_name+'</a>';
	}
    	
    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
	var grid = new Ext.ux.scalr.GridViewer({
        renderTo: "maingrid-ct",
        height: 500,
        title: "Applications",
        id: 'apps_list',
        store: store,
        maximize: true,
        viewConfig: { 
        	emptyText: "No applications found"
        },

        // Columns
        columns:[
			{header: "Domain name", width: 100, dataIndex: 'zone', sortable: true},
			{header: "Farm", width: 100, dataIndex: 'farm_name', renderer:farmRenderer, sortable: false},
			{header: "Role", width: 100, dataIndex: 'role_name', renderer:roleRenderer, sortable: true},
			{header: "DNS Zone status", width: 50, dataIndex: 'string_status', renderer: renderStatus, sortable: false}
		],

		//TODO: Hide option for non-active rows
		
    	// Row menu
    	rowOptionsMenu: [
			{id: "option.edit", 		text:'Edit DNS Zone', 			  	href: "/sites_add.php?ezone={zone}"},
			new Ext.menu.Separator({id: "option.editSep"}),
			{id: "option.switch", 	text: 'Switch application to another farm / role', 	href: "/app_switch.php?application={zone}"},
			{id: "option.configureVhost", 	text: 'Configure apache virtual host', 	href: "/vhost.php?name={zone}"}
     	],

     	getRowOptionVisibility: function (item, record) {
			var data = record.data;

			if (data.status != 0)
			{
				if (item.id != 'option.switch' || data.status != 1)
					return false;
				else
					return true;
			}
			else
			{
				if (item.id == 'option.configureVhost')
				{
					if (data.role_alias == 'app' || data.role_alias == 'www')
						return true;
					else
						return false;
				}
				else
					return true;
			}
		},

		getRowMenuVisibility: function (record) {
			return (record.data.status == 0 || record.data.status == 1);
		},
		// With selected options
		withSelected: {
			menu: [
				{text: "Delete", value: "delete", handler:function(){

					var zones = grid.getSelectionModel().selections.keys;

					SendRequestWithConfirmation(
						{
							action: 'RemoveApplications', 
							zones: Ext.encode(zones)
						},
						'Remove selected application(s)?',
						'Removing application(s). Please wait...',
						'ext-mb-object-removing',
						function(){
							grid.autoSize();
						},
						function(){
							store.load();
						}
					);
				}}
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