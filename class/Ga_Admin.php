<?php

class Ga_Admin
{

    //stores the selected account id
    const GA_VERSION_OPTION_NAME = 'googleanalytics_version';
    const GA_OAUTH_AUTH_CODE_OPTION_NAME = 'googleanalytics_oauth_auth_code';
    //stores the access token and the refresh token
    const GA_OAUTH_AUTH_TOKEN_OPTION_NAME = 'googleanalytics_oauth_auth_token';
    //manually not used'
    const MIN_WP_VERSION = '3.8';
    const NOTICE_SUCCESS = 'success';
    const NOTICE_WARNING = 'warning';
    const NOTICE_ERROR = 'error';
    const GA_HEARTBEAT_API_CACHE_UPDATE = false;

    const GA_ACCOUNT_AND_DATA_ARRAY = 'googleanalytics_accounts_and_data';
    const GA_SELECTED_VIEWS = 'googleanalytics_selected_views';

    /**
     * Instantiate API client.
     *
     * @return Ga_Lib_Google_Api_Client|null
     */
    public static function api_client($type = '')
    {
        $instance = Ga_Lib_Google_Api_Client::get_instance();
        return $instance;
    }

    /*
     * Initializes plugin's options during plugin activation process.
     */

    public static function activate_googleanalytics()
    {
        add_option(self::GA_ACCOUNT_AND_DATA_ARRAY, wp_json_encode(array()));
        add_option(self::GA_SELECTED_VIEWS, wp_json_encode(array()));
        add_option(self::GA_VERSION_OPTION_NAME);
        add_option(self::GA_OAUTH_AUTH_CODE_OPTION_NAME);
        add_option(self::GA_OAUTH_AUTH_TOKEN_OPTION_NAME);
        Ga_Cache::add_cache_options();
    }

    /*
     * Deletes plugin's options during plugin activation process.
     */

    public static function deactivate_googleanalytics()
    {
        delete_option(self::GA_ACCOUNT_AND_DATA_ARRAY);
        delete_option(self::GA_SELECTED_VIEWS);
        delete_option(self::GA_OAUTH_AUTH_CODE_OPTION_NAME);
        delete_option(self::GA_OAUTH_AUTH_TOKEN_OPTION_NAME);
        Ga_Cache::delete_cache_options();
    }

    /**
     * Deletes plugin's options during plugin uninstallation process.
     */
    public static function uninstall_googleanalytics()
    {
        delete_option(self::GA_VERSION_OPTION_NAME);
    }

    /**
     * Do actions during plugin load.
     */
    public static function loaded_googleanalytics()
    {
//        self::update_googleanalytics();
    }

//    /**
//     * Update hook fires when plugin is being loaded.
//     */
//    public static function update_googleanalytics()
//    {
//
//        $version = get_option(self::GA_VERSION_OPTION_NAME);
//        $installed_version = get_option(self::GA_VERSION_OPTION_NAME, '1.0.7');
//        $old_property_value = Ga_Helper::get_option('web_property_id');
//        if (version_compare($installed_version, GOOGLEANALYTICS_VERSION, 'eq')) {
//            return;
//        }
//
//        update_option(self::GA_VERSION_OPTION_NAME, GOOGLEANALYTICS_VERSION);
//    }

    public static function preupdate_selected_views($new_value, $old_value)
    {
        $data = json_decode(get_option(self::GA_ACCOUNT_AND_DATA_ARRAY, array()));
        foreach ($data as $account_email => $account){
            foreach($account->account_summaries as $account_summary){
                foreach ($account_summary->webProperties as $property){
                    foreach ($property->profiles as $profile){
                        if (array_key_exists($profile->id, $new_value)){
                            $profile->include_in_stats = true;
                        } else {
                            $profile->include_in_stats = false;
                        }
                    }
                }
            }
        }
        update_option(self::GA_ACCOUNT_AND_DATA_ARRAY, json_encode($data));


        return wp_json_encode($new_value);
    }

