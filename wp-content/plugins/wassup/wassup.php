<?php
/*
Plugin Name: WassUp
Plugin URI: http://www.wpwp.org
Description: Wordpress plugin to analyze your visitors traffic with real time stats, chart and a lot of chronological informations. It has sidebar Widget support to show current online visitors and other statistics.
Version: 1.6.1
Author: Michele Marcucci, Helene D.
Author URI: http://www.michelem.org/

Copyright (c) 2007 Michele Marcucci
Released under the GNU General Public License (GPL)
http://www.gnu.org/licenses/gpl.txt
*/

//# Stop any attempt to call wassup.php directly.  -Helene D. 1/27/08.
if (preg_match('#'.basename(__FILE__) .'#', $_SERVER['PHP_SELF'])) { 
	die('Permission Denied! You are not allowed to call this page directly.');
}
$version = "1.6.1";
define('WASSUPFOLDER', dirname(plugin_basename(__FILE__)), TRUE);
require_once(dirname(__FILE__).'/lib/wassup.class.php');
require_once(dirname(__FILE__).'/lib/main.php');
$wpurl = get_bloginfo('wpurl');	//global

if (isset($_GET['export'])) {
	export_wassup();
}

global $wp_version;

//#This works only in WP2.2 or higher
if (version_compare($wp_version, '2.2', '<')) {
	wp_die( '<strong style="color:#c00;background-color:#dff;padding:5px;">'.__("Sorry, Wassup requires WordPress 2.2 or higher to work","wassup").'.</strong>');
} elseif (function_exists('wp_cache_flush')) {
	//clear the WP cache
	wp_cache_flush();	//to prevent "cannot redeclare" errors???
}
//#add initial options and create table when Wassup activated 
//  -Helene D. 2/26/08.
function wassup_install() {
	global $wpdb;
	$table_name = $wpdb->prefix . "wassup";
	$table_tmp_name = $wpdb->prefix . "wassup_tmp";

	//### Add/update wassup settings in Wordpress options table
	$wassup_options = new wassupOptions; //#settings initialized here

	//# set hash
	$whash = $wassup_options->get_wp_hash();
	if (!empty($whash)) {
		$wassup_options->whash = $whash;
	}
	//# Add timestamp to optimize table once a day
	$wassup_options->wassup_optimize = wassup_get_time();

        //# set wmark and wip to null  
        $wassup_options->wmark = 0;     //#no preservation of delete/mark
        $wassup_options->wip = null;

	//### For upgrade of Wassup, manually initialize new settings
	//# initialize settings for 'spamcheck', 'refspam', and 'spam'
	if (!isset($wassup_options->wassup_spamcheck)) {
		$wassup_options->wassup_spamcheck = "0";
		//#set wassup_spamcheck=0 if wassup_refspam=0 and wassup_spam=0
		if (!isset($wassup_options->wassup_spam) && !isset($wassup_options->wassup_refspam)) {
			$wassup_options->wassup_spam = "1";
			$wassup_options->wassup_refspam = "1";
		} elseif ( $wassup_options->wassup_spam == "0" && $wassup_options->wassup_refspam == "0" ) { 
	   		$wassup_options->wassup_spamcheck = "0";
	   	}
	}
	
	//# update wassup settings for 'savepath' (default is null)
	//$wassup_options->wassup_savepath = "/fakedirectory"; //#debug
   	if (!isset($wassup_options->wassup_savepath)) {
  		$wassup_options->wassup_savepath = null;
	}
	//# display google chart by default for upgrades from 1.4.4
	if (!isset($wassup_options->wassup_chart)) {
		$wassup_options->wassup_chart = 1;
	}
        //# assign top ten items for upgrades from 1.4.9 or less
	if (empty($wassup_options->wassup_top10)) {
		$wassup_options->wassup_top10 = serialize(array("topsearch"=>"1",
						"topreferrer"=>"1",
						"toprequest"=>"1",
						"topbrowser"=>"1",
						"topos"=>"1",
						"toplocale"=>"0",
						"topfeed"=>"0",
						"topcrawler"=>"0",
						"topvisitor"=>"0",
						"topreferrer_exclude"=>""));
	}
	//#upgrade from 1.6: new options wassup_time_format and wassup_hack
	if (!isset($wassup_options->wassup_time_format)) {
		$wassup_options->wassup_time_format = 24;
	}
	if (!isset($wassup_options->wassup_hack)) {
		$wassup_options->wassup_hack = 1;
	}
	$wassup_options->saveSettings();

	//### Detect problems with WassUp install and show warning
	//# 
	//#Check for problems with 'session_savepath' and disable 
	//#  recording, if found.  -Helene D. 2/24/08
	/*
	$sessionpath = $wassup_options->wassup_savepath;
	if (empty($sessionpath)) { $sessionpath = getSessionpath(); }
	//default to "/tmp" if no sessionpath value
	if (empty($sessionpath)) { 
		$sessionpath = "/tmp";
		$wassup_options->wassup_savepath = $sessionpath;
	}
	if ($wassup_options->isWritableFolder($sessionpath) == false) {
		if ($wassup_options->wassup_active == "1") {
			$wassup_options->wassup_active = "0";
  			$wassup_options->wassup_alert_message = __('WassUp has detected a problem with "session.save_path" setting in your Wordpress/PHP configuration. Statistics logging has been disabled as a result. To fix, go to admin menu, "Wassup-->Options-->Manage Files & Database" and modify "Temporary files location folder".','wassup');
		} else {
  			$wassup_options->wassup_alert_message = __('WassUp has detected a problem with "session.save_path" setting in your Wordpress/PHP configuration. Please fix by modifying "Temporary files location folder" in admin menu, "Wassup-->Options-->Manage Files & Database".','wassup');
		}
	}
	$wassup_options->saveSettings();
	unset($sessionpath); //because "install" works in global scope
	*/
	//# TODO:
	//###Detect known incompatible plugins like "wp_cache" and disable 
	//#  recordings and show warning message...

	//### Create/upgrade wassup MAIN table
	if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) { 
		CreateTable($table_name);
		CreateTable($table_tmp_name);
	} else {
		UpdateTable(); //<== wassup_tmp is added here, if missing
	}
} //#end function wassup_install

//set global variables that are dependent on Wassup's wp_options values
$wassup_settings = get_option('wassup_settings'); //temp only..
$wassup_options = new wassupOptions; 
//$wassup_options->loadSettings();	//done automatically 
$whash = $wassup_options->whash;	//global...

//#Completely remove all wassup tables and options from Wordpress when
//# the 'wassup_uninstall' option is set and plugin is deactivated.
//#  -Helene D. 2/26/08
function wassup_uninstall() {
	global $wassup_options, $wpdb;
	if ($wassup_options->wassup_uninstall == "1") {
		$table_name = $wpdb->prefix . "wassup";
		$table_tmp_name = $wpdb->prefix . "wassup_tmp";
		//$wpdb->query("DROP TABLE IF EXISTS $table_name"); //incorrectly causes an activation error in Wordpress
		//$wpdb->query("DROP TABLE IF EXISTS $table_tmp_name"); //incorrectly causes an activation error in Wordpress
		mysql_query("DROP TABLE IF EXISTS $table_tmp_name");
		mysql_query("DROP TABLE IF EXISTS $table_name");
		$wassup_options->deleteSettings(); 
	}
} //#end function wassup_uninstall

function add_wassup_meta_info() {
	global $version;
	print '<meta name="wassup-version" content="'.$version.'" />';
}

//# Wassup init hook actions performed before headers are sent: 
//#   -Load jquery AJAX library and dependent javascripts for admin menus
//#   -Load language/localization files for admin menus and widget
//#   -Set 'wassup' cookie for new visitor hits
function wassup_init() {
	global $wpurl;

	//### Add wassup scripts to Wassup Admin pages...
	if (stristr($_GET['page'],'wassup') !== FALSE) {
		if ( function_exists('wp_deregister_script')) {
			//removes old jquery vers.
			wp_deregister_script('jquery');	
		}
		// the safe way to load jquery into WP
		wp_register_script('jquery', $wpurl.'/wp-content/plugins/'.WASSUPFOLDER.'/js/jquery.js',FALSE,'1.2.6');
		if ($_GET['page'] == "wassup-spy") {
			//the safe way to load a jquery dependent script
			wp_enqueue_script('spy', $wpurl.'/wp-content/plugins/'.WASSUPFOLDER.'/js/spy.js', array('jquery'), '1.4');
		} elseif($_GET['page'] == "wassup-options") {
			wp_enqueue_script('ui.base', $wpurl.'/wp-content/plugins/'.WASSUPFOLDER.'/js/ui.base.js', array('jquery'), '3');
			wp_enqueue_script('ui.tabs', $wpurl.'/wp-content/plugins/'.WASSUPFOLDER.'/js/ui.tabs.js', array('jquery'), '3');
		} else {
			//the safe way to load a jquery dependent script
			wp_enqueue_script('thickbox', $wpurl.'/wp-content/plugins/'.WASSUPFOLDER.'/thickbox/thickbox.js', array('jquery'), '3');
		} 
	}

	//Loading language file...
	//Doesn't work if the plugin file has its own directory.
	//Let's make it our way... load_plugin_textdomain() searches only in the wp-content/plugins dir.
	$currentLocale = get_locale();
	if(!empty($currentLocale)) {
		$moFile = dirname(__FILE__) . "/language/" . $currentLocale . ".mo";
		if(@file_exists($moFile) && is_readable($moFile)) load_textdomain('wassup', $moFile);
	}

	//Set Wassup cookie for visitor hits before headers are sent
	//add_action('init', 'wassupPrepend');
	if (!is_admin()) {	//exclude wordpress admin page visits
		wassupPrepend();
	}
} // end function wassup_init

//Add the wassup stylesheet and other javascripts...
function add_wassup_css() {
        global $wpurl, $wassup_options, $whash;

	//assign a value to whash, if none
        if ($whash == "") {
		$whash = $wassup_options->get_wp_hash();
		$wassup_options->whash = $whash;	//save new hash
		$wassup_options->saveSettings();
	}

        $plugin_page = attribute_escape($_GET['page']);

        if (stristr($plugin_page,'wassup') !== FALSE) { $plugin_page="wassup"; }
        //Add css and javascript to wassup menu pages only...
        if ($plugin_page == "wassup") {
                //$wassup_settings = get_option('wassup_settings');
		echo "\n".'<script type="text/javascript">var tb_pathToImage = "'.$wpurl.'/wp-content/plugins/'.WASSUPFOLDER.'/thickbox/loadingAnimation.gif";</script>';
                echo "\n".'<link rel="stylesheet" href="'.$wpurl.'/wp-content/plugins/'.WASSUPFOLDER.'/thickbox/thickbox.css'.'" type="text/css" />';
                echo "\n".'<link rel="stylesheet" href="'.$wpurl.'/wp-content/plugins/'.WASSUPFOLDER.'/ui.tabs.css'.'" type="text/css" />';
                echo "\n".'<link rel="stylesheet" href="'.$wpurl.'/wp-content/plugins/'.WASSUPFOLDER.'/wassup.css'.'" type="text/css" />'."\n";

if ($_GET['page'] != "wassup-options" AND $_GET['page'] != "wassup-spy") { ?>
<script type='text/javascript'>
  //<![CDATA[
  function selfRefresh(){
 	location.href='?<?php print $_SERVER['QUERY_STRING']; ?>';
  }
  setTimeout('selfRefresh()', <?php print ($wassup_options->wassup_refresh * 60000); ?>);
  //]]>
</script>

<script type='text/javascript'>
  //<![CDATA[
  var _countDowncontainer="0";
  var _currentSeconds="0";
  function ActivateCountDown(strContainerID, initialValue) {
  	_countDowncontainer = document.getElementById(strContainerID);
  	SetCountdownText(initialValue);
  	window.setTimeout("CountDownTick()", 1000);
  }
  function CountDownTick() {
  	SetCountdownText(_currentSeconds-1);
  	window.setTimeout("CountDownTick()", 1000);
  }
  function SetCountdownText(seconds) {
  	//store:
  	_currentSeconds = seconds;
  	//build text:
  	var strText = AddZero(seconds);
  	//apply:
  	if (_countDowncontainer) {	//prevents error in "Options" submenu
  		_countDowncontainer.innerHTML = strText;
  	}
  }
  function AddZero(num) {
  	return ((num >= "0")&&(num < 10))?"0"+num:num+"";
  }
  //]]>
</script>
<script type="text/javascript">
  //<![CDATA[
  window.onload=WindowLoad;
  function WindowLoad(event) {
  	ActivateCountDown("CountDownPanel", <?php print ($wassup_options->wassup_refresh * 60); ?>);
  }
  //]]>
</script>

<script type="text/javascript">
  //<![CDATA[
  jQuery(document).ready(function($){
  	$("a.showhide").click(function(){
  	   var id = $(this).attr('id');
  	   $("div.navi" + id).toggle("slow");
  	   return false;
  	});
  	$("a.toggleagent").click(function(){
  	   var id = $(this).attr('id');
  	   $("div.naviagent" + id).slideToggle("slow");
  	   return false;
  	});
        $("a.deleteID").click(function(){
           var id = $(this).attr('id');
                 $.ajax({
		  url: "<?php echo $wpurl.'/wp-content/plugins/'.WASSUPFOLDER.'/lib/action.php?action=delete&whash='.$whash; ?>&id=" + id,
                  async: false
                 })
           $("div.delID" + id).fadeOut("slow");
           return false;
        });
  	$("a.show-search").toggle(function(){
  	   $("div.search-ip").slideDown("slow");
  	     $("a.show-search").html("<a href='#' class='show-search'><?php _e("Hide Search", "wassup") ?></a>");
  	    },function() {
  	   $("div.search-ip").slideUp("slow");
  	     $("a.show-search").html("<a href='#' class='show-search'><?php _e("Search", "wassup") ?></a>");
  	   return false;
  	   });
  	$("a.show-topten").toggle(function(){
  	   $("div.topten").slideDown("slow");
  	     $("a.show-topten").html("<a href='#' class='show-topten'><?php _e("Hide TopTen", "wassup") ?></a>");
  	    },function() {
  	   $("div.topten").slideUp("slow");
  	     $("a.show-topten").html("<a href='#' class='show-topten'><?php _e("Show TopTen", "wassup") ?></a>");
  	   return false;
  	   });

  	$("a.toggle-all").toggle(function() {
  	     $("div.togglenavi").slideDown("slow");
  	     $("a.toggle-all").html("<a href='#' class='toggle-all'><?php _e("Collapse All", "wassup") ?></a>");
  	    },function() {
  	     $("div.togglenavi").slideUp("slow");
  	     $("a.toggle-all").html("<a href='#' class='toggle-all'><?php _e("Expand All", "wassup") ?></a>");
  	   return false;
  	    });
  	$("a.toggle-allcrono").toggle(function() {
  	     $("div.togglecrono").slideUp("slow");
  	     $("a.toggle-allcrono").html("<a href='#' class='toggle-allcrono'><?php _e("Expand Cronology", "wassup") ?></a>");
  	  },function() {
  	     $("div.togglecrono").slideDown("slow");
  	     $("a.toggle-allcrono").html("<a href='#' class='toggle-allcrono'><?php _e("Collapse Cronology", "wassup") ?></a>");
  	  return false;
  	  });
  });	//end jQuery(document).ready
  //]]>
</script>
<?php } //end if page != wassup-options ?>

<script type='text/javascript'>
  //<![CDATA[
  function go()
  {
  	box = document.forms["0"].navi;
  	destination = box.options[box.selectedindex].value;
  	if (destination) location.href = destination;
  }
  function go2()
  {
  	box2 = document.forms["0"].type;
  	destination2 = box2.options[box2.selectedindex].value;
  	if (destination2) location.href = destination2;
  }
  //]]>
</script>

<?php
if ($_GET['page'] == "wassup-options") {
        //#Current active tabs are indentified after page reload with 
        //#  either $_GET['tab']=N or $_POST['submit-optionsN'] where 
        //#  N=tab number. The tab is then activated directly in 
        //#  "settings.php" with <li class="ui-tabs-selected">
?>
<script type="text/javascript">
  //<![CDATA[
  jQuery(document).ready(function($) {
        $('#tabcontainer > ul').tabs({ fx: { opacity: 'toggle' } });
  });
  //]]>
</script>
<?php
} elseif ($_GET['page'] == "wassup-spy") {
?>
<script type="text/javascript">
  //<![CDATA[
  jQuery(document).ready(function($){
  	$('#spyContainer > div:gt(4)').fadeEachDown(); // initial fade
  	$('#spyContainer').spy({ 
  		limit: 10, 
  		fadeLast: 5, 
		ajax: '<?php echo $wpurl."/wp-content/plugins/".WASSUPFOLDER."/lib/action.php?action=spy&whash=$whash"; ?>',
  		timeout: 2000, 
  		'timestamp': myTimestamp, 
		fadeInSpeed: 1100 });
  });
	
  function myTimestamp() {
  	var d = new Date();
  	var timestamp = d.getFullYear() + '-' + pad(d.getMonth()) + '-' + pad(d.getDate());
  	timestamp += ' ';
  	timestamp += pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
  	return timestamp;
  }

  // pad ensures the date looks like 2006-09-13 rather than 2006-9-13
  function pad(n) {
  	n = n.toString();
  	return (n.length == 1 ? '0' + n : n);
  }

  //]]>
</script>
<?php } //end if page == "wassup-spy"

} //end if plugin_page == "wassup"
} //end function add_wassup_css()

//put WassUp in the top-level admin menu and add submenus....
function wassup_add_pages() {
	global $wassup_options;
	$userlevel = $wassup_options->wassup_userlevel;
	if (empty($userlevel)) { $userlevel = 8; }
	// add the default submenu first (important!)...
	add_submenu_page(WASSUPFOLDER, __('Visitor Details', 'wassup'), __('Visitor Details', 'wassup'), $userlevel, WASSUPFOLDER, 'WassUp'); //<-- WASSUPFOLDER needed here for directory names that include a version number...
	// then add top menu and other submenus...
	add_menu_page('Wassup', 'WassUp', $userlevel, WASSUPFOLDER, 'Wassup');
	add_submenu_page(WASSUPFOLDER, __('Spy Visitors', 'wassup'), __('SPY Visitors', 'wassup'), $userlevel, 'wassup-spy', 'WassUp');
	add_submenu_page(WASSUPFOLDER, __('Current Visitors Online', 'wassup'), __('Current Visitors Online', 'wassup'), $userlevel, 'wassup-online', 'WassUp');
	add_submenu_page(WASSUPFOLDER, __('Options', 'wassup'), __('Options', 'wassup'), $userlevel, 'wassup-options', 'WassUp');
}

