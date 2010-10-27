{include file="inc/header.tpl" upload_files=1}
	{literal}
	<link rel="stylesheet" type="text/css" media="all" href="/css/calendar.css"  />
	<script type="text/javascript" src="/js/calendar/calendar.js"></script>
	<script type="text/javascript" src="/js/calendar/calendar-en.js"></script>
	<script type="text/javascript">
	
	// This function gets called when the end-user clicks on some date.
	function selected(cal, date) {
	  cal.sel.value = date; // just update the date in the input field.
	  if (cal.sel.id == "sel1" || cal.sel.id == "sel3")
		// if we add this call we close the calendar on single-click.
		// just to exemplify both cases, we are using this only for the 1st
		// and the 3rd field, while 2nd and 4th will still require double-click.
		cal.callCloseHandler();
	}
	
	function closeHandler(cal) {
	  cal.hide();                        // hide the calendar
	}
	function showCalendar(id, format) {
	  var el = document.getElementById(id);
	  if (calendar != null) {
		// we already have some calendar created
		calendar.hide();                 // so we hide it first.
	  } else {
		// first-time call, create the calendar.
		var cal = new Calendar(false, null, selected, closeHandler);
		// uncomment the following line to hide the week numbers
		// cal.weekNumbers = false;
		calendar = cal;                  // remember it in the global var
		cal.setRange(1900, 2070);        // min/max year allowed.
		cal.create();
	  }
	  calendar.setDateFormat(format);    // set the specified date format
	  calendar.parseDate(el.value);      // try to parse the text in field
	  calendar.sel = el;                 // inform it what input field we use
	  calendar.showAtElement(el);        // show the calendar below it
	
	  return false;
	}
	
	var MINUTE = 60 * 1000;
	var HOUR = 60 * MINUTE;
	var DAY = 24 * HOUR;
	var WEEK = 7 * DAY;
	
	function isDisabled(date) {
	  var today = new Date();
	  return (Math.abs(date.getTime() - today.getTime()) / DAY) > 10;
	}
	</script>
	{/literal}
	{include file="inc/table_header.tpl"}
		{include file="inc/intable_header.tpl" header="Comments" color="Gray"}
        <tr>
    		<td colspan="2">
    			<textarea class="text" rows="10" cols="80" name='comments' id='comments'>{$comments}</textarea>
    		</td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
	
		{include file="inc/intable_header.tpl" header="Billing settings" color="Gray"}
        <tr>
    		<td width="20%">Bill for scalr use:</td>
    		<td><input type="checkbox" {if $isbilled == 1}checked{/if} name="isbilled" value="1" /></td>
    	</tr>
    	<tr>
    		<td width="20%">Due date:</td>
    		<td>
    			<input name="dtdue" style="vertical-align:middle;width:100px;" type="text" class="text" id="dtdue" value="{$dtdue}">
				<input name="reset" style="margin-left:-7px;height:22px;vertical-align:middle;" type="reset" class="btn" onclick="return showCalendar('dtdue', 'y-mm-dd');" value=" ... ">
    		</td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
                
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
    		<td><input type="text" class="text" name="farms_limit" value="{if $farms_limit}{$farms_limit}{else}0{/if}" size="5" /> (0 for unlimited)</td>
    	</tr>
    	<tr>
    		<td width="20%">Full name:</td>
    		<td><input type="text" class="text" name="name" value="{$fullname}" /></td>
    	</tr>
    	<tr>
    		<td width="20%">Organization:</td>
    		<td><input type="text" class="text" name="org" value="{$org}" /></td>
    	</tr>
    	<tr>
    		<td width="20%">Country:</td>
    		<td>
    			<select  id="country" name="country" class="text">
				{section name="id" loop=$countries}
					<option value="{$countries[id].code}" {if $country|strtolower == $countries[id].code|strtolower || (!$country|strtolower && $countries[id].code|strtolower == 'us')}selected{/if}>{$countries[id].name}</option>
				{/section}
				</select>
    		</td>
    	</tr>
    	<tr>
    		<td width="20%">State / Region:</td>
    		<td><input type="text" class="text" name="state" value="{$state}" /></td>
    	</tr>
    	<tr>
    		<td width="20%">City:</td>
    		<td><input type="text" class="text" name="city" value="{$city}" /></td>
    	</tr>
    	<tr>
    		<td width="20%">Postal code:</td>
    		<td><input type="text" class="text" name="zipcode" value="{$zipcode}" /></td>
    	</tr>
    	<tr>
    		<td width="20%">Address 1:</td>
    		<td><input type="text" class="text" name="address1" value="{$address1}" /></td>
    	</tr>
    	<tr>
    		<td width="20%">Address 2:</td>
    		<td><input type="text" class="text" name="address2" value="{$address2}" /></td>
    	</tr>
    	<tr>
    		<td width="20%">Phone:</td>
    		<td><input type="text" class="text" name="phone" value="{$phone}" /></td>
    	</tr>
    	<tr>
    		<td width="20%">Fax:</td>
    		<td><input type="text" class="text" name="fax" value="{$fax}" /></td>
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