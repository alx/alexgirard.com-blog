<?php
/*
Plugin Name: Feevy Widget
Plugin URI: http://www.feevy.com
Description: Adds a sidebar widget to display your feevy bar
Author: Las Indias - Alexandre Girard
Version: 1.0
Author URI: http://feevy.com
*/

// This gets called at the plugins_loaded action
function widget_feevy_init() {
	
	// Check for the required API functions
	if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') )
		return;

  function widget_feevy($args, $number = 1) {
  	extract($args);
		# Load blog.feevy.com code with white skin by default
		$defaults = array('code' => 18, 'style' => 'white');
		$options = (array) get_option('widget_feevy');

		foreach ( $defaults as $key => $value )
			if ( !isset($options[$number][$key]) )
				$options[$number][$key] = $defaults[$key];

		$feevy_url = 'http://www.feevy.com/code/' . $options[$number]['code'] . '/tags/porsmilin';
		$feevy_url.= '/' . $options[$number]['style'];
		?>
		<?php echo $before_widget; ?>
			<?php echo $before_title . "{$options[$number]['title']}" . $after_title; ?><div id="feevy-box" style="margin:0;padding:0;border:none;"> </div>
			<script type="text/javascript" src="<?php echo $feevy_url; ?>"></script>
		<?php echo $after_widget; ?>
  <?php
  }

  function widget_feevy_control($number) {
  	$options = $newoptions = get_option('widget_feevy');
  	if ( !is_array($options) )
  		$options = $newoptions = array();
  	if ( $_POST["feevy-submit-$number"] ) {
  	  $newoptions[$number]['title'] = strip_tags(stripslashes($_POST["feevy-title-$number"]));
  		$newoptions[$number]['code'] = strip_tags(stripslashes($_POST["feevy-code-$number"]));
		$newoptions[$number]['style'] = strip_tags(stripslashes($_POST["feevy-style-$number"]));
  	}
  	if ( $options != $newoptions ) {
  		$options = $newoptions;
  		update_option('widget_feevy', $options);
  	}
  	$title = wp_specialchars($options[$number]['title'], true);
  	$code = $options[$number]['code'];
  	$style = $options[$number]['style'];
  ?>
    <div style="text-align:right">
				<label for="feevy-title-<?php echo "$number"; ?>" style="line-height:35px;display:block;"><?php _e('Widget title:', 'widgets'); ?> <input type="text" id="feevy-title-<?php echo "$number"; ?>" name="feevy-title-<?php echo "$number"; ?>" value="<?php echo $title; ?>" /></label>
				<label for="feevy-code-<?php echo "$number"; ?>" style="line-height:35px;display:block;"><?php _e('Your feevy ID:', 'widgets'); ?> <input type="text" id="feevy-code-<?php echo "$number"; ?>" name="feevy-code-<?php echo "$number"; ?>" value="<?php echo $code; ?>" /></label>
				<label for="feevy-style-<?php echo "$number"; ?>" style="line-height:35px;display:block;"><?php _e('Select your feevy style:', 'widgets'); ?>
				<select name="feevy-style-<?php echo "$number"; ?>" id="feevy-style-<?php echo "$number"; ?>">
					<option value="dark" <?php if ($style == "dark") echo "SELECTED"; ?>>dark</option>
					<option value="white" <?php if ($style == "white") echo "SELECTED"; ?>>white</option>
					<option value="liquid" <?php if ($style == "liquid") echo "SELECTED"; ?>>liquid</option>
					<option value="open-css" <?php if ($style == "open-css") echo "SELECTED"; ?>>open-css</option>
				</select></label>
				<input type="hidden" name="feevy-submit-<?php echo "$number"; ?>" id="feevy-submit-<?php echo "$number"; ?>" value="1" />
		</div>
  <?php
  }

  function widget_feevy_setup() {
  	$options = $newoptions = get_option('widget_feevy');
  	if ( isset($_POST['feevy-number-submit']) ) {
  		$number = (int) $_POST['feevy-number'];
  		if ( $number > 9 ) $number = 9;
  		if ( $number < 1 ) $number = 1;
  		$newoptions['number'] = $number;
  	}
  	if ( $options != $newoptions ) {
  		$options = $newoptions;
  		update_option('widget_feevy', $options);
  		widget_feevy_register($options['number']);
  	}
  }

  function widget_feevy_page() {
  	$options = $newoptions = get_option('widget_feevy');
  ?>
  	<div class="wrap">
  		<form method="POST">
  			<h2><?php _e('Feevy Widgets', 'widgets'); ?></h2>
  			<p style="line-height: 30px;"><?php _e('How many Feevy widgets would you like?', 'widgets'); ?>
  			<select id="feevy-number" name="feevy-number" value="<?php echo $options['number']; ?>">
  <?php for ( $i = 1; $i < 10; ++$i ) echo "<option value='$i' ".($options['number']==$i ? "selected='selected'" : '').">$i</option>"; ?>
  			</select>
  			<span class="submit"><input type="submit" name="feevy-number-submit" id="feevy-number-submit" value="<?php _e('Save'); ?>" /></span></p>
  		</form>
  	</div>
  <?php
  }

  function widget_feevy_register() {
  	$options = get_option('widget_feevy');
  	$number = $options['number'];
  	if ( $number < 1 ) $number = 1;
  	if ( $number > 9 ) $number = 9;
  	for ($i = 1; $i <= 9; $i++) {
  		$name = array('Feevy %s', 'widgets', $i);
  		register_sidebar_widget($name, $i <= $number ? 'widget_feevy' : /* unregister */ '', $i);
  		register_widget_control($name, $i <= $number ? 'widget_feevy_control' : /* unregister */ '', 297, 210, $i);
  	}
  	add_action('sidebar_admin_setup', 'widget_feevy_setup');
  	add_action('sidebar_admin_page', 'widget_feevy_page');
  }
  
	widget_feevy_register();
}

// Delay plugin execution to ensure Dynamic Sidebar has a chance to load first
add_action('widgets_init', 'widget_feevy_init');

?>