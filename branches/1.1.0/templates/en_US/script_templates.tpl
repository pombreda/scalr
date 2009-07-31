{include file="inc/header.tpl"}
<link rel="stylesheet" href="css/grids.css" type="text/css" />
<div id="maingrid-ct" class="ux-gridviewer" style="padding: 5px;"></div>
<script type="text/javascript">

var uid = {$smarty.session.uid};

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
	        	
	        fields: [
				{name: 'id', type: 'int'},
				'name', 'description', 'origin', 'clientid', 'approval_state', 'dtupdated', 'client_email', 'version', 'client_name'
	        ]
    	}),
    	remoteSort: true,
		url: '/server/grids/scripts_list.php?a=1{/literal}{$grid_query_string}{literal}',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });
		
	function authorRenderer(value, p, record) {
		if (uid == 0)
		{
			if (record.data.clientid != 0)
				return '<a href="clients_view.php?clientid='+record.data.clientid+'">'+record.data.client_email+'</a>';
			else
				return "Scalr";		
		}
		else
		{
			if (record.data.clientid != 0)
			{
				if (uid == record.data.clientid)
					return "Me";
				else
					return record.data.client_name 
			}
			else
				return "Scalr";
				
		}
	}

	function originRenderer(value, p, record) {
		if (value == 'Shared')
			return '<img src="/images/dhtmlxtree/csh_vista/icon_script.png" title="Contributed by Scalr">';
		else if (value == 'Custom')
			return '<img src="/images/dhtmlxtree/csh_vista/icon_script_custom.png" title="Custom">';
		else
			return '<img src="/images/dhtmlxtree/csh_vista/icon_script_contributed.png" title="Contributed by '+record.data.client_name+'">';
	}

	function asRenderer(value, p, record) {
		if (value == 'Approved' || !value)
			return '<img src="/images/true.gif" title="Approved" />';
		else if (value == 'Pending')
			return '<img src="/images/pending.gif" title="Pending" />';
		else if (value == 'Declined')
			return '<img src="/images/false.gif" title="Declined" />';
	}
	
    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
	var grid = new Ext.ux.scalr.GridViewer({
        renderTo: "maingrid-ct",
        height: 500,
        title: "Clients",
        id: 'scripts_list',
        store: store,
        maximize: true,
        tbar: ['&nbsp;&nbsp;Moderation phase:', new Ext.form.ComboBox({
			allowBlank: true,
			editable: false, 
	        store: [['',''],['Approved','Approved'],['Declined','Declined'],['Pending','Pending']],
	        value: '',
	        displayField:'state',
	        typeAhead: false,
	        mode: 'local',
	        triggerAction: 'all',
	        selectOnFocus:false,
	        width:100,
	        listeners:{select:function(combo, record, index){
	        	store.baseParams.approval_state = record.data.value; 
	        	store.load();
	        }}
	    }), '-', '&nbsp;&nbsp;Origin:', new Ext.form.ComboBox({
			allowBlank: true,
			editable: false, 
	        store: [['',''],['Shared','Shared'],['Custom','Custom'],['User-contributed','User-contributed']],
	        value: '',
	        displayField:'state',
	        typeAhead: false,
	        mode: 'local',
	        triggerAction: 'all',
	        selectOnFocus:false,
	        width:150,
	        listeners:{select:function(combo, record, index){
	        	store.baseParams.origin = record.data.value; 
	        	store.load();
	        }}
	    }), '-', {
	        icon: '/images/add.png', // icons can also be specified inline
	        cls: 'x-btn-icon',
	        tooltip: 'Create new script template',
	        handler: function()
	        {
				document.location.href = '/script_templates.php?task=create';
	        }}],
	    
        viewConfig: { 
        	emptyText: "No scripts defined"
        },

        // Columns
        columns:[
			{header: "Author", width: 100, dataIndex: 'id', renderer:authorRenderer, sortable: false},
			{header: "Name", width: 100, dataIndex: 'name', sortable: true},
			{header: "Description", width: 120, dataIndex: 'description', sortable: true},
			{header: "Latest version", width: 70, dataIndex: 'version', sortable: false, align:'center'},
			{header: "Updated on", width: 70, dataIndex: 'dtupdated', sortable: false},
			{header: "Origin", width: 50, dataIndex: 'origin', renderer:originRenderer, sortable: false, align:'center'},
			{header: "Moderation phase", width: 80, dataIndex: 'approval_state', renderer:asRenderer, sortable: false, align:'center'}
		],

		//TODO: Hide option for non-active rows
		
    	// Row menu
    	rowOptionsMenu: [
			{id: "option.fork", 		text:'Fork', 			  	href: "/script_templates.php?task=fork&id={id}"},
			new Ext.menu.Separator({id: "option.forkSep"}),
			
			{id: "option.info", 		text: 'View', 	href: "/script_info.php?id={id}"},
			new Ext.menu.Separator({id: "option.optSep"}),

			{id: "option.share", 		text: 'Share', 	href: "/script_templates.php?task=share&id={id}"},
			new Ext.menu.Separator({id: "option.shareSep"}),

			{id: "option.edit", 		text: 'Edit', 	href: "/script_templates.php?task=edit&id={id}"},
			{id: "option.delete", 		text: 'Delete', 	handler:function(menuItem){

				var Item = menuItem.parentMenu.record.data;
				SendRequestWithConfirmation(
					{
						action: 'RemoveScript', 
						scriptID: Item.id
					},
					'Remove script?',
					'Removing script. Please wait...',
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
		listeners: {
			beforeshowoptions: function (grid, record, romenu, ev) {
				romenu.record = record;
			}
		},
     	getRowOptionVisibility: function (item, record) {
			var data = record.data;

			if (item.id == 'option.fork' || item.id == 'option.forkSep')
			{
				if (uid != 0 && (data.clientid == 0 || (data.clientid != 0 && data.clientid != uid)))
					return true;
				else
					return false;
			}
			else if (item.id != 'option.info')
			{
				if ((data.clientid != 0 && data.clientid == uid) || uid == 0)
				{
					if (item.id == 'option.share' || item.id == 'option.shareSep')
					{
						if (data.origin == 'Custom' && uid != 0)
							return true;
						else
							return false;
					}
					else 
						return true;
				}
				else
					return false;
			}
			else
				return true;
		},

		getRowMenuVisibility: function (record) {
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