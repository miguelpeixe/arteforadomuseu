<?php

/*
 * Arte Fora do Museu
 * Art guides
 */

class ArteForaDoMuseu_ArtGuides {

	var $post_type = 'art-guide';
	var $singular = false;
	var $artguide = false;

	function __construct() {
		$this->setup_post_type();
		$this->setup_marker_query();
		$this->setup_post();
		$this->setup_ajax();
		$this->hook_ui_elements();
	}

	function setup_post_type() {
		add_action('init', array($this, 'register_post_type'));
		add_action('mappress_mapped_post_types', array($this, 'remove_from_mappress_mapped'));
	}

	function register_post_type() {

		$labels = array( 
			'name' => __('Art guides', 'arteforadomuseu'),
			'singular_name' => __('Art guide', 'arteforadomuseu'),
			'add_new' => __('Add art guide', 'arteforadomuseu'),
			'add_new_item' => __('Add new art guide', 'arteforadomuseu'),
			'edit_item' => __('Edit art guide', 'arteforadomuseu'),
			'new_item' => __('New art guide', 'arteforadomuseu'),
			'view_item' => __('View art guide', 'arteforadomuseu'),
			'search_items' => __('Search art guides', 'arteforadomuseu'),
			'not_found' => __('No art guide found', 'arteforadomuseu'),
			'not_found_in_trash' => __('No art guide found in the trash', 'arteforadomuseu'),
			'menu_name' => __('Art guides', 'arteforadomuseu')
		);

		$args = array( 
			'labels' => $labels,
			'hierarchical' => false,
			'description' => __('Art guides', 'arteforadomuseu'),
			'supports' => array('title', 'editor', 'author', 'excerpt'),
			'public' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'has_archive' => true,
			'rewrite' => array('slug' => 'guides', 'with_front' => false)
		);

		register_post_type($this->post_type, $args);

	}

	function remove_from_mappress_mapped($post_types) {
		unset($post_types[$this->post_type]);
		return $post_types;
	}

	/*
	 * Marker query
	 */

	function setup_marker_query() {
		add_filter('mappress_marker_query', array($this, 'query'));
	}

	function query($query) {
		if(is_singular($this->post_type)) {
			global $post;
			$query = $this->get_query($post->ID);
		}
		return $query;
	}

	/*
	 * Store singular guide_id post
	 */

	function setup_post() {
		add_action('the_post', array($this, 'post'));
	}

	function post() {
		if(get_post_type(get_the_ID()) == $this->post_type) {
			global $post;
			$this->artguide = $post;
			if(is_singular($this->post_type))
				$this->singular = true;
		}
	}

	/*
	 * Ajax
	 */

	function setup_ajax() {
		add_action('wp_ajax_nopriv_add_artwork_to_guide', array($this, 'ajax_add_artwork'));
		add_action('wp_ajax_add_artwork_to_guide', array($this, 'ajax_add_artwork'));
		add_action('wp_ajax_nopriv_remove_artwork_from_guide', array($this, 'ajax_remove_artwork'));
		add_action('wp_ajax_remove_artwork_from_guide', array($this, 'ajax_remove_artwork'));
		add_action('wp_ajax_nopriv_delete_guide', array($this, 'ajax_delete'));
		add_action('wp_ajax_delete_guide', array($this, 'ajax_delete'));
	}

	function ajax_add_artwork() {

		$guide_id = false;

		if(!current_user_can('edit_posts'))
			$this->ajax_response(array('error_msg' => __('You are not allowed to do that', 'arteforadomuseu')));

		if(isset($_REQUEST['guide_action']) && $_REQUEST['guide_action'] == 'new') {

			if(isset($_REQUEST['title']) && !$_REQUEST['title'])
				$this->ajax_response(array('error_msg' => __('You need a title for your art guide', 'arteforadomuseu')));

			$guide_id = wp_insert_post(array('post_type' => $this->post_type, 'post_status' => 'publish', 'post_title' => $_REQUEST['title'], 'post_content' => $_REQUEST['description']));

			if(!$guide_id)
				$this->ajax_response(array('error_msg' => __('Something went wrong', 'arteforadomuseu')));
		}

		if(!$guide_id) {
			if(isset($_REQUEST['guide_id']) && !$_REQUEST['guide_id'])
				$this->ajax_response(array('error_msg' => __('You need to select an art guide', 'arteforadomuseu')));
			else
				$guide_id = $_REQUEST['guide_id'];
		}

		$artwork_id = $_REQUEST['artwork_id'];

		if($this->has_artwork($guide_id, $artwork_id))
			$this->ajax_response(array('error_msg' => __('This artwork is already part of the selected guide', 'arteforadomuseu')));

		$this->add_artwork($guide_id, $artwork_id);
		$this->ajax_response(array('success' => 1));
	}

