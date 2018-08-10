<?php
	$link = mysqli_connect("IP Address of DB or localhost", "USERNAME", "PASSWORD", "DATABASE NAME");//connect to database
    if(mysqli_connect_error()){
        die("Database connection error.");//stop program if there is an error
    }
	$content = json_decode(file_get_contents('php://input'), true);//get provided settings (we will program those in the next step)
	if(isset($content)){//if there is any data
	$day = (int)(new DateTime(null, new DateTimeZone('America/Chicago')))->format('d');//gets today's day
		if($content['all'] == true){//if we want to get ALL data
			if($content['page']=="day"){//if we are getting the ID 1 values - today's values
				$id1 = getDataById(1);//get the data of ID 1 (today)
				$date = substr($id1["time"][0],0,10); //cut the date so that we only see the the date and not the time
				for($i=0; $i<count($id1["time"]); $i++){//loop through every tume entry
					$id1["time"][$i]=substr($id1["time"][$i], 11, 5);//cut the date so that we see the timestamp and not the date (we will have the date displayed on the top)
				}
			$query = "SELECT counter FROM weather_data WHERE id = 1 ORDER BY counter DESC LIMIT 1";//get the primary key of latest database entry
			$result = mysqli_query($link, $query);
			if(mysqli_num_rows($result)==0){//if id 1 does not exist in the database
				$lastSent = 0;//set the last sent id value to 0
			}
			else{//otherwise
				$row = mysqli_fetch_assoc($result);//get the id 1 results from the database
				$lastSent = $row['counter'];//set the last sent value to the id of the latest database entry
			}
			echo json_encode([1 => $id1,// send all the id = 1 values
				  "last" => $lastSent,//send the value of the last primary key value
				  "day" => $day,//send the day (to check later if the day changed)
				  "date" => $date]); // send the data (YYYY-MM-DD)
			}
			else if($content['page']=="old"){//if we want the id=3 (old) data
				$years = years();//returns all the years for which data exists
				$id3 = thirdId($years);//get the id=3 values sorted by the year and month they are from
				for($i=0; $i<count($id3); $i++){//sort through each year
					foreach ($id3[$years[$i]] as $key=>$value) {//assign a key and value to each month in the year array
						for($c=0; $c<count($id3[$years[$i]][$key]['time']); $c++){//sort through each time in a given month
							$id3[$years[$i]][$key]["time"][$c]=substr($id3[$years[$i]][$key]["time"][$c], 0, 10);//change the timestamp to show only the month and day
						}
					}
				}
				echo json_encode([//echo all the data
						3 => $id3,
						"last" => -5,//unused value - can be anything
						"day" => $day,
						"years" => $years]);
			}
			else if($content['page']=="yesterday"){//if we want the id=2 data
				$id2 = getDataById(2);//get data
					$date = substr($id2["time"][0],0,11);//get date
					for($i=0; $i<count($id2["time"]); $i++){
						$id2["time"][$i]=substr($id2["time"][$i], 11, 5);//get the time out of the timestamp and omit the date for each item
					}
				echo json_encode([//send the data
						2 => $id2,
						"last" => -5,//unused value
						"day" => $day,
						"date" => $date]);
				}
			
		}
		else if($content['all'] == false){//if we only want the new id=1 data
			$lastSent = $content['last'];//get last sent value (given in the settings)
			$query = "SELECT time, light, temperature, pressure, humidity, counter FROM weather_data WHERE id=1 AND counter>".(string)$lastSent." ORDER BY counter DESC";// get all data newer than the given lastSent
			$result = mysqli_query($link, $query);
			$id1 = array();//make array for id 1
			$id1['time'] = $id1['light'] = $id1['temperature'] = $id1['pressure'] = $id1['humidity'] = array();//make empty arrays for all data values
			if(mysqli_num_rows($result)>0){//if there is at least one new data point
				$row = mysqli_fetch_assoc($result);
				$id1['time'][] = $row['time'];//add time to array
				$id1['light'][] = $row['light'];//add light to array
				$id1['temperature'][] = $row['temperature'];//as above
				$id1['pressure'][] = $row['pressure'];//as above
				$id1['humidity'][] = $row['humidity'];//as above
				$lastSent = $row['counter'];//make the last sent the lsat counter value
				if(mysqli_num_rows($result)>1){//if there is any more data
					while($row = mysqli_fetch_assoc($result)){//loop through newer data and add it to arrays
						$id1['time'][] = $row['time'];
						$id1['light'][] = $row['light'];
						$id1['temperature'][] = $row['temperature'];
						$id1['pressure'][] = $row['pressure'];
						$id1['humidity'][] = $row['humidity'];
					}
				}
			}
			for($i=0; $i<count($id1["time"]); $i++){
				$id1["time"][$i]=substr($id1["time"][$i], 11, 5);//make the timestamp display only the time
			}
			echo json_encode([//send the data
				  1 => $id1,
				  "last" => $lastSent,
				  "day" => $day]);
		}
	}
	
	function getDataById($id){//gets data from a certain id
		/* Will return 2D array */
		global $link;
		$array = array();//array that will be returned
		$array['time'] = $array['light'] = $array['temperature'] = $array['pressure'] = $array['humidity'] = array();//make empty arrays
		$query = "SELECT time, light, temperature, pressure, humidity, counter FROM weather_data WHERE id = ".$id." ORDER BY counter ASC";//select data by id
		$result = mysqli_query($link, $query);
			while($row = mysqli_fetch_assoc($result)){//loop through data and add to array
				$array["time"][]=$row['time'];
				$array["light"][]=$row['light'];
				$array['temperature'][] = $row['temperature'];
				$array['pressure'][] = $row['pressure'];
				$array['humidity'][] = $row['humidity'];
			}
		return $array;//return the array
	}
	function years(){//returns distinct years
		/*returns all years for which data exists*/
		global $link;
		$array = array();
		$query = "SELECT DISTINCT year FROM weather_data WHERE id=3 ORDER BY counter ASC";//selects DISTINCT years
		$result = mysqli_query($link, $query);
		while($row = mysqli_fetch_assoc($result)){
				$array[]=$row["year"];//adds each year to array
			}
		return $array;//returns array
	}
