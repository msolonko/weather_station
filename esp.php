<?php
    $link = mysqli_connect("IP Address of DB or localhost", "USERNAME", "PASSWORD", "DATABASE NAME");//connect to database
    if(mysqli_connect_error()){
        die("Database connection error.");//stop program if there is an error
    }
if(isset($_POST['light'])){//if there is data being sent (if we have light being send, the rest is also being sent)
  $date= new DateTime(null, new DateTimeZone('America/Chicago'));//create a new date for the current moment
  $query = "SELECT time FROM weather_data WHERE id=1 ORDER BY counter DESC LIMIT 1";//select one entry from id 1 in the database
  $result = mysqli_query($link, $query);//get result of query
  if(mysqli_num_rows($result)>0){//if there is data returned
	  $time = date_create_from_format('Y-m-d H:i:s', mysqli_fetch_assoc($result)['time'], new DateTimeZone('America/Chicago'));//make a DATE object from the database data entry
	  if($date->format('d') !== $time->format('d')){//if the DAY is different between today and the date from the database that is labeled as TODAY (id = 1), it is time to change days
		 /*** A call to two functions that average out some data to save database space. For the current setup, we will have 144 entries for current day, 24 for previous, and 1 for the days before that. ***/
		 toDaily();
		  toHourly();
	  }
  }
  /*** The bottom two lines are the most important: they insert the received values into the database ***/
   $query = "INSERT INTO weather_data(time, light, temperature, pressure, humidity, month, year) VALUES ('".(string)$date->format('Y-m-d H:i:s')."', ".mysqli_real_escape_string($link, $_POST['light']).", ".mysqli_real_escape_string($link, $_POST['temperature']).", ".mysqli_real_escape_string($link, $_POST['pressure']).", ".mysqli_real_escape_string($link, $_POST['humidity']).", ".(string)$date->format('m').", ".(string)$date->format('Y').")";
 mysqli_query($link, $query);
}

function toHourly(){//this converts the 144 data points from today to 24 for yesterday
	global $link;
	  $query = "SELECT month, year FROM weather_data WHERE id=1 LIMIT 1";//select the month and year so that we can use that later
	  $row = mysqli_fetch_assoc(mysqli_query($link, $query));
	  $month = $row["month"];
	  $year = $row["year"];
	  $query = "SELECT time, light, temperature, pressure, humidity FROM weather_data WHERE id=1 ORDER BY counter ASC";//select all today's data
	  $result = mysqli_query($link, $query);
	  $remainder = mysqli_num_rows($result)%6;//lets me know how many times to loop
	  $full = floor(mysqli_num_rows($result)/6);//lets me know how many times to loop
	  $timeAvg = $lightAvg = $tempAvg = $pressureAvg = $humidityAvg = array();//make average arrays that will store the 24 average values
	  $light_sum = $temp_sum = $pressure_sum = $humidity_sum = $time_sum = 0;
	  for($i=0; $i<$full; $i++){
		  for($g=0;$g<6;$g++){
			  $row = mysqli_fetch_assoc($result);
			  $time_sum += date_create_from_format('Y-m-d H:i:s', $row['time'], new DateTimeZone('America/Chicago'))->getTimestamp();//gets the timestamp for average time calculation
			  $light_sum += $row['light'];
			  $temp_sum += $row['temperature'];
			  $pressure_sum += $row['pressure'];
			  $humidity_sum += $row['humidity'];
		  }
		  $timeAvg[] = (new DateTime(null, new DateTimeZone('America/Chicago')))->setTimestamp(floor($time_sum/6))->format('Y-m-d H:i:s');//makes a timestamp with the average time
		  $lightAvg[] = floor($light_sum/6);//calculates light average and adds to array
		  $tempAvg[] = floor($temp_sum/6);//as above
		  $pressureAvg[] = floor($pressure_sum/6);//as above
		  $humidityAvg[] = floor($humidity_sum/6);//as above
	    $light_sum = $temp_sum = $pressure_sum = $humidity_sum = $time_sum = 0;//resets variables
	  }
	  if($remainder>0){//accounts for if the id=1 datapoints are not divisible by 6
		  for($i=0; $i<$remainder; $i++){
			   $row = mysqli_fetch_assoc($result);
			   $time_sum += date_create_from_format('Y-m-d H:i:s', $row['time'], new DateTimeZone('America/Chicago'))->getTimestamp();
			   $light_sum += $row['light'];
			   $temp_sum += $row['temperature'];
			  $pressure_sum += $row['pressure'];
			  $humidity_sum += $row['humidity'];
		  }
		   	   $timeAvg[] = (new DateTime(null, new DateTimeZone('America/Chicago')))->setTimestamp(floor($time_sum/$remainder))->format('Y-m-d H:i:s');
		   $lightAvg[] = floor($light_sum/$remainder);//calculates light average
		     $tempAvg[] = $temp_sum/$remainder;
		  $pressureAvg[] = $pressure_sum/$remainder;
		  $humidityAvg[] = $humidity_sum/$remainder;
	  }
	   $query = "DELETE FROM weather_data WHERE id=1";//removes all old data
	   mysqli_query($link, $query);
	   /*** Adds values for every hour, id=2 ***/
	   for($j=0;$j<count($timeAvg);$j++){//loops through all the new data we created
			$query = "INSERT INTO weather_data (time, light, temperature, pressure, humidity, month, year, id) VALUES('".$timeAvg[$j]."', ".$lightAvg[$j].", ".$tempAvg[$j].", ".$pressureAvg[$j].", ".$humidityAvg[$j].", ".$month.", ".$year.", 2)";
			mysqli_query($link, $query);//adds new data to the database with id=2
	   }
}

