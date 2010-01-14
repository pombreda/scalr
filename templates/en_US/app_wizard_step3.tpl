{include file="inc/header.tpl"}
{include file="inc/table_header.tpl"}
    {include file="inc/intable_header.tpl" header="DNS Settings (Step 3 of 3)" color="Gray"}
	<tr valign="top">
		<td width="400" nowrap><p>Traffic to your domain will go to the following role</p></td>
		<td colspan="6">
		  <select name="dnsami" class="text">
		  {section name=id loop=$roles}
		      <option value="{$roles[id].ami_id}">{$roles[id].name}</option>
		  {/section}
		  </select>
		</td>
	</tr>
	<tr>
		<td colspan='7'>This role should preferably be a load balancer.</td>
	</tr>
{include file="inc/intable_footer.tpl" color="Gray"}
<input type="hidden" name="step" value="4">
{include file="inc/table_footer.tpl" colspan=9 button_js_name='Finish' button_js=1 button_js_action='SubmitForm();' loader='Building new farm... <span style="color:red;">Please do not close this page!</span>'}
<script language="Javascript">
                        	
$('button_js').style.display='';

{literal}
function SubmitForm()
{
   $('button_js').disabled = true;
   $('btn_loader').style.display = '';
   
   document.getElementById("frm").submit();   
}
{/literal}
</script>
{include file="inc/footer.tpl"}