function WassUp() {
        global $wpdb, $wp_version, $version, $wpurl, $wassup_options, $whash;

	//#debug...
	//error_reporting(E_ALL | E_STRICT);	//debug, E_STRICT=php5 only
	//ini_set('display_errors','On');	//debug
	//$wpdb->show_errors();	//debug

        $table_name = $wpdb->prefix . "wassup";
        $table_tmp_name = $wpdb->prefix . "wassup_tmp";
	$wassup_options->loadSettings();	//needed in case "update_option is run elsewhere in wassup (widget)

	// RUN THE SAVE/RESET OPTIONS
	$admin_message="";
	if (isset($_POST['submit-options']) || 
	    isset($_POST['submit-options2']) || 
	    isset($_POST['submit-options3'])) {
		if ($_POST['wassup_remind_flag'] == 1 AND $_POST['wassup_remind_mb'] == "") {
			$wassup_options->wassup_remind_flag = $_POST['wassup_remind_flag'];
			$wassup_options->wassup_remind_mb = 10;
		} else {
			$wassup_options->wassup_remind_flag = $_POST['wassup_remind_flag'];
			$wassup_options->wassup_remind_mb = $_POST['wassup_remind_mb'];
		}
		$wassup_options->wassup_active = $_POST['wassup_active'];
		$wassup_options->wassup_chart_type = $_POST['wassup_chart_type'];
		$wassup_options->wassup_loggedin = $_POST['wassup_loggedin'];
		$wassup_options->wassup_spider = $_POST['wassup_spider'];
		$wassup_options->wassup_attack = $_POST['wassup_attack'];
		$wassup_options->wassup_hack = $_POST['wassup_hack'];
		$wassup_options->wassup_spamcheck = $_POST['wassup_spamcheck'];
		$wassup_options->wassup_spam = $_POST['wassup_spam'];
		$wassup_options->wassup_refspam = $_POST['wassup_refspam'];
		$wassup_options->wassup_exclude = $_POST['wassup_exclude'];
		$wassup_options->wassup_exclude_url = $_POST['wassup_exclude_url'];
		$wassup_options->delete_auto = $_POST['delete_auto'];
		$wassup_options->delete_auto_size = $_POST['delete_auto_size'];
		$wassup_options->wassup_screen_res = $_POST['wassup_screen_res'];
		$wassup_options->wassup_refresh = $_POST['wassup_refresh'];
		$wassup_options->wassup_userlevel = $_POST['wassup_userlevel'];
		$wassup_options->wassup_dashboard_chart = $_POST['wassup_dashboard_chart'];
		$wassup_options->wassup_time_format = $_POST['wassup_time_format'];
		$wassup_options->wassup_default_type = $_POST['wassup_default_type'];
		$wassup_options->wassup_default_limit = $_POST['wassup_default_limit'];
                $top_ten = array("topsearch" => $_POST['topsearch'],
                                "topreferrer" => $_POST['topreferrer'],
                                "toprequest" => $_POST['toprequest'],
                                "topbrowser" => $_POST['topbrowser'],
                                "topos" => $_POST['topos'],
                                "toplocale" => $_POST['toplocale'],
				"topvisitor" => $_POST['topvisitor'],
                                "topfeed" => "0",
                                "topcrawler" => "0",
                                "topreferrer_exclude" => $_POST['topreferrer_exclude']);
                $wassup_options->wassup_top10 = serialize($top_ten);
		/* if ( $_POST['wassup_savepath'] != $wassup_options->wassup_savepath ) {
			if (empty($_POST['wassup_savepath']) || rtrim($_POST['wassup_savepath'],"/") == getSessionpath()) {
				$wassup_options->wassup_savepath = NULL;
			} else {
				$wassup_options->setSavepath($_POST['wassup_savepath']);
			}
		} */
		if ($wassup_options->saveSettings()) {
			$admin_message = __("Wassup options updated successfully","wassup")."." ;
		}
	} elseif (isset($_POST['submit-options4'])) {	//uninstall checkbox
		$wassup_options->wassup_uninstall = $_POST['wassup_uninstall'];
		if ($wassup_options->saveSettings()) {
			$admin_message = __("Wassup uninstall option updated successfully","wassup")."." ;
		}
	} elseif (isset($_POST['submit-spam'])) {
		$wassup_options->wassup_spamcheck = $_POST['wassup_spamcheck'];
		$wassup_options->wassup_spam = $_POST['wassup_spam'];
		$wassup_options->wassup_refspam = $_POST['wassup_refspam'];
		if ($wassup_options->saveSettings()) {
			$admin_message = __("Wassup spam options updated successfully","wassup")."." ;
		}
	} elseif (isset($_POST['reset-to-default'])) {
		$wassup_options->loadDefaults();
		if ($wassup_options->saveSettings()) {
			$admin_message = __("Wassup options updated successfully","wassup")."." ;
		}
	}

	//#sets current tab style for Wassup admin submenu?
	if ($_GET['page'] == "wassup-spy") {
		$class_spy="class='current'";
	} elseif ($_GET['page'] == "wassup-options") {
		$class_opt="class='current'";
	} elseif ($_GET['page'] == "wassup-online") {
		$class_ol="class='current'";
	} else {
		$class_sub="class='current'";
	}

	//for stringShortener calculated values and max-width...-Helene D. 11/27/07, 12/6/07
	if (!empty($wassup_options->wassup_screen_res)) {
		$screen_res_size = (int) $wassup_options->wassup_screen_res;
	} else { 
		$screen_res_size = 670;
	}
	$max_char_len = ($screen_res_size)/10;
	$screen_res_size = $screen_res_size+20; //for wrap margins...

	//for generating page link urls....
	//$wpurl =  get_bloginfo('wpurl');	//global
	$siteurl =  get_bloginfo('siteurl');

	//#display an admin message or an alert. This must be above "wrap"
	//# div. -Helene D. 2/26/08.
	if (!empty($admin_message)) {
		$wassup_options->showMessage($admin_message);
	} elseif (!empty($wassup_options->wassup_alert_message)) {
		$wassup_options->showMessage();
		//#show alert message only once, so remove it here...
		$wassup_options->wassup_alert_message = "";
		$wassup_options->saveSettings();
	}
	//#debug - display MySQL errors/warnings
	//$mysqlerror = $wpdb->print_error();	//debug
	//if (!empty($mysqlerror)) { $wassup_options->showMessage($mysqlerror); }	//debug

	//moved max-width to single "wrap" div and removed it from 
	//  the individual spans and divs in style.php... ?>
	<div class="wrap" style="max-width:<?php echo $screen_res_size; ?>px;" >

<?php	// HERE IS THE VISITORS ONLINE VIEW
	if ($_GET['page'] == "wassup-online") { ?>
		<h2><?php _e("Current Visitors Online", "wassup"); ?></h2>
		<p class="legend"><?php echo __("Legend", "wassup").': <span class="box-log">&nbsp;&nbsp;</span> '.__("Logged-in Users", "wassup").' <span class="box-aut">&nbsp;&nbsp;</span> '.__("Comments Authors", "wassup").' <span class="box-spider">&nbsp;&nbsp;</span> '.__("Spiders/bots", "wassup"); ?></p><br />
		<p class="legend"><a href="#" class="toggle-all"><?php _e("Expand All","wassup"); ?></a></p>
		<?php
		$to_date = wassup_get_time();
		$from_date = strtotime('-3 minutes', $to_date);
		$currenttot = $wpdb->get_var("SELECT COUNT(DISTINCT wassup_id) as currenttot FROM $table_tmp_name WHERE `timestamp` BETWEEN $from_date AND $to_date");
		$currenttot = $currenttot+0;	//set to integer
		print "<p class='legend'>".__("Visitors online", "wassup").": <strong>".$currenttot."</strong></p><br />";
		if ($currenttot > 0) {
			$qryC = $wpdb->get_results("SELECT id, wassup_id, max(timestamp) as max_timestamp, ip, hostname, searchengine, urlrequested, agent, referrer, spider, username, comment_author FROM $table_tmp_name WHERE `timestamp` BETWEEN $from_date AND $to_date GROUP BY ip ORDER BY max_timestamp DESC");
		foreach ($qryC as $cv) {
		if ($wassup_options->wassup_time_format == 24) {
			$timed = gmdate("H:i:s", $cv->max_timestamp);
		} else {
			$timed = gmdate("h:i:s a", $cv->max_timestamp);
		}
		$ip_proxy = strpos($cv->ip,",");
		//if proxy, get 2nd ip...
		if ($ip_proxy !== false) {
			$ip = substr($cv->ip,(int)$ip_proxy+1);
		} else { 
			$ip = $cv->ip;
		}
		if ($cv->referrer != '') {
			if (!eregi($wpurl, $cv->referrer) OR $cv->searchengine != "") { 
				if (!eregi($wpurl, $cv->referrer) AND $cv->searchengine == "") {
				$referrer = '<a href="'.$cv->referrer.'" target=_"BLANK"><span style="font-weight: bold;">'.stringShortener($cv->referrer, round($max_char_len*.8,0)).'</span></a>';
				} else {
				$referrer = '<a href="'.$cv->referrer.'" target=_"BLANK">'.stringShortener($cv->referrer, round($max_char_len*.9,0)).'</a>';
				}
			} else { 
			$referrer = __("From your blog", "wassup"); 
			} 
		} else { 
			$referrer = __("Direct hit", "wassup"); 
		} 
		$numurl = $wpdb->get_var("SELECT COUNT(DISTINCT id) as numurl FROM $table_tmp_name WHERE wassup_id='".$cv->wassup_id."'");
	?>
			<div class="sum">
			<span class="sum-box"><?php if ($numurl >= 2) { ?><a  href="#" class="showhide" id="<?php echo $cv->id ?>"><?php print $ip; ?></a><?php } else { ?><?php print $ip; ?><?php } ?></span>
			<div class="sum-det"><span class="det1">
			<?php
			//# html_entity_decode() links that were already 
			//#  "htmlentities-encoded" in database to prevent wacky links
			//#  like "/imagegallery/?album=3&amp;amp;amp;gallery=13"
			print '<a href="'.wAddSiteurl(htmlspecialchars(html_entity_decode($cv->urlrequested))).'" target="_BLANK">';
			print stringShortener(urlencode(html_entity_decode($cv->urlrequested)), round($max_char_len*.9,0)); ?></a></span><br />
			<span class="det2"><strong><?php print $timed; ?> - </strong><?php print $referrer ?></span></div>
			</div>
			<?php // User is logged in or is a comment's author
			if ($cv->username != "" OR $cv->comment_author != "") {
				if ($cv->username != "") {
					$Ousername = '<li class="users"><span class="indent-li-agent">'.__("LOGGED IN USER", "wassup").': <strong>'.$cv->username.'</strong></span></li>'; 
					$Ocomment_author = '<li class="users"><span class="indent-li-agent">'.__("COMMENT AUTHOR", "wassup").': <strong>'.$cv->comment_author.'</strong></span></li>'; 
					$unclass = "userslogged";
				} elseif ($cv->comment_author != "") {
					$Ocomment_author = '<li class="users"><span class="indent-li-agent">'.__("COMMENT AUTHOR", "wassup").': <strong>'.$cv->comment_author.'</strong></span></li>'; 
					$unclass = "users";
				}
			?>
			<ul class="<?php print $unclass; ?>">
				<?php print $Ousername; ?>
				<?php print $Ocomment_author; ?>
			</ul>
			<?php  } ?>
			<div style="display: none;" class="togglenavi navi<?php echo $cv->id ?>">
			<ul class="url">
	<?php 
			$qryCD = $wpdb->get_results("SELECT `timestamp`, urlrequested FROM $table_tmp_name WHERE wassup_id='".$cv->wassup_id."' ORDER BY `timestamp` ASC");
			$i=0;
			foreach ($qryCD as $cd) {	
			$time2 = gmdate("H:i:s", $cd->timestamp);
			$num = ($i&1);
			$char_len = round($max_char_len*.9,0);
			if ($num == 0) $classodd = "urlodd"; else  $classodd = "url";
			if ($i >= 1) {
	?>
				<li class="<?php print $classodd; ?> navi<?php echo $cv->id ?>"><span class="indent-li"><?php print $time2; ?> - 
				<?php
				print '<a href="'.wAddSiteurl(htmlspecialchars(html_entity_decode($cd->urlrequested))).'" target="_BLANK">';
				print stringShortener(urlencode(html_entity_decode($cd->urlrequested)), $char_len).'</a></span></li>'."\n";
			}
			$i++;
			} //end foreach qryCD
			print '</ul>';
			print '</div>';
			print '<p class="sum-footer"></p>';
		} //end foreach qryC
		} //end if currenttot ?>
	<br /><p class="legend"><a href="#" class="toggle-all"><?php _e("Expand All", "wassup"); ?></a></p>
	
<?php	// HERE IS THE SPY MODE VIEW
	} elseif ($_GET['page'] == "wassup-spy") { ?>
		<h2><?php _e("SPY Visitors", "wassup"); ?></h2>
		<p class="legend"><?php echo __("Legend", "wassup").': <span class="box-log">&nbsp;&nbsp;</span> '.__("Logged-in Users", "wassup").' <span class="box-aut">&nbsp;&nbsp;</span> '.__("Comments Authors", "wassup").' <span class="box-spider">&nbsp;&nbsp;</span> '.__("Spiders/bots", "wassup"); ?></p><br />
		<div>
		<a href="#?" onclick="return pauseSpy();"><span id="spy-pause"><?php _e("Pause", "wassup"); ?></span></a>
		<a href="#?" onclick="return playSpy();"><span id="spy-play"><?php _e("Play", "wassup"); ?></span></a>
		<br />&nbsp;<br /></div>
		<div id="spyContainer">
		<?php 
		//display the last few hits here. The rest will be added by spy.js
		$to_date = (wassup_get_time()-2);
		$from_date = ($to_date - 12*(60*60)); //display last 10 visits in 12 hours...
		spyview($from_date,$to_date,10); ?>
		</div><br />

<?php	// HERE IS THE OPTIONS VIEW
	} elseif($_GET['page'] == "wassup-options") {
		//#moved content to external include file, "settings.php"
		//#  to make "wassup" code easier to read and modify 
		//#  -Helene D. 1/15/08.
		include(dirname(__FILE__).'/lib/settings.php'); ?>

<?php	// HERE IS THE MAIN/DETAILS VIEW
	} else { ?>
		<h2><?php _e("Latest hits", "wassup"); ?></h2>
		<?php if ($wassup_options->wassup_active != 1) { ?>
			<p style="color:red; font-weight:bold;"><?php _e("WassUp recording is disabled", "wassup"); ?></p>
		<?php }

		$res = (int) $wassup_options->wassup_screen_res;
		if (empty($res)) $res=620;
		elseif ($res < 800) $res=620;
		elseif ($res < 1024) $res=740;
		elseif ($res < 1200) $res=1000;
		else $res=1000;

		//## GET parameters that change options settings
		if (isset($_GET['wchart']) || isset($_GET['wmark'])) { 
			if (isset($_GET['wchart'])) { 
			if ($_GET['wchart'] == 0) {
				$wassup_options->wassup_chart = 0;
			} else {
				$wassup_options->wassup_chart = 1;
			}
			}
			if (isset($_GET['wmark'])) {
			if ($_GET['wmark'] == 0) {
                		$wassup_options->wmark = "0";
				$wassup_options->wip = "";
			} else {
				$wassup_options->wmark = "1";
				$wassup_options->wip = attribute_escape($_GET['wip']);
			}
			}
			$wassup_options->saveSettings();
		}

		//## GET params that filter detail display 
		//
		//## Filter detail list by date range...
		$to_date = wassup_get_time();
		if (isset($_GET['last']) && $_GET['last'] != "") { 
			$last = htmlentities(attribute_escape($_GET['last']));
		} else {
			$last = 1; 
		}
		$from_date = strtotime('-'.$last.' day', $to_date);

		//## Filter detail lists by visitor type...
		if (isset($_GET['type'])) {
			$type = htmlentities(attribute_escape($_GET['type']));
		} elseif ($wassup_options->wassup_default_type != '') {
			$type = $wassup_options->wassup_default_type;
		}
		$whereis="";
		if ($type == 'spider') {
			$whereis = " AND spider!=''";
		} elseif ($type == 'nospider') {
			$whereis = " AND spider=''";
		} elseif ($type == 'spam') {
			$whereis = " AND spam>0";
		} elseif ($type == 'nospam') {
			$whereis = " AND spam=0";
		} elseif ($type == 'nospamspider') {
			$whereis = " AND spam=0 AND spider=''";
		} elseif ($type == 'searchengine') {
			$whereis = " AND searchengine!='' AND search!=''";
		} elseif ($type == 'referrer') {
			$whereis = " AND referrer!='' AND referrer NOT LIKE '%$wpurl%' AND searchengine='' AND search=''";
		} elseif ($type == 'comauthor') {
			$whereis = " AND comment_author!=''";
		} elseif ($type == 'loggedin') {
			$whereis = " AND username!=''";
		}

		//## Filter detail lists by a specific page and number
		//#  of items per page...
		$items = 10;	//default
		if (isset($_GET['limit']) && is_numeric($_GET['limit'])) {
			//$items = htmlentities(attribute_escape($_GET['limit'])); 
			$items = $_GET['limit']; 
		} elseif ($wassup_options->wassup_default_limit != '') {
			$items = $wassup_options->wassup_default_limit;
		}
		if ((int)$items < 1 ) { $items = 10; }
		//# current page selections
		if (isset($_GET['pages']) && is_numeric($_GET['pages'])) {
			$pages = (int)$_GET['pages'];
		} else {
			$pages = 1;
		}
		if ( $pages > 1 ) {
			$limit = " LIMIT ".(($pages-1)*$items).",$items";
		} else {
			$limit = " LIMIT $items";
		}

		//## Filter detail lists by a searched item
                if (!empty($_GET['search'])) { 
                        $search = attribute_escape($_GET['search']);
                } else {
                        $search = "";
                }

                // DELETE EVERY RECORD MARKED BY IP
                //#  Delete limited to selected date range only. -Helene D. 3/4/08.
                if (!empty($_GET['deleteMARKED']) && $wassup_options->wmark == "1" ) {
                        $rec_deleted = $wpdb->get_var("SELECT COUNT(ip) as deleted FROM $table_name WHERE ip='".urlencode(attribute_escape($_GET['dip']))."' AND `timestamp` BETWEEN $from_date AND $to_date");
                        if (method_exists($wpdb,'prepare')) {
                                $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE ip='%s' AND `timestamp` BETWEEN %s AND %s", urlencode(attribute_escape($_GET['dip'])), $from_date, $to_date));
                        } else {
                                $wpdb->query("DELETE FROM $table_name WHERE ip='".urlencode(attribute_escape($_GET['dip']))."' AND `timestamp` BETWEEN $from_date AND $to_date");
                        }
                        echo '<p><strong>'.$rec_deleted.' '.__('records deleted','wassup').'</strong></p>';
                        //reset wmark/deleteMarked after delete and
                        //  clean up $_SERVER['QUERY_STRING'] as it is
                        //  used in filter selections below...
                        $remove_query= array("&dip=".$_GET['dip'],"&deleteMARKED=".$_GET['deleteMARKED'],"&wmark=1","&wip=".$_GET['wip']);
                        $new_query = str_replace($remove_query,"",$_SERVER['QUERY_STRING']);
                        $_SERVER['QUERY_STRING']=$new_query;
                        $wassup_options->wmark = "0";
                        $wassup_options->wip = null;
                        $wassup_options->saveSettings();
                }

		// Instantiate class to count items
		$Tot = New MainItems;
		$Tot->tableName = $table_name;
		$Tot->from_date = $from_date;
		$Tot->to_date = $to_date;
		$Tot->whereis = $whereis;
		$Tot->Limit = $limit;
		$Tot->WpUrl = $wpurl;

		$itemstot = $Tot->calc_tot("count", $search, null, "DISTINCT");
		$pagestot = $Tot->calc_tot("count", $search, null, null);
		$spamtot = $Tot->calc_tot("count", $search, "AND spam>0");
		// Check if some records was marked
		if ($wassup_options->wmark == "1") {
			$markedtot = $Tot->calc_tot("count", $search, "AND ip LIKE '%".$wassup_options->wip."%'", "DISTINCT");
		}
		// Check if some records was searched
		if (!empty($search)) {
			$searchtot = $Tot->calc_tot("count", $search, null, "DISTINCT");
		} ?>
                <form><table width="100%">
                <tr>
                <td>
                <p class="legend">
                <?php if ($wassup_options->wassup_chart == "1") { ?>
                        <a href="<?php echo '?page='.WASSUPFOLDER.'&wchart=0&last='.$last.'&limit='.$items.'&type='.$_GET['type'].'&search='.$search.'&pages='.$pages; ?>" style="text-decoration:none;">
                        <img src="<?php echo $wpurl.'/wp-content/plugins/'.WASSUPFOLDER.'/img/chart_delete.png" style="padding:0px 6px 0 0;" alt="'.__('hide chart','wassup').'" title="'.__('Hide the chart and site usage','wassup'); ?>" /></a>
                <?php } else { ?>
                        <a href="<?php echo '?page='.WASSUPFOLDER.'&wchart=1&last='.$last.'&limit='.$items.'&type='.$_GET['type'].'&search='.$search.'&pages='.$pages; ?>" style="text-decoration:none;">
                        <img src="<?php echo $wpurl.'/wp-content/plugins/'.WASSUPFOLDER.'/img/chart_add.png" style="padding:0px 6px 0 0;" alt="'.__('show chart','wassup').'" title="'.__('Show the chart and site usage','wassup'); ?>" /></a>
                <?php }

		//## Show selectable detail filters...
		if (isset($_GET['limit'])) {
			$new_limit = eregi_replace("\&limit=".$_GET['limit']."", "", $_SERVER['QUERY_STRING']);
		} else { 
			$new_limit = $_SERVER['QUERY_STRING'];
		}
		if (isset($_GET['last'])) {
			$new_last = eregi_replace("\&last=".$_GET['last']."", "", $_SERVER['QUERY_STRING']);
		} else {
			$new_last = $_SERVER['QUERY_STRING'];
		}
                _e('Summary for the last','wassup'); ?>
                <select style="font-size: 11px;" name="last" onChange="window.location.href=this.options[this.selectedIndex].value;">
                <?php
                //## selectable filter by date range
                echo "
                <option value='?$new_last&last=1'".($_GET['last'] == 1 ? " SELECTED" : "").">".__('24 hours','wassup')."</option>
                <option value='?$new_last&last=7'".($_GET['last'] == 7 ? " SELECTED" : "").">".__('7 days','wassup')."</option>
                <option value='?$new_last&last=30'".($_GET['last'] == 30 ? " SELECTED" : "").">".__('1 month','wassup')."</option>
                <option value='?$new_last&last=365'".($_GET['last'] == 365 ? " SELECTED" : "").">".__('1 year','wassup')."</option>"; ?>
                </select></p>
                </td>
                <td align="right"><p style="font-size: 11px;"><?php _e('Items per page','wassup'); ?>: <select name="navi" style="font-size: 11px;" onChange="window.location.href=this.options[this.selectedIndex].value;">
                <?php
                //## selectable filter by number of items on page (default_limit)
                $selected=$items;
                $optionargs="?$new_limit&limit=";
                $wassup_options->showFormOptions("wassup_default_limit","$selected","$optionargs");
                ?>
                </select> - <?php _e('Show items by','wassup'); ?>: <select style="font-size: 11px;" name="type" onChange="window.location.href=this.options[this.selectedIndex].value;">
                <?php
                //## selectable filter by type of record (wassup_default_type)
                $selected=$type;
                $optionargs="?page=".WASSUPFOLDER."&type=";
                $wassup_options->showFormOptions("wassup_default_type","$selected","$optionargs");
                ?>
                </select>
                </p>
                </td>
                </tr>
                </table>
                </form>

		<?php // Print Site Usage
                if ($wassup_options->wassup_chart == 1) { ?>
        <div class='main-tabs'>
                <div id='usage'>
                        <ul>
                        <li><span style="border-bottom:2px solid #0077CC;"><?php echo $itemstot; ?></span> <small><?php _e('Visits','wassup'); ?></small></li>
                        <li><span style="border-bottom:2px dashed #FF6D06;"><?php echo $pagestot; ?></span> <small><?php _e('Pageviews','wassup'); ?></small></li>
                        <li><span><?php echo @number_format(($pagestot/$itemstot), 2); ?></span> <small><?php _e('Pages/Visits','wassup'); ?></small></li>
                        <?php // Print spam usage only if enabled
			if ($wassup_options->wassup_spamcheck == 1) { ?>
			<li><span><a href="#TB_inline?height=180&width=300&inlineId=hiddenspam" class="thickbox"><?php echo $spamtot; ?></a></span> <span>(<?php echo @number_format(($spamtot*100/$pagestot), 2); ?>%)</span> <small><?php _e('Spams','wassup'); ?></small></li>
			<?php } ?>
                        </ul>
                <?php
                // Print the Google chart!
                if ($pagestot > 20) {
                        echo $Tot->TheChart($last, $res, "125", $search, $wassup_options->wassup_chart_type, "bg,s,ffffff")."";
                } else {
                        echo '<div id="placeholder" align="center"><p style="padding-top:50px;">'.__('Too few records to print chart','wassup').'...</p></div>';
                } ?>
                </div>
        </div>
        <?php   } //end if wassup_chart == 1
	
		if (!isset($_GET['limit']) OR $_GET['limit'] == 10 OR $_GET['limit'] == 20) { 
		
			$expcol = '
		<table width="100%"><tr>
		<td align="left" class="legend"><a href="#" class="toggle-all">'.__('Expand All','wassup').'</a></td>
		<td align="right" class="legend"><a href="#" class="toggle-allcrono">'.__('Collapse Chronology','wassup').'</a></td>
		</tr></table><br />';
		}
		
		// MAIN QUERY
		$main = $Tot->calc_tot("main", $search);

		if ($itemstot > 0) {
		$p=new pagination();
		$p->items($itemstot);
		$p->limit($items);
		$p->currentPage($pages);
		$p->target("admin.php?page=".WASSUPFOLDER."&limit=$items&type=$type&last=$last&search=$search");
		$p->calculate();
		$p->adjacents(5);
		}

		// hidden spam options
                ?>
                <div id="hiddenspam" style="display:none;">
        <h2><?php _e('Spam Options','wassup'); ?></h2>
        <form action="" method="post">
	<p><input type="checkbox" name="wassup_spamcheck" value="1" <?php if($wassup_options->wassup_spamcheck == 1 ) print "CHECKED"; ?> /> <strong><?php _e('Enable/Disable Spam Check on Records','wassup'); ?></strong></p>
        <p style="padding-left:30px;"><input type="checkbox" name="wassup_spam" value="1" <?php if($wassup_options->wassup_spam == 1) print "CHECKED"; ?> /> <?php _e('Record Akismet comment spam attempts','wassup'); ?></p>
        <p style="padding-left:30px;"><input type="checkbox" name="wassup_refspam" value="1" <?php if($wassup_options->wassup_refspam == 1) print "CHECKED"; ?> /> <?php _e('Record referrer spam attempts','wassup'); ?></p>
        <p style="padding-left:0;"><input type="submit" name="submit-spam" value="<?php _e('Save Settings','wassup'); ?>" /></p>
        </form>
                </div>
                <table width="100%">
                <tr>
                <td align="left" class="legend">
                <?php
		// Marked items - Refresh
                if ($wassup_options->wmark == 1) echo '<a href="?'.$_SERVER['QUERY_STRING'].'&search='.$wassup_options->wip.'" title="'.__('Filter by marked IP','wassup').'"><strong>'.$markedtot.'</strong> '.__('show marked items','wassup').'</a> - ';
                if (!empty($search)) print "<strong>$searchtot</strong> ".__('Searched for','wassup').": <strong>$search</strong> - ";
                echo __('Auto refresh in','wassup').' <span id="CountDownPanel"></span> '.__('seconds','wassup'); ?>
		</td>
		<td align="right" class="legend"><a href="<?php echo $wpurl.'/wp-content/plugins/'.WASSUPFOLDER.'/lib/action.php?action=topten&whash='.$whash.'&from_date='.$from_date.'&to_date='.$to_date.'&width='.$res.'&height=400'; ?>" class="thickbox" title="Wassup <?php _e('Top Ten','wassup'); ?>"><?php _e('Show Top Ten','wassup'); ?></a> - <a href="#" class='show-search'><?php _e('Search','wassup'); ?></a></td>
                </tr>
                </table>
<div class="search-ip" style="display: none;">
	<table border=0 width="100%">
		<tr valign="top">
		<td align="right">
        	<form action="" method="get">
		<input type="hidden" name="page" value="<?php echo WASSUPFOLDER; ?>" />
			<input type="text" size="25" name="search" value="<?php if ($search != "") print $search; ?>" /><input type="submit" name="submit-search" value="search" />
		</form>
		</td>
		</tr>
	</table>
</div>
<?php
	//# Detailed List of Wassup Records...
	print $expcol;
        //# Show Page numbers/Links...
        if ($itemstot >= 10) {
                print "\n".'<div id="pag" align="center">'.$p->show().'</div><br />'."\n";
        }
	if ($itemstot > 0) {
	foreach ($main as $rk) {
		$timestampF = $rk->max_timestamp;
		$dateF = gmdate("d M Y", $timestampF);
		if ($wassup_options->wassup_time_format == 24) {
			$datetimeF = gmdate('Y-m-d H:i:s', $timestampF);
			$timeF = gmdate("H:i:s", $timestampF);
		} else {
			$datetimeF = gmdate('Y-m-d h:i:s a', $timestampF);
			$timeF = gmdate("h:i:s a", $timestampF);
		}
		//$ip = @explode(",", $rk->ip);
		$ip_proxy = strpos($rk->ip,",");
		//if proxy, get 2nd ip...
		if ($ip_proxy !== false) {
			$ip = substr($rk->ip,(int)$ip_proxy+1);
		} else { 
			$ip = $rk->ip;
		}

		// Visitor Record - raw data (hidden)
		$raw_div="raw-".substr($rk->wassup_id,0,25).rand(0,99);
		echo "\n"; ?>
                <div id="<?php echo $raw_div; ?>" style="display:none; padding-top:7px;" >
                <h2><?php _e("Raw data","wassup"); ?>:</h2>
                <style type="text/css">.raw { color: #542; padding-left:5px; }</style>
                <ul style="list-style-type:none;padding:20px 0 0 30px;">
		<li><?php echo __("Visit type","wassup").': <span class="raw">';
                if ($rk->username != "") { 
			echo __("Logged-in user","wassup").' - '.$rk->username;
		} elseif ($rk->spam == "1" || $rk->spam == "2" ) { 
                	_e("Spammer","wassup");
		} elseif ($rk->comment_author != "") { 
                	echo __("Comment author","wassup").' - '.$rk->comment_author;
		} elseif ($rk->feed != "") { 
                	echo __("Feed","wassup").' - '.$rk->feed;
		} elseif ($rk->spider != "") { 
			echo __("Spider","wassup").' - '.$rk->spider;
		} else {
			 _e("Regular visitor","wassup");
		}
		echo '</span>'; ?></li>
                <li><?php echo __("IP","wassup").': <span class="raw">'.$rk->ip.'</span>'; ?></li>
                <li><?php echo __("Hostname","wassup").': <span class="raw">'.$rk->hostname.'</span>'; ?></li>
                <li><?php echo __("Url Requested","wassup").': <span class="raw">'.htmlspecialchars(html_entity_decode(clean_url($rk->urlrequested))).'</span>'; ?></li>
                <li><?php echo __("User Agent","wassup").': <span class="raw">'.$rk->agent.'</span>'; ?></li>
                <li><?php echo __("Referrer","wassup").': <span class="raw">'.urldecode($rk->referrer).'</span>'; ?></li>
                <?php if ($rk->search != "") { ?>
                <li><?php echo __("Search Engine","wassup").': <span class="raw">'.$rk->searchengine.'</span> &nbsp; &nbsp; ';
		echo __("Search","wassup").': <span class="raw">'.$rk->search.'</span>'; ?></li>
                <?php }
                if ($rk->os != "") { ?>
                <li><?php echo __("OS","wassup").': <span class="raw">'.$rk->os.'</span>'; ?></li>
                <?php }
                if ($rk->browser != "") { ?>
                <li><?php echo __("Browser","wassup").': <span class="raw">'.$rk->browser.'</span>'; ?></li>
                <?php }
                if ($rk->language != "") { ?>
                <li><?php echo __("Locale/Language","wassup").': <span class="raw">'.$rk->language.'</span>'; ?></li>
                <?php } ?>
                <li><?php echo 'Wassup ID'.': <span class="raw">'.$rk->wassup_id.'</span>'; ?></li>
                <li><?php echo __("End timestamp","wassup").': <span class="raw">'.$datetimeF.' ( '.$rk->max_timestamp.' )</span>'; ?></li>
                </ul>
                </div> <!-- raw-wassup_id -->

                <?php //Visitor Record - detail listing
		if ($rk->referrer != '') {
			if (!eregi($wpurl, $rk->referrer) OR $rk->searchengine != "") { 
				if (!eregi($wpurl, $rk->referrer) AND $rk->searchengine == "") {
				$referrer = '<a href="'.$rk->referrer.'" target="_BLANK"><span style="font-weight: bold;">'.stringShortener($rk->referrer, round($max_char_len*.8,0)).'</span></a>';
				} else {
				$referrer = '<a href="'.$rk->referrer.'" target="_BLANK">'.stringShortener($rk->referrer, round($max_char_len*.9,0)).'</a>';
				}
			} else { 
                        $referrer = __('From your blog','wassup');
                        }
                } else { 
                        $referrer = __('Direct hit','wassup');
		} 
		$numurl = $wpdb->get_var("SELECT COUNT(DISTINCT id) as numurl FROM $table_name WHERE wassup_id='".$rk->wassup_id."'");
		if ($rk->hostname != "") $hostname = $rk->hostname; else $hostname = "unknown";
	?>

		<div class="delID<?php echo $rk->wassup_id ?>">
                <div class="<?php if ($wassup_options->wmark == 1 AND $wassup_options->wip == $ip) echo "sum-nav-mark"; else echo "sum-nav"; ?>">

                <p class="delbut">
                <?php // Mark/Unmark IP
                if ($wassup_options->wmark == 1 AND $wassup_options->wip ==  $ip) { ?>
                        <a  href="?<?php echo $_SERVER['QUERY_STRING']; ?>&deleteMARKED=1&dip=<?php print $ip; ?>" style="text-decoration:none;">
                        <img src="<?php echo $wpurl.'/wp-content/plugins/'.WASSUPFOLDER.'/img/cross.png" alt="'.__('delete','wassup').'" title="'.__('Delete ALL marked records with this IP','wassup'); ?>" /></a>
                        <a href="?page=<?php echo WASSUPFOLDER; ?>&wmark=0" style="text-decoration:none;">
                        <img src="<?php echo $wpurl.'/wp-content/plugins/'.WASSUPFOLDER.'/img/error_delete.png" alt="'.__('unmark','wassup').'" title="'.__('UnMark IP','wassup'); ?>" /></a>
                <?php } else { ?>
                        <a  href="#" class="deleteID" id="<?php echo $rk->wassup_id ?>" style="text-decoration:none;">
                        <img src="<?php echo $wpurl.'/wp-content/plugins/'.WASSUPFOLDER.'/img/cross.png" alt="'.__('delete','wassup').'" title="'.__('Delete this record','wassup'); ?>" /></a>
                        <a href="?<?php echo $_SERVER['QUERY_STRING']; ?>&wmark=1&wip=<?php print $ip; ?>" style="text-decoration:none;">
                        <img src="<?php echo $wpurl.'/wp-content/plugins/'.WASSUPFOLDER.'/img/error_add.png" alt="'.__('mark','wassup').'" title="'.__('Mark IP','wassup'); ?>" /></a>
                <?php } ?>
                <a href="#TB_inline?height=400&width=<?php echo $res.'&inlineId='.$raw_div; ?>" class="thickbox">
                <img src="<?php echo $wpurl.'/wp-content/plugins/'.WASSUPFOLDER.'/img/database_table.png" alt="'.__('show raw table','wassup').'" title="'.__('Show the items as raw table','wassup'); ?>" /></a>
                </p>

			<span class="sum-box"><?php if ($numurl >= 2) { ?><a  href="#" class="showhide" id="<?php echo $rk->id ?>"><?php print $ip; ?></a><?php } else { ?><?php print $ip; ?><?php } ?></span>
			<span class="sum-date"><?php print $datetimeF; ?></span>
			<div class="sum-det"><span class="det1">
			<?php 
			print '<a href="'.wAddSiteurl(htmlspecialchars(html_entity_decode(clean_url($rk->urlrequested)))).'" target="_BLANK">';
			print stringShortener(urlencode(html_entity_decode(clean_url($rk->urlrequested))), round($max_char_len*.8,0)); ?></a>
                        </span><br />
                        <span class="det2"><strong><?php _e('Referrer','wassup'); ?>: </strong><?php print $referrer; ?><br /><strong><?php _e('Hostname','wassup'); ?>:</strong> <a  href="#" class="toggleagent" id="<?php echo $rk->id ?>"><?php print $hostname; ?></a></span></div>
                        </div>
			<div style="margin-left: auto; margin-right: auto;">
			<div style="display: none;" class="togglenavi naviagent<?php echo $rk->id ?>">
			<ul class="useragent">
				<li class="useragent"><span class="indent-li-agent"><?php _e('User Agent','wassup'); ?>: <strong><?php print $rk->agent; ?></strong></span></li>
			</ul>
			</div>
			<?php // Referer is search engine
			if ($rk->searchengine != "") {
				if (eregi("images", $rk->searchengine)) {
					$bg = 'style="background: #e5e3ec;"';
					$page = (number_format(($rk->searchpage / 19), 0) * 18); 
					$Apagenum = explode(".", number_format(($rk->searchpage / 19), 1));
					$pagenum = ($Apagenum[0] + 1);
					$url = parse_url($rk->referrer); 
					$ref = $url['scheme']."://".$url['host']."/images?q=".eregi_replace(" ", "+", $rk->search)."&start=".$page;
				} else {
					$bg = 'style="background: #e4ecf4;"';
					$pagenum = $rk->searchpage;
					$ref = $rk->referrer;
				}
			?>
			<ul class="searcheng" <?php print $bg; ?>>
                                <li class="searcheng"><span class="indent-li-agent"><?php _e('SEARCH ENGINE','wassup'); ?>: <strong><?php print $rk->searchengine." (".__("page","wassup").": $pagenum)"; ?></strong></span></li>
                                <li class="searcheng"><?php _e("KEYWORDS","wassup"); ?>: <strong><a href="<?php print $ref;  ?>" target="_BLANK"><?php print stringShortener($rk->search, round($max_char_len*.52,0)); ?></a></strong></li>
			</ul>
			<?php 
			} ?>
			<?php
			// User is logged in or is a comment's author
			if ($rk->username != "" OR $rk->comment_author != "") {
				if ($rk->username != "") {
					$Ocomment_author = '<li class="users"><span class="indent-li-agent">'.__("LOGGED IN USER","wassup").': <strong>'.$rk->username.'</strong></span></li>
				<li class="users"><span class="indent-li-agent">'.__("COMMENT AUTHOR","wassup").': <strong>'.$rk->comment_author.'</strong></span></li>';
					$unclass = "userslogged";
				} elseif ($rk->comment_author != "") {
					$Ocomment_author = '<li class="users"><span class="indent-li-agent">'.__("COMMENT AUTHOR","wassup").': <strong>'.utf8_decode($rk->comment_author).'</strong></span></li>';
					$unclass = "users";
				}
			?>
			<ul class="<?php print $unclass; ?>">
				<?php print $Ocomment_author; ?>
			</ul>
			<?php  } ?>
			<?php // Referer is a Spider or Bot
			if ($rk->spider != "") {
			if ($rk->feed != "") { ?>
			<ul style="background:#fdeec8;" class="spider">
                                <li class="feed"><span class="indent-li-agent"><?php _e('FEEDREADER','wassup'); ?>: <strong><?php print $rk->spider; ?></strong></span></li>
				<?php if (is_numeric($rk->feed)) { ?>
                                <li class="feed"><span class="indent-li-agent"><?php _e('SUBSCRIBER(S)','wassup'); ?>: <strong><?php print (int)$rk->feed; ?></strong></span></li>
				<?php  } ?>
                        </ul>
                        <?php  } else { ?>
                        <ul class="spider">
                                <li class="spider"><span class="indent-li-agent"><?php _e('SPIDER','wassup'); ?>: <strong><?php print $rk->spider; ?></strong></span></li>
			</ul>
			<?php  }
			} ?>
                        <?php // Referer is a SPAM
                        if ($rk->spam > 0 && $rk->spam < 3) { ?>
                        <ul class="spam">
			<li class="spam"><span class="indent-li-agent">
				<?php _e("Probably SPAM!","wassup"); 
				if ($rk->spam==2) { echo '('.__("Referer Spam","wassup").')'; }
				else { echo '(Akismet '.__("Spam","wassup").')'; }  ?>
				</span></li>
                        </ul>
                        <?php  } elseif ($rk->spam == 3) { ?>
                        <ul class="spam">
			<li class="spam"><span class="indent-li-agent">
				<?php _e("Probably hack attempt!","wassup"); ?>
                        </li></ul>
                        <?php  } ?>
			<?php // User os/browser/language
			if ($rk->spider == "" AND ($rk->os != "" OR $rk->browser != "")) {
			?>
			<ul class="agent">
			<li class="agent"><span class="indent-li-agent">
				<?php if ($rk->language != "") { ?>
				<img src="<?php echo $wpurl.'/wp-content/plugins/'.WASSUPFOLDER.'/img/flags/'.strtolower($rk->language).'.png'.'" alt="'.strtolower($rk->language).'" title="'.__("Language","wassup").': '.strtolower($rk->language); ?>" />
				<?php }
				_e("OS","wassup"); ?>: <strong><?php print $rk->os; ?></strong></span></li>
			<li class="agent"><?php _e("BROWSER","wassup"); ?>: <strong><?php print $rk->browser; ?></strong></li>
			<?php if ($rk->screen_res != "") { ?>
				<li class="agent"><?php _e("RESOLUTION","wassup"); ?>: <strong><?php print $rk->screen_res; ?></strong></li>
			<?php } ?>
			</ul>
			<?php  } ?>
			
			<div style="display: visible;" class="togglecrono navi<?php echo $rk->id ?>">
			<ul class="url">
	<?php 
			$qryCD = $wpdb->get_results("SELECT `timestamp`, urlrequested FROM $table_name WHERE wassup_id='".$rk->wassup_id."' ORDER BY `timestamp` ASC");
			$i=0;
			foreach ($qryCD as $cd) {	
			//$timestamp2 = $cd->timestamp; //redundant
			$time2 = gmdate("H:i:s", $cd->timestamp);
			$char_len = round($max_char_len*.92,0);
			$num = ($i&1);
			if ($num == 0) $classodd = "urlodd"; else  $classodd = "url";
			if ($i >= 1) {
				print '<li class="'.$classodd.' navi'.$rk->id.'"><span class="indent-li-nav">'.$time2.' ->';
				print '<a href="'.wAddSiteurl(htmlspecialchars(html_entity_decode($cd->urlrequested))).'" target="_BLANK">';
				print stringShortener(urlencode(html_entity_decode($cd->urlrequested)), $char_len).'</a></span></li>'."\n";
			}
			$i++;
			} //end foreach qryCD
			print "</ul>";
			?>
			</div>
			<p class="sum-footer"></p>
		</div>
	</div>
<?php	} //end foreach qry

	} //end if itemstot > 0
		print '<br />';
		if ($itemstot >= 10) $p->show();
		print '<br />';
		if (!isset($_GET['limit']) OR $_GET['limit'] == 10 OR $_GET['limit'] == 20) {
		        print $expcol;
		}

	} //end MAIN/DETAILS VIEW ?>

	<p><small>WassUp ver: <?php echo $version.' - '.__("Check the official","wassup").' <a href="http://www.wpwp.org" target="_BLANK">WassUp</a> '.__("page for updates, bug reports and your hints to improve it","wassup").' - <a href="http://trac.wpwp.org/wiki/Documentation" title="Wassup '.__("User Guide documentation","wassup").'">Wassup '.__("User Guide documentation","wassup").'</a>'; ?></small></p>

	</div>	<!-- end wrap -->
<?php 
} //end function Wassup

function CreateTable($table_name="",$withcharset=true) {
	global $wpdb;
	$charset_collate = '';
	//#don't do character set/collation if <  MySQL 4.1
	if (version_compare(mysql_get_server_info(), '4.1.0', '<')) {
		$withcharset=false;
	} elseif (!defined('DB_CHARSET')) {  //DB_CHARSET must be defined in wp-config.php
		$withcharset=false;
	}
	if ($withcharset && $wpdb->supports_collation() && !empty($wpdb->charset)) {
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		//use collate only when charset is specified
		if (!empty($wpdb->collate)) {
			$charset_collate .= " COLLATE $wpdb->collate";
		}
	}
	if ($table_name == "") {
		$table_name = $wpdb->prefix . "wassup";
	}
        $sql_createtable = "CREATE TABLE " . $table_name . " (
  id mediumint(9) NOT NULL auto_increment,
  wassup_id varchar(80) NOT NULL,
  timestamp varchar(20) NOT NULL,
  ip varchar(35) default NULL,
  hostname varchar(150) default NULL,
  urlrequested text,
  agent varchar(255) default NULL,
  referrer text default NULL,
  search varchar(255) default NULL,
  searchpage int(11) default 0,
  os varchar(15) default NULL,
  browser varchar(50) default NULL,
  language varchar(5) default NULL,
  screen_res varchar(15) default NULL,
  searchengine varchar(25) default NULL,
  spider varchar(50) default NULL,
  feed varchar(50) default NULL,
  username  VARCHAR(50) default NULL,
  comment_author VARCHAR(50) default NULL,
  spam VARCHAR(5) default 0,
  UNIQUE KEY id (id),
  KEY idx_wassup (wassup_id(32),timestamp),
  INDEX (os),
  INDEX (browser),
  INDEX (timestamp)
) $charset_collate;";
        require_once( ABSPATH.'wp-admin/upgrade-functions.php');
	dbDelta($sql_createtable);

	//#TODO: check for errors or warnings during table creation
} //end function createTable

