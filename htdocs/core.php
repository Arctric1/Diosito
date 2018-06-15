<?php
    // --------------------------------------------------------------------------------------------------------------------- //
    // This is the core file where all queries are executed
    // These are the only settings in the file. You need to set up the correct URL's for the linked files.
    
    define('config', 'config/config.php');
    
    
    // Do not edit anything under this comment
    // --------------------------------------------------------------------------------------------------------------------- //
    if(!include_once(constant('config'))) {
        echo "Could not find config file: " . constant('config') . "<br>";
        echo "Please check your settings";
        return;
    }
    date_default_timezone_set(constant('timezone'));
    if(isset($_GET["query"])) {
        header('Content-Type: application/json');
        switch($_GET["query"]) {
            case "name": {
                if(isset($_GET["value"])) echo(json_encode(getNames($_GET["value"])));
                break;
            }
            case "playercount": {
                echo(json_encode(array("result" => getPlayerCount())));
                break;
            }
            case "leaderboard": {
                echo(json_encode(getLeaderboard()));
                break;
            }
        }
        exit();
    }	
    function filter($input) {
        return preg_replace("/[^a-zA-Z0-9_-]+/", "", $input);
    }
    function hasSetting($setting, $array) {
        return array_key_exists($setting, $array);
    }
    function getPlayerInfo($connection, $name) {
        $query = "SELECT uuid, player_id, last_join FROM leaderheadsplayers WHERE name = '" . $name . "' ORDER BY last_join LIMIT 1";
        $result_set = $connection->query($query) or trigger_error($connection->error);
        return $result_set;
    }
    function getLeaderboard() {
        if(!isset($_GET["page"])) return;
        $page = $_GET["page"];
        if(!ctype_digit($page)) return;
        if(!isset($_GET["count"])) return;
        $count = $_GET["count"];
        if(!ctype_digit($count)) return;
        $lower_limit = rtrim(rtrim(sprintf('%.8F', ($page - 1) * $count), '0'), ".");
        $upper_limit = rtrim(rtrim(sprintf('%.8F', $count), '0'), ".");
        $current_day = date("N");
        $previous_day = $current_day == 1 ? 7 : $current_day - 1;
        $current_year = date("Y");
        $current_week = date("W");
        $previous_week = date("W", strtotime(date("Y-m-d", strtotime("-1 week"))));
        $previous_week_year = date("Y", strtotime(date("Y-m-d", strtotime("-1 week"))));
       
        $current_month = date("n");
        $previous_month = $current_month == 1 ? 10 : $current_month - 1;
        $previous_month_year = $current_month == 1 ? $current_year - 1 : $current_year;
        $tables = json_decode($_GET["tables"]);
        $order_table;
        $order_index;
        $query = "SELECT p.name";
        for($x=0; $x < count($tables); $x++) {
            if($tables[$x]->time_type == "alltime") {
                $query .= ", {$x}a.stat_value";
            } else {
                $query .= ", ({$x}n.stat_value - {$x}o.stat_value)";
            }
            if(isset($tables[$x]->order) && $tables[$x]->order == true) {
                $order_table = $tables[$x];
                $order_index = $x;
            }
        }
        $query .= " FROM leaderheadsplayers p";
        for($x=0; $x < count($tables); $x++) {
            $table = filter($tables[$x]->table);
            $type = filter($tables[$x]->type);
            switch($tables[$x]->time_type) {
                case "alltime": {
                    $query .= " LEFT JOIN $table {$x}a ON {$x}a.player_id = p.player_id AND {$x}a.stat_type = '$type'";
                    break;
                }
                case "daily": {
                    $query .= " LEFT JOIN $table {$x}n ON {$x}n.player_id = p.player_id AND {$x}n.stat_type = '$type' AND {$x}n.day = $current_day";
                    $query .= " LEFT JOIN $table {$x}o ON {$x}o.player_id = p.player_id AND {$x}o.stat_type = '$type' AND {$x}o.day = $previous_day";
                    break;
                }
                case "weekly": {
                    $query .= " LEFT JOIN $table {$x}n ON {$x}n.player_id = p.player_id AND {$x}n.stat_type = '$type' AND {$x}n.week = $current_week AND {$x}n.year = $current_year";
                    $query .= " LEFT JOIN $table {$x}o ON {$x}o.player_id = p.player_id AND {$x}o.stat_type = '$type' AND {$x}o.week = $previous_week AND {$x}o.year = $previous_week_year";
                    break;
                }
                case "monthly": {
                    $query .= " LEFT JOIN $table {$x}n ON {$x}n.player_id = p.player_id AND {$x}n.stat_type = '$type' AND {$x}n.month = $current_month AND {$x}n.year = $current_year";
                    $query .= " LEFT JOIN $table {$x}o ON {$x}o.player_id = p.player_id AND {$x}o.stat_type = '$type' AND {$x}o.month = $previous_month AND {$x}o.year = $previous_month_year";
                    break;
                }
            }
        }
        $query .= " WHERE p.player_id IN (SELECT player_id FROM (";
        $table = filter($order_table->table);
        $type = filter($order_table->type);
        switch($order_table->time_type) {
            case "alltime": {
                $query .= "SELECT 0a.player_id FROM $table 0a WHERE 0a.stat_type = '$type' ORDER BY 0a.stat_value DESC LIMIT $lower_limit, $upper_limit";
                break;
            }
            case "daily": {
                $query .= "SELECT 0n.player_id FROM $table 0n LEFT JOIN $table 0o ON 0o.player_id = 0n.player_id AND 0o.stat_type = '$type' AND 0o.day = $previous_day WHERE 0n.stat_type = '$type' AND 0n.day = $current_day ORDER BY (0n.stat_value - 0o.stat_value) DESC LIMIT $lower_limit, $upper_limit";
                break;
            }
            case "weekly": {
                $query .= "SELECT 0n.player_id FROM $table 0n LEFT JOIN $table 0o ON 0o.player_id = 0n.player_id AND 0o.stat_type = '$type' AND 0o.week = $previous_week AND 0o.year= $previous_week_year WHERE 0n.stat_type = '$type' AND 0n.week = $current_week AND 0n.year = $current_year ORDER BY (0n.stat_value - 0o.stat_value) DESC LIMIT $lower_limit, $upper_limit";

                break;
            }
            case "monthly": {
                $query .= "SELECT 0n.player_id FROM $table 0n LEFT JOIN $table 0o ON 0o.player_id = 0n.player_id AND 0o.stat_type = '$type' AND 0o.month = $previous_month AND 0o.year= $previous_month_year WHERE 0n.stat_type = '$type' AND 0n.month = $current_month AND 0n.year = $current_year ORDER BY (0n.stat_value - 0o.stat_value) DESC LIMIT $lower_limit, $upper_limit";
                break;
            }
        }
        $query .= ") 1a)";
        if($order_table->time_type == "alltime") {
            $query .= " ORDER BY {$order_index}a.stat_value DESC";
        } else {
            $query .= " ORDER BY ({$order_index}n.stat_value - {$order_index}o.stat_value) DESC";
        }
        $results = array();
        $connection = getConnection();
        if($connection == NULL) return;
        $result_set = $connection->query($query) or trigger_error($connection->error);
        if($result_set) {
            while($fetched_row = $result_set->fetch_array()) {
                $result = array();
                for($x = 0; $x <= count($tables); $x++) {
                    $result[$x] = $fetched_row[$x];
                }
                array_push($results, $result);
            }
            $result_set->close();
        }
        $connection->close();
        return(json_encode($results));
    }
    function getPlayerCount() {
        $connection = getConnection();
        $query =  "SELECT COUNT(*) FROM leaderheadsplayers";
        $result_set = $connection->query($query) or trigger_error($connection->error);
        $row = $result_set->fetch_array();
        $count = $row[0];
        $result_set->close();
        $connection->close();
        return $count;
    }
    function getNames($name) {
        $name = filter($name);
        if(empty($name)) return array();
        $connection = getConnection();
        $query = "SELECT name FROM leaderheadsplayers WHERE name LIKE '$name%'";
        $result_set = $connection->query($query) or trigger_error($connection->error);
        $names = array();
        while($row = $result_set->fetch_array()) {
            $names[] = $row[0];
        }
        $connection->close();
        return $names;
    }
    function getPlayerStats($connection, $id, $rows) {
        $results = array();
        $alltime_rows = array();
        foreach($rows as $row) if($row["time_type"] == "alltime") array_push($alltime_rows, $row);
        $monthly_rows = array();
        foreach($rows as $row) if($row["time_type"] == "monthly") array_push($monthly_rows, $row);
        $weekly_rows = array();
        foreach($rows as $row) if($row["time_type"] == "weekly") array_push($weekly_rows, $row);
        $daily_rows = array();
        foreach($rows as $row) if($row["time_type"] == "daily") array_push($daily_rows, $row);
        if(!empty($alltime_rows)) {
            $query = "SELECT ";
            foreach($alltime_rows as $row) {
                $query .= $row["alias"] . ".stat_value, ";
            }
            $query = substr($query, 0, -2);
            $query .= " FROM leaderheadsplayers p ";
            foreach($alltime_rows as $row) {
                $clean_type = $row["alias"];
                $query .= "LEFT JOIN "  . $row["table"] . " $clean_type ON $clean_type.player_id = " . $id . " AND $clean_type.stat_type='" . $row["type"] . "' ";
            }
            $query .= "WHERE p.player_id = " . $id . " LIMIT 1";
            $result_set = $connection->query($query) or trigger_error($connection->error."[$query]");
            if($result_set) {
                while($fetched_row = $result_set->fetch_array()) {
                    $index = 0;
                    foreach($alltime_rows as $row) {
                        $results[$row["index"]] = $fetched_row[$index];
                        $index++;
                    }
                }
            }
        }
        if(!empty($monthly_rows)) {
			$current_month = date("n");
			$previous_month = $current_month == 1 ? 10 : $current_month - 1;
			$current_year = date("Y");
			$previous_year = $current_month == 1 ? $current_year - 1 : $current_year;
            $query = "SELECT ";
            foreach($monthly_rows as $row) {
                $clean_type = $row["alias"];
                $query .= "($clean_type" . "_new.stat_value - $clean_type" . "_old.stat_value), ";
            }
            $query = substr($query, 0, -2);
            $query .= " FROM leaderheadsplayers p ";
            foreach($monthly_rows as $row) {
                $clean_type = $row["alias"];
                $query .= "LEFT JOIN "  . $row["table"] . " $clean_type" . "_new ON $clean_type" . "_new.player_id = p.player_id AND $clean_type" . "_new.stat_type='" . $row["type"] . "' AND $clean_type" . "_new.month = $current_month AND $clean_type" . "_new.year = $current_year ";
                $query .= "LEFT JOIN "  . $row["table"] . " $clean_type" . "_old ON $clean_type" . "_old.player_id = p.player_id AND $clean_type" . "_old.stat_type='" . $row["type"] . "' AND $clean_type" . "_old.month = $previous_month AND $clean_type" . "_old.year = $previous_year ";
            }
            $query .= "WHERE p.player_id = " . $id . " LIMIT 1";
            $result_set = $connection->query($query) or trigger_error($connection->error."[$query]");
            if($result_set) {
                while($fetched_row = $result_set->fetch_array()) {
                    $index = 0;
                    foreach($monthly_rows as $row) {
                        $results[$row["index"]] = $fetched_row[$index];
                        $index++;
                    }
                }
            }
        }
        if(!empty($weekly_rows)) {
            $current_week = date("W");
            $previous_week = date("W", strtotime(date("Y-m-d", strtotime("-1 week"))));
            $current_year = date("Y");
            $previous_year = date("Y", strtotime(date("Y-m-d", strtotime("-1 week"))));
            $query = "SELECT ";
            foreach($weekly_rows as $row) {
                $clean_type = $row["alias"];
                 $query .= "($clean_type" . "_new.stat_value - $clean_type" . "_old.stat_value), ";
            }
            $query = substr($query, 0, -2);
            $query .= " FROM leaderheadsplayers p ";
            foreach($weekly_rows as $row) {
                $clean_type = $row["alias"];
                $query .= "LEFT JOIN "  . $row["table"] . " $clean_type" . "_new ON $clean_type" . "_new.player_id = p.player_id AND $clean_type" . "_new.stat_type='" . $row["type"] . "' AND $clean_type" . "_new.week = $current_week AND $clean_type" . "_new.year = $current_year ";
                $query .= "LEFT JOIN "  . $row["table"] . " $clean_type" . "_old ON $clean_type" . "_old.player_id = p.player_id AND $clean_type" . "_old.stat_type='" . $row["type"] . "' AND $clean_type" . "_old.week = $previous_week AND $clean_type" . "_old.year = $previous_year ";
            }
            $query .= "WHERE p.player_id = " . $id . " LIMIT 1";
            $result_set = $connection->query($query) or trigger_error($connection->error."[$query]");
            if($result_set) {
                while($fetched_row = $result_set->fetch_array()) {
                    $index = 0;
                    foreach($weekly_rows as $row) {
                        $results[$row["index"]] = $fetched_row[$index];
                        $index++;
                    }
                }
            }
        }
        if(!empty($daily_rows)) {
            $current_day = date("N");
            $previous_day = $current_day == 1 ? 7 : $current_day - 1;
            $query = "SELECT ";
            foreach($daily_rows as $row) {
                $clean_type = $row["alias"];
                 $query .= "($clean_type" . "_new.stat_value - $clean_type" . "_old.stat_value), ";
            }
            $query = substr($query, 0, -2);
            $query .= " FROM leaderheadsplayers p ";
            foreach($daily_rows as $row) {
                $clean_type = $row["alias"];
                $query .= "LEFT JOIN "  . $row["table"] . " $clean_type" . "_new ON $clean_type" . "_new.player_id = p.player_id AND $clean_type" . "_new.stat_type='" . $row["type"] . "' AND $clean_type" . "_new.day = $current_day ";
                $query .= "LEFT JOIN "  . $row["table"] . " $clean_type" . "_old ON $clean_type" . "_old.player_id = p.player_id AND $clean_type" . "_old.stat_type='" . $row["type"] . "' AND $clean_type" . "_old.day = $previous_day ";
            }
            $query .= "WHERE p.player_id = " . $id . " LIMIT 1";
            $result_set = $connection->query($query) or trigger_error($connection->error."[$query]");
            if($result_set) {
                while($fetched_row = $result_set->fetch_array()) {
                    $index = 0;
                    foreach($daily_rows as $row) {
                        $results[$row["index"]] = $fetched_row[$index];
                        $index++;
                    }
                }
            }
        }
        return $results;
    }
    function getConnection() {
        $connection = new mysqli(constant('database_host'), constant('database_username'), constant('database_password'), constant('database_database'), constant('database_port'));
        if($connection->connect_error) {
           return NULL;
        }
        return $connection;
    }
    function writeConsole($data) {
        if(constant('debug')) {
            if(is_array($data) || is_object($data)) {
		echo("<script>console.log('PHP: ".json_encode($data)."');</script>");
            } else {
		echo("<script>console.log('PHP: ".$data."');</script>");
            }
        }
    }
    function format_interval(DateInterval $interval, $messages, $last_seen_interval) {
        if($interval->i <= $last_seen_interval && $interval->y == 0 && $interval->m == 0 && $interval->d == 0 && $interval->h == 0) return $messages["just_now"];
        if($interval->y == 1) return $interval->format($messages["year_ago"]);
        if($interval->y) return $interval->format($messages["years_ago"]);
        if($interval->m == 1) return $interval->format($messages["month_ago"]);
        if($interval->m) return $interval->format($messages["months_ago"]);
        if($interval->d == 1) return $interval->format($messages["day_ago"]);
        if($interval->d) return $interval->format($messages["days_ago"]);
        if($interval->h == 1) return $interval->format($messages["hour_ago"]);
        if($interval->h) return $interval->format($messages["hours_ago"]);
        if($interval->i == 1) return $interval->format($messages["minute_ago"]);
        if($interval->i) return $interval->format($messages["minutes_ago"]);
        return "";
    }  
    function formatTime($minutes, $time_format_days, $time_format_hours, $time_format_minutes) {
        $zero = new DateTime('@0');
        $offset = new DateTime('@' . $minutes * 60);
        $diff = $zero->diff($offset);
        if($minutes < 60) return $diff->format($time_format_minutes);
        if($minutes < 1440) return $diff->format($time_format_hours);
        return $diff->format($time_format_days);
    }
    function formatHigh($stat, $options) {
        if($stat >= 1.0E24) return round(($score / 1.0E24), 0) . $options["septillions_format"];
        if($stat >= 1.0E21) return round(($stat / 1.0E21), 0) . $options["sextillions_format"];
        if($stat >= 1.0E18) return round(($stat / 1.0E18), 0) . $options["quintillions_format"];
        if($stat >= 1.0E15) return round(($stat / 1.0E15), 0) . $options["quadrillions_format"];
        if($stat >= 1.0E12) return round(($stat / 1.0E12), 0) . $options["trillions_format"];
        if($stat >= 1.0E9) return round(($stat / 1.0E9), 0) . $options["billions_format"];
        if($stat >= 1.0E6) return round(($stat / 1.0E6), 0) . $options["millions_format"];
        if($stat >= 1.0E3) return round(($stat / 1.0E3), 0) . $options["thousands_format"];
        return round($stat, 0);
    }
    function clean($type) {
        return str_replace('-', '_', $type);
    }
    function getStats($connection, $selected_time, $selected_column, $columns, $under_limit, $count) {
        $results = array();
        switch($selected_time) {
            case 'alltime': {
                $query = "SELECT name, ";
                foreach($columns as $column) {
                    $query .= $column["alias"] . ".stat_value, ";
                }
                $query = substr($query, 0, -2);
                $query .= " FROM leaderheadsplayers p ";
                foreach($columns as $column) {
                    $type = $column["type"];
                    $table = $column["table"];
                    $clean_type = $column["alias"];
                    $query .= "LEFT JOIN $table $clean_type ON $clean_type.player_id = p.player_id AND $clean_type.stat_type='$type' ";
                }
                $query .= "ORDER by " . $selected_column["alias"] . ".stat_value DESC LIMIT $under_limit, $count";
                $result_set = $connection->query($query) or trigger_error($connection->error."[$query]");
                if($result_set) {
                    while($row = $result_set->fetch_array()) {
                        $stats = array();
                        for($x = 1; $x <= count($columns); $x++) {
                            $stat = $row[$x];
                            if($stat == NULL) $stat = 0;
                            $stats[$x - 1] = $stat;
                        }
                        $results[$row[0]] = $stats; 
                    }
                    return $results;
                }
                break;
            }
            case 'monthly': {
                $current_month = date("n");
                $previous_month = $current_month == 1 ? 10 : $current_month - 1;
                $current_year = date("Y");
                $previous_year = $current_month == 1 ? $current_year - 1 : $current_year;
                $query = "SELECT name, ";
                foreach($columns as $column) {
                    $clean_type = $column["alias"];
                    $query .= "($clean_type" . "_new.stat_value - $clean_type" . "_old.stat_value)". ($column == $selected_column ? " AS difference" : "")  . ", ";
                }
                $query = substr($query, 0, -2);
                $query .= " FROM leaderheadsplayers p ";
                foreach($columns as $column) {
                    $type = $column["type"];
                    $table = $column["table"];
                    $clean_type = $column["alias"];
                    $query .= "LEFT JOIN $table $clean_type" . "_new ON $clean_type" . "_new.player_id = p.player_id AND $clean_type" . "_new.stat_type='$type' AND $clean_type" . "_new.month = $current_month AND $clean_type" . "_new.year = $current_year ";
                    $query .= "LEFT JOIN $table $clean_type" . "_old ON $clean_type" . "_old.player_id = p.player_id AND $clean_type" . "_old.stat_type='$type' AND $clean_type" . "_old.month = $previous_month AND $clean_type" . "_old.year = $previous_year ";
                }
                $query .= "ORDER by difference DESC LIMIT $under_limit, $count";
                $result_set = $connection->query($query) or trigger_error($connection->error."[$query]");
                if($result_set) {
                    while($row = $result_set->fetch_array()) {
                        $stats = array();
                        for($x = 1; $x <= count($columns); $x++) {
                            $stat = $row[$x];
                            if($stat == NULL) $stat = 0;
                            $stats[$x - 1] = $stat;
                        }
                        $results[$row[0]] = $stats; 
                    }
                    return $results;
                }
                break;
            }
            case 'weekly': {
                $current_week = date("W");
                $previous_week = date("W", strtotime(date("Y-m-d", strtotime("-1 week"))));
                $current_year = date("Y");
                $previous_year = date("Y", strtotime(date("Y-m-d", strtotime("-1 week"))));
                $query = "SELECT name, ";
                foreach($columns as $column) {
                    $clean_type = $column["alias"];
                    $query .= "($clean_type" . "_new.stat_value - $clean_type" . "_old.stat_value)". ($column == $selected_column ? " AS difference" : "")  . ", ";
                }
                $query = substr($query, 0, -2);
                $query .= " FROM leaderheadsplayers p ";
                foreach($columns as $column) {
                    $type = $column["type"];
                    $table = $column["table"];
                    $clean_type = $column["alias"];
                    $query .= "LEFT JOIN $table $clean_type" . "_new ON $clean_type" . "_new.player_id = p.player_id AND $clean_type" . "_new.stat_type='$type' AND $clean_type" . "_new.week = $current_week AND $clean_type" . "_new.year = $current_year ";
                    $query .= "LEFT JOIN $table $clean_type" . "_old ON $clean_type" . "_old.player_id = p.player_id AND $clean_type" . "_old.stat_type='$type' AND $clean_type" . "_old.week = $previous_week AND $clean_type" . "_old.year = $previous_year ";
                }
                $query .= "ORDER by difference DESC LIMIT $under_limit, $count";
                $result_set = $connection->query($query) or trigger_error($connection->error."[$query]");
                if($result_set) {
                    while($row = $result_set->fetch_array()) {
                        $stats = array();
                        for($x = 1; $x <= count($columns); $x++) {
                             $stat = $row[$x];
                            if($stat == NULL) $stat = 0;
                            $stats[$x - 1] = $stat;
                        }
                        $results[$row[0]] = $stats; 
                    }
                    return $results;
                }
                break;
            }
            case 'daily': {
                $current_day = date("N");
                $previous_day = $current_day == 1 ? 7 : $current_day - 1;
                $query = "SELECT name, ";
                foreach($columns as $column) {
                    $clean_type = $column["alias"];
                    $query .= "($clean_type" . "_new.stat_value - $clean_type" . "_old.stat_value)". ($column == $selected_column ? " AS difference" : "")  . ", ";
                }
                $query = substr($query, 0, -2);
                $query .= " FROM leaderheadsplayers p ";
                foreach($columns as $column) {
                    $type = $column["type"];
                    $table = $column["table"];
                    $clean_type = $column["alias"];
                    $query .= "LEFT JOIN $table $clean_type" . "_new ON $clean_type" . "_new.player_id = p.player_id AND $clean_type" . "_new.stat_type='$type' AND $clean_type" . "_new.day = $current_day ";
                    $query .= "LEFT JOIN $table $clean_type" . "_old ON $clean_type" . "_old.player_id = p.player_id AND $clean_type" . "_old.stat_type='$type' AND $clean_type" . "_old.day = $previous_day ";
                }
                $query .= "ORDER by difference DESC LIMIT $under_limit, $count";
                $result_set = $connection->query($query) or trigger_error($connection->error."[$query]");
                if($result_set) {
                    while($row = $result_set->fetch_array()) {
                        $stats = array();
                        for($x = 1; $x <= count($columns); $x++) {
                            $stat = $row[$x];
                            if($stat == NULL) $stat = 0;
                            $stats[$x - 1] = $stat;
                        }
                        $results[$row[0]] = $stats; 
                    }
                    return $results;
                }
                break;
            }
        }      
    }
?>
<style>
    @font-face {
        font-family: 'Lato';
        font-style: normal;
        font-weight: 400;
        src: local('Lato Regular'), local('Lato-Regular'), url(https://fonts.gstatic.com/s/lato/v11/UyBMtLsHKBKXelqf4x7VRQ.woff2) format('woff2');
        unicode-range: "U+0100-024F, U+1E00-1EFF, U+20A0-20AB, U+20AD-20CF, U+2C60-2C7F, U+A720-A7FF";
    }
    @font-face {
      font-family: 'Lato';
      font-style: normal;
      font-weight: 400;
      src: local('Lato Regular'), local('Lato-Regular'), url(https://fonts.gstatic.com/s/lato/v11/1YwB1sO8YE1Lyjf12WNiUA.woff2) format('woff2');
      unicode-range: "U+0000-00FF, U+0131, U+0152-0153, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2212, U+2215, U+E0FF, U+EFFD, U+F000";
    }
</style>