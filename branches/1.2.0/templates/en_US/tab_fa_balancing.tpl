<div id="itab_contents_balancing_n" class="x-hide-display" style="padding:10px;">
	<div>
	<table width="100%" cellspacing="4">
		<tbody>
	   		<tr>
	     		<td colspan="2">
	     			<input onclick='LBSetSettings(this.checked);' type="checkbox" id="lb.use_elb" class="role_settings" name="lb.use_elb" value="1" style="vertical-align:middle;" />
	             	Use <a target="_blank" href="http://aws.amazon.com/elasticloadbalancing/">Amazon Elastic Load Balancer</a> to balance load between instances of this role
	     		</td>
	     	</tr>
	     </tbody>
	</table>
	</div>
	<div id='lb_settings' class='x-hide-display'>
		<input type="hidden" id="lb.hostname" name="lb.hostname" class="role_settings" value="" />
  		<br />
		<table width="100%" cellpadding="4" cellspacing="4" border="0" style="border:1px solid #f9f9f9;background-color:#f9f9f9;">
			<tr>
				<td width="170" nowrap="nowrap">Healthy Threshold <img style="vertical-align:middle;cursor:pointer;" title="Help" onclick="ShowHelp(event, 'lb_ht_help', this);" src="/images/icon_shelp.png">:</td>
				<td width="200"><input type="text" id="lb.healthcheck.healthythreshold" name="lb.healthcheck.healthythreshold" class="role_settings text" size="2" /></td>
				<td width="100%"></td>
			</tr>
			<tr>
				<td>Interval <img style="vertical-align:middle;cursor:pointer;" title="Help" onclick="ShowHelp(event, 'lb_int_help', this);" src="/images/icon_shelp.png">:</td>
				<td><input type="text" id="lb.healthcheck.interval" name="lb.healthcheck.interval" class="role_settings text" size="2" /> seconds</td>
			</tr>
			<tr>
				<td>Target <img style="vertical-align:middle;cursor:pointer;" title="Help" onclick="ShowHelp(event, 'lb_target_help', this);" src="/images/icon_shelp.png">:</td>
				<td><input type="text" id="lb.healthcheck.target" name="lb.healthcheck.target" class="role_settings text" size="20" /></td>
			</tr>
			<tr>
				<td>Timeout <img style="vertical-align:middle;cursor:pointer;" title="Help" onclick="ShowHelp(event, 'lb_timeout_help', this);" src="/images/icon_shelp.png">:</td>
				<td><input type="text" id="lb.healthcheck.timeout" name="lb.healthcheck.timeout" class="role_settings text" size="2" /> seconds</td>
			</tr>
			<tr>
				<td>Unhealthy Threshold <img style="vertical-align:middle;cursor:pointer;" title="Help" onclick="ShowHelp(event, 'lb_uht_help', this);" src="/images/icon_shelp.png">:</td>
				<td><input type="text" id="lb.healthcheck.unhealthythreshold" name="lb.healthcheck.unhealthythreshold" class="role_settings text" size="2" /></td>
			</tr>
		</table>
		<br />
		<div style="border:1px solid #f9f9f9;background-color:#f9f9f9;padding:5px;">
			<div style="padding-bottom:7px;">Availablility zones:</div>
			{section name=zid loop=$avail_zones}
	   			{if $avail_zones[zid] != ""}
	   			<input type="checkbox" id="lb.avail_zone.{$avail_zones[zid]}" name="lb.avail_zone.{$avail_zones[zid]}" class="role_settings" value="1"> {$avail_zones[zid]}
	   			{/if}
	   		{/section}
   		</div>
   		<br />
		<div id="lb.listeners.grid.container"></div>
       	{literal}
        <script language="Javascript" type="text/javascript">
	        function RemoveListener(record_id)
			{
				LBListenersStore.remove(LBListenersStore.getById(record_id));
			}

			var LBListenersGrid = null;
			var LBListenersStore = null;
         		
         	Ext.onReady(function(){

   		    var myData = [];

     		    
   		    // create the data store
   		    LBListenersStore = new Ext.data.SimpleStore({
   		        fields: [
   		           {name: 'protocol'},
   		           {name: 'lb_port', type: 'int'},
   		           {name: 'instance_port', type: 'int'}
   		        ]
   		    });

   		    function protoRenderer(value, p, record)
   		    {
   		    	var rnd_id = 'lb.role.listener.'+parseInt(Math.random()*100000);
   		    	var listener = record.data.protocol+"#"+record.data.lb_port+"#"+record.data.instance_port;;
   		    	
				return value+"<input type='hidden' id='"+rnd_id+"' name='"+rnd_id+"' value='"+listener+"' class='role_settings' />";
   		    }

       		function removeRenderer(value, p, record){
       			return '<img onclick="RemoveListener('+record.id+');" style="cursor:pointer;" src="/images/delete_zone.gif" />';
       	    }

         		    // create the Grid
         	LBListenersGrid = new Ext.grid.GridPanel({
				store: LBListenersStore,
   		     	viewConfig: { 
   		        	emptyText: "No listeners defined"
   		        },
   		        columns: [
   		            {id:'protocol',header: "Protocol", width: 150, renderer:protoRenderer, sortable: true, dataIndex: 'protocol'},
   		            {header: "Load balancer port", width: 180, sortable: true, dataIndex: 'lb_port'},
   		            {header: "Instance port", width: 180, sortable: true, dataIndex: 'instance_port'},
   		         	{id:'remove', header: "Remove", width: 50, sortable: false, renderer:removeRenderer, dataIndex: 'protocol', align:'center'}
   		        ],
   		     	bbar:['Protocol:&nbsp;', 
           		    '<select style="vertical-align:middle;" id="lb.bbar.proto" name="lb.bbar.proto"><option value="TCP">TCP</option><option value="HTTP">HTTP</option></select>',
           		    '&nbsp;','Load balancer port:&nbsp;',
           		    {
          		     	xtype:'field',
          		     	inputType:'text',
          		     	id: 'lb.bbar.lb_port',
          		     	name: 'lb.bbar.lb_port',
          		     	width:75,
       	                allowBlank:false
         	        },
         	        '&nbsp;&nbsp;&nbsp;Instance port:&nbsp;',
     	            {
          		     	xtype:'field',
          		     	id: 'lb.bbar.i_port',
	           		    name: 'lb.bbar.i_port',
	           		 	width:75,
         	            allowBlank:false
					},
					'&nbsp;',
					{
     			        icon: '/images/add.png', // icons can also be specified inline
     			        cls: 'x-btn-icon',
     			        tooltip: 'Add new listener',
         			    handler: function() 
         			    {
          	            	var lb_val = $('lb.bbar.lb_port').value;
          	            	        							
							if (lb_val < 1024 || lb_val > 65535)
							{
								if (lb_val != 80 && lb_val != 443)
								{
									Ext.MessageBox.show({
							           title: 'Error',
							           msg: 'Valid LoadBalancer ports are - 80, 443 and 1024 through 65535',
							           buttons: Ext.MessageBox.OK,
							           animEl: 'mb9',
							           icon: Ext.MessageBox.ERROR
							        });										
									return false;
								}
							}
						
							var is_val = $('lb.bbar.i_port').value; 
								           							
							if (is_val < 1 || is_val > 65535)
							{
								Ext.MessageBox.show({
						           title: 'Error',
						           msg: 'Valid instance ports are one (1) through 65535',
						           buttons: Ext.MessageBox.OK,
						           animEl: 'mb9',
						           icon: Ext.MessageBox.ERROR
						        });	
				        									
								return false;
							}

							var recordData = {
         	            		protocol:$('lb.bbar.proto').value,
         	            		lb_port:lb_val, 
         	            		instance_port:is_val
          	        		};

							var list_exists = false;
							
							$('lb.bbar.proto').value = 'TCP';
							$('lb.bbar.lb_port').value = '';
							$('lb.bbar.i_port').value = '';
							
							LBListenersStore.each(function(item, index, length){
								if (item.data.protocol == recordData.protocol &&
									item.data.lb_port == recordData.lb_port &&
									item.data.instance_port == recordData.instance_port
								)
								{
									Ext.MessageBox.show({
							           title: 'Error',
							           msg: 'Such listener already exists',
							           buttons: Ext.MessageBox.OK,
							           animEl: 'mb9',
							           icon: Ext.MessageBox.ERROR
							        });

									list_exists = true;
									
							        return false;
								}
									
							}, this);

							if (!list_exists)
       	            			LBListenersStore.add(new LBListenersStore.reader.recordType(recordData));
       			        }
       			    }],
       		        stripeRows: true,
       		        autoHeight:true,
       		        style:'width:100%',
       		     	enableColumnHide:false, 
       		        title:'Listeners&nbsp;&nbsp;&nbsp;<img style="vertical-align:middle;cursor:pointer;" title="Help" onclick="ShowHelp(event, \'lb_listeners_help\', this);" src="/images/icon_shelp.png">'
       		    });

       		 	LBListenersGrid.render('lb.listeners.grid.container');
       			LBListenersStore.loadData(myData);

       			/**
           		TODO: Remove this workaround.
           		*/
    	       	(function (){
    	       		var recordData = {
   	            		protocol:1,
   	            		lb_port:1, 
   	            		instance_port:1
    	        	};
               		var t = new LBListenersStore.reader.recordType(recordData);
               		LBListenersStore.add(t);
               		LBListenersStore.remove(LBListenersStore.getById(t.id));
    	       	}).defer(20);           		    
        	});
        		
			function LBSetSettings(isenabled)
       		{
				if (isenabled)
				{
					$('lb_settings').removeClassName('x-hide-display');
					LBListenersGrid.getBottomToolbar().setDisabled(false);
					LBListenersGrid.getColumnModel().setHidden(LBListenersGrid.getColumnModel().getIndexById('remove'), false);
				}
				else
					$('lb_settings').addClassName('x-hide-display');
       		}
       	</script>
	{/literal}
	</div>
</div>