function UpdateTable() {
	global $wpdb, $wassup_options;
	$table_name = $wpdb->prefix . "wassup";
	$table_tmp_name = $wpdb->prefix . "wassup_tmp";
	$idx_timestamp = false;	//used for upgrade from <= 1.4.9 
	$idx_wassup = false;	//used for upgrade from <= 1.6 

	// Upgrade from version < 1.3.9 - add 'spam' column to wassup table
	if ($wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'spam'") == "") {
                $sql_add_spam = "ALTER TABLE {$table_name} ADD COLUMN spam VARCHAR(5) DEFAULT '0'";
                $wpdb->query( $sql_add_spam );
        }

	// Upgrade from version <= 1.4.9 - create an index on 'timestamp'
	//#$idx_cols = $wpdb->get_col("SHOW INDEX FROM $table_name","Column_name"); //doesn't work
	//# look for an index on 'timestamp' and make one if doesn't exist
	$result = mysql_query("SHOW INDEX FROM $table_name");
	if ($result) {
		$row_count = mysql_num_rows($result);
		//# look for an index on 'timestamp'
		if ($row_count > 0) {
			while ($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
				if ($row["Column_name"] == "timestamp") {
					$idx_timestamp = true;
				} elseif ( $row["Key_name"] == "idx_wassup") {
					$idx_wassup = true;
				}
                        }
			//# create an index on 'timestamp'
			if (!$idx_timestamp) {
				$wpdb->query("ALTER TABLE {$table_name} ADD INDEX idx_timestamp (timestamp)");
			}
		} //end if row_count
		mysql_free_result($result);
	} //end if result

	// Upgrade from version <= 1.5.1 - increase size of wassup_id
	$wassup_col = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'wassup_id'");
	foreach ($wassup_col as $wID) {
		if ($wID->Type != "varchar(80)") {
			$wpdb->query("ALTER TABLE {$table_name} CHANGE wassup_id wassup_id varchar(80) NULL");
		}
	}

	// Upgrade from version <= 1.6 
	//  - change wassup_id index to combination (wassup_id,timestamp)
	if (!$idx_wassup) {
		$wpdb->query("ALTER TABLE {$table_name} DROP KEY wassup_id");
		$wpdb->query("ALTER TABLE {$table_name} ADD KEY idx_wassup (wassup_id(32),timestamp)");
	}

	// For all upgrades 
	//  - drop and recreate table "wp_wassup_tmp" and optimize "wp_wassup"
	//$wpdb->query("DROP TABLE IF EXISTS $table_tmp_name"); //incorrectly causes an activation error in Wordpress
	mysql_query("DROP TABLE IF EXISTS $table_tmp_name");
	CreateTable($table_tmp_name);
	$wpdb->query("OPTIMIZE TABLE {$table_name}");
} //end function UpdateTable

