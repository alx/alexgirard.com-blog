<?php
if (!class_exists('pagination')) { 	//in case another app uses this class...
class pagination{
/*
Script Name: *Digg Style Paginator Class
Script URI: http://www.mis-algoritmos.com/2007/05/27/digg-style-pagination-class/
Description: Class in PHP that allows to use a pagination like a digg or sabrosus style.
Script Version: 0.3.2
Author: Victor De la Rocha
Author URI: http://www.mis-algoritmos.com
*/
	/*Default values*/
        var $total_pages;
        var $limit;
        var $target;
        var $page;
        var $adjacents;
        var $showCounter;
        var $className;
        var $parameterName;
        var $urlF ;

        /*Buttons next and previous*/
        var $nextT;
        var $nextI;
        var $prevT;
        var $prevI;

        /*****/
        var $calculate;
	
	#Total items
	function items($value){$this->total_pages = intval($value);}
	
	#how many items to show per page
	function limit($value){$this->limit = intval($value);}
	
	#Page to sent the page value
	function target($value){$this->target = $value;}
	
	#Current page
	function currentPage($value){$this->page = intval($value);}
	
	#How many adjacent pages should be shown on each side of the current page?
	function adjacents($value){$this->adjacents = intval($value);}
	
	#show counter?
	function showCounter($value=""){$this->showCounter=($value===true)?true:false;}

	#to change the class name of the pagination div
	function changeClass($value=""){$this->className=$value;}

	function nextLabel($value){$this->nextT = $value;}
	function nextIcon($value){$this->nextI = $value;}
	function prevLabel($value){$this->prevT = $value;}
	function prevIcon($value){$this->prevI = $value;}

	#to change the class name of the pagination div
	function parameterName($value=""){$this->parameterName=$value;}

	#to change urlFriendly
	function urlFriendly($value="%"){
			if(eregi('^ *$',$value)){
					$this->urlF=false;
					return false;
				}
			$this->urlF=$value;
		}
	
	var $pagination;

	function pagination(){
                /*Set Default values*/
                $this->total_pages = null;
                $this->limit = null;
                $this->target = "";
                $this->page = 1;
                $this->adjacents = 2;
                $this->showCounter = false;
                $this->className = "pagination";
                $this->parameterName = "pages";
                $this->urlF = false;//urlFriendly

                /*Buttons next and previous*/
                $this->nextT = __("Next","wassup");
                $this->nextI = "&#187;"; //&#9658;
                $this->prevT = __("Previous","wassup");
                $this->prevI = "&#171;"; //&#9668;

                $this->calculate = false;
	}
	function show(){
			if(!$this->calculate)
				if($this->calculate())
					echo "<div class=\"$this->className\">$this->pagination</div>";
		}
	function get_pagenum_link($id){
			if(strpos($this->target,'?')===false)
					if($this->urlF)
							return str_replace($this->urlF,$id,$this->target);
						else
							return "$this->target?$this->parameterName=$id";
				else
					return "$this->target&$this->parameterName=$id";
		}
	
	function calculate(){
			$this->pagination = "";
			$this->calculate == true;
			$error = false;
			if($this->urlF and $this->urlF != '%' and strpos($this->target,$this->urlF)===false){
					//Es necesario especificar el comodin para sustituir
					echo 'Especificaste un wildcard para sustituir, pero no existe en el target<br />';
                                        $error = true;
                                }elseif($this->urlF and $this->urlF == '%' and strpos($this->target,$this->urlF)===false){
                                        echo 'Es necesario especificar en el target el comodin';
                                        $error = true;
                                }
                        if($this->total_pages == null){
                                        echo __("It is necessary to specify the","wassup")." <strong>".__("number of pages","wassup")."</strong> (\$class->items(1000))<br />";
                                        $error = true;
                                }
                        if($this->limit == null){
                                        echo __("It is necessary to specify the","wassup")." <strong>".__("limit of items","wassup")."</strong> ".__("to show per page","wassup")." (\$class->limit(10))<br />";
                                        $error = true;
				}
			if($error)return false;
			
			$n = trim($this->nextT.' '.$this->nextI);
			$p = trim($this->prevI.' '.$this->prevT);
			
			/* Setup vars for query. */
			if($this->page) 
				$start = ($this->page - 1) * $this->limit;             //first item to display on this page
			else
				$start = 0;                                //if no page var is given, set start to 0
		
			/* Setup page vars for display. */
			if ($this->page == 0) $this->page = 1;                    //if no page var is given, default to 1.
			$prev = $this->page - 1;                            //previous page is page - 1
			$next = $this->page + 1;                            //next page is page + 1
			$lastpage = ceil($this->total_pages/$this->limit);        //lastpage is = total pages / items per page, rounded up.
			$lpm1 = $lastpage - 1;                        //last page minus 1
			
			/* 
				Now we apply our rules and draw the pagination object. 
				We're actually saving the code to a variable in case we want to draw it more than once.
			*/
			
			if($lastpage > 1){
					//anterior button
					if($this->page > 1)
							$this->pagination .= "<a href=\"".$this->get_pagenum_link($prev)."\">$p</a>";
						else
							$this->pagination .= "<span class=\"disabled\">$p</span>";
					//pages	
					if ($lastpage < 7 + ($this->adjacents * 2)){//not enough pages to bother breaking it up
							for ($counter = 1; $counter <= $lastpage; $counter++){
									if ($counter == $this->page)
											$this->pagination .= "<span class=\"current\">$counter</span>";
										else
											$this->pagination .= "<a href=\"".$this->get_pagenum_link($counter)."\">$counter</a>";
								}
						}
					elseif($lastpage > 5 + ($this->adjacents * 2)){//enough pages to hide some
							//close to beginning; only hide later pages
							if($this->page < 1 + ($this->adjacents * 2)){
									for ($counter = 1; $counter < 4 + ($this->adjacents * 2); $counter++){
											if ($counter == $this->page)
													$this->pagination .= "<span class=\"current\">$counter</span>";
												else
													$this->pagination .= "<a href=\"".$this->get_pagenum_link($counter)."\">$counter</a>";
										}
									$this->pagination .= "...";
									$this->pagination .= "<a href=\"".$this->get_pagenum_link($lpm1)."\">$lpm1</a>";
									$this->pagination .= "<a href=\"".$this->get_pagenum_link($lastpage)."\">$lastpage</a>";
								}
							//in middle; hide some front and some back
							elseif($lastpage - ($this->adjacents * 2) > $this->page && $this->page > ($this->adjacents * 2)){
									$this->pagination .= "<a href=\"".$this->get_pagenum_link(1)."\">1</a>";
									$this->pagination .= "<a href=\"".$this->get_pagenum_link(2)."\">2</a>";
									$this->pagination .= "...";
									for ($counter = $this->page - $this->adjacents; $counter <= $this->page + $this->adjacents; $counter++)
										if ($counter == $this->page)
												$this->pagination .= "<span class=\"current\">$counter</span>";
											else
												$this->pagination .= "<a href=\"".$this->get_pagenum_link($counter)."\">$counter</a>";
									$this->pagination .= "...";
									$this->pagination .= "<a href=\"".$this->get_pagenum_link($lpm1)."\">$lpm1</a>";
									$this->pagination .= "<a href=\"".$this->get_pagenum_link($lastpage)."\">$lastpage</a>";
								}
							//close to end; only hide early pages
							else{
									$this->pagination .= "<a href=\"".$this->get_pagenum_link(1)."\">1</a>";
									$this->pagination .= "<a href=\"".$this->get_pagenum_link(2)."\">2</a>";
									$this->pagination .= "...";
									for ($counter = $lastpage - (2 + ($this->adjacents * 2)); $counter <= $lastpage; $counter++)
										if ($counter == $this->page)
												$this->pagination .= "<span class=\"current\">$counter</span>";
											else
												$this->pagination .= "<a href=\"".$this->get_pagenum_link($counter)."\">$counter</a>";
								}
						}
					//siguiente button
					if ($this->page < $counter - 1)
							$this->pagination .= "<a href=\"".$this->get_pagenum_link($next)."\">$n</a>";
						else
							$this->pagination .= "<span class=\"disabled\">$n</span>";
						if($this->showCounter)$this->pagination .= "<div class=\"pagination_data\">($this->total_pages ".__("Pages","wassup").")</div>";
				}

			return true;
		}
	} //end class pagination
} //end if !class_exists('pagination')

