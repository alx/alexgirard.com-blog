<?php
/* #######
 * ##  wassupOptions - A PHP Class for Wassup plugin option settings.
 * ##    Contains variables and functions used to set or change wassup 
 * ##    settings in Wordpress' wp_options table and to output those
 * ##    values for use in forms.
 * ##  Author: Helene D. 2/24/08
 */
class wassupOptions {
	/* general/detail settings */
	var $wassup_refresh = "3";
	var $wassup_userlevel = "8";
	var $wassup_screen_res = "800";
	var $wassup_default_type = "";
	var $wassup_default_limit = "10";
	var $wassup_top10 ;
	var $wassup_dashboard_chart;
	var $wassup_time_format;	//new

	/* recording settings */
	var $wassup_active = "1";
	var $wassup_loggedin = "1";
	var $wassup_spider = "1";
	var $wassup_attack = "1";
	var $wassup_hack = "1";	//new - to identify/record break-in attempts
	var $wassup_exclude;
	var $wassup_exclude_url;

	/* spam settings */
	var $wassup_spamcheck;
        var $wassup_spam;
        var $wassup_refspam;

	/* table/file management settings */
	var $wassup_savepath;
	var $delete_auto;
        var $delete_auto_size;
	var $wassup_remind_mb;
	var $wassup_remind_flag;
	var $wassup_uninstall;	//for complete uninstall of wassup
	var $wassup_optimize;	//for optimize table once a day

	/* chart display settings */
	var $wassup_chart;
	var $wassup_chart_type;

	/* widget settings */
	var $wassup_widget_title;
	var $wassup_widget_ulclass;
	var $wassup_widget_loggedin;
	var $wassup_widget_comauth;
	var $wassup_widget_search;
	var $wassup_widget_searchlimit;
	var $wassup_widget_ref;
	var $wassup_widget_reflimit;
	var $wassup_widget_topbr;
	var $wassup_widget_topbrlimit;
	var $wassup_widget_topos;
	var $wassup_widget_toposlimit;
	var $wassup_widget_chars;

	/* temporary action settings */
	var $wassup_alert_message;	//used to display alerts
	var $wmark;
	var $wip;
	var $whash = "";	//wp_hash value used by action.php

	/* Constructor */
	function wassupoptions() {
		//# initialize class variables with current options 
		//# or with defaults if none
		$this->loadSettings();
	}

	/* Methods */
	function loadDefaults() {
		$this->wassup_active = "1";
		$this->wassup_loggedin = "1";
		$this->wassup_spider = "1";
		$this->wassup_attack = "1";
		$this->wassup_hack = "1";
		$this->wassup_spamcheck = "1";
        	$this->wassup_spam = "1";
        	$this->wassup_refspam = "1";
		$this->wassup_exclude = "";
		$this->wassup_exclude_url = "";
		$this->wassup_savepath = null;
		$this->wassup_chart = "1";
		$this->wassup_chart_type = "2";
		$this->delete_auto = "never";
        	$this->delete_auto_size = "0";
		$this->wassup_remind_mb = "0";
		$this->wassup_remind_flag = "0";
		$this->wassup_refresh = "3";
		$this->wassup_userlevel = "8";
		$this->wassup_screen_res = "800";
		$this->wassup_default_type = "everything";
		$this->wassup_default_limit = "10";
		$this->wassup_dashboard_chart = "0";
		$this->wassup_time_format = "24";
		$this->wassup_widget_title = "Visitors Online";
		$this->wassup_widget_ulclass = "links";
		$this->wassup_widget_loggedin = "1";
		$this->wassup_widget_comauth = "1";
		$this->wassup_widget_search = "1";
		$this->wassup_widget_searchlimit = "5";
		$this->wassup_widget_ref = "1";
		$this->wassup_widget_reflimit = "5";
		$this->wassup_widget_topbr = "1";
		$this->wassup_widget_topbrlimit = "5";
		$this->wassup_widget_topos = "1";
		$this->wassup_widget_toposlimit = "5";
		$this->wassup_widget_chars = "18";
		$this->wassup_alert_message = "";
		$this->wassup_uninstall = "0";
		$this->wassup_optimize = wassup_get_time();
		$this->wassup_top10 = serialize(array("topsearch"=>"1",
					"topreferrer"=>"1",
					"toprequest"=>"1",
					"topbrowser"=>"1",
					"topos"=>"1",
					"toplocale"=>"0",
					"topfeed"=>"0",
					"topcrawler"=>"0",
					"topvisitor"=>"0",
					"topreferrer_exclude"=>""));
		$this->whash = $this->get_wp_hash();
	}

