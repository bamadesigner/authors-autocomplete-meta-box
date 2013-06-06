<?php

/*
Plugin Name: Authors Autocomplete Meta Box
Plugin URI: http://wordpress.org/plugins/authors-autocomplete-meta-box
Description: Replaces the default WordPress Author meta box (that has an author dropdown) with a meta box that allows you to select the author via Autocomplete.
Version: 1.1
Author: Rachel Carden
Author URI: http://www.rachelcarden.com
*/

/*
 * Big shoutout to http://www.ereleases.com for commissioning
 * this plugin and letting me share it with the community.
 * Thanks, guys. You rock!
 */

/*
 * Registers all of the admin scripts and styles.
 */
add_action( 'admin_enqueue_scripts', 'authors_autocomplete_mb_admin_enqueue_scripts_styles' );
function authors_autocomplete_mb_admin_enqueue_scripts_styles( $page ) {
	switch( $page ) {
		case 'post.php':
		case 'post-new.php':
			wp_enqueue_style( 'authors-autocomplete-admin-post', plugins_url( 'css/admin-post.css' , __FILE__ ) );
			wp_enqueue_script( 'authors-autocomplete-admin-post', plugins_url( 'js/admin-post.js' , __FILE__ ), array( 'jquery', 'post', 'jquery-ui-autocomplete' ), '', true );
			break;
	}
}

/*
 * Used to validate whether a user should be
 * allowed for autocomplete results.
 */
function authors_autocomplete_mb_allow_user( $userdata, $post_id = 0, $post_type = '' ) {
	global $wpdb, $blog_id, $wp_roles;
	
	// must be an object
	if ( ! is_object( $userdata ) )
		return false;
		
	// must contain the user ID
	if ( ! isset( $userdata->ID ) )
		return false;
		
	/*
	 * This filter allows you to block users from the list according
	 * to user ID by returning true to allow and false to deny.
	 */
	if ( apply_filters( 'authors_autocomplete_mb_allow_user_id', true, $userdata->ID, $post_id, $post_type ) ) {
	
		// make sure we have the user's capabilities (what is actually their user roles)
		if ( ! isset( $userdata->capabilities ) )
			$userdata->capabilities = $wpdb->get_var( "SELECT meta_value FROM $wpdb->usermeta WHERE user_id = {$userdata->ID} AND meta_key = '{$wpdb->get_blog_prefix( $blog_id )}capabilities'" );
			
		// make sure user roles exist and are an array
		if ( ( $user_roles = maybe_unserialize( $userdata->capabilities ) ) && is_array( $user_roles ) ) {
		
			foreach( $user_roles as $role => $has_role ) {
			
				/*
				 * This filter allows you to block users from the list according
				 * to user role by returning true to allow and false to deny.
				 */
				if ( apply_filters( 'authors_autocomplete_mb_allow_user_role', true, $role, $post_id, $post_type ) ) {
			
					/*
					 * If their user role has the capability to 'edit_posts'/'edit_pages',
					 * then they are an author. The filter,
					 * 'authors_autocomplete_mb_author_capability', allows
					 * you to change the tested author capability to
					 * something other than 'edit_posts'/'edit_pages'.
					 */
					$author_capability = apply_filters( 'authors_autocomplete_mb_author_capability', ( isset( $post_type ) && $post_type == 'page' ) ? 'edit_pages' : 'edit_posts', $post_id, $post_type );
										
					if ( isset( $wp_roles->roles[ $role ] )
						&& isset( $wp_roles->roles[ $role ][ 'capabilities' ] )
						&& isset( $wp_roles->roles[ $role ][ 'capabilities' ][ $author_capability ] )
						&& $wp_roles->roles[ $role ][ 'capabilities' ][ $author_capability ] ) {
						
						// this user has passed all of the tests
						return true;
						
					}
					
				}
				
			}
			
		}
		
	}
		
	return false;
	
}

/*
 * Takes care of the autocomplete results.
 */