if (!class_exists('Detector')) { 	//in case another app uses this class...
//
// Detector class (c) Mohammad Hafiz bin Ismail 2006
// detect location by ipaddress
// detect browser type and operating system
//
// November 27, 2006
//
// by : Mohammad Hafiz bin Ismail (info@mypapit.net)
// 
// You are allowed to use this work under the terms of 
// Creative Commons Attribution-Share Alike 3.0 License
// 
// Reference : http://creativecommons.org/licenses/by-sa/3.0/
// 

class Detector {

	var $town;
	var $state;
	var $country;
	var $Ctimeformatode;
	var $longitude;
	var $latitude;
	var $ipaddress;
	var $txt;

	var $browser;
	var $browser_version;
	var $os_version;
	var $os;
	var $useragent;

	function Detector($ip="", $ua="")
	{	
		$apiserver="http://showip.fakap.net/txt/";
		if ($ip != "") {	
		if (preg_match('/\b(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b/',$ip,$matches))
		  {
		    $this->ipaddress=$ip;
		  }

		else { $this->ipaddress = "0.0.0.0"; }

		//uncomment this below if CURL doesnt work		

		$this->txt=file_get_contents($apiserver . "$ip");

		$wtf=$this->txt;
		$this->processTxt($wtf);
		}

		$this->useragent=$ua;
		$this->check_os($ua);
		$this->check_browser($ua);
	}

	function processTxt($wtf)

	{
//	  	$tok = strtok($txt, ',');
	  	$this->town = strtok($wtf,',');
	  	$this->state = strtok(',');
	  	$this->country=strtok(',');
	  	$this->ccode = strtok(',');
	  	$this->latitude=strtok(',');
	  	$this->longitude=strtok(',');
	}

	function check_os($useragent) {

			$os = "N/A"; $version = "";

			if (preg_match("/Windows NT 5.1/",$useragent,$match)) {
				$os = "WinXP"; $version = "";
			} elseif (preg_match("/Windows NT 5.2/",$useragent,$match)) {
				$os = "Win2003"; $version = "";
			} elseif (preg_match("/Windows NT 6.0/",$useragent,$match)) {
				$os = "WinVista"; $version = "";
			} elseif (preg_match("/(?:Windows NT 5.0|Windows 2000)/",$useragent,$match)) {
				$os = "Win2000"; $version = "";
			} elseif (preg_match("/Windows ME/",$useragent,$match)) {
				$os = "WinME"; $version = "";
			} elseif (preg_match("/(?:WinNT|Windows\s?NT)\s?([0-9\.]+)?/",$useragent,$match)) {
				$os = "WinNT"; $version = $match[1];
			} elseif (preg_match("/Mac OS X/",$useragent,$match)) {
				$os = "MacOSX"; $version = "";
			} elseif (preg_match("/(Mac_PowerPC|Macintosh)/",$useragent,$match)) {
				$os = "MacPPC"; $version = "";
			} elseif (preg_match("/(?:Windows95|Windows 95|Win95|Win 95)/",$useragent,$match)) {
				$os = "Win95"; $version = "";
			} elseif (preg_match("/(?:Windows98|Windows 98|Win98|Win 98|Win 9x)/",$useragent,$match)) {
				$os = "Win98"; $version = "";
			} elseif (preg_match("/(?:WindowsCE|Windows CE|WinCE|Win CE)/",$useragent,$match)) {
				$os = "WinCE"; $version = "";
			} elseif (preg_match("/PalmOS/",$useragent,$match)) {
				$os = "PalmOS";
			} elseif (preg_match("/\(PDA(?:.*)\)(.*)Zaurus/",$useragent,$match)) {
				$os = "Sharp Zaurus";
			} elseif (preg_match("/Linux\s*((?:i[0-9]{3})?\s*(?:[0-9]\.[0-9]{1,2}\.[0-9]{1,2})?\s*(?:i[0-9]{3})?)?/",$useragent,$match)) {
				$os = "Linux"; $version = $match[1];
			} elseif (preg_match("/NetBSD\s*((?:i[0-9]{3})?\s*(?:[0-9]\.[0-9]{1,2}\.[0-9]{1,2})?\s*(?:i[0-9]{3})?)?/",$useragent,$match)) {
				$os = "NetBSD"; $version = $match[1];
			} elseif (preg_match("/OpenBSD\s*([0-9\.]+)?/",$useragent,$match)) {
				$os = "OpenBSD"; $version = $match[1];
			} elseif (preg_match("/CYGWIN\s*((?:i[0-9]{3})?\s*(?:[0-9]\.[0-9]{1,2}\.[0-9]{1,2})?\s*(?:i[0-9]{3})?)?/",$useragent,$match)) {
				$os = "CYGWIN"; $version = $match[1];
			} elseif (preg_match("/SunOS\s*([0-9\.]+)?/",$useragent,$match)) {
				$os = "SunOS"; $version = $match[1];
			} elseif (preg_match("/IRIX\s*([0-9\.]+)?/",$useragent,$match)) {
				$os = "SGI IRIX"; $version = $match[1];
			} elseif (preg_match("/FreeBSD\s*((?:i[0-9]{3})?\s*(?:[0-9]\.[0-9]{1,2})?\s*(?:i[0-9]{3})?)?/",$useragent,$match)) {
				$os = "FreeBSD"; $version = $match[1];
			} elseif (preg_match("/SymbianOS\/([0-9.]+)/i",$useragent,$match)) {
				$os = "SymbianOS"; $version = $match[1];
			} elseif (preg_match("/Symbian\/([0-9.]+)/i",$useragent,$match)) {
				$os = "Symbian"; $version = $match[1];
			} elseif (preg_match("/PLAYSTATION 3/",$useragent,$match)) {
				$os = "Playstation"; $version = 3;
			}

			$this->os = $os;
			$this->os_version = $version;
		}

		function check_browser($useragent) {

			$browser = "";

			if (preg_match("/^Mozilla(?:.*)compatible;\sMSIE\s(?:.*)Opera\s([0-9\.]+)/",$useragent,$match)) {
				$browser = "Opera";
			} elseif (preg_match("/^Opera\/([0-9\.]+)/",$useragent,$match)) {
				$browser = "Opera";
			} elseif (preg_match("/^Mozilla(?:.*)compatible;\siCab\s([0-9\.]+)/",$useragent,$match)) {
				$browser = "iCab";
			} elseif (preg_match("/^iCab\/([0-9\.]+)/",$useragent,$match)) {
				$browser = "iCab";
			} elseif (preg_match("/^Mozilla(?:.*)compatible;\sMSIE\s([0-9\.]+)/",$useragent,$match)) {
				$browser = "IE";
			} elseif (preg_match("/^(?:.*)compatible;\sMSIE\s([0-9\.]+)/",$useragent,$match)) {
				$browser = "IE";
			} elseif (preg_match("/^Mozilla(?:.*)(?:.*)Safari/",$useragent,$match)) {
				$browser = "Safari";
			//} elseif (preg_match("/^Mozilla(?:.*)\(Windows(?:.*)Safari\/([0-9\.]+)/",$useragent,$match)) {
			//	$browser = "Safari";
			} elseif (preg_match("/^Mozilla(?:.*)\(Macintosh(?:.*)OmniWeb\/v([0-9\.]+)/",$useragent,$match)) {
				$browser = "Omniweb";
			} elseif (preg_match("/^Mozilla(?:.*)\(compatible; Google Desktop/",$useragent,$match)) {
				$browser = "Google Desktop";
			} elseif (preg_match("/^Mozilla(?:.*)\(compatible;\sOmniWeb\/([0-9\.v-]+)/",$useragent,$match)) {
				$browser = "Omniweb";
			} elseif (preg_match("/^Mozilla(?:.*)Gecko(?:.*?)(?:Camino|Chimera)\/([0-9\.]+)/",$useragent,$match)) {
				$browser = "Camino";
			} elseif (preg_match("/^Mozilla(?:.*)Gecko(?:.*?)Netscape\/([0-9\.]+)/",$useragent,$match)) {
				$browser = "Netscape";
			} elseif (preg_match("/^Mozilla(?:.*)Gecko(?:.*?)(?:Fire(?:fox|bird)|Phoenix)\/([0-9\.]+)/",$useragent,$match)) {
				$browser = "Firefox";
			} elseif (preg_match("/^Mozilla(?:.*)Gecko(?:.*?)Minefield\/([0-9\.]+)/",$useragent,$match)) {
				$browser = "Minefield";
			} elseif (preg_match("/^Mozilla(?:.*)Gecko(?:.*?)Epiphany\/([0-9\.]+)/",$useragent,$match)) {
				$browser = "Epiphany";
			} elseif (preg_match("/^Mozilla(?:.*)Galeon\/([0-9\.]+)\s(?:.*)Gecko/",$useragent,$match)) {
				$browser = "Galeon";
			} elseif (preg_match("/^Mozilla(?:.*)Gecko(?:.*?)K-Meleon\/([0-9\.]+)/",$useragent,$match)) {
				$browser = "K-Meleon";
			} elseif (preg_match("/^Mozilla(?:.*)rv:([0-9\.]+)\)\sGecko/",$useragent,$match)) {
				$browser = "Mozilla";
			} elseif (preg_match("/^Mozilla(?:.*)compatible;\sKonqueror\/([0-9\.]+);/",$useragent,$match)) {
				$browser = "Konqueror";
			} elseif (preg_match("/^Mozilla\/(?:[34]\.[0-9]+)(?:.*)AvantGo\s([0-9\.]+)/",$useragent,$match)) {
				$browser = "AvantGo";
			} elseif (preg_match("/^Mozilla(?:.*)NetFront\/([34]\.[0-9]+)/",$useragent,$match)) {
				$browser = "NetFront";
			} elseif (preg_match("/^Mozilla\/([34]\.[0-9]+)/",$useragent,$match)) {
				$browser = "Netscape";
			} elseif (preg_match("/^Liferea\/([0-9\.]+)/",$useragent,$match)) {
				$browser = "Liferea";
			} elseif (preg_match("/^curl\/([0-9\.]+)/",$useragent,$match)) {
				$browser = "curl";
			} elseif (preg_match("/^links\/([0-9\.]+)/i",$useragent,$match)) {
				$browser = "Links";
			} elseif (preg_match("/^links\s?\(([0-9\.]+)/i",$useragent,$match)) {
				$browser = "Links";
			} elseif (preg_match("/^lynx\/([0-9a-z\.]+)/i",$useragent,$match)) {
				$browser = "Lynx";
			} elseif (preg_match("/^Wget\/([0-9\.]+)/i",$useragent,$match)) {
				$browser = "Wget";
			} elseif (preg_match("/^Xiino\/([0-9\.]+)/i",$useragent,$match)) {
				$browser = "Xiino";
			} elseif (preg_match("/^W3C_Validator\/([0-9\.]+)/i",$useragent,$match)) {
				$browser = "W3C Validator";
			} elseif (preg_match("/^Jigsaw(?:.*) W3C_CSS_Validator_(?:[A-Z]+)\/([0-9\.]+)/i",$useragent,$match)) {
				$browser = "W3C CSS Validator";
			} elseif (preg_match("/^Dillo\/([0-9\.]+)/i",$useragent,$match)) {
				$browser = "Dillo";
			} elseif (preg_match("/^amaya\/([0-9\.]+)/i",$useragent,$match)) {
				$browser = "Amaya";
			} elseif (preg_match("/^DocZilla\/([0-9\.]+)/i",$useragent,$match)) {
				$browser = "DocZilla";
			} elseif (preg_match("/^fetch\slibfetch\/([0-9\.]+)/i",$useragent,$match)) {
				$browser = "FreeBSD libfetch";
			} elseif (preg_match("/^Nokia([0-9a-zA-Z\-.]+)\/([0-9\.]+)/i",$useragent,$match)) {
				$browser="Nokia";
			} elseif (preg_match("/^SonyEricsson([0-9a-zA-Z\-.]+)\/([a-zA-Z0-9\.]+)/i",$useragent,$match)) {
				$browser="SonyEricsson";
			}

			//$version = $match[1];
			//restrict version to major and minor version #'s
			preg_match("/^\d+(\.\d+)?/",$match[1],$majorvers);
			$version = $majorvers[0];

			$this->browser = $browser;
			$this->browser_version = $version;
	}
} //end class Detector
} //end if !class_exists('Detector')

