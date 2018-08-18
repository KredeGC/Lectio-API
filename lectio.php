<?php
	/*
	Uofficielt API til Lectio
	Baseret på simple_html_dom.php
	Lavet af Henrik Pedersen og Daniel Poulsen
	Opdateret og vedligeholdt af Krede
	*/
	
	class lectio {
		const LECTIO_URL = "https://www.lectio.dk/lectio/";
		
		function __construct($path = null) {
			if ($path == null) {
				$path = 'simple_html_dom.php';
			}
			if (is_file($path) && !class_exists('simple_html_dom')) {
				require_once($path);
			}
		}
		
		private function get_html($url) {
			$options = array(
				'http' => array(
					'method' => "GET",
					'header' => "Accept-language: en\r\n".
								"User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13\r\n"
				)
			);
			
			$context = stream_context_create($options);
			$html = file_get_contents($url, false, $context);
			
			if ($html == null) {
				return "404: Error appeared while retrieving html";
			}
			return $html;
		}
		
		private function nice_string($text) {
			return trim(str_replace("&#39;", "'", $text));
		}
		
		private function get_list($url, $rex) {
			$list = array();
			$html = $this->get_html($url);
			$dom = new simple_html_dom();
			$dom->load($html);
			$anchors = $dom->find('a');
			foreach ($anchors as $anchor) {
				$href = $anchor->getAttribute("href");
				if (preg_match($rex, $href, $matches)) { // Regex
					$name = trim(html_entity_decode($anchor->plaintext));
					$list[$name] = $matches[1];
				}
			}
			
			ksort( $list, SORT_NATURAL ); // Sorter liste
			
			return $list;
		}
		
		public function get_schools() {
			$list = $this->get_list(self::LECTIO_URL."login_list.aspx", "/\/lectio\/(\d*)\/default\.aspx/");
			return $list;
		}
		
		public function get_classes($id) {
			$list = $this->get_list(self::LECTIO_URL.$id."/FindSkema.aspx?type=stamklasse", "/\/lectio\/\d*\/SkemaNy\.aspx\?type=stamklasse&amp;klasseid=(\d*)/");
			return $list;
		}
		
		public function get_teachers($id) {
			$list = $this->get_list(self::LECTIO_URL.$id."/FindSkema.aspx?type=laerer", "/\/lectio\/\d*\/SkemaNy\.aspx\?type=laerer&amp;laererid=(\d*)/");
			return $list;
		}
		
		public function get_students_page($url) {
			$list = $this->get_list($url, "/\/lectio\/\d*\/SkemaNy\.aspx\?type=elev&amp;elevid=(\d*)/");
			return $list;
		}
		
		public function get_students($id) {
			$url = self::LECTIO_URL.$id.'/FindSkema.aspx?type=elev&forbogstav=';
			$students = array();
			$alphabet	= array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J',
								'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T',
								'U', 'V', 'W', 'X', 'Y', 'Z', 'Æ', 'Ø', 'Å', '?');
			foreach ($alphabet as $key) {
				$students = array_merge($students, $this->get_students_page($url.$key));
			}
			
			ksort( $students, SORT_NATURAL ); // Sorter eleverne da de kan været placeret tilfældigt
			
			return $students;
		}
		
		public function get_schedule($url) {
			$schedule					= array();
			$schedule['title']			= '';
			$schedule['week']			= 0;
			$schedule['year']			= 0;
			$schedule['day']			= '';
			$schedule['weekdays']		= array();
			$schedule['schedule']		= array();
			$schedule['dayschedule']	= array();
			
			date_default_timezone_set('Europe/Copenhagen');
			$finalrex = "(?=Lærere:|Lærer:|Lokale:|Lokaler:|Note:|Lektier:|Øvrigt indhold:|$)";
			
			$html = $this->get_html($url);
			$dom = new simple_html_dom();
			$dom->load($html);
			$schedule['title'] = $dom->find('.s2weekHeader td', 0)->plaintext;
			
			if (preg_match("/Uge (\d*) - (\d*)/", $schedule['title'], $matches)) { // Find uge og år
				$schedule['week'] = floatval($matches[1]);
				$schedule['year'] = floatval($matches[2]);
			}
			
			$headers = $dom->find('.s2dayHeader td'); // Vi starter iterationer i 1 fordi vi springer den første over (altid tom)
			for ($i = 1; $i < count($headers); $i++) {
				$name = $headers[$i]->plaintext;
				$schedule['weekdays'][] = $name;
				$schedule['schedule'][$name] = array(
					'notes'	=> array(),
					'lessons'	=> array()
				);
			}
			
			$collection = $dom->find('.s2skemabrikcontainer');
			$schedulebrik = new simple_html_dom(); // Definer den her for genbrugens skyld da den tømmes efter hver iteration
			$max = count($schedule['weekdays']);
			
			for ($i = 0; $i < $max; $i++) { // Iterer alle noterne i "toppen" af ugedagene
				$schedulebrik->load($collection[$i]->innertext);
				$noter = $schedulebrik->find('.s2skemabrikcontent');
				foreach($noter as $note) {
					$info = trim(html_entity_decode($note->plaintext));
					$info = preg_replace("/\s+/u", " ", $info);
					$schedule['schedule'][$schedule['weekdays'][$i]]['notes'][] = $info; // Tilføj til liste over noter for dagen
				}
			}
			
			// Vi starter iteratoren count($schedule['weekdays']) + 1 fordi vi vil springe topnoter + et stil element i mellem over
			for ($i = 0; $i < $max; $i++) {
				$schedulebrik->load($collection[$i+$max+1]->innertext);
				// $schedule_fag = $schedulebrik->find('.s2skemabrikcontent'); // Søg efter fag brikkerne. Indeholder alternativ titel
				$schedule_info = $schedulebrik->find('.s2skemabrik'); // Søg efter alle skemabrikkerne. Indeholder modul information
				$day = $schedule['weekdays'][$i];
				$lessons = array();
				
				for ($y = 0; $y < count($schedule_info); $y++) {
					$info = trim(html_entity_decode($schedule_info[$y]->getAttribute('data-additionalinfo')));
					if (substr($info, 0, 7) == "Aflyst!") continue; // Modulet er aflyst, fjern
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
						$team = $this->nice_string($matches[1]);
					}
					
					if (preg_match("/(\d*\/\d*-\d*)/", $info, $matches) && strpos($info, $matches[1])) { //  Titel
						$title = $this->nice_string(substr($info, 0, strpos($info, $matches[1])));
					}
					
					if (preg_match("/Lærer: (.*?) \((\S*)\)/", $info, $matches)) { // En lærer
						$teachers[] = $matches[2];
					} elseif (preg_match("/(?<=Lærere:)(.*?)".$finalrex."/", $info, $matches)) { // Flere lærere
						foreach (explode(', ', $matches[1]) as $name) {
							$teachers[] = $this->nice_string($name);
						}
					}
					
					if (preg_match("/(?<=Lokale:|Lokaler:)(.*?)".$finalrex."/", $info, $matches)) { // Lokaler
						foreach (explode(', ', $matches[1]) as $name) {
							if (trim($name) == "") continue;
							$room[] = $this->nice_string($name);
						}
					}
					
					if (preg_match("/(?<=Lektier:)(.*?)".$finalrex."/", $info, $matches)) { // Lektier
						foreach (explode('[...]', $matches[1]) as $name) {
							if (trim($name) == "") continue;
							$homework[] = $this->nice_string(substr($name, 2));
						}
					}
					
					if (preg_match("/(?<=Øvrigt indhold:)(.*?)".$finalrex."/", $info, $matches)) { // Øvrigt indhold
						foreach (explode('[...]', $matches[1]) as $name) {
							if (trim($name) == "") continue;
							$additional[] = $this->nice_string(substr($name, 2));
						}
					}
					
					if (preg_match("/(?<=Note:)(.*?)".$finalrex."/", $info, $matches)) { // Note
						$note = $this->nice_string($matches[1]);
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
				
				ksort( $lessons, SORT_NATURAL ); // Sorter dagene da brikkerne kan være placeret tilfældigt
			
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
		
		public function get_schedule_class($id, $lectio_id, $week=null) {
			$url = 'https://www.lectio.dk/lectio/'.$id.'/SkemaNy.aspx?type=stamklasse&klasseid='.$lectio_id;
			if ($week != null) {
				$url = $url.'&week='.$week;
			}
			return  $this->get_schedule($url);
		}
		
		public function get_schedule_student($id, $lectio_id, $week=null) {
			$url = 'https://www.lectio.dk/lectio/'.$id.'/SkemaNy.aspx?type=elev&elevid='.$lectio_id;
			if ($week != null) {
				$url = $url.'&week='.$week;
			}
			return  $this->get_schedule($url);
		}
		
		public function get_schedule_teacher($id, $lectio_id, $week=null) {
			$url = 'https://www.lectio.dk/lectio/'.$id.'/SkemaNy.aspx?type=laerer&laererid='.$lectio_id;
			if ($week != null) {
				$url = $url.'&week='.$week;
			}
			return  $this->get_schedule($url);
		}
	}
?>