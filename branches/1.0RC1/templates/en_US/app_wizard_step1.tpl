{include file="inc/header.tpl"}
{include file="inc/table_header.tpl"}
    {include file="inc/intable_header.tpl" header="Step 1 - Application information" color="Gray"}
	<tr>
		<td width="15%">Domain name:</td>
		<td colspan="6"><input type="text" class="text" name="domainname" value="{$domainname}" /> Use <b>ns1.scalr.net</b>, <b>ns2.scalr.net</b> and <b>ns3.scalr.net</b> as nameservers for this domain.</td>
	</tr>
{include file="inc/intable_footer.tpl" color="Gray"}
<input type="hidden" name="step" value="2">
{include file="inc/table_footer.tpl" button2=1 button2_name='Next'}
{include file="inc/footer.tpl"}