{include file="inc/header.tpl"}
        {if !$nofilter}        
<table border="0" width="100%" cellspacing="0" cellpadding="0" height="40">
	<tr>
		<td align="center" nowrap width="10">&nbsp;</td>
		<td width="310" align="left" valign="bottom">
		<div>
        	<table border="0" cellpadding="0" cellspacing="0">
        		<tr>
        			<td width="7"><div class="TableHeaderLeft"></div></td>
        			<td><div class="TableHeaderCenter"></div></td>
        			<td><div class="TableHeaderCenter"></div></td>
        			<td width="7"><div class="TableHeaderRight"></div></td>
        		</tr>
        		<tr>
        			<td width="7" class="TableHeaderCenter"></td>
        			<td nowrap>
        				&nbsp;
        				<input name="filter_q" type="text" class="text" id="filter_q" value="{$filter_q}">
        				<select name="severity" class="text" style="vertical-align:middle;">
        				    <option {if $severity == $key}selected{/if} value="">All</option>
        				{foreach item=item key=key from=$severities}
        				    <option {if $severity == $key}selected{/if} value="{$key}">{$item}</option>
        				{/foreach}
        				</select>
        			</td>
        			<td align="left" nowrap>
        					&nbsp;
        					<input name="Submit" type="submit" class="btn{if $filter_q}i{else}{/if}" value="Filter">
        					<input name="act" type="hidden" id="act" value="filter1">
        					&nbsp;
        			</td>
        			<td width="7" class="TableHeaderCenter"></td>
        		</tr>
        	</table>
        </div>
        </td>
		<td colspan="4" align="left" valign="bottom">{$paging}</td>
		<td align="center" nowrap>&nbsp;</td>
	</tr>
</table>
{/if}
<table border="0" cellpadding="0" cellspacing="0" class="Webta_Table" width="100%">
<tr>
	<td width="7"><div class="TableHeaderLeft"></div></td>
	<td><div class="TableHeaderCenter"></div></td>
	<td width="7"><div class="TableHeaderRight"></div></td>
</tr>
<tr>
	<td width="7" class="TableHeaderCenter"></td>
	<td><table width="100%" cellspacing="0" cellpadding="0">
	<tr>
		<td>
		
		<table id="Webta_Settings" width="100%" cellpadding="0" cellspacing="0">
		<tr><td valign="top">
    <table class="Webta_Items" rules="groups" frame="box" cellpadding="4" width="100%" id="Webta_Items">
	<thead>
		<tr>
			<th width="120">Severity</th>
			<th width="150">Time</th>
			<th>Message</th>
		</tr>
	</thead>
	<tbody>
	{section name=id loop=$rows}
	<tr id='tr_{$smarty.section.id.iteration}'>
	
		<td class="Item" valign="top" nowrap>{$rows[id].severity}</td>
		<td class="Item" valign="top" nowrap>{$rows[id].dtadded}</td>
		<td class="Item" valign="top">{$rows[id].message|nl2br|truncate:200:"...":true}</td>
	</tr>
	{sectionelse}
	<tr>
		<td colspan="5" align="center">No log entries found</td>
	</tr>
	{/section}
	<tr>
		<td colspan="5" align="center">&nbsp;</td>
		<!--<td class="ItemDelete" valign="top">&nbsp;</td>-->
	</tr>
	</tbody>
	</table>
	{include file="inc/table_footer.tpl" colspan=9 disable_footer_line=1}	
		{include file="inc/footer.tpl"}