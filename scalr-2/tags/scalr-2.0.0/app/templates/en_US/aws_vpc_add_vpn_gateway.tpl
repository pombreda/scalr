{include file="inc/header.tpl"}
    <script language="Javascript">
    {literal}
        
    function SaveParams()
    {    	 	
		var elems = Ext.get('footer_button_table').select('.btn');
		elems.each(function(item){
			item.disabled = true;
		});
		
		Ext.get('btn_hidden_field').dom.name = this.name;
		Ext.get('btn_hidden_field').dom.value = this.value;
		
		document.forms[1].submit();
    }
    
    {/literal}
    </script>
{include file="inc/table_header.tpl"}
		{include file="inc/intable_header.tpl" header="VPN gateway " color="Gray"}
		
    	<tr>
    		<td colspan="2">
    		  <table cellspacing="5" width="100%" border="0" >    		      
    		      <tr>
    		      	  <td style="font-weight:bold; width:20%;">Availability zone</td>    	
    					<td> 
    						<select style="width:180px;" id="aws_availability_zone" name="aws_availability_zone" class="role_settings text">
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
					<td style="font-weight:bold;">Type</td>    	
					<td> 
						<select  style="width:180px;margin:5px;" name="type" id="type">    							
							<option value="ipsec.1" selected>ipsec.1</option>    							
						</select>
					</td>
    		      </tr>    		     
    		  </table>
       		</td>
    	</tr>
    	
    	
		{include file="inc/intable_footer.tpl" color="Gray"} 
	{include file="inc/table_footer.tpl" button_js=1 button_js_name="Create gateway" show_js_button=1 button_js_action="SaveParams();"}	
{include file="inc/footer.tpl"}