function wassup_get_time() {
	$timeright = gmdate("U");
	$offset = (get_option("gmt_offset")*60*60);
	$timeright = ($timeright + $offset) ;
	return $timeright;
}

/*
# PHP Calendar (version 2.3), written by Keith Devens
# http://keithdevens.com/software/php_calendar
#  see example at http://keithdevens.com/weblog
# License: http://keithdevens.com/software/license
*/
//
// Currently not used in WassUp it's a next implementation idea
//
function generate_calendar($year, $month, $days = array(), $day_name_length = 3, $month_href = NULL, $first_day = 0, $pn = array()){
	$first_of_month = gmmktime(0,0,0,$month,1,$year);
	#remember that mktime will automatically correct if invalid dates are entered
	# for instance, mktime(0,0,0,12,32,1997) will be the date for Jan 1, 1998
	# this provides a built in "rounding" feature to generate_calendar()

	$day_names = array(); #generate all the day names according to the current locale
	for($n=0,$t=(3+$first_day)*86400; $n<7; $n++,$t+=86400) #January 4, 1970 was a Sunday
		$day_names[$n] = ucfirst(gmstrftime('%A',$t)); #%A means full textual day name

	list($month, $year, $month_name, $weekday) = explode(',',gmstrftime('%m,%Y,%B,%w',$first_of_month));
	$weekday = ($weekday + 7 - $first_day) % 7; #adjust for $first_day
	$title   = htmlentities(ucfirst($month_name)).'&nbsp;'.$year;  #note that some locales don't capitalize month and day names

	#Begin calendar. Uses a real <caption>. See http://diveintomark.org/archives/2002/07/03
	@list($p, $pl) = each($pn); @list($n, $nl) = each($pn); #previous and next links, if applicable
	if($p) $p = '<span class="calendar-prev">'.($pl ? '<a href="'.htmlspecialchars($pl).'">'.$p.'</a>' : $p).'</span>&nbsp;';
	if($n) $n = '&nbsp;<span class="calendar-next">'.($nl ? '<a href="'.htmlspecialchars($nl).'">'.$n.'</a>' : $n).'</span>';
	$calendar = '<table class="calendar">'."\n".
		'<caption class="calendar-month">'.$p.($month_href ? '<a href="'.htmlspecialchars($month_href).'">'.$title.'</a>' : $title).$n."</caption>\n<tr>";

	if($day_name_length){ #if the day names should be shown ($day_name_length > 0)
		#if day_name_length is >3, the full name of the day will be printed
		foreach($day_names as $d)
			$calendar .= '<th abbr="'.htmlentities($d).'">'.htmlentities($day_name_length < 4 ? substr($d,0,$day_name_length) : $d).'</th>';
		$calendar .= "</tr>\n<tr>";
	}

	if($weekday > 0) $calendar .= '<td colspan="'.$weekday.'">&nbsp;</td>'; #initial 'empty' days
	for($day=1,$days_in_month=gmdate('t',$first_of_month); $day<=$days_in_month; $day++,$weekday++){
		if($weekday == 7){
			$weekday   = 0; #start a new week
			$calendar .= "</tr>\n<tr>";
		}
		if(isset($days[$day]) and is_array($days[$day])){
			@list($link, $classes, $content) = $days[$day];
			if(is_null($content))  $content  = $day;
			$calendar .= '<td'.($classes ? ' class="'.htmlspecialchars($classes).'">' : '>').
				($link ? '<a href="'.htmlspecialchars($link).'">'.$content.'</a>' : $content).'</td>';
		}
		else $calendar .= "<td>$day</td>";
	}
	if($weekday != 7) $calendar .= '<td colspan="'.(7-$weekday).'">&nbsp;</td>'; #remaining "empty" days

	return $calendar."</tr>\n</table>\n";
}

