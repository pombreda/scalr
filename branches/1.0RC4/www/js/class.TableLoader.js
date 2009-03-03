var TableLoader = Class.create();
    TableLoader.prototype = {
    	
    	initialize:function()
		{
		
		},
		
		Load: function(contaner_name, uri)
		{
			var url = '/server/server.php?'+uri; 
		
			$('table_body_list').update(
			'<tr id="table_loader">'+
				'<td colspan="30" align="center">'+
					'<img style="vertical-align:middle;" src="/images/snake-loader.gif"> Loading snapshots list. Please wait...'+
				'</td>'+
			'</tr>'
			);
			
			$('table_refresh_icon').style.display = 'none';
		
			$$('div.vrule').each(function(item){
				item.parentNode.removeChild(item);
			});
		
			new Ajax.Request(url,
			{ 
				method: 'get',
				contaner_name: contaner_name, 
				onSuccess: function(transport)
				{ 
					try
					{
						$(transport.request.options.contaner_name).update(transport.responseText);
						$('table_refresh_icon').style.display = '';
						
						window.setTimeout('webtacp.reloadTables()', 200);
					}
					catch(e)
					{
						alert(e.message);
					}					 
				} 
			});
		}
    };