//Set Wassup_id and cookie (before headers sent)
function wassupPrepend() {
	$wassup_id = "";
	$session_timeout = 1;
	//### Check if this is an ongoing visit or a new hit...
	//#visitor tracking with "cookie"...
	if (isset($_COOKIE['wassup'])) {
		$wassup_cookie = explode('::',$_COOKIE['wassup']);
		$wassup_id = $wassup_cookie[0];
		if (!empty($wassup_cookie[1])) { 
			$wassup_timer = $wassup_cookie[1];
			$session_timeout = ((int)$wassup_timer - (int)time());
		}
	}
	if (empty($wassup_id) || $session_timeout < 1) {
		$ipAddress = "";
		$hostname = "";
		//#### Get the visitor's details from http header...
		if (isset($_SERVER["REMOTE_ADDR"])) {
		if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])){
			//in case of multiple forwarding
		        list($IP) = explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]);
		        $proxy = $_SERVER["REMOTE_ADDR"];
			$hostname = @gethostbyaddr($IP);
			if (empty($hostname) || $hostname == "unknown") {
		        	$hostname = @gethostbyaddr($proxy);
			}
			if (empty($IP) || $IP == "unknown") {
				$IP = $proxy;
				$ipAddress = $_SERVER["REMOTE_ADDR"];
			} else {
				$ipAddress = $proxy.",".$IP;
			}
		}else{
		        list($IP) = explode(",",$_SERVER["REMOTE_ADDR"]);
		        $hostname = @gethostbyaddr($IP);
			$ipAddress = $_SERVER["REMOTE_ADDR"];
		} 
		}
		if (empty($IP)) { $IP = $ipAddress; }
		if (empty($hostname)) { $hostname = "unknown"; }
		$userAgent = (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
		$current_user = wp_get_current_user();
		$logged_user = $current_user->user_login;
		//# Create a new wassup id for this visit from a 
		//#  combination of date/hour/min/ip/hostname/useragent/. 
		//#  It is not unique so that multiple visits from the 
		//#  same ip/userAgent within a 30 minute-period, can be 
		//#  tracked, even when session/cookies is disabled. 
		$temp_id = sprintf("%-060.60s", date('YmdH').str_replace(array(' ','http://','www.','/','.','\'','"',"\\",'$','-','&','+','_',';',',','>','<',':','#','*','%','!','@',')','(',), '', intval(date('i')/30).$IP.strrev($logged_user).strrev($userAgent).strrev($hostname).intval(date('i')/30)).date('HdmY').$hostname.rand());
		//$temp_id = sprintf("%-060.60s", date('YmdH').str_replace(array(' ','http://','www.','/','.','\'','"',"\\",'$','-','&','+','_',';',',','>','<',':','#','*','%','!','@',')','(',), '', intval(date('i')/30).$IP.strrev($userAgent).strrev($hostname).intval(date('i')/30)).date('HdmY').$hostname.rand());

		//Work-around for cookie rejection:
		//#assign new wassup id from "temp_id" 
		//$wassup_id = $temp_id;	//debug
		$wassup_id = md5($temp_id);
		$wassup_timer=((int)time() + 2700); //use 45 minutes timer

		//put the cookie in the oven and set the timer...
		//#this must be done before headers sent
		$cookieurl = parse_url(get_option('home'));
		$cookiedomain = preg_replace('/^www\./','',$cookieurl['host']);
		$cookiepath = $cookieurl['path'];
		$expire = time()+3000;	//expire based on unix time, not on Wordpress time
		$cookievalue = implode('::',array("$wassup_id", "$wassup_timer"));
		setcookie("wassup", "$cookievalue", $expire, $cookiepath, $cookiedomain);
	}
} //end function wassupPrepend

