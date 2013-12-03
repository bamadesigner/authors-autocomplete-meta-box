jQuery.noConflict()(function(){

	// save the post ID and post type for AJAX calls
	var $authors_autocomplete_mb_post_id = 0;
	if ( jQuery( '#post_ID' ).length > 0 )
		$authors_autocomplete_mb_post_id = jQuery( '#post_ID' ).val();
		
	var $authors_autocomplete_mb_post_type = '';
	if ( jQuery( '#post_type' ).length > 0 )
		$authors_autocomplete_mb_post_type = jQuery( '#post_type' ).val();

	// handle autocomplete for authors
	jQuery( 'table#authors_autocomplete_mb_autocomplete' ).each( function() {
		
		// this is the authors autocomplete input	
		var $authors_autocomplete_mb_input = jQuery( 'input#authors_autocomplete_mb_post_author' );
		
		// remove the hidden authors dropdown to remove any $_POST confusion
		jQuery( '#authors_autocomplete_mb_dropdown' ).remove();
		
		// autocomplete new tags
		if ( $authors_autocomplete_mb_input.size() > 0 ) {
			
			$authors_autocomplete_mb_input.autocomplete({
				delay: 100,
				minLength: 1,
				source: function( $request, $response ){
					jQuery.ajax({
						url: ajaxurl,
						type: 'POST',
						async: true,
						cache: false,
						dataType: 'json',
						data: {
							action: 'authors_autocomplete_mb_autocomplete_callback',
							authors_autocomplete_mb_search_term: $request.term,
							authors_autocomplete_mb_post_id: $authors_autocomplete_mb_post_id,
							authors_autocomplete_mb_post_type: $authors_autocomplete_mb_post_type
						},
						success: function( $data ){
							$response( jQuery.map( $data, function( $item ) {
								return {
									user_id: $item.user_id,
									user_login: $item.user_login,
									display_name: $item.display_name,
									email: $item.email,
									value: $item.label,
									label: $item.label
								};
							}));
						}
					});
				},
				search: function( $event, $ui ) {
				
					// make sure any errors are removed
					authors_autocomplete_mb_remove_error_message();
					
				},
				select: function( $event, $ui ) {
				
					// stop the loading spinner
					authors_autocomplete_mb_stop_loading_spinner();
				
					// make sure any errors are removed
					authors_autocomplete_mb_remove_error_message();
					
					// change the saved post author
					authors_autocomplete_mb_change_post_author( $ui.item.user_id, $ui.item.display_name );
					
				},
				response: function( $event, $ui ) {
				
					// stop the loading spinner
					authors_autocomplete_mb_stop_loading_spinner();
					
				},
				focus: function( $event, $ui ) {
					
					// stop the loading spinner
					authors_autocomplete_mb_stop_loading_spinner();
				
					// make sure any errors are removed
					authors_autocomplete_mb_remove_error_message();
					
				},
				close: function( $event, $ui ) {
				
					// stop the loading spinner
					authors_autocomplete_mb_stop_loading_spinner();
					
				},
				change: function( $event, $ui ) {
					
					// stop the loading spinner
					authors_autocomplete_mb_stop_loading_spinner();
					
					// remove any existing message
					authors_autocomplete_mb_remove_error_message();
					
					// get the saved author display name. we'll need it later.
					var $saved_author_display_name = jQuery( '#authors_autocomplete_mb_post_author_override_display_name' ).val();
					
					// convert to saved author display name
					if ( $authors_autocomplete_mb_input.val() == '' )
						$authors_autocomplete_mb_input.val( $saved_author_display_name );
						
					// see if what they entered is actual user
					else {
					
						// save what the user entered
						var $entered_user_value = $authors_autocomplete_mb_input.val();
					
						// see if the user exists
						jQuery.ajax({
							url: ajaxurl,
							type: 'POST',
							async: true,
							cache: false,
							dataType: 'json',
							data: {
								action: 'authors_autocomplete_mb_if_user_exists_by_value',
								authors_autocomplete_mb_user_value: $entered_user_value,
								authors_autocomplete_mb_post_id: $authors_autocomplete_mb_post_id,
								authors_autocomplete_mb_post_type: $authors_autocomplete_mb_post_type
							},
							success: function( $user ){
								
								// if the user exists
								if ( $user.ID ) {
								
									// change the input's display name
									$authors_autocomplete_mb_input.val( $user.display_name );
								
									// change the saved post author
									authors_autocomplete_mb_change_post_author( $user.ID, $user.display_name );
									
								} else {
								
									// convert to saved author display name
									$authors_autocomplete_mb_input.val( $saved_author_display_name );
								
									// show an error message
									
									// if the user is not allowed to be an author
									if ( $user.notallowed )
										authors_autocomplete_mb_add_error_message( $user.notallowed );
									
									// if the user is not allowed
									else if ( $user.doesnotexist )
										authors_autocomplete_mb_add_error_message( $user.doesnotexist );
								
								}
												
							}
						});
						
					}
					
				}

			}).data( "ui-autocomplete" )._renderItem = function( $ul, $item ) {
				return jQuery( '<li>' ).append( '<a><strong>' + $item.display_name + '</strong><br />Username: <em>' + $item.user_login + '</em><br />E-mail: <em>' + $item.email + '</em></a>' ).appendTo( $ul );
			};
			
			/**
			 * When focus is returned to input,
			 * make sure the loading spinner stops and
			 * any error messages are removed.
			 *
			 * For some reason the autocomplete 'focus'
			 * event doesn't work 100% of the time.
			 */
			$authors_autocomplete_mb_input.on( 'focus', function( $event, $ui ) {
				
				// stop the loading spinner
				authors_autocomplete_mb_stop_loading_spinner();
				
				// make sure any errors are removed
				authors_autocomplete_mb_remove_error_message();
					
			});
			
	    }
		
	});
			
});

