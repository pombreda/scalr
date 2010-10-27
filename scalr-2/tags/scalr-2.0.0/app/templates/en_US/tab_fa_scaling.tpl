<div id="itab_contents_scaling_n" class="x-hide-display" style="padding:10px;">
	<div style="height:auto;margin:0px;padding:0px;">
		<div>
		<table width="99%" cellspacing="4">
			<tbody>
	       		<tr>
	         		<td width="200">Minimum instances <img style="vertical-align:middle;cursor:pointer;" title="Help" onclick="ShowHelp(event, 'mini_help', this);" src="/images/icon_shelp.png">:</td>
	         		<td><input type="text" size="2" class="role_settings text" id="scaling.min_instances" name="scaling.min_instances" value=""></td>
	         	</tr>
	         	<tr>
	         		<td>Maximum instances <img style="vertical-align:middle;cursor:pointer;" title="Help" onclick="ShowHelp(event, 'maxi_help', this);" src="/images/icon_shelp.png">:</td>
	         		<td><input type="text" size="2" class="role_settings text" id="scaling.max_instances" name="scaling.max_instances" value=""></td>
	         	</tr>
	         	<tr>
	         		<td>Polling interval (every):</td>
	         		<td><input type="text" size="2" class="role_settings text" id="scaling.polling_interval" name="scaling.polling_interval" value="1" /> minute(s)</td>
	         	</tr>
	         	<tr>	         	    
	         		<td colspan="2">
	         			<input type="checkbox" class="role_settings" id="scaling.upscale.timeout_enabled" name="scaling.upscale.timeout_enabled" style="vertical-align:middle;"  />
	         			Wait <input type="text" size="2" class="role_settings" id="scaling.upscale.timeout" name="scaling.upscale.timeout" value="10" /> 
	         			minute(s) after a new instance have been started and is running before the next up-scale
	         		</td>
	         	</tr>
	            <tr>					         	
	         		<td colspan="2">
	         			<input type="checkbox" class="role_settings" id="scaling.downscale.timeout_enabled" name="scaling.downscale.timeout_enabled" style="vertical-align:middle;"  />
	         			Wait <input type="text" size="2" class="role_settings" id="scaling.downscale.timeout" name="scaling.downscale.timeout" value="10" /> 
	         			minute(s) after a shutdown before shutting down another instance
	         		</td>
	         	</tr>

	         </tbody>
	     </table>	     
	     <table width="99%" cellspacing="0">
			<tbody>				
	     		<tr>
	         		<td colspan="2">
	         			<input style='vertical-align:middle;' type='checkbox' class='role_settings' name='scaling.keep_oldest' id='scaling.keep_oldest' value="1" />
	         			Keep oldest instance running after scale down
	         		</td>
	         	</tr>
	         </tbody>
	     </table>	     
	     </div>
	     <div style="">
   			<br /><br />
   			{foreach item=scaling_algo key=name from=$scaling_algos}
   			<div style="{$name}_based_scaling_container" style='padding:0px;'>
   				<div>
   					<input type='hidden' id='scaling_algo_list_{$name}' name='scaling_algo_list_{$name}' value='{$name}' class='scaling_algo_list' />
   					<input type='checkbox' onClick='RoleTabObject.SetScaling("{$name}", this.checked)' class='scaling_options' name='scaling.{$name}.enabled' id='scaling.{$name}.enabled' value="1" />
   					Enable scaling based on {$scaling_algo.based_on}
   				</div>
   				<div id="{$name}_scaling_options" style='display:none;border-bottom:1px solid #cccccc;margin-left:4px;margin-top:5px;background-color:#F9F9F9;margin-bottom:12px;padding:10px;'>
   					<div style="clear:both;">
   						{if $name != 'time'}
	   						{assign var="scaling_algo_data_form" value=$scaling_algo.settings}
	   						{include file="inc/scaling_algo_settings.tpl" DataForm=$scaling_algo_data_form}
   						{else}
   							<div style="font-size:11px;padding:2px;padding-bottom:7px;">Current time zone is: <span style='font-weight:bold;'>{$current_time_zone}</span> ({$current_time}). <a href="/system_settings.php">Click here</a> if you want to change it.</div>
   							{include file='inc/scaling_time_algo_settings.tpl'}
   						{/if}
   					</div>
   				</div>
   			</div>
   			{/foreach}
   			<br />
		</div>
	</div>
</div>