<?php
// action.php -- perform an action that renders an output to the browser

//force browser to disable caching so action.php works as an ajax request
/* header("Expires: Fri, 22 Jun 2007 05:00:00 GMT"); // Date in the past
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
// HTTP/1.1
header('Cache-Control: no-store, no-cache, must-revalidate');
*/

//#check for required files and include them
if (!function_exists('get_bloginfo')) {
	if (!defined('ABSPATH')) {
		$file = preg_replace('/\\\\/', '/', __FILE__);
		$wpabspath=substr($file,0,strpos($file, '/wp-content/')+1);
		//don't call wp-blog-header.php as it will insert headers from plugins that we don't want here.
		//if (file_exists($wpabspath. 'wp-blog-header.php')) {
		//	include_once($wpabspath. 'wp-blog-header.php');
		//}
	} else {
		$wpabspath=ABSPATH;
	}
	
	if (file_exists($wpabspath. 'wp-config.php')) {
        	include_once($wpabspath.'wp-config.php');
	} else {
		//Note: localization functions, _e() and __(), are not used
		//  here because they would not be defined if this error 
		//  occurred
		echo '<span style="color:red;">Action.php ERROR: file not found, '.$wpabspath.'wp-config.php</span>';
		die();
	}
}
if (!function_exists('stringShortener')) {
       if (file_exists(dirname(__FILE__). '/main.php')) {
		include_once(dirname(__FILE__). '/main.php');
	} else {
		echo '<span style="font-color:red;">Action.php '.__("ERROR: file not found","wassup").', '.dirname(__FILE__).'/main.php</span>';
		exit();
	}
}
//echo "\n"; //send headers
//echo "Debug: Starting action.php from directory ".dirname(__FILE__).".  ABSPATH=".$wpabspath.".<br />\n"; //debug

//#set required variables
$siteurl =  get_bloginfo('siteurl');
$wpurl =  get_bloginfo('wpurl');
$table_name = $wpdb->prefix . "wassup";
$wassup_settings = get_option('wassup_settings');
if (!defined('WASSUPFOLDER')) {
	define('WASSUPFOLDER', dirname(dirname(__FILE__)));
}

//#do a hash check
$hashfail = true;
if (isset($_GET['whash']) && $_GET['whash'] == $wassup_settings['whash']) {
	$hashfail = false;
}

