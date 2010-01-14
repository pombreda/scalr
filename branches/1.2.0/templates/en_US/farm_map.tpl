{include file="inc/header.tpl"}
	<!-- Additional Styles  -->
	<link rel="stylesheet" href="css/SelectControl.css" type="text/css" />
	<link rel="stylesheet" href="css/MapSelectControl.css" type="text/css" />
	<link href="css/popup.css" rel="stylesheet" type="text/css" />
	
	<!-- Additional javascript libs -->
	<script type="text/javascript" src="js/class.SelectControl.js"></script>
	<script type="text/javascript" src="js/class.NewPopup.js"></script>
	{literal}
	<style>
	
	.map_bull
	{
		background: url('/images/map_icons/bull.gif') no-repeat;
		margin-top:4px;
		height:21px;
		float:left;
	}
	
	.map_sep
	{
		background: url('/images/map_icons/map_bar_sep.gif') no-repeat;
		float:left;
		height:21px;
		margin-left:10px;
		margin-right:10px;
		margin-top:3px;
	}
	
	.map_role_container
	{
		width:100%;
		padding:0px;
		height:auto;
		margin-top:20px;
	}
	
	.map_role_headline
	{
		color:#808080;
		font-size:11px;
		height:21px;
		font-family:Verdana;
		width:100%;
		line-height:19px;
		background: url('/images/map_icons/header_line.png') repeat-x;
	}
	
	.map_instance_container
	{
		margin-right:20px;
		float:left;
		height:160px;
	}
	
	.map_instance_dashboard
	{
		cursor:pointer;
		width:116px;
		height:118px;
		background:url('/images/map_icons/item_bg.png') no-repeat;
	}
	
	</style>
	
	<script language="Javascript">
	var NoCloseButton = false;

	Event.observe(window, 'load', function(){
			
		 popup = new NewPopup('instance_info_popup', {target: '', width: 270, height: 220, selecters: new Array()});
	}); 
	
	function ShowInstanceInfo(event, instance_id, obj)
	{
		var event = event || window.event;
		
		var pos = Position.cumulativeOffset(obj);
		pos[0] = pos[0]+parseInt($(obj.id).offsetWidth)/2;
		pos[1] = pos[1]+parseInt($(obj.id).offsetHeight)/2;
		
		popup.raisePopup(pos);
		
		$('popup_loader').setStyle({display: ''});
		$('popup_contents').setStyle({display: 'none'});
		
		var url = '/server/server.php?_cmd=get_instance_info&iid='+instance_id; 
		
		new Ajax.Request(url,
		{ 
			method: 'get', 
			onSuccess: function(transport)
			{ 
				var cont = $('popup_contents');
				cont.innerHTML = transport.responseText; 
				cont.setStyle({display: ''});
				$('popup_loader').setStyle({display: 'none'});				 
			} 
		});
	}
	</script>
	{/literal}
	{assign var=farmname value=$farminfo.name}
	{include file="inc/table_header.tpl" table_header_text="$farmname" nofilter=1}
		<div style="margin:20px;width:auto;">
			{foreach key=key item=item from=$roles}
			<div class="map_role_container">
				<div class="map_role_headline">
					<div style="margin-left:-9px;height:21px;width:9px;float:left;background: url('/images/map_icons/header_lt.png') no-repeat;">&nbsp;</div>
					<div style="width:auto; float:left;margin-left:5px; margin-right:15px;">
						<div style="float:left; font-weight:normal;color:#000000;width:auto;min-width:240px;">
						{if $item.roletype == 'CUSTOM'}<img alt="Custom role" title="Custom role" style="vertical-align:middle;" src="/images/map_icons/custom_role.png">{/if}	{$item.name} ({$item.ami_id})
						</div>
						<div style="float:left;">
							<div class="map_bull" style="margin-left:10px;margin-right:2px;">&nbsp;</div>
							{if $item.architecture}<div style="float:left;">{$item.architecture}</div>{/if}
							<div class="map_bull" style="margin-left:8px;margin-right:2px;">&nbsp;</div>
							<div style="float:left;">{$item.instance_type}</div>
						</div>
						<div style="float:left;padding-top:4px;margin-left:20px;" title="Load average (current/min/max): {$item.LA|string_format:"%.2f"} / {$item.min_LA} / {$item.max_LA}">
							<div style="float:left;height:21px;line-height:21px;width:48px;vertical-align:middle;background: url('/images/map_icons/map_gauge_bg.png') no-repeat;"></div>
							<div style="float:left;margin-top:2px;margin-left:-47px;height:21px;line-height:21px;width:{$item.la_bar.width}px;vertical-align:middle;background: url('/images/gauge/bar_{$item.la_bar.color}.png') no-repeat;"></div>							
						</div>
						<div class="map_sep" style="margin-left:25px;margin-right:0px;">&nbsp;</div>
						<div style="float:left; width:200px;margin-left:5px;">
							<span id="control_{$item.id}"></span>
						</div>
					</div>
					<div style="margin-right:-9px;height:21px;width:9px;float:right;background: url('/images/map_icons/header_rt.png') no-repeat;">&nbsp;</div>
				</div>
				<div style="font-size:1px;clear:both;"></div>
				<script language="Javascript" type="text/javascript">
					var id = '{$item.id}';
					var name = '{$item.name}';
					var farmid = '{$item.farmid}';
				
			    	var menu = [
			            {literal}{href: 'farm_stats.php?role='+name+'&farmid='+farmid, innerHTML: 'Statistics'}{/literal}
			        ];
			        
			        {literal}			
			        var control = new SelectControl({menu: menu, stylePrefix:'map-', popupPosition: 'old'});
			        control.attach('control_'+id);
			        {/literal}
				
				</script>
				<div style="width:760px;">
				{section name=id loop=$item.instances}
				<div class="map_instance_container">
					<div style="padding-top:10px;">
						<div id="body_{$item.instances[id].instance_id}" onClick="ShowInstanceInfo(event, '{$item.instances[id].instance_id}', $('body_{$item.instances[id].instance_id}'));" align="center" class="map_instance_dashboard">
							<div style="font-size:10px;padding-top:5px;">{$item.instances[id].instance_id}</div>
							<div style="margin-top:4px; background:url('/images/map_icons/icons/{$item.icon}.png') no-repeat;height:75px;width:95px;">&nbsp;</div>
							<div style="display:{if $item.instances[id].issync}{else}none{/if};background: url('/images/map_icons/instance_sync.gif') no-repeat;margin-left:6px;margin-bottom:4px;float:left;width:15px;" title="Synchronizing. New role name: {$item.instances[id].issync}">
								&nbsp;
							</div>
							<div style="background: url('/images/map_icons/instance_state_{$item.instances[id].state_image}.png') no-repeat;float:right;margin-bottom:4px;width:17px;margin-right:6px;" title="{$item.instances[id].state}">
								&nbsp;
							</div>
							{if $item.instances[id].mysql_type}
							<div style="float:right; font-size:10px;margin-right:5px;">
								({$item.instances[id].mysql_type})
							</div>
							{/if}
						</div>
						<div style="margin-left:2px;">
							<a id="control_{$item.instances[id].instance_id}" href="javascript:void(0)">Options</a>
						</div>
					</div>
				</div>
				<script language="Javascript" type="text/javascript">
			    	var iid = '{$item.instances[id].instance_id}';
			    	var farmid = '{$item.instances[id].farmid}';
			    	
			    	var menu = [
			    		{if $item.instances[id].state == 'Running'}
			    			{literal}{target:'_blank', href: 'instances_view.php?action=sshClient&farmid='+farmid+'&instanceid='+iid, innerHTML: 'Open SSH console'}{/literal},
							{literal}{type: 'separator'}{/literal},		    		
			            	{if $item.instances[id].LA && $item.instances[id].isrebootlaunched == 0}{literal}{href: 'syncronize_role.php?iid='+iid, innerHTML: 'Synchronize to all'}{/literal},{/if}
			            	{if $item.instances[id].isrebootlaunched == 0}
			            		{literal}{href: 'console_output.php?iid='+iid, innerHTML: 'View console output'}{/literal},			            		
			            	{/if}
				            {if $item.instances[id].canusecustomEIPs}
				            	{if $item.instances[id].custom_elastic_ip}
				            		{literal}{href: 'instance_eip.php?iid='+iid+'&task=unassign', innerHTML: 'Disassociate Elastic IP'}{/literal},
				            	{else}
				            		{literal}{href: 'instance_eip.php?iid='+iid+'&task=assign', innerHTML: 'Associate Elastic IP'}{/literal},
				            	{/if}
				            {/if}
				        {/if}
				        {if $item.alias == 'mysql'}
			        		{literal}{type: 'separator'},{/literal}
			        		{literal}{href: 'farm_mysql_info.php?farmid='+farmid, innerHTML: 'Backup\/bundle MySQL data'}{/literal},
			        	{/if}
				        {literal}{type: 'separator'}{/literal},
				        {if $item.instances[id].isrebootlaunched == 0 && $item.instances[id].state == 'Running'}
				        	{literal}{href: 'instances_view.php?task=reboot&iid='+iid+'&farmid='+farmid, innerHTML: 'Reboot'}{/literal},
				        {/if}
				        {literal}{href: 'instances_view.php?task=terminate&iid='+iid+'&farmid='+farmid, innerHTML: 'Terminate'},{/literal}
				        {literal}{type: 'separator'}{/literal},
			            {literal}{href: 'logs_view.php?iid='+iid, innerHTML: 'View logs'}{/literal}
			        ];
			        
			        {literal}			
			        var control = new SelectControl({menu: menu, popupPosition: 'old'});
			        control.attach('control_'+iid);
			        {/literal}
				
				</script>
				{/section}
				{section name=id loop=$item.empty_instances}
				<div style="margin-right:20px;float:left;height:160px;">
					<div style="padding-top:10px;">
						<div id="body_empty_{$smarty.section.id.iteration}" align="center" class="map_instance_dashboard">
							<div style="font-size:10px;padding-top:5px;color:#cccccc">Spare instance</div>
							<div style="margin-top:4px;width:100px;height:75px;background: url('/images/map_icons/icons/{$item.icon}_disabled.png') no-repeat;">&nbsp;</div>
						</div>
						<div style="margin-left:2px;">
							&nbsp;
						</div>
					</div>
				</div>
				{/section}
				</div>
			</div>
			<div style="clear:both;"></div>
			{/foreach}
		</div>
	{include file="inc/table_footer.tpl" disable_footer_line=1}
	<div id="instance_info_popup" align="left">
    	<div id="popup_loader" align="center" style="display:none;padding-left:20px;padding-top:10px;line-height:130px;width:auto;height:130px;margin-top:16px;">
    		<img style="vertical-align:middle;" src="/images/snake-loader.gif"> Loading instance information...
    	</div>
    	<div id="popup_contents" style="display:none;margin-top:15px;">
    		
    	</div>
	</div>
{include file="inc/footer.tpl"}