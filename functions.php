<?php

/**
 * Ping cXense crawler so that the crawler re-indexes the URL of the post
 * @param int|string $post_id Either post ID or an URL
 * @return array|null
 */
function cxense_ping_crawler($post_id) {
    return CxenseAPI::pingCrawler($post_id);
}

/**
 * Output content profiling meta tags (open-graph and cXenseParse)
 * @param string|null $location Override current URL
 * @param bool|string $site_name
 * @param bool|string $desc
 * @param bool|string $title
 */
function cxense_output_meta_tags($location=null, $site_name=false, $desc=false, $title=false) {

    if ( is_singular() || is_single() ) {

        global $post;

        $recs_tags = [];

        // Get organisation prefix
        $org_prefix = cxense_get_opt('cxense_org_prefix') ? cxense_get_opt('cxense_org_prefix') . '-' : '';

        // Set the ID
        $recs_tags['cXenseParse:recs:articleid'] = $post->ID;

        // Set the pagetype
        $recs_tags['cXenseParse:' . $org_prefix . 'pagetype'] = $post->post_type;

        // Set the publish time
        $recs_tags['cXenseParse:recs:publishtime'] = date('c', strtotime($post->post_date));

        foreach($recs_tags as $name => $val) {
            echo '<meta name="'.$name.'" content="'.$val.'" />'.PHP_EOL;
        }
    }
}

/**
 * Outputs a cxense recommend widget (EXPERIMENTAL !!)
 * @param string $id
 * @param int $width
 * @param int $height
 * @param bool $template - Optional, will default to "default-widget.html" located the template directory of this plugin
 * @param bool $resize_content Optional, whether or not the widget should resize it self after loading
 */
function cxense_recommend_widget($id, $width, $height, $template=false, $resize_content=true) {
    static $num_widgets = 1;
    ?>
    <div id="cxense-widget-<?php echo $num_widgets ?>"></div>
    <script>
        var cX = cX || {}; cX.callQueue = cX.callQueue || [];
        cX.callQueue.push(['insertWidget',{
            widgetId: '<?php echo $id ?>',
            insertBeforeElementId: 'cxense-widget-<?php echo $num_widgets ?>',
            renderTemplateUrl: '<?php echo $template ? $template : CXENSE_PLUGIN_URL.'/templates/recommend-widgets/default-widget.html' ?>',
            resizeToContentSize : <?php echo $resize_content ? 'true':'false' ?>,
            width: <?php echo $width ?>,
            height: <?php echo $height ?>
        }]);
    </script>
    <?php
    $num_widgets++;
}

/**
 * Outputs javascript that register a pageview at cXense
 */
function cxense_analytics_script() {
    require __DIR__ . '/analytics-script.php';
}

/**
 * Get a settings option used by this plugin. Will fall back on
 * constant if defined
 * @param string $name
 * @return mixed
 */
function cxense_get_opt($name) {

    $locale = cxense_get_current_locale();

    if($locale !== null) {
        $name .= '_' . $locale;
    }


    if( $opt = get_option($name) ) {
        return $opt;
    } else {
        $name = strtoupper($name);
        return defined($name) ? constant($name) : false;
    }
}

/**
 * Do a search against cXense indexed pages. Will return false in case
 * of an error or a json result if all is fine (will be cached)
 * @param string $query
 * @param array $args
 * @return array|bool
 */
function cxense_search($query, $args) {
    $default_args = array(
        'columns' => 'title,description,body',
        'count' => 10,
        'pagination' => 0,
        'sort' => 'og-article-published-time:desc',
        'cache_ttl' => HOUR_IN_SECONDS,
        'site_id' => cxense_get_opt('cxense_site_id'),
    );

    $args = array_merge($default_args, $args);

    $url = sprintf(
        'http://sitesearch.cxense.com/api/search/%s?p_aq=query(%s:"%s",token-op=and)&p_c=%d&p_s=%d&p_sm=%s',
        $args['site_id'],
        $args['columns'],
        urlencode($query),
        $args['count'],
        $args['pagination'],
        $args['sort']
    );

    $cache_key = md5($url);
    $result = get_transient($cache_key);

    if( !$result ) {
        $response = wp_remote_get($url);
        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            error_log('PHP Warning: Something went wrong trying to get cxense search data: '.$error_message, E_USER_WARNING);
            return false;
        } else {
            $result = @json_decode($response['body'], true);
            if( !$result ) {
                error_log('PHP Warning: Unable to parse json from result ('.json_last_error().')', E_USER_WARNING);
                return false;
            }
            set_transient($cache_key, $result, $args['cache_ttl']);
        }
    }

    return $result;
}