    public static function preupdate_exclude_roles($new_value, $old_value)
    {

        print_r($new_value);
        update_option("test", json_encode($new_value));
        if (!Ga_Helper::are_features_enabled()) {
            return '';
        }

        return wp_json_encode($new_value);
    }



    /**
     * Registers plugin's settings.
     */
    public static function admin_init_googleanalytics()
    {
        register_setting(GA_NAME, self::GA_SELECTED_VIEWS);
        register_setting(GA_NAME, self::GA_OAUTH_AUTH_CODE_OPTION_NAME);
        add_filter('pre_update_option_' . Ga_Admin::GA_SELECTED_VIEWS, 'Ga_Admin::preupdate_selected_views', 1, 2);
    }

    /**
     * Builds plugin's menu structure.
     */
    public static function admin_menu_googleanalytics()
    {
        if (current_user_can('manage_options')) {
            add_submenu_page('options-general.php', __( 'Analytics (DT)', 'disciple_tools' ),
            __( 'Analytics (DT)', 'disciple_tools' ), 'manage_options', 'googleanalytics/settings', 'Ga_Admin::options_page_googleanalytics' );
        }
    }


    /**
     * Prepares and displays plugin's settings page.
     */
    public static function options_page_googleanalytics()
    {

        if (!Ga_Helper::is_wp_version_valid() || !Ga_Helper::is_php_version_valid()) {
            return false;
        }

        /**
         * Keeps data to be extracted as variables in the view.
         *
         * @var array $data
         */
        $data = array();
        $data[self::GA_ACCOUNT_AND_DATA_ARRAY] = json_decode(get_option(self::GA_ACCOUNT_AND_DATA_ARRAY, "[]"), true);

        foreach ($data[self::GA_ACCOUNT_AND_DATA_ARRAY] as $account_email => $account){
            if(!Ga_Helper::is_authorized($account['token'])){
                foreach($account['account_summaries'] as $account_summary){
                    $account_summary['reauth'] = true;
//                    foreach ($account_summary->webProperties as $property){
//                        foreach ($property->profiles as $profile){
//                        }
//                    }
                }
            };
        }
        $data['popup_url'] = self::get_auth_popup_url();

        if (!empty($_GET['err'])) {
            switch ($_GET['err']) {
                case 1:
                    $data['error_message'] = Ga_Helper::ga_oauth_notice('There was a problem with Google Oauth2 authentication process.');
                    break;
            }
        }
        Ga_View_Core::load('page', array(
            'data' => $data,
            'tooltip' => ''
        ));

        self::display_api_errors();
    }

    /**
     * Prepares and returns a plugin's URL to be opened in a popup window
     * during Google authentication process.
     *
     * @return mixed
     */
    public static function get_auth_popup_url()
    {
        return admin_url(Ga_Helper::create_url(Ga_Helper::GA_SETTINGS_PAGE_URL, array(Ga_Controller_Core::ACTION_PARAM_NAME => 'ga_action_auth')));
    }


    /**
     * Adds JS scripts for the settings page.
     */
    public static function enqueue_ga_scripts()
    {
        wp_register_script(GA_NAME . '-page-js', GA_PLUGIN_URL . '/js/' . GA_NAME . '_page.js', array(
            'jquery'
        ));
        wp_enqueue_script(GA_NAME . '-page-js');
    }

    /**
     * Adds CSS plugin's scripts.
     */
    public static function enqueue_ga_css()
    {
        wp_register_style(GA_NAME . '-css', GA_PLUGIN_URL . '/css/' . GA_NAME . '.css', false, null, 'all');
        wp_register_style(GA_NAME . '-additional-css', GA_PLUGIN_URL . '/css/ga_additional.css', false, null, 'all');
        wp_enqueue_style(GA_NAME . '-css');
        wp_enqueue_style(GA_NAME . '-additional-css');
        if (Ga_Helper::is_wp_old()) {
            wp_register_style(GA_NAME . '-old-wp-support-css', GA_PLUGIN_URL . '/css/ga_old_wp_support.css', false, null, 'all');
            wp_enqueue_style(GA_NAME . '-old-wp-support-css');
        }
        wp_register_style(GA_NAME . '-modal-css', GA_PLUGIN_URL . '/css/ga_modal.css', false, null, 'all');
        wp_enqueue_style(GA_NAME . '-modal-css');
    }


