<?php
/*
Plugin Name: Available for Hire
Plugin URI: http://blog.alexgirard.com/#
Description: This is a clone of <a href="http://drnicwilliams.com">Dr Nic</a> Banner to tell you visitors you're available for hire
Author: Alexandre Girard
Version: 1.0
Author URI: http://blog.alexgirard.com/
*/

$hire_specialities = array(
	'php' => array('name' => 'PHP','url' => "http://php.net"),
	'wordpress' => array('name' => 'Wordpress','url' => "http://wordpress.org"),
	'ruby' => array('name' => 'Ruby','url' => "http://www.ruby-lang.org/"),
	'rails' => array('name' => 'Rails','url' => "http://www.rubyonrails.org/"),
	'python' => array('name' => 'Python','url' => "http://python.org"),
	'django' => array('name' => 'Django','url' => "http://www.djangoproject.com/"),
	'java' => array('name' => 'Java','url' => "http://java.com"),
	'iphone' => array('name' => 'iPhone','url' => "http://www.apple.com/iphone"),
	'lisp' => array('name' => 'LISP','url' => "http://lisp.org"),
	'erlang' => array('name' => 'Erlang','url' => "http://erlang.org")
);

function hire_config_page() {
	if ( function_exists('add_submenu_page') )
		add_options_page(__('Available for Hire'), __('Available for Hire'), 'manage_options', 'available-for-hire-config', 'hire_conf');
}

function hire_conf() {
	global $hire_specialities;

	if ( isset($_POST['submit']) ) {
		if ( function_exists('current_user_can') && !current_user_can('manage_options') )
			die(__('Cheatin&#8217; uh?'));

		check_admin_referer( $hire_nonce );
		
		update_option('hire_name', $_POST['hire_name']);
		update_option('hire_email', $_POST['hire_email']);
		
		$specialities = "";
		foreach ( $hire_specialities as $speciality ) :
			if (array_key_exists('speciality_'.strtolower($speciality['name']), $_POST)) {
				$specialities .= strtolower($speciality['name'])."+";
			}
		endforeach;
		
		// Remove trailing sign
		$specialities = substr($specialities, 0, -1);
		update_option('hire_specialities', $specialities);
	}
?>
<?php if ( !empty($_POST ) ) : ?>
<div id="message" class="updated fade"><p><strong><?php _e('Options saved.') ?></strong></p></div>
<?php endif; ?>
<div class="wrap">
<h2><?php _e('Available for Hire Configuration'); ?></h2>
<div class="narrow">
<form method="post" action="" id="hire-conf">

<?php wp_nonce_field($hire_nonce) ?>

<h3> General information</h3>
<table class="form-table">
	<tr valign="top">
	<th scope="row"><?php _e('Name'); ?></th>
	<td><input type="text" name="hire_name" value="<?php echo get_option('hire_name'); ?>" /></td>
	</tr>
	
	<tr valign="top">
	<th scope="row"><?php _e('Email'); ?></th>
	<td><input type="text" name="hire_email" value="<?php echo get_option('hire_email'); ?>" /></td>
	</tr>
</table>

<h3> Specialities</h3>
<p>Select the skills you want want to show in your banner.</p>

<table class="form-table">
	
	<tr valign="top">
	<?php
	$row = 0;
	foreach ( $hire_specialities as $speciality ) : ?>
		<td>
			<input type=checkbox name="speciality_<?php echo strtolower($speciality['name']); ?>"
			<?php if(ereg(".*".strtolower($speciality['name']).".*", get_option('hire_specialities'))) echo "CHECKED"; ?>
			>
			<?php echo $speciality['name']; ?><br>
			<img src="<?php echo get_option('home'); ?>/wp-content/plugins/hire/logos/<?php echo strtolower($speciality['name']); ?>.png">
		</td>
	<?php 
	$row += 1;
	if($row % 4 == 0) echo "</tr><tr valign='top'>";
	endforeach; ?>	
	</tr>
</table>

	<p class="submit"><input type="submit" name="submit" value="<?php _e('Update options &raquo;'); ?>" /></p>
</form>
</div>
</div>
<?php
}

function display_available_for_hire(){
	global $hire_specialities;
	
	// Display style
	echo '<style type="text/css">
	#banner {background:transparent url('.get_option('home').'/wp-content/plugins/hire/images/repeater.jpg) repeat-x scroll 0 0;bottom:0;margin:0;position:fixed;width:100%;color:#444444;font-family:"lucida grande",geneva,verdana,helvetica,arial,sans-serif;font-size:16px;text-align:center;font-size-adjust:none;font-style:normal;font-variant:normal;font-weight:normal;line-height:normal;}
	#banner, #banner .inner {height:78px;z-index:100;}
	#banner .right {float:right;padding:10px 20px;}
	banner .logos a {background-color:transparent;}
	#banner a {color:#F8F4D9;}
	#banner .logos img {border:0}
	#banner .logos img.full {height:55px;margin-left:1em;}
	#banner .inner {background:transparent url('.get_option('home').'/wp-content/plugins/hire/images/pattern.jpg) no-repeat scroll 0 0;color:#F6E9C9;font-family:"Lucida Grande","Trebuchet MS",Verdana,sans-serif;font-size:1.6em;padding-left:25px;}
	#banner .inner span {color:#FBFBD0;font-size:0.5em;margin-right:1em;}
	#banner .inner .aboveline {bottom:0;height:70px;position:fixed;}
	#banner .inner .belowline {bottom:0;height:38px;position:fixed;}
	</style>';

	// Display banner
	echo '<div id="banner">
		<div class="right">
			<span class="logos">';
			
	// Display skills logo
	$specialities = split("\+", get_option('hire_specialities'));
	
	foreach($specialities as $speciality) :
		echo '<a href="'.$hire_specialities[$speciality]['url'].'"><img src="'.get_option('home').'/wp-content/plugins/hire/logos/'.$speciality.'.png" alt="'.$hire_specialities[$speciality]['name'].' Logo" class="full" border="0"></a>';
	endforeach;
	echo '</span>
		</div>
		<div class="inner">
		  <div class="aboveline">
		    '.get_option('hire_name').' is available for Hire! 
		  </div>
			<div class="belowline">
				<span>Email <a href="mailto:'.get_option('hire_email').'?body=We+want+'.get_option('hire_name').'+for+our+project">'.get_option('hire_email').'</a></span>
			</div>
		</div>
	</div>';
}

function hire_install() {
	update_option('hire_name', get_profile("display_name", "admin"));
	update_option('hire_email', get_profile("user_email", "admin"));
}

function hire_uninstall() {
	delete_option('hire_name');
	delete_option('hire_email');
}

register_activation_hook(__FILE__, 'hire_install');
register_deactivation_hook(__FILE__, 'hire_uninstall');

add_action('admin_menu', 'hire_config_page');
add_action('wp_head', 'display_available_for_hire');
?>