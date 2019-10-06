<?php
if ( ! defined( 'WPINC' ) ) {
	exit;
}

// New post defaults
return apply_filters('alter_review_export_fields', array(
	'comment_ID'			=> '',
	'comment_post_ID'		=> '',
	'comment_author'		=> '',
        'comment_author_url'            => '',
	'comment_author_email'		=> '',
        'comment_author_IP'             => '',
	'comment_date'			=> '',
	'comment_date_gmt'		=> '',
	'comment_content'		=> '',
	'comment_approved'		=> '',
	'comment_parent'		=> '',
	'user_id'			=> '',
	'comment_alter_id'		=> '',
	'rating'			=> '',
));