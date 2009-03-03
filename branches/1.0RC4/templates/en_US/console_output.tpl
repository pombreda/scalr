{include file="inc/header.tpl"}
	{include file="inc/table_header.tpl"}
	<div style="margin:10px;">
		{if $console_output}
			<!-- <pre>  -->
			{$console_output}
			<!-- </pre>  -->
		{else}
			Console output not available yet
		{/if}
	</div>
	{include file="inc/table_footer.tpl" disable_footer_line=1}
{include file="inc/footer.tpl"}