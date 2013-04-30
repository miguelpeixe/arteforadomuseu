<?php

// Art Guide
require_once(STYLESHEETPATH . '/inc/artguides.php');

function afdm_scripts() {
	wp_enqueue_style('afdm-main', get_stylesheet_directory_uri() . '/css/main.css');
}
add_action('wp_enqueue_scripts', 'afdm_scripts', 100);

function afdm_marker_extent() {
	return true;
}
add_action('mappress_use_marker_extent', 'afdm_marker_extent');

add_filter('show_admin_bar', '__return_false');

function afdm_prevent_admin_access() {
	if (!current_user_can('edit_others_posts') && !defined('DOING_AJAX')) {
		wp_redirect(home_url());
		exit();
	}
}
add_action('admin_init', 'afdm_prevent_admin_access', 0);

function afdm_get_user_menu() {
	?>
	<div class="user-meta">
		<?php
		if(!is_user_logged_in()) :
			?>
			<span class="dropdown-title login"><span class="lsf">login</span> <?php _e('Login', 'arteforadomuseu'); ?></span>
			<div class="dropdown-content">
				<div class="login-content">
					<p><?php _e('Login with: ', 'arteforadomuseu'); ?></p>
					<?php do_action('oa_social_login'); ?>
				</div>
			</div>
			<?php
		else :
			?>
			<span class="dropdown-title login"><span class="lsf">user</span> <?php _e('Account', 'arteforadomuseu'); ?></span>
			<div class="dropdown-content">
				<div class="logged-in">
					<p><?php _e('Hello', 'arteforadomuseu'); ?>, <?php echo wp_get_current_user()->display_name; ?>. <a class="logout" href="<?php echo wp_logout_url(home_url()); ?>" title="<?php _e('Logout', 'arteforadomuseu'); ?>"><?php _e('Logout', 'arteforadomuseu'); ?> <span class="lsf">logout</span></a></p>
					<?php if(current_user_can('edit_posts')) : ?>
						<ul class="user-actions">
							<?php if(afdm_get_user_artguides()) : ?>
								<li><a href="<?php echo home_url('/guides/?author=' . wp_get_current_user()->ID); ?>"><?php _e('My art guides', 'arteforadomuseu'); ?></a></li>
							<?php endif; ?>
							<li><a href="#"><?php _e('Create an art guide', 'arteforadomuseu'); ?></a></li>
							<li><a href="#"><?php _e('Submit an artwork', 'arteforadomuseu'); ?></a></li>
							<?php if(current_user_can('edit_others_posts')) : ?>
								<li><a href="<?php echo get_admin_url(); ?>"><?php _e('Administration', 'arteforadomuseu'); ?></a></li>
							<?php endif; ?>
						</ul>
					<?php endif; ?>
				</div>
			</div>
			<?php
		endif;
	?>
	</div>
	<?php
}