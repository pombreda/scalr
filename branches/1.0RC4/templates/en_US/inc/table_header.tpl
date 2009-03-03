{if !$nofilter}
<table border="0" width="100%" cellspacing="0" cellpadding="0" height="40">
	<tr>
		<td align="center" nowrap width="10">&nbsp;</td>
		<td width="310" align="left" valign="bottom">{if $filter}{$filter}{/if}</td>
		<td colspan="4" align="left" valign="bottom">{$paging}</td>
		<td align="center" nowrap>&nbsp;</td>
	</tr>
</table>
{/if}

{if $show_reload_icon}
<table border="0" width="100%" cellspacing="0" cellpadding="0" height="40">
	<tr>
		<td align="center" nowrap width="10">&nbsp;</td>
		<td width="310" align="left" valign="bottom"></td>
		<td colspan="4" align="left" valign="bottom">
			<div class="paging">
					<div id="table_refresh_icon" class="tabActive"><img style="cursor:pointer;" onclick="{$reload_action}" src="/images/refresh_tiny.png"></div>
			</div>
		</td>
		<td align="center" nowrap width="20">&nbsp;</td>
	</tr>
</table>
{/if}

{if $tabs}
{literal}
<script language="Javascript">
	function SetActiveTab(id, itable_tabs)
	{
		//
		// Unselect current active tab
		//
		var container = $('tabs_container');
		
		var elems = container.select('[class="TableHeaderLeft"]');
		elems.each(function(item){    
			item.className = 'TableHeaderLeft_LGray';
		});
	
		var elems = container.select('[class="TableHeaderCenter"]');
		elems.each(function(item){    
			item.className = 'TableHeaderCenter_LGray';
		});
		
		var elems = container.select('[class="TableHeaderRight"]');
		elems.each(function(item){    
			item.className = 'TableHeaderRight_LGray';
		});
		
		var elems = container.select('[class="TableHeaderContent"]');
		elems.each(function(item){    
			item.bgColor = '#f4f4f4';
			item.className = 'TableHeaderContent_LGray';
		});
		
		//
		// Select active tab
		//
		var ctab = $('tab_'+id)
		
		var elems = ctab.select('[class="TableHeaderLeft_LGray"]');
		elems.each(function(item){    
			item.className = 'TableHeaderLeft';
		});
		
		var elems = ctab.select('[class="TableHeaderCenter_LGray"]');
		elems.each(function(item){    
			item.className = 'TableHeaderCenter';
		});
		
		var elems = ctab.select('[class="TableHeaderRight_LGray"]');
		elems.each(function(item){    
			item.className = 'TableHeaderRight';
		});
		
		var elems = ctab.select('[class="TableHeaderContent_LGray"]');
		elems.each(function(item){    
			item.bgColor = '#C3D9FF';
			item.className = 'TableHeaderContent';
		});
		
		
		var elems = $$('div.tab_contents');
		elems.each(function(item){    
			item.style.display = "none";
		});
		
		if ($('tab_contents_'+id))
			$('tab_contents_'+id).style.display = "";
			
		try
		{
			OnTabChanged(id);
		}
		catch(e){}
	}
</script>
{/literal}
<table border="0" width="100%" cellspacing="0" cellpadding="0">
	<tr>
		<td align="center" nowrap width="10">&nbsp;</td>
		<td align="left" valign="bottom" id="tabs_container">
			{foreach from=$tabs_list key=id item=tab_name}
			{if $selected_tab == $id}
				{assign var="is_active_tab" value="1"}
			{else}
				{assign var="is_active_tab" value="0"}
			{/if}
		  	<div class="table_tab" id="tab_{$id}" onClick="SetActiveTab('{$id}');">
            	<table border="0" cellpadding="0" cellspacing="0" width="120">
            		<tr>
            			<td width="7"><div class="TableHeaderLeft{if !$is_active_tab}_LGray{/if}"></div></td>
            			<td><div class="TableHeaderCenter{if !$is_active_tab}_LGray{/if}"></div></td>
            			<td><div class="TableHeaderCenter{if !$is_active_tab}_LGray{/if}"></div></td>
            			<td width="7"><div class="TableHeaderRight{if !$is_active_tab}_LGray{/if}"></div></td>
            		</tr>
            		<tr id="tab_content_{$id}" class="TableHeaderContent{if !$is_active_tab}_LGray{/if}" bgcolor="{if $is_active_tab}#C3D9FF{else}#f4f4f4{/if}">
            			<td width="7" class="TableHeaderCenter{if !$is_active_tab}_LGray{/if}"></td>
            			<td id="tab_name_{$id}" nowrap style="padding-bottom:5px;" align="center">
							{$tab_name}
            			</td>
            			<td align="left" nowrap></td>
            			<td width="7" class="TableHeaderCenter{if !$is_active_tab}_LGray{/if}"></td>
            		</tr>
            	</table>
            </div>
            {/foreach}
            <div style="clear:both;"></div>
		</td>
		<td colspan="4" align="left" valign="bottom"></td>
		<td align="center" nowrap>&nbsp;</td>
	</tr>
</table>
{/if}

{if $table_header_text}
<table border="0" width="100%" cellspacing="0" cellpadding="0" height="40">
	<tr>
		<td align="center" nowrap width="10">&nbsp;</td>
		<td width="310" align="left" valign="bottom">
		  <div>
            	<table border="0" cellpadding="0" cellspacing="0">
            		<tr>
            			<td width="7"><div class="TableHeaderLeft"></div></td>
            			<td><div class="TableHeaderCenter"></div></td>
            			<td><div class="TableHeaderCenter"></div></td>
            			<td width="7"><div class="TableHeaderRight"></div></td>
            		</tr>
            		<tr bgcolor="#C3D9FF">
            			<td width="7" class="TableHeaderCenter"></td>
            			<td nowrap style="padding-bottom:5px;">
            			 {$table_header_text}
            			</td>
            			<td align="left" nowrap></td>
            			<td width="7" class="TableHeaderCenter"></td>
            		</tr>
            	</table>
            </div>
		</td>
		<td colspan="4" align="left" valign="bottom"></td>
		<td align="center" nowrap>&nbsp;</td>
	</tr>
</table>
{/if}

<table border="0" cellpadding="0" cellspacing="0" class="Webta_Table" width="100%">
<tr>
	<td width="7"><div class="TableHeaderLeft"></div></td>
	<td><div class="TableHeaderCenter"></div></td>
	<td width="7"><div class="TableHeaderRight"></div></td>
</tr>
<tr>
	<td width="7" class="TableHeaderCenter"></td>
	<td><table width="100%" cellspacing="0" cellpadding="0">
	<tr>
		<td>
		
		<table id="Webta_Settings" width="100%" cellpadding="0" cellspacing="0">
		<tr><td valign="top">