    /**
     * Enqueues plugin's JS and CSS scripts.
     */
    public static function enqueue_scripts()
    {
        if (Ga_Helper::is_dashboard_page() || Ga_Helper::is_plugin_page()) {
            wp_register_script(GA_NAME . '-js', GA_PLUGIN_URL . '/js/' . GA_NAME . '.js', array(
                'jquery'
            ));
            wp_enqueue_script(GA_NAME . '-js');

            wp_register_script('googlecharts', 'https://www.gstatic.com/charts/loader.js', null, null, false);
            wp_enqueue_script('googlecharts');

            self::enqueue_ga_css();
        }

        if (Ga_Helper::is_plugin_page()) {
            self::enqueue_ga_scripts();
        }
    }


    /**
     * Shows plugin's notice on the admin area.
     */
    public static function admin_notice_googleanalytics()
    {
        if (!empty($_GET['settings-updated']) && Ga_Helper::is_plugin_page()) {
            echo Ga_Helper::ga_wp_notice(_('Settings saved'), self::NOTICE_SUCCESS);
        }
    }


    /**
     * Adds plugin's actions
     */
    public static function add_actions()
    {
        add_action('admin_init', 'Ga_Admin::admin_init_googleanalytics');
        add_action('admin_menu', 'Ga_Admin::admin_menu_googleanalytics');
        add_action('admin_enqueue_scripts', 'Ga_Admin::enqueue_scripts');
        add_action('wp_ajax_ga_ajax_data_change', 'Ga_Admin::ga_ajax_data_change');
        add_action('heartbeat_tick', 'Ga_Admin::run_heartbeat_jobs');
    }

    /**
     * Runs jobs
     * @param $response
     * @param $screen_id
     */
    public static function run_heartbeat_jobs($response, $screen_id = '')
    {

        if (Ga_Admin::GA_HEARTBEAT_API_CACHE_UPDATE) {
            // Disable cache for ajax request
            self::api_client()->set_disable_cache(true);

            // Try to regenerate cache if needed
//            self::generate_stats_data();
        }
    }

    /**
     * Adds plugin's filters
     */
    public static function add_filters()
    {
        add_filter('plugin_action_links', 'Ga_Admin::ga_action_links', 10, 5);
    }

    /**
     * Adds new action links on the plugin list.
     *
     * @param $actions
     * @param $plugin_file
     *
     * @return mixed
     */
    public static function ga_action_links($actions, $plugin_file)
    {

        if (basename($plugin_file) == GA_NAME . '.php') {
            array_unshift($actions, '<a href="' . esc_url(get_admin_url(null, Ga_Helper::GA_SETTINGS_PAGE_URL)) . '">' . _('Settings') . '</a>');
        }

        return $actions;
    }

    public static function init_oauth()
    {

        $code = Ga_Helper::get_option(self::GA_OAUTH_AUTH_CODE_OPTION_NAME);

        if (!empty($code)) {
            Ga_Helper::update_option(self::GA_OAUTH_AUTH_CODE_OPTION_NAME, "");

            // Get access token
            $response = self::api_client()->call('ga_auth_get_access_token', $code);
            if (empty($response)) {
                return false;
            }
            $param = '';

            $token = self::parse_access_token($response);
            if (empty($token)) {
                $param = '&err=1';
            } else {
                $account_summaries = self::api_client()->call('ga_api_account_summaries', array($token));

                self::save_accounts($token, $account_summaries->getData());
            }

            wp_redirect(admin_url(Ga_Helper::GA_SETTINGS_PAGE_URL . $param));
        }
    }