	//#Load class variables with current options or with defaults 
	function loadSettings() {
		//# load class variables with current options or load
		//#   default settings if no options set.
		$options_array = get_option('wassup_settings');
		if (empty($options_array)) {
			$this->loadDefaults();
		} else {
			foreach ($options_array as $optionkey => $optionvalue) {
				//if (isset($this->$optionkey)) { //returns false for null values
				if (array_key_exists($optionkey,$this)) {
					$this->$optionkey = $optionvalue;
				}
			}
		}
		return true;
	}

	//#Save class variables to the Wordpress options table
	function saveSettings() {
		//#  convert class variables into an array and save using
		//#  Wordpress functions, "update_option" or "add_option"
		//#convert class into array...
		$settings_array = array();
		foreach (array_keys(get_class_vars(get_class($this))) as $k) {
			$settings_array[$k] = $this->$k;
		}
		//#save array to options table...
		$options_check = get_option('wassup_settings');
		if (empty($options_check)) {
			add_option('wassup_settings', $settings_array, 'Options for WassUp');
		} else {
			update_option('wassup_settings', $settings_array);
		}
		return true;
	}

	function deleteSettings() {
		//#delete the contents of the options table...
		delete_option('wassup_settings');
	}

	//#Return an array containing all possible values of the given 
	//#  class variable, $key. For use in form validation, etc.
	function getItemOptions($key="",$meta="") {
		$item_options = array();
		$item_options_meta = array();
		if ($key == "wassup_screen_res") {
			$item_options = array("640","800","1024","1200");
			$item_options_meta = array("&nbsp;640",
				"&nbsp;800",
				"1024",
				"1200");
		} elseif ($key == "wassup_userlevel") {
			$item_options = array("","8","6","2");
			$item_options_meta = array("--",
				__("Administrators","wassup"),
				__("Contributors","wassup"),
				__("Authors","wassup"));
		} elseif ($key == "wassup_chart_type") {
			$item_options = array("1","2");
			$item_options_meta = array(
				__("One - two lines chart one axis","wassup"),
				__("Two - two lines chart two axes","wassup"));
		} elseif ($key == "wassup_default_type") {
			$item_options = array("everything","spider","nospider","spam","nospam","nospamspider","loggedin","comauthor","searchengine","referrer");
			$item_options_meta = array(
				__("Everything","wassup"),
				__("Spider","wassup"),
				__("No spider","wassup"),
				__("Spam","wassup"),
				__("No Spam","wassup"),
				__("No Spam, No Spider","wassup"),
				__("Users logged in","wassup"),
				__("Comment authors","wassup"),
				__("Referer from search engine","wassup"),
				__("Referer from ext link","wassup"));
		} elseif ($key == "wassup_default_limit") {
			$item_options = array("10","20","50","100");
			$item_options_meta = array("&nbsp;10",
				"&nbsp;20",
				"&nbsp;50",
				"100");
		} elseif ($key == "delete_auto") {
			$item_options = array("never","-1 day","-1 week","-1 month","-3 months","-6 months","-1 year");
			$item_options_meta = array(
				__("Don't delete anything","wassup"),
				__("24 hours","wassup"),
				__("1 week","wassup"),
				__("1 month","wassup"),
				__("3 months","wassup"),
				__("6 months","wassup"),
				__("1 year","wassup"));
		} elseif (!empty($key)) {	//enable/disable is default
			$item_options =  array("1","0");
			$item_options_meta =  array("Enable","Disable");
		}
		if ($meta == "meta") {
			return $item_options_meta;
		} else {
			return $item_options;
		}
	} //end getItemValues