//Truncate $input string to a length of $max
function stringShortener($input, $max=0, $separator="(...)", $exceedFromEnd=0){
	if(!$input || !is_string($input)){return false;};
	
	//Replace all %-hex chars with literals and trim the input string of whitespaces
	//   ...because it is shorter and more legible -Helene D. 11/18/07
	$input = trim(rawurldecode($input));

	$inputlen=strlen($input);
	$max=(is_numeric($max))?(integer)$max:$inputlen;
	if($max>=$inputlen){return $input;};
	$separator=($separator)?$separator:"(...)";
	$modulus=(($max%2));
	$halfMax=floor($max/2);
	$begin="";
	if(!$modulus){$begin=substr($input, 0, $halfMax);}
	else{$begin=(!$exceedFromEnd)? substr($input, 0, $halfMax+1) : substr($input, 0, $halfMax);}
	$end="";
	if(!$modulus){$end=substr($input,$inputlen-$halfMax);}
	else{$end=($exceedFromEnd)? substr($input,$inputlen-$halfMax-1) :substr($input,$inputlen-$halfMax);}
	$extracted=substr( $input, strpos($input,$begin)+strlen($begin), $inputlen-$max );
	$outstring = $begin.$separator.$end;
	if (strlen($outstring) >= $inputlen) {  //Because "Fir(...)fox" is longer than "Firefox"
		$outstring = $input;
	}
	//# added WP 2.x function attribute_escape to help make malicious 
	//#   code harmless when echoed to screen...
	if (function_exists('attribute_escape')) {
		return attribute_escape($outstring);
	} else {
		return addslashes($outstring);
	}
}

