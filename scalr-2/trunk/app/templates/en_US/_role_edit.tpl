{include file="inc/header.tpl"}
    <script language="Javascript" src="/js/class.DataFormField.js"></script>
    
    {literal}
    <script language="Javascript" type="text/javascript">
    
    Ext.onReady(function()
    {   
		 window.df_action = 'create';	 
		 window.df = new DataForm(Ext.get('role_data_form'));
		 
		 {/literal}
		 {if $role_options_dataform}
		 	var data_form = {$role_options_dataform|replace:"\'":"\\\'"};
		 	window.df.Load(data_form);
		 {/if}
		 {literal}
	
		 SetFieldForm();
		 AllowMultipleChoice(false);
	});
    
    </script>
	<style>
	
	.item_key
	{
	    padding:2px;float:left;width:150px;
	}
	
	.item_value
	{
	    padding:2px;float:left;width:150px;
	}
	
	.item_def
	{
	    padding:2px;float:left;width:75px;
	}
	
	
	.item_delete
	{
	    padding:2px;float:left;width:46px;
	    cursor:pointer;
	}
	
	</style>
{/literal}
    <br>
	{include file="inc/table_header.tpl" nofilter=1 tabs=1}
		{include intable_classname="tab_contents" intableid="tab_contents_general" visible="" file="inc/intable_header.tpl" header="Role information" color="Gray"}
    	<tr>
    		<td width="20%" style="padding:5px;">Image IDs:</td>
    		<td style="padding:5px;">{$DBRole->getImagesString()}<input type="hidden" name="id" value="{$DBRole->id}"></td>
    	</tr>
    	<tr>
    		<td width="20%" style="padding:5px;">Architecture:</td>
    		<td style="padding:5px;">{$DBRole->architecture}</td>
    	</tr>
		<tr>
    		<td width="20%" style="padding:5px;">Role name:</td>
    		<td style="padding:5px;">{$DBRole->name}</td>
    	</tr>
    	<tr>
    		<td width="20%" style="padding:5px;">Type:</td>
    		<td style="padding-left:0px;">
				<select name="alias" class="text" id="alias">
					<option {if $DBRole->hasBehavior('app')}selected{/if} value="app">Application servers</option>
					<option {if $DBRole->hasBehavior('www')}selected{/if} value="www">Load balancers</option>
					<option {if $DBRole->hasBehavior('mysql')}selected{/if} value="mysql">Database servers</option>
					<option {if $DBRole->hasBehavior('base')}selected{/if} value="base">Base images</option>
					<option {if $DBRole->hasBehavior('memcached')}selected{/if} value="memcached">Caching servers</option>
				</select>
			</td>
    	</tr>
    	<tr valign="middle">
    		<td style="padding:5px;" width="20%">Default SSH port:</td>
    		<td style="padding:5px;padding-left:0px;">
    		<div style="float:left;vertical-align:middle;height:30px;">
    			<input type="text" class="text" style="margin-top:4px;" name="default_SSH_port" value="{if $DBRole->defaultSshPort}{$DBRole->defaultSshPort}{else}22{/if}" />
    		</div>
    		<div style="float:left;vertical-align:middle;height:30px;">
	    		<div class="Webta_ExperimentalMsg" style="margin-top:2px;font-size:10px;line-height:;">
					This setting WON'T change default SSH port on the servers. This port should be opened in the security groups.
				</div>
			</div>
    		</td>
    	</tr>
    	<tr valign="top">
    		<td style="padding:5px;" width="20%">Description:</td>
    		<td style="padding:5px;padding-left:0px;">
    			<textarea rows="5" cols="50" name="description" class="text">{$DBRole->description}</textarea>
    		</td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}

		<div id="tab_contents_options" class="tab_contents" style="padding:7px;display:none;">
		<p class="placeholder">
			These options will appear in the Parameters tab on farm/role edit Page.
			<a target="_blank" href="http://code.google.com/p/scalr/wiki/RoleOptions">How do I retrieve these values on my instances?</a>
		</p>
		<table border="0" cellpadding="0" cellspacing="0" width="100%">
		<tr>
			<td width="7" class="TableHeaderCenter_Gray"></td>
			<td class="Inner_Gray">
				<table width="100%" cellspacing="0" cellpadding="0" id="Webta_InnerTable_roleopts">
		        <tr>
		    		<td colspan="2">
		    			<table width="100%" cellspacing="0" cellpadding="0">
		    				<tr valign="top">
		    					<td width="50%" style="padding:10px;">
		    						<table width="100%" border="0" cellspacing="0" cellpadding="0">
		    							<tr>
											<td width="7"><div class="TableHeaderLeft_Gray"></div></td>
											<td>
												<div id="webta_table_header" style="line-height:20px;" class="SettingsHeader_Gray">Field Name</div>
											</td>
											<td>
												<div id="webta_table_header" style="line-height:20px;" class="SettingsHeader_Gray">Field type</div>
											</td>
											<td>
												<div id="webta_table_header" style="line-height:20px;" class="SettingsHeader_Gray">Required</div>
											</td>
											<td>
												<div id="webta_table_header" style="line-height:20px;" class="SettingsHeader_Gray"></div>
											</td>
											<td width="7"><div class="TableHeaderRight_Gray"></div></td>
										</tr>
										<tbody id="no_fields">
		    								<tr>
		    									<td colspan="10" align="center">No options defined</td>
		    								</tr>
		    							</tbody>
		    							<tbody id="role_data_form">
		    							
		    							</tbody>
		    						</table>
		    					</td>
		    					<td width="4" align="center" style="border-right:4px solid #cccccc;">
		    					&nbsp;
		    					</td>
		    					<td width="50%" style="padding-left:10px;padding-top:3px;padding-right:0px;">
		    						<div id="role_data_form_builder">
		    							{include file="inc/intable_header.tpl" no_first_row=1 header="Field" color="Gray"}
		    							<tr>
		    								<td colspan="2">
							    				<div style="padding-left:0px;">
							    					<div style="margin-top:0px;">
							    						<div style="float:left;line-height:25px;height:25px;width:150px;">Type:</div>
							    						<div style="float:left;line-height:25px;height:25px;">
							    							<select class="text" name="fieldtype" id="fieldtype" onChange='SetFieldForm()' style="vertical-align:middle;">
										    					<option value="text">Text</option>
										    					<option value="textarea">Textarea</option>
										    					<option value="select">List</option>
										    					<option value="checkbox">Boolean</option>
										    				</select>
							    						</div>
							    						<div style="clear:both;"></div>
							    					</div>
							    					<div style="margin-top:5px;">
							    						<div style="float:left;line-height:25px;height:25px;width:150px;">Name:</div>
							    						<div style="float:left;line-height:25px;height:25px;"><input style="vertical-align:middle;" type="text" id="fieldname" class="text" name="fieldname" value=""></div>
							    						<div style="clear:both;"></div>
							    					</div>
							    					<div style="margin-top:5px;">
							    						<div style="float:left;line-height:25px;height:25px;width:150px;">Required?</div>
							    						<div style="float:left;line-height:25px;height:25px;"><input style="vertical-align:middle;" type="checkbox" id="fieldrequired" name="fieldrequired" value="1"></div>
							    						<div style="clear:both;"></div>
							    					</div>
							    					<div id="text_properties" style="display:none;">
							    						<div style="margin-top:5px;">
								    						<div style="float:left;line-height:25px;height:25px;width:150px;">Default value:</div>
								    						<div style="float:left;line-height:25px;height:25px;"><input style="vertical-align:middle;" type="text" class="text" name="fielddefval" value=""></div>
								    						<div style="clear:both;"></div>
								    					</div>	
							    					</div>
							    					<div id="checkbox_properties" style="display:none;">
							    						<!-- TODO: -->	
							    					</div>
							    					<div id="textarea_properties" style="display:none;">
							    						<div style="margin-top:5px;">
								    						<div style="float:left;line-height:25px;height:25px;width:150px;">Default value:</div>
								    						<div style="float:left;margin-bottom:10px;"><textarea style="vertical-align:middle;" cols="50" rows="10" type="text" class="text" name="fielddefval" value=""></textarea></div>
								    						<div style="clear:both;"></div>
								    					</div>	
							    					</div>
							    					<div id="select_properties" style="display:none;">
							    						<div style="25px;margin-top:5px;">
								    						<div style="float:left;line-height:25px;height:25px;width:150px;">Allow multiple choice:</div>
								    						<div style="float:left;line-height:10px;">
								    							<input style="vertical-align:middle;margin-top:4px;" type="checkbox" onClick="AllowMultipleChoice(this.checked)" name="allow_multiplechoise" id="allow_multiplechoise" value="1">
								    						</div>
								    						<div style="clear:both;"></div>
								    					</div>
								    				</div>
							    				</td>
							    			</tr>
							    			{include file="inc/intable_footer.tpl" color="Gray"}
					    					{include intableid='list_options' no_first_row=1 visible='none' file="inc/intable_header.tpl" header="Options" color="Gray"}
					    					<tr>
					    						<td colspan="2">
						    					<div style="">
						    						<div style="float:left;">
						    							<div style="padding:2px;">
													         <div id="item1" style="width:440px;padding-left:0px;">
												                 <div style="padding:2px;float:left;width:150px;"><b>Value</b></div>
												                 <div style="padding:2px;float:left;width:150px;"><b>Name</b></div>
												                 <div style="padding:2px;float:left;width:75px;" align="center"><b>Default</b></div>
												                 <div style="padding:2px;float:left;width:auto;" align="center"><b>Delete</b></div>
													         </div>
													         <div id="Items" style="margin-left:0px;width:360px;">
													             <div id="no_items" align="center" style="display:;">No items defined</div>
													         </div>
													     </div>
													     <div style="clear:both;"></div>
													     <div style="padding:2px;width:440px;">
													         <div style="padding:2px;float:left;width:150px;"><input style="width:100px;" type="text" class="text" id="ikey" value=""></div>
													         <div style="padding:2px;float:left;width:150px;"><input style="width:100px;" type="text" class="text" id="iname" value=""></div>
													         <div style="padding:2px;float:left;width:75px;" align="center">
													         	<input type="checkbox" name="idef_add" id="idef_add" value="1">
													         </div>
													         <div style="padding:2px;float:left;width:auto;" align="center"><input onclick="AddItem();" type="button" class="btn" id="iname" value="Add"></div>
													     </div>
						    						</div>
						    					</div>	
						    					</td>
							    			</tr>
							    			{include file="inc/intable_footer.tpl" color="Gray"}
					    					<div id="field_buttons_add" style="clear:both;height:50px;padding-left:16px;">
					    						<br />
					    						<input type="Button" name="setfield" onClick="window.df_action = 'create'; SetField();" value="Add" class="btn">
					    						<br />
					    					</div>
					    					<div id="field_buttons_edit" style="clear:both;height:50px;padding-left:16px;display:none;">
					    						<br />
					    						<input type="Button" name="setfield" onClick="window.df_action = 'edit'; SetField();" value="Edit" class="btn">
					    						&nbsp;
					    						<input type="Button" name="setfield" onClick="ResetFieldForm();" value="Cancel" class="btn">
					    						<br />
					    					</div>
					    				</div>
					    			</div>
		    					</td>
		    				</tr>
		    			</table>
		    		</td>
		    	</tr>
				</table>
				</td>
				<td width="7" class="TableHeaderCenter_Gray"></td>
			</tr>
			</table>
		</div>
	{include file="inc/table_footer.tpl" button_js=1 button_js_action="return PrepareSubmit();" button_js_name="Save" show_js_button=1}
{include file="inc/footer.tpl"}