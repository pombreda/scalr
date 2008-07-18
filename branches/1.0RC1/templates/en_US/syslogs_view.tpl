{include file="inc/header.tpl"}
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
    {include file="inc/table_header.tpl" filter=0 paging=""}
    {include file="inc/intable_header.tpl" header="Search" color="Gray"}
        <tr>
			<td nowrap="nowrap">Search string:</td>
			<td><input type="text" name="search" class="text" id="search" value="{$search}" size="20" /></td>
		</tr>
		<tr>
			<td nowrap="nowrap">Farm:</td>
			<td>
				<select name="farmid" class="text">
					<option value="">Any</option>
					{section name=id loop=$farms}
					<option {if $farmid == $farms[id].id}selected{/if} value="{$farms[id].id}">{$farms[id].name}</option>
					{/section}
				</select>
			</td>
		</tr>
		<tr valign="top">
			<td nowrap="nowrap">Severity:</td>
			<td>
				<div style="width:600px;">
				{foreach item=item key=key from=$severities}
				    <div style="float:left;word-wrap:pre;width:200px;"><input name="severity[]" style="vertical-align:middle;" type="checkbox" {if $checked_severities[$key]}checked{/if} value="{$key}"> {$item}</div>
				{/foreach}
				</div>
			</td>
		</tr>
		<tr>
			<td nowrap="nowrap">Date:</td>
			<td>
			<input name="dt" style="vertical-align:middle;" type="text" class="text" id="dt" value="{$dt}">
			<input name="reset" style="vertical-align:middle;" type="reset" class="btn" onclick="return showCalendar('dt', 'mm/dd/y');" value=" ... ">
			</td>
		</tr>
    {include file="inc/intable_footer.tpl" color="Gray"}
    {include file="inc/table_footer.tpl" colspan=9 button2=1 button2_name="Search"}
    <br>
   	{include file="inc/table_header.tpl" filter=0}
		<table class="Webta_Items" rules="groups" frame="box" width="100%" cellpadding="2" id="Webta_Items">
		<thead>
			<tr>
				<th>Date</th>
				<th>First log entry</th>
				<th>Warnings</th>
				<th>Errors</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
		{section name=id loop=$rows}
		<tr id='tr_{$smarty.section.id.iteration}' {if $rows[id].errors > 0}style="background-color:pink;"{/if}>
			<td class="Item" nowrap="nowrap" valign="top">{$rows[id].dtadded}</td>
			<td class="Item" valign="top">{$rows[id].action|truncate:102:"...":true}</td>
			<td class="Item" valign="top">{$rows[id].warns}</td>
			<td class="Item" valign="top">{$rows[id].errors}</td>
			<td class="ItemEdit" nowrap valign="top"><a href="syslog_transaction_details.php?trnid={$rows[id].transactionid}">View log entries</a></td>
		</tr>
		{sectionelse}
		<tr>
			<td colspan="6" align="center">{t}No log entries found{/t}</td>
		</tr>
		{/section}
		<tr>
			<td colspan="4" align="center">&nbsp;</td>
			<td class="ItemEdit" align="center">&nbsp;</td> 
		</tr>
		</tbody>
		</table>
	{include file="inc/table_footer.tpl" colspan=9 disable_footer_line=1}
	<br>
{include file="inc/footer.tpl"}