//# Return a value of true if url argument is a root url and false when
//#  url constains a subdirectory path or query parameters...
//#  - Helene D. 2007
function url_rootcheck($urltocheck) {
	$isroot = false;
	//url must begin with 'http://'
	if (strncasecmp($urltocheck,'http://',7) == 0) {
		$isroot = true;
		$urlparts=parse_url($urltocheck);
		if (!empty($urlparts['path']) && $urlparts['path'] != "/") {
			$isroot=false;
		} elseif (!empty($urlparts['query'])) {
			$isroot=false;
		}
	}
	return $isroot;
}

//#from a page/post url input, output a url with "$siteurl" prepended for 
//#  blogs that have wordpress installed in a separate folder
//#  -Helene D. 1/22/08
function wAddSiteurl($inputurl) {
	$wpurl = rtrim(get_bloginfo('wpurl'),"/");
	$siteurl = rtrim(get_bloginfo('siteurl'),"/");
	if (strcasecmp($siteurl, $wpurl) == 0) {
		$outputurl=$inputurl;
	} elseif (stristr($inputurl,$siteurl) === FALSE && url_rootcheck($siteurl))  {
		$outputurl=$siteurl."/".ltrim($inputurl,"/");
	} else {
		$outputurl=$inputurl;
	}
	return $outputurl;
}

