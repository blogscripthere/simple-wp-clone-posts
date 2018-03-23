<?php
/**
 * @package Wp_Clone_Posts
 * @version 1.0
 */
/*
Plugin Name: ScriptHere's Easily Clone Pages and Posts.
Plugin URI: https://github.com/blogscripthere/simple-wp-clone-posts
Description: ScriptHere's easily clone pages and posts in WordPress
Author: Narendra Padala
Author URI: https://in.linkedin.com/in/narendrapadala
Text Domain: shcp
Version: 1.0
Last Updated: 04/03/2018
*/

/*
* Add the clone link to action list for post and pages callback
*/
function sh_clone_post_link_callback( $actions, $post ) {
    //check if user has edit capabilities or not
    if (current_user_can('edit_posts')) {
        //set link
        $actions['clone'] = '<a href="' . wp_nonce_url('admin.php?action=sh_clone_post_as_draft_callback&post='.$post->ID, basename(__FILE__), 'clone_nonce' ) . '" title="Clone this item" rel="permalink">Clone</a>';
    }
    //return
    return $actions;
}

/*
* Add the clone link to action list for post and pages hooks
*/
add_filter( 'post_row_actions', 'sh_clone_post_link_callback', 10, 2 );
add_filter( 'page_row_actions', 'sh_clone_post_link_callback', 10, 2 );

/*
* Create a draft for post and page.
*/
function sh_clone_draft_post($post_id = 0){
	//check if post id passed or not
	if(!$post_id) { 
		//return	
		return 0;
	}
	//get the post data
	$post = get_post( $post_id );
	//check post exists or not
	if (isset( $post ) && $post != null) {

	    //get current user details
	    $current_user = wp_get_current_user();
	    //set current user id as author id for copied post
        $author = $current_user->ID;
        //generate new post data arguments
        $args = array(
            'post_title'     => $post->post_title,
            'post_name'      => $post->post_name,
            'post_content'   => $post->post_content,
            'post_excerpt'   => $post->post_excerpt,
            'post_parent'    => $post->post_parent,
            'post_type'      => $post->post_type,
            'post_status'    => 'draft',
            'post_password'  => $post->post_password,
            'post_author'    => $author,
            'ping_status'    => $post->ping_status,
            'to_ping'        => $post->to_ping,
            'comment_status' => $post->comment_status,
            'menu_order'     => $post->menu_order
        );
        //insert post data
        $draft_post_id = wp_insert_post( $args );
        //check
        if($draft_post_id) {
            //return
            return array('draft_post_id'=>$draft_post_id,'post'=>$post);
        }else{
        	//display error
        	wp_die('Failed to create draft post or page.');
        }
	}else{
        //display error
        wp_die('Failed to create post or page. The original post or page data was not found' . $post_id);
    }
}
/*
* Get all current post meta and set them to draft post or page.
*/
function sh_clone_post_meta($post_id,$draft_post_id){
    //get post meta
    $post_meta = get_post_meta($post_id);
    //loop meta
    foreach($post_meta as $key=>$val) {
        //check and skip
        if( $key == '_wp_old_slug' ) { continue; }
        //get value
        $value = addslashes($val[0]);
        // add post meta
        add_post_meta( $draft_post_id, $key, $value );
    }
}

/*
* Get all current post terms and set them to draft post or page.
*/
function sh_clone_post_taxonomies($post,$draft_post_id){
    //get original post taxonomies
    $taxonomies = get_object_taxonomies($post->post_type);
    //loop
    foreach ($taxonomies as $taxonomy) {
        //get
        $post_terms = wp_get_object_terms($post->ID, $taxonomy, array('fields' => 'slugs'));
        //set
        wp_set_object_terms($draft_post_id, $post_terms, $taxonomy, false);
    }
}

/*
* Process clone as draft post or page after clicking the clone link callback.
*/
function sh_clone_post_as_draft_callback(){
    global $wpdb;
    //check
    if (! (isset( $_REQUEST['post'])  || ( isset($_REQUEST['action']) && $_REQUEST['action'] == 'sh_clone_post_as_draft_callback' ) ) ) {
        wp_die('Copy posting is not offered!');
    }
    //Acquire original post ID
    $post_id = absint( $_REQUEST['post'] ) ;
    //check post id
    if($post_id > 0 ){
        //create a draft post
        $draft = sh_clone_draft_post($post_id);
        //Check out new post or page, created or not
        if($draft) {
            //Acquire original post
            $post = $draft['post'];
            //Acquire draft post
            $draft_post_id = $draft['draft_post_id'];
            //setup taxonomies
            sh_clone_post_taxonomies($post,$draft_post_id);
            //setup post meta
            sh_clone_post_meta($post_id,$draft_post_id);
			//redirect to the new draft's edit post screen			
			wp_redirect( admin_url( 'post.php?action=edit&post=' . $draft_post_id ) );
        }else{
            //display error
            wp_die('Failed to create draft post or page. The original post or page was not found' . $post_id);
        }
    }else {
        //display error
        wp_die('Failed to create post. The original post was not found' . $post_id);
    }
}

/*
* After clicking the clone link, initialize the administration action and clone the post or page.
*/
add_action( 'admin_action_sh_clone_post_as_draft_callback', 'sh_clone_post_as_draft_callback' );

