{include file="inc/header.tpl" add_to_head='<script src="/js/timeline/api/timeline-api.js" type="text/javascript"></script>'}	
	<script language="Javascript">
	var tl;
	var farmid = '{$farminfo.id}';
	
	{literal}
	function onLoad() {
		var eventSource = new Timeline.DefaultEventSource();
		var bandInfos = [
		    Timeline.createBandInfo({
		    	eventSource:    eventSource,
		        width:          "70%", 
		        intervalUnit:   Timeline.DateTime.DAY, 
		        intervalPixels: 100
		    }),
		    Timeline.createBandInfo({
		    	eventSource:    eventSource,
		        width:          "30%", 
		        intervalUnit:   Timeline.DateTime.MONTH, 
		        intervalPixels: 200,
		        overview:	true
		    })
	  	];
	  
	  	bandInfos[1].syncWith = 0;
  	  	bandInfos[1].highlight = true;
	  
	  	bandInfos[1].locale = "en";
	  	bandInfos[0].locale = "en";
	  
		tl = Timeline.create(document.getElementById("my-timeline"), bandInfos);
		Timeline.loadXML("/storage/events/"+farmid+"/timeline.xml", function(xml, url) { eventSource.loadXML(xml, url); });
	}
	
	var resizeTimerID = null;
	function onResize() {
	    if (resizeTimerID == null) {
	        resizeTimerID = window.setTimeout(function() {
	            resizeTimerID = null;
	            tl.layout();
	        }, 500);
	    }
	}
		
	Event.observe(window, 'load', function()
	{   
		 onLoad();
	});
	
	Event.observe(window, 'resize', function()
	{   
		 onResize();
	}); 
	{/literal}
	</script>
	<div style="float:right;margin-right:20px;">
		<img src="/images/dhtmlxtree/csh_vista/folderOpen_scripting.gif" style="vertical-align:middle;"><a href="configure_event_notifications.php?farmid={$farminfo.id}">Configure event notifications</a>
	</div>	
	{include file="inc/table_header.tpl" nofilter=1 table_header_text="Events timeline"}
    	<div id="my-timeline" style="height: 250px; border: 1px solid #aaa"></div>
	{include file="inc/table_footer.tpl" colspan=9 disable_footer_line=1}
	<br />
	<link rel="stylesheet" href="css/grids.css" type="text/css" />
	<div id="maingrid-ct" class="ux-gridviewer" style="padding: 5px;"></div>
	<script type="text/javascript">
	var uid = '{$smarty.session.uid}';	
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
					'id','dtadded', 'type', 'message'
		        ]
	    	}),
	    	remoteSort: true,
			url: '/server/grids/farm_events_list.php?a=1{/literal}{$grid_query_string}{literal}',
			listeners: { dataexception: Ext.ux.dataExceptionReporter }
	    });
				
	    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
		var grid = new Ext.ux.scalr.GridViewer({
	        renderTo: "maingrid-ct",
	        height: 500,
	        title: "Events list",
	        id: 'farm_events_list',
	        store: store,
	        maximize: true,
	        viewConfig: { 
	        	emptyText: "No events found"
	        },
	        			
	        // Columns
	        columns:[
				{header: "Date", width: 80, dataIndex: 'dtadded', sortable: false},
				{header: "Event", width: 50, dataIndex: 'type', sortable: false},
				{header: "Description", width: 300, dataIndex: 'message', sortable: false}
			]
	    });
	    
	    grid.render();
	    store.load();
	
		return;
	});
	{/literal}
	</script>
	
    <!-- 
    <table class="Webta_Items" rules="groups" frame="box" cellpadding="4" width="100%" id="Webta_Items">
	<thead>
		<tr>
			<th>Date</th>
			<th>Event</th>
			<th>Message</th>
		</tr>
	</thead>
	<tbody>
	{section name=id loop=$rows}
	<tr id='tr_{$smarty.section.id.iteration}'>
	
		<td class="Item" valign="top" nowrap>{$rows[id].dtadded}</td>
		<td class="Item" valign="top" nowrap>{$rows[id].type}</td>
		<td class="Item" valign="top">{$rows[id].message|nl2br}</td>
	</tr>
	{sectionelse}
	<tr>
		<td colspan="3" align="center">No events found</td>
	</tr>
	{/section}
	<tr>
		<td colspan="3" align="center">&nbsp;</td>
	</tr>
	</tbody>
	</table>
	{include file="inc/table_footer.tpl" colspan=9 disable_footer_line=1}
	 -->	
{include file="inc/footer.tpl"}