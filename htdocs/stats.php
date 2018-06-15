<?php
    // --------------------------------------------------------------------------------------------------------------------- //
    // These are the only settings in the file. You need to set up the correct URL's for the linked files.
    // These values will be correct by default.
	
    define('stats_config', 'config/stats_config.yml');
    define('core', 'core.php');
    define('stylesheet', 'stylesheets/stats_style.css');
    define('bootstrap', 'stylesheets/bootstrap.css');
    define('jquery', 'libs/jquery.js');
    define('jquery_ui', 'libs/jquery_ui.js');
    define('jquery_ui_stylesheet', 'stylesheets/jquery_ui.css');
    define('spyc', 'libs/spyc.php');

    // Do not edit anything under this comment if you don't know what you're doing.
    // --------------------------------------------------------------------------------------------------------------------- //
?>
<?php
    ini_set('display_errors', 1);
    //error_reporting(0);
    if(!file_exists(constant('stats_config'))) {
        echo "Could not find config file: " . constant('stats_config') . "<br>";
        echo "Please check your settings";
        return;
    }
    if(!file_exists(constant('stylesheet'))) {
        echo "Could not find stylesheet file: " . constant('stylesheet') . "<br>";
        echo "Please check your settings";
        return;
    }
    if(!file_exists(constant('bootstrap'))) {
        echo "Could not find bootstrap stylesheet file: " . constant('bootstrap') . "<br>";
        echo "Please check your settings";
        return;
    }
    if(!file_exists(constant('jquery'))) {
        echo "Could not find the jquery file: " . constant('jquery') . "<br>";
        echo "Please check your settings";
        return;
    }
    if(!file_exists(constant('jquery_ui'))) {
        echo "Could not find the jquery_ui file: " . constant('jquery_ui') . "<br>";
        echo "Please check your settings";
        return;
    }
    if(!file_exists(constant('jquery_ui_stylesheet'))) {
        echo "Could not find the jquery_ui_stylesheet file: " . constant('jquery_ui_stylesheet') . "<br>";
        echo "Please check your settings";
        return;
    }
    if(!include_once(constant('core'))) {
        echo "Could not find the core file: " . constant('core') . "<br>";
        echo "Please check your settings";
        return;
    }
    if(!require_once(constant('spyc'))) {
        echo "Could not find the spyc file: " . constant('spyc') . "<br>";
        echo "Please check your settings";
        return;
    }
    $stats_config = Spyc::YAMLLoad(constant('stats_config'));
    $user = filter_input(INPUT_GET, "player");
    $user = preg_replace('/[^\w]/', '', $user);
    if(strlen($user) > 16) $user = substr($user, 0, 16);
    $description = $stats_config["description"];
    $core_path = $_SERVER['PHP_SELF'];
    $core_path = substr($core_path, 0, strrpos($core_path, '/') + 1) . constant('core');
    $core_path = "http" . (!empty($_SERVER['HTTPS']) ? "s" : "") . "://" . $_SERVER['SERVER_NAME'] . $core_path;