    /**
     * Save analytics accounts data
     * @param $token
     * @param $account_summaries
     */
    public static function save_accounts($token, $account_summaries){
        $array = json_decode(get_option(self::GA_ACCOUNT_AND_DATA_ARRAY, array()), true);
        $return = array();
        $return['token'] = $token;
        $return['account_summaries'] = array();

        if (!empty($account_summaries['items'])) {
            foreach ($account_summaries['items'] as $item) {
                $tmp = array();
                $tmp['id'] = $item['id'];
                $tmp['name'] = $item['name'];
                if (is_array($item['webProperties'])) {
                    foreach ($item['webProperties'] as $property) {
                        $profiles = array();
                        if (is_array($property['profiles'])) {
                            foreach ($property['profiles'] as $profile) {
                                $profiles[] = array(
                                    'id' => $profile['id'],
                                    'name' => $profile['name']
                                );
                            }
                        }

                        $tmp['webProperties'][] = array(
                            'webPropertyId' => $property['id'],
                            'name' => $property['name'],
                            'profiles' => $profiles
                        );
                    }
                }
                $return['account_summaries'][] = $tmp;
            }

            $array[$account_summaries['username']] = $return;
            update_option(self::GA_ACCOUNT_AND_DATA_ARRAY, json_encode($array));
        }

    }


    public static function parse_access_token($response, $refresh_token = '')
    {
        $access_token = $response->getData();
        if (!empty($access_token)) {
            $access_token['created'] = time();
        } else {
            return false;
        }

        if (!empty($refresh_token)) {
            $access_token['refresh_token'] = $refresh_token;
        }
        return $access_token;

    }

    public static function save_access_token($response, $token){
        if (isset($token['account_id'])){
            $new_token = self::parse_access_token($response);
            $array = json_decode(get_option(self::GA_ACCOUNT_AND_DATA_ARRAY, array()), true);
            foreach($array as $email => $account){
                foreach($account['account_summaries'] as $account_summary){
                    if ($account_summary['id'] === $token["account_id"]){
                        $account['token'] = $new_token;
                    }
                }
            }
            update_option(self::GA_ACCOUNT_AND_DATA_ARRAY, json_encode($array));
            return $new_token;
        }
    }



    /**
     * Displays API error messages.
     */
    public static function display_api_errors($alias = '')
    {
        $errors = self::api_client($alias)->get_errors();
        if (!empty($errors)) {
            foreach ($errors as $error) {
                echo Ga_Notice::get_message($error);
            }
        }
    }



    public static function get_report_data($last_report){

        $data = json_decode(get_option(self::GA_ACCOUNT_AND_DATA_ARRAY, "[]"), true);
        $selected_views = array();

        $website_unique_visits = array();
        foreach ($data as $account_email => $account){
            foreach($account['account_summaries'] as $account_summary){
                $account_summary['reauth'] = true;
                foreach ($account_summary['webProperties'] as $property){
                    foreach ($property['profiles'] as $profile){
                        if (isset($profile['include_in_stats']) && $profile['include_in_stats']==true){
                            $selected_views[] = array(
                                'account_id'		=> $account_summary['id'],
                                'web_property_id'	=> $property['webPropertyId'],
                                'view_id'			=> $profile['id'],
                                'token'             => $account['token'],
                                'url'               => $property['name']
                            );
                        }
                    }
                }
            };
        }

        $last_report = new DateTime($last_report);
        $today = new DateTime();
        $interval = $last_report->diff($today);

        $datys_ago = $interval->format('%adaysago');

        foreach($selected_views as $selected){
            $query_params = Ga_Stats::get_query('report', $selected['view_id'], $datys_ago);
            $query_params['token'] = $selected['token'];
            $query_params['token']['account_id'] = $selected['account_id'];
            $stats_data = self::api_client()->call('ga_api_data', array(
                $query_params
            ));
            $report = !empty($stats_data) ? Ga_Stats::get_report($stats_data->getData()) : array();
            $website_unique_visits[$selected['url']] = $report;
        }

        return $website_unique_visits;
    }
}
