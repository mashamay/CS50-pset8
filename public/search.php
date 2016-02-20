<?php

    require(__DIR__ . "/../includes/config.php");

    // numerically indexed array of places
    $places = [];

    // TODO: search database for places matching $_GET["geo"]
	
    $params = array_map("trim", preg_split("/[\ \n\,]+/", $_GET["geo"]));

    // remove "US" param
    if (($index = array_search("US", $params)) !== false) {
        unset($params[$index]);
    }
   
    $search = [];
    $cityFound = 0;
    $stateFound = 0;
    $zipFound = 0;
    
    for ( $n = 0; $n < count($params); $n++ ) 
    {
        //if only digits => set id zip 
        if (is_numeric($params[$n])) 
        { 
            $search['zip'] = $params[$n];
            $zipFound = 1;
        }
        
        // if two letters => set id state 
        elseif ( strlen($params[$n]) === 2 ) 
        { 
            // compare with the the table
            $state = query("SELECT admin_name1 FROM places WHERE admin_code1 = (?) LIMIT 1", $params[$n]);
            // if match was found change to full name
            if ($state)
            {
                // change array with corresponding state full name
                $search['state'] = $state[0]['admin_name1'];
                $stateFound = 1;
            }
         }
         
        // if match against admin_name1 => state
        elseif ( strlen($params[$n]) > 2)
        {
            // compare with the the table -> state
            $state = query("SELECT admin_name1 FROM places WHERE admin_name1 = (?) LIMIT 1", $params[$n]); 
            
            // if match was found change to full name
            if ($state)
            {
                // change array with corresponding state full name
                $search['state'] = $params[$n];
                $stateFound = 1;
            }
            
            // compare with the the table city
            $city = query("SELECT place_name FROM places WHERE place_name = (?) LIMIT 1", $params[$n]);
            // if match was found set key <- city
            if ($city)
            {
                $search['city'] = $params[$n];
                $cityFound = 1;
            }
        }      
    }
    
    // if zip found
    if ($zipFound === 1)
    {
        // put the array back together using implode().
        $query = $search['zip'];
        // Search across multiple columns
        $places = query("SELECT * FROM places WHERE MATCH(postal_code, place_name, admin_name1, admin_code1) AGAINST (?)", $query);
    }
    
    // if only only city or state found
    elseif ($cityFound + $stateFound === 1)
    {
        // put the array back together using implode().
        $query = implode(" ", $search);
        // Search across multiple columns
        $places = query("SELECT * FROM places WHERE MATCH(postal_code, place_name, admin_name1, admin_code1) AGAINST (?)", $query);
    }
 
    // if city and state found
    elseif ($cityFound + $stateFound === 2)
    {
        // Search across multiple columns
        $places = query("SELECT * FROM places WHERE place_name = ? AND admin_name1 = ?", $search['city'], $search['state']);
    }   
    
    //foreach ($rows as $value)
    //{
      //  array_push($places, $value);
    //}

    // output places as JSON (pretty-printed for debugging convenience)
    header("Content-type: application/json");
    print(json_encode($places, JSON_PRETTY_PRINT));

?>
