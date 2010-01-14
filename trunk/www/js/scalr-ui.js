
	function SendRequestWithConfirmation(RequestObject, ConfirmText, ProgressText, IconClass, OnError, OnSuccess, URL)
	{
		var REQ_URL = '/server/ajax-ui-server.php';
		if (URL)
				REQ_URL = URL;
		
		Ext.MessageBox.show({
			title:'Confirm',
			msg: ConfirmText,
			icon: 'ext-mb-info',
			buttons: Ext.Msg.YESNOCANCEL,
			fn: function(btn){
				
			if (arguments[0] != 'yes')
				return;

			RequestObject.r = Math.random();

			Ext.MessageBox.show({
			    msg: ProgressText,
			    progressText: 'Processing...',
			    width:450,
			    wait:true,
			    waitConfig: {interval:200},
			    icon:IconClass, //'ext-mb-info', //custom class in msg-box.html
			    animEl: 'mb7'
			});

			if (window.TID)
				RequestObject.decrease_mininstances_setting = 1;
			
			if (window.TIF)
				RequestObject.force_terminate = 1;
			
			$('Webta_ErrMsg').style.display = 'none';

			Ext.Ajax.request({
			   url: REQ_URL,
			   success: function(response,options){
			   	
			   		eval('var result = '+response.responseText+';');
			   		if (result.result == 'ok')
			   		{
						Ext.MessageBox.hide();
						
						if (typeof(OnSuccess) == 'function')	
							OnSuccess();
			   		}
			   		else
			   		{
			   			Ext.MessageBox.hide();
					   	var err_obj = $('Webta_ErrMsg');
						err_obj.innerHTML = result.msg;
						err_obj.style.display = '';
						
						if (typeof(OnError) == 'function')	
							OnError();
							
						new Effect.Pulsate(err_obj);
			   		}
					
			   },
			   failure: function(response,options) {
				   	Ext.MessageBox.hide();
				   	var err_obj = $('Webta_ErrMsg');
					err_obj.innerHTML = 'Cannot proceed your request at the moment. Please try again later.';
					err_obj.style.display = '';
					
					if (typeof(OnError) == 'function')	
						OnError();
						
					new Effect.Pulsate(err_obj);
			   },
			   params: Ext.urlEncode(RequestObject)
			});
		}});
	}

	