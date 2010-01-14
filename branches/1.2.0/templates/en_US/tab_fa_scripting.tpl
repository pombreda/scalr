<div id="itab_contents_scripts_n" class="x-hide-display" style="padding:10px;padding-top:0px;">
	<table width="99%" cellspacing="4">
		<tbody>
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
											<input type="radio" name="issync" value="1" id="issync_1" style="vertical-align:middle;"> {t}Synchronous{/t} <img style="vertical-align:middle;cursor:pointer;" title="Help" onclick="ShowHelp(event, 'script_sync_help', this);" src="/images/icon_shelp.png">&nbsp;&nbsp;
											<input type="radio" name="issync" value="0" id="issync_0" style="vertical-align:middle;"> {t}Asynchronous{/t} <img style="vertical-align:middle;cursor:pointer;" title="Help" onclick="ShowHelp(event, 'script_async_help', this);" src="/images/icon_shelp.png">
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
	</table>
</div>