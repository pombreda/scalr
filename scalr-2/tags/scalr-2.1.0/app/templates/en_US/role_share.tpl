{include file="inc/header.tpl"}
    <script language="Javascript" src="/js/class.DataFormField.js"></script>    
	{literal}
    <script language="Javascript" type="text/javascript">
    
    Ext.onReady(function()
	{   
		Ext.get('button_js').dom.disabled = true; 
		
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
		
	.valid_inputbox
	{
		background-color:#e0f5bd;
	}
		
	.invalid_inputbox
	{
		background-color:#f7c7c7;
	}
		
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
    		<td width="20%">{t}AMI{/t}:</td>
    		<td>{$ami_id}<input type="hidden" name="id" value="{$id}"></td>
    	</tr>
    	<tr>
    		<td width="20%">{t}Architecture{/t}:</td>
    		<td>{$arch}</td>
    	</tr>
		<tr>
    		<td width="20%">{t}Role name{/t}:</td>
    		<td><input type="text" name="name" id="role_name" value="{$name}" class="text"> <span id="name_check" style="display:none;"><img src="/images/snake-loader.gif" style="vertical-align:middle;"> Checking name availability...</span></td>
    	</tr>
    	<tr>
    		<td width="20%">{t}Type{/t}:</td>
    		<td>{$alias}</td>
    	</tr>
    	<tr valign="top">
    		<td width="20%">{t}Description{/t}:</td>
    		<td>
    			<textarea rows="5" cols="50" name="description" class="text">{$description}</textarea>
    		</td>
    	</tr>
    	<tr valign="top">
    		<td width="20%">{t}Comments{/t}:</td>
    		<td>
    			<p class="Webta_Ihelp" style="width:496px;">
					{t}This field is visible for Scalr team only.{/t}
				</p>
    			<textarea rows="5" cols="50" style="width:516px;" name="sharing_comments" class="text">{$sharing_comments}</textarea>
    		</td>
    	</tr>
    	<tr valign="top">
    		<td colspan="2">
    			&nbsp;    		
    		</td>
    	</tr>
    	<tr valign="top">
    		<td colspan="2">
    			<input onClick="CheckRequirements();" type="checkbox" id="no_keys_on_ami" name="no_keys_on_ami" value="1" style="vertical-align:middle;" />
    			{t}I did not  leave any sensible data (like passwords or private keys) on this image.{/t}
    		</td>
    	</tr>
    	{if !$is_role_public}
    	<tr valign="top">
    		<td colspan="2">
    			<input onClick="CheckRequirements();" type="checkbox" id="make_role_public" name="make_role_public" value="1" style="vertical-align:middle;" />
    			{t}I agree to make this AMI public. Any Amazons EC2 user will be abe to lauch it.{/t}
    		</td>
    	</tr>
    	{/if}
    	<script type="text/javascript">
    		{literal}
			function CheckRequirements()
			{
				r = !Ext.get('no_keys_on_ami').dom.checked;
				if (Ext.get('make_role_public'))
				{
					r = r | !Ext.get('make_role_public').dom.checked;
				}
				
				Ext.get('button_js').dom.disabled = r;
			}

			function CheckName()
			{
				Ext.get('name_check').dom.style.display = '';
				var elems = Ext.get('footer_button_table').select('.btn');
				elems.each(function(item){
					item.dom.disabled = true;
				});
				
				//Ext.get('Webta_ErrMsg').dom.style.display = 'none';
				
				Ext.Ajax.request({
		    		url:'/server/server.php?_cmd=check_role_name&name='+Ext.get('role_name').dom.value+"&ami_id={/literal}{$ami_id}{literal}",
		    		method:'GET',
		    		success:function(transport, options)
		    		{
						try
						{
							Ext.get('name_check').dom.style.display = 'none';
							var elems = Ext.get('footer_button_table').select('.btn');
							elems.each(function(item){
								item.dom.disabled = false;
							});
							CheckRequirements();
							
							if (transport.responseText == 'ok')
							{
								return PrepareSubmit();
							}
							else if (transport.responseText == 'REDIRECT')
							{
								document.location.href = '/roles_view.php';
							}
							else
							{
								Scalr.Viewers.ErrorMessage(transport.responseText);
								
								//TODO: new Effect.Pulsate(err_obj);
							}
						}
						catch(e)
						{
							alert(e.message);
						}
		    		}
		    	});
				
				return false;
			}

    		{/literal}
    	</script>
        {include file="inc/intable_footer.tpl" color="Gray"}

		<div id="tab_contents_options" class="tab_contents" style="padding:7px;display:none;">
		<p class="placeholder">
			{t}These options will appear in the Parameters tab on farm/role edit Page.{/t}
			<a target="_blank" href="http://code.google.com/p/scalr/wiki/RoleOptions">{t}How do I retrieve these values on my instances?{/t}</a>
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
												<div id="webta_table_header" style="line-height:20px;" class="SettingsHeader_Gray">{t}Field Name{/t}</div>
											</td>
											<td>
												<div id="webta_table_header" style="line-height:20px;" class="SettingsHeader_Gray">{t}Field type{/t}</div>
											</td>
											<td>
												<div id="webta_table_header" style="line-height:20px;" class="SettingsHeader_Gray">{t}Required{/t}</div>
											</td>
											<td>
												<div id="webta_table_header" style="line-height:20px;" class="SettingsHeader_Gray"></div>
											</td>
											<td width="7"><div class="TableHeaderRight_Gray"></div></td>
										</tr>
										<tbody id="no_fields">
		    								<tr>
		    									<td colspan="10" align="center">{t}No options defined{/t}</td>
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
							    						<div style="float:left;line-height:25px;height:25px;width:150px;">{t}Type{/t}:</div>
							    						<div style="float:left;line-height:25px;height:25px;">
							    							<select class="text" name="fieldtype" id="fieldtype" onChange='SetFieldForm()' style="vertical-align:middle;">
										    					<option value="text">{t}Text{/t}</option>
										    					<option value="textarea">{t}Textarea{/t}</option>
										    					<option value="select">{t}List{/t}</option>
										    					<option value="checkbox">{t}Boolean{/t}</option>
										    				</select>
							    						</div>
							    						<div style="clear:both;"></div>
							    					</div>
							    					<div style="margin-top:5px;">
							    						<div style="float:left;line-height:25px;height:25px;width:150px;">{t}Name{/t}:</div>
							    						<div style="float:left;line-height:25px;height:25px;"><input style="vertical-align:middle;" type="text" id="fieldname" class="text" name="fieldname" value=""></div>
							    						<div style="clear:both;"></div>
							    					</div>
							    					<div style="margin-top:5px;">
							    						<div style="float:left;line-height:25px;height:25px;width:150px;">{t}Required{/t}?</div>
							    						<div style="float:left;line-height:25px;height:25px;"><input style="vertical-align:middle;" type="checkbox" id="fieldrequired" name="fieldrequired" value="1"></div>
							    						<div style="clear:both;"></div>
							    					</div>
							    					<div id="text_properties" style="display:none;">
							    						<div style="margin-top:5px;">
								    						<div style="float:left;line-height:25px;height:25px;width:150px;">{t}Default value{/t}:</div>
								    						<div style="float:left;line-height:25px;height:25px;"><input style="vertical-align:middle;" type="text" class="text" name="fielddefval" value=""></div>
								    						<div style="clear:both;"></div>
								    					</div>	
							    					</div>
							    					<div id="checkbox_properties" style="display:none;">
							    						<!-- TODO: -->	
							    					</div>
							    					<div id="textarea_properties" style="display:none;">
							    						<div style="margin-top:5px;">
								    						<div style="float:left;line-height:25px;height:25px;width:150px;">{t}Default value{/t}:</div>
								    						<div style="float:left;margin-bottom:10px;"><textarea style="vertical-align:middle;" cols="50" rows="10" type="text" class="text" name="fielddefval" value=""></textarea></div>
								    						<div style="clear:both;"></div>
								    					</div>	
							    					</div>
							    					<div id="select_properties" style="display:none;">
							    						<div style="25px;margin-top:5px;">
								    						<div style="float:left;line-height:25px;height:25px;width:150px;">{t}Allow multiple choice{/t}:</div>
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
												                 <div style="padding:2px;float:left;width:150px;"><b>{t}Value{/t}</b></div>
												                 <div style="padding:2px;float:left;width:150px;"><b>{t}Name{/t}</b></div>
												                 <div style="padding:2px;float:left;width:75px;" align="center"><b>{t}Default{/t}</b></div>
												                 <div style="padding:2px;float:left;width:auto;" align="center"><b>{t}Delete{/t}</b></div>
													         </div>
													         <div id="Items" style="margin-left:0px;width:360px;">
													             <div id="no_items" align="center" style="display:;">{t}No items defined{/t}</div>
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
		<input type="hidden" name="task" value="share" />
	{include file="inc/table_footer.tpl" button_js=1 button_js_action="return CheckName();" button_js_name="Save changes &amp; Share role" show_js_button=1 cancel_btn=1}
{include file="inc/footer.tpl"}