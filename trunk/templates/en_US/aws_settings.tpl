{include file="inc/header.tpl" upload_files=1}
	{include file="inc/table_header.tpl"}
        {include file="inc/intable_header.tpl" header="AWS settings" color="Gray"}
        <tr>
    		<td width="20%">AWS Account ID:</td>
    		<td><input type="text" class="text" name="aws_accountid" value="{$aws_accountid}" /></td>
    	</tr>
    	<tr>
    		<td width="20%">Access Key ID:</td>
    		<td><input type="text" class="text" name="aws_accesskeyid" value="{$aws_accesskeyid}" /></td>
    	</tr>
    	<tr>
    		<td width="20%">Secret Access Key:</td>
    		<td><input type="password" class="text" name="aws_accesskey" value="{if $aws_accesskey}******{/if}" /></td>
    	</tr>
    	<tr>
    		<td width="20%">X.509 Certificate file:</td>
    		<td><input type="file" class="text" name="cert_file" /></td>
    	</tr>
    	<tr>
    		<td width="20%">X.509 Private Key file:</td>
    		<td><input type="file" class="text" name="pk_file" /></td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
	{include file="inc/table_footer.tpl" edit_page=1}
{include file="inc/footer.tpl"}