function thirdId($y){
	/*returns array sorted with months and years*/
	global $link;
	$array = array();//array to be returned
	for($i = 0; $i<count($y); $i++){//loops through each distinct year
		$m = array();//month array
		$query = "SELECT DISTINCT month FROM weather_data WHERE year = ".$y[$i]." AND id=3 ORDER BY counter ASC";//gets distinct month
		$result = mysqli_query($link, $query);
		while($row = mysqli_fetch_assoc($result)){
				$m[]=$row["month"];//adds distinct month to array
		}
		for($j=0;$j<count($m);$j++){//loops through each month
			$array[$y[$i]][$m[$j]]['time'] = $array[$y[$i]][$m[$j]]['light'] = $array[$y[$i]][$m[$j]]['temperature'] = $array[$y[$i]][$m[$j]]['pressure'] = $array[$y[$i]][$m[$j]]['humidity'] = array();//makes empty array by month and year
			$query = "SELECT time, light, temperature, pressure, humidity, counter FROM weather_data WHERE id=3 AND year=".$y[$i]." AND month=".$m[$j]." ORDER BY counter ASC";//selects data from certain month and year
			$result = mysqli_query($link, $query);
			while($row = mysqli_fetch_assoc($result)){//loop through data
				$array[$y[$i]][$m[$j]]["time"][]=$row['time'];//add time to array for specific month and year
				$array[$y[$i]][$m[$j]]["light"][]=$row['light'];//as above
				$array[$y[$i]][$m[$j]]['temperature'][] = $row['temperature'];//as above
				$array[$y[$i]][$m[$j]]['pressure'][] = $row['pressure'];//as above
				$array[$y[$i]][$m[$j]]['humidity'][] = $row['humidity'];//as above
			}
		}
	}
	return $array;//return array
}
?>