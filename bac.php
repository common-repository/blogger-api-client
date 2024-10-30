<?php
/*
Plugin Name: Blogger API Client
Plugin URI: http://ryanlee.org/software/wp/bac/
Description: Re-posts published posts to anything that implements the Blogger API (such as <a href="http://www.blogger.com/">Blogger</a> itself or <a href="http://www.drupal.org/">Drupal</a>) with directions back to the original Wordpress blog for updates and comments.  Also edits and deletes according to WordPress' actions.  BAC is based on <a href="http://ryanlee.org/software/wp/croissanga/">Croissanga</a> and the <a href="http://www.dentedreality.com.au/bloggerapi/class/">bloggerapi class</a> (made available by Beau Lebens).  This plugin was produced as part of <a href="http://dig.csail.mit.edu/" title="Decentralized Information Group">DIG</a>'s infrastructure, a member of MIT's <a href="http://www.csail.mit.edu/" title="Computer Science and Artificial Intelligence Laboratory">CSAIL</a>.  Licensed under BSD license at end of file; please attribute.  Please see the README before activating this plugin, other steps are required.
Version: 0.2
Author: Ryan Lee
Author URI: http://ryanlee.org/
*/

include_once("bac/ixr.bloggerclient.php");

$bac_username = ""; 	// your username at your Blogger instance
$bac_password = ""; 	// and your password
$bac_server   = "";     // the server receiving your crossposts
$bac_path     = "";     // the path to the xmlrpc engine
$bac_key      = "";     // unnecessary except on blogger.com, where the
                        // value is 0123456789ABCDEF
$bac_blogid   = "";     // specify which blog if you have several under one
                        // username; possible on blogger.com

// example using IDs 1 and 2: $bac_category = array("1", "2");
$bac_category = array();  	// if not empty, then only post to Blogger when
				// the post is categorized with an ID in the
				// array, ignore any others

function bac_map_set_blogger_id($postID, $bloggerID) {
	global $wpdb;
	$insert_dml = "INSERT INTO bac_wp_post_map (post_ID, blogger_ID) VALUES ($postID, '$bloggerID')";
	$rowcount = $wpdb->query($insert_dml);
	if ($rowcount == 1) {
		// successful insert
		return true;
	} else {
		// failure
		return false;
	}
}

function bac_map_blogger_id_exists($postID) {
	global $wpdb;
	$query = "SELECT COUNT(*) FROM bac_wp_post_map WHERE post_ID = $postID";
	$exists = $wpdb->get_var($query);
	if ($exists == 1) {
		return true;
	} else {
		return false;
	}
}

function bac_map_get_blogger_id($postID) {
	global $wpdb;
	$query = "SELECT blogger_ID FROM bac_wp_post_map WHERE post_ID = $postID";
	$bloggerID = $wpdb->get_var($query);
	return $bloggerID;
}

function bac_map_delete_map($postID) {
	global $wpdb;
	$delete_dml = "DELETE FROM bac_wp_post_map WHERE post_ID = $postID";
	$rowcount = $wpdb->query($delete_dml);
	if ($rowcount == 1) {
		// successful deletion
		return true;
	} else {
		// failure
		return false;
	}
}

function bac_post_action($action, $ID) {
	global $post, $bac_server, $bac_path, $bac_username, $bac_password, $bac_category, $bac_key, $bac_blogid;
	$blog = new bloggerclient($bac_server, $bac_path, $bac_key, $bac_username, $bac_password);

	if ($action == 'delete') {
		if (!bac_map_blogger_id_exists($ID)) {
			return;
		}
		$bac_ID = bac_map_get_blogger_id($ID);
		$blog->deletePost($bac_ID, "true");
		bac_map_delete_map($ID);
		return;
	}

	query_posts('p=' . $ID);
	the_post();
	$category_match = false;
	if (count($bac_category) == 0) {
		$category_match = true;
	}
	for ($i = 0; !$category_match && $i < count($bac_category); $i++) {
		if (in_category($bac_category[$i])) {
			$category_match = true;
		}
	}
	if (!$category_match) return;
	$head = "<span style=\"display: none\">" . the_title('', '', false) . "</span>\n";
	$head .= "<p><em>This entry was <a href=\"" . get_permalink($ID) . "\">originally published</a>";
	$head .= " at <a href=\"" . get_settings('home') . "\">" . get_settings('blogname') . "</a></em></p>\n\n";
	$content = get_the_content();
	$content = apply_filters('the_content', $content);
	$content = str_replace(']]>', ']]>', $content);
	//!!!uncomment for urlparse support
	//$content = urlparse_external_links($content, $ID);
	$entry = $head . $content;
	if ($action == 'edit') {
		if (!bac_map_blogger_id_exists($ID)) {
			return;
		}
		$bac_ID = bac_map_get_blogger_id($ID);
		$blog->editPost($bac_ID, $entry, true);
	} elseif ($action == 'post') {
		$bac_ID = $blog->newPost($bac_blogid, $entry, true);
		bac_map_set_blogger_id($ID, $bac_ID);
	}
}

// new post
function bac_post_entry($ID) {
	bac_post_action('post', $ID);
	return $ID;
}

// edit a post already existing in Blogger
function bac_edit_post($ID) {
	bac_post_action('edit', $ID);
	return $ID;
}

function bac_delete_post($ID) {
	bac_post_action('delete', $ID);
	return $ID;
}

// if edited and already published:
//   - check if still in right category, remote-delete if not
function bac_edit_dispatch($ID) {
	global $wpdb, $bac_category;
	$deleted = false;
	if ($wpdb->get_var("SELECT post_password FROM $wpdb->posts WHERE id = '$ID';") != "") {
		if (bac_map_blogger_id_exists($ID)) {
			bac_delete_post($ID);
			$deleted = true;
		}
	}
	if ($wpdb->get_var("SELECT post_status FROM $wpdb->posts WHERE id = '$ID';") != "publish" && !$deleted) {
		if (bac_map_blogger_id_exists($ID)) {
			bac_delete_post($ID);
			$deleted = true;
		}
	}
	if (!$deleted) {
		query_posts('p=' . $ID);
		the_post();
		$category_match = false;
		if (count($bac_category) == 0) {
			$category_match = true;
		}
		for ($i = 0; !$category_match && $i < count($bac_category); $i++) {
			if (in_category($bac_category[$i])) {
				$category_match = true;
			}
		}
		if (!$category_match && bac_map_blogger_id_exists($ID)) {
			bac_delete_post($ID);
		}
	}

	return $ID;
}

function bac_dispatch($ID) {
	global $wpdb;

        if ($wpdb->get_var("SELECT post_password FROM $wpdb->posts WHERE id = '$ID';") == "") {
		if (bac_map_blogger_id_exists($ID)) {
			bac_edit_post($ID);
		} else {
			bac_post_entry($ID);
		}
	}

	return $ID;
}

add_action('publish_post', 'bac_dispatch', 9);
add_action('delete_post', 'bac_delete_post');
add_action('edit_post', 'bac_edit_dispatch');

/* License (BSD)
Copyright (c) 2005, Ryan Lee
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
    * Neither the name of the ryanlee.org nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

?>
