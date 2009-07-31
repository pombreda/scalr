{include file="inc/header.tpl"}
<br />
<link rel="stylesheet" href="css/grids.css" type="text/css" />
<div id="maingrid-ct" class="ux-gridviewer" style="padding: 5px;"></div>
<script type="text/javascript">

var uid = '{$smarty.session.uid}';

var regions = [
{section name=id loop=$regions}
	['{$regions[id]}','{$regions[id]}']{if !$smarty.section.id.last},{/if}
{/section}
];

var region = '{$smarty.session.aws_region}';

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
	            'name', 'type', 'ami_id', 'architecture', 'iscompleted', 'isreplaced','approval_state',
	            'client_name', 'fail_details', 'abort_id', 'dtbuilt', 'roletype'
	        ]
    	}),
    	remoteSort: true,
		url: '/server/grids/client_roles_list.php?a=1{/literal}{$grid_query_string}{literal}',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });
	

	function statusRenderer(value, p, record) {
		var retval = "";
		
		if (record.data.isreplaced && record.data.iscompleted != 2)
			retval = "Synchronizing&#x2026";
		else
		{
			if (record.data.iscompleted == 1)
				retval = "Active";
			else if (record.data.iscompleted == 0)
				retval = "Bundling...";
			else
			{
				if (record.data.fail_details)
					retval += '<a href="custom_roles_failed_details.php?id='+record.data.id+'">';

				retval += "Failed";
					
				if (record.data.fail_details)
					retval += '</a>';
			}
		}

		if (record.data.abort_id)
			retval += ' (<a href="client_roles_view.php?task=abort&id='+record.data.abort_id+'">Abort</a>)';

		return retval;
	}

	function clientRenderer(value, p, record) {

		var retval = "";
		
		if (uid == 0 && record.data.clientid)
			retval += '<a href="clients_view.php?clientid='+record.data.clientid+'">';

		retval += record.data.client_name;
			
		if (uid == 0 && record.data.clientid)
			retval += '</a>';

		return retval;		
	}

	function contribRenderer(value, p, record) {
		
		if (record.data.roletype == 'SHARED')
			return '<img src="/images/true.gif">';
		else
			return '<img src="/images/false.gif">';
	}

	function modRenderer(value, p, record) {

		if (record.data.approval_state == 'Approved')
			return '<img src="/images/true.gif" title="Approved" />';
		else if (record.data.approval_state == 'Pending')
			return '<img src="/images/pending.gif" title="Pending" />';
		else if (record.data.approval_state == 'Declined')
			return '<img src="/images/false.gif" title="Declined" />';
		else
			return '';
	}
	
    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
	var grid = new Ext.ux.scalr.GridViewer({
        renderTo: "maingrid-ct",
        height: 500,
        title: "Roles",
        id: 'client_roles_list',
        store: store,
        maximize: true,
        viewConfig: { 
        	emptyText: "No roles found"
        },
		tbar: ['&nbsp;&nbsp;Region:', new Ext.form.ComboBox({
			allowBlank: false,
			editable: false, 
	        store: regions,
	        value: region,
	        displayField:'state',
	        typeAhead: false,
	        mode: 'local',
	        triggerAction: 'all',
	        selectOnFocus:false,
	        width:100,
	        listeners:{select:function(combo, record, index){
	        	store.baseParams.region = record.data.value; 
	        	store.load();
	        }}
	    }), '-', '&nbsp;&nbsp;Moderation phase:', new Ext.form.ComboBox({
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

				if (record.data.value == '' || record.data.value == '&nbsp;')
					record.data.value = '';
	        
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
	        emptyText: ' ',
	        width:150,
	        listeners:{select:function(combo, record, index){

	    		if (record.data.value == '' || record.data.value == '&nbsp;')
					record.data.value = '';
	        
	        	store.baseParams.origin = record.data.value; 
	        	store.load();
	        }}
	    })],
		
        // Columns
        columns:[
			{header: "Role name", width: 70, dataIndex: 'name', sortable: true},
			{header: "Owner", width: 50, dataIndex: 'clientid', renderer:clientRenderer, sortable: false},
			{header: "Category", width: 60, dataIndex: 'type', sortable: false},
			{header: "AMI ID", width: 40, dataIndex: 'ami_id', sortable: true},
			{header: "Arch", width: 30, dataIndex: 'architecture', sortable: true}, 
			{header: "Status", width: 40, dataIndex: 'id', renderer:statusRenderer, sortable: false},
			{header: "Last build", width: 40, dataIndex: 'dtbuilt', sortable: false},
			{header: "Contributed", width: 40, dataIndex: 'id', renderer:contribRenderer, sortable: false, hidden:true, align:'center'},
			{header: "Moderation phase", width: 30, dataIndex: 'id', renderer:modRenderer, sortable: false, hidden:true, align:'center'}
		],
		
    	// Row menu
    	rowOptionsMenu: [
      	             	
			{id: "option.view", 		text:'View details', 			  	href: "/role_info.php?id={id}"},

			{id: "option.edit", 		text:'Edit', 			  			href: "/client_role_edit.php?id={id}"},
			new Ext.menu.Separator({id: "option.editSep"}),
			{id: "option.share", 		text:'Share this role', 			href: "/client_role_edit.php?task=share&id={id}"},
			new Ext.menu.Separator({id: "option.shareSep"}),
			
			{id: "option.logs", 		text:'View bundle log', 			  	href: "/custom_role_log.php?id={id}"}
     	],
     	getRowOptionVisibility: function (item, record) {

			if (item.id == 'option.view')
			{
				if (record.data.iscompleted == '1' && !record.data.isreplaced)
					return true;
				else
					return false;
			}

			if (record.data.roletype == 'CUSTOM')
			{

				if (item.id == 'option.edit' || item.id == 'option.editSep' || item.id == 'option.share' || item.id == 'option.shareSep')
				{
					if (uid != 0 && record.data.iscompleted == '1' && !record.data.isreplaced)
						return true;
					else
						return false;
				}
				
				return true;
			}
			else
				return false;
		},
		withSelected: {
			menu: [
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