//Track visitors and save record in wassup table, after page is displayed
function wassupAppend() {
	global $wpdb, $wpurl, $wassup_options; //removed unused globals
	$siteurl =  get_bloginfo('siteurl');
	$table_name = $wpdb->prefix . "wassup";	
	$table_tmp_name = $wpdb->prefix . "wassup_tmp";	
	$wassup_settings = get_option('wassup_settings');
	$current_user = wp_get_current_user();
	$logged_user = $current_user->user_login;
	$urlRequested = clean_url($_SERVER['REQUEST_URI']);

	if (empty($logged_user) && $wassup_setting->wassup_hack == "1") {
		$hackercheck = true;
	} else {
		$hackercheck = false;
	}

	if ((!is_admin() && stristr($urlRequested,"/wp-admin/") === FALSE) || $hackercheck) {	//exclude valid wordpress admin page visits

	//#### Get the visitor's details from http header...
	if (isset($_SERVER["REMOTE_ADDR"])) {
		if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])){
			//in case of multiple forwarding
		        list($IP) = explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]);
		        $proxy = $_SERVER["REMOTE_ADDR"];
			$hostname = @gethostbyaddr($IP);
			if (empty($hostname) || $hostname == "unknown") {
		        	$hostname = @gethostbyaddr($proxy);
			}
			if (empty($IP) || $IP == "unknown") {
				$IP = $proxy;
				$ipAddress = $_SERVER["REMOTE_ADDR"];
			} else {
				$ipAddress = $proxy.",".$IP;
			}
		}else{
		        list($IP) = explode(",",$_SERVER["REMOTE_ADDR"]);
		        $hostname = @gethostbyaddr($IP);
			$ipAddress = $_SERVER["REMOTE_ADDR"];
		} 
	}
	if (empty($IP)) { $IP = $ipAddress; }
	if (empty($hostname)) { $hostname = "unknown"; }

	// Get the visitor's resolution, TODO
	/*
	if(isset($HTTP_COOKIE_VARS["users_resolution"])) {
		$screen_res = $HTTP_COOKIE_VARS["users_resolution"];
	} else { //means cookie is not found set it using Javascript
	?>
	<script language="javascript">
	<!--
	writeCookie();
	
	function writeCookie() 
	{
	 var today = new Date();
	 var the_date = new Date("December 31, 2023");
	 var the_cookie_date = the_date.toGMTString();
	 var the_cookie = "users_resolution="+ screen.width +"x"+ screen.height;
	 var the_cookie = the_cookie + ";expires=" + the_cookie_date;
	 document.cookie=the_cookie
		 
	 location = '<?php echo $_SERVER['REQUEST_URI']; ?>';
	}
	//-->
	</script>
	<?php
		$screen_res = $HTTP_COOKIE_VARS["users_resolution"];
	}
	*/

    	$referrer = (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
    	$userAgent = (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
    	$language = (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? attribute_escape($_SERVER['HTTP_ACCEPT_LANGUAGE']) : '');
	//$current_user = wp_get_current_user();	//moved up
	//$logged_user = $current_user->user_login;	//moved up
    	$comment_user = (isset($_COOKIE['comment_author_'.COOKIEHASH]) ? utf8_encode($_COOKIE['comment_author_'.COOKIEHASH]) : '');
   
	$timestamp  = wassup_get_time(); //Add a timestamp to visit... 
	$flag_exclude_url = 0;

	//#####Start recording visit....
	//## wassup is activated and IP not on exclusion list... 
	if ($wassup_options->wassup_active == 1) {	//(moved)
	if (empty($wassup_options->wassup_exclude) ||
	     strstr($wassup_options->wassup_exclude,$ipAddress) == FALSE) {
	
	//## check if url requested is not on exclusion list...
	if (!empty($wassup_options->wassup_exclude_url)) {
		$exclude_url_list = explode(",", $wassup_options->wassup_exclude_url);
		foreach ($exclude_url_list as $exclude_url) {
			if (stristr($urlRequested, trim($exclude_url)) !== FALSE) {
				$flag_exclude_url = 1;
			}
		}
	}
	if ($flag_exclude_url != 1) {
	
	//### Exclude requests for themes, plugins, and favicon from recordings
	if (stristr($urlRequested,"favicon.ico") === FALSE) {		//moved
	if (stristr($urlRequested,"/wp-content/plugins") === FALSE || stristr($urlRequested,"forum") !== FALSE || $hackercheck) {	//moved and modified to allow forum requests
	if (stristr($urlRequested,"/wp-content/themes") === FALSE || stristr($urlRequested,"comment") !== FALSE) {	//moved and modified to allow comment requests
		
	//# More recording exclusion controls
	if ($wassup_options->wassup_loggedin == 1 || !$loggedinuser ) {
	if ($wassup_options->wassup_attack == 1 || stristr($userAgent,"libwww-perl") === FALSE ) {
	if (!is_404() || $hackercheck) {	//don't record 404 pages...

		//##### Extract useful visit information from http header..
		$browser = "";
		$os = "";
		list($browser,$os) = wGetBrowser($userAgent);

	//#===================================================
	//###Start visitor tracking...
	//Work-around for cookie rejection:
	//# Create a temporary id for this visit from a combination of 
	//#  date/hour/min/ip/hostname/useragent/os/browser. 
	//#  It is not unique so that multiple visits from the same 
	//#  ip/userAgent within a  30 minute-period, can be tracked as 
	//#  such, even when session/cookies is disabled. 
	//# An md5 encoded version of temp_id is saved as "wassup_id".
	$temp_id = sprintf("%-060.60s", date('YmdH').str_replace(array(' ','http://','www.','/','.','\'','"',"\\",'$','-','&','+','_',';',',','>','<',':','#','*','%','!','@',')','(',), '', intval(date('i')/30).$IP.strrev($logged_user).strrev($userAgent).strrev($hostname).intval(date('i')/30)).date('HdmY').$hostname.rand());
	$wassup_id = "";
	//Read the cookie for wassup_id
	if (isset($_COOKIE['wassup'])) {
		$wassup_cookie = explode('::',$_COOKIE['wassup']);
		$wassup_id = $wassup_cookie[0];
	}
	/*
	$session_timeout = 1;
	//### Check if this is an ongoing visit or a new hit...
	//#visitor tracking with "session"...
	//# Set savepath directory before session_start()
	$sessionpath = $wassup_options->wassup_savepath;
	if (empty($sessionpath)) { $sessionpath = getSessionpath(); }
	if ($sessionpath != "" && $wassup_options->isWritableFolder($sessionpath)) {
		session_save_path($sessionpath);
	}
	session_start();	//required to use/update $_SESSION
	   
	//#confirm that session is started...
	if (isset($_SESSION)) {
		// Prevent Session Fixation attack (http://shiflett.org/articles/session-fixation)
		if (!isset($_SESSION['initiated'])) { 
			session_regenerate_id(); 
			$_SESSION['initiated'] = true; 
		} 
		//Get session variables...
		if (isset($_SESSION['wassup_id'])) {
			$wassup_id = $_SESSION['wassup_id'];
			$session_timeout = ((int)$_SESSION['wassup_timer'] - (int)time());
			if (isset($_SESSION['spamresult'])) {
				$spamresult = $_SESSION['spamresult'];
			}
			if ( $_SESSION['urlrequest'] == $urlRequested && (($timestamp - (int)$_SESSION['visittime']) < 5)) {
	   			$dup_urlrequest=1;
	   		}
		} 
		//#reset wassup_id for new visitors or when timer is 0...
		if (empty($wassup_id) or $session_timeout < 1) {
			//# don't "destroy" old session in case it is
			//#  in use elsewhere in wordpress ??..
			//if (!empty($wassup_id)) {
			//	session_destroy();
			//	session_start();
			//}
			$_SESSION['wassup_id'] = md5($temp_id);
			//$_SESSION['wassup_id'] = md5(uniqid(rand(), true));
			//#timeout session after 24 minutes
			$_SESSION['wassup_timer'] = ((int)time() + 1440);
			$dup_urlrequest=0;
		}
		$wassup_id = $_SESSION['wassup_id'];
		if ($dup_urlrequest == 0) {	//for dup checks
			$_SESSION['visittime'] = $timestamp;	//for dup checks
			$_SESSION['urlrequest'] = $urlRequested;
		}
		//#SID is empty when session uses cookies...
		//if (defined('SID') && !empty(SID)) {
		//	//#manually add SID to url_rewriter when trans-sid 
		//	//#  is disabled (how to test for this???)...
		//	output_add_rewrite_var(session_name(), htmlspecialchars(session_id()));
		//}
		@session_write_close(); 

	} */
	//### Check if this is an ongoing visit or a new hit...
	/* //#visitor tracking with "cookie"...
	if (isset($_COOKIE['wassup'])) {
		$wassup_cookie = unserialize($_COOKIE['wassup']);
		//$wassup_id = $wassup_cookie['wassup_id'];
		$wassup_timer = $wassup_cookie['timer'];
		if (isset($wassup_cookie['spamresult'])) {
			$spamresult = $wassup_cookie['spamresult'];
		}
		$session_timeout = ((int)$wassup_timer - (int)time());
		if ( $session_timeout < 1) {
			//#reset cookie values when timer is 0
			unset($wassup_cookie);
			$wassup_id = ""; //a new id will be assigned
		}
	} */
	//Work-around for cookie rejection:
	//#assign new wassup id from "temp_id" and include it in dup check
	if (empty($wassup_id)) {
		$wassup_id = md5($temp_id);
		//$wassup_id = $temp_id;	//debug
	}
	//### Check for duplicates. 
	$dup_urlrequest=0;
	// Dup: Hit recorded, ==wassup_id, last visit, <90 secs old, ==URL
	$dups = $wpdb->get_results("SELECT wassup_id, urlrequested, spam, `timestamp` AS hit_timestamp FROM ".$table_tmp_name." WHERE wassup_id='".$wassup_id."' AND `timestamp` >".($timestamp-90)." GROUP BY wassup_id ORDER BY hit_timestamp DESC");
	if (!empty($dups)) {
		$i=0;
		foreach ($dups as $dup) {	//check first record only
			if ($i == 0) {
			       	if ($dup->urlrequested == $urlRequested) {
					$dup_urlrequest=1;
				}
				//retrieve spam check results
				$spamresult = $dup->spam;
			}
			$i=$i+1;
		}
	}
	//
	//#End visitor tracking with cookie/session
	//#===================================================

	//### Exclude duplicates...
	if ($dup_urlrequest == 0) {
		//##### Extract useful visit information from http header..
		if (empty($browser) || strstr($browser,"N/A") || is_feed()) {
			list($spider,$feed) = wGetSpider($userAgent,$hostname,$browser);
		}
		
		//#I prefer to see os/browser info. for spiders/bots.

	//spider exclusion control
	//# Spider exclusion control moved to avoid unneeded tests
	if ($wassup_options->wassup_spider == 1 || $spider == '') {
	   //
	   //#get language/locale info from hostname or referrer data
	   $language = wGetLocale($language,$hostname,$referrer);

	   //# get search string details from referrer data
	   list($searchengine,$search_phrase)=explode("|",wGetSE($referrer));
	   $se=seReferer($referrer);
	   if ($search_phrase != '')  {
	   	if (stristr($searchengine,"images")) {
	   		// ATTENTION Position retrieved by referer in Google Images is 
	   		// the Position number of image NOT the number of items in the page like web search
	   		$searchpage=$se['Pos'];
	   		$searchcountry = explode(".", $se['Se']);
	   	} else {
	   		$searchpage=($se['Pos']/10+1);
	   		$searchcountry = explode(".", $se['Se']);
	   	}
	   	if ($searchcountry[3] != '' ) {
	   		$searchengine .= " ".strtoupper($searchcountry[3]);
	   	} elseif ($searchcountry[2] != '') {
	   		$searchengine .= " ".strtoupper($searchcountry[2]);
	   	}
	   }
	   if ($searchpage == "") {
	   	$searchpage = 0;
	   }

	//### Check for spam...
        $spam = 0;      //a spam default of 0 is required to add record...

        if ( $wassup_options->wassup_spamcheck == 1 ) {
        if ( $wassup_options->wassup_refspam == 1 && !empty($referrer) ) {
                //#first check for referrer spam (faster, if positive)
                //#...but skip when referrer is own blog ($siteurl/$wpurl)
                if (stristr($referrer,$wpurl) === FALSE && stristr($referrer,$siteurl) === FALSE) {
                        // Do a control if it is Referrer Spam
                        if (wGetSpamRef($referrer) == 1) {
                                $spam = 2;
                                $spamresult = $spam;
                        }
                }
        }
        if ( $wassup_options->wassup_spam == 1 && $spam == 0 ) {
                //# some valid spiders to exclude from spam checking
                $goodbot = false;
		if ($hostname!="" && !empty($spider)) {
			if (preg_match('/^(googlebot|msnbot|yahoo\!\ slurp|technorati)/i',$spider)>0 && preg_match('/\.(googlebot|live|msn|yahoo|technorati)\.(com|net)$/i',$hostname)>0){
				$goodbot = true;
			}
		}

                //# No duplicate spam testing in same session unless there 
                //#  is a forum page request or comment...
                if (isset($spamresult) && stristr($urlRequested,"comment") === FALSE && stristr($urlRequested,"forum") === FALSE && empty($comment_user) && empty($_POST['comment'])) {
                        $spam = $spamresult;

                //# No spam check on known bots (google, yahoo,...) unless
                //#  there is a comment or forum page request...
                } elseif (empty($spider) || !$goodbot || stristr($urlRequested,"comment") !== FALSE || stristr($urlRequested,"forum") !== FALSE  || !empty($comment_user) ) { 

                   // Try to search for previous spammer detected by akismet with same IP
                   if (!empty($ipAddress)) {
                           $checkauthor = New CheckComment;
                           $checkauthor->tablePrefix = $wpdb->prefix;
                           $spammerIP = $checkauthor->isSpammer($ipAddress);
                           if ( $spammerIP > 0) {
                                $spam = 1;
                                $spamresult = $spam;
                           }
                   }

                   // search for spammer in badhosts file...
                   if ( $spam == 0) {
                        if (!empty($hostname) && $hostname != "unknown") {
                                if (wGetSpamRef($hostname) == 1) {
                                        $spam = 1;
                                        $spamresult = $spam;
                                }
                        }
                   }

                   //#lastly check for comment spammers using Akismet API
                   //#  Note: this causes "header already sent" errors in some Wordpress configurations
                   if ($spam == 0) {
                        $akismet_key = get_option('wordpress_api_key');
                        $akismet_class = dirname(__FILE__).'/lib/akismet.class.php';
                        if (file_exists($akismet_class) && !empty($akismet_key)) {
                                $comment_user_email = utf8_encode($_COOKIE['comment_author_email_'.COOKIEHASH]);
                                $comment_user_url = utf8_encode($_COOKIE['comment_author_url_'.COOKIEHASH]);
                                include($akismet_class);

                                // load array with comment data 
                                $Acomment = array(
                                        'author' => $comment_user,
                                        'email' => $comment_user_email,
                                        'website' => $comment_user_url,
                                        'body' => $_POST["comment"],
                                        'permalink' => $urlRequested,
                                        'user_ip' => $ipAddress,
                                        'user_agent' => $userAgent
                                );

                                // instantiate an instance of the class 
                                $akismet = new Akismet($wpurl, $akismet_key, $Acomment);
                                // Check if it's spam
                                if ( $akismet->isSpam() ) {
                                        $spam = 1;
                                        $spamresult = $spam;
                                }
                                // test for errors
                                if($akismet->errorsExist()) {
                                        //#error means don't save result in cookie
                                        unset($spamresult);
                                }
                        } //end if file_exists(akismet_class)
                } //end if $spam == 0 

           } //end else $spamresult

        } //end if wassup_spam == 1
        } //end if wassup_spamcheck == 1

	//identify hacker/bad activity attempts and assign spam=3
	if ($spam == 0 && $hackercheck) {
		if (is_admin() || stristr($urlRequested,"/wp-content/plugins")!==FALSE || stristr($urlRequested,"/wp-admin/")!== FALSE) {
			$spam=3;
		}
	}
	// Personally used to debug
	if ($current_user->user_email == "michele@befree.it") {
	}

        //## Final exclusion control is spam...
	if ($spam == 0 OR ($wassup_options->wassup_spam == 1 AND $spam == 1) OR ($wassup_options->wassup_refspam == 1 AND $spam == 2)) {
		/* // #save spam results in session...
                if (isset($spamresult)) {
                        @session_start(); //required to access $_SESSION
                        $_SESSION['spamresult'] = $spamresult;
                        @session_write_close();
		}
		*/
		
		// #Record visit in wassup tables...	
		// Insert the record into the db
		insert_into_wp($table_name, $wassup_id, $timestamp, $ipAddress, $hostname, $urlRequested, $userAgent, $referrer, $search_phrase, $searchpage, $os, $browser, $language, $screen_res, $searchengine, $spider, $feed, $logged_user, $comment_user, $spam);
		// Insert the record into the wassup_tmp table too
		insert_into_wp($table_tmp_name, $wassup_id, $timestamp, $ipAddress, $hostname, $urlRequested, $userAgent, $referrer, $search_phrase, $searchpage, $os, $browser, $language, $screen_res, $searchengine, $spider, $feed, $logged_user, $comment_user, $spam);
		// Delete records older then 3 minutes
		$wpdb->query("DELETE FROM $table_tmp_name WHERE `timestamp`<'".strtotime("-3 minutes", $timestamp)."'");

        } //end if $spam == 0

        } //end if wassup_spider
	} //end if dup_urlrequest == 0

        } //end if !is_404
        } //end if wassup_attack
        } //end if wassup_loggedin

        } //end if !themes
        } //end if !plugins
        } //end if !favicon

	//### Purge old records from wassup table
	//automatic database cleanup of old records...
	if ($wassup_options->delete_auto != "") {
	   // do purge every few visits to keep wassup fast...
	   if ( ((int)$timestamp)%7 == 0 ) {
	   	//use visit timestamp instead of current time for
	   	//  delete parameter
	   	//$to_date = wassup_get_time();
		$from_date = strtotime($wassup_options->delete_auto, $timestamp);
		//#check before doing delete as it could lock the table...
		if ((int)$wpdb->get_var("SELECT COUNT(id) FROM $table_name WHERE `timestamp`<'$from_date'") > 0) {
			$wpdb->query("DELETE FROM $table_name WHERE `timestamp`<'$from_date'");
		}
		// Optimize table once a day
		if ($timestamp > strtotime("24 hours", $wassup_options->wassup_optimize)) {
			$wpdb->query("OPTIMIZE TABLE $table_name");
			$wassup_options->wassup_optimize = wassup_get_time();
                        $wassup_options->saveSettings();
		}
	   }
	} //end if delete_auto

	} //end if wassup_exclude
	} //end if wassup_exclude_url
	} //end if wassup_active
	} //end if !is_admin
	
	//### Notify admin if alert is set and wassup table > alert
	if ($wassup_options->wassup_remind_flag == 1) {
	   // check database size every few visits to keep wassup fast...
	   if ( (time())%7 == 0 ) {
		$table_status = $wpdb->get_results("SHOW TABLE STATUS LIKE '$table_name'");
		foreach ($table_status as $fstatus) {
			$data_lenght = $fstatus->Data_length;
		}
		$tusage = ($data_lenght/1024/1024);
		if ($tusage > $wassup_options->wassup_remind_mb) {
			$recipient = get_bloginfo('admin_email');
			$sender = get_bloginfo('name').' <wassup_noreply@'.parse_url(get_bloginfo('siteurl'),PHP_URL_HOST).'>';
                        $subject = "[ALERT]".__('WassUp Plugin table has reached maximum size!','wassup');
                        $message = __('Hi','wassup').",\n".__('you have received this email because your WassUp Database table at your Wordpress blog','wassup')." (".get_bloginfo('url').") ".__('has reached the maximum value you set in the options menu','wassup')." (".$wassup_options->wassup_remind_mb." Mb).\n\n";
                        $message .= __('This is only a reminder, please take the actions you want in the WassUp options menu','wassup')." (".get_bloginfo('url')."/wp-admin/admin.php?page=wassup-options).\n\n".__('This alert now will be removed and you will be able to set a new one','wassup').".\n\n";
                        $message .= __('Thank you for using WassUp plugin. Check if there is a new version available here:','wassup')." http://wordpress.org/extend/plugins/wassup/\n\n".__('Have a nice day!','wassup')."\n";
                        mail($recipient, $subject, $message, "From: $sender");
                        $wassup_options->wassup_remind_flag = 2;
                        $wassup_options->saveSettings();
		}
	   }
	} //if wassup_remind_flag
	//} //if SECRET_KEY
} //end function wassupAppend()

// Function to insert the item into the db
function insert_into_wp($table_name, $wassup_id, $timestamp, $ipAddress, $hostname, $urlRequested, $userAgent, $referrer, $search_phrase, $searchpage, $os, $browser, $language, $screen_res, $searchengine, $spider, $feed, $logged_user, $comment_user, $spam) {
	global $wpdb;
        if (!empty($table_name) && !empty($wassup_id) && !empty($timestamp)) {
	//double-check that table exists before doing insert to avoid errors showing up on page
	if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {

		if (method_exists($wpdb,'prepare')) {
			$insert = $wpdb->query( $wpdb->prepare("INSERT INTO $table_name (wassup_id, `timestamp`, ip, hostname, urlrequested, agent, referrer, search, searchpage, os, browser, language, screen_res, searchengine, spider, feed, username, comment_author, spam) 
   		           VALUES ( %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s )",
	   		   attribute_escape($wassup_id),
		   	   attribute_escape($timestamp),
		   	   attribute_escape($ipAddress),
	   		   attribute_escape($hostname),
		   	   attribute_escape($urlRequested), 
		   	   attribute_escape($userAgent),
	   		   attribute_escape($referrer),
		   	   attribute_escape($search_phrase),
		   	   attribute_escape($searchpage), 
	   		   attribute_escape($os), 
		   	   attribute_escape($browser), 
		   	   attribute_escape($language), 
	   		   attribute_escape($screen_res), 
		   	   attribute_escape($searchengine), 
		   	   attribute_escape($spider), 
	   		   attribute_escape($feed), 
		   	   attribute_escape($logged_user), 
		   	   attribute_escape($comment_user), 
	   		   attribute_escape($spam)
		   	   ));
                } else {
	                $insert = $wpdb->query("INSERT INTO " . $table_name .
        	           " (wassup_id, `timestamp`, ip, hostname, urlrequested, agent, referrer, search, searchpage, os, browser, language, screen_res, searchengine, spider, feed, username, comment_author, spam) " .
                	   "VALUES (
	                   '".attribute_escape($wassup_id)."',
        	           '".attribute_escape($timestamp)."',
                	   '".attribute_escape($ipAddress)."',
	                   '".attribute_escape($hostname)."',
        	           '".attribute_escape($urlRequested)."', 
                	   '".attribute_escape($userAgent)."',
	                   '".attribute_escape($referrer)."',
        	           '".attribute_escape($search_phrase)."',
                	   '".attribute_escape($searchpage)."', 
	                   '".attribute_escape($os)."', 
        	           '".attribute_escape($browser)."', 
                	   '".attribute_escape($language)."', 
	                   '".attribute_escape($screen_res)."', 
        	           '".attribute_escape($searchengine)."', 
                	   '".attribute_escape($spider)."', 
	                   '".attribute_escape($feed)."', 
        	           '".attribute_escape($logged_user)."', 
                	   '".attribute_escape($comment_user)."', 
	                   '".attribute_escape($spam)."'
        	           )");
		} //end else method_exists(prepare)
        } //end if table exists
        } //end if !empty($table_name)
} //end function insert_into_wp

