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
		{include file="inc/intable_header.tpl" header="Customer gateway" color="Gray"}
		
    	<tr>
    		<td colspan="2">
    		  <table cellpadding="5" cellspacing="15" width="100%" border="0" >    		      
    		      <tr>
    		      	  <td style="font-weight:bold; width:30%;">Static IP address</td>
    		      	  <td><input type="text" class="text" style="width:180px;" id="ipAddress" name="ipAddress" value="">(e.g. 12.1.2.3)</td>
    		      </tr>   
    		      <tr>
    		      	  <td style="font-weight:bold; width:30%;">Autonomous System Number (BGP ASN)</td>
    		      	  <td style="font-style:italic;vertical-align:text-top;font-size:9pt; ">
    		      			<input type="text" class="text" style="width:180px;" id="bgpAsn" name="bgpAsn" value=""> 
    		      			You can use a private ASN (in the 64512 - 65534 range)
    		      	  </td>
    		      	  
    		      </tr>  		      
    		      <tr>
    					<td style="font-weight:bold;">Type</td>    	
    					<td> 
    						<select class="text" style="width:180px;"  name="type" id="type">    							
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