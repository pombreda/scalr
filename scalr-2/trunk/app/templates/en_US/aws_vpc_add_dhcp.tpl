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
		{include file="inc/intable_header.tpl" header="DHCP options set" color="Gray"}
		
    	<tr>
    		<td colspan="2">
    		  <table cellpadding="5" cellspacing="15" width="100%" border="0" >    		      
    		      <tr>
    		      	  <td style="font-weight:bold; width:15%;">Domain name</td>
    		      	  <td style="font-style:italic;vertical-align:text-top;font-size:9pt; width:40%;">
    		      		<input type="text" class="text" style="width:100%;" id="domainName" name="domainName" value="">    		      		
    		      	  </td> 
    		      	  <td style="font-style:italic;vertical-align:text-top;font-size:9pt;">
    		      	  (e.g. example.com)
    		      	  </td>
    		      	   		          
    		      </tr>    		      
    		      <tr>
    					<td style="font-weight:bold;">Domain name servers</td>    	
    					<td style="font-style:italic;vertical-align:text-top;font-size:9pt; "> 
    						<input type="text" class="text" style="width:100%;" id="domainNameServers" name="domainNameServers" value="">    						
    					</td>
    					<td style="font-style:italic;vertical-align:text-top;font-size:9pt;">
    					Enter up to 4 DNS server IP addresses separated by commas
						</td>
    		      </tr>
    		        <tr>
    					<td style="font-weight:bold;">NTP servers</td>    	
    					<td style="font-style:italic;vertical-align:text-top;font-size:9pt;"> 
    						<input type="text" class="text" style="width:100%;" id="ntpServers" name="ntpServers" value="">
    					</td>
    					<td style="font-style:italic;vertical-align:text-top;font-size:9pt;">
    						Enter up to 4 DNS server IP addresses separated by commas
    					</td>
    		      </tr> 
    		        <tr>
    					<td style="font-weight:bold;">NetBIOS name servers</td>    	
    					<td style="font-style:italic;vertical-align:text-top;font-size:9pt; "> 
    						<input type="text" class="text" style="width:100%;" id="netBiosNameServers" name="netBiosNameServers" value="">
    					</td>
    					<td style="font-style:italic;vertical-align:text-top;font-size:9pt; ">
    						Enter up to 4 NetBIOS name server IP addresses separated by commas
    					</td>
    		      </tr>    		     
    		       <tr>
    					<td style="font-weight:bold;">NetBIOS node type</td>    	
    					<td style="font-style:italic;vertical-align:text-top;font-size:9pt;"> 
    						<input type="text" class="text" style="width:100%;" id="netBiosType" name="netBiosType" value="">
    					</td>
    					<td style="font-style:italic;vertical-align:text-top;font-size:9pt;">
    						Enter NetBIOS node type (1, 2, 4, or 8)
    					</td>
    		      </tr>   
    		  </table>
       		</td>
    	</tr>
    	
    	
		{include file="inc/intable_footer.tpl" color="Gray"} 
	{include file="inc/table_footer.tpl" button_js=1 button_js_name="Create options set" show_js_button=1 button_js_action="SaveParams();"}	
{include file="inc/footer.tpl"}