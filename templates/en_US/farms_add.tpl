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
	</style>
	{/literal}
    <link rel="STYLESHEET" type="text/css" href="/css/dhtmlXTree.css" />
    <link href="css/popup.css" rel="stylesheet" type="text/css" />
    <script language="javascript" type="text/javascript" src="/js/dhtmlxtree/dhtmlXTree.js"></script>
    <script language="javascript" type="text/javascript" src="/js/dhtmlxtree/dhtmlXCommon.js"></script>
    <script language="javascript" type="text/javascript" src="/js/highlight/highlight.js"></script>    
     
    <link rel="stylesheet" type="text/css" href="/js/FC_TrackBar/trackbar.css" />
    <script type="text/javascript">
    	var NoCloseButton = false;
    </script>
	<script type="text/javascript" src="/js/FC_TrackBar/trackbar.js"></script>
	<script type="text/javascript" src="/js/class.NewPopup.js"></script>
	<script type="text/javascript" src="/js/class.RoleTab.js"></script>
	<script type="text/javascript" src="/js/class.FarmRole.js"></script>
	<script type="text/javascript" src="/js/farm_manager.inc.js"></script>
	<link rel="stylesheet" href="/js/highlight/styles/default.css" />
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
	
    <div style="padding-left:5px;padding-right:5px;height:100%;" id="main_page_cont">
    <table width="100%" cellpadding="10" cellspacing="5" border="0" style="height:100%;">
        <tr valign="top">
            <td width="250" valign="top">
                {include file="inc/table_header.tpl" nofilter=1}
                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-left:10px;">
                	<tr valign="top">
                		<td>
                			<div style="position:relative;top:0px;left:0px;width:250px;height:500px;">
	                		    <div style="position:absolute;top:0px;left:0px;z-index:1;">
	                		      <div id="inventory_tree" style="width:250px;height:500px;"></div>
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
	                                var SELECTED_AMI_ID = '{$ami_id}';                          

	                                var tree = null;
	                                var events_tree = null;
									var LoadMask = null;
									
									{if $roles}
	                                var l_roles = {$roles};
	                                {/if}

	                                {if $default_scaling_algos}
		                            var default_scaling_algos = {$default_scaling_algos};
		                            {/if}
	                                
	                                /*
	                                Setup initial observer
	                                */
									{literal}
									
									hljs.initHighlightingOnLoad.apply(null, hljs.ALL_LANGUAGES);								 
	            	                            	                
	            	                /*
	            	                Setup script events tree
	            	                */
	            	                var ConfigFieldTemplate = new Template('<div class="s_field_container"> '+
										'<div class="s_field_name">#{title}:</div>'+
										'<div style="float:left;"><input id="script_configopt_#{name}" type=\'text\' name=\'#{name}\' value=\'\' class=\'text configopt\'></div>'+
										'<div style="clear:both;"></div>'+
										'</div>'
									); 

	            	                Ext.onReady(function(){

	            	                	var gridCt = Ext.get(document.body);
	            	        	   		var bodyEl = Ext.get(document.body);
	            	        	    	//bodyEl.setStyle("overflow", "hidden");
	            	        	    	gridCt.setWidth(Ext.lib.Dom.getViewWidth() - gridCt.getPadding("lr") - gridCt.getBorderWidth("lr"));
	            	        	    	gridCt.setHeight(Math.max(300, Ext.lib.Dom.getViewHeight() - gridCt.getY() - gridCt.getPadding("tb") - gridCt.getBorderWidth("tb")));

										
	            	                	window.LoadMask = new Ext.LoadMask(Ext.getBody(), {msg:"Please wait..."});
	            	                	window.LoadMask.show();
			            	            
		            	            	var Tree = Ext.tree;
		            	            	    
	            	            	    window.RolesOrderTree = new Tree.TreePanel({
	            	            	        el:'roles_order_panel',
	            	            	        useArrows:false,
	            	            	        lines: false,
	            	            	        title:'Drag & drop items to set roles launch order.',
	            	            	        autoScroll:true,
	            	            	        animate:true,
	            	            	        height:300,
	            	            	        style:'width:100%',
	            	            	        itemCls:'test-class',
	            	            	        enableDD:true,
	            	            	        containerScroll: true,
											rootVisible:false,
	            	            	        
	            	            	        root: {
	            	            	            text: 'Roles',
	            	            	            draggable:false,
	            	            	            id:'source'
	            	            	        }
	            	            	    });

	            	            	    	            	            	    
	            	            	    // render the tree
	            	            	    window.RolesOrderTree.render();            	            	    
	            	            	    window.RolesOrderTree.getRootNode().expand();

	            	            	    MainLoader(1);
										MainLoader(2);
		            	            });
		            	            
	            	                {/literal}
	                            </script>
	                        </div>
                		</td>
                	</tr>
                </table>
                {include file="inc/table_footer.tpl" disable_footer_line=1}
            </td>
            <td style="padding:0px;margin:0px;" valign="top">
                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="padding:0px;margin:0px;">
                    <tr>
                        <td style="padding:0px;margin:0px;padding-top:10px;">
                           {include file="inc/table_header.tpl" nofilter=1 tabs=1}
								{include intable_tabs=0 intable_classname="tab_contents" intableid="tab_contents_general" visible="" file="inc/intable_header.tpl" header="Farm information" color="Gray"}
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
                            		<td width="20%">Region:</td>
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
                           		{include file="inc/intable_footer.tpl" color="Gray"}
                           		
                           		{include intable_tabs=0 intable_classname="tab_contents" intableid="tab_contents_rso" visible="none" file="inc/intable_header.tpl" header="Roles startup order" color="Gray"}
	                            	<tr>
	                            		<td colspan='2' style='width:100%;'>
	                            		<div id='roles_order_panel'></div>
	                            		</td>
	                            	</tr>
                           		{include file="inc/intable_footer.tpl" color="Gray"}
                           		
                           		<div id="tab_contents_roles" class="tab_contents" style="display:none;">
                           			{include file="inc/intable_header_tabs.tpl" header="Role information" color="Gray"}
                           			<tbody id="itab_contents_info" class="itab_contents">
                           			<tr id="c_warning" style="display:none;">
                           				<td colspan="2">
                           					<div class="Webta_ExperimentalMsg" style="margin-bottom:15px;">
												This role created by user. Scalr is not responsible for functionality of this image. Use it at your own risk.
											</div>
                           				</td>
                           			</tr>
                           			<tr>
	                            		<td>Role name:</td>
	                            		<td>
	                            			<span id="c_role_name"></span>
	                            			<span>&nbsp;&nbsp;[ <a target="_blank" id="role_info_link" href="">More info</a> ]</span>
	                            		</td>
	                            	</tr>
	                            	<tr id="c_author_tr" style="display:none;">
	                            		<td>Author:</td>
	                            		<td id="c_author"></td>
	                            	</tr>
	                            	<tr>
	                            		<td>Category:</td>
	                            		<td id="c_role_type"></td>
	                            	</tr>
	                            	<tr id="c_based_on_tr" style="display:none;">
	                            		<td>Based on role:</td>
	                            		<td id="c_based_on"></td>
	                            	</tr>
	                            	<tr>
	                            		<td>AMI ID:</td>
	                            		<td id="c_role_amiid"></td>
	                            	</tr>
	                            	<tr>
	                            		<td>Architecture:<span id="c_role_arch" style="display:none;"></span></td>
	                            		<td>
	                            			<span id="arch_i386" class="ui_enum">i386</span>&nbsp;&nbsp;<span id="arch_x86_64" class="ui_enum">x86_64</span>
	                            		</td>
	                            	</tr>
	                            	<tr><td colspan="2">&nbsp;</td></tr>
	                            	<tr valign="top">
	                            		<td valign="top">Description:</td>
	                            		<td id="c_role_descr"></td>
	                            	</tr>
	                            	<tr><td colspan="2">&nbsp;</td></tr>
	                            	<tr valign="top" id="comments_links_row">
	                            		<td colspan="2"><a id="comments_link" target="_blank" href=""></a></td>
	                            	</tr>
	                            	</tbody>
	                            	
	                            	<tbody id="itab_contents_mysql" class="itab_contents" style="display:none">
	                            	<tr>
	                            		<td colspan="2"><input style="vertical-align:middle;" type="checkbox" {if $farminfo.mysql_bundle == 1}checked{/if} name="mysql_bundle" id="mysql_bundle" value="1"> Bundle and save mysql data snapshot every <img style="vertical-align:middle;cursor:pointer;" title="Help" onclick="ShowHelp(event, 'mysql_help', this);" src="/images/icon_shelp.gif">: <input type="text" size="3" class="text" id="mysql_rebundle_every" name="mysql_rebundle_every" value="{if $farminfo.mysql_rebundle_every}{$farminfo.mysql_rebundle_every}{else}48{/if}" /> hours</td>
	                            	</tr>
	                            	<tr>
	                            		<td colspan="2"><input style="vertical-align:middle;" type="checkbox" {if $farminfo.mysql_bcp == 1}checked{/if} name="mysql_bcp" id="mysql_bcp" value="1"> Periodically backup databases every: <input type="text" size="3" class="text" id="mysql_bcp_every" name="mysql_bcp_every" value="{if $farminfo.mysql_bcp_every}{$farminfo.mysql_bcp_every}{else}180{/if}" /> minutes</td>
	                            	</tr>
	                            	<tr>
	                            		<td colspan="2">&nbsp;</td>
	                            	</tr>
	                            	<tr>
	                            		<td>Storage engine:</td>
	                            		<td>
	                            			<select onchange="CheckEBSSize(this.value);" id="mysql_data_storage_engine" name="mysql_data_storage_engine" class="text">
	                            				<option value="eph">Ephemeral device</option>
	                            				<option value="lvm">LVM</option>
	                            				<option value="ebs">EBS</option>
	                            			</select>
	                            		</td>
	                            	</tr>
	                            	<tr id="mysql_ebs_size_tr">
	                            		<td>EBS size (max. 1000 GB):</td>
	                            		<td>
	                            			<input type="text" size="5" class="text" id="mysql_ebs_size" name="mysql_ebs_size" value="100" /> GB
	                            		</td>
	                            	</tr>
	                            	<script language="Javascript">
	                            	{literal}
									function CheckEBSSize(storage_engine)
									{
										if (storage_engine == 'ebs') {
											$('mysql_ebs_size_tr').style.display = '';
										}
										else {
											$('mysql_ebs_size_tr').style.display = 'none';
										}
									}
	                            	
	                            	CheckEBSSize($('mysql_data_storage_engine').value);
	                            	{/literal}
	                            	</script>
	                           		</tbody>
	                            	
	                            	<tbody id="itab_contents_scaling" class="itab_contents" style="display:none">
                           			<tr>
	                            		<td>Minimum instances <img style="vertical-align:middle;cursor:pointer;" title="Help" onclick="ShowHelp(event, 'mini_help', this);" src="/images/icon_shelp.gif">:</td>
	                            		<td><input type="text" size="2" class="role_settings text" id="scaling.min_instances" name="scaling.min_instances" value=""></td>
	                            	</tr>
	                            	<tr>
	                            		<td>Maximum instances <img style="vertical-align:middle;cursor:pointer;" title="Help" onclick="ShowHelp(event, 'maxi_help', this);" src="/images/icon_shelp.gif">:</td>
	                            		<td><input type="text" size="2" class="role_settings text" id="scaling.max_instances" name="scaling.max_instances" value=""></td>
	                            	</tr>
	                            	<tr>
	                            		<td>Polling interval (every):</td>
	                            		<td><input type="text" size="2" class="role_settings text" id="scaling.polling_interval" name="scaling.polling_interval" value="1" /> minute(s)</td>
	                            	</tr>
	                            	<tr valign="middle">
	                            		<td colspan="2">
	                            			<br />
	                            			{foreach item=scaling_algo key=name from=$scaling_algos}
	                            			<div style="{$name}_based_scaling_container" style='padding:0px;'>
	                            				<div>
	                            					<input type='checkbox' onClick='RoleTabObject.SetScaling("{$name}", this.checked)' class='scaling_options' name='scaling.{$name}.enabled' id='scaling.{$name}.enabled' value="1" />
	                            					Enable scaling based on {$scaling_algo.based_on}
	                            				</div>
	                            				<div id="{$name}_scaling_options" style='display:none;border-bottom:1px solid #cccccc;margin-left:4px;margin-top:5px;background-color:#F9F9F9;margin-bottom:12px;padding:10px;'>
	                            					<div>
	                            						<table>
	                            						{assign var="scaling_algo_data_form" value=$scaling_algo.settings}
	                            						{include file="inc/scaling_algo_settings.tpl" DataForm=$scaling_algo_data_form}
	                            						</table>
	                            					</div>
	                            				</div>
	                            			</div>
	                            			{/foreach}
	                            		</td>
	                            	</tr>
                           			</tbody>
	                            	
	                            	<tbody id="itab_contents_placement" class="itab_contents" style="display:none">
                           			<tr>
	                            		<td>Placement:</td>
	                            		<td>
	                            			<select id="availZone" name="availZone[{$servers[id].id}]" class="text">
	                                    		{section name=zid loop=$avail_zones}
	                                    			{if $avail_zones[zid] == ""}
	                                    			<option {if $servers[id].avail_zone == ""}selected{/if} value="">Choose randomly</option>
	                                    			<option {if $servers[id].avail_zone == "x-scalr-diff"}selected{/if} value="x-scalr-diff">Place in different zones</option>
	                                    			{else}
	                                    			<option {if $servers[id].avail_zone == $avail_zones[zid]}selected{/if} value="{$avail_zones[zid]}">{$avail_zones[zid]}</option>
	                                    			{/if}
	                                    		{/section}
	                                    	</select>
	                                    </td>
	                            	</tr>
	                            	<tr>
	                            		<td>Instances type:</td>
	                            		<td>
	                            			<select id="iType" name="iType[{$servers[id].id}]" class="text">
	                                    		{section name=zid loop=$servers[id].types}
	                                    			<option {if $servers[id].instance_type == $servers[id].types[zid]}selected{/if} value="{$servers[id].types[zid]}">{$servers[id].types[zid]}</option>
	                                    		{/section}
	                                    	</select>
	                            		</td>
	                            	</tr>
                           			</tbody>
	                            	
	                            	<tbody id="itab_contents_params" class="itab_contents" style="display:none">
	                            	<tr>
                           				<td colspan="2">
			                            	<p class="placeholder">
				    							<a target="_blank" href="http://code.google.com/p/scalr/wiki/RoleOptions">How do I retrieve these values on my instances?</a>
				    						</p>
		    							</td>
		    						</tr>
                           			<tr>
                           				<td colspan="2" id="role_parameters">
                           					
                           				</td>
                           			</tr>
                           			</tbody>
	                            	
	                            	<tbody id="itab_contents_eips" class="itab_contents" style="display:none">
	                            	<tr>
	                            		<td colspan="2">
	                            			<p class="placeholder">
												If this option is enabled, 
												Scalr will assign Elastic IPs to all instances of this role. It usually takes few minutes for IP to assign.
												The amount of allocated IPs increases when new instances start, 
												but not decreases when instances terminated.
												Elastic IPs are assigned after instance initialization. 
												This operation takes few minutes to complete. During this time instance is not available from 
												the outside and not included in application DNS zone.
	                            			</p>
	                            		</td>
	                            	</tr>
                           			<tr>
                           				<td>Use Elastic IPs:</td>
                           				<td>
	                                    	<input {if $servers[id].use_elastic_ips == 1}checked{/if} {if $farminfo.status == 1 && $servers[id].use_elastic_ips == 1}disabled{/if} type="checkbox" id="use_elastic_ips" name="use_elastic_ips[{$servers[id].id}]" value="1">
	                                    	{if $servers[id].use_elastic_ips == 1 && $farminfo.status == 1}<input type="hidden" name="use_elastic_ips[{$servers[id].id}]" value="1" />{/if}
                           				</td>
                           			</tr>
                           			</tbody>
	                            	
	                            	<tbody id="itab_contents_ebs" class="itab_contents" style="display:none">
                           			<tr>
	                            		<td colspan="2">
	                            			<p class="placeholder">
												When new instance initialized, Scalr will<br>
												1. Attach a first detached volume, left by terminated or crashed instance or create a new EBS volume, attach it, and create an ext3 filesystem on it.<br />
												2. If "Automatically mount device" option selected, volume will be mounted.<br>
	                            			</p>
	                            		</td>
	                            	</tr>
                           			<tr>
	                            		<td colspan="2">When instance based on this role boots up:</td>
	                            	</tr>
                           			<tr>
							    		<td colspan="2"><input onclick="ShowEBSOptions(this.value);" type="radio" name="ebs_ctype" checked value="1" style="vertical-align:middle;"> Do not use EBS</td>
							    	</tr>
                           			<tr>
							    		<td colspan="2">
							    			<div style="float:left;">
							    				<input onclick="ShowEBSOptions(this.value);" type="radio" name="ebs_ctype" value="2" style="vertical-align:middle;"> Attach empty volume with size:
							    				<input style="vertical-align:middle;" type="text" id="ebs_size" name="ebs_size" value="1" class="text" size="3"> GB
							    			</div>							    			
							    		</td>
							    	</tr>
							    	<tr>
							    		<td colspan="2"><input onclick="ShowEBSOptions(this.value);" type="radio" {if $snapshots|@count == 0}disabled{/if} name="ebs_ctype" value="3" style="vertical-align:middle;"> Attach volume from snapshot:
							    			<select {if $snapshots|@count == 0}disabled{/if} style="vertical-align:middle;" id="ebs_snapid" name="ebs_snapid" class="text">
							    			{section name=sid loop=$snapshots}
												<option {if $snapId == $snapshots[sid]}selected{/if} value="{$snapshots[sid]}">{$snapshots[sid]}</option>
											{sectionelse}
												<option value="">No snapshots found</option>
											{/section}
											</select>
							    		</td>
							    	</tr>
							    	<tr>
							    		<td colspan="2">
							    			<div id="ebs_mount_options" style="display:none;">
							    			<br />
							    			<input type="checkbox" onclick="$('ebs_mountpoint').disabled = !this.checked;" id="ebs_mount" style="vertical-align:middle;"> Automatically mount device to <input type="text" class="text" id="ebs_mountpoint" disabled size="10" value="/mnt/storage"> mount point.
							    			</div>
							    		</td>
							    	</tr>
                           			</tbody>
	                            	
	                            	<tbody id="itab_contents_timeouts" class="itab_contents" style="display:none">
                           			<tr>
										<td colspan="2">Terminate instance if it will not send 'rebootFinish' event after reboot in <input name="reboot_timeout" type="text" class="text" id="reboot_timeout" value="" size="3"> seconds.</td>
									</tr>
									<tr>
										<td colspan="2">Terminate instance if it will not send 'hostUp' or 'hostInit' event after launch in <input name="launch_timeout" type="text" class="text" id="launch_timeout" value="" size="3"> seconds.</td>
									</tr>
									<tr>
										<td colspan="2">Terminate instance if cannot retrieve it's status in <input name="status_timeout" type="text" class="text" id="status_timeout" value="" size="3"> minutes.</td>
									</tr>
									</tbody>
									
									<tbody id="itab_contents_dns" class="itab_contents" style="display:none">
                           			<tr>
                           				<td colspan="2">
	                                    	<input type="checkbox" id="dns.exclude_role" class="role_settings" name="dns.exclude_role" value="1" style="vertical-align:middle;" />
	                                    	Exclude role from DNS zone
                           				</td>
                           			</tr>
									</tbody>
																		
									<tbody id="itab_contents_scripts" class="itab_contents" style="display:none;">
                           			<tr>
										<td colspan="2">
											<div style="float:left;margin-left:-10px;width:100%;margin-top:0px;position:relative;">
											<table width="100%" border="0">
												<tr valign="top">
													<td>
														<div style="padding:5px;">
							                		      <div id="scripts_tree" style="width:250px;height:400px;"></div>
							                		    </div> 
													</td>
													<td style="border-left:3px solid #dcdcdc;">
														&nbsp;
													</td>
													<td>
													</td>
													<td width="100%">
														<p class="placeholder">
															Scalr can execute scripts on instances upon various events.<br>
															Tick the checkbox to enable the script and enter variable values.<br>
															Drag scripts in the tree to change the order of scripts to be executed.<br>
															Scalr will replace variables in template with entered values before executing script on instance.<br> 
															
															You can create your own scripts templates inside Settings &rarr; Script templates.<br>
				                            			</p>
														<div id="event_script_config_form">
															<div id="event_script_info" style="display:none;">
																<br>
																<div style="padding-bottom:12px;">
																	<div style="width:120px;float:left;">When:</div> 
																	<div id="event_script_edescr" style="float:left;"></div>
																	<div style="line-height:3px;clear:both;"></div>
																</div>
																<div style="padding-bottom:12px;">
																	<div style="width:120px;float:left;">Do:</div>
																	<div style="float:left;" id="event_script_config_form_description"></div>
																	<div style="line-height:3px;clear:both;"></div>
																</div>
																<div id="event_script_target" style="padding-bottom:12px;display:none;">
																	<div style="width:120px;float:left;">Where:</div> 
																	<div style="float:left;">
																		<select style="vertical-align:middle;" name="event_script_target_value" id="event_script_target_value" class="text">
																			<option id="event_script_target_value_instance" value="instance">That instance only</option>
																			<option id="event_script_target_value_role" value="role">All instances of the role</option>
																			<option value="farm">All instances in the farm</option>
																		</select>
																	</div>
																	<div style="line-height:3px;clear:both;"></div>
																	
																	<div style="width:120px;float:left;margin-top:8px;">Execution mode:</div> 
																	<div style="float:left;margin-top:6px;">
																		<input type="radio" name="issync" value="1" id="issync_1" style="vertical-align:middle;"> {t}Synchronous{/t} <img style="vertical-align:middle;cursor:pointer;" title="Help" onclick="ShowHelp(event, 'script_sync_help', this);" src="/images/icon_shelp.gif">&nbsp;&nbsp;
																		<input type="radio" name="issync" value="0" id="issync_0" style="vertical-align:middle;"> {t}Asynchronous{/t} <img style="vertical-align:middle;cursor:pointer;" title="Help" onclick="ShowHelp(event, 'script_async_help', this);" src="/images/icon_shelp.gif">
																	</div>
																	<div style="line-height:3px;clear:both;"></div>
																	
																	<div style="width:120px;float:left;margin-top:8px;">Timeout:</div> 
																	<div style="float:left;margin-top:6px;">
																		<input style='vertical-align:middle;' type='text' name='scripting_timeout' id='scripting_timeout' class="text" size="5"> seconds
																	</div>
																	<div style="line-height:3px;clear:both;"></div>
																</div>
																<div id="event_script_version" style="padding-bottom:12px;display:none;">
																	<div style="width:120px;float:left;">Version:</div>
																	<div style="float:left;">
																		<select style="vertical-align:middle;" onchange="SetVersion(this.value);" name="script_version" id="script_version" class="text">
																			<option value="latest">Latest</option>
																		</select>
																	</div>
																	<div style="line-height:3px;clear:both;"></div>
																</div>
															</div>
															<div id="event_script_config_title" style="margin-bottom:15px;display:none;">With the following values:</div>
															<div id="event_script_config_container">
																
																
															</div>
															<div id="script_source_div" style="width:580px;">
																<div id="view_source_link" style="margin-left:15px;cursor:pointer;" onclick="ViewTemplateSource();">
																	<table style="" cellpadding="0" cellspacing="0">
																		<tr>
																			<td width="7"><div class="TableHeaderLeft_Gray" style="height:25px;"></div></td>
																			<td>
																			<div style="padding-top:2px;line-height:20px;" class="SettingsHeader_Gray" align="center">
																				<img id="source_img" src="/images/dhtmlxtree/csh_vista/script_source_open.gif" onclick="ViewTemplateSource();" style="vertical-align:middle;cursor:pointer;"> <span id="source_link" style="vertical-align:middle;">View source</span>
																			</div>
																			</td>
																			<td width="7"><div class="TableHeaderRight_Gray" style="height:25px;"></div></td>
																		</tr>
																	</table>
																</div>
																<div style="border-top: #dcdcdc 3px solid;height:1px;line-height:1px;width:570px;">&nbsp;</div>
																<div id="script_source_container" style="height:185px;width:570px;overflow:hidden;margin-top:-1px;">
																	
																</div>
															</div>
														</div>
													</td>
												</tr>
											</table>
											</div>
										</td>
									</tr>
									</tbody>
                           			{include file="inc/intable_footer_tabs.tpl" color="Gray"}
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
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    </div>
    <script language="Javascript">
    $('button_js').style.display='';
	var NoCloseButton = false;
	
	{literal}
	function ShowDescriptionPopup(event, id, obj)
	{
		var event = event || window.event;
		
		var pos = Position.positionedOffset(obj);
		
		pos[0] = Event.pointerX(event);
		pos[1] = Event.pointerY(event);
		
		$('popup_contents').innerHTML = $('role_description_tooltip_'+id).innerHTML;
		
		popup.raisePopup(pos);
	}
	
	function ShowHelp(event, content_id, obj)
	{
		var event = event || window.event;
		
		var pos = Position.positionedOffset(obj);
		
		pos[0] = Event.pointerX(event)+50;
		pos[1] = Event.pointerY(event);
		
		$('popup_help_contents').innerHTML = $(content_id).innerHTML;
		
		popup_help.raisePopup(pos);
	}
	{/literal}
	
	$('tab_roles').style.display = 'none';
	
	SetActiveTab('general');
	</script>
	
	{section name=id loop=$roles_descr}
		{if $roles_descr[id].description}
		<span id="role_description_tooltip_{$roles_descr[id].ami_id}" style="display:none;">
			<div><span style="color:#888888;">Role name:</span> {$roles_descr[id].name}</div>
			<div><span style="color:#888888;">AMI ID:</span> {$roles_descr[id].ami_id}</div>
			<div><span style="color:#888888;">Description:</span><br> {$roles_descr[id].description}</div>
		</span>
		{/if}
	{/section}
	<span id="script_sync_help" style="display:none;">
		Scalr will wait until the script finishes executing.<br /><br />
	</span>
	<span id="script_async_help" style="display:none;">
		Scalr will launch a script in a new proccess on the instance.<br />It will not wait until execution is finished.
	</span>
	<span id="mini_help" style="display:none;">
		Always keep at least this many running instances	
	</span>
	<span id="maxi_help" style="display:none;">
		Scalr will not launch more instances	
	</span>
	<span id="mysql_help" style="display:none;">
		MySQL snapshots contain a hotcopy of mysql data directory, file that holds binary log position and debian.cnf
		<br>
		When farm starts:<br> 
		1. MySQL master dowloads and extracts a snapshot from S3<br>
		2. When data is loaded and master starts, slaves download and extract a snapshot as well.<br>
		3. Slaves are syncing with master for some time
	</span>
	<span id="scaler_help" style="display:none;">
		Agenda:<span style="font-size:6px;"><br /><br /></span> 
		<span style="padding:2px;padding-left:0px;margin-bottom:2px;"><span style="color:#9ef19c;background-color:#9ef19c;line-height:10px;width:10px;height:10px;">&nbsp;&nbsp;&nbsp;</span>&nbsp;&nbsp;- normal<br /></span>
		<span style="padding:2px;padding-left:0px;margin-bottom:2px;"><span style="color:#f7f9c8;background-color:#f7f9c8;line-height:10px;width:10px;">&nbsp;&nbsp;&nbsp;</span>&nbsp;&nbsp;- recommended only if you do not expect frequent load spikes/drops<br /></span>
		<span style="padding:2px;padding-left:0px;margin-bottom:2px;"><span style="color:#f7b8b8;background-color:#f7b8b8;line-height:10px;width:10px;">&nbsp;&nbsp;&nbsp;</span>&nbsp;&nbsp;- the difference is too small. Scaling may act unexpectedly (instances will be launched and terminated too frequently)<br /></span>
	</span>
{include file="inc/footer.tpl"}