// This is the function to print out a chart's preview in the dashboard
function wassupDashChart() {
	global $wpdb, $wassup_options;
	if ($wassup_options->wassup_dashboard_chart == 1) {
	$table_name = $wpdb->prefix . "wassup";
	$to_date = wassup_get_time();
	$Chart = New MainItems;
	$Chart->tableName = $table_name;
	$Chart->to_date = $to_date;

	echo '<h3>WassUp Stats <cite><a href="admin.php?page=wassup">More &raquo;</a></cite></h3>';
        echo $Chart->TheChart(1, "400", "125", "", $wassup_options->wassup_chart_type, "bg,s,00000000", "dashboard", "left");
	}
} //end function wassupDashChart

//#Return current PHP session.save_path value (pathname portion)
function getSessionpath() {
	$sessionpath = session_save_path();
	if (strpos($sessionpath, ";") !== FALSE) {
     		$sessionpath = substr($sessionpath, strpos($sessionpath, ";")+1);
	}
	$sessionpath = rtrim($sessionpath,"/");
	return $sessionpath;
}

function wGetQueryPairs($url){
	$parsed_url = parse_url($url);
	$tab=parse_url($url);
	$host = $tab['host'];
	if(key_exists("query",$tab)){
	 $query=$tab["query"];
	 return explode("&",$query);
	} else {
	 return null;
	}
}

function array_search_extended($file,$str_search) {
	foreach($file as $key => $line) {
		if (strpos($line, $str_search)!== FALSE) {
			return $key;
		}
	}
	return false;
}

function seReferer($ref = false){
    $SeReferer = (is_string($ref) ? $ref : mb_convert_encoding(strip_tags($_SERVER['HTTP_REFERER']), "HTML-ENTITIES", "auto"));
    if( //Check against Google, Yahoo, MSN, Ask and others
        preg_match(
        "/[&\?](prev|q|p|w|searchfor|as_q|as_epq|s|query)=([^&]+)/i",
        $SeReferer,$pcs)
    ){
        if(preg_match("/https?:\/\/([^\/]+)\//i",$SeReferer,$SeDomain)){
            $SeDomain    = trim(strtolower($SeDomain[1]));
            $SeQuery    = $pcs[2];
            if(preg_match("/[&\?](start|b|first|stq)=([0-9]*)/i",$SeReferer,$pcs)){
                $SePos    = (int)trim($pcs[2]);
            }
        }
    }
    if(!isset($SeQuery)){
        if( //Check against DogPile
            preg_match(
            "/\/search\/web\/([^\/]+)\//i",
            $SeReferer,$pcs)
        ){
            if(preg_match("/https?:\/\/([^\/]+)\//i",$SeReferer,$SeDomain)){
                $SeDomain    = trim(strtolower($SeDomain[1]));
                $SeQuery    = $pcs[1];
            }
        }
    }
    // We Do Not have a query
    if(!isset($SeQuery)){ return false; }
    $OldQ=$SeQuery;
    $SeQuery=urldecode($SeQuery);
    // The Multiple URLDecode Trick to fix DogPile %XXXX Encodes
    while($SeQuery != $OldQ){
        $OldQ=$SeQuery; $SeQuery=urldecode($SeQuery);
    }
    //-- We have a query
    return array(
        "Se"=>$SeDomain,
        "Query"=>$SeQuery,
        "Pos"=>(int)$SePos,
        "Referer"=>$SeReferer
    );
}

function wGetSE($referrer = null){
	$key = null;
	$lines = array("Alice|search.alice.it|qs|", "Google|www.google.|as_q|", "Google|www.google.|q|", "Google Groups|groups.google.|q|", 
			"Google Images|images.google.|prev|", "Yahoo|search.yahoo.com|p|", "Google Blog|blogsearch.google.|as_q|", "Google Blog|blogsearch.google.|q|",
			"Virgilio|search.virgilio.it|qs|","Arianna|arianna.libero.it|query|","Altavista|.altavista.com|q|","Kataweb|kataweb.it|q|",
			"Il Trovatore|categorie.iltrovatore.it|query|","Il Trovatore|search.iltrovatore.it|q|","2020Search|2020search.c|us|st|pn|1|",
			"abcsearch.com|abcsearch.com|terms|","100Links|100links.supereva.it|q|","Alexa|alexa.com|q|","Alltheweb|alltheweb.com|q|",
			"Aol|.aol.|query|","Aol|aolrecherches.aol.fr|query|","Ask|ask.com|ask|","Ask|ask.com|q|","DMOZ|search.dmoz.org|search|",
			"Dogpile|dogpile.com|q|","Excite|excite.|q|","Godago|.godago.com|keywords|","HotBot|hotbot.*|query|","ixquick|ixquick.com|query|",
			"Lycos|cerca.lycos.it|query|","Lycos|lycos.|q|","Windows Live|search.live.com|q|mkt|","My Search|mysearch.com|searchfor|",
			"My Way|mysearch.myway.com|searchfor|","Metacrawler|metacrawler.|q|","Netscape Search|search.netscape.com|query|","MSN|msn.|q|",
			"Overture|overture.com|Keywords|","Supereva|supereva.it|q|","Teoma|teoma.com|q|","Tiscali|search-dyn.tiscali.|key|","Voil|voila.fr|kw|",
			"Web|web.de|su|","Clarence|search.clarence.com|q|","Gazzetta|search.gazzetta.it|q|","PagineGialle|paginegialle.it|qs|",
			"Jumpy|servizi.mediaset.it|searchWord|","ItaliaPuntoNet|italiapuntonet.net|search|","StartNow|search.startnow.|q|","Search|search.it|srctxt|",
			"Search|search.com|q|", "Good Search|goodsearch.com|Keywords|", "ABC Sok|verden.abcsok.no|q|", "Kvasir|kvasir.no|searchExpr|", 
			"Start.no|start.no|q|", "bluewin.ch|bluewin.ch|query|", "Google Translate|translate.google.|u|");
	foreach($lines as $line_num => $se) {
		list($nome,$url,$key,$lang)=explode("|",$se);
		if(@strpos($referrer,$url)===FALSE) continue;
		// found it!
		// The SE is Google Images
		if ($nome == "Google Images") {
			$variables = wGetQueryPairs($referrer);
			$rightkey = array_search_extended($variables, "images");
			$variables = eregi_replace("prev=/images\?q=", "", urldecode($variables[$rightkey]));
			$variables = explode("&",$variables);
			return ($nome."|".urldecode($variables[0]));
		} else {
			$variables = wGetQueryPairs($referrer);
			$i = count($variables);
			while($i--){
			   $tab=explode("=",$variables[$i]);
			   if($tab[0] == $key){return ($nome."|".urldecode($tab[1]));}
			}
		}
	}
	return null;
}

//extract browser and platform info from a user agent string and
// return the values in an array: 0->browser 1->os. -Helene D. 6/7/08.
function wGetBrowser($agent="") {
	if (empty($agent)) { $agent = $_SERVER['HTTP_USER_AGENT']; }
	$browsercap = array();
	$browscapbrowser = "";
	$browser = "";
	$os = "";
	//check PHP browscap data for browser and platform, when available
	if (ini_get("browscap") != "" ) {
		$browsercap = get_browser($agent,true);
		if (!empty($browsercap['platform'])) {
		if (stristr($browsercap['platform'],"unknown") === false) {
			$os = $browsercap['platform'];
			if (!empty($browsercap['browser'])) {
				$browser = $browsercap['browser'];
			} else {
				$browser = $browsercap['parent'];
			}
			if (!empty($browsercap['version'])) {
				$browser = $browser." ".$browsercap['version'];
			}
		} }
		//reject generic browscap browsers (ex: mozilla, default)
		if (preg_match('/^(mozilla|default|unknown)/i',$browser) > 0) {
			$browscapbrowser = "$browser";	//save just in case
			$browser = "";
		}
	}
	$os = trim($os); 
	$browser = trim($browser);

	//use Detector class when browscap is missing or browser is unknown
	if ( $os == "" || $browser == "") {
		$dip = &new Detector("", $agent);
		$browser =  trim($dip->browser." ".$dip->browser_version);
		$os = trim($dip->os." ".$dip->os_version);

		//use saved browscap data, if Detector had no results
		if (!empty($browscapbrowser) && ($browser == "" || $browser == "N/A")) {
			if ($os != "" && $os != "N/A") {
				$browser = $browscapbrowser;
			}
		}
	}
	return array($browser,$os);
} //end function wGetBrowser

//extract spider and feed info from a user agent string and
// return the values in an array: 0->spider 1->feed
function wGetSpider($agent="",$hostname="", $browser=""){
	if (empty($agent)) { $agent = $_SERVER['HTTP_USER_AGENT']; }
	$ua = $agent;
	$crawler = "";
	$feed = "";
	$os = "";
	//check browscap data for crawler info., when available
	if (ini_get("browscap") != "" ) {
		$browsercap = get_browser($agent,true);
		//if no platform(os), assume crawler...
		if (!empty($browsercap['platform'])) {
			if ( $browsercap['platform'] != "unknown") {
				$os = $browsercap['platform'];
			}
		}
		if (!empty($browsercap['crawler']) || !empty($browsercap['stripper']) || $os == "") {
			if (!empty($browsercap['browser'])) {
				$crawler = $browsercap['browser'];
			} else {
				$crawler = $browsercap['parent'];
			}
			if (!empty($browsercap['version'])) {
				$crawler = $crawler." ".$browsercap['version'];
			}
		}
		//reject unknown browscap crawlers (ex: default)
		if (preg_match('/^(default|unknown)/i',$crawler) > 0) {
			$crawler = "";
		}
	}

	//get crawler info. from a known list
	$crawler = trim($crawler);
	$agent=str_replace(" ","",$agent);
	if (empty($crawler)) {
		$key = null;
	//# query list to identify some feedreaders and bots that don't show their name first in UA string
	//#   format: "Bot Name"|"UserAgent keyword" (no spaces)|(F=feedreader or R=robot)
	$lines = array( "AboutUsBot|AboutUsBot/|R|", 
			"Aggrevator|Aggrevator/0.|F|", 
			"AlestiFeedBot|AlestiFeedBot||", 
			"Alexa|ia_archiver|R|", "AltaVista|Scooter-|R|", 
			"AltaVista|Scooter/|R|", "AltaVista|Scooter_|R|", 
			"AMZNKAssocBot|AMZNKAssocBot/|R|",
			"AppleSyndication|AppleSyndication/|F|",
			"Apple-PubSub|Apple-PubSub/|F|",
			"Ask.com/Teoma|AskJeeves/Teoma)|R|",
			"Ask Jeeves/Teoma|ask.com|R|",
			"AskJeeves|AskJeeves|R|", 
			"BlogBot|BlogBot/|F|", "Bloglines|Bloglines/|F|",
			"Blogslive|Blogslive|F|",
			"BlogsNowBot|BlogsNowBot|F|",
			"BlogPulseLive|BlogPulseLive|F|",
			"IceRocket BlogSearch|BlogSearch/|F|",
			"Charlotte|Charlotte/|R|", 
			"Xyleme|cosmos/0.|R|", "cURL|curl/|R|",
			"Die|die-kraehe.de|R|", 
			"Diggit! Robot|Digger/|R|", 
			"disco/Nutch|disco/Nutch|R|",
			"Emacs-w3|Emacs-w3/v[0-9\.]+|", 
			"ananzi|EMC|", 
			"EnaBot|EnaBot|", 
			"esculapio|esculapio/1.1|", "Esther|esther|", 
			"everyfeed-spider|everyfeed-spider|F|", 
			"Evliya|Evliya|", "nzexplorer|explorersearch|", 
			"eZ publish Validator|eZpublishLinkValidator|",
			"FastCrawler|FastCrawler|R|", 
			"FDSE|(compatible;FDSErobot)|R|", 
			"Feed::Find|Feed::Find|",
			"FeedBurner|FeedBurner|F|",
			"FeedDemon|FeedDemon/|F|",
			"FeedHub FeedFetcher|FeedHub|F|", 
			"Feedreader|Feedreader|F|", 
			"Feedshow|Feedshow|F|", 
			"Feedster|Feedster|F|",
			"FeedTools|feedtools|F|",
			"Feedfetcher-Google|Feedfetcher-google|F|", 
			"Felix|FelixIDE/1.0|", "Wild|Hazel's|", "FetchRover|ESIRover|", 
			"fido|fido/0.9|", 
			"Fish|Fish-Search-Robot|", "Fouineur|Fouineur|", 
			"Freecrawl|Freecrawl|R|", "FunnelWeb|FunnelWeb-1.0|", 
			"gammaSpider|gammaSpider|", "gazz|gazz/1.0|", "GCreep|gcreep/1.0|", 
			"GetRight|GetRight|R|", 
			"GetterroboPlus|straight|", 
			"GetURL|GetURL.rexx|", "Golem|Golem/1.1|", 
			"Googlebot|Googlebot/|R|", 
			"Google|googlebot/|R|","Google Images|Googlebot-Image|R|",
			"Google AdSense|Mediapartners-Google|R|", 
			"Google Desktop|GoogleDesktop|F|", 
			"GreatNews|GreatNews|F|", 
			"Gregarius|Gregarius/|F|",
			"Gromit|Gromit/1.0|", 
			"gsinfobot|gsinfobot|", 
			"Northern|Gulliver/1.1|", "Gulper|Gulper|", 
			"GurujiBot|GurujiBot|", 
			"Harvest|yes|", "havIndex|havIndex/X.xx[bxx]|",
			"heritrix|heritrix/|",
			"HI|AITCSRobot/1.1|",
			"HKU|HKU|", 
			"Hometown|Hometown|", 
			"ht://Dig|htdig/3|R|", "HTMLgobble|HTMLgobble|", "Hyper-Decontextualizer|Hyper|", 
			"iajaBot|iajaBot/0.1|", "IBM_Planetwide|IBM_Planetwide,|", 
			"ichiro|ichiro|", 
			"Popular|gestaltIconoclast/1.0|", 
			"Ingrid|INGRID/0.1|", "Imagelock|Imagelock|", "IncyWincy|IncyWincy/1.0b1|", "Informant|Informant|", 
			"InfoSeek|InfoSeek|", 
			"InfoSpiders|InfoSpiders/0.1|", "Inspector|inspectorwww/1.0|", "IntelliAgent|'IAGENT/1.0'|", 
			"ISC Systems iRc Search|ISCSystemsiRcSearch|", 
			"Israeli-search|IsraeliSearch/1.0|", 
			"IRLIRLbot/|IRLIRLbot|",
			"Italian Blog Rankings|blogbabel|F|", 
			"Jakarta|Jakarta|", 
			"Java|Java/|",
			"JBot|JBot|", 
			"JCrawler|JCrawler/0.2|", 
			"JoBo|JoBo|", "Jobot|Jobot/0.1alpha|", "JoeBot|JoeBot/x.x,|", "The|JubiiRobot/version#|", "JumpStation|jumpstation|", 
			"image.kapsi.net|image.kapsi.net/1.0|R|", 
			"Internet|User-Agent:|", 
			"kalooga/kalooga|kalooga/kalooga|", 
			"Katipo|Katipo/1.0|", "KDD-Explorer|KDD-Explorer/0.1|", 
			"KIT-Fireball|KIT-Fireball/2.0|", 
			"KindOpener|KindOpener|", 
			"kinjabot|kinjabot|", 
			"KO_Yappo_Robot|KO_Yappo_Robot/1.0.4(http://yappo.com/info/robot.html)|", 
			"Krugle|Krugle|", 
			"LabelGrabber|LabelGrab/1.1|",
			"Larbin|larbin_|", "legs|legs|", 
			"libwww-perl|libwww-perl|", 
			"lilina|Lilina|", 
			"Link|Linkidator/0.93|", "LinkWalker|LinkWalker|", 
			"LiteFinder|LiteFinder|", 
			"logo.gif|logo.gif|", 
			"LookSmart|grub-client|",
			"Lsearch/sondeur|Lsearch/sondeur|", 
			"Lycos|Lycos/x.x|", 
			"Magpie|Magpie/1.0|", 
			"MagpieRSS|MagpieRSS|", 
			"Mail.ru|Mail.ru|", 
			"marvin/infoseek|marvin/infoseek|", 
			"Mattie|M/3.8|", 
			"MediaFox|MediaFox/x.y|", 
			"Megite2.0|Megite.com|", 
			"NEC-MeshExplorer|NEC-MeshExplorer|", 
			"MindCrawler|MindCrawler|", 
			"Missigua Locator|Missigua Locator|", 
			"MJ12bot|MJ12bot|", 
			"mnoGoSearch|UdmSearch|", 
			"MOMspider|MOMspider/1.00|", 
			"Monster|Monster/vX.X.X|", 
			"Moreover|Moreoverbot|",
			"Motor|Motor/0.2|", 
			"MSNBot|MSNBOT/0.1|R|", 
			"MSN|msnbot|R|",
			"MSRBOT|MSRBOT|R|", 
			"Muninn|Muninn/0.1|", 
			"Muscat|MuscatFerret/<version>|", 
			"Mwd.Search|MwdSearch/0.1|", 
			"Naver|NaverBot|","Naver|Cowbot|",
			"NDSpider|NDSpider/1.5|", 
			"Nederland.zoek|Nederland.zoek|", 
			"NetCarta|NetCarta|", "NetMechanic|NetMechanic|", 
			"NetScoop|NetScoop/1.0|", 
			"NetNewsWire|NetNewsWire|", 
			"NewsAlloy|NewsAlloy|",
			"newscan-online|newscan-online/1.1|", 
			"NewsGatorOnline|NewsGatorOnline|", 
			"NG/2.0|NG/2.0|", 
			"NHSE|NHSEWalker/3.0|", "Nomad|Nomad-V2.x|", 
			"Nutch/Nutch|Nutch/Nutch|", 
			"ObjectsSearch|ObjectsSearch/0.01|", 
			"Occam|Occam/1.0|", 
			"Openfind|Openfind|", 
			"OpiDig|OpiDig|", 
			"Orb|Orbsearch/1.0|", 
			"OSSE Scanner|OSSE Scanner|", 
			"OWPBot|OWPBot|", 
			"Pack|PackRat/1.0|", "ParaSite|ParaSite/0.21|", 
			"Patric|Patric/0.01a|", 
			"PECL::HTTP|PECL::HTTP|", 
			"PerlCrawler|PerlCrawler/1.0|", 
			"Phantom|Duppies|", "PhpDig|phpdig/x.x.x|", 
			"PiltdownMan|PiltdownMan/1.0|", 
			"Pimptrain.com's|Pimptrain|", "Pioneer|Pioneer|", 
			"Portal|PortalJuice.com/4.0|", "PGP|PGP-KA/1.2|", 
			"PlumtreeWebAccessor|PlumtreeWebAccessor/0.9|", 
			"Poppi|Poppi/1.0|", "PortalB|PortalBSpider/1.0|", 
			"psbot|psbot/|", 
			"R6_CommentReade|R6_CommentReade|", 
			"R6_FeedFetcher|R6_FeedFetcher|", 
			"radianrss|RadianRSS|", 
			"Raven|Raven-v2|", 
			"relevantNOISE|www.relevantnoise.com|",
			"Resume|Resume|", "RoadHouse|RHCS/1.0a|", 
			"RixBot|RixBot|", "Road|Road|", 
			"Robbie|Robbie/0.1|", "RoboCrawl|RoboCrawl|", 
			"RoboFox|Robofox|", "Robot|Robot|", 
			"Robozilla|Robozilla/1.0|", 
			"Rojo|rojo|F|", 
			"Roverbot|Roverbot|", 
			"RssBandit|RssBandit|", 
			"RSSMicro|RSSMicro.com|F|",
			"Ruby|Rfeedfinder|", 
			"RuLeS|RuLeS/1.0|", 
			"Runnk RSS aggregator|Runnk|", 
			"SafetyNet|SafetyNet|", 
			"Sage|(Sage)|F|",
			"SBIder|Site|", 
			"Scooter|Scooter/2.0|", 
			"ScoutJet|ScoutJet|",
			"Search.Aus-AU.COM|not|", 
			"SearchProcess|searchprocess/0.9|", 
			"Seekbot|HTTPFetcher|", 
			"wp-autoblogSimplePie|SimplePie|", 
			"Sitemap Generator|SitemapGenerator|", 
			"Senrigan|Senrigan/xxxxxx|", 
			"SG-Scout|SG-Scout|", "Shai'Hulud|Shai'Hulud|", 
			"Simmany|SimBot/1.0|", 
			"SiteTech-Rover|SiteTech-Rover|", 
			"shelob|shelob|", 
			"Skymob.com|aWapClient|", 
			"Sleek|Sleek|", 
			"Inktomi|Slurp/2.0|", 
			"Snapbot|Snap|",
			"Smart|ESISmartSpider/2.0|", 
			"Snooper|Snooper/b97_01|", "Solbot|Solbot/1.0|", 
			"Sphere Scout|SphereScout|", 
			"Spider|Spider|", "spider_monkey|mouse.house/7.1|", "SpiderBot|SpiderBot/1.0|", "Spiderline|spiderline/3.1.3|", "SpiderView(tm)|SpiderView|", "Site|ssearcher100|", 
			"StackRambler|StackRambler|", 
			"Strategic Board Bot|StrategicBoardBot|", 
			"Suke|suke/*.*|", 
			"SummizeFeedReader|SummizeFeedReader|", 
			"suntek|suntek/1.0|", 
			"SurveyBot|SurveyBot|", 
			"Sygol|http://www.sygol.com|", 
			"Syndic8|Syndic8|F|", 
			"TACH|TACH|", "Tarantula|Tarantula/1.0|",
			"tarspider|tarspider|", "Tcl|dlw3robot/x.y|", 
			"TechBOT|TechBOT|", 
			"Technorati|Technoratibot|",
			"Teemer|Teemer|", 
			"Templeton|Templeton/{version}|",
			"TitIn|TitIn/0.2|", "TITAN|TITAN/0.1|", 
			"Twiceler|cuill.com/twiceler/|R|",
			"UCSD|UCSD-Crawler|", "UdmSearch|UdmSearch/2.1.1|",
			"UniversalFeedParser|UniversalFeedParser|", 
			"UptimeBot|uptimebot|", 
			"URL|urlck/1.2.3|", "URL|URL|", 
			"VadixBot|VadixBot|", 
			"Valkyrie|Valkyrie/1.0|", "Verticrawl|Verticrawlbot|", "Victoria|Victoria/1.0|", "vision-search|vision-search/3.0'|", 
			"void-bot|void-bot/0.1|", 
			"Voila|VoilaBot|",
			"Voyager|Voyager/0.0|", "VWbot|VWbot_K/4.2|", 
			"W3C_Validator|W3C_Validator|",
			"The|w3index|", "W3M2|W3M2/x.xxx|", 
			"w3mir|w3mir|", 
			"w@pSpider|w@pSpider/xxx|", 
			"WallPaper|CrawlPaper/n.n.n|", "the|WWWWanderer|", 
			"Web|root/0.1|", 
			"WebCatcher|WebCatcher/1.0|", 
			"webcollage|webcollage|", 
			"WebCopier|WebCopier|", 
			"WebCopy|WebCopy/(version)|", 
			"webfetcher|WebFetcher/0.8,|", 
			"WebGenBot|WebGenBot|", 
			"Webinator|weblayers|", 
			"weblayers/0.0|WebLinker|", 
			"WebLinker/0.0|WebMirror|", 
			"webLyzard|webLyzard|", 
			"Weblog|wlm-1.1|", 
			"Digimarc|WebReaper|", "WebReaper|webs|", "webs@recruit.co.jp|Websnarf|", "WebVac|webvac/1.0|", "webwalk|webwalk|", 
			"WebWalker|WebWalker/1.10|", "WebWatch|WebWatch|", 
			"WebStolperer|WOLP/1.0|", 
			"WebZinger|none|", 
			"Wells Search II|WellsSearchII|", 
			"Wget|Wget/1.4.0|", 
			"Wget|Wget/1.|",
			"whatUseek|whatUseek_winona/3.0|", 
			"whiteiexpres/Nutch|whiteiexpres/Nutch|",
			"wikioblogs|wikioblogs|", 
			"WikioFeedBot|WikioFeedBot|", 
			"WikioPxyFeedBo|WikioPxyFeedBo|",
			"Wired|wired-digital-newsbot/1.5|", 
			"Wordpress Pingback/Trackback|Wordpress|", 
			"WWWC|WWWC/0.25|", 
			"XGET|XGET/0.7|", 
			"yacybot|yacybot|",
			"MyBlogLog|Yahoo!MyBlogLogAPIClient|F|",
			"Yahoo!|slurp@inktomi|","Yahoo!|Yahoo!Slurp|","Yahoo!|MMCrawler|",
			"Yahoo FeedSeeker|YahooFeedSeeker|",
			"Yandex|Yandex|");
		foreach($lines as $line_num => $spider) {
			list($nome,$key,$typebot)=explode("|",$spider);
			if ($key != "") {
				if(strstr($agent,$key)===FALSE) { 
					continue; 
				} else { 
					$crawler = trim($nome);
					if (!empty($typebot) && $typebot == "F") {
						$feed = $crawler;
					}
				}
			}
		}
	} // end if crawler

	//#If crawler not in list, use first word in user agent for crawler name
	if (empty($crawler)) { 
		if (preg_match("/^(\w+)[\/\ \-\:_\.]/",$ua,$matches) > 0) {
			if (strlen($matches[1]) > 1 && $matches[1] != "Mozilla") { 
				$crawler = $matches[1];
			}
		}
		if (empty($crawler) && !empty($browser)) { 
			$crawler = $browser;
		}
	}

	//#do a feed check and get feed subcribers, if available
	if (preg_match("/([0-9]{1,10})(subscriber)/i",$agent,$subscriber) > 0) {
		// It's a feedreader with some subscribers
		$feed = $subscriber[1];
		if (empty($crawler)) { 
			$crawler = "Feed Reader";
		}
	} elseif (is_feed() || (empty($feed) && preg_match("/(feed|rss)/i",$agent)>0)) {
		if (!empty($crawler)) { 
			$feed = $crawler;
		} else {
			$crawler = "Feed Reader";
			$feed = "feed reader";
		}
	} //end else preg_match subscriber

	//check for spoofers of Google/Yahoo crawlers...
	if ($hostname!="") {
		if (preg_match('/^(googlebot|yahoo\!\ slurp)/i',$crawler)>0 && preg_match('/\.(googlebot|yahoo)\./i',$hostname)==0){
			$crawler = "Spoofer bot";
		}
	} //end if hostname

	return array($crawler,trim($feed));
} //end function wGetSpider

