{include file="inc/header.tpl"}
{literal}
<style type="text/css">
.backtrace {
	list-style:decimal;
	margin:10px 0px 0px 35px;
	padding:0px;
}
</style>
{/literal}

{include file="inc/table_header.tpl"}
<center>
<div align="center" style="width: 600px; margin:50px; padding:30px;">
	<div style="font-size:24px; background-color:red;padding:10px; color:white;">Unrecoverable error</div>
    <div style="background-color: #f0f0f0; text-align:left;font-size:14px; color:black; padding:20px;">{$message}</div>
    {if $backtrace}
	    <div style="overflow: auto; height:200px; word-wrap: break-word; text-align:left; padding: 20px; background-color:fcfcfc;">
	    <span style="text-decoration:underline;">Call stack</span> 
		{$backtrace}
	    </div>
	{/if}
    <div style="height:2px; background-color:#CCCCCC; font-size:1px;"></div>
    {if $post_serialized != ''}
    	{include file="inc/table_footer.tpl" button2=1 button2_name='Retry' backbtn=1}
    {else}
		{include file="inc/table_footer.tpl" retry_btn=1 backbtn=1}
	{/if}
</div>
</center>
{include file="inc/footer.tpl"}