	function ajax_remove_artwork() {
		if(!current_user_can('edit_post', $_REQUEST['guide_id']))
			$this->ajax_response(array('error_msg' => __('You are not allowed to do that', 'arteforadomuseu')));

		delete_post_meta($_REQUEST['guide_id'], '_artworks', $_REQUEST['artwork_id']);
		$this->ajax_response(array('success_msg' => __('Artwork has been removed from this art guide', 'arteforadomuseu')));
	}

	function ajax_delete() {
		if(!current_user_can('delete_post', $_REQUEST['guide_id']))
			$this->ajax_response(array('error_msg' => __('You are not allowed to do that', 'arteforadomuseu')));

		wp_delete_post($_REQUEST['guide_id']);
		$this->ajax_response(array('success_msg' => __('Art guide has been deleted', 'arteforadomuseu')));
	}

	function ajax_response($data) {
		header('Content Type: application/json');
		echo json_encode($data);
		exit;
	}

	/*
	 * Functions
	 */

	function get_query($guide_id = false, $query = false) {
		if(!$query)
			$query = array();

		if(!$guide_id) {
			global $post;
			$guide_id = $post->ID;
		}

		$query['post_type'] = 'any';
		$query['post__in'] = $this->get_artworks_id($guide_id);

		return $query;
	}

	function get_image_mosaic($guide_id = false) {
		global $post;
		$guide_id = $guide_id ? $guide_id : $post->ID;

		$artworks = get_post_meta($guide_id, '_artworks');
		$image_ids = array();
		if($artworks) {
			$i = 0;
			foreach($artworks as $artwork) {
				if($i >= 3)
					continue;
				if(has_post_thumbnail($artwork)) {
					$image_ids[$i] = get_post_thumbnail_id($artwork);
				} else {
					$images = get_posts(array('post_type' => 'attachment', 'post_status' => 'any', 'post_parent' => $artwork));
					if($images) {
						$image_ids[$i] = array_shift($images)->ID;
					}
				}
				$i++;
			}
			$mosaic = '<div class="artwork-mosaic images-' . count($image_ids) . ' clearfix">';
			$i = 1;
			foreach($image_ids as $image_id) {
				$image_src = wp_get_attachment_image_src($image_id, 'thumbnail');
				$mosaic .= '<div class="image-' . $i . '-container image-container">';
				$mosaic .= '<a href="' . get_permalink($guide_id) . '"><img src="' . $image_src[0] . '" class="image-' . $i . '" /></a>';
				$mosaic .= '</div>';
				$i++;
			}
			$mosaic .= '</div>';
			return $mosaic;
		}
		return false;
	}

	function from_user($user_id = false) {
		$user_id = $user_id ? $user_id : wp_get_current_user()->ID;
		return get_posts(array('post_type' => $this->post_type, 'author' => $user_id, 'posts_per_page' => -1));
	}

	function add_artwork($guide_id, $artwork_id = false) {

		global $post;
		$artwork_id = $artwork_id ? $artwork_id : $post->ID;

		if(!$artwork_id || !$guide_id)
			return false;

		if(!current_user_can('edit_post', $guide_id))
			return false;

		if($this->has_artwork($guide_id, $artwork_id))
			return false;

		return add_post_meta($guide_id, '_artworks', $artwork_id);
	}

	function get_artworks_id($guide_id) {
		$ids = get_post_meta($guide_id, '_artworks');

		if(!$ids)
			$ids = array(0);

		return $ids;
	}

	function get_artwork_count($guide_id) {
		global $post;
		$guide_id = $guide_id ? $guide_id : $post->ID;
		return count(get_post_meta($guide_id, '_artworks'));
	}

	function has_artwork($guide_id, $artwork_id) {
		$ids = $this->get_artworks_id($guide_id);
		if(in_array($artwork_id, $ids)) {
			return true;
		} else {
			return false;
		}
	}


	/*
	 * UI
	 */