function toDaily(){//this converts all values from id = 2 (Yesterday) to ONE averaged value
	global $link;
	 	 $query = "SELECT light, temperature, pressure, humidity FROM weather_data WHERE id=2 ORDER BY counter ASC";//select all data from yesterday
	 $result = mysqli_query($link, $query);
	if(mysqli_num_rows($result)>0){
	  $light_sum = $temp_sum = $pressure_sum = $humidity_sum = 0;//set all these variables to 0 so that we can later add values to them and find the average
	  for($i=0; $i<mysqli_num_rows($result); $i++){//loop through returned data
		  $row = mysqli_fetch_assoc($result);
		  $light_sum += $row['light'];//add the light value to variable
		   $temp_sum += $row['temperature'];//same as above
			  $pressure_sum += $row['pressure'];//same as above
			  $humidity_sum += $row['humidity'];//same as above
	  }
	  $lightAvg = floor($light_sum/mysqli_num_rows($result));//calculates light average
	      $tempAvg = $temp_sum/mysqli_num_rows($result);//same as above
		  $pressureAvg = $pressure_sum/mysqli_num_rows($result);//same as above
		  $humidityAvg = $humidity_sum/mysqli_num_rows($result);//same as above
	  $query = "SELECT time, month, year FROM weather_data WHERE id=2 ORDER BY counter ASC LIMIT 1";
		$row = mysqli_fetch_assoc(mysqli_query($link, $query));
	  $time = date_create_from_format('Y-m-d H:i:s', $row['time'], new DateTimeZone('America/Chicago'))->format('Y-m-d')." 00:00:00";//construct the new date which is the year, month, day, and 00:00:00 for the rest since it is only one point that represents the whole day
	  $query = "DELETE FROM weather_data WHERE id=2";//removes all yesterday's data
	  mysqli_query($link, $query);
	   /*** Adds daily value, id=3 ***/
	$query = "INSERT INTO weather_data (time, light, temperature, pressure, humidity, month, year, id) VALUES('".$time."', ".$lightAvg.", ".$tempAvg.", ".$pressureAvg.", ".$humidityAvg.", ".$row["month"].", ".$row["year"].", 3)";
//inserts the new data point to id=3
		mysqli_query($link, $query);
	}
}
?>