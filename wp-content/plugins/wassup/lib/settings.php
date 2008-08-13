<?php
  //# settings.php -- a wassup.php include file for changing Wassup default 
  //#   settings in the Wassup options menu
?>
<?php
	if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
		if (!empty($_POST['delete_manual'])) {
			$to_date = wassup_get_time();
			$from_date = @strtotime($_POST['delete_manual'], $to_date);
			$wpdb->query("DELETE FROM $table_name WHERE timestamp<'$from_date'");
			$wpdb->query("OPTIMIZE TABLE $table_name");
		} 
		if (!empty($_POST['wassup_empty'])) {
			$wpdb->query("DELETE FROM $table_name");
			$wpdb->query("OPTIMIZE TABLE $table_name");
		}
		$table_status = $wpdb->get_results("SHOW TABLE STATUS LIKE '$table_name'");
		foreach ($table_status as $fstatus) {
			$data_lenght = $fstatus->Data_length;
			$data_rows = $fstatus->Rows;
			$table_engine = (isset($fstatus->Engine)? $fstatus->Engine: 'unknown');
		}
		$tusage = number_format(($data_lenght/1024/1024), 2, ",", " ");
		$tusage2 = ($data_lenght/1024/1024);
	}
	
	$adminemail = get_bloginfo('admin_email');

	$alert_msg = "";
	if ($wassup_options->wassup_remind_flag == 2) {
		$alert_msg = '<p style="color:red;font-weight:bold;">'.__('ATTENTION! Your WassUp table have reached the maximum value you set, I disabled the alert, you can re-enable it here.','wassup').'</p>';
		$wassup_options->wassup_remind_flag = 0;
		$wassup_options->saveSettings();
	}
	$alertstyle = 'color:red; background-color:#ffd;';
	?>
	<style type="text/css">
	  h3 { margin-bottom:0px; padding-bottom:5px; color:#333; }
          form p { margin-top:0px; padding-top:0px; padding-left:15px; }
	<?php if (version_compare($wp_version, '2.5', '<')) { ?>
	  #wassup_opt_frag-1, #wassup_opt_frag-2, 
	  #wassup_opt_frag-3, #wassup_opt_frag-4 { 
		background-color: #e6eff6; 
		border-left: 1px solid #cce; 
		border-right: 1px solid #cce; 
		border-bottom: 1px solid #cce;
		font-size: 85%;
	  }
	  #tab_container { list-style:none; }
	  #tab_container ul li { min-width:60px; display:inline;}
	  #tab_container ul li a span { font-size: 105%; }
	<?php } else { ?>
	  #wassup_opt_frag-1 { height: 100%; margin:0;}
	  #wassup_opt_frag-2 { height: 100%; margin:0;}
	  #wassup_opt_frag-3 { height: 100%; margin:0;}
	  #wassup_opt_frag-4 { height: 100%; margin:0;}
	<?php } //end if version_compare ?>
	</style>
	<h2><?php _e('Options','wassup'); ?></h2>
	<p><?php _e('You can add a sidebar Widget with some useful statistics information by activating the','wassup'); ?>
	   <a href="/wp-admin/widgets.php"><?php _e('Wassup Widget in the Widgets menu option','wassup'); ?></a>.</p>
	<p style="padding:10px 0 10px 0;"><?php _e('Select the options you want for the WassUp plugin','wassup'); ?></p>

	<?php
	if (!empty($_GET['tab']) && is_numeric($_GET['tab'])) { $tab = $_GET['tab']; }
	else { $tab = "0"; } ?>
	<form action="" method="post">
	<div id="tabcontainer">
	    <ul style="list-style:none;">
                <li><a href="#wassup_opt_frag-1"><span><?php _e("General Setup", "wassup") ?></span></a></li>
		<li<?php if ($tab == "2"  || isset($_POST['submit-options2'])) { echo ' class="ui-tabs-selected"';} ?>><a href="#wassup_opt_frag-2"><span><?php _e("Statistics Recording", "wassup") ?></span></a></li>
                <li<?php if ($tab == "3"  || isset($_POST['submit-options3'])) { echo ' class="ui-tabs-selected"';} ?>><a href="#wassup_opt_frag-3"><span><?php _e("Manage Files & Database", "wassup") ?></span></a></li>
                <li<?php if ($tab == "4"  || isset($_POST['submit-options4'])) { echo ' class="ui-tabs-selected"';} ?>><a href="#wassup_opt_frag-4"><span><?php _e("Uninstall", "wassup") ?></span></a></li>
            </ul>

	   <div id="wassup_opt_frag-1">
		<h3><?php _e('Your default screen resolution (browser width)','wassup'); ?></h3>
		<p><strong><?php _e('Default screen resolution (in pixels)','wassup'); ?></strong>:
		<select name='wassup_screen_res' style="width: 90px;">
		<?php $wassup_options->showFormOptions("wassup_screen_res"); ?>
	        </select>
	        </p>
		<br /><h3><?php _e('Set minimum users level which can view and manage WassUp plugin (default Administrators)','wassup'); ?></h3>
		<p><select name="wassup_userlevel">
		<?php $wassup_options->showFormOptions("wassup_userlevel"); ?>
		</select></p><br />

		<br /><h3><?php _e('Dashboard Settings','wassup'); ?></h3>
		<p><input type="checkbox" name="wassup_dashboard_chart" value="1" <?php if($wassup_options->wassup_dashboard_chart == 1) print "CHECKED"; ?> /> <strong><?php _e('Display small chart in the dashboard','wassup'); ?></strong>
		</p><br />

		<br /><h3><?php _e('Time format','wassup'); ?></h3>
		<p>12h <input type="radio" name="wassup_time_format" value="12" <?php if($wassup_options->wassup_time_format == 12) print "CHECKED"; ?> /> - 24h <input type="radio" name="wassup_time_format" value="24" <?php if($wassup_options->wassup_time_format == 24) print "CHECKED"; ?> /> <strong><?php _e('Time format 12/24 hour','wassup'); ?></strong>
		</p><br />

		<br /><h3><?php _e('Visit Detail Settings','wassup'); ?></h3>
		<p><strong><?php _e('Chart type - How many axes','wassup'); ?></strong>:
		<select name='wassup_chart_type'>
		<?php $wassup_options->showFormOptions("wassup_chart_type"); ?>
		</select>
		</p><br />
		<p>
		<strong><?php echo __('Set how many minutes wait for automatic page refresh','wassup').'</strong> ('.__('Current Visitors Online and Visitors Details','wassup').'):'; ?>
		<input type="text" name="wassup_refresh" size="2" value="<?php print $wassup_options->wassup_refresh; ?>" /> <?php _e('refresh minutes (default 3)','wassup'); ?></p><br />

		<p><strong><?php _e('Show visitor details for','wassup'); ?></strong>:
		<select name='wassup_default_type'>
		<?php $wassup_options->showFormOptions("wassup_default_type"); ?>
		</select>
		</p><br />
		<p><strong><?php _e('Number of items per page','wassup'); ?></strong>:
		<select name='wassup_default_limit'>
		<?php $wassup_options->showFormOptions("wassup_default_limit"); ?>
		</select>
		</p><br />
		<?php
		//TODO: Make Top 10 Customizable with up to 10 choices
		$top_ten = unserialize($wassup_options->wassup_top10);
		?>
		<br /><h3><?php _e('Customize Top Ten List','wassup'); ?></h3>
		<p style="margin-top:5px;"> <strong> <?php _e("Choose one or more items for your Top Ten list", "wassup"); ?></strong> (<?php _e("over 5 selections may cause horizontal scrolling","wassup"); ?>):<br />
		<div style="padding-left:25px;padding-top:0;margin-top:0;display:block;clear:left;">
		<div style="display:block; vertical-align:top; float:left; width:225px;">
	        <input type="checkbox" name="topsearch" value="1" <?php if($top_ten['topsearch'] == 1) print "CHECKED"; ?> /><?php _e("Top Searches", "wassup"); ?><br />
	        <input type="checkbox" name="topreferrer" value="1" <?php if($top_ten['topreferrer'] == 1) print "CHECKED"; ?> /><?php _e("Top Referrers", "wassup"); ?><br />
		<input type="checkbox" name="toprequest" value="1" <?php if($top_ten['toprequest'] == 1) print "CHECKED"; ?> /><?php _e("Top Requests", "wassup"); ?><br />
		</div>
		<div style="display:block; vertical-align:top; float:left; width:225px;">
	        <input type="checkbox" name="topbrowser" value="1" <?php if($top_ten['topbrowser'] == 1) print "CHECKED"; ?> /><?php _e("Top Browsers", "wassup"); ?> <br />
		<input type="checkbox" name="topos" value="1" <?php if($top_ten['topos'] == 1) print "CHECKED"; ?> /><?php _e("Top OS", "wassup"); ?> <br />
	        <input type="checkbox" name="toplocale" value="1" <?php if($top_ten['toplocale'] == 1) print "CHECKED"; ?> /><?php _e("Top Locales", "wassup"); ?></span><br />
		</div>
		<div style="vertical-align:top; float:left; width:225px;">
	        <input type="checkbox" name="topvisitor" value="1" <?php if($top_ten['topvisitor'] == 1) print "CHECKED"; ?> /><?php _e("Top Visitors", "wassup"); ?><br /><!-- 
	        <input type="checkbox" name="topfeed" value="1" DISABLED /><?php _e("Top Feeds", "wassup"); ?><br />
	        <input type="checkbox" name="topcrawler" value="1" DISABLED /><?php _e("Top Crawlers", "wassup"); ?> --><br />
		</div>
		</div>
		</p>
		<p style="margin-top:5px; clear:left;"> <strong><?php _e("Additional website domains to exclude from Top Referrers", "wassup"); ?></strong> :<br />
		<span style="padding-left:10px;display:block;clear:left;">
		<textarea name="topreferrer_exclude" rows="1" cols="66"><?php echo $top_ten['topreferrer_exclude']; ?></textarea><br />
		<?php  _e("Comma separated value","wassup"); ?> (ex: mydomain2.net, mydomain2.info,...).
		<?php  _e("Don't list your website domain defined in WordPress","wassup"); ?>.
		</span></p>
		<br /><br />
		<p style="clear:both;padding-left:0;padding-top:15px;"><input type="submit" name="submit-options" value="<?php _e('Save Settings','wassup'); ?>" />&nbsp;<input type="reset" name="reset" value="<?php _e('Reset','wassup'); ?>" /> - <input type="submit" name="reset-to-default" value="<?php _e("Reset to Default Settings", "wassup"); ?>" /></p><br />
	   </div>

	   <div id="wassup_opt_frag-2">
		<h3><?php _e('Statistics Recording Settings','wassup'); ?></h3>
		<p> <input type="checkbox" name="wassup_active" value="1" <?php if($wassup_options->wassup_active == 1) print "CHECKED"; ?> /> <strong><?php _e('Enable/Disable Recording','wassup'); ?></strong></p>
		<p style="margin-top:5px;"> <strong> <?php _e("Checkbox to record statistics for each type of \"visitor\"", "wassup") ?></strong><br />
		<span style="padding-left:25px;padding-top:0;margin-top:0;display:block;clear:left;">
	        <input type="checkbox" name="wassup_loggedin" value="1" <?php if($wassup_options->wassup_loggedin == 1) print "CHECKED"; ?> /> <?php _e("Record logged in users", "wassup") ?><br />
	        <input type="checkbox" name="wassup_spider" value="1" <?php if($wassup_options->wassup_spider == 1) print "CHECKED"; ?> /> <?php _e("Record spiders and bots", "wassup") ?><br />
	        <input type="checkbox" name="wassup_attack" value="1" <?php if($wassup_options->wassup_attack == 1) print "CHECKED"; ?> /> <?php _e("Record attack/exploit attempts (libwww-perl agent)", "wassup") ?><br />
	        <input type="checkbox" name="wassup_hack" value="1" <?php if($wassup_options->wassup_hack == 1) print "CHECKED"; ?> /> <?php _e("Record admin break-in/hacker attempts", "wassup") ?><br />
		</span>
		</p>
		<br /><p><input type="checkbox" name="wassup_spamcheck" value="1" <?php if($wassup_options->wassup_spamcheck == 1 ) print "CHECKED"; ?> /> <strong><?php _e('Enable/Disable Spam Check on Records','wassup'); ?></strong></p>
		<p style="margin-top:5px;"> <strong> <?php _e('Checkbox to record statistics for each type of "spam"','wassup'); ?></strong><br />
		<span style="padding-left:25px;padding-top:0;margin-top:0;display:block;clear:left;">
		<input type="checkbox" name="wassup_spam" value="1" <?php if($wassup_options->wassup_spam == 1) print "CHECKED"; ?> /> <?php _e('Record Akismet comment spam attempts','wassup'); ?> (check if an IP has previous comments as spam)<br />
		<input type="checkbox" name="wassup_refspam" value="1" <?php if($wassup_options->wassup_refspam == 1) print "CHECKED"; ?> /> <?php _e('Record referrer spam attempts','wassup'); ?><br />
		</span>
		</p>
		<br /><p><strong><?php _e('Enter source IPs to exclude from recording','wassup'); ?></strong>:
		<br /><span style="padding-left:10px;display:block;clear:left;">
	        <textarea name="wassup_exclude" rows="4" cols="40"><?php print $wassup_options->wassup_exclude; ?></textarea></span><?php _e("comma separated value (ex: 127.0.0.1, 10.0.0.1, etc...)", "wassup") ?></p>
		<br /><p><strong><?php _e('Enter requested URLs to exclude from recording','wassup'); ?></strong>:
		<br /><span style="padding-left:10px;display:block;clear:left;">
	        <textarea name="wassup_exclude_url" rows="4" cols="40"><?php print $wassup_options->wassup_exclude_url; ?></textarea></span><?php _e("comma separated value, don't put the entire url, only the last path or some word to exclude (ex: /category/wordpress, 2007, etc...)", "wassup") ?></p>
		<br /><br />
		<p style="clear:both;padding-left:0;padding-top:15px;"><input type="submit" name="submit-options2" value="<?php _e('Save Settings','wassup'); ?>" />&nbsp;<input type="reset" name="reset" value="<?php _e('Reset','wassup'); ?>" /> - <input type="submit" name="reset-to-default" value="<?php _e("Reset to Default Settings", "wassup"); ?>" /></p><br />
	   </div>
	
	   <div id="wassup_opt_frag-3">
	   <?php /*
	   <h3><?php _e('Temporary files location folder','wassup'); ?></h3>
		<p><?php echo '<strong>'.__('Current "Save path" directory for storing temporary files used to track visitor activity','wassup').'</strong>:<br />';
		$sessionpath = $wassup_options->wassup_savepath;
		if (empty($sessionpath)) {
			$sessionpath = getSessionpath();
		}
		//$sessionpath = "/fakefolder/temp";	//#debug
		$sessionstyle = '';
		//# check that session_save_path exists and is writable...
		if ($sessionpath == "" || $wassup_options->isWritableFolder($sessionpath) == false) {
			$sessionwarn = '<span style="font-size:95%; padding-left:5px;'.$alertstyle.'"><span style="text-decoration:blink;">'.__('WARNING','wassup').'!</span> '.__('Directory does not exist or is not writable. Please enter a different path above or change "session.save_path" in "php.ini" to point to a valid, writable folder','wassup').'.</span>';
			$sessionstyle = $alertstyle;
		} else {
			$sessionwarn ='<span style="font-size:95%; color:#555; padding-left:5px;">'.__('Note: To adjust, modify the directory shown in the box above or edit "sessions.save_path" in','wassup').' <i>php.ini</i>.</span>';
		} ?>
		<textarea name="wassup_savepath" rows="1" style="width:550px;padding-left:25px;clear:left;<?php echo $sessionstyle; ?>"><?php echo $sessionpath; ?></textarea>
		<br />&nbsp;&nbsp;<?php echo __('Use absolute directory paths only. This value is usually','wassup').' "/tmp".'."\n"; ?>
		<br />&nbsp; <span style="font-size:95%; color:#555;">System default for session.save_path="<?php echo session_save_path(); //debug ?>" from <i>php.ini</i> or from web server configuration.</span>
		<br />&nbsp;<?php echo $sessionwarn."\n"; ?>
		</p><br />
		*/ ?>
	   <?php //TODO ?>
	   <!--
	   <br /><h3><?php _e('Rescan Old Records','wassup'); ?></h3>
		<p><?php _e("Statistical records collected by earlier versions of WassUp may not have the latest spider, search engine, and spam data properly identified.  Click the \"Rescan\" button to retroactively scan and update old records","wassup"); ?>.
		<br /><input type="button" name="rescan" value="<?php _e('Rescan Old Records','wassup'); ?>" /> 
		</p><br />
	   -->
		<br /><h3><?php _e('Select actions for table growth','wassup'); ?></h3>
		<p><?php _e("WassUp table grows very fast (especially if your blog is frequently visited), I recommend you to delete old records sometimes. You can select any option below to reset it, delete old records automatically or manually. (If you haven't database space problems you can leave the table as is)","wassup"); ?></p>
		<p><?php _e('Current WassUp table usage is','wassup'); ?>:
		<strong>
		<?php
		if ( (int)$tusage >= (int)$wassup_options->wassup_remind_mb) { 
			print '<span style="'.$alertstyle.'">'.$tusage.'</span>';
		} else { print $tusage; } ?>
		</strong> Mb (<?php echo $data_rows.' '.__('records','wassup'); ?>)</p>
		<?php print $alert_msg; ?>
		<br /><p><input type="checkbox" name="wassup_remind_flag" value="1" <?php if ($wassup_options->wassup_remind_flag == 1) print "CHECKED"; ?>> 
		<strong><?php _e('Alert me','wassup'); ?></strong> (<?php _e('email to','wassup'); ?>: <strong><?php print $adminemail; ?></strong>) <?php _e('when table reaches','wassup'); ?> <input type="text" name="wassup_remind_mb" size="3" value="<?php print $wassup_options->wassup_remind_mb; ?>"> Mb</p>
		<p><input type="checkbox" name="wassup_empty" value="1"> 
		<strong><?php _e('Empty table','wassup'); ?></strong> (<a href="?<?php print $_SERVER['QUERY_STRING']; ?>&export=1"><?php _e('export table in SQL format','wassup'); ?></a>)</p>
		<br /><p><strong><?php _e("Automatically delete records older than:", "wassup") ?></strong> 
		<select name="delete_auto">
		<?php $wassup_options->showFormOptions("delete_auto"); ?>
		</select></p>
		<br /><p><?php _e("Delete NOW records older than:", "wassup") ?> 
		<select name="delete_manual">
		<option value="never"><?php _e("Action is NOT undoable", "wassup") ?> &nbsp;</option>
		<option value="-1 day"><?php _e("24 hours", "wassup") ?></option>
		<option value="-1 week"><?php _e("1 week", "wassup") ?></option>
		<option value="-1 month"><?php _e("1 month", "wassup") ?></option>
		<option value="-3 months"><?php _e("3 months", "wassup") ?></option>
		<option value="-6 months"><?php _e("6 months", "wassup") ?></option>
		<option value="-1 year"><?php _e("1 year", "wassup") ?></option>
		</select></p><br />

	    <br /><h3><?php _e("Server Settings and Memory Resources","wassup"); ?></h3>
	   	<p style="color:#555; margin-top:0; padding-top:0;"><?php _e('For information only. Some values may be adjustable in PHP startup file, php.ini or php5.ini','wassup'); ?>.</p>
	   	<p><strong>WordPress <?php _e('Version','wassup'); ?></strong>: <?php echo $wp_version; ?></p>
	   	<p style="padding-top:5px;"><strong>MySQL <?php _e('Version','wassup'); ?></strong>:
		<?php $sqlversion = $wpdb->get_var("SELECT VERSION() AS version");
			if (!empty($sqlversion)) { echo $sqlversion; }
			else { _e("unknown","wassup"); }
		?></p>
	   	<p><strong>MySQL <?php _e('Engine','wassup'); ?></strong>:
		<?php	if (!empty($table_engine)) { echo $table_engine; }
			else { _e("unknown","wassup"); }
		?></p>
		<!-- 
	   	<p><strong>MySQL <?php _e('Query Cache Limit','wassup'); ?></strong>:
		<?php $sqlquery = $wpdb->get_col("SHOW VARIABLES LIKE 'query_cache_limit'");
			if (!empty($sqlquery)) {
				$query_cache="";
				foreach ($sqlquery as $fcache) {
					$query_cache = $fcache;
				}
				if (is_numeric($query_cache)) {
					echo ($query_cache/1024/1024) . "M";
				} else {
					echo $query_cache;
				}
			} else { 
				_e("unknown","wassup");
			}
		?></p>
		-->
	   	<p style="padding-top:5px;"><strong>PHP <?php _e("Version","wassup"); ?></strong>: <?php echo PHP_VERSION; ?></p>
		<p><strong>PHP <?php _e("Safe Mode", "wassup"); ?> : </strong>
		<?php	if (ini_get("safe_mode")) { _e("on","wassup"); }
			else { _e("off","wassup"); }
		?></p>
	   	<p><strong>PHP <?php _e("Memory Allocation","wassup"); ?></strong>:
		<?php	
			$memory_use=0;
			if (function_exists("memory_get_usage")) {
				$memory_use=round(memory_get_usage()/1024/1024,2);
			}
			$memory_limit = ini_get("memory_limit");
			if (preg_match('/^(\d+){1,4}(\w?)/',$memory_limit,$matches) > 0) {
				$mem=(int)$matches[1];
				if ( $mem < 12 && $matches[2] == "M") { 
			   		print '<span style="'.$alertstyle.'">'.$memory_limit."</span>";
				} else {
					echo $memory_limit;
				}
			} else { 
				$memory_limit=0; _e("unknown","wassup");
			}
		?></p>
	   	<p><strong>PHP <?php _e("Memory Usage","wassup"); ?></strong>:
		<?php
			if ($memory_limit >0 && ($memory_limit-$memory_use) < 2) {
				print '<span style="'.$alertstyle.'">'.$memory_use."M</span>";
			} elseif ($memory_use >0) {
			   	echo $memory_use."M";
			} else { 
				_e("unknown","wassup");
			}
		?></p>
	   	<p><strong>PHP <?php _e("Script Timeout Limit","wassup"); ?></strong> (in seconds):
		<?php	$max_execute = ini_get("max_execution_time");
			if (!empty($max_execute)) { echo $max_execute; }
			else { _e("unknown","wassup"); }
		?></p>
	   	<p><strong>PHP <?php _e("Browser Capabilities File","wassup"); ?></strong> (browscap):
		<?php	$browscap = ini_get("browscap");
			if ( $browscap == "") { _e("not set","wassup"); } 
			else { echo basename($browscap); }
		?></p>
		<br /><br />
		<p style="clear:both;padding-left:0;padding-top:15px;"><input type="submit" name="submit-options3" value="<?php _e('Save Settings','wassup'); ?>" />&nbsp;<input type="reset" name="reset" value="<?php _e('Reset','wassup'); ?>" /> - <input type="submit" name="reset-to-default" value="<?php _e("Reset to Default Settings", "wassup"); ?>" /></p><br />
	   </div>
	
	   <div id="wassup_opt_frag-4">
		<h3><?php _e('Want to uninstall WassUp?', 'wassup') ;?></h3>
		<p><?php _e('No problem. Before you deactivate this plugin, check the box below to cleanup any data that was collected by WassUp that could be left behind.', 'wassup') ;?></p><br />
		<p><input type="checkbox" name="wassup_uninstall" value="1" <?php if ($wassup_options->wassup_uninstall == 1 ) print "CHECKED"; ?> /> <strong><?php _e('Permanently remove WassUp data and settings from Wordpress','wassup'); ?></strong></p>
		<?php if ($wassup_options->wassup_uninstall == 1) { ?>
			<span style="font-size:95%;font-weight:bold; margin-left:20px;<?php echo $alertstyle; ?>"><span style="text-decoration:blink;padding-left:5px;"><?php _e("WARNING","wassup"); ?>! </span><?php _e("All WassUp data and settings will be deleted upon deactivation of this plugin","wassup"); ?>.</span><br />
		<?php } ?>
		<br /><p><?php _e("This action cannot be undone. Before uninstalling WassUp, you should backup your Wordpress database first. WassUp data is stored in the table", "wassup"); ?> <strong>wp_wassup</strong>.</p>

		<br /><p><?php _e("To help improve this plugin, we would appreciate your feedback at","wassup"); ?> <a href="http://www.wpwp.org">www.wpwp.org</a>.</p>
		<br /><br />
		<p style="clear:both;padding-left:0;padding-top:15px;"><input type="submit" name="submit-options4" value="<?php _e('Save Settings','wassup'); ?>" />&nbsp;<input type="reset" name="reset" value="<?php _e('Reset','wassup'); ?>" /> - <input type="submit" name="reset-to-default" value="<?php _e("Reset to Default Settings", "wassup"); ?>" /></p><br />
	   </div>
        </form>
	</div>
	<br />
