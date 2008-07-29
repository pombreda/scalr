{include file="inc/header.tpl"}
	{include file="inc/table_header.tpl"}
		{include file="inc/intable_header.tpl" header="Admin account" color="Gray"}
		<tr>
			<td width="18%">Login:</td>
			<td width="82%"><input name="admin_login" type="text" class="text" id="login" value="{$admin_login}" size="30"></td>
		</tr>
		<tr>
			<td>Password:</td>
			<td><input name="pass" type="password" class="text" id="pass" value="******" size="30"></td>
		</tr>
		<tr>
			<td width="18%">E-mail:</td>
			<td width="82%"><input name="email_address" type="text" class="text" id="email_address" value="{$email_address}" size="30"></td>
		</tr>
		<tr>
			<td>Name:</td>
			<td><input name="email_name" type="text" class="text" id="email_name" value="{$email_name}" size="30">
		</td>
		</tr>
		{include file="inc/intable_footer.tpl" color="Gray"}
		
		{include file="inc/intable_header.tpl" header="eMail settings" color="Gray"}
		<tr>
			<td width="18%">SMTP connection:</td>
			<td width="82%"><input name="email_dsn" type="text" class="text" id="email_dsn" value="{$email_dsn}" size="30"> (user:password@host:port. Leave empty to use MTA)</td>
		</tr>
		{include file="inc/intable_footer.tpl" color="Gray"}
		
		{include file="inc/intable_header.tpl" header="Log rotation settings" color="Gray"}
		<tr>
			<td width="18%">Keep logs for:</td>
			<td width="82%"><input name="log_days" type="text" class="text" id="log_days" value="{$log_days}" size="5"> days</td>
		</tr>
		{include file="inc/intable_footer.tpl" color="Gray"}
		
		{include file="inc/intable_header.tpl" header="DNS settings" color="Gray"}
		<tr>
			<td width="18%">Dynamic A record TTL:</td>
			<td width="82%"><input name="dynamic_a_rec_ttl" type="text" class="text" id="dynamic_a_rec_ttl" value="{$dynamic_a_rec_ttl}" size="5"> seconds</td>
		</tr>
		<tr>
			<td width="18%">Default SOA owner:</td>
			<td width="82%"><input name="def_soa_owner" type="text" class="text" id="def_soa_owner" value="{$def_soa_owner}" size="30"></td>
		</tr>
		<tr>
			<td width="18%">Default SOA parent:</td>
			<td width="82%"><input name="def_soa_parent" type="text" class="text" id="def_soa_parent" value="{$def_soa_parent}" size="30"></td>
		</tr>
		<tr valign="top">
			<td width="18%">Named.conf Zone template:</td>
			<td width="82%"><textarea name="namedconftpl" class="text" id="namedconftpl" cols="60" rows="5">{$namedconftpl}</textarea></td>
		</tr>
		{include file="inc/intable_footer.tpl" color="Gray"}
		
		{include file="inc/intable_header.tpl" header="AWS settings" color="Gray"}
		<tr>
			<td width="18%">Account ID:</td>
			<td width="82%"><input name="aws_accountid" type="text" class="text" id="aws_accountid" value="{$aws_accountid}" size="30"></td>
		</tr>
		<tr>
			<td width="18%">Key name:</td>
			<td width="82%"><input name="aws_keyname" type="text" class="text" id="aws_keyname" value="{$aws_keyname}" size="30"></td>
		</tr>
		<tr>
			<td width="18%">Access key:</td>
			<td width="82%"><input name="aws_accesskey" type="text" class="text" id="aws_accesskey" value="{$aws_accesskey}" size="30"></td>
		</tr>
		<tr>
			<td width="18%">Access key ID:</td>
			<td width="82%"><input name="aws_accesskey_id" type="text" class="text" id="aws_accesskey_id" value="{$aws_accesskey_id}" size="30"></td>
		</tr>
		<tr>
			<td width="18%">Security groups prefix:</td>
			<td width="82%"><input name="secgroup_prefix" type="text" class="text" id="secgroup_prefix" value="{$secgroup_prefix}" size="30"></td>
		</tr>
		<tr valign="top">
			<td width="18%">S3cfg template:</td>
			<td width="82%"><textarea name="s3cfg_template" class="text" id="s3cfg_template" cols="60" rows="10">{$s3cfg_template}</textarea></td>
		</tr>
		<tr>
			<td width="18%">Instances limit:</td>
			<td width="82%"><input name="client_max_instances" type="text" class="text" id="client_max_instances" value="{$client_max_instances}" size="10"></td>
		</tr>
		{include file="inc/intable_footer.tpl" color="Gray"}
		
		{include file="inc/intable_header.tpl" header="RRD statistics settings" color="Gray"}
		<tr>
			<td width="18%">Path to rrdtool binary:</td>
			<td width="82%"><input name="rrdtool_path" type="text" class="text" id="rrdtool_path" value="{$rrdtool_path}" size="30"></td>
		</tr>
		<tr>
			<td width="18%">Path to font (for rrdtool):</td>
			<td width="82%"><input name="rrd_default_font_path" type="text" class="text" id="rrd_default_font_path" value="{$rrd_default_font_path}" size="30"></td>
		</tr>
		<tr>
			<td width="18%">Path to RRD database dir:</td>
			<td width="82%"><input name="rrd_db_dir" type="text" class="text" id="rrd_db_dir" value="{$rrd_db_dir}" size="30"></td>
		</tr>
		<tr>
			<td width="18%">Statistics URL:</td>
			<td width="82%"><input name="rrd_stats_url" type="text" class="text" id="rrd_stats_url" value="{if $rrd_stats_url}{$rrd_stats_url}{else}http://{$smarty.server.SERVER_NAME}{/if}" size="30">
			<span class="Webta_Ihelp">Allowed tags: %fid% - Farm ID, %rn% - role name, %wn% - watcher name</span>
			</td>
		</tr>
		<tr>
			<td width="18%">Store graphics in:</td>
			<td width="82%">
				<select name="rrd_graph_storage_type">
					<option value="S3">Amazon S3</option>
					<option value="LOCAL">Local filesystem</option>
				</select>
			</td>
		</tr>
		<tr>
			<td width="18%">Path to graphics:</td>
			<td width="82%"><input name="rrd_graph_storage_path" type="text" class="text" id="rrd_graph_storage_path" value="{$rrd_graph_storage_path}" size="30">
			<span class="Webta_Ihelp">Bucket name for Amazon S3 or path to folder for Local filesystem</span>
			</td>
		</tr>
		{include file="inc/intable_footer.tpl" color="Gray"}
		
		{include file="inc/intable_header.tpl" header="Application settings" color="Gray"}
		<tr>
			<td width="18%">Path to snmpinform:</td>
			<td width="82%"><input name="snmptrap_path" type="text" class="text" id="snmptrap_path" value="{$snmptrap_path}" size="30"></td>
		</tr>
		<tr>
			<td width="18%">Event handler URL:</td>
			<td width="82%"><select name="http_proto" class="text" style="vertical-align:middle;">
				<option {if $http_proto == 'http://'}selected{/if} value="http://">http://</option>
				<option {if $http_proto == 'https://'}selected{/if} value="https://">https://</option>
			</select><input name="eventhandler_url" type="text" class="text" id="eventhandler_url" value="{$eventhandler_url}" size="30"></td>
		</tr>
		<tr>
			<td colspan="2">Terminate instance if it doesn't send 'rebootFinish' event after reboot in <input name="reboot_timeout" type="text" class="text" id="reboot_timeout" value="{$reboot_timeout}" size="3"> seconds.</td>
		</tr>
		<tr>
			<td colspan="2">Terminate instance if it doesn't send 'hostUp' or 'hostInit' event after launch in <input name="launch_timeout" type="text" class="text" id="launch_timeout" value="{$launch_timeout}" size="3"> seconds.</td>
		</tr>
		{include file="inc/intable_footer.tpl" color="Gray"}
	{include file="inc/table_footer.tpl" edit_page=1}
{include file="inc/footer.tpl"}
