{include file="inc/header.tpl"}
	<br />
	<div style="position:relative;width:auto;">
	{include file="inc/table_header.tpl" nofilter=1}
		{include file="inc/intable_header.tpl" header="General information" color="Gray"}
		<tr>
    		<td>{t}Author{/t}:</td>
    		<td>
    			{if $role.client.id}
					{if $role.client.id == $smarty.session.uid}
						Me
					{else}
						{$role.client.fullname}
					{/if}
				{else}
					Scalr
				{/if}
    		</td>
    	</tr>
		<tr>
			<td>Role name:</td>
			<td id="c_role_name">{$role.name}</td>
		</tr>
		<tr>
			<td>Category:</td>
			<td id="c_role_type">{$role.type}</td>
		</tr>
		<tr>
			<td>AMI ID:</td>
			<td id="c_role_amiid">{$role.ami_id}
			
			{if $comments_enabled && $role.approval_state == 'Pending'}
				&nbsp;&nbsp;[<a href="contrib_role_switch_ami.php?ami_id={$role.ami_id}">Switch to new AMI</a>]
			{/if}
			</td>
		</tr>
		<tr>
			<td>Architecture:</td>
			<td>
				<span id="arch_i386" class="ui_enum {if $role.architecture == 'i386'}ui_enum_selected{/if}">i386</span>&nbsp;&nbsp;<span id="arch_x86_64" class="ui_enum {if $role.architecture == 'x86_64'}ui_enum_selected{/if}">x86_64</span>
			</td>
		</tr>
		{if $comments_enabled}
		<tr><td colspan="2">&nbsp;</td></tr>
    	<tr>
    		<td>{t}Moderation phase{/t}:</td>
    		<td>
    			{if $role.approval_state == 'Approved' || !$role.approval_state}
					<img src="/images/true.gif" title="{t}Approved{/t}">
				{elseif $role.approval_state == 'Pending'}
					<img src="/images/pending.gif" title="{t}Pending{/t}">
				{elseif $role.approval_state == 'Declined'}
					<img src="/images/false.gif" title="{t}Declined{/t}">
					&nbsp;{$role.approval_state}
				{/if}
    		</td>
    	</tr>
    	{/if}
		<tr><td colspan="2">&nbsp;</td></tr>
		<tr valign="top">
			<td valign="top">Description:</td>
			<td id="c_role_descr">{$role.description}</td>
		</tr>
    	{include file="inc/intable_footer.tpl" color="Gray"}
    	
    	{if $comments_enabled}
	    	{include file="inc/comments.tpl"}
		{else}
			{include file="inc/table_footer.tpl" disable_footer_line="1"}
		{/if}
	</div>
{include file="inc/footer.tpl"}