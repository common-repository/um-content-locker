<?php

/*

Plugin name: Content Locker for Ultimate Member
Description: This plugin will help you to lock content by shortcodes
Author: Md. Sarwar-A-Kawsar
Author URI: https://fiverr.com/sa_kawsar
Version: 1.0

*/

defined('ABSPATH') or die('You cannot access to this page');

add_action('wp_enqueue_scripts','clum_enqueue_script');
function clum_enqueue_script(){
	wp_enqueue_script( 'jquery' );
}

add_shortcode( 'content_locker', 'clum_content_locker_callback' );
function clum_content_locker_callback( $atts, $content = null ){
	ob_start();
		// get the post ID
		global $post;
		$post_id = $post->ID;
		// get the user ID
		$user_id = get_current_user_id();
		// get current date
		$date = md5(current_time( 'd-m-y' ));
		// get user data 
		$today_contents = get_user_meta( $user_id, 'today_contents', true );
		// update the dataset
		if($today_contents['date'] <> $date):
			update_user_meta( $user_id, 'today_contents', array( 'date' => '' , 'dataset' => array() ) );
		endif;
		// trigger to unlock
		if(isset($_POST['unlock_content'])):
			// get update data
			$data = get_user_meta( $user_id, 'today_contents', true );
			// get package data
			$package_slug = clum_get_current_user_level();
			$packages = get_option( 'cl_packages' );
			$package = 0;
			// 
			if($package_slug && $package_slug<>"" && !empty($packages)):
				$package = $packages[$package_slug];
			endif;

			if( count($data['dataset']) < $package ):
				$data['date'] = md5(current_time( 'd-m-y' ));
				$data['dataset'][] = sanitize_text_field( $_POST['post_id'] );
				update_user_meta( $user_id, 'today_contents', $data );
			else:
				echo esc_html( '<div class="alert alert-danger">You daily limit exceeded.</div>' );
			endif;
		endif;
		// get updated content
		$today_contents = get_user_meta( $user_id, 'today_contents', true );
		// check if content exists
		if(!empty($today_contents) && in_array($post_id, $today_contents['dataset'])):
			echo $content;
		else:
			?>
			<form method="post">
				<input type="hidden" value="<?php echo $post_id; ?>" name="post_id"/>
				<button type="submit" 
				style="display: inline-block;
			    color: #fff;
			    background-color: #5cb85c;
			    border-color: #4cae4c;
			    padding: 6px 12px;
			    margin-bottom: 0;
			    font-size: 14px;
			    font-weight: 400;
			    line-height: 1.42857143;
			    text-align: center;
			    white-space: nowrap;
			    vertical-align: middle;
			    -ms-touch-action: manipulation;
			    touch-action: manipulation;
			    cursor: pointer;
			    -webkit-user-select: none;
			    -moz-user-select: none;
			    -ms-user-select: none;
			    user-select: none;
			    background-image: none;
			    border: 1px solid transparent;
			    border-radius: 4px;" 
				name="unlock_content" />Unlock  <span class="glyphicon glyphicon-download-alt"></span></button>
				<!-- <span class="glyphicon glyphicon-search"></span> -->
			</form>
			<?php
		endif;
	return ob_get_clean();
}

// Admin section
add_action( 'admin_menu', 'clum_custom_admin_menu' );
function clum_custom_admin_menu(){
	add_menu_page( 'Content Locker', 'Content Locker', 'manage_options', 'content_locker', 'clum_content_locker_menu_callback');
}

function clum_content_locker_menu_callback(){

	if(isset($_POST['delete_package']) && isset($_POST['nonce_delete_package']) && wp_verify_nonce( 'nonce_delete_package', 'cl_delete_package' ) && current_user_can( 'administrator' )):
		$package_slug = sanitize_text_field( $_POST['package_slug'] );
		$cl_packages = get_option( 'cl_packages' );
		unset($cl_packages[$package_slug]);
		update_option( 'cl_packages', $cl_packages );
		echo esc_html( '<div class="notice notice-success is-dismissible">
         <p>Package has been removed!</p>
     </div>' );
	endif;

	if(isset($_POST['add_package']) && isset($_POST['nonce_add_package']) && wp_verify_nonce( 'nonce_add_package', 'cl_add_package' ) && current_user_can( 'administrator' )):
		$package_name = sanitize_text_field( $_POST['package_name'] );
		$package_limit = sanitize_text_field( $_POST['package_limit'] );
		$cl_packages = get_option( 'cl_packages' );
		if($cl_packages):
			$md5 = $package_name;
			$cl_packages[$md5] = $package_limit;
			update_option( 'cl_packages', $cl_packages );
		else:
			$packages = [];
			$md5 = $package_name;
			$packages[$md5] = $package_limit;
			update_option( 'cl_packages', $packages );
		endif;
		echo esc_html( '<div class="notice notice-success is-dismissible">
         <p>New package added!</p>
     </div>' );
	endif;

	?>
    <h2>Add Package</h2>
    <?php $packages_level = get_option('ihc_levels'); 
    ?>
    <table class="form-table"><form method="post">
        <tr>
            <th>
                <label for="">Package name</label>
            </th>
            <td>
            	<select name="package_name">
            		<?php 
      	      		foreach($packages_level as $cl_key => $cl_value ):
            		?>
            		<option value="<?php echo $cl_key; ?>"><?php echo $cl_value['label']; ?></option>
            	<?php endforeach; ?>
            	</select>
            </td>
        </tr>

        <tr>
            <th>
                <label for="">Package limit</label>
            </th>
            <td>
            	<input type="number" name="package_limit" placeholder="Package limit" />
            </td>
        </tr>

        <tr>
            <th>
            	<input type="hidden" name="nonce_add_package" value="<?php echo wp_create_nonce( 'cl_add_package' ); ?>"/>
                <input type="submit" name="add_package" class="button button-primary" value="Add package"/>
            </th>
        </tr></form>
    </table>
    <style type="text/css">
    	.country-table td,th{
    		padding: 8px;
    	}
    </style>
<h2>Packages</h2>
<?php

$cl_packages = get_option( 'cl_packages' );
?>
<table class="country-table" border="1">
	<tr>
		<th>SL</th>
		<th>Package name</th>
		<th>Package limit</th>
		<th>Action</th>
	</tr>
	<?php 
	if($cl_packages):
		$i=0;
		$package_levels = get_option( 'ihc_levels' );
		foreach ($cl_packages as $key => $value):
			$i++;
	?>
	<tr>
		<td><?php echo $i; ?></td>
		<td>
			<?php 
				echo $package_levels[$key]['label'];
			?>
		</td>
		<td><?php echo $value; ?></td>
		<td>
			<form method="post">
    			<input type="hidden" value="<?php echo $key; ?>" name="package_slug" />
    			<input type="hidden" name="nonce_delete_package" value="<?php echo wp_create_nonce( 'cl_delete_package' ); ?>" />
    			<input type="submit" class="button button-primary" name="delete_package" value="Delete"/>
			</form>
		</td>
	</tr>
	<?php
	endforeach; endif;
	?>
</table>
<?php
}

function clum_get_current_user_level(){
	$user_id = get_current_user_id();
	$levels = get_user_meta( $user_id, 'ihc_user_levels', true );
	if($levels):
		$max = max(explode(',',$levels));
		return $max;
	else:
		return 0;
	endif;
}