//Output wassup records in Digg spy style...
function spyview ($from_date="",$to_date="",$rows="999") {
	global $wpdb;

	//check for arguments...
	if(empty($to_date)) $to_date = wassup_get_time();
	if (empty($from_date)) $from_date = ($to_date - 5);
	$table_tmp_name = $wpdb->prefix . "wassup_tmp";

	if (function_exists('get_option')) {
		$wassup_settings = get_option('wassup_settings');
	}
	if (!empty($wassup_settings['wassup_screen_res'])) {
		$screen_res_size = (int) $wassup_settings['wassup_screen_res'];
	} else { 
		$screen_res_size = 670;
	}
	$max_char_len = ($screen_res_size)/10;
	if (function_exists('get_bloginfo')) {
		$wpurl = get_bloginfo('wpurl');
		$siteurl = get_bloginfo('siteurl');
	}

	$qryC = $wpdb->get_results("SELECT id, wassup_id, max(timestamp) as max_timestamp, ip, hostname, searchengine, urlrequested, agent, referrer, spider, username, comment_author FROM $table_tmp_name WHERE timestamp BETWEEN $from_date AND $to_date GROUP BY id ORDER BY max_timestamp DESC");

	if (!empty($qryC)) {
		//restrict # of rows to display when needed...
		$row_count = 0;
		//display the rows...
		foreach ($qryC as $cv) {
		if ( $row_count < (int)$rows ) {
		   $timestamp = $cv->max_timestamp;
		   $ip = @explode(",", $cv->ip);
		   if ($cv->referrer != '') {
		   	if (!eregi($wpurl, $cv->referrer) OR $cv->searchengine != "") { 
		   	if (!eregi($wpurl, $cv->referrer) AND $cv->searchengine == "") {
		   		$referrer = '<a href="'.$cv->referrer.'" target=_"BLANK"><span style="font-weight: bold;">'.stringShortener($cv->referrer, round($max_char_len*.8,0)).'</span></a>';
		   	} else {
		   		$referrer = '<a href="'.$cv->referrer.'" target=_"BLANK">'.stringShortener($cv->referrer, round($max_char_len*.9,0)).'</a>';
		   	}
		   	} else { 
                        $referrer = __('From your blog','wassup');
                        }
                   } else {
                        $referrer = __('Direct hit','wassup');
		   } 
		   // User is logged in or is a comment's author
		   if ($cv->username != "" AND $cv->comment_author != "") {
		   	$unclass = "-log";
		   } elseif ($cv->comment_author != "") {
		   	$unclass = "-aut";
		   } elseif ($cv->spider != "") {
		   	$unclass = "-spider";
		   } 
		   // Start getting GEOIP info
		   /*
		   // TODO
		   $ch = curl_init("http://api.hostip.info/get_html.php?ip=".$ip[0]."&position=true");
		   curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		   curl_setopt($ch, CURLOPT_HEADER, 0);
		   $data = curl_exec($ch);
		   curl_close($ch);
		   */
		   ?>
		   <div class="sum-spy">

		   <span class="sum-box<?php print $unclass; ?>">
		   	<?php print $ip[0]; ?></span>
		   <div class="sum-det"><span class="det1">
		   <?php
		   	print '<a href="'.wAddSiteurl(htmlspecialchars(html_entity_decode($cv->urlrequested))).'" target="_BLANK">';
		   	print stringShortener(html_entity_decode($cv->urlrequested), round($max_char_len*.9,0)); ?>
		   </a></span><br />
		   <span class="det2"><strong><?php print gmdate("H:i:s", $timestamp); ?> - </strong>
		   <?php print $referrer; ?></span>
		   </div></div>
<?php		} //end if row_count
		$row_count=$row_count+1;
		} //end foreach
	} else {
		//display "no activity" periodically so we know spy is running...
		if ((int)$to_date%7 == 0 ) {
			echo '<div class="sum-spy"><span class="det3">'.gmdate("H:i:s",$to_date).' - '.__("No visitor activity","wassup").' &nbsp; &nbsp; :-( &nbsp; </span></div>';
		}
	} //end if !empty($qryC)
} //end function spyview

// How many digits have an integer
function digit_count($n, $base=10) {

  if($n == 0) return 1;

  if($base == 10) {
    # using the built-in log10(x)
    # might be more accurate than log(x)/log(10).
    return 1 + floor(log10(abs($n)));
  }else{
    # here  logB(x) = log(x)/log(B) will have to do.
   return 1 + floor(log(abs($n))/ log($base));
  }
}

//Round the integer to the next near 10
function roundup($value) {
	$dg = digit_count($value);
	if ($dg <= 2) {
		$dg = 1;
	} else {
		$dg = ($dg-2);
	}
	return (ceil(intval($value)/pow(10, $dg))*pow(10, $dg)+pow(10, $dg));
}

