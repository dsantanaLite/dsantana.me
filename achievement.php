<?php 

		/*
			$array - array to add surrounding chars to each element
			$chartype - a string of two chars, the opening char then closing char. 

			return the given array with all surrounding chars added. 

		*/
		function addSurroundingChars ($array,$chartype){

			foreach ($array as &$element){
				$element=$chartype[0].$element.$chartype[1];
			}
			return $array;
		}

		#width of given string. 
		function lengthOfChars($string){

			$str_arr = str_split($string);
			$len = 0;
			foreach ($str_arr as $char){
					$len+=getSizeOfChar($char);
			}
			return $len;
			
		}

		#length values based on steam font looked at by eye. Not exact...
		#sizes are relative, ex: Capital W(16) looks 4 times bigger than Capital J(4)
		function getSizeOfChar($char){

			$charArray = [
			//special chars
			'['=>6,']'=>6,'#'=>8,' '=>4,' '=>7,'-'=>6,':'=>6,'.'=>6,'Û'=>12,','=>7,'!'=>7,'6'=>7,'*'=>7, '`'=>6,
			//lowercase alpha
			'a'=>8,'b'=>8,'c'=>8,'d'=>8,'e'=>8,'f'=>7,'g'=>8,'h'=>8,'i'=>4,'j'=>4,'k'=>8,'l'=>4,'m'=>14,'n'=>8,'o'=>8,'p'=>8,'q'=>8,'r'=>7,
				's'=>8,'t'=>7,'u'=>8,'v'=>8,'w'=>12,'x'=>8,'y'=>8,'z'=>8,
			//digits
			'1'=>8,'2'=>8,'3'=>8,'4'=>8,'5'=>8,'6'=>8,'7'=>8,'8'=>8,'9'=>8,'0'=>8,
			//uppercase alpha
			'A'=>12,'B'=>12,'C'=>12,'D'=>12,'E'=>12,'F'=>11,'G'=>13,'H'=>12,'I'=>7,'J'=>4,'K'=>12,'L'=>8,'M'=>14,'N'=>12,'O'=>13,'P'=>12,
				'Q'=>13,'R'=>12,'S'=>12,'T'=>11,'U'=>12,'V'=>12,'W'=>16,'X'=>12,'Y'=>12,'Z'=>11];

			if(array_key_exists($char, $charArray))
				return $charArray[$char];
			else 
				return 8;

		}

		#Return array of how many games completed in a year
		#returns: [year=>numGamesCompleted]
		function createYearArray ($datesArray){

			//make the array of dates one big string
			$text = implode(",",$datesArray);

			//match all years store result in yearArray
			preg_match_all('/\d\d\d\d/', $text,$yearArray,PREG_PATTERN_ORDER);

			//returns array of form year => number of occurences. 
			$returnArray = array_count_values($yearArray[0]);
		
			return $returnArray;

		}

		#Return array of how many games completed per month
		#returns: [year-month=>numGamesCompleted]
		function createMonthArray ($datesArray){

			$text =implode(",",$datesArray);

			//match all year-month combos, store result in monthArray
			preg_match_all('/\d\d\d\d-\d\d/', $text,$monthArray,PREG_PATTERN_ORDER);

			//count the number of values in each year-month combo
			//returns array of form year-month => number of occurences. 
			$returnArray=array_count_values($monthArray[0]);

			return $returnArray;

		}

		//arg: str of form dddd-dd-dd
		//return: dddd
		function getYear($str){

			preg_match('/\d\d\d\d/', $str,$temp);
			return $temp[0];

		}

		//arg: str of form dddd-dd-dd
		//return: dddd-dd
		function getMonthYearNum($str){
			preg_match('/\d\d\d\d-\d\d/', $str,$temp);
			return $temp[0];
		}


		/*
		function: getMonthYearString($str)

		Arg: $str - a string containing the form dddd-dd-dd for a date

		Return: a string of format Month Year (May 2017)

		*/
		function getMonthYearString($str){
			
			$formatstring="";

			$months = [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 =>'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'];

			//get all sequences of digits
			preg_match_all('/\d+/', $str,$temp);

			//the month value will be in the first set(perfect matches), and be the second match (after year).
			$month_num = $temp[0][1];

			//temp[0][0] = year
			$formatstring.=$months[$month_num]." ".$temp[0][0];
 
			return $formatstring;
		}

	/*

		function: getAstatsInfo($steamid)

		Accepts a steamid64 as argument and gets contents of the astats page that corresponds to this steam id

		url args{
			Limit = 0 Show all games on one page. 
			PerfectOnly = 1 Only show 100% games.
		}

		Errors: Check if the user has a profile/entered id correctly/has any games completed 100%

		Returns: content of that Astats page as a string. 

	*/
	function getAstatsInfo($steamid){

		$url = "http://astats.astats.nl/astats/User_Games.php?Limit=0&PerfectOnly=1&Hidden=1&SteamID64=$steamid&DisplayType=1";
		$achievement_page = @file_get_contents($url);

		if(!$achievement_page){
			echo "ERROR: Failed to connect to astats site.";
			exit;
		}

		if(preg_match('/No profile found/',$achievement_page)||empty($achievement_page)){
			echo "Profile with that number couldn't be found.\nEnter a SteamId64: Should be 17 digits long\nMake sure the account has an astats profile generated.";
			exit;
		}

		if(preg_match('/No results/',$achievement_page)){
			echo "Didn't find any 100% completed games.";
			exit;
		}

		return $achievement_page;

	}

	/*
		Add line number to the left of each line of output.
		Arg: Array to add numbers to  
		Format: #(value)
		Return: Array with numbers added. 
	*/
	function addLineCount($array){

		$count = count($array);

		for($i=0;$i<$count;$i++){
			$len = $count - $i;
			if(($len)<10)
				$array[$i] = "#0$len - " . $array[$i];
			else
				$array[$i] = "#$len - "  . $array[$i];
		}

		return $array;

	}

	function createSteamFormat($steamid,$date_column,$num_column,$split,$schar){

		$steamid = htmlspecialchars($steamid);
		if(strlen($steamid)!=17){
			return "SteamId64 is 17 digits long. Make sure it is entered correctly.";
		}

		$achievement_page = getAstatsInfo($steamid);

		//the longest lines can be before they wrap around in the steam info box. (based on 1080p)
		$max_len=750;
		//about how long every date of format [dddd-dd-dd] is. vary slightly based on the actual digits. 
		$date_len=85;

		//slimpage gets just the game data html, remove most website styling. 
		preg_match("/<tbody>[\s\S]*<\/tbody>/",$achievement_page,$temp);
		$slimpage = $temp[0];

		//create array of each games html elements. 
		preg_match_all('/<a href="Steam_Game_Info.+?<\/a>/', $slimpage,$temp1,PREG_PATTERN_ORDER);
		$names = $temp1[0];

		//delete garbage from name strings. 
		foreach ($names as &$element){
			$element=preg_replace("/<a href=.*AEE\'>/",'',$element);
			$element=preg_replace("/<\/a>/",'',$element);
		}

		//extract num achievements into $total array
		preg_match_all("/<\/a>.{46}\d+/", $slimpage,$temp2,PREG_PATTERN_ORDER);
		$tempStr=implode(",",$temp2[0]);
		preg_match_all("/AEE'>\d+/", $tempStr,$temp2,PREG_PATTERN_ORDER);
		$tempStr=implode(",",$temp2[0]);
		preg_match_all("/\d+/", $tempStr,$temp2,PREG_PATTERN_ORDER);
		$total = $temp2[0];

		//get dates from slimpage.
		preg_match_all('/\d*-\d*-\d*/', $slimpage,$temp3,PREG_PATTERN_ORDER);
		$dates = $temp3[0];

		//shorten very long game names, add ... to end. 
		foreach($names as &$line){
			if(lengthOfChars($line)>=300){
				$diff = lengthOfChars($line)-300;
				$diff = $diff/8;
				$line = substr($line,0,strlen($line)-$diff);
				$line = $line . "...";
			}
		}

		$dates=addSurroundingChars($dates,"[]");
		$total=addSurroundingChars($total,"[]");
		$names=addSurroundingChars($names,"[]");

		$least = min(count($names),count($dates),count($total));

		$names = addLineCount($names);

		$greatest=0;

		//find the length of the longest name, used to determine how many seperator chars to add. 
		foreach ($names as $item){
			$len = lengthOfChars($item);
			if($len>$greatest)
				$greatest=$len;
		}

		//Add a few extra chars so that the longest name has seperation too. 
		$greatest = $greatest+100;

		//if user wants either column, add the seperator char in after the names. 
		if($num_column=='true'||$date_column=='true'){
			foreach ($names as &$line){

				//(the length of the longest name + 100) - how long this name is. 
				$difference = $greatest - lengthOfChars($line);

				$numspace = $difference/getSizeOfChar($schar);

				while ($numspace>0){
					$line.=$schar;
					$numspace = $numspace - 1;
				}
			}
		}

		if($date_column=='true'&&$num_column=='true'){
			for($i=0;$i<$least;$i++){
				$numspace = ($max_len - lengthOfChars($names[$i])-lengthOfChars($total[$i])-$date_len)/getSizeOfChar($schar);
				while ($numspace>0){
					$total[$i].=$schar;
					$numspace = $numspace - 1;
				}
			}
		}

		if($split=="year")
			$dateHash=createYearArray($dates);
		else
			$dateHash=createMonthArray($dates);

		$newFile = "";

		//build up a line of all the elements that the user wants. 
		for ($i=0; $i<$least;$i++){
			$theline = $names[$i];
			$numline = $total[$i];
			$dateline = $dates[$i];
			if($split=="year"&&$dateHash[getYear($dates[$i])]>0){
				$newFile .= "[h1]" . getYear($dates[$i]) . " - ";
				$newFile .= $dateHash[getYear($dates[$i])] . " Games Completed[/h1] \n";	
				$dateHash[getYear($dates[$i])]=-1;
			}else if($split=="month"&&$dateHash[getMonthYearNum($dates[$i])]>0){
				$newFile .= "[h1]" . getMonthYearString($dates[$i]) . " - ";
				if($dateHash[getMonthYearNum($dates[$i])]>1)
					$newFile .= $dateHash[getMonthYearNum($dates[$i])] . " Games Completed[/h1] \n";
				else
					$newFile .= $dateHash[getMonthYearNum($dates[$i])] . " Game Completed[/h1] \n";
				$dateHash[getMonthYearNum($dates[$i])]=-1;
			}

			if($num_column=='true')
				$theline.=$numline;
			if($date_column=='true')
				$theline.=$dateline;
			$theline.="\n";
			$newFile.=$theline;
		}

			return $newFile;
	}

	if(isset($_POST["steamid"]) && isset($_POST["date_column"]) && isset($_POST["num_column"]) 
		&& isset($_POST["split"]) && isset($_POST["schar"]))
	{
		echo createSteamFormat($_POST["steamid"], $_POST['date_column'], $_POST["num_column"],$_POST["split"],$_POST["schar"]);
	}else{
		echo "Enter a SteamId64: Should be 17 digits long";
	}

?> 