//#perform an "action" and display the results
if (!$hashfail) {
	//
	// ### Separate "delete" action because it has no output
	// ACTION: DELETE ON THE FLY FROM VISITOR DETAILS VIEW
	if ($_GET['action'] == "delete" && $_GET['id'] != "" ) {
		if (method_exists($wpdb,'prepare')) {
			$wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE wassup_id='%s'", urlencode(attribute_escape($_GET['id']))));
		} else {
			$wpdb->query("DELETE FROM $table_name WHERE wassup_id='".urlencode(attribute_escape($_GET['id']))."'");
		}
	} else { 
	//
	// ### Begin actions that have output...
		//#debug...
		//error_reporting(E_ALL | E_STRICT);	//debug, E_STRICT=php5 only
		//ini_set('display_errors','On');	//debug
	?>
<html>
<head>
	<link rel="stylesheet" href="<?php echo $wpurl.'/wp-content/plugins/'.WASSUPFOLDER; ?>/wassup.css" type="text/css" />
	<style type="text/css">.top10 { color: #542; }</style>
</head>
<body>
	<?php //#retrieve command-line arguments
	if (isset($_GET['to_date'])) $to_date = urlencode(attribute_escape($_GET['to_date']));
	else $to_date = wassup_get_time();
	if (isset($_GET['from_date'])) $from_date = urlencode(attribute_escape($_GET['from_date']));
	else $from_date = ($to_date - 3);

	if (isset($_GET['width'])) {
		if (is_numeric($_GET['width'])) $max_char_len = ($_GET['width'])/10;
	}
	if (isset($_GET['rows'])) {
		if (is_numeric($_GET['rows'])) $rows = $_GET['rows'];
	}

	//#check that $to_date is a number
	if (!is_numeric($to_date)) { //bad date sent
		echo '<span style="color:red;">Action.php '.__("ERROR: bad date","wassup").', '.$to_date.'</span>';
		exit();
	}

	//#perform action and display output
	//
	// ACTION: RUN SPY VIEW
	if ($_GET['action'] == "spy") {
		if (empty($rows)) { $rows = 999; }
		spyview($from_date,$to_date,$rows);

	// ACTION: SUMMARY PIE CHART
	} elseif ($_GET['action'] == "piechart") {
		// Prepare Pie Chart
		$Tot = New MainItems;
		$Tot->tableName = $table_name;
		$Tot->from_date = $from_date;
		$Tot->to_date = $to_date;
		$items_pie[] = $Tot->calc_tot("count", $search, "AND spam>0", "DISTINCT");
		$items_pie[] = $Tot->calc_tot("count", $search, "AND searchengine!='' AND spam=0", "DISTINCT");
		$items_pie[] = $Tot->calc_tot("count", $search, "AND searchengine='' AND referrer NOT LIKE '%".$this->WpUrl."%' AND referrer!='' AND spam=0", "DISTINCT");
		$items_pie[] = $Tot->calc_tot("count", $search, "AND searchengine='' AND (referrer LIKE '%".$this->WpUrl."%' OR referrer='') AND spam=0", "DISTINCT"); ?>
		<div style="text-align: center"><img src="http://chart.apis.google.com/chart?cht=p3&amp;chco=0000ff&amp;chs=600x300&amp;chl=Spam|Search%20Engine|Referrer|Direct&amp;chd=<?php chart_data($items_pie, null, null, null, 'pie'); ?>" /></div>


	<?php
	// ACTION: DISPLAY RAW RECORDS
	} elseif ($_GET['action'] == "displayraw") {
		$raw_table = $wpdb->get_results("SELECT ip, hostname, agent, referrer, search, searchpage, os, browser, language FROM $table_name WHERE wassup_id='".urlencode(attribute_escape($_GET['wassup_id']))."' ORDER BY timestamp ASC LIMIT 1"); ?>
		<div><h2><?php _e("Raw data","wassup"); ?>:</h2>
		<ul style="list-style-type:none;padding:20px 0 0 30px;">
		<?php foreach ($raw_table as $rt) { ?>
			<li><?php echo __("IP","wassup").": ".$rt->ip; ?></li>
			<li><?php echo __("Hostname","wassup").": ".$rt->hostname; ?></li>
			<li><?php echo __("User Agent","wassup").": ".$rt->agent; ?></li>
			<li><?php echo __("Referrer","wassup").": ".urldecode($rt->referrer); ?></li>
			<?php if ($rt->search != "") { ?>
			<li><?php echo __("Search","wassup").": ".$rt->search; ?></li>
			<?php }
			if ($rt->os != "") { ?> 
			<li><?php echo __("OS","wassup").": ".$rt->os; ?></li>
			<?php }
			if ($rt->browser != "") { ?> 
			<li><?php echo __("Browser","wassup").": ".$rt->browser; ?></li>
			<?php }
			if ($rt->language != "") { ?> 
			<li><?php echo __("Language","wassup").": ".$rt->language; ?></li>
			<?php }
		} //end foreach ?>
		</ul>
		</div>

	<?php
	// ACTION: RUN TOP TEN
	} elseif ($_GET['action'] == "topten") {
		$top_ten = unserialize($wassup_settings['wassup_top10']);
		$sitedomain = parse_url($siteurl);
		//$sitedomain = $sitedomain['host'];
		$sitedomain = preg_replace('/^www\./i','',$sitedomain['host']);

		if (empty($max_char_len)) {
			$max_char_len = ($wassup_settings['wassup_screen_res'])/10;
		}
		//#add an extra width offset when columns count < 5
		$col_count = 0;
		foreach ($top_ten as $topitem) {
			if ($topitem == 1) { $col_count = $col_count+1; }
		}
		if ($col_count > 0 && $col_count < 5 ) {
			$widthoffset = (($max_char_len*(5 - $col_count))/$col_count)*.4; //just a guess
		} else { 
			$widthoffset = 0;
		}
		//extend page width to make room for more than 5 columns
		$pagewidth = $wassup_settings['wassup_screen_res'];
		if ($col_count > 5) {
			$pagewidth = $pagewidth+17*($col_count-5);
		}

		//only exclude spam if it is being recorded
		if ($wassup_settings['wassup_spamcheck'] == 1) {
			$spamselect = "AND spam=0";
		} else {
			$spamselect = "";
		} 
	?>
	<div id="toptenchart" style="width:<?php echo $pagewidth; ?>px;">
		<table width="100%" border=0>
		<tr valign="top">
		<?php
		//#output top 10 searches
		if ($top_ten['topsearch'] == 1) {
			$top_results =  $wpdb->get_results("SELECT count(search) as top_search, search, referrer FROM $table_name WHERE timestamp BETWEEN $from_date AND $to_date AND search!='' $spamselect GROUP BY search ORDER BY top_search DESC LIMIT 10");
			$char_len = round(($max_char_len*.30)+$widthoffset,0);
		?>
		<td style="min-width:<?php echo ($char_len-5); ?>px;">
		<ul class="charts">
			<li class="chartsT"><?php _e("TOP QUERY", "wassup"); ?></li>
		<?php 
		foreach ($top_results as $top10) { ?>
			<li class="charts"><?php echo $top10->top_search.': <a href="'.$top10->referrer.'" target="_BLANK">'.stringShortener(preg_replace('/'.preg_quote($siteurl,'/').'/i', '', $top10->search),$char_len).'</a>'; ?></li>
		<?php } ?>
		</ul>
		</td>
		<?php
		} // end if topsearch

		//#output top 10 referrers
		if ($top_ten['topreferrer'] == 1) {
			//domains to exclude from top ten referrers:
			//exclude siteurl, wpurl, and user-specified domains
			$exclude_list = $sitedomain;
			$wpdomain = parse_url($wpurl);
			$wpdomain = preg_replace('/^www\./i','',$wpdomain['host']);
			if ($wpdomain != $sitedomain) {
				$exclude_list .= ",".$wpdomain;
			}
			if (!empty($top_ten['topreferrer_exclude'])) {
				$exclude_list .= ",".$top_ten['topreferrer_exclude'];
			}
			//create mysql query to exclude referrer domains 
			$exclude_referrers = "";
			$exclude_array = array_unique(explode(",", $exclude_list));
			foreach ($exclude_array as $exclude_domain) {
				$exclude_domain = trim($exclude_domain);
				if ($exclude_domain != "" ) {
					$exclude_referrers .= " AND referrer NOT LIKE '%".$exclude_domain."%'";
				}
			}
			//$top_results = $wpdb->get_results("SELECT count(referrer) as top_referrer, referrer FROM $table_name WHERE timestamp BETWEEN $from_date AND $to_date AND referrer!='' AND referrer NOT LIKE '%".$sitedomain."%' AND searchengine='' $spamselect GROUP BY referrer ORDER BY top_referrer DESC LIMIT 200");
			$top_results = $wpdb->get_results("SELECT count(referrer) as top_referrer, referrer FROM $table_name WHERE timestamp BETWEEN $from_date AND $to_date AND referrer!='' $exclude_referrers AND searchengine='' $spamselect GROUP BY referrer ORDER BY top_referrer DESC LIMIT 10");
			$char_len = round(($max_char_len*.22)+$widthoffset,0);
		?>
		<td style="min-width:<?php echo ($char_len-5); ?>px;">
		<ul class="charts">
                        <li class="chartsT"><?php _e("TOP REFERRER", "wassup"); ?></li>
		<?php
		foreach ($top_results as $top10) { ?>
			<li class="charts"><?php echo $top10->top_referrer.': ';
			print '<a href="'.$top10->referrer.'" title="'.$top10->referrer.'" target="_BLANK">';
			//#cut http:// from displayed url, then truncate
			//#   instead of using stringShortener...
			print substr(str_replace("http://", "", attribute_escape($top10->referrer)),0,$char_len);
			if (strlen($top10->referrer) > ($char_len + 7)) { 
			   	print '...';
			}
			print '</a>'; ?></li>
		<?php } ?>
                </ul>
                </td>
		<?php
		} //end if topreferrer

		//#output top 10 url requests
		if ($top_ten['toprequest'] == 1) {
			$top_results = $wpdb->get_results("SELECT count(urlrequested) as top_urlrequested, urlrequested FROM $table_name WHERE timestamp BETWEEN $from_date AND $to_date AND urlrequested!='' $spamselect GROUP BY REPLACE(urlrequested, '/', '') ORDER BY top_urlrequested DESC LIMIT 10");
			$char_len = round(($max_char_len*.28)+$widthoffset,0);
		 ?>
		<td style="min-width:<?php echo ($char_len-5); ?>px;">
		<ul class="charts">
			<li class="chartsT"><?php _e("TOP REQUEST", "wassup"); ?></li>
		<?php
		foreach ($top_results as $top10) { ?>
			<li class="charts"><?php echo $top10->top_urlrequested.': ';
			print '<a href="'.wAddSiteurl(htmlspecialchars(html_entity_decode($top10->urlrequested))).'" title="'.html_entity_decode($top10->urlrequested).'" target="_BLANK">';
			print stringShortener(urlencode(html_entity_decode($top10->urlrequested)),$char_len).'</a>'; ?></li>
		<?php } ?>
		</ul>
		</td>
		<?php 
		} //end if toprequest

		//#get top 10 browsers...
		if ($top_ten['topbrowser'] == 1) {
			$top_results = $wpdb->get_results("SELECT count(browser) as top_browser, browser FROM $table_name WHERE timestamp BETWEEN $from_date AND $to_date AND browser!='' AND browser NOT LIKE 'N/A%' $spamselect GROUP BY browser ORDER BY top_browser DESC LIMIT 10");
			$char_len = round(($max_char_len*.17)+$widthoffset,0);
		?>
		<td style="min-width:<?php echo ($char_len-5); ?>px;">
		<ul class="charts">
			<li class="chartsT"><?php _e("TOP BROWSER", "wassup") ?></li>
		<?php
		foreach ($top_results as $top10) { ?>
			<li class="charts"><?php echo $top10->top_browser.': ';
			echo '<span class="top10" title="'.$top10->browser.'">'.stringShortener($top10->browser, $char_len).'</span>'; ?>
			</li>
		<?php } ?>
		</ul>
		</td>
		<?php }  //end if topbrowser

		//#output top 10 operating systems...
		if ($top_ten['topos'] == 1) { 
			$top_results = $wpdb->get_results("SELECT count(os) as top_os, os FROM $table_name WHERE timestamp BETWEEN $from_date AND $to_date AND os!='' AND os NOT LIKE '%N/A%' $spamselect GROUP BY os ORDER BY top_os DESC LIMIT 10");
			$char_len = round(($max_char_len*.15)+$widthoffset,0);
		
		 ?>
		<td style="min-width:<?php echo ($char_len-5); ?>px;">
		<ul class="charts">
			<li class="chartsT"><?php _e("TOP OS", "wassup") ?></li>
		<?php 
		foreach ($top_results as $top10) { ?>
			<li class="charts"><?php print $top10->top_os.': '; ?>
				<span class="top10" title="<?php echo $top10->os; ?>"><?php echo stringShortener($top10->os, $char_len); ?></span>
			</li>
		<?php } ?>
		</ul>
		</td>
		<?php } // end if topos
		
		//#output top 10 locales/geographic regions...
		if ($top_ten['toplocale'] == 1) {
			$top_results = $wpdb->get_results("SELECT count(LOWER(language)) as top_locale, LOWER(language) as locale FROM $table_name WHERE timestamp BETWEEN $from_date AND $to_date AND language!='' $spamselect GROUP BY locale ORDER BY top_locale DESC LIMIT 10");
			$char_len = round(($max_char_len*.15)+$widthoffset,0);
		
		 ?>
		<td style="min-width:<?php echo ($char_len-5); ?>px;">
		<ul class="charts">
			<li class="chartsT"><?php _e("TOP LOCALE", "wassup"); ?></li>
		<?php 
		foreach ($top_results as $top10) { ?>
			<li class="charts"><?php echo $top10->top_locale.': ';
			echo '<img src="'.$wpurl.'/wp-content/plugins/'.WASSUPFOLDER.'/img/flags/'.strtolower($top10->locale).'.png" alt="" />'; ?>
			<span class="top10" title="<?php echo $top10->locale; ?>"><?php echo $top10->locale; ?></span>
			</li>
		<?php } ?>
		</ul>
		</td>
		<?php } // end if toplocale
		
		//#output top 10 visitors
		if ($top_ten['topvisitor'] == 1) {
			$result = false;
			$char_len = round(($max_char_len*.17)+$widthoffset,0);
			$tmptable = "top_visitor".rand(0,999);
			if (mysql_query ("CREATE TEMPORARY TABLE {$tmptable} SELECT username as visitor, '1loggedin_user' as visitor_type, `timestamp` as visit_timestamp FROM $table_name WHERE `timestamp` BETWEEN $from_date AND $to_date AND username!='' $spamselect UNION SELECT comment_author as visitor, '2comment_author' as visitor_type, `timestamp` as visit_timestamp FROM wp_wassup WHERE `timestamp` BETWEEN $from_date AND $to_date AND username='' AND comment_author!='' $spamselect UNION SELECT hostname as visitor, '3hostname' as visitor_type, `timestamp` as visit_timestamp FROM wp_wassup WHERE `timestamp` BETWEEN $from_date AND $to_date AND username='' AND comment_author='' AND spider=''")) {
    				$numRows = mysql_affected_rows();
    				if ($numRows > 0) {
					$result = mysql_query ("SELECT count(visitor) as top_visitor, visitor, visitor_type FROM {$tmptable} WHERE visitor!='' GROUP BY visitor ORDER BY 1 DESC, visitor_type, visitor LIMIT 10");
				}
			} //end if mysql_query
		 ?>
		<td style="min-width:<?php echo ($char_len-5); ?>px;">
		<ul class="charts">
			<li class="chartsT"><?php _e("TOP VISITOR", "wassup"); ?></li>
		<?php 
		if ($result) { 
		while ($top10 = mysql_fetch_array($result,MYSQL_ASSOC)) { ?>
			<li class="charts"><?php echo $top10['top_visitor'].': '; ?>
			<span class="top10" title="<?php echo $top10['visitor']; ?>"><?php echo stringShortener($top10['visitor'], $char_len); ?></span>
			</li>
		<?php }
		mysql_free_result($result);
		} //end if result
		mysql_query("DROP TABLE IF EXISTS {$tmptable}"); ?>
		</ul>
		</td>
		<?php } // end if topvisitor
		?>
		</tr>
		</table>
		<?php if ($wassup_settings['wassup_spamcheck'] == 1) { ?>
		<span style="font-size:6pt;">* <?php _e("This top ten doesn't include Spam records","wassup"); ?></span>
		<?php } ?>
	</div>
	<?php 
	} else {
		echo '<span style="color:red;">Action.php '.__("ERROR: Missing or unknown parameters","wassup").', action='.attribute_escape($_GET["action"]).'</span>';
	} ?>
</body></html>	
	<?php 
	} //end else action=DELETE
} else {
	echo '<span style="color:red;">Action.php '.__("ERROR: Nothing to do here","wassup").'</span>';
} //end if !$hashfail
?>
