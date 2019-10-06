// Triggered When Test FTP Button is clicked for Product
jQuery('#prod_test_ftp_connection').click(function(){
	jQuery('.spinner').addClass('is-active');
	var use_ftp = jQuery("#pro_use_ftps").prop("checked") ? 1 : 0;
	jQuery.ajax({
		url : xa_prod_piep_test_ftp.admin_ajax_url,
		type:       'POST',
		data : {
				action:		'product_test_ftp_connection',
				ftp_host:	jQuery('#pro_ftp_server').val(),
				ftp_port:	jQuery('#pro_ftp_port').val(),
				ftp_userid:	jQuery('#pro_ftp_user').val(),
				ftp_password:	jQuery('#pro_ftp_password').val(),
				use_ftps:	use_ftp
				},
		success : function(response){
			jQuery('.spinner').removeClass('is-active');
			jQuery('#prod_ftp_test_msg').remove();
			jQuery('#prod_ftp_test_notice').prepend(response);
			jQuery("#prod_ftp_test_msg").delay(8000).fadeOut(300);
		}
	    });
});

// Triggered When Test FTP Button is clicked for review
jQuery('#rev_test_ftp_connection').click(function(){
	jQuery('.spinner').addClass('is-active');
	var rev_use_ftp = jQuery("#rev_use_ftps").prop("checked") ? 1 : 0;
	jQuery.ajax({
		url : xa_prod_review_test_ftp.admin_ajax_url,
		type:       'POST',
		data : {
				action:		'product_reviews_test_ftp_connection',
				ftp_host:	jQuery('#rev_ftp_server').val(),
				ftp_port:	jQuery('#rev_ftp_port').val(),
				ftp_userid:	jQuery('#rev_ftp_user').val(),
				ftp_password:	jQuery('#rev_ftp_password').val(),
				use_ftps:	rev_use_ftp
				},
		success : function(response){
			jQuery('.spinner').removeClass('is-active');
			jQuery('#prod_rev_ftp_test_msg').remove();
			jQuery('#prod_rev_ftp_test_notice').prepend(response);
			jQuery("#prod_rev_ftp_test_msg").delay(8000).fadeOut(300);
		}
	    });
});