{include file="inc/header.tpl"}
{include file="inc/table_header.tpl"}
    {include file="inc/intable_header.tpl" header="Assign a domain name to your Application (Step 1 of 3)" color="Gray"}
	<tr>
		<td colspan="7">
			<p>Set nameservers to <b>ns1.scalr.net</b>, <b>ns2.scalr.net</b>, <b>ns3.scalr.net</b>, and <b>ns4.scalr.net</b> for the domain through your Registrar.
			</p>
		</td>
	</tr>
	<tr>
		<td width="15%">Website Domain name</td>
		<td colspan="6"><input type="text" class="text" name="domainname" value="{$domainname}" /></td>
	</tr>
	<tr>
		<td width="15%">Location:</td>
		<td colspan="6">
			<select name="region" id="region" style="vertical-align:middle;">
				{foreach from=$regions name=id key=key item=item}
					<option {if $region == $key}selected{/if} value="{$key}">{$item}</option>
				{/foreach}
			</select>
			
		</td>
	</tr>
{include file="inc/intable_footer.tpl" color="Gray"}
<input type="hidden" name="step" value="2">
{include file="inc/table_footer.tpl" button2=1 button2_name='Next'}
{include file="inc/footer.tpl"}