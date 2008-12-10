	function LoadStatsImage(farmid, watchername, type, role_name, hash)
	{
		var url = '/server/statistics.php?task=get_stats_image_url&farmid='+farmid+'&watchername='+watchername+'&graph_type='+type+'&role_name='+role_name; 
		new Ajax.Request(url,
		{ 
			method: 'get',
			hash: hash, 
			onSuccess: function(transport)
			{ 
				var hash = transport.request.options.hash;
				
				try
				{
					eval('var response = '+transport.responseText);
					if (response.type == 'ok')
					{
						var image1 = new Image();
						var suffix = new Date();
						image1.src = response.msg+"?"+suffix.getTime();
						image1.onload = function()
						{
							$('image_'+hash).src = this.src;
							$('loader_'+hash).style.display = 'none';
							$('image_div_'+hash).style.display = '';
						}
					}
					else
					{
						$('loader_content_'+hash).update('<img src="/images/cross_circle.png"> '+response.msg);
					}
				}
				catch(e)
				{
					$('loader_content_'+hash).update('<img src="/images/cross_circle.png"> '+e.message);
				}					 
			} 
		});
		
	}