<div style="float:left;">
	<table border="0" cellpadding="0" cellspacing="0" style="height:26px;">
		<tr>
			<td width="7"><div class="TableHeaderLeft"></div></td>
			<td><div class="TableHeaderCenter"></div></td>
			<td><div class="TableHeaderCenter"></div></td>
			<td width="7"><div class="TableHeaderRight"></div></td>
		</tr>
		<tr bgcolor="#C3D9FF">
			<td width="7" class="TableHeaderCenter"></td>
			<td nowrap style="height:26px;">
				<input style="margin-left:5px;" name="filter_q" type="text" class="text" id="filter_q" value="{$filter_q}">
			</td>
			<td align="left" nowrap style="height:26px;">
					&nbsp;
					<input name="Submit" type="submit" class="btn{if $filter_q}i{else}{/if}" value="Filter">
					<input name="act" type="hidden" id="act" value="filter1">
					&nbsp;
			</td>
			<td width="7" class="TableHeaderCenter"></td>
		</tr>
	</table>
</div>