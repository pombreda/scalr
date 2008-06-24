{include file="inc/header.tpl" upload_files=1}
	{include file="inc/table_header.tpl"}
		{include file="inc/intable_header.tpl" header="Account information" color="Gray"}
    	<tr>
    		<td width="20%">E-mail:</td>
    		<td><input type="text" class="text" name="email" value="{$email}" /></td>
    	</tr>
    	<tr>
    		<td width="20%">Password:</td>
    		<td><input type="password" class="text" name="password" value="{if $password}******{/if}" /></td>
    	</tr>
    	<tr>
    		<td width="20%">Confirm password:</td>
    		<td><input type="password" class="text" name="password2" value="{if $password}******{/if}" /></td>
    	</tr>
    	<tr>
    		<td width="20%">Farms limit:</td>
    		<td><input type="text" class="text" name="farms_limit" value="{if $farms_limit}{$farms_limit}{else}1{/if}" size="5" /></td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
        
        {include file="inc/intable_header.tpl" header="AWS information" color="Gray"}
        <tr>
    		<td width="20%">Account ID:</td>
    		<td><input type="text" class="text" name="aws_accountid" value="{$aws_accountid}" /></td>
    	</tr>
    	<tr>
    		<td width="20%">Access key id:</td>
    		<td><input type="text" class="text" name="aws_accesskeyid" value="{$aws_accesskeyid}" /></td>
    	</tr>
    	<tr>
    		<td width="20%">Access key:</td>
    		<td><input type="text" class="text" name="aws_accesskey" value="{$aws_accesskey}" /></td>
    	</tr>
    	<tr>
    		<td width="20%">Certificate file:</td>
    		<td><input type="file" class="text" name="cert_file" /></td>
    	</tr>
    	<tr>
    		<td width="20%">Private key file:</td>
    		<td><input type="file" class="text" name="pk_file" /></td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
	{include file="inc/table_footer.tpl" edit_page=1}
{include file="inc/footer.tpl"}