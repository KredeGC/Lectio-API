<?php
	$root = $_SERVER['DOCUMENT_ROOT'];
	
	include($root.'/lib/simple_html_dom.php');
	
	date_default_timezone_set('Europe/Copenhagen');
	
	function get_content($url) {
		$options = array(
			'http' => array(
				'method' => "GET",
				'header' => "Accept-language: en\r\nUser-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13\r\n"
			)
		);
		
		$context = stream_context_create($options);
		$html = file_get_contents($url, false, $context);
		
		if ($html == null) {
			return "404: Error appeared while retrieving html";
		}
		return $html;
		
		// Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13
	}
	
	function nice_string($text) {
		return trim(str_replace("&#39;", "'", $text));
	}
	
	
	function get_schools() {
		$schools = array();
		$lectio_html = get_content("https://www.lectio.dk/lectio/login_list.aspx"); // Elsker at de bruger en iframe, det gør det tusind gange nemmere
		$html = new simple_html_dom();
		$html->load($lectio_html);
		$anchors = $html->find('a');
		for ($i = 0; $i < count($anchors); $i++) {
			$href = $anchors[$i]->getAttribute("href");
			if (preg_match("/\/lectio\/(\d*)\/default\.aspx/", $href, $matches)) {
				$name = trim(html_entity_decode($anchors[$i]->plaintext));
				$schools[$name] = $matches[1];
			}
		}
		
		return $schools;
	}
	
	
	function get_classes($id) {
		$classes = array();
		$lectio_html = get_content("https://www.lectio.dk/lectio/".$id."/FindSkema.aspx?type=stamklasse");
		$html = new simple_html_dom();
		$html->load($lectio_html);
		$anchors = $html->find('a');
		for ($i = 0; $i < count($anchors); $i++) {
			$href = $anchors[$i]->getAttribute("href");
			if (preg_match("/\/lectio\/\d*\/SkemaNy\.aspx\?type=stamklasse&amp;klasseid=(\d*)/", $href, $matches)) { // Regex
				$name = trim(html_entity_decode($anchors[$i]->plaintext));
				$classes[$name] = $matches[1];
			}
		}
		
		ksort( $classes, SORT_NATURAL ); // Sorter holdene da de kan været placeret tilfældigt
		
		return $classes;
	}
	
	function get_teachers($id) {
		$teachers = array();
		$lectio_html = get_content("https://www.lectio.dk/lectio/".$id."/FindSkema.aspx?type=laerer");
		$html = new simple_html_dom();
		$html->load($lectio_html);
		$anchors = $html->find('a');
		for ($i = 0; $i < count($anchors); $i++) {
			$href = $anchors[$i]->getAttribute("href");
			if (preg_match("/\/lectio\/\d*\/SkemaNy\.aspx\?type=laerer&amp;laererid=(\d*)/", $href, $matches)) { // Regex
				$name = trim(html_entity_decode($anchors[$i]->plaintext));
				$teachers[$name] = $matches[1];
			}
		}
		
		return $teachers;
	}
	
	
	function get_students_from_page($url) {
		$students = array();
		$lectio_html = get_content($url);
		$html = new simple_html_dom();
		$html->load($lectio_html);
		$anchors = $html->find('a');
		for ($i = 0; $i < count($anchors); $i++) {
			$href = $anchors[$i]->getAttribute("href");
			if (preg_match("/\/lectio\/\d*\/SkemaNy\.aspx\?type=elev&amp;elevid=(\d*)/", $href, $matches)) { // Regex
				$name = trim(html_entity_decode($anchors[$i]->plaintext));
				$students[$name] = $matches[1];
			}
		}
		
		return $students;
	}
	
	function get_students_from_school($id) {
		$url_start = 'http://www.lectio.dk/lectio/'.$id.'/FindSkema.aspx?type=elev&forbogstav=';
		$students = array();
		$alphabet	= array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J',
							'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T',
							'U', 'V', 'W', 'X', 'Y', 'Z', 'Æ', 'Ø', 'Å', '?');
		foreach ($alphabet as $key) {
			$students = array_merge($students, get_students_from_page($url_start.$key));
		}
		
		ksort( $students, SORT_NATURAL ); // Sorter eleverne da de kan været placeret tilfældigt
		
		return $students;
	}
	
	
	function get_schedule($url) {
		$schedule					= array();
		$schedule['title']			= '';
		$schedule['week']			= 0;
		$schedule['year']			= 0;
		$schedule['day']			= '';
		$schedule['weekdays']		= array();
		$schedule['schedule']		= array();
		$schedule['dayschedule']	= array();
		
		$finalrex = "(?=Lærere:|Lærer:|Lokale:|Lokaler:|Note:|Lektier:|Øvrigt indhold:|$)";
		
		$lectio_html = get_content($url);
		$html = new simple_html_dom();
		$html->load($lectio_html);
		$schedule['title'] = $html->find('.s2weekHeader td', 0)->plaintext;
		
		if (preg_match("/Uge (\d*) - (\d*)/", $schedule['title'], $matches)) { // Find uge og år
			$schedule['week'] = floatval($matches[1]);
			$schedule['year'] = floatval($matches[2]);
		}
		
		$headers = $html->find('.s2dayHeader td');
		for ($i = 1; $i < count($headers); $i++) { // Vi starter i = 1 fordi vi springer den første over (altid tom)
			$schedule['weekdays'][] = $headers[$i]->plaintext;
			$schedule['schedule'][$headers[$i]->plaintext] = array(
				'notes' => array(),
				'lessons' => array()
			);
		}
		
		$collection = $html->find('.s2skemabrikcontainer');
		$schedulebrik = new simple_html_dom(); // Definer den her for genbrugens skyld da den kan tømmes efter hver iteration
		
		for ($i = 0; $i < count($schedule['weekdays']); $i++) { // Iterer alle noterne i "toppen" af ugedagene
			$schedulebrik->load($collection[$i]->innertext);
			$noter = $schedulebrik->find('.s2skemabrikcontent');
			foreach($noter as $note) {
				$info = trim(html_entity_decode($note->plaintext));
				$info = preg_replace("#\s+#", " ", $info);
				$schedule['schedule'][$schedule['weekdays'][$i]]['notes'][] = $info; // Tilføj til liste over noter for dagen
			}
		}
		
		// Vi starter iteratoren count($schedule['weekdays']) + 1 fordi vi vil springe topnoter + et stil element i mellem over
		$max = count($schedule['weekdays']);
		for ($i = $max+1; $i < (2*$max+1); $i++) {
			$schedulebrik->load($collection[$i]->innertext);
			// $schedule_fag = $schedulebrik->find('.s2skemabrikcontent'); // Søg efter fag brikkerne. Indeholder alternativ titel
			$schedule_info = $schedulebrik->find('.s2skemabrik'); // Søg efter alle skemabrikkerne. Indeholder modul information
			$day = $schedule['weekdays'][$i-$max-1];
			
			$lessons = array();
			
			for ($y = 0; $y < count($schedule_info); $y++) {
				$info = trim(html_entity_decode($schedule_info[$y]->getAttribute('data-additionalinfo')));
				if (substr($info, 0, 7) == "Aflyst!") continue; // Modulet er aflyst
				$info = preg_replace('/\s+/u', ' ', $info);
				$keyname = 'NA:NA';
				$changed = false;
				$time = array();
				$title = '';
				$team = '';
				$teachers = array();
				$room = array();
				$homework = array();
				$additional = array();
				$note = '';
				
				if (substr($info, 0, 8) == "Ændret!") { // Status
					$changed = true;
					$info = substr($info, 8);
				}
				
				if (preg_match("/(\d\d:\d\d) til (\d\d:\d\d)/", $info, $matches)) { // Find tid
					$keyname = $matches[1];
					$time = array($matches[1], $matches[2]);
				}
				
				if (preg_match("/(?<=Hold:)(.*?)".$finalrex."/", $info, $matches)) { // Hold
					$team = nice_string($matches[1]);
				}
				
				if (preg_match("/(\d*\/\d*-\d*)/", $info, $matches) && strpos($info, $matches[1])) { //  Titel
					$title = nice_string(substr($info, 0, strpos($info, $matches[1])));
				} /*else { // Sæt titlen til faget hvis ingen titel findes
					$title = nice_string(html_entity_decode($schedule_fag[$y]->plaintext));
				}*/
				
				if (preg_match("/Lærer: (.*?) \((\S*)\)/", $info, $matches)) { // En lærer
					$teachers[] = $matches[2];
				} elseif (preg_match("/(?<=Lærere:)(.*?)".$finalrex."/", $info, $matches)) { // Flere lærere
					foreach (explode(', ', $matches[1]) as $name) {
						$teachers[] = nice_string($name);
					}
				}
				
				if (preg_match("/(?<=Lokale:|Lokaler:)(.*?)".$finalrex."/", $info, $matches)) { // Lokaler
					foreach (explode(', ', $matches[1]) as $name) {
						if (trim($name) == "") continue;
						$room[] = nice_string($name);
					}
				}
				
				if (preg_match("/(?<=Lektier:)(.*?)".$finalrex."/", $info, $matches)) { // Lektier
					foreach (explode('[...]', $matches[1]) as $name) {
						if (trim($name) == "") continue;
						$homework[] = nice_string(substr($name, 2));
					}
				}
				
				if (preg_match("/(?<=Øvrigt indhold:)(.*?)".$finalrex."/", $info, $matches)) { // Øvrigt indhold
					foreach (explode('[...]', $matches[1]) as $name) {
						if (trim($name) == "") continue;
						$additional[] = nice_string(substr($name, 2));
					}
				}
				
				if (preg_match("/(?<=Note:)(.*?)".$finalrex."/", $info, $matches)) { // Note
					$note = nice_string($matches[1]);
				}
				
				$key = $keyname;
				$in = 0;
				while (isset($lessons[$key])) { // Brikken findes allerede, inkrementer for ikke at override
					$in++;
					$key = $keyname.'('.$in.')';
				}
				
				$lessons[$key] = array(
					'changed'		=> $changed,
					'time'			=> $time,
					'title'			=> $title,
					'team'			=> $team,
					'teachers'		=> $teachers,
					'classroom'		=> $room,
					'homework'		=> $homework,
					'additional'	=> $additional,
					'note'			=> $note,
					'all'			=> $info
				);
			}
			
			ksort( $schedule['schedule'][$day]['lessons'], SORT_NATURAL ); // Sorter dagene da brikkerne kan været placeret tilfældigt
			
			foreach ($lessons as $lesson) {
				$schedule['schedule'][$day]['lessons'][] = $lesson;
			}
		}
		
		$date = date("j")."\/".date("n");
		
		foreach ($schedule['weekdays'] as $day) {
			if (preg_match("/[a-z]* \(".$date."\)/i", $day)) {
				$schedule['day'] = $day;
				$schedule['dayschedule'] = $schedule['schedule'][$day];
			}
		}
		
		return $schedule;
	}
	
	
	function get_schedule_class($id, $lectio_id, $week=null) {
		$url = 'https://www.lectio.dk/lectio/'.$id.'/SkemaNy.aspx?type=stamklasse&klasseid='.$lectio_id;
		if ($week != null) {
			$url = $url.'&week='.$week;
		}
		return get_schedule($url);
	}
	
	function get_schedule_student($id, $lectio_id, $week=null) {
		$url = 'https://www.lectio.dk/lectio/'.$id.'/SkemaNy.aspx?type=elev&elevid='.$lectio_id;
		if ($week != null) {
			$url = $url.'&week='.$week;
		}
		return get_schedule($url);
	}
	
	function get_schedule_teacher($id, $lectio_id, $week=null) {
		$url = 'https://www.lectio.dk/lectio/'.$id.'/SkemaNy.aspx?type=laerer&laererid='.$lectio_id;
		if ($week != null) {
			$url = $url.'&week='.$week;
		}
		return get_schedule($url);
	}
?>