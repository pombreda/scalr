{include file="inc/login_header.tpl"}
	<center>
	<div class="middle">	
	
		<table border="0" cellpadding="0" cellspacing="0" class="Webta_Table">
		<tr>
			<td width="7"><div class="TableHeaderLeft"></div></td>
			<td><div class="TableHeaderCenter"></div></td>
			<td width="7"><div class="TableHeaderRight"></div></td>
		</tr>
		<tr>
			<td width="7" class="TableHeaderCenter"></td>
			<td align="center"><div id="loginform">
      
      <div id="login_box">
        <h3>Password recovery</h3>
        {if $err.0 != ''}
			<span style="color:red;font-weight:bold;">{$err.0}</span>
			<br>
			<br>
		{/if}
          <label>E-mail</label>
          <input name="email" type="text" class="text" id="email" value="" size="15" />
          <input type="hidden" name="action" value="pwdrecovery" />
          <button style="vertical-align:middle;"  type="submit" class="btn" name="Submit2">
            Submit
          </button>
      </div>
    </td>
			<td width="7" class="TableHeaderCenter"></td>
		</tr>
		<tr>
			<td width="7"><div class="TableFooterLeft"></div></td>
			<td><div class="TableFooterCenter"></div></td>
			<td width="7"><div class="TableFooterRight"></div></td>
		</tr>
		</table>
	</div>
	</center>
{include file="inc/login_footer.tpl"}