function chart_data($Wvisits, $pages=null, $atime=null, $type, $charttype=null, $axes=null, $chart_type=null) {
// Port of JavaScript from http://code.google.com/apis/chart/
// http://james.cridland.net/code
   // First, find the maximum value from the values given
   if ($axes == 1) {
	$maxValue = roundup(max(array_merge($Wvisits, $pages)));
	//$maxValue = roundup(max($Wvisits));
	$halfValue = ($maxValue/2); 
	$maxPage = $maxValue;
   } else {
	$maxValue = roundup(max($Wvisits));
	$halfValue = ($maxValue/2);
	$maxPage = roundup(max($pages));
	$halfPage = ($maxPage/2);
   }

   // A list of encoding characters to help later, as per Google's example
   $simpleEncoding = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

   $chartData = "s:";

	// Chart type has two datasets
	if ($charttype == "main") {
		$label_time = "";
		for ($i = 0; $i < count($Wvisits); $i++) {
			$currentValue = $Wvisits[$i];
			$currentTime = $atime[$i];
			if ($chart_type == "dashboard") {
				$label_time="|";
			} else {
				$label_time.=ereg_replace(" ", "+", $currentTime)."|";
			}
     
			if ($currentValue > -1) {
				$chartData.=substr($simpleEncoding,61*($currentValue/$maxValue),1);
			} else {
				$chartData.='_';
			}
		} 
		// Add pageviews line to the chart
		if (count($pages) != 0) {
			$chartData.=",";
			for ($i = 0; $i < count($pages); $i++) {
				$currentPage = $pages[$i];
				$currentTime = $atime[$i];
     
				if ($currentPage > -1) {
					$chartData.=substr($simpleEncoding,61*($currentPage/$maxPage),1);
				} else {
					$chartData.='_';
				}
			}
		}
		// Return the chart data - and let the Y axis to show the maximum value
   		if ($axes == 1) {
			return $chartData."&chxt=x,y&chxl=0:|".$label_time."1:|0|".$halfValue."|".$maxValue."&chxs=0,6b6b6b,9";
		} else {
			return $chartData."&chxt=x,y,r&chxl=0:|".$label_time."1:|0|".$halfValue."|".$maxValue."|2:|0|".$halfPage."|".$maxPage."&chxs=0,6b6b6b,9";
		}
	
	// Chart type has one one dataset
	// It's unused now
	} else {
		for ($i = 0; $i < count($Wvisits); $i++) {
			$currentValue = $Wvisits[$i];
			$currentTime = $atime[$i];
			$label_time.=ereg_replace(" ", "+", $currentTime)."|";

			if ($currentValue > -1) {
				$chartData.=substr($simpleEncoding,61*($currentValue/$maxValue),1);
			} else {
				$chartData.='_';
			}
		}
		return $chartData."&chxt=x,y&chxl=0:|".$label_time."|1:|0|".$halfValue."|".$maxValue."&chxs=0,6b6b6b,9";
	}

}

// Used to show main visitors details query, to count items and to extract data for main chart
class MainItems {
	// declare variables
        var $tableName;
        var $searchString;
        var $from_date;
        var $to_date;
        var $whereis;
        var $ItemsType;
        var $Limit;
        var $Last;
	var $WpUrl;

	// Function to show main query and count items
        function calc_tot($Type, $Search="", $specific_where_clause=null, $distinct_type=null) {
                global $wpdb;
                $this->ItemsType = $Type;
                $this->searchString = $Search;
		$ss = "";
		
		// Add the Search variable to the WHERE clause
		if ($Search != "") { $ss = " AND (ip LIKE '%".$this->searchString."%' OR hostname LIKE '%".$this->searchString."%' OR urlrequested LIKE '%".$this->searchString."%' OR agent LIKE '%".$this->searchString."%' OR referrer LIKE '%".$this->searchString."%') "; }

		// Switch by every (global) items type (visits, pageviews, spams, etc...)
                switch ($Type) {
                        // This is the MAIN query to show the chronology
                        case "main":
                                $qry = $wpdb->get_results("SELECT id, wassup_id, max(timestamp) as max_timestamp, ip, hostname, urlrequested, agent, referrer, search, searchpage,  os, browser, language, screen_res, searchengine, spider, feed, username, comment_author, spam FROM ".$this->tableName." WHERE wassup_id IS NOT NULL AND timestamp BETWEEN ".$this->from_date." AND ".$this->to_date." $ss ".$this->whereis." GROUP BY wassup_id ORDER BY max_timestamp DESC ".$this->Limit."");
                                return $qry;
                        break;
                        // These are the queries to count the items hits/pages/spam
                        case "count":
                                $itemstot = $wpdb->get_var("SELECT COUNT(".$distinct_type." wassup_id) AS itemstot FROM ".$this->tableName." WHERE wassup_id IS NOT NULL ".$specific_where_clause." AND timestamp BETWEEN ".$this->from_date." AND ".$this->to_date." $ss ".$this->whereis);
                                return $itemstot;
                        break;
                }
        }

