
	</td></tr></table>

</td>
		</tr>
		{if !$disable_footer_line}
		<tr class="th" height="0">
			<td colspan="{if $colspan}{$colspan}{else}14{/if}"><table id="footer_button_table" border="0" width="100%" class="WebtaTable_Footer">
					<tr>
						<td  colspan="4" align="left">
												
						{if $prev_page}
							<input type="submit" class="btn" value="Prev" name="back">&nbsp;
						{/if}

						{if $edit_page}
							<input style="vertical-align:middle;" name="Submit" type="submit" class="btn" value="Save">
							<input name="id" type="hidden" id="id" value="{$id}">
						{elseif $search_page}
							<input type="submit" class="btn" value="Search">
						{elseif $page_data_options_add}
							<a href="{$smarty.server.PHP_SELF|replace:"view":"add"}{$page_data_options_add_querystring}">{if $page_data_options_add_text}{$page_data_options_add_text}{else}Add new{/if}</a>
						{/if}
						{if $next_page}
								<input type="submit" style="margin-left:6px;" class="btn" name="next" value="Next" />	
						{/if}
						{if $button_js}
								<input id="button_js" style="margin-left:6px;display:{if !$show_js_button}none{/if};" type="button" onclick="{$button_js_action}" class="btn" name="cbtn_2" value="{$button_js_name}" />
						{/if}
						{if $button2}
								<input type="submit" style="margin-left:6px;" class="btn" id="cbtn_2" name="cbtn_2" value="{$button2_name}" />	
						{/if}
						{if $button3}
								<input type="submit" style="margin-left:6px;" class="btn" id="cbtn_3" name="cbtn_3" value="{$button3_name}" />	
						{/if}
						{if $cancel_btn}
							<input type="submit" class="btn" name="cancel" value="Cancel" />&nbsp;
						{/if}
						{if $retry_btn}
								<input type="button" style="margin-left:6px;" class="btn" name="retrybtn" value="Retry" onclick="window.location=get_url;return false;" />	
						{/if}
                        {if $backbtn}
								<input type="submit" style="margin-left:6px;" class="btn" name="cbtn_3" value="Back" onclick="history.back();return false;" />	
						{/if}
						{if $loader}
						    <span style="display:none;" id="btn_loader">
                                <img style="vertical-align:middle;" src="images/snake-loader.gif"> {$loader}
                            </span>
						{/if}
						&nbsp;
						</td>
						<td width="10%" align="right" nowrap>
							<input name="page" type="hidden" id="page" value="{$page}">
							<input name="f" type="hidden" id="f" value="{$f}">
							{if $page_data_options && $page_data_options|@count > 0}
								Selected:
								<select name="action" class="text" style="vertical-align:middle;">
									{section name=id loop=$page_data_options}
								     <option value="{$page_data_options[id].action}">{$page_data_options[id].name}</option> 
								    {/section}
								</select>
								<input type="submit" name="actionsubmit" style="vertical-align:middle;" value="Apply" class="btn">
							{/if}
						
						</td>
						<td width="1" align="left">&nbsp;</td>
					</tr>
					{if $extra_html}
					<tr>
						<td colspan="10">
							{$extra_html}
						</td>
					</tr>
					{/if}
			</table></td>
		</tr>
		<input type="hidden" id="btn_hidden_field" name="" value="">
		{literal}
		<script language="Javascript">
			var footer_button_table = $('footer_button_table');
			var elems = footer_button_table.select('[class="btn"]');
			elems.each(function(item){
				if (item.id != 'button_js')
				{    
					item.onclick = function()
					{
						var footer_button_table = $('footer_button_table');
						var elems = footer_button_table.select('[class="btn"]');
						elems.each(function(item){
							item.disabled = true;
						});
						
						$('btn_hidden_field').name = this.name;
						$('btn_hidden_field').value = this.value;
						
						document.forms[1].submit();
						
						return false;
					}
				}
			});
		</script>
		{/literal}
		{/if}
		</table></td>
	<td width="7" class="TableHeaderCenter"></td>
</tr>
<tr>
	<td width="7"><div class="TableFooterLeft"></div></td>
	<td><div class="TableFooterCenter"></div></td>
	<td width="7"><div class="TableFooterRight"></div></td>
</tr>
</table>
	