function authors_autocomplete_mb_stop_loading_spinner() {
	jQuery( 'input#authors_autocomplete_mb_post_author' ).removeClass( 'ui-autocomplete-loading' );
}

function authors_autocomplete_mb_remove_error_message() {
	jQuery( '#authors_autocomplete_mb_error_message' ).remove();
}

function authors_autocomplete_mb_add_error_message( $message ) {

	// remove any existing error message
	authors_autocomplete_mb_remove_error_message();
	
	// add a new error message
	var $authors_autocomplete_mb_error_message = jQuery( '<div id="authors_autocomplete_mb_error_message">' + $message + '</div>' );
	jQuery( 'input#authors_autocomplete_mb_post_author' ).after( $authors_autocomplete_mb_error_message );
	
}

function authors_autocomplete_mb_change_post_author( $post_author_id, $post_author_display_name ) {

	// this stores the selected author ID
	var $authors_autocomplete_mb_post_author_override_user_id = jQuery( '#authors_autocomplete_mb_post_author_override_user_id' );
	
	// this stores the selected author display  name
	var $authors_autocomplete_mb_post_author_override_display_name = jQuery( '#authors_autocomplete_mb_post_author_override_display_name' );

	// if different, store the author ID and change gravatar
	if ( $post_author_id > 0 && $authors_autocomplete_mb_post_author_override_user_id.val() != $post_author_id ) {
		
		// save new author ID
		$authors_autocomplete_mb_post_author_override_user_id.val( $post_author_id );
		
		// save new display name
		$authors_autocomplete_mb_post_author_override_display_name.val( $post_author_display_name );
		
		// holds gravatar
		var $gravatar = jQuery( '#authors_autocomplete_mb_post_author_gravatar' );
	
		// change the gravatar
		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			async: true,
			cache: false,
			dataType: 'html',
			data: {
				action: 'authors_autocomplete_mb_get_user_gravatar',
				authors_autocomplete_mb_user_id: $post_author_id,
				authors_autocomplete_mb_gravatar_size: parseInt( $gravatar.css( 'width' ) )
			},
			success: function( $data ){
				$gravatar.html( $data );
			},
			error: function() {
				$gravatar.hide();
			}
		});
		
	}

}