	// $Ctype = chart's type by time
	// $Res = resolution
	// $Search = string to add to where clause
        function TheChart($Ctype, $Res, $chart_height, $Search="", $axes_type, $chart_bg, $chart_type=null, $chart_pos=null) {
		global $wpdb;
		$mysqlversion=substr(mysql_get_server_info(),0,3);
		$ss = "";

		//#Mysql's 'FROM_UNIXTIME' returns the local server 
		//#  datetime from an expected UTC unix timestamp, so 
		//#  convert 'timestamp' to UTC and calculate in any 
		//#  differences between the server TZ and Wordpress' time 
		//#  offset to get an accurate Wordpress datetime value.
		$WPoffset = (int)(get_option("gmt_offset")*60*60);
		$UTCoffset = $WPoffset + ((int)date('Z') - $WPoffset);
		//
		//#for US/Euro date display: USA Timezone=USA date format.
		if (in_array(date('T'), array("ADT","AST","AKDT","AKST","CDT","CST","EDT","EST","HADT","HAST","MDT","MST","PDT","PST"))) { 
			$USAdate = true;
		} else {
			$USAdate = false;
		}
		if (!isset($chart_pos)) $chart_pos = "center";
                $this->searchString = $Search;
                $this->Last = $Ctype;
		// Options by chart type
                switch ($Ctype) {
                        case 0:
                                $label = __("Last 6 Hours", "wassup");
                                $strto = "6 hours";
                                $Ctimeformat = "%H";
                                $x_axes_label = "%H:00";
                        break;
                        case 1:
                                $label = __("Last 24 Hours", "wassup");
                                $strto = "24 hours";
                                $Ctimeformat = "%H";
                                $x_axes_label = "%H:00";
                        break;
                        case 7:
                                $label = __("Last 7 Days", "wassup");
                                $strto = "7 days";
				$Ctimeformat = "%d";
				if ($USAdate) { 
					$x_axes_label = "%a %b %d";
				} else {
					$x_axes_label = "%a %d %b";
				}
                        break;
                        case 30:
                                $label = __("Last Month", "wassup");
                                $strto = "30 days";
                                $Ctimeformat = "%d";
				if ($USAdate) { 
					$x_axes_label = " %b %d";
				} else {
					$x_axes_label = "%d %b";
				}
                        break;
                        case 365:
                                $label = __("Last Year", "wassup");
                                $strto = "12 months";
                                $Ctimeformat = "%m";
                                $x_axes_label = "%b %Y";
                        break;
                }

		// Add Search variable to WHERE clause
                if ($Search != "") { $ss = " AND (ip LIKE '%".$this->searchString."%' OR hostname LIKE '%".$this->searchString."%' OR urlrequested LIKE '%".$this->searchString."%' OR agent LIKE '%".$this->searchString."%' OR referrer LIKE '%".$this->searchString."%') "; }

                $hour_todate = $this->to_date;
                $hour_fromdate = strtotime("-".$strto, $hour_todate);

		if ($hour_fromdate == "") $hour_fromdate = strtotime("-24 hours", $hour_todate);

		/* Debug
                $q = "SELECT COUNT(DISTINCT wassup_id) as items, COUNT(wassup_id) as pages, DATE_FORMAT(FROM_UNIXTIME((timestamp-$UTCoffset)), '$x_axes_label') as thedate FROM ".$this->tableName." WHERE wassup_id IS NOT NULL AND timestamp BETWEEN $hour_fromdate AND $hour_todate ".$this->whereis." $ss GROUP BY DATE_FORMAT(FROM_UNIXTIME((timestamp-$UTCoffset)), '$Ctimeformat') ORDER BY timestamp";
		echo $q;
		*/
                //$aitems = $wpdb->get_results("SELECT COUNT(DISTINCT wassup_id) as items, COUNT(wassup_id) as pages, DATE_FORMAT(FROM_UNIXTIME((timestamp-$UTCoffset)), '$x_axes_label') as thedate FROM ".$this->tableName." WHERE wassup_id IS NOT NULL AND timestamp BETWEEN $hour_fromdate AND $hour_todate ".$this->whereis." $ss GROUP BY DATE_FORMAT(FROM_UNIXTIME((timestamp-$UTCoffset)), '$Ctimeformat') ORDER BY timestamp", ARRAY_A);
                $aitems = $wpdb->get_results("SELECT COUNT(DISTINCT wassup_id) as items, COUNT(wassup_id) as pages, DATE_FORMAT(FROM_UNIXTIME(CAST((timestamp-$UTCoffset) AS UNSIGNED)), '$x_axes_label') as thedate FROM ".$this->tableName." WHERE timestamp BETWEEN $hour_fromdate AND $hour_todate ".$this->whereis." $ss GROUP BY DATE_FORMAT(FROM_UNIXTIME(CAST((timestamp-$UTCoffset) AS UNSIGNED)), '$Ctimeformat') ORDER BY timestamp", ARRAY_A);
		// Extract arrays for Visits, Pages and X_Axis_Label
		if (count($aitems) > 0) {
			foreach ($aitems as $bhits) {
                		$ahits[] = $bhits['items'];
	                	$apages[] = $bhits['pages'];
				$atime[] = $bhits['thedate'];
                	}
		// Print the main chart in visitors details view
		echo "<div id='placeholder' align='$chart_pos'>
			<img src='http://chart.apis.google.com/chart?chf=".$chart_bg."&chtt=".urlencode($label)."&chls=4,1,0|2,6,2&chco=2683ae,FF6D06&chm=B,2683ae30,0,0,0&chg=10,20,2,5&cht=lc&chs=".$Res."x".$chart_height."&chd=".chart_data($ahits, $apages, $atime, $Ctimeformat, "main", $axes_type, $chart_type)."'>\n";
		//echo "UTCoffset=$UTCoffset\nmysqlversion=$mysqlversion\n"; //debug
		//print_r($atime);	//debug
                echo "</div>\n";
		}
        }

}

// Class to check if a previous comment with a specific IP was detected as SPAM by Akismet default plugin
class CheckComment {
        var $tablePrefix;

	function isSpammer ($authorIP) {
		global $wpdb;
	        $spam_comment = $wpdb->get_var("SELECT COUNT(comment_ID) AS spam_comment FROM ".$this->tablePrefix."comments WHERE comment_author_IP='$authorIP' AND comment_approved='spam'");
		return $spam_comment;
	}
}
?>