	//#generates <options> tags for the given class variable, $itemkey 
	//#  for use in a <select> form.
	function showFormOptions ($itemkey="",$selected="",$optionargs="") {
		$form_items =$this->getItemOptions($itemkey);
		if (count($form_items) > 0) {
			$form_items_meta = $this->getItemOptions($itemkey,"meta");
			if (empty($selected)) { 
				if (!empty($this->$itemkey)) {
					$selected = $this->$itemkey;
				} else { 
					$selected = $form_items[0];
				}
			}
			foreach ($form_items as $k => $option_item) {
	        		echo "\n\t\t".'<option value="'.$optionargs.$option_item.'"';
	        		if ($selected == $option_item) { echo ' SELECTED>'; }
				else { echo '>'; }
				echo $form_items_meta[$k].'&nbsp;&nbsp;</option>';
			}
		}
	} //end showFormOptions


	//#Sets the class variable, wassup_savepath, with the given 
	//#  value $savepath
	function setSavepath($savepath="") {
		$savepath = rtrim($savepath,"/");
		$siteurl = rtrim(get_bloginfo('siteurl'),"/");
		if (!empty($savepath)) {
			//remove site URL from path in case user entered it
			if (strpos($savepath, $siteurl) === 0) {
				$tmppath=substr($savepath,strlen($siteurl)+1);
			} elseif (strpos($savepath,'/') === 0 && !$this->isWritableFolder($savepath)) {
				$tmppath=substr($savepath,1);
			} elseif (strpos($savepath,'./') === 0 ) {
				$tmppath=substr($savepath,2);
			} else { 
				$tmppath = $savepath;
			}
			//append website root or home directory to relative paths...
			if (preg_match('/^[a-zA-Z]/',$tmppath) > 0 || strpos($tmppath,'../') === 0) {
				if (!empty($_ENV['DOCUMENT_ROOT'])) {
					$tmppath = rtrim($_ENV['DOCUMENT_ROOT'],'/').'/'.$tmppath;
				} elseif (!empty($_ENV['HOME'])) {
					$tmppath = rtrim($_ENV['HOME'],'/').'/'.$tmppath;
				}
				if ($this->isWritableFolder($tmppath)) {
					$savepath = $tmppath;
				}
			} 
		}
		$this->wassup_savepath = $savepath;
	}

	//#Return true if the given directory path exists and is writable
	function isWritableFolder($folderpath="") {
		$folderpath=trim($folderpath);	//remove white spaces
		if (!empty($folderpath) && strpos($folderpath,'http://') !== 0 ) {
			if (file_exists($folderpath)) { 
				$testfile = rtrim($folderpath,"/")."/temp".time().'.txt';
				//#check that the directory is writable...
				if (@touch($testfile)) { unlink($testfile); }
				else { return false; }
			} else {
				return false;
			}
		} else {
			return false;
		}
		return true;
	}

	//#Set a wp_hash value and return it
	function get_wp_hash($hashkey="") {
		$wassuphash = "";
		if (function_exists('wp_hash')) { 
			if (empty($hashkey)) {
				if (defined('SECRET_KEY')) { 
					$hashkey = SECRET_KEY;
				} else { 
					$hashkey = "wassup";
				}
			}
			$wassuphash = wp_hash($hashkey);
		}
		return $wassuphash;
	} //end function get_wp_hash

	//#show a system message in Wassup Admin menus
	function showMessage($message="") {
		if (empty($message) && !empty($this->wassup_alert_message)) {
			$message = $this->wassup_alert_message;
		}
		//#check for error message/notice message
		if (stristr($message,"error") !== FALSE || stristr($message,"problem") !== FALSE) {
			echo '<div class="fade error" id="wassup-error"><p style="color:#d00;padding:10px;">'.$message;
			//print_r($this); // #debug
			echo '</p></div>'."\n";
		} else {
			echo '<div class="fade updated" id="wassup-message"><p style="color:#040;padding:10px;">'.$message;
			//print_r($this); // #debug
			echo '</p></div>'."\n";
		}
	} //end showMessage

	function showError($message="") {
		$this->showMessage($message);
	}
} //end class wassupOptions
?>
