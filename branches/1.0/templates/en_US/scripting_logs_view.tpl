{include file="inc/header.tpl"}
<link rel="stylesheet" href="css/grids.css" type="text/css" />
<div id="search-ct"></div> 
<div id="maingrid-ct" class="ux-gridviewer" style="padding: 5px;"></div>
<script type="text/javascript">
{literal}
Ext.onReady(function () {

	var farms_store = new Ext.data.SimpleStore({
	    fields: ['value', 'text'],
	    data : {/literal}{$farms}{literal}
	});
	
	// ---- Init search form
	var searchPanel = new Ext.FormPanel({
		style: 'margin:5px 5px 15px 5px',
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
			{text: 'Filter', handler: doFilter}
		]
	});
	
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
				'id','farmid','event','instance','dtadded','message','farm_name'
            ]
        }),
        baseParams: {
        	sort: 'id',
        	dir: 'DESC'
        },
    	remoteSort: true,
		url: 'server/grids/scripting_log_list.php?a=1{/literal}{$grid_query_string}{literal}',
		listeners: { dataexception: Ext.ux.dataExceptionReporter }
    });
	Ext.apply(store.baseParams, Ext.ux.parseQueryString(window.location.href));

	function targetRenderer (value, p, record) {
		return '<a href="/instances_view.php?iid='+value+'&farmid='+record.data.farmid+'">'+value+'</a>';				
	}

	function farmRenderer (value, p, record) {
		return '<a href="farms_view.php?id='+record.data.farmid+'">'+value+'</a>';
	}
	
    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
	var grid = new Ext.ux.scalr.GridViewer({
        renderTo: "maingrid-ct",
        id: "scripting_logs_list3",
        height: 550,
        title: "Scripting Log {/literal}({$table_title_text}){literal}",
        store: store,
        maximize: true,
        enableFilter: false,
        viewConfig: { 
        	emptyText: "No logs found"
        },
                
	    // Columns
        columns:[
			{header: "Time", width: 40, dataIndex: 'dtadded', sortable: false},
			{header: "Event", width: 35, dataIndex: 'event', sortable: false, align:'center'},
			{header: "Farm", width: 35, dataIndex: 'farm_name', renderer:farmRenderer, sortable: false},
			{header: "Target", width: 35, dataIndex: 'instance', renderer:targetRenderer, sortable: false},
			{header: "Message", width: 150, dataIndex: 'message', sortable: false}
		]
    });

	var farm_clm_index = grid.getColumnModel().findColumnIndex('farm_name');
	grid.getColumnModel().setHidden(farm_clm_index, false);
	
    store.load();
});
{/literal}
</script>
{include file="inc/footer.tpl"}

<!-- 
	{include file="inc/table_header.tpl" filter=0 paging=""}
    {include file="inc/intable_header.tpl" header="Search" color="Gray"}
        <tr>
			<td nowrap="nowrap">Search string:</td>
			<td><input type="text" name="search" class="text" id="search" value="{$search}" size="20" /></td>
		</tr>
		<tr>
			<td nowrap="nowrap">Farm:</td>
			<td>
				<select name="farmid">
					<option></option>
					{section name=id loop=$farms}
					<option {if $farmid == $farms[id].id}selected{/if} value="{$farms[id].id}">{$farms[id].name}</option>
					{/section}
				</select>
			</td>
		</tr>
    {include file="inc/intable_footer.tpl" color="Gray"}
    {include file="inc/table_footer.tpl" colspan=9 button2=1 button2_name="Search"}
    <br>
    {include file="inc/table_header.tpl"}
    <table class="Webta_Items" rules="groups" frame="box" cellpadding="4" width="100%" id="Webta_Items">
	<thead>
		<tr>
			<th>Time</th>
			<th>Event</th>
			<th>Farm</th>
			<th>Target</th>
			<th>Message</th>
		</tr>
	</thead>
	<tbody>
	{section name=id loop=$rows}
	<tr id='tr_{$smarty.section.id.iteration}'>
		<td class="Item" valign="top" nowrap>{$rows[id].dtadded}</td>
		<td class="Item" valign="top" nowrap>{if $rows[id].event}On{$rows[id].event}{/if}</td>
		<td class="Item" valign="top" nowrap><a href="farms_view.php?id={$rows[id].farm.id}">{$rows[id].farm.name}</a></td>
		<td class="Item" valign="top" nowrap><a href="instances_view.php?farmid={$rows[id].farm.id}&iid={$rows[id].instance}">{$rows[id].instance}</a></td>
		<td class="Item" valign="top">{$rows[id].message|nl2br}</td>
	</tr>
	{sectionelse}
	<tr>
		<td colspan="5" align="center">No log entries found</td>
	</tr>
	{/section}
	<tr>
		<td colspan="5" align="center">&nbsp;</td>
	</tr>
	</tbody>
	</table>
	{include file="inc/table_footer.tpl" colspan=9 disable_footer_line=1}	
 -->