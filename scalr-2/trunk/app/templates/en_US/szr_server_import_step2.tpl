{include file="inc/header.tpl"}
<script>
{literal}
Ext.onReady(function () {

	function waitHello() {
		Ext.Ajax.request({
			url: '/server/ajax-ui-server-import.php?action=WaitHello',
			success: function (xhr) {
				try {
					response = Ext.decode(xhr.responseText);
					if (response.data.helloReceived) {
						location.href = response.data.redirectTo || "/bundle_tasks.php"
						/*
						Ext.select('#activity-indicator img').replaceWith({tag: 'img', src: "/images/ok.png"})
						var msgEl = Ext.select('#activity-indicator span').item(0)
						msgEl.dom.innerHTML = 'Communication has been established. You can close this window';
						*/
						return;
					}
				} catch (e) {
				}
				// Next round
				waitHello.defer(1000);
			}
		});
	}
	waitHello();
	
});
{/literal}
</script>
<style>
{literal}
h2 {
	margin:10px 0;
	font-size:120%;
}
h3 {
	margin:5px 0;
}
p {
	padding: 5px 0;
}
pre {
	padding: 5px; border: 1px solid #cccccc; background: #f7f7f7;
}
{/literal}
</style>

	{include file="inc/table_header.tpl"}
	{include file="inc/intable_header.tpl" header="Import server &mdash; Step 2 (Establish communication)" color="Gray"}
		<tr>
			<td colspan="2">
				<h2>Install Scalarizr.</h2>
				
				<h3 >For RHEL/CentOS/Fedora</h3>
				
				<p>Install scalr repository package:</p>
				<pre>rpm -Uvh http://rpm.scalr.net/rpm/scalr-release-2-1.noarch.rpm</pre>
				
				<p>Install scalarizr:</p>
				<pre>$ yum install scalarizr</pre>
				
				<p>NOTE! on RHEL5/CentOS5 you need to install python26. <a href="http://fedoraproject.org/wiki/EPEL">EPEL community</a> maintains latest python versions for RHEL5<br/>
				Here is the <a href="http://fedoraproject.org/wiki/EPEL/FAQ#howtouse">quick sample</a> how to enable their repository<br>
				Then you should install python 2.6
				</p>
				<pre>$ yum install python26</pre>
				
				

				<h3>For Debian/Ubuntu</h3>
<p>Download and install scalr_repository package:</p>
<pre>wget http://apt.scalr.net/scalr-repository_0.2_all.deb
dpkg -i scalr-repository_0.2_all.deb</pre>				
	
<p>Download the list of packages in repository:</p>
<pre>apt-get update</pre>
				
<p>Install scalarizr:</p>
<pre>$ apt-get install scalarizr</pre>
			</td>
		</tr>
		
		<tr>
			<td colspan="2">
				<h2 >Configure Scalarizr by executing the following command:</h2>
				<textarea style='width:80%;height:150px;' class='text'>{$command}</textarea>
			</td>
		</tr>
	    <tr>
			<td colspan="2">
				<div id="activity-indicator" style="font-size:120%; padding-bottom: 20px">
					<img src="/images/loading.gif"/>
					<span>Establishing communication with Scalarizr</span>
				</div>
			</td>
		</tr>
		
		
		
		<input type="hidden" name="step" value="2"/>
    {include file="inc/intable_footer.tpl" color="Gray"}
    {include file="inc/table_footer.tpl"}

{include file="inc/footer.tpl"}