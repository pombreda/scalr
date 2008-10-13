{include file="inc/header.tpl" upload_files=1}
{include file="inc/table_header.tpl"}
	{literal}
	<script language="Javascript">
		function ShowSSLOption(obj)
		{
			if ($('ssl_keyname'))
				$('ssl_keyname').style.display = 'none';
			
			if (obj.checked)
			{
				$('ssl_options').style.display = "";
			}
			else
			{
				$('ssl_options').style.display = "none";
			}
		}
	</script>
	{/literal}
    {include file="inc/intable_header.tpl" header="Apache virtual host settings" color="Gray"}
    <tr>
		<td width="20%">Virtual host name:</td>
		<td colspan="6">{$vhost.name}</td>
	</tr>
	<tr>
		<td colspan="2">&nbsp;</td>
	</tr>
	<tr>
		<td width="20%">Document root:</td>
		<td colspan="6"><input type="text" class="text" size="50" name="document_root_dir" value="{$vhost.document_root_dir}"></td>
	</tr>
	<tr>
		<td width="20%">Logs directory:</td>
		<td colspan="6"><input type="text" class="text" size="50" name="logs_dir" value="{$vhost.logs_dir}"></td>
	</tr>
	<tr>
		<td width="20%">Server admin email:</td>
		<td colspan="6"><input type="text" class="text" size="25" name="server_admin" value="{$vhost.server_admin}"></td>
	</tr>
	<tr>
		<td width="20%">Server alias (space separated):</td>
		<td colspan="6"><input type="text" class="text" size="25" name="aliases" value="{$vhost.aliases}"> (Exclude: {$vhost.name}, www.{$vhost.name})</td>
	</tr>
	{if $can_use_ssl}
	<tr>
		<td width="20%">Enable SSL:</td>
		<td colspan="6"><input onclick="ShowSSLOption(this)" type="checkbox" {if $vhost.issslenabled ==1}checked{/if} id="issslenabled" name="issslenabled" value="1"></td>
	</tr>
	{if $cert_name}
	<tbody id="ssl_keyname" style="display:;">
	<tr>
		<td width="20%">Certificate:</td>
		<td>{$cert_name}&nbsp;&nbsp;[<a href="javascript:ShowSSLOption($('issslenabled'));">Upload new certificate & private key</a>]</td>
	</tr>
	</tbody>
	{/if}
	<tbody id="ssl_options" style="display:none;">
	<tr>
		<td width="20%">Certificate:</td>
		<td colspan="6"><input type="file" class="text" name="ssl_cert"></td>
	</tr>
	<tr>
		<td width="20%">Private key:</td>
		<td colspan="6"><input type="file" class="text" name="ssl_pk"></td>
	</tr>
	</tbody>
	{/if}
    {include file="inc/intable_footer.tpl" color="Gray"}
    <input type="hidden" name="name" value="{$vhost.name}">
    <input type="hidden" name="vhost_page" value="1">
{include file="inc/table_footer.tpl" button2=1 button2_name=$button2_name}
<script language="Javascript">
	{if !$cert_name}
		ShowSSLOption($('issslenabled'));
	{/if}
</script>
{include file="inc/footer.tpl"}