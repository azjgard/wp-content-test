jQuery(document).ready(function(){

	// display users on change of role dropdown
	jQuery('select[name="role_filter"]').change(function(){
		jQuery('#mupr-image-loader').show();
		jQuery('.mupr-msg').html('');
		var role = jQuery(this).val();
				var udata = {
			 	action: "rolewise_users_display_action",
			 	role_val : role
	      }
	    	jQuery.post(mupr_ajax_obj.ajax_url,udata,
          function ( response ) {
          	jQuery('#mupr-image-loader').hide();
          	var ajax_result = jQuery.parseJSON(response);
          	if(ajax_result.result == '1'){
						jQuery('.users_list table tbody').html(ajax_result.content);
						jQuery('input[name="reset"]').prop('disabled',false);
					}
					else{
						var html = '<tr><td colspan="3" align="center">'+ajax_result.message+'</td></tr>';
						jQuery('.users_list table tbody').html(html);
						jQuery('input[name="reset"]').attr('disabled','true');
					}
	      }) 
	})

	// reset password mail send 
	jQuery('input[name="reset"]').click(function(){
		jQuery('.mupr-loader-img').show();
		var sendData = {
			action: "send_reset_password_mail_action",
			role: jQuery('.filters select[name="role_filter"]').val()
		}
		jQuery.post(mupr_ajax_obj.ajax_url,sendData,
      function ( response ) {
      	jQuery('.mupr-loader-img').hide();
      	var ajax_result = jQuery.parseJSON(response);
      	if(ajax_result.result == '1'){
					jQuery('.mass-reset-password-div .mupr-msg').html(ajax_result.message);
				}
				else{
					var html = '<tr><td colspan="3" align="center">'+ajax_result.message+'</td></tr>';
					jQuery('.users_list table tbody').html(html);
				}
    	}) 
	})
})