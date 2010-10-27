{include file="inc/header.tpl" upload_files=1}
	{include file="inc/table_header.tpl"}
		{include file="inc/intable_header.tpl" header="General information" color="Gray"}
        <tr>
    		<td width="20%">Transaction ID:</td>
    		<td>{$entry.transaction_id}</td>
    	</tr>
    	<tr>
    		<td width="20%">Action:</td>
    		<td>{$entry.action}</td>
    	</tr>
    	<tr>
    		<td width="20%">IP address:</td>
    		<td>{$entry.ipaddress}</td>
    	</tr>
    	<tr>
    		<td width="20%">Time:</td>
    		<td>{$entry.dtadded}</td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
        
		{include file="inc/intable_header.tpl" header="Request" color="Gray"}
    	<tr>
    		<td colspan="2">{$entry.request}</td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
        
        {include file="inc/intable_header.tpl" header="Response" color="Gray"}
    	<tr>
    		<td colspan="2"><code>{$entry.response|htmlspecialchars}</code></td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
	{include file="inc/table_footer.tpl" disable_footer_line=1}
{include file="inc/footer.tpl"}