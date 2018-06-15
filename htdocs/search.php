<?php
    // --------------------------------------------------------------------------------------------------------------------- //
    // These are the only settings in the file. You need to set up the correct URL's for the linked files.
    // These values will be correct by default.
	
    define('search_config', 'config/search_config.yml');
    define('core', 'core.php');
    define('stylesheet', 'stylesheets/search_style.css');
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
    error_reporting(0);
    if(!file_exists(constant('search_config'))) {
        echo "Could not find config file: " . constant('search_config') . "<br>";
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
    $stats_config = Spyc::YAMLLoad(constant('search_config'));
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
        <title><?=$stats_config['page_title']?></title>
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
        $global_settings = $stats_config['settings'];
        $search_bar_button_placeholder = $stats_config['search_bar_button_placeholder'];
        $search_bar_button_text = $stats_config['search_bar_button_text'];
        $search_bar_url = $stats_config['search_bar_url'];
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
                                                <input required maxlength="16" class='search-field form-control header-font input-sm player-search-field' id='search-player-global' placeholder='<?=$global_settings['global_search_bar_button_placeholder']?>' type='text'>
                                                <div class='input-group-btn'>
                                                   <button type='submit' class='search-button header-font btn btn-sm btn-primary'><?=$global_settings['global_search_bar_button_text']?></button>
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
                <div class="col-xs-10 col-xs-offset-1 col-md-10 col-md-offset-1">        
                    <form class='main-form form form-inline' onsubmit="window.location.href = replaceAll('<?=$search_bar_url?>', '{name}', document.getElementById('search-player').value); return false;">
                        <div class='input-group'>
                            <input required maxlength="16" class='main-search-field form-control main-font input-sm player-search-field' id='search-player' placeholder='<?=$search_bar_button_placeholder?>' type='text'>
                            <div class='input-group-btn'>
                               <button type='submit' class='main-search-button main-font btn btn-sm btn-primary'><?=$search_bar_button_text?></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </body>
</html>