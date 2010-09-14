{include file="inc/header.tpl" noheader=1}
	<div id="role_info_popup" align="left" style="display:none;">
    	<div id="popup_contents" style="margin-top:5px;margin-left:5px;">
    		
    	</div>
	</div>
	<div id="popup_help" align="left" style="display:none;owerflow:hidden;">
    	<div id="popup_help_contents" style="padding-top:0px;padding-left:30px;background: url(/images/icon_ihelp.gif) #ffffff no-repeat 0px 0px;">

    	</div>
	</div>
	{literal}
	<style>

	#variantList {
		background:white;
		font: normal 1.3em Arial;
		line-height:18px;
		zoom:1;
	}
	
	#variantList .var-item {
		padding:10px 10px;
		border-bottom:1px solid #D6E4E1;
	    margin: 0 1px;
		zoom:1;
	}
	
	#variantList .x-view-selected {
		background:#D9E8FB;
	}
	
	
	div.x-dataview-drag-insert-below {
	 	 border-bottom:1px dotted #3366cc !important;
	}
	div.x-dataview-drag-insert-above {
		 border-top:1px dotted #3366cc;
	}
	
	.x-tree-node-anchor
	{
		font-size:14px;
	}
	
	.x-tree-node-icon
	{
		background-image:URL('/images/dhtmlxtree/csh_vista/icon_hardware.gif');
	}
	
	.x-tree-node-el
	{	
		CURSOR: pointer;	
		LINE-HEIGHT: 18px;
		padding-top:4px;
		padding-bottom:4px;
		border-bottom:1px solid #F9F9F9;
	}
	
	.x-tab-panel-header
	{
		border-bottom: 0px !important;
	}
	</style>
	{/literal}
    <link rel="STYLESHEET" type="text/css" href="/css/dhtmlXTree.css" />
    <link href="css/popup.css" rel="stylesheet" type="text/css" />
    <script language="javascript" type="text/javascript" src="/js/dhtmlxtree/dhtmlXTree.js"></script>
    <script language="javascript" type="text/javascript" src="/js/dhtmlxtree/dhtmlXCommon.js"></script>
    <script language="javascript" type="text/javascript" src="/js/highlight/highlight.pack.js"></script>    
     
    <link rel="stylesheet" type="text/css" href="/js/FC_TrackBar/trackbar.css" />
    <script type="text/javascript">
    	var NoCloseButton = false;
    </script>
	<script type="text/javascript" src="/js/FC_TrackBar/trackbar.js"></script>
	<script type="text/javascript" src="/js/class.NewPopup.js"></script>
	<script type="text/javascript" src="/js/class.RoleTab.js"></script>
	<script type="text/javascript" src="/js/class.FarmRole.js"></script>
	<script type="text/javascript" src="/js/farm_manager.inc.js"></script>
	<link rel="stylesheet" href="/js/highlight/styles/sunburst.css" />
    <br />
    <style>
	{literal}												
		.s_field_container
		{
			margin-bottom:10px;
			width:400px;
		}
		
		.s_field_name
		{
			width:150px;
			float:left;
			vertical-align:middle;
		}
	{/literal}
	</style>
    <div id="main_page_cont">
    	<div style="height:100%;padding-left:269px;position:relative;">
	    	<div style="width:264px;position:absolute;left:0px;top:0px;">
	    		{include file="inc/table_header.tpl" nofilter=1}
					<div style="position:relative;top:0px;left:0px;width:264px;height:500px;">
						<div style="position:absolute;top:0px;left:0px;z-index:1;">
							<div id="inventory_tree" style="width:250px;height:500px;"></div>
						</div>                            
					</div>
	            {include file="inc/table_footer.tpl" disable_footer_line=1}
	    	</div>
    	<div>
    		{include file="inc/table_header.tpl" nofilter=1 tabs=1}								
				{include intable_tabs=0 intable_classname="tab_contents" intableid="tab_contents_general" file="inc/intable_header.tpl" header="Farm information" color="Gray"}
					<tr id="mysql_dep_warning" style="display:none;">
						<td colspan="2">
                     			<div class="Webta_ExperimentalMsg" style="margin-bottom:15px;">
							'mysql' and 'mysql64' roles are deprecated. Please use 'mysqllvm' and 'mysqllvm64' instead.
							</div>
                     	</td>
					</tr>
                    <tr>
                      	<td width="20%">Name:</td>
                       	<td><input type="text" class="text" name="farm_name" id="farm_name" value="{$farminfo.name}" /></td>
                       	
                    </tr>
                    <tr>
						<td width="20%">Location:</td>
                       	<td>
                       		{$region}
                       	</td>
					</tr>
					<tr>
                       	<td colspan='2'>
                       		&nbsp;
                       	</td>
					</tr>
                    <tr>
                       	<td colspan='2'>
                       		<input type='radio' onclick='RoleTabObject.SetRolesLaunchOrder("0");' id='roles_launch_order_0' name="roles_launch_order" checked="checked" style='vertical-align:middle;'> Launch roles simultaneously
                       		<br/>
                       		<input type='radio' onclick='RoleTabObject.SetRolesLaunchOrder("1");' id='roles_launch_order_1' name="roles_launch_order" style='vertical-align:middle;'> Launch roles one-by-one in the order I set (slower)
                       	</td>
                    </tr>
                    <tr>
                       	<td colspan='2'>
                       		&nbsp;
                       	</td>
					</tr>
                    <tr>						
						<td width="20%" valign="top">Comments:</td>
                       	<td><textarea type="text" class="text" cols="40" rows="5"  name="farm_comments" id="farm_comments">{$farminfo.comments}</textarea></td>
                    </tr>
					{include file="inc/intable_footer.tpl" color="Gray"}
                        		
                    <div id="tab_contents_rso" class="tab_contents" style="display: none">
                    	<div id='roles_order_panel'></div>
                    </div>
                        		
               		<div id="tab_contents_roles" class="tab_contents">
               			<div id="role_tabs_container"></div>
               			{include file='tab_fa_about.tpl'}
               			{include file='tab_fa_mysql.tpl'}
               			{include file='tab_fa_scaling.tpl'}
               			{include file='tab_fa_balancing.tpl'}
               			{include file='tab_fa_placement.tpl'}
               			{include file='tab_fa_rds.tpl'}
               			{include file='tab_fa_params.tpl'}
               			{include file='tab_fa_eips.tpl'}
               			{include file='tab_fa_ebs.tpl'}
               			{include file='tab_fa_dns.tpl'}
               			{include file='tab_fa_timeouts.tpl'}
               			{include file='tab_fa_scripting.tpl'}
               			
               			               			               			
               			
               			{include file='tab_fa_cloudwatch.tpl'}
               		</div>
				{include file="inc/table_footer.tpl" disable_footer_line=1}
				<br>
				{include file="inc/table_header.tpl" nofilter=1}
                        	{include
					file="inc/table_footer.tpl" 
					colspan=9 
					button_js_name='Save' 
					button_js=1 
					button_js_action='window.RoleTabObject.SubmitForm();' 
					loader='Building farm. Please wait...'
				}
    		</div>
    	</div>
    </div>
    <script language="Javascript" type="text/javascript">
       	/*
       	Init variables
       	*/								                            	
       	var i_types = new Array();
    	i_types['i386'] = new Array();
    	{section name=id loop=$32bit_types}
    	i_types['i386'][i_types['i386'].length] = '{$32bit_types[id]}';
    	{/section}
    	
    	i_types['x86_64'] = new Array();
    	{section name=id loop=$64bit_types}
    	i_types['x86_64'][i_types['x86_64'].length] = '{$64bit_types[id]}';
    	{/section}
        
        var MANAGER_ACTION = '{if $id}edit{else}create{/if}';
        var FARM_ID		   = '{$id}';
        var REGION		   = '{$region}';
        var FARM_MYSQL_ROLE = '{$farm_mysql_role}'; 
        var FARM_ROLES_LAUNCH_ORDER = '{if $farminfo}{$farminfo.farm_roles_launch_order}{else}0{/if}';
        var SELECTED_ROLE_ID = '{$role_id}';  
        var RETURN_TO = '{$return_to}';                        

        var tree = null;
        var events_tree = null;
		var LoadMask = null;
		var RoleTabsPanel = null;

		{if $roles}
		var l_roles = {$roles};
        {else}
        var l_roles = '';
        {/if}

		{if $default_scaling_algos}
		var default_scaling_algos = {$default_scaling_algos};
		{/if}
                        
        /*
        Setup initial observer
        */
		{literal}
    	                            	                
        /*
        Setup script events tree
        */
		var ConfigFieldTemplate = new Ext.Template('<div class="s_field_container"> '+
			'<div class="s_field_name">{title}:</div>'+
			'<div style="float:left;"><input id="script_configopt_{name}" type=\'text\' name=\'{name}\' value=\'\' class=\'text configopt\'></div>'+
			'<div style="clear:both;"></div>'+
			'</div>'
		); 

        Ext.onReady(function(){

        	var gridCt = Ext.get(document.body);
	   		var bodyEl = Ext.get(document.body);
	    	gridCt.setHeight(Math.max(300, Ext.lib.Dom.getViewHeight() - gridCt.getY() - gridCt.getPadding("tb") - gridCt.getBorderWidth("tb")));

        	window.LoadMask = new Ext.LoadMask(Ext.getBody(), {msg:"Please wait..."});
        	window.LoadMask.show();
      	            
       	    window.RolesOrderTree = new Ext.grid.GridPanel({
       	        renderTo: 'roles_order_panel',
       	        title:'Use up & down arrows to set roles launch order.',
       	        height:300,
       	        hideHeaders: true,
       	        viewConfig: {
					forceFit: true
				},
       	        store: new Ext.data.ArrayStore({
					fields: [ 'role_id', 'role_name'],
					pruneModifiedRecords: true
				}),
       	        columns: [{
					id: 'role_up',
					width: '30',
					renderer: function(value, metadata) {
						metadata.attr = 'style="padding: 0px"';
						return '<img src="/images/up_icon.png" style="cursor: pointer">';
					}
       	        }, {
					id: 'role_down',
					width: '30',
					renderer: function(value, metadata) {
						metadata.attr = 'style="padding: 0px"';
						return '<img src="/images/down_icon.png" style="cursor: pointer">';
					}
				}, {
					id: 'role_name',
					header: 'role_name',
					dataIndex: 'role_name'
       	        }],
       	        listeners: {
					'cellclick': function(grid, rowIndex, columnIndex, e) {
						var store = grid.getStore();

						if (columnIndex == grid.getColumnModel().getIndexById('role_up') && rowIndex > 0) {
							var record = store.getAt(rowIndex);
							store.removeAt(rowIndex);
							store.insert(rowIndex - 1, record);
						} else if (columnIndex == grid.getColumnModel().getIndexById('role_down') && (rowIndex < store.getCount() - 1)) {
							var record = store.getAt(rowIndex);
							store.removeAt(rowIndex);
							store.insert(rowIndex + 1, record);
						}
					}
				}
       	        
			});
       	    
      	    // render the grid
      	    window.RolesOrderTree.render();            	            	    
      	    // fix for IE
      	    Ext.get('tab_contents_rso').dom.style.display = 'none';

      	    window.RoleTabsPanel = new Ext.TabPanel({
		        renderTo:'role_tabs_container',
		        id:'role_options_tab_panel',
		        resizeTabs:false, // turn on tab resizing
		        enableTabScroll:true,
		        deferredRender:true,
		        defaults: {autoScroll:false, autoHeight:true},
		        bodyStyle:'overflow:hidden; width:auto;',
				listeners:{
					beforerender:function(tabPanel){

		        	}
		       	},
		        items: [
				{
					id:'role_t_about',
					x_display:'all',
	              	title: 'About',
	               	closable:false,
	               	contentEl:'itab_contents_info_n'
	           	},								            
	           	{
					id:'role_t_mysql',
					x_display:'ec2:mysql',
	               	title: 'MySQL settings',
	               	closable:false,
	              	contentEl:'itab_contents_mysql_n'
	           	},
	           	{
	               id:'role_t_scaling',
	               x_display:'ec2',
	           	   title: 'Scaling options',
	               closable:false,
	               contentEl:'itab_contents_scaling_n'
	           	},
	           	{
	           		id:'role_t_lb',
	           		x_display:'ec2',
	           		title: 'Load balancing options',
	              	closable:false,
	               	contentEl:'itab_contents_balancing_n'
	           	},
	           	{
	           		id:'role_t_placement',
	           		x_display:'ec2',
	           		title: 'Placement and type',
	               	closable:false,
	               	contentEl:'itab_contents_placement_n'
	           	},
	           	{
	           		id:'role_t_rds',
	           		x_display:'rds',
	           		title: 'RDS options',
	               	closable:false,
	               	contentEl:'itab_contents_rds_n'
	           	},
	           	{
	           		id:'role_t_params',
	           		x_display:'ec2',
	           		title: 'Parameters',
	               	closable:false,
	               	contentEl:'itab_contents_params_n'
	           	},
	           	{
	           		id:'role_t_eip',
	           		x_display:'ec2',
	           		title: 'Elastic IPs',
	               	closable:false,
	               	contentEl:'itab_contents_eips_n'
	           	},
	          	{
	           		id:'role_t_ebs',
	           		x_display:'ec2',
	           		title: 'EBS',
	               	closable:false,
	               	contentEl:'itab_contents_ebs_n'
	           	},
	           	{
	           		id:'role_t_dns',
	           		x_display:'ec2',
	           		title: 'DNS',
	               	closable:false,
	               	contentEl:'itab_contents_dns_n'
	           	},
	           	{
	           		id:'role_t_timeouts',
	           		x_display:'ec2',
	           		title: 'Timeouts',
	               	closable:false,
	               	contentEl:'itab_contents_timeouts_n'
	           	},
	           	{
	           		id:'role_t_scripting',
	           		x_display:'ec2',
	           		title: 'Scripting',
	               	closable:false,
	               	contentEl:'itab_contents_scripts_n'
	           	},/*
	           	{
	           		id:'role_t_security_groups',
	           		x_display:'ec2',
	           		title: 'Security groups',
	               	closable:false,
	               	contentEl:'itab_contents_security_groups_n'
	           	},*/
	           	{
	           		id:'role_t_cloudwatch',
	           		x_display:'ec2',
	           		title: 'CloudWatch',
	               	closable:false,
	               	contentEl:'itab_contents_cloudwatch_n'
	           	}/*,
				{
					title: 'MTA options',
					closeable:false,
					contentEl:'itab_contents_mta_n'
				}*/
	           	]
		    });
       										
			MainLoader(1);
			MainLoader(2);
		}); 	            
		{/literal}

	    Ext.get('button_js').dom.style.display='';
		var NoCloseButton = false;
		
		{literal}
		function ShowDescriptionPopup(event, id, obj)
		{
			var event = event || window.event;
			event = Ext.EventObject.setEvent(event);
			//var pos = Position.positionedOffset(obj);
			
			//pos[0] = Event.pointerX(event);
			//pos[1] = Event.pointerY(event);
			
			Ext.get('popup_contents').dom.innerHTML = Ext.get('role_description_tooltip_'+id).dom.innerHTML;
			
			popup.raisePopup(event.getXY());
		}
		
		function ShowHelp(event, content_id, obj)
		{
			var event = event || window.event;
			event = Ext.EventObject.setEvent(event);
			//var pos = Position.positionedOffset(obj);
			
			//pos[0] = Event.pointerX(event)+50;
			//pos[1] = Event.pointerY(event);
			
			Ext.get('popup_help_contents').dom.innerHTML = Ext.get(content_id).dom.innerHTML;
			
			popup_help.raisePopup(event.getXY());
		}
		{/literal}
		
		Ext.get('tab_roles').dom.style.display = 'none';
	</script>
	
	{section name=id loop=$roles_descr}
		{if $roles_descr[id].description}
		<span id="role_description_tooltip_{$roles_descr[id].id}" style="display:none;">
			<div><span style="color:#888888;">Role name:</span> {$roles_descr[id].name}</div>
			<div><span style="color:#888888;">AMI ID:</span> {$roles_descr[id].ami_id}</div>
			<div><span style="color:#888888;">Description:</span><br> {$roles_descr[id].description}</div>
		</span>
		{/if}
	{/section}
	
	<div id="scaling.time.add_period"></div>
	
	<span id="lb_listeners_help" style="display:none;">
		This parameter is used to denote a list of the following tuples LoadBalancerPort, InstancePort, and Protocol.<br />
		<br />
		<b>LoadBalancerPort</b> - The external TCP port of the LoadBalancer. Valid LoadBalancer ports are - 80, 443 and 1024 through 65535. This property cannot be modified for the life of the LoadBalancer.
		<br /> 
		<b>InstancePort</b> - The InstancePort data type is simple type of type: integer. It is the TCP port on which the server on the instance is listening. Valid instance ports are one (1) through 65535. This property cannot be modified for the life of the LoadBalancer.
		<br />
		<b>Protocol</b> - LoadBalancer transport protocol to use for routing - TCP or HTTP. This property cannot be modified for the life of the LoadBalancer.
		<br />
		<br />
	</span>
	
	<span id="lb_ht_help" style="display:none;">
		The number of consecutive health probe successes required before moving the instance to the Healthy state.<br /> 
		The default is 3 and a valid value lies between 2 and 10.<br /><br />
	</span>
	<span id="lb_int_help" style="display:none;">
		The approximate interval (in seconds) between health checks of an individual instance.<br /> 
		The default is 30 seconds and a valid interval must be between 5 seconds and 600 seconds. 
		Also, the interval value must be greater than the Timeout value<br /><br />
	</span>
	<span id="lb_target_help" style="display:none;">
		The instance being checked. The protocol is either TCP or HTTP. The range of valid ports is one (1) through 65535.<br />
		Notes: TCP is the default, specified as a TCP: port pair, for example "TCP:5000". 
		In this case a healthcheck simply attempts to open a TCP connection to the instance on the specified port. 
		Failure to connect within the configured timeout is considered unhealthy.<br /> 
		For HTTP, the situation is different. HTTP is specified as a "HTTP:port/PathToPing" grouping, for example "HTTP:80/weather/us/wa/seattle". In this case, a HTTP GET request is issued to the instance on the given port and path. Any answer other than "200 OK" within the timeout period is considered unhealthy.<br /> 
		The total length of the HTTP ping target needs to be 1024 16-bit Unicode characters or less.
		<br /><br />
	</span>
	<span id="lb_timeout_help" style="display:none;">
		Amount of time (in seconds) during which no response means a failed health probe. <br />
		The default is five seconds and a valid value must be between 2 seconds and 60 seconds. 
		Also, the timeout value must be less than the Interval value.<br /><br />
	</span>
	<span id="lb_uht_help" style="display:none;">
		The number of consecutive health probe failures that move the instance to the unhealthy state.<br /> 
		The default is 5 and a valid value lies between 2 and 10.
		<br /><br />
	</span>
	
	<span id="script_sync_help" style="display:none;">
		Scalr will wait until the script finishes executing.<br /><br />
	</span>
	<span id="script_async_help" style="display:none;">
		Scalr will launch a script in a new proccess on the instance.<br />It will not wait until execution is finished.
		<br /><br /><br />
	</span>
	<span id="mini_help" style="display:none;">
		Always keep at least this many running instances.
		<br /><br /><br />	
	</span>
	<span id="maxi_help" style="display:none;">
		Scalr will not launch more instances.
		<br /><br /><br />	
	</span>
	<span id="mysql_help" style="display:none;">
		MySQL snapshots contain a hotcopy of mysql data directory, file that holds binary log position and debian.cnf
		<br>
		When farm starts:<br> 
		1. MySQL master dowloads and extracts a snapshot from S3<br>
		2. When data is loaded and master starts, slaves download and extract a snapshot as well<br>
		3. Slaves are syncing with master for some time
	</span>
	<span id="scaler_help" style="display:none;">
		Agenda:<span style="font-size:6px;"><br /><br /></span> 
		<span style="padding:2px;padding-left:0px;margin-bottom:2px;"><span style="color:#9ef19c;background-color:#9ef19c;line-height:10px;width:10px;height:10px;">&nbsp;&nbsp;&nbsp;</span>&nbsp;&nbsp;- normal<br /></span>
		<span style="padding:2px;padding-left:0px;margin-bottom:2px;"><span style="color:#f7f9c8;background-color:#f7f9c8;line-height:10px;width:10px;">&nbsp;&nbsp;&nbsp;</span>&nbsp;&nbsp;- recommended only if you do not expect frequent load spikes/drops<br /></span>
		<span style="padding:2px;padding-left:0px;margin-bottom:2px;"><span style="color:#f7b8b8;background-color:#f7b8b8;line-height:10px;width:10px;">&nbsp;&nbsp;&nbsp;</span>&nbsp;&nbsp;- the difference is too small. Scaling may act unexpectedly (instances will be launched and terminated too frequently)<br /></span>
	</span>
{include file="inc/footer.tpl"}