//#get the visitor locale/language
function wGetLocale($language="",$hostname="",$referrer="") {
	//#use country code for language, if it exists in hostname
	if (!empty($hostname) && preg_match("/\.[a-zA-Z]{2}$/", $hostname) > 0) {
		$country = strtolower(substr($hostname,-2));
		if ($country == "uk") { $country = "gb"; } //change UK to GB for consistent language codes
		$language = $country;
	} elseif (strlen($language) >2) {
	   	$langarray = @explode("-", $language);
	   	$langarray = @explode(",", $langarray[1]);
	   	list($language) = @explode(";", strtolower($langarray[0]));
	}
	//#check referrer search string for language/locale code, if any
	if ((empty($language) || $language=="us" || $language=="en") && !empty($referrer)) {
		$country = $language;
		// google referrer syntax: google.com[.country],hl=language
		if (preg_match('/\.google(\.com)?\.(com|([a-z]{2}))?\/.*[&?]hl\=(\w{2})\-?(\w{2})?/',$referrer,$matches)>0) {
			if (!empty($matches[5])) {
				$country = strtolower($matches[5]);
			} elseif (!empty($matches[3])) {
				$country = strtolower($matches[3]);
			} elseif (!empty($matches[4])) {
				$country = strtolower($matches[4]);
			}
		}
		$language = $country;
	}
	//default to "US" if language==en (english)
	if ($language == "en") {
		$language = "us";
	}
	return $language;
} //end function wGetLocale

//# Check input, $referrer against a list of known spammers and 
//#   return "1" if match found. 
//#   All comparisons are case-insensistive and uses the faster string 
//#   functions (stristr) instead of "regular expression" functions.
function wGetSpamRef($referrer) {
	$referrer=htmlentities(strip_tags(str_replace(" ","",html_entity_decode($referrer))));
	$badhostfile= dirname(__FILE__).'/badhosts.txt';
        $key = null;
	if (empty($referrer)) { return null; }	//nothing to check...

	//#Assume any referrer name similar to "viagra/zanax/.."
	//#  is spam and mark as such...
	$lines = array("cialis","viagra","zanax","phentermine");
	foreach ($lines as $badreferrer) {
		if (stristr($referrer, $badreferrer) !== FALSE) { 
			return 1;
		}
	}
	$lines = array("1clickholdem.com", "1ps.biz", "24h.to", "4all-credit.com", "4all-prescription.com", "4u-money.com", "6q.org", "88.to", "always-casino.com",
        "always-credit.com", "andipink.com", "antiquemarketplace.net", "artmedia.com.ru", "asstraffic.com", "at.cx", "available-casino.com", "available-credit.com",
        "available-prescription.com", "base-poker.com", "bayfronthomes.net", "bitlocker.net", "black-poker.com", "blest-money.com", "budgethawaii.net", "bwdow.com",
        "cafexml.com", "cameralover.net", "capillarychromatogr.org", "cash-2u.com", "casino-500.com", "casino-bu.com", "casinos4spain.com", "cheat-elite.com", "clan.ws",
        "computerxchange.com", "conjuratia.com", "credit-4me.com", "credit-dreams.com", "cups.cs.cmu.edu", "de.tc", "dietfacts.com", "doctor-here.com", "doctor-test.com",
        "eu.cx", "fidelityfunding.net", "finance-4all.com", "finestrealty.net", "fortexasholdem.com", "freewarechannel.de", "gb.com", "golfshoot.com", "great-finance.com",
        "great-money.com", "health-livening.com", "here.ws", "hu.tc", "iepills.com", "ihomebroker.com", "including-poker.com", "internettexashold.com", "isdrin.de",
        "iwebtool.com", "jaja-jak-globusy.com", "jobruler.com", "jpe.com", "js4.de", "just-pharmacy.com", "learnhowtoplay.com", "mine-betting.com", "new-doctor.com",
        "nonews.ru", "now-cash.com", "online-pills.us", "online.cx", "only-casino.com", "ourtexasholdem.com", "p.cx", "partyshopcentral.com", "petsellers.net",
        "pharmacy-here.com", "pills-only.com", "plenty-cash.com", "poker-check.com", "poker-spanish.com", "pressemitteilung.ws", "quality-poker.com", "reale-amateure.com",
        "realtorx2.com", "rulen.de", "shop.tc", "sp.st", "spanish-casino-4u.com", "standard-poker.com", "start.bg", "take-mortgage.com", "texasholdfun.com",
        "the-discount-store.com", "unique-pills.com", "unixlover.com", "us.tc", "useful-pills.com", "vadoptions.com", "vcats.com", "vinsider.com", "vjackpot.com",
        "vmousetrap.com", "vplaymate.com", "vselling.com", "vsymphony.com", "vthought.com", "walnuttownfireco.org", "white-pills.com", "wkelleylucas.com", "yourpsychic.net",
        "mature-lessons.com", "wrongsideoftown.com", "wildpass.com", "collegefuckfest.com", "brutalblowjobs.com", "livemarket.com.ua", "allinternal.com", "asstraffic.com",
        "progressiveupdate.net","dating-s.net","ua-princeton.com","royalfreehost.com", "www.texas-va-loan.com", "jmhic.com", "whvc.net", "vegas-hair.com", "owned.com",
        "sml338.org", "kredite-kredit", "buy-2005.com", "vrajitor.com", "ro7kalbe.com", "ca-america.com", "udcorp.com", "walnuttownfireco.org", "yx-colorweaving.com",
        "terashells.com", "chat-nett.com", "exitq.com", "cxa.de", "sysrem03.com", "pharmacy.info", "guide.info", "drugstore.info","vpshs.com", "vp888.net", "coresat.com",
        "psxtreme.com", "freakycheats.com", "cool-extreme.com", "pervertedtaboo.com", "crescentarian.net", "texas-holdem", "yelucie.com", "poker-online.com",  
        "findwebhostingnow.com", "smsportali.net", "6q.org", "flowersdeliveredquick.com", "trackerom.com", "andrewsaluk.com", "4u.net", "4u.com", "doobu.com", "isacommie.com",
        "musicbox1.com", "roody.com", "zoomgirls.net", "cialis-gl-pills.com", "fickenfetzt.com");
	foreach($lines as $line_num => $spammer) {
                if(stristr($referrer,$spammer) !== FALSE) {
                        // find it!
                        return 1;
                }
        }
	//#check for a customized spammer list...
	if (file_exists($badhostfile)) {
		$lines = file($badhostfile,FILE_IGNORE_NEW_LINES);
		foreach($lines as $line_num => $spammer) {
			if(stristr($referrer,trim($spammer)) !== FALSE) {
                        // find it!
			return 1;
			}
		}
	}
	return null;
} //end function wGetSpamRef()

function export_wassup() {
global $wpdb, $table_name;
$table_name = $wpdb->prefix . "wassup";
$filename = 'wassup.' . gmdate('Y-m-d') . '.sql';

//# check for records before exporting...
$numrecords = $wpdb->get_var("SELECT COUNT(wassup_id) FROM $table_name");
if ( $numrecords > 0 ) {
	//TODO: use compressed file transfer when zlib available...
	do_action('export_wassup');
	header('Content-Description: File Transfer');
	header("Content-Disposition: attachment; filename=$filename");
	header('Content-Type: text/plain charset=' . get_option('blog_charset'), true);

	// Function is below
	backup_table($table_name);

	die(); 	//sends output and flushes buffer
} //end if numrecords > 0
} //end function export_wassup()

/**
* Taken partially from wp-db-backup plugin
* Alain Wolf, Zurich - Switzerland
* Website: http://www.ilfilosofo.com/blog/wp-db-backup/
* @param string $table
* @param string $segment
* @return void
*/
function backup_table($table, $segment = 'none') {
	global $wpdb;
	define('ROWS_PER_SEGMENT', 100);

	$table_structure = $wpdb->get_results("DESCRIBE $table");
	if (! $table_structure) {
		$this->error(__('Error getting table details','wassup') . ": $table");
		return FALSE;
	}

	if(($segment == 'none') || ($segment == 0)) {
		// Add SQL statement to drop existing table
		$sql .= "\n\n";
		$sql .= "#\n";
		$sql .= "# " . sprintf(__('Delete any existing table %s','wassup'),$table) . "\n";
		$sql .= "#\n";
		$sql .= "\n";
		$sql .= "#\n";
		$sql .= "# Uncomment if you need\n";
		$sql .= "#DROP TABLE IF EXISTS " . $table . ";\n";
		
		// Table structure
		// Comment in SQL-file
		$sql .= "\n\n";
		$sql .= "#\n";
		$sql .= "# " . sprintf(__('Table structure of table %s','wassup'),$table) . "\n";
		$sql .= "#\n";
		$sql .= "\n";
		$sql .= "#\n";
		$sql .= "# Uncomment if you need\n";
		
		$create_table = $wpdb->get_results("SHOW CREATE TABLE $table", ARRAY_N);
		if (FALSE === $create_table) {
			$err_msg = sprintf(__('Error with SHOW CREATE TABLE for %s.','wassup'), $table);
			print $err_msg;
			$sql .= "#\n# $err_msg\n#\n";
		}
		$sql .= $create_table[0][1] . ' ;';
		
		if (FALSE === $table_structure) {
			$err_msg = sprintf(__('Error getting table structure of %s','wassup'), $table);
			print $err_msg;
			$sql .= "#\n# $err_msg\n#\n";
		}
	
		// Comment in SQL-file
		$sql .= "\n\n";
		$sql .= "#\n";
		$sql .= '# ' . sprintf(__('Data contents of table %s','wassup'),$table) . "\n";
		$sql .= "#\n";
	}
	
	if(($segment == 'none') || ($segment >= 0)) {
		$defs = array();
		$ints = array();
		foreach ($table_structure as $struct) {
			if ( (0 === strpos($struct->Type, 'tinyint')) ||
				(0 === strpos(strtolower($struct->Type), 'smallint')) ||
				(0 === strpos(strtolower($struct->Type), 'mediumint')) ||
				(0 === strpos(strtolower($struct->Type), 'int')) ||
				(0 === strpos(strtolower($struct->Type), 'bigint')) ||
				(0 === strpos(strtolower($struct->Type), 'timestamp')) ) {
					$defs[strtolower($struct->Field)] = $struct->Default;
					$ints[strtolower($struct->Field)] = "1";
			}
		}
		
		// Batch by $row_inc
		
		if($segment == 'none') {
			$row_start = 0;
			$row_inc = ROWS_PER_SEGMENT;
		} else {
			$row_start = $segment * ROWS_PER_SEGMENT;
			$row_inc = ROWS_PER_SEGMENT;
		}
		
		do {	
			if ( !ini_get('safe_mode')) @set_time_limit(15*60);
			$table_data = $wpdb->get_results("SELECT * FROM $table LIMIT {$row_start}, {$row_inc}", ARRAY_A);

			$entries = 'INSERT INTO ' . $table . ' VALUES (';	
			//    \x08\\x09, not required
			$search = array("\x00", "\x0a", "\x0d", "\x1a");
			$replace = array('\0', '\n', '\r', '\Z');
			if($table_data) {
				foreach ($table_data as $row) {
					$values = array();
					foreach ($row as $key => $value) {
						if ($ints[strtolower($key)]) {
							// make sure there are no blank spots in the insert syntax,
							// yet try to avoid quotation marks around integers
							$value = ( '' === $value) ? $defs[strtolower($key)] : $value;
							$values[] = ( '' === $value ) ? "''" : $value;
						} else {
							$values[] = "'" . str_replace($search, $replace, addslashes($value)) . "'";
						}
					}
					$sql .= " \n" . $entries . implode(', ', $values) . ') ;';
				}
				$row_start += $row_inc;
			}
		} while((count($table_data) > 0) and ($segment=='none'));
	}
	
	if(($segment == 'none') || ($segment < 0)) {
		// Create footer/closing comment in SQL-file
		$sql .= "\n";
		$sql .= "#\n";
		$sql .= "# " . sprintf(__('End of data contents of table %s','wp-db-backup'),$table) . "\n";
		$sql .= "# --------------------------------------------------------\n";
		$sql .= "\n";
	}
	print $sql;
} // end backup_table()

