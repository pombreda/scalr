	{include file="inc/header.tpl"}
	<link rel="stylesheet" href="css/grids.css" type="text/css" />
	<div id="maingrid-ct" class="ux-gridviewer"></div>
	<script type="text/javascript">
	{literal}
	Ext.onReady(function () 
	{
		
		// create the Data Store
	    var store = new Ext.ux.scalr.Store({
    		reader: new Ext.ux.scalr.JsonReader({
		        root: 'data',
		        successProperty: 'success',
		        errorProperty: 'error',
		        totalProperty: 'total',
		        id: 'name',
	        		
		        fields: 
		        [
					 'name', 'cfid', 'cfurl', 'cname', 'status', 'enabled'					
		        ]
    		}),
    		remoteSort: true,                        
			url: '/server/grids/s3browser_list.php?a=1{/literal}{$grid_query_string}{literal}',
			listeners: { dataexception: Ext.ux.dataExceptionReporter }
	    });
			 

	    var renderers = Ext.ux.scalr.GridViewer.columnRenderers;
	    
	    function s3EnabledRenderer(value, p, record)
	    {		    	 	 				 
			if (record.data.enabled == "true")				
				return '<img src="/images/true.gif">';								
			else
			{
			 	if(record.data.enabled == "false")			
					return '<img src="/images/false.gif">';							
				else
					return "";
			}
			
		}
	    
		var grid = new Ext.ux.scalr.GridViewer({
	        renderTo: "maingrid-ct",
	        height: 500,
	        title: "S3 buckets",
	        id: 's3browser'+GRID_VERSION,
	        store: store,
	        maximize: true,
	        viewConfig: { 
        		emptyText: "No tasks defined"
	        },
			 
			enableFilter: true,
  		    
	        // Columns
	        columns:
	        [
				{header: "Bucket name", width: 40, 		dataIndex: 'name',		sortable: false},
				{header: "Cloudfront ID", width: 30, 	dataIndex: 'cfid', 		sortable: false},
				{header: "Cloudfront URL", width: 30, 	dataIndex: 'cfurl', 	sortable: false},			
				{header: "CNAME", width: 40, 			dataIndex: 'cname', 	sortable: false},
				{header: "Status", width: 20, 			dataIndex: 'status', 	sortable: false},				
				{header: "Enabled", width: 30, 			dataIndex: 'enabled', renderer:s3EnabledRenderer,	sortable: false}
				
			],

    		// Row menu
    		rowOptionsMenu: 
    		[   
    			{id: "option.create_dist", 		text: 'Create distribution', 	href: "/s3browser.php?action=create_dist&name={name}"},			
				{id: "option.delete_dist", 		text: 'Remove distribution',   	href: "/s3browser.php?action=delete_dist&id={cfid}"},
				{id: "option.disable_dist", 	text: 'Disable distribution',   href: "/s3browser.php?action=disable_dist&id={cfid}"},
				{id: "option.enable_dist", 		text: 'Enable distribution',  	href: "/s3browser.php?action=enable_dist&id={cfid}"},
					new Ext.menu.Separator({id: "option.editSep"}),
				{id: "option.delete_backet", 	text: 'Delete bucket',			href: "/s3browser.php?action=delete_backet&name={name}"}
				
     		],
 
			getRowOptionVisibility: function (item, record)
			{
				var data = record.data; 				
				
				var isDist = false;
				
				if(data.cfid) 
					isDist = true;
					
				switch(item.id)
				{
					case "option.disable_dist":
						return ((data.enabled == "true") && isDist); // returns true if distribution has enabled status. Shows disable button
						
					case  "option.enable_dist":
						return ((data.enabled == "false") && isDist);  
						
					case "option.delete_dist":
						return (isDist);   
						
					case "option.create_dist":
						 return(!isDist);
						
					default:
						return true;
						
				} 
			}
			
	    });
	    grid.render();
	    store.load();

		return;
	});
	{/literal}
	</script>
	{include file="inc/footer.tpl"}