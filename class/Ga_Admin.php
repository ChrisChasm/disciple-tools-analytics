<?php

class Ga_Admin
{

    //stores the selected account id
    const GA_EXCLUDE_ROLES_OPTION_NAME = 'googleanalytics_exclude_roles';
    const GA_HIDE_TERMS_OPTION_NAME = 'googleanalytics_hide_terms';
    const GA_VERSION_OPTION_NAME = 'googleanalytics_version';
    const GA_OAUTH_AUTH_CODE_OPTION_NAME = 'googleanalytics_oauth_auth_code';
    //stores the access token and the refresh token
    const GA_OAUTH_AUTH_TOKEN_OPTION_NAME = 'googleanalytics_oauth_auth_token';
    const GA_ACCOUNT_DATA_OPTION_NAME = 'googleanalytics_account_data';
    //manually not used
    const GA_WEB_PROPERTY_ID_MANUALLY_OPTION_NAME           = 'googleanalytics_web_property_id_manually';
    const GA_WEB_PROPERTY_ID_MANUALLY_VALUE_OPTION_NAME  = 'googleanalytics_web_property_id_manually_value';
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
        add_option(self::GA_EXCLUDE_ROLES_OPTION_NAME, wp_json_encode(array()));
        add_option(self::GA_ACCOUNT_AND_DATA_ARRAY, wp_json_encode(array()));
        add_option(self::GA_SELECTED_VIEWS, wp_json_encode(array()));
        add_option(self::GA_HIDE_TERMS_OPTION_NAME, false);
        add_option(self::GA_VERSION_OPTION_NAME);
        add_option(self::GA_OAUTH_AUTH_CODE_OPTION_NAME);
        add_option(self::GA_OAUTH_AUTH_TOKEN_OPTION_NAME);
        add_option(self::GA_ACCOUNT_DATA_OPTION_NAME);
        add_option(self::GA_WEB_PROPERTY_ID_MANUALLY_OPTION_NAME);
        add_option(self::GA_WEB_PROPERTY_ID_MANUALLY_VALUE_OPTION_NAME);
        Ga_Cache::add_cache_options();
    }

    /*
     * Deletes plugin's options during plugin activation process.
     */

    public static function deactivate_googleanalytics()
    {
        delete_option(self::GA_EXCLUDE_ROLES_OPTION_NAME);
        delete_option(self::GA_ACCOUNT_AND_DATA_ARRAY);
        delete_option(self::GA_SELECTED_VIEWS);
        delete_option(self::GA_OAUTH_AUTH_CODE_OPTION_NAME);
        delete_option(self::GA_OAUTH_AUTH_TOKEN_OPTION_NAME);
        delete_option(self::GA_ACCOUNT_DATA_OPTION_NAME);
        delete_option(self::GA_WEB_PROPERTY_ID_MANUALLY_OPTION_NAME);
        delete_option(self::GA_WEB_PROPERTY_ID_MANUALLY_VALUE_OPTION_NAME);
        Ga_Cache::delete_cache_options();
    }

    /**
     * Deletes plugin's options during plugin uninstallation process.
     */
    public static function uninstall_googleanalytics()
    {
        delete_option(self::GA_HIDE_TERMS_OPTION_NAME);
        delete_option(self::GA_VERSION_OPTION_NAME);
    }

    /**
     * Do actions during plugin load.
     */
    public static function loaded_googleanalytics()
    {
        self::update_googleanalytics();
    }

    /**
     * Update hook fires when plugin is being loaded.
     */
    public static function update_googleanalytics()
    {

        $version = get_option(self::GA_VERSION_OPTION_NAME);
        $installed_version = get_option(self::GA_VERSION_OPTION_NAME, '1.0.7');
        $old_property_value = Ga_Helper::get_option('web_property_id');
        if (version_compare($installed_version, GOOGLEANALYTICS_VERSION, 'eq')) {
            return;
        }

        if (version_compare($installed_version, GOOGLEANALYTICS_VERSION, 'lt')) {

            if (!empty($old_property_value)) {
                Ga_Helper::update_option(self::GA_WEB_PROPERTY_ID_MANUALLY_VALUE_OPTION_NAME, $old_property_value);
                Ga_Helper::update_option(self::GA_WEB_PROPERTY_ID_MANUALLY_OPTION_NAME, 1);
                delete_option('web_property_id');
            }
        }

        update_option(self::GA_VERSION_OPTION_NAME, GOOGLEANALYTICS_VERSION);
    }

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
     * Pre-update hook for preparing JSON structure.
     *
     * @param $new_value
     * @param $old_value
     *
     * @return mixed
     */
    public static function preupdate_selected_account($new_value, $old_value)
    {
        $data = null;
        if (!empty($new_value)) {
            $data = explode("_", $new_value);

            if (!empty($data[1])) {
                Ga_Helper::update_option(self::GA_WEB_PROPERTY_ID_OPTION_NAME, $data[1]);
            }
        }

        return wp_json_encode($data);
    }

    public static function preupdate_disable_all_features($new_value, $old_value)
    {
        if ($old_value == 'on') {
            Ga_Helper::update_option(Ga_Admin::GA_WEB_PROPERTY_ID_MANUALLY_OPTION_NAME, false);
        }

        return $new_value;
    }

    /**
     * Registers plugin's settings.
     */
    public static function admin_init_googleanalytics()
    {
        register_setting(GA_NAME, self::GA_EXCLUDE_ROLES_OPTION_NAME);
        register_setting(GA_NAME, self::GA_SELECTED_VIEWS);
        register_setting(GA_NAME, self::GA_OAUTH_AUTH_CODE_OPTION_NAME);
        register_setting(GA_NAME, self::GA_WEB_PROPERTY_ID_MANUALLY_OPTION_NAME);
        register_setting(GA_NAME, self::GA_WEB_PROPERTY_ID_MANUALLY_VALUE_OPTION_NAME);
        add_filter('pre_update_option_' . Ga_Admin::GA_EXCLUDE_ROLES_OPTION_NAME, 'Ga_Admin::preupdate_exclude_roles', 1, 2);
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
     * Prepares and displays plugin's stats page.
     */
//    public static function statistics_page_googleanalytics()
//    {
//
//        if (!Ga_Helper::is_wp_version_valid() || !Ga_Helper::is_php_version_valid()) {
//            return false;
//        }
//
//        $data = self::get_stats_page();
//        Ga_View_Core::load('statistics', array(
//            'data' => $data
//        ));
//
//        if (Ga_Cache::is_data_cache_outdated('', Ga_Helper::get_account_id())) {
//            self::api_client()->add_own_error('1', _('Saved data is shown, it will be refreshed soon'), 'Ga_Data_Outdated_Exception');
//        }
//
//        self::display_api_errors();
//    }

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
     * Prepares and returns Google Account's dropdown code.
     *
     * @return string
     */
    public static function get_accounts_selector()
    {
        $selected = Ga_Helper::get_selected_account_data();

        return Ga_View_Core::load('ga_accounts_selector', array(
            'selector' => json_decode(get_option(self::GA_ACCOUNT_DATA_OPTION_NAME), true),
            'selected' => $selected ? implode("_", $selected) : null,
            'add_manually_enabled' => Ga_Helper::is_code_manually_enabled() || Ga_Helper::is_all_feature_disabled()
        ), true);
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
     * Enqueues dashboard JS scripts.
     */
    private static function enqueue_dashboard_scripts()
    {
        wp_register_script(GA_NAME . '-dashboard-js', GA_PLUGIN_URL . '/js/' . GA_NAME . '_dashboard.js', array(
            'jquery'
        ));
        wp_enqueue_script(GA_NAME . '-dashboard-js');
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

        if (Ga_Helper::is_dashboard_page()) {
            self::enqueue_dashboard_scripts();
        }

        if (Ga_Helper::is_plugin_page()) {
            self::enqueue_ga_scripts();
        }
    }

    /**
     * Prepares plugin's statistics page and return HTML code.
     *
     * @return string HTML code
     */
//    public static function get_stats_page()
//    {
//        $chart = null;
//        $boxes = null;
//        $labels = null;
//        $sources = null;
//        if (Ga_Helper::is_authorized() && Ga_Helper::is_account_selected() && !Ga_Helper::is_all_feature_disabled()) {
//            list($chart, $boxes, $labels, $sources) = self::generate_stats_data();
//        }
//
//        return Ga_Helper::get_chart_page('stats', array(
//            'chart' => $chart,
//            'boxes' => $boxes,
//            'labels' => $labels,
//            'sources' => $sources
//        ));
//    }

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
     * Adds GA dashboard widget only for administrators.
     */
//    public static function add_dashboard_device_widget()
//    {
//        if (Ga_Helper::is_administrator()) {
//            wp_add_dashboard_widget('ga_dashboard_widget', __('Google Analytics Dashboard'), 'Ga_Helper::add_ga_dashboard_widget');
//        }
//    }

    /**
     * Adds plugin's actions
     */
    public static function add_actions()
    {
        add_action('admin_init', 'Ga_Admin::admin_init_googleanalytics');
        add_action('admin_menu', 'Ga_Admin::admin_menu_googleanalytics');
        add_action('admin_enqueue_scripts', 'Ga_Admin::enqueue_scripts');
//        add_action('wp_dashboard_setup', 'Ga_Admin::add_dashboard_device_widget');
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
            self::generate_stats_data();
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
     * Handle AJAX data for the GA dashboard widget.
     */
    public static function ga_ajax_data_change()
    {
        $date_range = !empty($_POST['date_range']) ? $_POST['date_range'] : null;
        $metric = !empty($_POST['metric']) ? $_POST['metric'] : null;
        echo Ga_Helper::get_ga_dashboard_widget_data_json($date_range, $metric, false, true);
        wp_die();
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

    /**
     * Gets dashboard data.
     *
     * @return array
     */
    public static function generate_stats_data()
    {
        $selected = Ga_Helper::get_selected_account_data(true);

        $query_params = Ga_Stats::get_query('main_chart', $selected['view_id']);
        $stats_data = self::api_client()->call('ga_api_data', array(
            $query_params
        ));

        $boxes_data = self::api_client()->call('ga_api_data', array(
            Ga_Stats::get_query('boxes', $selected['view_id'])
        ));
        $sources_data = self::api_client()->call('ga_api_data', array(
            Ga_Stats::get_query('sources', $selected['view_id'])
        ));
        $chart = !empty($stats_data) ? Ga_Stats::get_chart($stats_data->getData()) : array();
        $boxes = !empty($boxes_data) ? Ga_Stats::get_boxes($boxes_data->getData()) : array();
        $last_chart_date = !empty($chart) ? $chart['date'] : strtotime('now');
        unset($chart['date']);
        $labels = array(
            'thisWeek' => date('M d, Y', strtotime('-6 day', $last_chart_date)) . ' - ' . date('M d, Y', $last_chart_date),
            'lastWeek' => date('M d, Y', strtotime('-13 day', $last_chart_date)) . ' - ' . date('M d, Y', strtotime('-7 day', $last_chart_date))
        );
        $sources = !empty($sources_data) ? Ga_Stats::get_sources($sources_data->getData()) : array();

        return array($chart, $boxes, $labels, $sources);
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