	function hook_ui_elements() {
		add_action('wp_footer', array($this, 'add_artwork_box'));
		add_action('afdm_before_artwork_header', array($this, 'hook_remove_artwork_button'));
		wp_enqueue_script('artguides', get_stylesheet_directory_uri() . '/inc/artguides.js', array('jquery'), '0.0.8');
		wp_localize_script('artguides', 'artguides', array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'confirm_delete' => __('Are you sure?', 'arteforadomuseu')
		));
	}

	function add_artwork_button($artwork_id = false) {
		global $post;
		$artwork_id = $artwork_id ? $artwork_id : $post->ID;
		if(!current_user_can('edit_posts'))
			return false;
		?>
		<a class="add_artwork" data-artwork="<?php echo $artwork_id; ?>" data-artwork-title="<?php echo get_the_title($artwork_id); ?>" href="#"><span class="lsf">addnew</span> <?php _e('Add to art guide', 'arteforadomuseu'); ?></a>
		<?php
	}

	function hook_remove_artwork_button() {
		if($this->singular) {
			global $post;
			$this->remove_artwork_button($this->artguide->ID, $post->ID);
		}
	}

	function remove_artwork_button($guide_id, $artwork_id = false) {
		global $post;
		$artwork_id = $artwork_id ? $artwork_id : $post->ID;
		if(!current_user_can('edit_post', $guide_id))
			return false;
		?>
		<form id="remove_artwork">
			<input type="hidden" name="guide_id" value="<?php echo $guide_id; ?>" />
			<input type="hidden" name="artwork_id" value="<?php echo $artwork_id; ?>" />
			<button title="<?php _e('Remove this artwork from the art guide', 'arteforadomuseu'); ?>"><span class="lsf">remove</span></button>
		</form>
		<?php
	}

	function delete_button($guide_id = false) {
		global $post;
		$guide_id = $guide_id ? $guide_id : $post->ID;
		if(!current_user_can('delete_post', $guide_id))
			return false;
		?>
		<form id="delete_artguide">
			<input type="hidden" name="guide_id" value="<?php echo $guide_id; ?>" />
			<button class="remove" title="<?php _e('Permanently delete this art guide', 'arteforadomuseu'); ?>"><span class="lsf">remove</span> <?php _e('Delete', 'arteforadomuseu'); ?></button>
		</form>
		<?php
	}

	function add_artwork_box() {
		$user_guides = $this->from_user();
		?>
		<div id="add_artwork" class="add_artwork_container lightbox_section">
			<div class="close-area close-box"></div>
			<div class="lightbox_content">
				<h2><span class="lsf">addnew</span> <?php printf(__('Add &ldquo;%s&rdquo; to:', 'arteforadomuseu'), '<span class="title"></span>'); ?></h2>
				<?php if($user_guides) : ?>
					<form id="add_to_existing_guide" class="clearfix">
						<input type="hidden" class="artwork_id" name="artwork_id" />
						<div class="form-inputs">
							<h3><?php _e('My art guides', 'arteforadomuseu'); ?></h3>
							<select class="user-guides" name="guide_id">
								<?php foreach($user_guides as $guide) : ?>
									<option value="<?php echo $guide->ID; ?>"><?php echo get_the_title($guide->ID); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="form-actions">
							<input type="submit" value="<?php _e('Add artwork', 'arteforadomuseu'); ?>" />
							<a class="close-box" href="#"><?php _e('Cancel', 'arteforadomuseu'); ?></a>
						</div>
					</form>
				<?php endif; ?>
				<form id="add_to_new_guide" class="clearfix">
					<input type="hidden" name="guide_action" value="new" />
					<input type="hidden" class="artwork_id" name="artwork_id" />
					<div class="form-inputs">
						<h3><?php _e('New art guide', 'arteforadomuseu'); ?></h3>
						<input type="text" name="title" class="title" placeholder="Title" />
						<textarea name="description" placeholder="Description"></textarea>
					</div>
					<div class="form-actions">
						<input type="submit" value="<?php _e('Create and add artwork', 'arteforadomuseu'); ?>" />
						<a class="close-box" href="#"><?php _e('Cancel', 'arteforadomuseu'); ?></a>
					</div>
				</form>
			</div>
		</div>
		<?php
	}
}

$artguide = new ArteForaDoMuseu_ArtGuides;

function afdm_artguides_add_artwork($guide_id, $artwork_id = false) {
	global $artguide;
	return $artguide->add_artwork($artwork_id, $guide_id);
}

function afdm_artguides_artwork_button($artwork_id = false) {
	global $artguide;
	return $artguide->add_artwork_button($artwork_id);
}

function afdm_get_user_artguides($user_id = false) {
	global $artguide;
	return $artguide->from_user($user_id);
}

function afdm_get_artguide_query($guide_id = false) {
	global $artguide;
	return $artguide->get_query($guide_id);
}

function afdm_get_artguide_artwork_count($guide_id = false) {
	global $artguide;
	return $artguide->get_artwork_count($guide_id);
}

function afdm_get_artguide_mosaic($guide_id = false) {
	global $artguide;
	return $artguide->get_image_mosaic($guide_id);
}

function afdm_get_artguide_delete_button($guide_id = false) {
	global $artguide;
	return $artguide->delete_button($guide_id);
}