/**
 * Get a list of all settings that this plugin has
 * @return array
 */
function cxense_get_settings() {

    $baseSettings = [
        ['name' => 'cxense_add_analytics_script', 'title' => 'Enable cxense analytics (Script and API call)', 'select' => ['yes' => 'Yes', 'no' => 'No']],
        ['name' => 'cxense_site_id', 'title' => 'Site ID'],
        ['name' => 'cxense_user_name', 'title' => 'User name'],
        ['name' => 'cxense_api_key', 'title' => 'API Key'],
        ['name' => 'cxense_org_prefix', 'title' => 'Organisation prefix'],

        //['name' => 'cxense_recommendable_post_types', 'title' => 'Recommendable post types (comma separated)'],
        //['name'=>'cxense_generate_og_tags', 'title' => 'Generate og-tags', 'select '=> ['yes' => 'Yes', 'no' => 'No']],
        //['name' => 'cxense_default_site_desc', 'title' => 'The default website description used in og:description'],
        //['name' => 'cxense_default_og_image', 'title' => 'URL to default og:image'],
        //['name' => 'cxense_user_products', 'title' => 'Paywall user products (comma separated string)'],
        //['name' => 'cxense_widgets_options', 'title' => 'cxense_widgets_options', 'add_field' => false],
    ];


    if (cxense_languages_is_enabled()) { // Languages are enabled we must alter settings keys to include languages

        $localizedSettings = [];

        foreach (cxense_get_languages() as $language) {
            foreach ($baseSettings as $baseSetting) { // Append locale to base settings
                $baseSetting['name'] .= '_' . $language->locale;
                $baseSetting['title'] .= ' ' . $language->locale;
                $localizedSettings[] = $baseSetting;
            }
        }

        return $localizedSettings;

    } else {
        return $baseSettings;
    }
}

/**
 * Register all settings used by this plugin
 */
function cxense_register_settings() {

    // Setup section on our options page
    add_settings_section('cxense-settings-section', 'cXense Settings', '__return_empty_string', 'cxense-settings');

    // Register our settings and create
    foreach(cxense_get_settings() as $setting) {

        // Register setting
        register_setting('cxense-settings', $setting['name']);

        // Add settings field if add_field isn't false
        if( !isset($setting['add_field']) || $setting['add_field'] !== false) {
            add_settings_field(
                $setting['name'],
                $setting['title'],
                function($args) {
                    $value = cxense_get_opt($args['name']);
                    if( !empty($args['select']) ) {
                        echo '<select name="'.$args['name'].'">';
                        foreach($args['select'] as $opt_val => $opt_name) {
                            echo '<option value="'.$opt_val.'"'.($opt_val == $value ? ' selected="selected"':'').'>'.$opt_name.'</option>';
                        }
                        echo '</select>';
                    } else {
                        echo '<input type="text" name="'.$args['name'].'" value="'.$value.'" />';
                    }
                },
                'cxense-settings',
                'cxense-settings-section',
                $setting
            );
        }
    }
}

/**
 * Remove all settings that might have been
 * saved to the database by the plugin
 */
function cxense_remove_all_settings() {
    foreach(cxense_get_settings() as $setting)
        delete_option($setting['name']);
}


/**
 * Check if languages are enabled
 *
 * @return bool
 */
function cxense_languages_is_enabled()
{
    return function_exists('Pll') && PLL()->model->get_languages_list();
}

/**
 * Get all available languages
 *
 * @return bool
 */
function cxense_get_languages()
{
    if (cxense_languages_is_enabled()) {
        return PLL()->model->get_languages_list();
    }
    return false;
}

/**
 * Get the current language by looking at the current HTTP_HOST
 *
 * @return null|PLL_Language
 */
function cxense_get_current_language()
{
    if (cxense_languages_is_enabled()) {
        return PLL()->model->get_language(pll_current_language());
    }
    return null;
}

/**
 *  Returns the current locale string or null if languages are not enabled
 *
 * @return null|string
 */
function cxense_get_current_locale() {
    $currentLang = cxense_get_current_language();
    return $currentLang ? $currentLang->locale : null;
}