// START initializing Widget
function wassup_widget_init() {

        if ( !function_exists('register_sidebar_widget') )
                return;

function wassup_widget($wargs) {
	global $wpdb;
	extract($wargs);
	$wassup_settings = get_option('wassup_settings');
	$wpurl =  get_bloginfo('wpurl');
	$siteurl =  get_bloginfo('siteurl');
	if ($wassup_settings['wassup_widget_title'] != "") $title = $wassup_settings['wassup_widget_title']; else $title = "Visitors Online";
	if ($wassup_settings['wassup_widget_ulclass'] != "") $ulclass = $wassup_settings['wassup_widget_ulclass']; else $ulclass = "links";
	if ($wassup_settings['wassup_widget_chars'] != "") $chars = $wassup_settings['wassup_widget_chars']; else $chars = "18";
	if ($wassup_settings['wassup_widget_searchlimit'] != "") $searchlimit = $wassup_settings['wassup_widget_searchlimit']; else $searchlimit = "5";
	if ($wassup_settings['wassup_widget_reflimit'] != "") $reflimit = $wassup_settings['wassup_widget_reflimit']; else $reflimit = "5";
	if ($wassup_settings['wassup_widget_topbrlimit'] != "") $topbrlimit = $wassup_settings['wassup_widget_topbrlimit']; else $topbrlimit = "5";
	if ($wassup_settings['wassup_widget_toposlimit'] != "") $toposlimit = $wassup_settings['wassup_widget_toposlimit']; else $toposlimit = "5";
	$table_name = $wpdb->prefix . "wassup";
	$table_tmp_name = $wpdb->prefix . "wassup_tmp";
	$to_date = wassup_get_time();
	$from_date = strtotime('-3 minutes', $to_date);

        print $before_widget;

	// Widget Latest Searches
	if ($wassup_settings['wassup_widget_search'] == 1) {
	$query_det = $wpdb->get_results("SELECT search, referrer FROM $table_tmp_name WHERE search!='' GROUP BY search ORDER BY `timestamp` DESC LIMIT ".attribute_escape($searchlimit)."");
	if (count($query_det) > 0) {
		print "$before_title ".__('Last searched terms','wassup')." $after_title";
		print "<ul class='$ulclass'>";
		foreach ($query_det as $sref) {
			print "<li>- <a href='".attribute_escape($sref->referrer)."' target='_blank' rel='nofollow'>".stringShortener(attribute_escape($sref->search), $chars)."</a></li>";
		}
		print "</ul>";
	}
	}

	// Widget Latest Referers
	if ($wassup_settings['wassup_widget_ref'] == 1) {
	$query_ref = $wpdb->get_results("SELECT referrer FROM $table_tmp_name WHERE searchengine='' AND referrer!='' AND referrer NOT LIKE '$wpurl%' GROUP BY referrer ORDER BY `timestamp` DESC LIMIT ".attribute_escape($reflimit)."");
	if (count($query_ref) > 0) {
		print "$before_title ".__('Last referers','wassup')." $after_title";
		print "<ul class='$ulclass'>";
		foreach ($query_ref as $eref) {
			print "<li>- <a href='".attribute_escape($eref->referrer)."' target='_blank' rel='nofollow'>".stringShortener(pregi_replace("#https?://#", "", attribute_escape($eref->referrer)), $chars)."</a></li>";
		}
		print "</ul>";
	}
	}

        // Widget TOP Browsers
        if ($wassup_settings['wassup_widget_topbr'] == 1) {
	$query_topbr = $wpdb->get_results("SELECT count(browser) as top_browser, browser FROM $table_name WHERE browser!='' AND browser NOT LIKE 'N/A%' GROUP BY browser ORDER BY top_browser DESC LIMIT ".attribute_escape($topbrlimit)."");
        if (count($query_topbr) > 0) {
                print "$before_title ".__('Top Browsers','wassup')." $after_title";
                print "<ul class='$ulclass'>";
                foreach ($query_topbr as $etopbr) {
                        print "<li>- ".stringShortener($etopbr->browser, $chars)."</li>";
                }
                print "</ul>";
        }
        }

        // Widget TOP Oses
        if ($wassup_settings['wassup_widget_topos'] == 1) {
	$query_topos = $wpdb->get_results("SELECT count(os) as top_os, os FROM $table_name WHERE os!='' AND os NOT LIKE 'N/A%' GROUP BY os ORDER BY top_os DESC LIMIT ".attribute_escape($toposlimit)."");
        if (count($query_topos) > 0) {
                print "$before_title ".__('Top OS','wassup')." $after_title";
                print "<ul class='$ulclass'>";
                foreach ($query_topos as $etopos) {
                        print "<li>- ".stringShortener($etopos->os, $chars)."</li>";
                }
                print "</ul>";
        }
        }

        // Widget Visitors Online
	$TotWid = New MainItems;
	$TotWid->tableName = $table_tmp_name;
	$TotWid->from_date = $from_date;
	$TotWid->to_date = $to_date;

	$currenttot = $TotWid->calc_tot("count", null, null, "DISTINCT");
	$currentlogged = $TotWid->calc_tot("count", null, "AND  username!=''", "DISTINCT");
	$currentauth = $TotWid->calc_tot("count", null, "AND  comment_author!='' AND username=''", "DISTINCT");

        print $before_title . $title . $after_title;
        print "<ul class='$ulclass'>";
        if ((int)$currenttot < 10) $currenttot = "0".$currenttot;
        print "<li><strong style='padding:0 4px 0 4px;background:#ddd;color:#777'>".$currenttot."</strong> ".__('visitor(s) online','wassup')."</li>";
        if ((int)$currentlogged > 0 AND $wassup_settings['wassup_widget_loggedin'] == 1) {
        if ((int)$currentlogged < 10) $currentlogged = "0".$currentlogged;
                print "<li><strong style='padding:0 4px 0 4px;background:#e7f1c8;color:#777'>".$currentlogged."</strong> ".__('logged-in user(s)','wassup')."</li>";
        }
        if ((int)$currentauth > 0 AND $wassup_settings['wassup_widget_comauth'] == 1) {
        if ((int)$currentauth < 10) $currentauth = "0".$currentauth;
                print "<li><strong style='padding:0 4px 0 4px;background:#fbf9d3;color:#777'>".$currentauth."</strong> ".__('comment author(s)','wassup')."</li>";
	}
	print "<li style='font-size:6pt; color:#bbb;'>".__("powered by", "wassup")." <a style='color:#777;' href='http://www.wpwp.org' title='WassUp - Real Time Visitors Tracking'>WassUp</a></li>";
	print "</ul>";
	print $after_widget;
} //end function wassup_widget

function wassup_widget_control() {
	//global $_POST;
	$wassup_settings = get_option('wassup_settings');
	
	if (isset($_POST['wassup-submit'])) {
		$wassup_settings['wassup_widget_title'] = $_POST['wassup_widget_title'];
		$wassup_settings['wassup_widget_ulclass'] = $_POST['wassup_widget_ulclass'];
		$wassup_settings['wassup_widget_chars'] = $_POST['wassup_widget_chars'];
		$wassup_settings['wassup_widget_loggedin'] = $_POST['wassup_widget_loggedin'];
		$wassup_settings['wassup_widget_comauth'] = $_POST['wassup_widget_comauth'];
		$wassup_settings['wassup_widget_search'] = $_POST['wassup_widget_search'];
		$wassup_settings['wassup_widget_searchlimit'] = $_POST['wassup_widget_searchlimit'];
		$wassup_settings['wassup_widget_ref'] = $_POST['wassup_widget_ref'];
		$wassup_settings['wassup_widget_reflimit'] = $_POST['wassup_widget_reflimit'];
		$wassup_settings['wassup_widget_topbr'] = $_POST['wassup_widget_topbr'];
		$wassup_settings['wassup_widget_topbrlimit'] = $_POST['wassup_widget_topbrlimit'];
		$wassup_settings['wassup_widget_topos'] = $_POST['wassup_widget_topos'];
		$wassup_settings['wassup_widget_toposlimit'] = $_POST['wassup_widget_toposlimit'];
		
		update_option('wassup_settings', $wassup_settings);
		$wassup_settings = get_option('wassup_settings');
	}
	
	?>
	<div class="wrap" style="text-align:left">
        <h3>Wassup Widget</h3>
        <p style="text-align:left"><input type="text" name="wassup_widget_title" size="20" value="<?php echo $wassup_settings['wassup_widget_title'] ?>" /> <?php _e("What title for the widget (default \"Visitors Online\")", "wassup") ?></p>
        <p style="text-align:left"><input type="text" name="wassup_widget_ulclass" size="3" value="<?php echo $wassup_settings['wassup_widget_ulclass'] ?>" /> <?php _e("What style sheet class for &lt;ul&gt; attribute (default \"links\")", "wassup") ?></p>
        <p style="text-align:left"><input type="text" name="wassup_widget_chars" size="3" value="<?php echo $wassup_settings['wassup_widget_chars'] ?>" /> <?php _e("How many characters left? (For template compatibility - default 18)", "wassup") ?></p>
        <p style="text-align:left"><input type="checkbox" name="wassup_widget_loggedin" value="1"<?php if ($wassup_settings['wassup_widget_loggedin'] == 1) echo "CHECKED"; ?> /> <?php _e("Check if you want to show logged-in online users (default Yes)", "wassup") ?></p>
        <p style="text-align:left"><input type="checkbox" name="wassup_widget_comauth" value="1" <?php if ($wassup_settings['wassup_widget_comauth'] == 1) echo "CHECKED"; ?> /> <?php _e("Check if you want to show comment-author online users (default Yes)", "wassup") ?></p>
        <p style="text-align:left"><input type="checkbox" name="wassup_widget_search" value="1" <?php if ($wassup_settings['wassup_widget_search'] == 1) echo "CHECKED"; ?> /> <?php _e("Check if you want to show some last search referers (default Yes)", "wassup") ?></p>
        <p style="text-align:left"><input type="text" name="wassup_widget_searchlimit" size="3" value="<?php echo $wassup_settings['wassup_widget_searchlimit'] ?>" /> <?php _e("How many search referers want to show (default 5)", "wassup") ?></p>
        <p style="text-align:left"><input type="checkbox" name="wassup_widget_ref" value="1" <?php if ($wassup_settings['wassup_widget_ref'] == 1) echo "CHECKED"; ?> /> <?php _e("Check if you want to show some last external referers (default Yes)", "wassup") ?></p>
        <p style="text-align:left"><input type="text" name="wassup_widget_reflimit" size="3" value="<?php echo $wassup_settings['wassup_widget_reflimit'] ?>" /> <?php _e("How many external referers want to show (default 5)", "wassup") ?></p>
        <p style="text-align:left"><input type="checkbox" name="wassup_widget_topbr" value="1" <?php if ($wassup_settings['wassup_widget_topbr'] == 1) echo "CHECKED"; ?> /> <?php _e("Check if you want to show top browsers (default No - enabling it could slow down blog)", "wassup") ?></p>
        <p style="text-align:left"><input type="text" name="wassup_widget_topbrlimit" size="3" value="<?php echo $wassup_settings['wassup_widget_topbrlimit'] ?>" /> <?php _e("How many top browsers want to show (default 5)", "wassup") ?></p>
        <p style="text-align:left"><input type="checkbox" name="wassup_widget_topos" value="1" <?php if ($wassup_settings['wassup_widget_topos'] == 1) echo "CHECKED"; ?> /> <?php _e("Check if you want to show top operating systems (default No - enabling it could slow down blog)", "wassup") ?></p>
        <p style="text-align:left"><input type="text" name="wassup_widget_toposlimit" size="3" value="<?php echo $wassup_settings['wassup_widget_toposlimit'] ?>" /> <?php _e("How many top operating systems want to show (default 5)", "wassup") ?></p>
        <p style="text-align:left"><input type="hidden" name="wassup-submit" id="wassup-submit" value="1" /></p>
        </div>
	<?php
} //end function wassup_widget_control

	$wassup_settings = get_option('wassup_settings');
	if ($wassup_settings['wassup_userlevel'] == "") {
		if ($wassup_settings['wassup_userlevel'] == "") {
			$wassup_settings['wassup_userlevel'] = 8;
			update_option('wassup_settings', $wassup_settings);
	}
	if ($wassup_settings['wassup_refresh'] == "") {
			$wassup_settings['wassup_refresh'] = 3;
			update_option('wassup_settings', $wassup_settings);
		}
	}
		if(function_exists('register_sidebar_widget')) {
			register_sidebar_widget(__('Wassup Widget'), 'wassup_widget'); 
			register_widget_control(array('Wassup Widget', 'widgets'), 'wassup_widget_control', 600, 540);
		}
} //end function wassup_widgit_init

function wassup_sidebar($before_widget='', $after_widget='', $before_title='', $after_title='', $wtitle='', $wulclass='', $wchars='', $wsearch='', $wsearchlimit='', $wref='', $wreflimit='', $wtopbr='', $wtopbrlimit='', $wtopos='', $wtoposlimit='') {
	global $wpdb;
	$wpurl =  get_bloginfo('wpurl');
	$siteurl =  get_bloginfo('siteurl');
	if ($wtitle != "") $title = $wtitle; else $title = "Visitors Online";
	if ($wulclass != "") $ulclass = $wulclass; else $ulclass = "links";
	if ($wchars != "") $chars = $wchars; else $chars = "18";
	if ($wsearchlimit != "") $searchlimit = $wsearchlimit; else $searchlimit = "5";
	if ($wreflimit != "") $reflimit = $wreflimit; else $reflimit = "5";
	if ($wtopbrlimit != "") $topbrlimit = $wtopbrlimit; else $topbrlimit = "5";
	if ($wtoposlimit != "") $toposlimit = $wtoposlimit; else $toposlimit = "5";
	$table_name = $wpdb->prefix . "wassup";
	$table_tmp_name = $wpdb->prefix . "wassup_tmp";
	$to_date = wassup_get_time();
	$from_date = strtotime('-3 minutes', $to_date);

        print $before_widget;
	if ($wsearch == 1) {
	$query_det = $wpdb->get_results("SELECT search, referrer FROM $table_tmp_name WHERE search!='' GROUP BY search ORDER BY `timestamp` DESC LIMIT $searchlimit");
	if (count($query_det) > 0) {
		print "$before_title Last searched terms $after_title";
		print "<ul class='$ulclass'>";
		foreach ($query_det as $sref) {
			print "<li>- <a href='".attribute_escape($sref->referrer)."' target='_blank' rel='nofollow'>".stringShortener(attribute_escape($sref->search), $chars)."</a></li>";
		}
		print "</ul>";
	}
	}

	if ($wref == 1) {
	$query_ref = $wpdb->get_results("SELECT referrer FROM $table_tmp_name WHERE searchengine='' AND referrer!='' AND referrer NOT LIKE '$wpurl%' GROUP BY referrer ORDER BY `timestamp` DESC LIMIT $reflimit");
	if (count($query_ref) > 0) {
		print "$before_title Last referers $after_title";
		print "<ul class='$ulclass'>";
		foreach ($query_ref as $eref) {
			print "<li>- <a href='".attribute_escape($eref->referrer)."' target='_blank' rel='nofollow'>".stringShortener(preg_replace("#https?://#", "", attribute_escape($eref->referrer)), $chars)."</a></li>";
		}
		print "</ul>";
	}
	}

	if ($wtopbr == 1) {
	$query_topbr = $wpdb->get_results("SELECT count(browser) as top_browser, browser FROM $table_name WHERE browser!='' AND browser NOT LIKE 'N/A%' GROUP BY browser ORDER BY top_browser DESC LIMIT $topbrlimit");
	if (count($query_topbr) > 0) {
		print "$before_title Top Browsers $after_title";
		print "<ul class='$ulclass'>";
		foreach ($query_topbr as $etopbr) {
			print "<li>- ".stringShortener(attribute_escape($etopbr->browser), $chars)."</li>";
		}
		print "</ul>";
	}
	}

	if ($wtopos == 1) {
	$query_topos = $wpdb->get_results("SELECT count(os) as top_os, os FROM $table_name WHERE os!='' AND os NOT LIKE 'N/A%' GROUP BY os ORDER BY top_os DESC LIMIT $toposlimit");
	if (count($query_topos) > 0) {
		print "$before_title Top OS $after_title";
		print "<ul class='$ulclass'>";
		foreach ($query_topos as $etopos) {
			print "<li>- ".stringShortener(attribute_escape($etopos->os), $chars)."</li>";
		}
		print "</ul>";
	}
	}

	$TotWid = New MainItems;
	$TotWid->tableName = $table_tmp_name;
	$TotWid->from_date = $from_date;
	$TotWid->to_date = $to_date;

	$currenttot = $TotWid->calc_tot("count", null, null, "DISTINCT");
	$currentlogged = $TotWid->calc_tot("count", null, "AND  username!=''", "DISTINCT");
	$currentauth = $TotWid->calc_tot("count", null, "AND  comment_author!=''' AND username=''", "DISTINCT");

	print $before_title . $title . $after_title;
	print "<ul class='$ulclass'>";
	if ((int)$currenttot < 10) $currenttot = "0".$currenttot;
	print "<li><strong style='padding:0 4px 0 4px;background:#ddd;color:#777'>".$currenttot."</strong> visitor(s) online</li>";
	if ((int)$currentlogged > 0 AND $wassup_settings['wassup_widget_loggedin'] == 1) {
	if ((int)$currentlogged < 10) $currentlogged = "0".$currentlogged;
		print "<li><strong style='padding:0 4px 0 4px;background:#e7f1c8;color:#777'>".$currentlogged."</strong> logged-in user(s)</li>";
	}
	if ((int)$currentauth > 0 AND $wassup_settings['wassup_widget_comauth'] == 1) {

	if ((int)$currentauth < 10) $currentauth = "0".$currentauth;
		print "<li><strong style='padding:0 4px 0 4px;background:#fbf9d3;color:#777'>".$currentauth."</strong> comment author(s)</li>";
	}
	print "<li style='font-size:6pt; color:#bbb;'>".__("powered by", "wassup")." <a style='color:#777;' href='http://www.wpwp.org/' title='WassUp - Real Time Visitors Tracking'>WassUp</a></li>";
	print "</ul>";
	print $after_widget;
} //end function wassup_sidebar

//### Add hooks after functions have been defined 
//## General hooks
add_action('init', 'wassup_init');
add_action("widgets_init", "wassup_widget_init");

//## Wassup Admin filters
register_activation_hook(__FILE__, 'wassup_install');
register_deactivation_hook(__FILE__, 'wassup_uninstall');
//add hooks for wassup admin header functions
add_action('admin_head', 'add_wassup_css');
add_action('admin_menu', 'wassup_add_pages');
add_action('activity_box_end', 'wassupDashChart');

//## Wassup visitor tracking hooks
//record visit after page is displayed to keep page load fast
add_action('shutdown', 'wassupAppend');
//add_action('send_headers', 'wassupAppend'); //slows down page load
add_action('wp_head', 'add_wassup_meta_info');
?>
