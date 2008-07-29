{include file="inc/header.tpl"}
	{include file="inc/table_header.tpl"}
	        {include file="inc/intable_header.tpl" header="Daily graph (5 minutes average)" color="Gray"}
	    	<tr>
	    		<td colspan="2" align="center">
					<img src="{$images.daily}" />
	    		</td>
	    	</tr>
	    	{include file="inc/intable_footer.tpl" color="Gray"}
	    	
	    	{include file="inc/intable_header.tpl" header="Weekly graph (30 minutes average)" color="Gray"}
	    	<tr>
	    		<td colspan="2" align="center">
					<img src="{$images.weekly}" />
	    		</td>
	    	</tr>
	    	{include file="inc/intable_footer.tpl" color="Gray"}
	    	
	    	{include file="inc/intable_header.tpl" header="Monthly graph (2 hours average)" color="Gray"}
	    	<tr>
	    		<td colspan="2" align="center">
					<img src="{$images.monthly}" />
	    		</td>
	    	</tr>
	    	{include file="inc/intable_footer.tpl" color="Gray"}
	    	
	    	{include file="inc/intable_header.tpl" header="Yearly graph (1 day average)" color="Gray"}
	    	<tr>
	    		<td colspan="2" align="center">
					<img src="{$images.yearly}" />
	    		</td>
	    	</tr>
	    	{include file="inc/intable_footer.tpl" color="Gray"}
	{include file="inc/table_footer.tpl" disable_footer_line=1}
{include file="inc/footer.tpl"}