?>
<!DOCTYPE HTML>
<html>
    <meta name="viewport" content="user-scalable=no, width=device-width, initial-scale=1.0" />
    <head>
        <meta http-equiv="Cache-control" content="public">
        <meta name="description" content=<?=$description?>>
        <meta charset="UTF-8">
        <title><?=str_replace("{name}", $user, $stats_config['page_title'])?></title>
        <style><?php include constant('bootstrap'); ?></style>
        <style><?php include constant('stylesheet'); ?></style>
        <style><?php include constant('jquery_ui_stylesheet'); ?></style>
        <script type="text/javascript"><?php include constant('jquery'); ?></script>
        <script type="text/javascript"><?php include constant('jquery_ui'); ?></script>
        <script type="text/javascript">
            function replaceAll(str, find, replace) {
                return str.replace(new RegExp(find, "g"), replace);
            }
            $(function() {
                var anchor = window.location.hash;
                if(anchor.length > 0) {
                    scrollTop: $(anchor).offset().top - ($(window).height() - $(anchor).outerHeight(true)) / 2;
                }
                $(".player-search-field").autocomplete({
                    source: function(request, response) {
                        $.getJSON("<?=$core_path?>", {
                            term: request.term,
                            query: 'name',
                        }, function(data) {
                            response(data);
                        })
                    },
                    minLength: 2,
                    autoFocus: true
                });
            });
        </script>
    </head>
    <body>
        <?php
        $connection_start_time = microtime(true);
        $connection = getConnection();
        writeConsole("MySQL connection receive time: " . number_format(microtime(true) - $connection_start_time, 4) . " seconds");
        $content_start_time = microtime(true);
        if($connection == NULL) {
            echo "<div class='container'><div class='flash' style='margin-top: 40px;'><div class='alert alert-danger' id='shutdown-alert'>
            Could not contact the database. Please contact a site administrator.
            </a></div></div>";
        }
        $player_info = (empty($user) || $connection == NULL) ? false : getPlayerInfo($connection, $user);
        $found = false;
        if($player_info && $player_info->num_rows > 0) {
            $row = $player_info->fetch_array();
            $uuid = $row[0];
            $id = $row[1];
            $timestamp = date('Y-m-d H:i:s', strtotime($row[2]));
            $found = true;
        }
        $global_settings = $stats_config['settings'];
        $messages = $stats_config['messages'];
        $last_seen_formats = $messages['last_seen'];
        $last_seen_interval = $global_settings["last_seen_interval"];
        $last_seen_message = $messages["last_seen_message"];
        $not_exist = $messages["not_exist"];
        if($found) $difference = date_diff(new DateTime(), new DateTime($timestamp));
        if($found) $last_seen = format_interval($difference, $last_seen_formats, $last_seen_interval);
        $high_formats = $messages["high_formats"];
        $player_picture = $global_settings["player_picture"];
        if($global_settings['enable_page_header']) {?>
             <div class='row-fluid'>
                <div class="col-xs-10 col-xs-offset-1 col-md-10 col-md-offset-1">
                    <div class = "page-header">
                        <div class='row-fluid'>
                            <div class="col-xs-12 col-md-6">
                                <h1><?=$global_settings['page_header_text']?></h1> 
                            </div>
                            <?php 
                            if($global_settings['enable_global_search_bar']) { ?>
                                <div class="col-xs-12 col-md-6">
                                    <div class='header-search-bar form-inline pull-right'>
                                        <form class='form form-inline' onsubmit="window.location.href = replaceAll('<?=$global_settings['global_search_bar_url']?>', '{name}', document.getElementById('search-player-global').value); return false;">
                                            <div class='input-group'>
                                                <input required maxlength="16" class='search-field form-control input-sm player-search-field' id='search-player-global' placeholder='<?=$global_settings['global_search_bar_button_placeholder']?>' type='text'>
                                                <div class='input-group-btn'>
                                                   <button type='submit' class='search-button btn btn-sm btn-primary'><?=$global_settings['global_search_bar_button_text']?></button>
                                                </div>
                                            </div>
                                        </form>
                                     </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
        <div class="container page-content">
            <div class="row">
                <div class="col-xs-12 col-md-2">
                    <img class="player-picture" src="<?=$found ? str_replace("{uuid}", $uuid, str_replace("{name}", $user, $player_picture)) : str_replace("{name}", empty($user) ? "Steve" : $user, $player_picture)?>">
                </div>
                <div class="col-xs-12 col-md-10">
                    <h1><?=$found ? $user : $not_exist?></h1>
                    <div class="last-seen"><?=$found ? str_replace("{time}", $last_seen, $last_seen_message) : $messages["last_seen"]["never_joined"]?></div>
                    <div class='container-pack'>
                        <?php 
                        if($found) {
                            foreach($stats_config['tables'] as $table => $properties) {
                            ?>
                            <?php
                            $lowercase_table_name = preg_replace('/\s+/', '', strtolower($table));
                            $lowercase_table_name = preg_replace("/[^A-Za-z0-9 ]/", '', $lowercase_table_name);
                            $selected_type = NULL;
                            $selected_time = NULL;
                            $rows = $properties['rows'];
                            $settings = array_key_exists('settings', $properties) ? $properties['settings'] : array();
                            if(is_string($settings)) $settings = array();
                            $defaults = $stats_config['defaults'];
                            $table_width = array_key_exists('table_width', $settings) ? $settings['table_width'] : $defaults['table_width'];
                            $enable_header = array_key_exists('enable_header', $settings) ? $settings['enable_header'] : $defaults['enable_header'];
                            $enable_caption = array_key_exists('enable_caption', $settings) ? $settings['enable_caption'] : $defaults['enable_caption'];
                            $enable_caption_custom_text = array_key_exists('enable_caption_custom_text', $settings) ? $settings['enable_caption_custom_text'] : $defaults['enable_caption_custom_text'];
                            $caption_custom_text = array_key_exists('caption_custom_text', $settings) ? $settings['caption_custom_text'] : $defaults['caption_custom_text'];
                            $time_format_days = array_key_exists('time_format_days', $settings) ? $settings['time_format_days'] : $defaults["messages"]['time_format_days'];
                            $time_format_hours = array_key_exists('time_format_hours', $settings) ? $settings['time_format_hours'] : $defaults["messages"]['time_format_hours'];
                            $time_format_minutes = array_key_exists('time_format_minutes', $settings) ? $settings['time_format_minutes'] : $defaults["messages"]['time_format_minutes'];
                            $index_width = array_key_exists('index_width', $settings) ? $settings['index_width'] : $defaults['index_width'];
                            $row_info = array();
                            $index = 0;
                            $type_indexes = array();
                            foreach($rows as $title => $row) {
                                $info = array();
                                $info["title"] = $title;
                                $info["type"] = $row["type"];
                                $type_index = array_key_exists($row["type"], $type_indexes) ? ($type_indexes[$row["type"]] + 1) : 0;
                                $type_indexes[$row["type"]] = $type_index;
                                $info["alias"] = clean($row["type"]) . $type_index;
                                $info["statistic_type"] = array_key_exists("statistic_type", $row) ? $row["statistic_type"] : $defaults["rows"]["statistic_type"];
                                $info["time_type"] = $time_type = array_key_exists("time_type", $row) ? $row["time_type"] : $defaults["rows"]["time_type"];
                                $info["table"] = array_key_exists("tables", $row) && array_key_exists($time_type, $row['tables']) ? $row['tables'][$time_type] : $defaults["rows"]["tables"][$time_type];
                                $info["format"] = array_key_exists('format', $row) ? $row['format'] : $defaults["rows"]['format'];
                                $info["decimals"] = array_key_exists('decimals', $row) ? $row['decimals'] : $defaults["rows"]['decimals'];
                                $info["digits"] = array_key_exists('format_3_digits', $row) ? $row['format_3_digits'] : $defaults["rows"]['format_3_digits'];
                                $info["high_numbers"] = array_key_exists('format_high_numbers', $row) ? $row['format_high_numbers'] : $defaults["rows"]['format_high_numbers'];
                                $info["width"] = array_key_exists('width', $row) ? $row['width'] : $defaults["rows"]['width'];
                                $info["index"] = $index;
                                array_push($row_info, $info);
                                $index++;
                            }    
                            ?>
                            <div id='<?=$lowercase_table_name?>' style='max-width: <?=$table_width?>' class='<?=$lowercase_table_name?>-container server-container container'>
                            <?php
                            if($enable_header) {?>
                                <div class='row'>
                                    <div class="col-xs-12 col-md-12">
                                        <div class='page-header'>
                                            <h2><?=$table?></h2>
                                        </div>
                                    </div>
                                </div>
                                <?php } ?>
                                <div class='row'>
                                    <div class="col-xs-12 col-md-12">
                                        <div class="table-container">
                                            <table class='<?=$lowercase_table_name?>-table table-stats table table-bordered table-striped'>
                                                <tbody>
                                                <?php if($enable_caption) echo "<caption><h2>" . ($enable_caption_custom_text ? $caption_custom_text : $leaderboard) . "</h2></caption>";
                                                $results = $connection == NULL ? array() : getPlayerStats($connection, $id, $row_info);
                                                foreach($row_info as $row) {
                                                    $stat = array_key_exists($row["index"], $results) ? $results[$row["index"]] : 0;
                                                    echo "<tr><td width=" . $index_width  . ">" . $row["title"] . "</td>";
                                                    switch($row["statistic_type"]) {
                                                        case "time": {
                                                            echo "<td width=" . $row["width"]  . ">" . str_replace("{amount}", formatTime($stat, $time_format_days, $time_format_hours, $time_format_minutes), $row["format"]) . "</td>";
                                                            break;
                                                        }
                                                        default: {
                                                            $stat = round($stat, $row["decimals"]);
                                                            if($row["digits"]) {
                                                                $stat = number_format($stat);
                                                            } else {
                                                                if($row["high_numbers"]) {
                                                                    $stat = formatHigh($stat, $high_formats);
                                                                }
                                                            }
                                                            echo "<td>" . str_replace("{amount}", $stat, $row["format"]) . "</td>";
                                                            break;
                                                        }
                                                    }
                                                    echo "</tr>";
                                                 }?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                            }
                        }
                        if($connection != NULL) $connection->close();
                        writeConsole("Content load time: " . number_format(microtime(true) - $content_start_time, 4) . " seconds");
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>