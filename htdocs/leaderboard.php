<?php
    // --------------------------------------------------------------------------------------------------------------------- //
    // These are the only settings in the file. You need to set up the correct URL's for the linked files.
    // These values will be correct by default. 

    define("leaderboard_config", "config/leaderboard_config.yml");
    define("core", "core.php");
    define("stylesheet", "stylesheets/leaderboard_style.css");
    define("bootstrap", "stylesheets/bootstrap.css");
    define("jquery", "libs/jquery.js");
    define("jquery_ui", "libs/jquery_ui.js");
    define("jquery_ui_stylesheet", "stylesheets/jquery_ui.css");
    define("spyc", "libs/spyc.php");
    define("resize_send", "libs/resize_send.js");
    
    // Do not edit anything under this comment if you don't know what you're doing.
    // --------------------------------------------------------------------------------------------------------------------- //
?>
<?php
    ini_set('display_errors', 1);
    if(!file_exists(constant('leaderboard_config'))) {
        echo "Could not find config file: " . constant('leaderboard_config') . "<br>";
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
    if(!file_exists(constant('resize_send'))) {
        echo "Could not find the resize_send file: " . constant('resize_send') . "<br>";
        echo "Please check your settings";
        return;
    }
    $leaderboard_config = Spyc::YAMLLoad(constant('leaderboard_config'));
    $description = $leaderboard_config["description"];
?>
<?php
    function getSetting($setting, $search) {
        foreach($search as $section) 
            if(array_key_exists($setting, $section))
                return $section[$setting];
    }
?>
<!DOCTYPE HTML>
<html>
    <meta name="viewport" content="user-scalable=no, width=device-width, initial-scale=1.0" />
    <head>
        <meta http-equiv="Cache-control" content="public">
        <meta name="description" content=<?=$description?>>
        <meta charset="UTF-8">
        <title><?=$leaderboard_config['page_title']?></title>
        <style><?php include constant('bootstrap'); ?></style>
        <style><?php include constant('stylesheet'); ?></style>
        <style><?php include constant('jquery_ui_stylesheet'); ?></style>
        <script type="text/javascript"><?php include constant('jquery'); ?></script>
        <script type="text/javascript"><?php include constant('jquery_ui'); ?></script>
        <script type="text/javascript"><?php include constant('resize_send'); ?></script>
    </head>
    <body>
        <?php
        if(1 == 2) {
            echo "<div class='container'><div class='flash' style='margin-top: 40px;'><div class='alert alert-danger' id='shutdown-alert'>
            Could not contact the database. Please contact a site administrator.
            </a></div></div>";
        }
        $global_settings = $leaderboard_config['settings'];
        $messages = $leaderboard_config['messages'];
        $high_formats = $messages['high_formats'];
        if($global_settings['enable_page_header']) {?>
             <div class="row-fluid">
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
                                        <form class='search-form form form-inline' url=<?=$global_settings['global_search_bar_url']?>>
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
        <div class='container-pack'>
            <?php
            foreach($leaderboard_config['leaderboards'] as $leaderboard => $properties) {
            $lowercase_leaderboard_name = preg_replace('/\s+/', '', strtolower($leaderboard));
            $lowercase_leaderboard_name = preg_replace("/[^A-Za-z0-9 ]/", '', $lowercase_leaderboard_name);
            $selected_column = NULL;
            $selected_time = NULL;
            $columns = $properties['columns'];
            $settings = array_key_exists('settings', $properties) ? $properties['settings'] : array();
            if(is_string($settings)) $settings = array();
            $defaults = $leaderboard_config['defaults'];
            $table_width = array_key_exists('table_width', $settings) ? $settings['table_width'] : $defaults['table_width'];
            $enable_column_select = array_key_exists('enable_column_select', $settings) ? $settings['enable_column_select'] : $defaults['enable_column_select'];
            $enable_header = array_key_exists('enable_header', $settings) ? $settings['enable_header'] : $defaults['enable_header'];
            $enable_caption = array_key_exists('enable_caption', $settings) ? $settings['enable_caption'] : $defaults['enable_caption'];
            $enable_caption_custom_text = array_key_exists('enable_caption_custom_text', $settings) ? $settings['enable_caption_custom_text'] : $defaults['enable_caption_custom_text'];
            $caption_custom_text = array_key_exists('caption_custom_text', $settings) ? $settings['caption_custom_text'] : $defaults['caption_custom_text'];
            $enable_select = array_key_exists('enable_select', $settings) ? $settings['enable_select'] : $defaults['enable_select'];
            $search_button_text = array_key_exists('search_bar_button_text', $settings) ? $settings['search_bar_button_text'] : $defaults['search_bar_button_text'];
            $enable_search_bar = array_key_exists('enable_search_bar', $settings) ? $settings['enable_search_bar'] : $defaults['enable_search_bar'];
            $search_bar_url = array_key_exists('search_bar_url', $settings) ? $settings['search_bar_url'] : $defaults['search_bar_url'];
            $enable_index_column = array_key_exists('enable_index_column', $settings) ? $settings['enable_index_column'] : $defaults['enable_index_column'];
            $index_column_width = array_key_exists('index_column_width', $settings) ? $settings['index_column_width'] : $defaults['index_column_width'];
            $player_column_width = array_key_exists('player_column_width', $settings) ? $settings['player_column_width'] : $defaults['player_column_width'];
            $index_column_text = array_key_exists('index_column_text', $settings) ? $settings['index_column_text'] : $defaults['index_column_text'];
            $player_column_text = array_key_exists('player_column_text', $settings) ? $settings['player_column_text'] : $defaults['player_column_text'];
            $search_button_placeholder = array_key_exists('search_bar_button_placeholder', $settings) ? $settings['search_bar_button_placeholder'] : $defaults['search_bar_button_placeholder'];
            $available_time_types = array_key_exists('available_time_types', $settings) ? $settings['available_time_types'] : $defaults['available_time_types'];
            $pagination_size = array_key_exists('pagination_size', $settings) ? $settings['pagination_size'] : $defaults['pagination_size'];
            $enable_upper_pagination = array_key_exists('enable_upper_pagination', $settings) ? $settings['enable_upper_pagination'] : $defaults['enable_upper_pagination'];
            $enable_lower_pagination = array_key_exists('enable_lower_pagination', $settings) ? $settings['enable_lower_pagination'] : $defaults['enable_lower_pagination'];
            $search_bar = "<div class='form-inline pull-right'><form class='search-form form form-inline' url='$search_bar_url'>";
            $search_bar .= "<div class='input-group'><input maxlength='16' required class='search-field form-control input-sm player-search-field' placeholder='" . $search_button_placeholder . "' type='text'>";
            $search_bar .= "<div class='input-group-btn'><button type='submit' class='search-button btn btn-sm btn-primary'>" . $search_button_text . "</button></div></div></form></div>";
            if(isset($_GET[$lowercase_leaderboard_name . "-time"])) {
                $input_time = $_GET[$lowercase_leaderboard_name . "-time"];
                foreach($available_time_types as $time_type) {
                    if($input_time == $time_type) {
                        $selected_time = $input_time;
                        break;
                    }
                }
            }
            $default_selected_time = current($available_time_types);
            if($selected_time == NULL)  $selected_time = $default_selected_time;
            $current_page = 1;
            if(isset($_GET[$lowercase_leaderboard_name . "-page"])) {
                $current_page = $_GET[$lowercase_leaderboard_name . "-page"];
                 if(!ctype_digit($current_page)) $current_page = 1;
            }
            $column_data_collection = array();
            foreach($columns as $title => $column) {
                $column_data = array();
                $sections = array($column, $leaderboard_config, $defaults["columns"]);
                $settings_query = array("width", "tables", "type", "time_type", "statistic_type", "decimals", "format_3_digits", "format_high_numbers", "keep_time_type", "format");
                foreach($settings_query as $settings_query_single) {
                    $column_data[$settings_query_single] = getSetting($settings_query_single, $sections);
                }
                $column_data["title"] = $title;
                array_push($column_data_collection , $column_data);
            }
            if(isset($_GET[$lowercase_leaderboard_name . "-type"])) {
                $selected_type = $_GET[$lowercase_leaderboard_name . "-type"];
                if(!ctype_digit($selected_type) || count($column_data_collection) <= $selected_type) $selected_type = 0;
            } else {
                $selected_type = 0;
            }
            $count = getSetting("count", array($leaderboard_config["settings"], $defaults));
            $settings_query = array("enable_click_name", "skull_url", "click_name_url", "enable_index_column", "index_column_format", "pagination_size");
            $settings = array();
            foreach($settings_query as $setting) {
                $settings[$setting] = getSetting($setting, array($leaderboard_config["settings"], $defaults));
            }
            $settings["high_formats"] = $messages["high_formats"];
            $settings["time_formats"] = $messages["time_formats"];
            ?>
            <div name=<?=$lowercase_leaderboard_name?> settings=<?=base64_encode(json_encode($settings))?> count=<?=$count?> selected_type=<?=$selected_type?> default_selected_time=<?=$default_selected_time?> selected_time=<?=$selected_time?> selected_page=<?=$current_page?> data=<?=base64_encode(json_encode($column_data_collection))?> id="<?=$lowercase_leaderboard_name?>" style='max-width: <?=$table_width?>' class='<?=$lowercase_leaderboard_name?>-container leaderboard-container container'>
            <?php
            if($enable_header) {?>
                <div class='row'>
                    <div class="col-xs-12 col-md-12">
                        <div class='page-header'>
                            <h2><?=$leaderboard?></h2>
                        </div>
                    </div>
                </div>
                <?php }
                if($enable_select || $enable_search_bar) { ?>
                <div class='row'>
                    <div class="col-xs-12 col-md-12">
                        <?php
                        if($enable_search_bar && !$enable_upper_pagination) echo $search_bar; ?>
                        <div class='form-inline'><?php
                            $time_option = "<select class='time-selector form-control input-sm'>";
                            foreach($available_time_types as $time_type) {
                                $time_option .= "<option" . ($selected_time == $time_type ? " selected" : '') . " value='$time_type'>" . $messages[$time_type] . "</option>";
                            }
                            $time_option .= "</select>";
                            $type_option = "<select class='type-selector form-control input-sm'>";                      
                            for($x = 0; $x < count($column_data_collection); $x++) {
                                $column = $column_data_collection[$x];
                                $type_option .=  "<option " . ($selected_type == $x ? "selected " : "") . "value=$x>" . strtolower($column['title']) . "</option>";
                            }
                            $type_option .= "</select>";
                            $text = str_replace('{time}', $time_option, array_key_exists('text', $settings) ? $settings['select_text'] :  $defaults['select_text']);
                            $text = str_replace('{type}', $type_option, $text);
                            if($enable_select) echo "<label>" . $text . "</label>";
                            ?>    
                        </div>
                    </div>
                </div>
                <?php } ?>
                <div class='row'>
                    <div class="col-xs-12 col-md-12">
                        <?php
                        $pagination_active_index = min($current_page, round($pagination_size / 2, 0, PHP_ROUND_HALF_DOWN));
                        $pagination = "<ul class='page-selector pagination'>";
                        if($current_page == 1) {
                            $pagination .= "<li class=disabled><a value=previous>&laquo;</a></li>";
                        } else {
                            $pagination .= "<li><a value=previous>&laquo;</a></li>";
                        }
                        for($x = $pagination_active_index - 1; $x > 0; $x--) {
                            $pagination .= "<li><a value=" . ($current_page - $x) . ">" . ($current_page - $x) . "</a></li>";                          
                        }
                        $pagination .= "<li class='active'><a value=$current_page>$current_page</a></li>";
                        for($x = 1; $x <= ($pagination_size - $pagination_active_index); $x++) {
                            $pagination .= "<li><a value=" . ($current_page + $x) . ">" . ($current_page + $x) . "</a></li>";
                        }
                        $pagination .=  "<li><a value=next>&raquo;</a></li></ul>";
                        if($enable_upper_pagination) echo $pagination;
                        if($enable_search_bar && $enable_upper_pagination) {
                            echo $search_bar;
                        }
                        ?>
                        <div class="table-container">
                            <table class='<?=$lowercase_leaderboard_name?>-table table-leaderboard table table-bordered table-striped'>
                                <thead>
                                    <tr>
                                        <?php if($enable_index_column) echo "<th width=" . $index_column_width  . ">" . $index_column_text . "</th>";
                                        echo "<th width=" . $player_column_width . ">" . $player_column_text . "</th>";
                                        foreach($column_data_collection as $column) {
                                            $width = $column["width"];
                                            if($enable_column_select) {
                                                echo "<th width=$width><a class='" . $lowercase_leaderboard_name . "-type-selector type-selector' value=" . $column['url'] . ">" . $column["title"] . "</a></th>";    
                                            } else {
                                                echo "<th width=$width>" . $column["title"] . "</th>";    
                                            }
                                        }
                                        ?>
                                     </tr>
                                </thead>
                                <tbody>
                                <?php if($enable_caption) echo "<caption><h2>" . ($enable_caption_custom_text ? $caption_custom_text : $leaderboard) . "</h2></caption>";
                                $i = 1;?>
                                </tbody>
                            </table>
                        </div>
                        <?php
                        if($enable_lower_pagination) {
                            echo $pagination;
                        }
                        ?>
                    </div>
                </div>
            </div>
            <?php
            }
            ?>
        </div>
    </body>
    <script type="text/javascript">
        function updateLeaderboard(id, variables) {
            var leaderboard = $("#" + id);
            var properties = JSON.parse(atob(leaderboard.attr("data")));
            var selected_time = leaderboard.attr("selected_time");
            var selected_type = leaderboard.attr("selected_type");
            var settings = JSON.parse(atob(leaderboard.attr("settings")));
            var tables = [];
            var tableElement = leaderboard.find("table.table-leaderboard");
            var filterVal = "blur(2px)";
            tableElement.css('filter',filterVal).css('webkitFilter',filterVal).css('mozFilter',filterVal).css('oFilter',filterVal).css('msFilter',filterVal);
            for(var x in properties) {
                property = properties[x];
                var local_time = (property["keep_time_type"] ? property["time_type"] : selected_time);
                var table = {time_type: local_time, table:property["tables"][local_time], type:property["type"]};
                if(x === selected_type) table.order = true;
                tables.push(table);
           }
            if(variables) updateVariables(leaderboard);
            var jsonRequest = $.getJSON(window.location.href.split('?')[0], {
                query: 'leaderboard',
                page: leaderboard.attr("selected_page"),
                count: leaderboard.attr("count"),
                tables: JSON.stringify(tables),
            }, function(data) {
                var results = JSON.parse(data);
                var body = leaderboard.find(".table-leaderboard tbody");        
                body.html("");
                if(results) {
                    for(var r = 0; r < results.length; r++) {
                        var row = "<tr>";
                        if(settings["enable_index_column"]) row += ("<td>" + settings["index_column_format"].replace("{rank}", r + 1 + (leaderboard.attr("selected_page") - 1) * leaderboard.attr("count")) + "</td>");                         
                        for(var c = 1; c <= tables.length + 1; c++) {
                            var result_value = results[r][c - 1];
                            row += "<td>";
                            if(c === 1) {
                                row += "<img class='skull' src="  + settings["skull_url"].replace("{name}", result_value) + " alt='" + result_value + "' title='" + result_value + "'/>";
                                if(settings["enable_click_name"]) {
                                    row += "<a href='" + settings["click_name_url"].replace("{name}", result_value) + "'>" + result_value + "</a></td>";
                                } else {
                                   row += result_value;
                                }
                            } else {
                                var format = properties[c - 2]["format"];
                                if(result_value === null) result_value = 0;
                                result_value = parseFloat(result_value);
                                switch(properties[c - 2]["statistic_type"]) {
                                    case "time": {
                                        row += format.replace("{amount}", formatTime(result_value, settings["time_formats"]));
                                        break;
                                    }
                                    default: {
                                        result_value = parseFloat(result_value).toFixed(properties[c - 2]["decimals"]);
                                        if(properties[c - 2]["format_3_digits"]) {
                                            result_value = result_value.toLocaleString();
                                        } else {
                                            if(properties[c - 2]["format_high_numbers"]) {
                                                result_value = formatHighNumbers(result_value, settings["high_formats"]);
                                            }
                                        }
                                        row += format.replace("{amount}", result_value);
                                        break;
                                    }
                                }
                            }
                            row += "</td>";
                        }
                        row += "</tr>";
                        body.append(row);
                    }
                }
            }).always(function() {
                var filterVal = "initial";
                tableElement.css('filter',filterVal).css('webkitFilter',filterVal).css('mozFilter',filterVal).css('oFilter',filterVal).css('msFilter',filterVal);
            });
        }
        function updateVariables() {
            var query = [];
            $("div.leaderboard-container").each(function() {
                var leaderboard = $(this);
                var name = leaderboard.attr("name");
                if(leaderboard.attr("selected_page") != 1) query.push(name + "-page=" + leaderboard.attr("selected_page"));
                if(leaderboard.attr("selected_time") != leaderboard.attr("default_selected_time")) query.push(name + "-time=" + leaderboard.attr("selected_time"));
                if(leaderboard.attr("selected_type") != 0) query.push(name + "-type=" + leaderboard.attr("selected_type"));
            });
            window.history.pushState(null, null, window.location.href.split('?')[0] + (query.length === 0 ? "" : ("?" + query.join("&"))));
        }
        function formatHighNumbers(input, formats) {
            if(input >= 1.0E24) return Math.round(input / 1.0E24) + formats["septillions_format"];
            if(input >= 1.0E21) return Math.round(input / 1.0E21) + formats["sextillions_format"];
            if(input >= 1.0E18) return Math.round(input / 1.0E18) + formats["quintillions_format"];
            if(input >= 1.0E15) return Math.round(input / 1.0E15) + formats["quadrillions_format"];
            if(input >= 1.0E12) return Math.round(input / 1.0E12) + formats["trillions_format"];
            if(input >= 1.0E9) return Math.round(input / 1.0E9) + formats["billions_format"];
            if(input >= 1.0E6) return Math.round(input / 1.0E6) + formats["millions_format"];
            if(input >= 1.0E3) return Math.round(input / 1.0E3) + formats["thousands_format"];
            return Math.round(input);
        }
        function formatTime(input, formats) {
            if(input < 60) return formats["time_format_minutes"].replace("{minutes}", input);
            if(input < 1440) return formats["time_format_hours"].replace("{minutes}", input % 60).replace("{hours}", Math.floor(input / 60));
            return formats["time_format_days"].replace("{minutes}", input % 60).replace("{hours}",  Math.floor((input % 1440) / 60)).replace("{days}", Math.floor(input / 1440));
        }
        function replaceParameters(url, paramName, paramValue) {
            url = url.replace(window.location.hash , '');
            var pattern = new RegExp('\\b(' + paramName + '=).*?(&|$)');
            if(url.search(pattern) >= 0) {
                return url.replace(pattern,'$1' + paramValue + '$2') + window.location.hash;
            }
            return url + (url.indexOf('?') > 0 ? '&' : '?') + paramName + '=' + paramValue + window.location.hash;
        }
        function replaceAll(str, find, replace) {
            return str.replace(new RegExp(find, 'g'), replace);
        }
        $(function() {
            $("form.search-form").on("submit", function(e) {
                e.preventDefault();
                window.location.href = $(this).attr("url").replace("{name}", $(this).find("input.search-field").val());
            });    
            $("select.type-selector").on("change", function() {
                var leaderboard =  $(this).closest(".leaderboard-container");
                leaderboard.attr("selected_type", $(this).find("option:selected").index());
                leaderboard.attr("selected_page", 1);
                changePage(leaderboard, 1);
                updateLeaderboard(leaderboard.attr("id"), true);
            });
            $("select.time-selector").on("change", function() {
                var leaderboard =  $(this).closest(".leaderboard-container");
                changePage(leaderboard, 1);
                leaderboard.attr("selected_time", $(this).find("option:selected").attr("value"));
                updateLeaderboard(leaderboard.attr("id"), true);
            });
            $("ul.page-selector li a").on("click", function() {
                if($(this).closest("li").hasClass("disabled")) return;
                var leaderboard = $(this).closest(".leaderboard-container");
                changePage(leaderboard, $(this).attr("value"));
                updateLeaderboard(leaderboard.attr("id"), true);
            });
            function changePage(leaderboard, clicked_index) {
                var page_selector = leaderboard.find(".page-selector");
                var list = page_selector.find("li");
                switch(clicked_index) {
                    case "previous": {
                        clicked_index = parseInt(page_selector.find("li.active a").attr("value")) - 1;
                        break;
                    }
                    case "next": {
                        clicked_index = parseInt(page_selector.find("li.active a").attr("value")) + 1;
                        break;
                    }
                    default: {
                        clicked_index = parseInt(clicked_index);
                        break;
                    }
                }
                list.removeClass("active");
                leaderboard.attr("selected_page", clicked_index);
                var settings = JSON.parse(atob(leaderboard.attr("settings")));
                var pagination_size = settings["pagination_size"];
                var active_index = Math.floor(pagination_size / 2);
                if(clicked_index > active_index) {
                    for(var x = 0; x < pagination_size; x++) {
                        list.eq(x + 1).find("a").html(x +(clicked_index - active_index) + 1).attr("value", x +(clicked_index - active_index) + 1);
                        list.eq(x + 3 + pagination_size).find("a").html(x +(clicked_index - active_index) + 1).attr("value", x +(clicked_index - active_index) + 1);
                    }
                    list.eq(active_index).addClass("active");
                    list.eq(active_index + 2 + pagination_size).addClass("active");
                } else {
                    for(var x = 0; x < pagination_size; x++) {
                        list.eq(x + 1).find("a").html(x + 1).attr("value", x + 1);
                        list.eq(x + 3 + pagination_size).find("a").html(x + 1).attr("value", x + 1);
                    }
                    list.eq(clicked_index).addClass("active");
                    list.eq(clicked_index + 2 + pagination_size).addClass("active");
                }
                if(clicked_index !== 1) {
                    list.eq(0).removeClass("disabled");
                    list.eq(2 + pagination_size).removeClass("disabled");
                } else {
                    list.eq(0).addClass("disabled");
                    list.eq(2 + pagination_size).addClass("disabled");
                }
            }
            $(".leaderboard-container").each(function() {
                updateLeaderboard($(this).attr('id'), false);
            });
            var anchor = window.location.hash;
            if(anchor.length > 0) {
                scrollTop: $(anchor).offset().top - ($(window).height() - $(anchor).outerHeight(true)) / 2;
            }
            $(".player-search-field").autocomplete({
                source: function(request, response) {
                    $.getJSON(window.location.href.split('?')[0], {
                        query: 'name',
                        value: request.term
                    }, function(data) {
                        response(data.slice(0, 10));
                    });
                },
                minLength: 2,
                autoFocus: true
            });
        });
    </script>    
</html>