add_action( 'wp_ajax_authors_autocomplete_mb_autocomplete_callback', 'ajax_authors_autocomplete_mb_autocomplete_callback' );
function ajax_authors_autocomplete_mb_autocomplete_callback() {
	global $wpdb, $blog_id;
	
	// if search term exists
	if ( $search_term = ( isset( $_POST[ 'authors_autocomplete_mb_search_term' ] ) && ! empty( $_POST[ 'authors_autocomplete_mb_search_term' ] ) ) ? $_POST[ 'authors_autocomplete_mb_search_term' ] : NULL ) {
	
		// retrieve all of the user data of users who match search term
		if ( ( $users = $wpdb->get_results( "SELECT users.*, usermeta.meta_value AS capabilities FROM $wpdb->users users INNER JOIN $wpdb->usermeta usermeta ON usermeta.user_id = users.ID AND usermeta.meta_key = '{$wpdb->get_blog_prefix( $blog_id )}capabilities' WHERE ( users.user_login LIKE '%$search_term%' OR users.display_name LIKE '%$search_term%' OR users.user_email LIKE '%$search_term%' ) ORDER BY users.display_name" ) )
			&& is_array( $users ) ) {
	
			// we need the post ID for authors_autocomplete_mb_allow_user()
			$post_id = ( isset( $_POST[ 'authors_autocomplete_mb_post_id' ] ) && $_POST[ 'authors_autocomplete_mb_post_id' ] > 0 ) ? $_POST[ 'authors_autocomplete_mb_post_id' ] : 0;
		
			// we need the post type for authors_autocomplete_mb_allow_user()
			$post_type = ( isset( $_POST[ 'authors_autocomplete_mb_post_type' ] ) && ! empty( $_POST[ 'authors_autocomplete_mb_post_type' ] ) ) ? $_POST[ 'authors_autocomplete_mb_post_type' ] : 0;
				
			// build the autocomplete results
			$results = array();
			
			// loop through each user to make sure they are allowed
			foreach ( $users as $user ) {			
				if ( authors_autocomplete_mb_allow_user( $user, $post_id, $post_type ) ) {
				
					$results[] = array(
						'user_id'		=> $user->ID,
						'user_login'	=> $user->user_login,
						'display_name'	=> $user->display_name,
						'email'			=> $user->user_email,
						'value'			=> $user->ID,
						'label'			=> $user->display_name,
						);
					
				}				
			}
			
			// "return" the results
			echo json_encode( $results );
			
		}
				
	}
	
	die();
}

/*
 * Figures out if what the user entered is an actual user.
 * If user exists, returns user info or error data.
 */
add_action( 'wp_ajax_authors_autocomplete_mb_if_user_exists_by_value', 'ajax_authors_autocomplete_mb_if_user_exists_by_value' );
function ajax_authors_autocomplete_mb_if_user_exists_by_value() {
	global $wpdb, $blog_id;
	
	// if user value exists
	if ( $user_value = ( isset( $_POST[ 'authors_autocomplete_mb_user_value' ] ) && ! empty( $_POST[ 'authors_autocomplete_mb_user_value' ] ) ) ? $_POST[ 'authors_autocomplete_mb_user_value' ] : NULL ) {
	
		// we need the post ID for authors_autocomplete_mb_allow_user()
		$post_id = ( isset( $_POST[ 'authors_autocomplete_mb_post_id' ] ) && $_POST[ 'authors_autocomplete_mb_post_id' ] > 0 ) ? $_POST[ 'authors_autocomplete_mb_post_id' ] : 0;
	
		// we need the post type for authors_autocomplete_mb_allow_user()
		$post_type = ( isset( $_POST[ 'authors_autocomplete_mb_post_type' ] ) && ! empty( $_POST[ 'authors_autocomplete_mb_post_type' ] ) ) ? $_POST[ 'authors_autocomplete_mb_post_type' ] : 0;
	
		// if the user exists, get all of their information
		if ( $user = $wpdb->get_row( "SELECT users.*, usermeta.meta_value AS capabilities FROM $wpdb->users users INNER JOIN $wpdb->usermeta usermeta ON usermeta.user_id = users.ID AND usermeta.meta_key = '{$wpdb->get_blog_prefix( $blog_id )}capabilities' WHERE ( users.user_login LIKE '$user_value' OR users.display_name LIKE '$user_value' OR users.user_email LIKE '$user_value' )" ) ) {
		
			// if user is allowed, "return" user information
			if ( authors_autocomplete_mb_allow_user( $user, $post_id, $post_type ) ) {
				echo json_encode( $user );
				die();
			}
			
			// otherwise, let the script know the user is not allowed
			else {
				echo json_encode( (object)array( 'notallowed' => 1 ) );
				die();
			}
				
		}
		
		// let the script know the user does not exist
		echo json_encode( (object)array( 'doesnotexist' => 1 ) );
			
	}
	
	die();
}

/*
 * Gets the gravatar for the selected user.
 */
add_action( 'wp_ajax_authors_autocomplete_mb_get_user_gravatar', 'ajax_authors_autocomplete_mb_get_user_gravatar' );
function ajax_authors_autocomplete_mb_get_user_gravatar() {

	// if user id exists
	if ( $user_id = ( isset( $_POST[ 'authors_autocomplete_mb_user_id' ] ) && ! empty( $_POST[ 'authors_autocomplete_mb_user_id' ] ) ) ? $_POST[ 'authors_autocomplete_mb_user_id' ] : NULL ) {
	
		// if set, allows you to overwrite the default size of 32 via AJAX call
		$gravatar_size = ( isset( $_POST[ 'authors_autocomplete_mb_gravatar_size' ] ) && ! empty( $_POST[ 'authors_autocomplete_mb_gravatar_size' ] ) && $_POST[ 'authors_autocomplete_mb_gravatar_size' ] > 0 ) ? $_POST[ 'authors_autocomplete_mb_gravatar_size' ] : 32;
		
		// "return" the gravatar
		echo get_avatar( $user_id, $gravatar_size );
		
	}
	
	die();
}

/*
 * Remove the core "Author" meta box and add our
 * custom "Author" meta box.
 */
add_action( 'add_meta_boxes', 'authors_autocomplete_mb_add_meta_boxes', 1, 2 );
function authors_autocomplete_mb_add_meta_boxes( $post_type, $post ) {
	global $wp_meta_boxes;
	if ( ( $screen = get_current_screen() )
		&& ( $post_type == $screen->id )
		&& post_type_supports( $post_type, 'author' )
		&& ( $post_type_object = get_post_type_object( $post_type ) )
		&& ( is_super_admin() || current_user_can( $post_type_object->cap->edit_others_posts ) ) ) {
		
		/*
		 * We can't simply remove_meta_box() because WordPress
		 * doesn't like us removing/replacing core WordPress
		 * meta boxes. If you use remove_meta_box( 'authordiv' ), it
		 * sets the box in $wp_meta_boxes as "false" which blocks us
		 * from using add_meta_box( 'authordiv' ).
		 */
		foreach( $wp_meta_boxes as $mb_post_type => $mb_post_type_context ) {
			foreach( $mb_post_type_context as $context => $mb_post_type_priority ) {
				foreach( $mb_post_type_priority as $priority => $mb_post_type_meta_boxes ) {
					foreach( $mb_post_type_meta_boxes as $mb_id => $mb ) {
						
						if ( 'authordiv' == $mb_id )
							unset( $wp_meta_boxes[ $mb_post_type ][ $context ][ $priority ][ $mb_id ] );
						
					}	
				}			
			}
		}
		
		/*
		 * Time to add our own author meta box.
		 */
		add_meta_box( 'authors_autocomplete_mb_authordiv', __( 'Author' ), 'authors_autocomplete_mb_post_author_meta_box', $post_type, 'normal', 'core' );

	}
}

/*
 * Print our custom "Author" meta box.
 */
function authors_autocomplete_mb_post_author_meta_box( $post, $metabox ) {
	global $user_ID;
	
	if ( ( $post_type = ( isset( $post->post_type ) && ! empty( $post->post_type ) ) ? $post->post_type : NULL )
		&& post_type_supports( $post_type, 'author' )
		&& ( $post_type_object = get_post_type_object( $post_type ) )
		&& ( is_super_admin() || current_user_can( $post_type_object->cap->edit_others_posts ) ) ) {
		
		// get selected author
		$author = isset( $post->post_author ) ? get_user_by( 'id', $post->post_author ) : get_user_by( 'id', $user_ID );
		
		?><div id="authors_autocomplete_mb_dropdown" class="hide-if-js">
			<label class="screen-reader-text" for="post_author_override"><?php _e( 'Author' ); ?></label>
			<?php wp_dropdown_users( array(
				'who' => 'authors',
				'name' => 'post_author_override',
				'selected' => ( isset( $author ) && isset( $author->ID ) ) ? $author->ID : $user_ID,
				'include_selected' => true
				)); ?>
				
		</div>		
		<div class="hide-if-no-js">
			<label class="screen-reader-text" for="authors_autocomplete_mb_post_author_override_user_id"><?php _e( 'Author' ); ?></label>
			<input type="hidden" id="authors_autocomplete_mb_post_author_override_user_id" name="post_author_override" value="<?php if ( isset( $author ) && isset( $author->ID ) ) echo $author->ID; ?>" />
			<input type="hidden" id="authors_autocomplete_mb_post_author_override_display_name" name="authors_autocomplete_mb_post_author_display_name" value="<?php if ( isset( $author ) && isset( $author->data->display_name ) ) echo $author->data->display_name; ?>" />
			<table cellpadding="0" cellspacing="0" border="0">
				<tr>
					<td id="authors_autocomplete_mb_post_author_gravatar"><?php echo get_avatar( $author->ID, 32 ); ?></td>
					<td><input type="text" name="authors_autocomplete_mb_post_author" id="authors_autocomplete_mb_post_author" class="form-input-tip" size="16" autocomplete="off" value="<?php if ( isset( $author ) && isset( $author->data->display_name ) ) echo $author->data->display_name; ?>" /></td>
				</tr>
			</table>
			<p class="howto">You can search for the author by display name, login, or e-mail address.</p>
		</div><?php
		
	}
}

?>