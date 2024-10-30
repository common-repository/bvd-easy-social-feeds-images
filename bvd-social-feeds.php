<?php
/*
  Plugin Name: BVD Easy Social Feeds & Images
  Plugin URI:  https://balcom-vetillo.com/products/wordpress-social-feed-plugin/
  Description: Displays Instagram, Twitter and Facebook Feeds
  Author: Balcom-Vetillo Design, Inc.
  Version: 1.0.7
  Author URI: https://www.balcom-vetillo.com
 */
define("SFR_URL", "https://balcom-vetillo.com/social-feeds-redirect/index.php");
define("SFR_URL_FACEBOOK", "https://balcom-vetillo.com/social-feeds-redirect/facebook.php");
define("SFR_URL_TWITTER", "https://balcom-vetillo.com/social-feeds-redirect/twitter.php");
define("SFR_URL_INSTAGRAM", "https://balcom-vetillo.com/social-feeds-redirect/instagram.php");

class bvdSocialFeeds {

    private $instagram_client_id = "d1422b002b8b43258e92b794e14710e4";
    public $uuid; //site unique ID
    public $secret; //site unique ID
    private $submit_success = 0;
    private $license_key_error = false;
    private $bvd_var_dump = '';

    function __construct() {

        //add_action( 'wp', array($this, 'setup_schedule')); //setup initial schedule
        //add_action( 'bvd_instagram_hourly', array($this, 'cron_hourly')); //run cron

        add_action('init', array($this, 'process_post'));
        add_action('admin_menu', array($this, 'setup_admin_menu'));
        add_action('template_redirect', array($this, "callback"));
        add_action('admin_notices', array($this, 'showAdminMessages'));

        wp_register_style('socialFeedsPluginUserStylesheet', plugins_url('bvd-social-feeds-user-style.css', __FILE__));
        wp_enqueue_style('socialFeedsPluginUserStylesheet');

        add_shortcode('bvd-instagram-feed', array($this, "instagram_feed_display"));
        add_shortcode('bvd-facebook-feed', array($this, "facebook_feed_display"));
        add_shortcode('bvd-twitter-feed', array($this, "twitter_feed_display"));
    }

    public function process_post() {
        $this->uuid = get_option("bvads_social_feed_uuid");
        if (!$this->uuid) {
            update_option("bvads_social_feed_uuid", $this->guid());
            $this->uuid = get_option("bvads_social_feed_uuid");
        }

        $this->secret = get_option("bvads_social_feed_secret");
        if (!$this->secret) {
            $resp = json_decode($this->url_get_contents(SFR_URL . "?sfr_uuid=" . $this->uuid . "&sfr_register=1&callback_url=" . $this->callback_url()));
            update_option("bvads_social_feed_secret", $resp->secret);
            $this->secret = get_option("bvads_social_feed_secret");
        }

        if ($_REQUEST['instagram_auth'] === "auth") {
            update_option("bvads_social_feed_instagram_access_token", $_REQUEST['token']);
            update_option("bvads_social_feed_instagram_user_id", $_REQUEST['user_id']);
            update_option("bvads_social_feed_instagram_username", $_REQUEST['username']);

            wp_redirect(get_admin_url(null, 'admin.php?page=bvd-social-feeds-instagram&tab=basic-settings'));
            exit;
        }

        if (isset($_REQUEST['disconnect_account'])) {
            switch ($_REQUEST['disconnect_account']) {
                case 'instagram' :
                    update_option("bvads_social_feed_instagram_access_token", false);
                    update_option("bvads_social_feed_instagram_user_id", false);
                    update_option("bvads_social_feed_instagram_username", false);
                    break;

                case 'facebook' :
                    update_option("bvads_facebook_oauth_token", false);
                    update_option("bvads_facebook_user_id", false);

                    $request = SFR_URL_FACEBOOK . "?sfr_uuid=" . get_option("bvads_social_feed_uuid") . "&action=disconnect_account";
                    $data = $this->url_get_contents($request);
                    //$data = json_decode($data);
                    break;

                case 'twitter' :
                    update_option("bvads_twitter_oauth_token", false);
                    update_option("bvads_twitter_oauth_secret", false);
                    update_option("bvads_twitter_screenname", false);
                    update_option("bvads_twitter_user_id", false);

                    $request = SFR_URL_TWITTER . "?sfr_uuid=" . get_option("bvads_social_feed_uuid") . "&action=disconnect_account";
                    $data = $this->url_get_contents($request);
                    break;
            }
        }

        if (isset($_REQUEST['bvd-post-action'])) {
            switch ($_REQUEST['bvd-post-action']) {
                case 'set-instagram-api-settings' :
                    $search = strtolower($_REQUEST['user-name']);
                    $access_token = get_option("bvads_social_feed_instagram_access_token");

                    $url = 'https://api.instagram.com/v1/users/search?q=' . $search . '&access_token=' . $access_token;
                    $resp = json_decode($this->url_get_contents($url), true);

                    foreach ($resp['data'] as $user) {
                        if (strtolower($user['username']) == $search) {
                            $user_id = $user['id'];
                            $user_name = $user['username'];

                            break;
                        }
                    }

                    update_option("bvads_social_feed_instagram_user_id", $user_id);
                    update_option("bvads_social_feed_instagram_username", $user_name);

                    update_option("bvads_instagram_settings_change", 1);

                    $this->submit_success = 1;
                    break;

                case 'set-instagram-display-options' :
                    update_option("bvads_social_feed_instagram_number_photos", $_REQUEST['number-display']);

                    update_option("bvads_social_feed_instagram_number_columns", $_REQUEST['number-columns']);

                    if (strpos($_REQUEST['padding-around'], 'px') === false) {
                        update_option("bvads_social_feed_instagram_padding_around", $_REQUEST['padding-around']);
                    } else {
                        update_option("bvads_social_feed_instagram_padding_around", str_replace('px', '', $_REQUEST['padding-around']));
                    }

                    update_option("bvads_social_feed_instagram_user_tag", $_REQUEST['user-tag']);

                    if (isset($_REQUEST['show-header'])) {
                        update_option("bvads_social_feed_instagram_show_header", 1);
                    } else {
                        update_option("bvads_social_feed_instagram_show_header", 0);
                    }
                    
                    if (isset($_REQUEST['show-profile'])) {
                        update_option("bvads_social_feed_instagram_show_profile", 1);
                    } else {
                        update_option("bvads_social_feed_instagram_show_profile", 0);
                    }

                    if (isset($_REQUEST['show-more-link'])) {
                        update_option("bvads_social_feed_instagram_show_more_link", 1);
                    } else {
                        update_option("bvads_social_feed_instagram_show_more_link", 0);
                    }
                    
                    update_option("bvads_social_feed_instagram_header_text", $_REQUEST['header-text']);
                    update_option("bvads_social_feed_instagram_more_link_text", $_REQUEST['more-link-text']);

                    update_option("bvads_instagram_settings_change", 1);

                    $this->submit_success = 1;
                    break;

                case 'set-instagram-design-options' :
                    if ($this->check_color($_REQUEST['header-background'])) {
                        update_option("bvads_social_feed_instagram_header_background", $_REQUEST['header-background']);
                    }
                    if ($this->check_color($_REQUEST['header-font-color'])) {
                        update_option("bvads_social_feed_instagram_header_font_color", $_REQUEST['header-font-color']);
                    }
                    if ($this->check_color($_REQUEST['btn-background'])) {
                        update_option("bvads_social_feed_instagram_more_button_background", $_REQUEST['btn-background']);
                    }
                    if ($this->check_color($_REQUEST['btn-background-hover'])) {
                        update_option("bvads_social_feed_instagram_more_button_background_hover", $_REQUEST['btn-background-hover']);
                    }
                    if ($this->check_color($_REQUEST['btn-font-color'])) {
                        update_option("bvads_social_feed_instagram_more_button_font_color", $_REQUEST['btn-font-color']);
                    }

                    if ($this->check_color($_REQUEST['btn-font-color-hover'])) {
                        update_option("bvads_social_feed_instagram_more_button_font_color_hover", $_REQUEST['btn-font-color-hover']);
                    }

                    update_option("bvads_instagram_settings_change", 1);

                    $this->submit_success = 1;
                    break;

                case 'reset-instagram-design-options' :
                    update_option("bvads_social_feed_instagram_header_background", '#B6B6B6');
                    update_option("bvads_social_feed_instagram_header_font_color", '#FFF');
                    update_option("bvads_social_feed_instagram_more_button_background", '#CCC');
                    update_option("bvads_social_feed_instagram_more_button_background_hover", '#8C8C8C');
                    update_option("bvads_social_feed_instagram_more_button_font_color", '#FFF');
                    update_option("bvads_social_feed_instagram_more_button_font_color_hover", '#FFF');

                    update_option("bvads_instagram_settings_change", 1);

                    $this->submit_success = 1;
                    break;

                case 'set-facebook-api-settings' :
                    if ($_REQUEST['facebook-pages-list'] != 0) {
                        $page_parts = explode('|', $_REQUEST['facebook-pages-list']);

                        update_option("bvads_social_feed_facebook_page_id", $page_parts[0]);
                        update_option("bvads_social_feed_facebook_page_name", $page_parts[1]);

                        update_option("bvads_facebook_settings_change", 1);

                        $this->submit_success = 1;
                    } else {
                        $fb_page = $_REQUEST['page-id'];

                        $request = SFR_URL_FACEBOOK . "?sfr_uuid=" . get_option("bvads_social_feed_uuid") . "&action=get_page_id&sfr_page=" . $fb_page;
                        $data = $this->url_get_contents($request);
                        $data = json_decode($data);

                        update_option("bvads_social_feed_facebook_page_id", $data->id);
                        update_option("bvads_social_feed_facebook_page_name", $data->name);

                        update_option("bvads_facebook_settings_change", 1);

                        $this->submit_success = 1;
                    }
                    break;

                case 'set-facebook-display-options' :
                    update_option("bvads_social_feed_facebook_number_items", $_REQUEST['number-display']);
                    if (isset($_REQUEST['show-header'])) {
                        update_option("bvads_social_feed_facebook_show_header", 1);
                    } else {
                        update_option("bvads_social_feed_facebook_show_header", 0);
                    }

                    if (isset($_REQUEST['show-more-link'])) {
                        update_option("bvads_social_feed_facebook_show_more_link", 1);
                    } else {
                        update_option("bvads_social_feed_facebook_show_more_link", 0);
                    }
                    
                    update_option("bvads_social_feed_facebook_header_text", $_REQUEST['header-text']);
                    update_option("bvads_social_feed_facebook_more_link_text", $_REQUEST['more-link-text']);

                    update_option("bvads_facebook_settings_change", 1);

                    $this->submit_success = 1;
                    break;

                case 'set-facebook-design-options' :
                    if ($this->check_color($_REQUEST['header-background'])) {
                        update_option("bvads_social_feed_facebook_header_background", $_REQUEST['header-background']);
                    }
                    if ($this->check_color($_REQUEST['header-font-color'])) {
                        update_option("bvads_social_feed_facebook_header_font_color", $_REQUEST['header-font-color']);
                    }
                    if ($this->check_color($_REQUEST['border-bottom'])) {
                        update_option("bvads_social_feed_facebook_border_bottom", $_REQUEST['border-bottom']);
                    }
                    if (isset($_REQUEST['border-bottom-weight'])) {
                        $pos = strpos($_REQUEST['border-bottom-weight'], 'px');
                        if ($pos !== false) {
                            $bottom_weight = str_replace('px', '', $_REQUEST['border-bottom-weight']);
                        } else {
                            $bottom_weight = $_REQUEST['border-bottom-weight'];
                        }
                        update_option("bvads_social_feed_facebook_border_bottom_weight", $bottom_weight);
                    }
                    if ($this->check_color($_REQUEST['btn-background'])) {
                        update_option("bvads_social_feed_facebook_more_button_background", $_REQUEST['btn-background']);
                    }
                    if ($this->check_color($_REQUEST['btn-background-hover'])) {
                        update_option("bvads_social_feed_facebook_more_button_background_hover", $_REQUEST['btn-background-hover']);
                    }
                    if ($this->check_color($_REQUEST['btn-font-color'])) {
                        update_option("bvads_social_feed_facebook_more_button_font_color", $_REQUEST['btn-font-color']);
                    }

                    if ($this->check_color($_REQUEST['btn-font-color-hover'])) {
                        update_option("bvads_social_feed_facebook_more_button_font_color_hover", $_REQUEST['btn-font-color-hover']);
                    }

                    update_option("bvads_facebook_settings_change", 1);

                    $this->submit_success = 1;
                    break;

                case 'reset-facebook-design-options' :
                    update_option("bvads_social_feed_facebook_header_background", '#B6B6B6');
                    update_option("bvads_social_feed_facebook_header_font_color", '#FFF');
                    update_option("bvads_social_feed_facebook_border_bottom", '#CCC');
                    update_option("bvads_social_feed_facebook_border_bottom_weight", '1');
                    update_option("bvads_social_feed_facebook_more_button_background", '#CCC');
                    update_option("bvads_social_feed_facebook_more_button_background_hover", '#8C8C8C');
                    update_option("bvads_social_feed_facebook_more_button_font_color", '#FFF');
                    update_option("bvads_social_feed_facebook_more_button_font_color_hover", '#FFF');

                    update_option("bvads_facebook_settings_change", 1);

                    $this->submit_success = 1;
                    break;

                case 'set-twitter-api-settings' :
                    update_option("bvads_twitter_screenname", $_REQUEST['page-id']);

                    update_option("bvads_twitter_settings_change", 1);

                    $this->submit_success = 1;
                    break;

                case 'set-twitter-display-options' :
                    update_option("bvads_social_feed_twitter_number_items", $_REQUEST['number-display']);
                    if (isset($_REQUEST['show-header'])) {
                        update_option("bvads_social_feed_twitter_show_header", 1);
                    } else {
                        update_option("bvads_social_feed_twitter_show_header", 0);
                    }
                    if (isset($_REQUEST['show-more-link'])) {
                        update_option("bvads_social_feed_twitter_show_more_link", 1);
                    } else {
                        update_option("bvads_social_feed_twitter_show_more_link", 0);
                    }
                    
                    update_option("bvads_social_feed_twitter_header_text", $_REQUEST['header-text']);
                    update_option("bvads_social_feed_twitter_more_link_text", $_REQUEST['more-link-text']);

                    update_option("bvads_twitter_settings_change", 1);

                    $this->submit_success = 1;
                    break;

                case 'set-twitter-design-options' :
                    if ($this->check_color($_REQUEST['header-background'])) {
                        update_option("bvads_social_feed_twitter_header_background", $_REQUEST['header-background']);
                    }
                    if ($this->check_color($_REQUEST['header-font-color'])) {
                        update_option("bvads_social_feed_twitter_header_font_color", $_REQUEST['header-font-color']);
                    }
                    if ($this->check_color($_REQUEST['border-bottom'])) {
                        update_option("bvads_social_feed_twitter_border_bottom", $_REQUEST['border-bottom']);
                    }
                    if (isset($_REQUEST['border-bottom-weight'])) {
                        $pos = strpos($_REQUEST['border-bottom-weight'], 'px');
                        if ($pos !== false) {
                            $bottom_weight = str_replace('px', '', $_REQUEST['border-bottom-weight']);
                        } else {
                            $bottom_weight = $_REQUEST['border-bottom-weight'];
                        }
                        update_option("bvads_social_feed_twitter_border_bottom_weight", $bottom_weight);
                    }
                    if ($this->check_color($_REQUEST['btn-background'])) {
                        update_option("bvads_social_feed_twitter_more_button_background", $_REQUEST['btn-background']);
                    }
                    if ($this->check_color($_REQUEST['btn-background-hover'])) {
                        update_option("bvads_social_feed_twitter_more_button_background_hover", $_REQUEST['btn-background-hover']);
                    }
                    if ($this->check_color($_REQUEST['btn-font-color'])) {
                        update_option("bvads_social_feed_twitter_more_button_font_color", $_REQUEST['btn-font-color']);
                    }

                    if ($this->check_color($_REQUEST['btn-font-color-hover'])) {
                        update_option("bvads_social_feed_twitter_more_button_font_color_hover", $_REQUEST['btn-font-color-hover']);
                    }

                    update_option("bvads_twitter_settings_change", 1);

                    $this->submit_success = 1;
                    break;

                case 'reset-twitter-design-options' :
                    update_option("bvads_social_feed_twitter_header_background", '#B6B6B6');
                    update_option("bvads_social_feed_twitter_header_font_color", '#FFF');
                    update_option("bvads_social_feed_twitter_border_bottom", '#CCC');
                    update_option("bvads_social_feed_twitter_border_bottom_weight", '1');
                    update_option("bvads_social_feed_twitter_more_button_background", '#CCC');
                    update_option("bvads_social_feed_twitter_more_button_background_hover", '#8C8C8C');
                    update_option("bvads_social_feed_twitter_more_button_font_color", '#FFF');
                    update_option("bvads_social_feed_twitter_more_button_font_color_hover", '#FFF');

                    update_option("bvads_twitter_settings_change", 1);

                    $this->submit_success = 1;
                    break;

                case 'set-pro-key' :
                    $this->license_key_activation($_REQUEST['pro-key']);
                    break;

                case 'deactivate-pro-key' :
                    $this->license_key_deactivation($_REQUEST['pro-key']);
                    break;
            }
        }
    }

    private function guid() {
        if (function_exists('com_create_guid')) {
            return com_create_guid();
        } else {
            mt_srand((double) microtime() * 10000); //optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45); // "-"
            $uuid = chr(123)// "{"
                    . substr($charid, 0, 8) . $hyphen
                    . substr($charid, 8, 4) . $hyphen
                    . substr($charid, 12, 4) . $hyphen
                    . substr($charid, 16, 4) . $hyphen
                    . substr($charid, 20, 12)
                    . chr(125); // "}"
            return $uuid;
        }
    }

    public static function callback_url() {
        return urlencode(home_url());
    }

    public function check_color($value) {

        if (preg_match('/^#[a-f0-9]{6}$/i', $value)) { // if user insert a HEX color with #     
            return true;
        }

        return false;
    }

    public function setup_admin_menu() {
        $icon = plugin_dir_url(__FILE__) . '/images/balcom-vetillo-icon.png';
        $my_page = add_menu_page("BVD Social Feeds", "BVD Social Feeds", "manage_options", "bvd-social-feeds", array($this, "admin_page"), $icon, '81.34587');
        $my_page_3 = add_submenu_page("bvd-social-feeds", "Social Feeds Facebook", "Facebook", "manage_options", "bvd-social-feeds-facebook", array($this, "admin_page_facebook"));
        $my_page_4 = add_submenu_page("bvd-social-feeds", "Social Feeds Twitter", "Twitter", "manage_options", "bvd-social-feeds-twitter", array($this, "admin_page_twitter"));
        $my_page_2 = add_submenu_page("bvd-social-feeds", "Social Feeds Instagram", "Instagram", "manage_options", "bvd-social-feeds-instagram", array($this, "admin_page_instagram"));

        add_action('load-' . $my_page, array($this, "social_feeds_load_styles"));
        add_action('load-' . $my_page_2, array($this, "social_feeds_load_styles"));
        add_action('load-' . $my_page_3, array($this, "social_feeds_load_styles"));
        add_action('load-' . $my_page_4, array($this, "social_feeds_load_styles"));
    }

    public function social_feeds_load_styles() {
        add_action('admin_enqueue_scripts', array($this, "social_feeds_enqueue"));
    }

    public function social_feeds_enqueue() {
        wp_register_style('socialFeedsPluginStylesheet', plugins_url('bvd-social-feeds-style.css', __FILE__));
        wp_enqueue_style('socialFeedsPluginStylesheet');

        // Add the color picker css file       
        wp_enqueue_style('wp-color-picker');

        // Include our custom jQuery file with WordPress Color Picker dependency
        wp_enqueue_script('social-feeds-color-picker', plugins_url('social-feeds-color-picker.js', __FILE__), array('wp-color-picker'), false, true);
    }

    public function license_key_activation($license_key) {
        $request = SFR_URL . "?sfr_uuid=" . get_option("bvads_social_feed_uuid") . "&sfr_activate_license=1&sfr_secret=" . get_option("bvads_social_feed_secret") . "&sfr_license_key=" . $license_key . "&server=" . $_SERVER['SERVER_NAME'];
        $data = $this->url_get_contents($request);
        $data = json_decode($data);
        if ($data->ACK == 'SUCCESS') {
            $this->submit_success = 1;
        } else {
            $this->submit_success = 0;
            $this->license_key_error = $data->error;
        }
    }

    public function license_key_deactivation($license_key) {
        $request = SFR_URL . "?sfr_uuid=" . get_option("bvads_social_feed_uuid") . "&sfr_deactivate_license=1&sfr_secret=" . get_option("bvads_social_feed_secret") . "&sfr_license_key=" . $license_key . "&server=" . $_SERVER['SERVER_NAME'];
        $data = $this->url_get_contents($request);
        $data = json_decode($data);
        if ($data->ACK == 'SUCCESS') {
            $this->submit_success = 2;
        } else {
            $this->submit_success = 0;
            $this->license_key_error = $data->error;
        }
    }

    public function url_get_contents($url) {
        if (function_exists('curl_exec')) {
            $conn = curl_init($url);
            curl_setopt($conn, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($conn, CURLOPT_FRESH_CONNECT, true);
            curl_setopt($conn, CURLOPT_RETURNTRANSFER, 1);
            $url_get_contents_data = (curl_exec($conn));
            curl_close($conn);
        } elseif (function_exists('file_get_contents')) {
            $url_get_contents_data = file_get_contents($url);
        } elseif (function_exists('fopen') && function_exists('stream_get_contents')) {
            $handle = fopen($url, "r");
            $url_get_contents_data = stream_get_contents($handle);
            fclose($handle);
        } else {
            $url_get_contents_data = false;
        }
        return $url_get_contents_data;
    }

    public function cache_file_get_contents($file) {
        if (function_exists('fopen') && function_exists('stream_get_contents')) {
            $handle = fopen($file, "r");
            $url_get_contents_data = stream_get_contents($handle);
            fclose($handle);
        } elseif (function_exists('file_get_contents')) {
            $url_get_contents_data = file_get_contents($file);
        }
        return $url_get_contents_data;
    }

    public function admin_page() {
        global $wpdb;
        ?>
        <div class="wrap">
            <h1>BVD Social Feeds</h1>
            <div class="designed-by-wrapper">
                <p>Plugin designed and developed by<br/><a href="https://www.balcom-vetillo.com/" target="_blank">Balcom-Vetillo Design</a>.</p>
                <a href="https://www.balcom-vetillo.com/" target="_blank"><img src="<?php echo plugins_url('images/BVD-Logo-vert.png', __FILE__); ?>" /></a>
            </div>
            <div class="main-content-wrapper">
                <?php
                if ($this->license_key_error) {
                    ?>
                    <div id="social-feeds-message" class="error">
                        <p><?php echo $this->license_key_error; ?></p>
                    </div>
                    <?php
                }

                if ($this->submit_success == 1) {
                    ?>
                    <div id="social-feeds-message" class="updated">
                        <p>Pro Key successfully submitted.</p>
                    </div>
                    <?php
                } elseif ($this->submit_success == 2) {
                    ?>
                    <div id="social-feeds-message" class="updated">
                        <p>Pro Key has been deactivated.</p>
                    </div>
                    <?php
                }
                ?>
                <div class="social-feeds-about-information">
                    <p>The Social Feeds Plugin will allow you to display feeds from your Facebook, Twitter and Instagram feeds. The plugin will display a feed from any account with public settings with minimal setup. Just go to each network page and click the button to authorize and you will be redirected to the login page for that social network if you aren't already logged in. Login here (your password is not shared with the plugin) and accept the requested permissions (the plugin only requests the most basic permissions that only let the plugin read your public data) then you are all set. There are several display and design options you can set and then view the shortcode tab to see how to add a feed to any page, post or template on your site.</p>
                </div>
                <?php
                if (!$this->check_pro_key()) {
                    $hidden_value = 'set-pro-key';
                    $pro_key = '';
                    $submit_value = 'Submit Key';
                } else {
                    $hidden_value = 'deactivate-pro-key';
                    $pro_key = $this->get_pro_key();
                    $submit_value = 'Deactivate Key';
                }
                ?>
                <div class="pro-key-submit-form-wrapper">
                    <div class="social-feeds-section-title">
                        Pro Version
                    </div>
                    <div class="pro-key-submit-form-info">
                        <?php
                        if (!$this->check_pro_key()) {
                            ?>
                            <p>The BVD Social Feeds plugin has an optional pro version that can be unlocked by purchasing a Pro Key. The pro version will unlock additional plugin settings.</p>
                            <p>If you have a Pro Key, enter it in the form below to activate the pro options in this plugin.</p>
                            <?php
                        } else {
                            ?>
                            <p>Your Pro Key has been activated!</p>
                            <p>You can deactivate your Pro key by submitting the form below.</p>
                            <?php
                        }
                        ?>
                    </div>
                    <form action="" method="post" class="pro-key-submit-form">
                        <input type="hidden" name="bvd-post-action" value="<?php echo $hidden_value; ?>" />
                        <div class="form-section">
                            <div class="form-section-left">
                                <label for="pro-key">Pro Key</label>
                            </div>
                            <div class="form-section-right">
                                <?php
                                if (!empty($pro_key)) {
                                    ?>
                                    <input type="text" name="pro-key" id="pro-key" placeholder="Pro Key" value="<?php echo $pro_key; ?>">
                                    <?php
                                } else {
                                    ?>
                                    <input type="text" name="pro-key" id="pro-key" placeholder="Pro Key">
                                    <?php
                                }
                                ?>
                            </div>
                            <div style="clear:left;"></div>
                        </div>
                        <div class="form-section">
                            <input type="submit" value="<?php echo $submit_value; ?>" />
                        </div>
                    </form>
                    <?php
                    if (empty($pro_key)) {
                        ?>
                        <div class="social-feeds-get-pro-key-link">
                            <a href="https://www.balcom-vetillo.com/plugin-keys/" target="_blank">Get a Pro Key</a>
                        </div>
                        <?php
                    }
                    ?>
                </div>

                <div class="social-feeds-page-links">
                    <a href="<?php echo get_admin_url(null, 'admin.php?page=bvd-social-feeds-facebook'); ?>">Facebook Settings</a><br/><br/>
                    <a href="<?php echo get_admin_url(null, 'admin.php?page=bvd-social-feeds-twitter'); ?>">Twitter Settings</a><br/><br/>
                    <a href="<?php echo get_admin_url(null, 'admin.php?page=bvd-social-feeds-instagram'); ?>">Instagram Settings</a><br/>
                </div>
            </div>

            <?php
            if (!empty($this->bvd_var_dump)) {
                ?>
                <pre><?php print_r($this->bvd_var_dump); ?></pre> 
                <?php
            }
            ?>
        </div>
        <?php
    }

    public function admin_page_instagram() {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'basic-settings';
        include 'admin-page-instagram.php';
    }

    public function admin_page_facebook() {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'basic-settings';
        include 'admin-page-facebook.php';
    }

    public function admin_page_twitter() {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'basic-settings';
        include 'admin-page-twitter.php';
    }

    //Check if there is a pro key
    public function check_pro_key() {
        $request = SFR_URL . "?sfr_uuid=" . get_option("bvads_social_feed_uuid") . "&action=verify_pro_key";
        $data = $this->url_get_contents($request);
        $data = json_decode($data);

        if ($data->key_status == "SUCCESS") {
            return true;
        } else {
            return false;
        }
    }

    public function get_pro_key() {
        $request = SFR_URL . "?sfr_uuid=" . get_option("bvads_social_feed_uuid") . "&action=get_pro_key";
        $data = $this->url_get_contents($request);
        $data = json_decode($data);

        if ($data->key_status == "SUCCESS") {
            return $data->pro_key;
        } else {
            return false;
        }
    }

    public function callback() {
        global $wpdb;
        if (isset($_REQUEST['sfr_callback'])) {
            switch ($_REQUEST['sfr_callback']) {
                case "twitter":
                    //verify uuid here
                    update_option("bvads_twitter_oauth_token", $_REQUEST['oauth_token']);
                    update_option("bvads_twitter_oauth_secret", $_REQUEST['oauth_secret']);
                    update_option("bvads_twitter_screenname", $_REQUEST['oauth_username']);
                    update_option("bvads_twitter_user_id", $_REQUEST['oauth_user_id']);

                    header("Location: " . admin_url() . "admin.php?page=bvd-social-feeds-twitter");
                    die();
                    break;

                case "facebook":
                    //verify uuid here
                    update_option("bvads_facebook_oauth_token", $_REQUEST['oauth_token']);
                    update_option("bvads_facebook_user_id", $_REQUEST['oauth_user_id']);

                    header("Location: " . admin_url() . "admin.php?page=bvd-social-feeds-facebook");
                    die();
                    break;
            }
        }
    }

    //Instagram Feed Display Shortcode
    public function instagram_feed_display($atts) {
        global $wpdb;

        $cache_file = plugin_dir_path(__FILE__) . 'instagram-feed-cache.txt';

        if (get_option("bvads_instagram_settings_change") == 1) {
            $ignore_cache = true;

            update_option("bvads_instagram_settings_change", 0);
        } else {
            $ignore_cache = false;
        }

        if (!$ignore_cache && file_exists($cache_file) && (filemtime($cache_file) > (time() - 60 * 15 ))) {
            //read from cache
            //less than 15 minutes old
            $output_string = $this->cache_file_get_contents($cache_file);
        } else {
            //cache outdated
            ob_start();

            if ($number_photos = get_option("bvads_social_feed_instagram_number_photos")) {
                if (!empty($number_photos)) {
                    $count_default = $number_photos;
                } else {
                    $count_default = 5;
                }
            } else {
                $count_default = 5;
            }

            if ($number_columns = get_option("bvads_social_feed_instagram_number_columns")) {
                if (!empty($number_columns)) {
                    $cols_default = $number_columns;
                } else {
                    $cols_default = 5;
                }
            } else {
                $cols_default = 5;
            }

            if ($padding_around = get_option("bvads_social_feed_instagram_padding_around")) {
                if (!empty($padding_around)) {
                    $pad_default = $padding_around;
                } else {
                    $pad_default = 5;
                }
            } else {
                $pad_default = 5;
            }

            $user_tag = get_option("bvads_social_feed_instagram_user_tag");
            
            if(get_option("bvads_social_feed_instagram_show_header")) {
                $show_header = get_option("bvads_social_feed_instagram_show_header");
            } else {
                $show_header = 0;
            }
            
            if(get_option("bvads_social_feed_instagram_show_profile")) {
                $show_profile = get_option("bvads_social_feed_instagram_show_profile");
            } else {
                $show_profile = 0;
            }
            
            if (get_option("bvads_social_feed_instagram_show_more_link")) {
                $show_more = get_option("bvads_social_feed_instagram_show_more_link");
            } else {
                $show_more = 0;
            }
            
            if (get_option("bvads_social_feed_instagram_header_text")) {
                $header_text = get_option("bvads_social_feed_instagram_header_text");
            } else {
                $header_text = 'Recent From Instagram';
            }
            
            if (get_option("bvads_social_feed_instagram_more_link_text")) {
                $more_link_text = get_option("bvads_social_feed_instagram_more_link_text");
            } else {
                $more_link_text = 'read more';
            }

            if (get_option("bvads_social_feed_instagram_header_background")) {
                $header_background = get_option("bvads_social_feed_instagram_header_background");
            } else {
                $header_background = '#B6B6B6';
            }

            if (get_option("bvads_social_feed_instagram_header_font_color")) {
                $header_font_color = get_option("bvads_social_feed_instagram_header_font_color");
            } else {
                $header_font_color = '#fff';
            }

            if (get_option("bvads_social_feed_instagram_more_button_background")) {
                $btn_background = get_option("bvads_social_feed_instagram_more_button_background");
            } else {
                $btn_background = '#ccc';
            }

            if (get_option("bvads_social_feed_instagram_more_button_font_color")) {
                $btn_font_color = get_option("bvads_social_feed_instagram_more_button_font_color");
            } else {
                $btn_font_color = '#fff';
            }

            if (get_option("bvads_social_feed_instagram_more_button_font_color_hover")) {
                $btn_font_color_hover = get_option("bvads_social_feed_instagram_more_button_font_color_hover");
            } else {
                $btn_font_color_hover = '#fff';
            }

            if (get_option("bvads_social_feed_instagram_more_button_background_hover")) {
                $btn_background_hover = get_option("bvads_social_feed_instagram_more_button_background_hover");
            } else {
                $btn_background_hover = '#8C8C8C';
            }

            $user_id = get_option("bvads_social_feed_instagram_user_id");
            $access_token = get_option("bvads_social_feed_instagram_access_token");

            $atts = shortcode_atts(
                    array(
                'use_tags' => false,
                'tags' => '',
                'count' => $count_default,
                'columns' => $cols_default,
                'padding' => $pad_default,
                'header' => $show_header,
                'profile' => $show_profile,
                'header_text' => $header_text,
                'more_link' => $show_more,
                'more_link_text' => $more_link_text
                    ), $atts, 'bvd-instagram-feed');

            if (!$this->check_pro_key()) {
                if ($atts['count'] > 5) {
                    $atts['count'] = 5;
                }
            }

            if (!$this->check_pro_key()) {
                if ($atts['columns'] > 5) {
                    $atts['columns'] = 5;
                }
            }
            ?>
            <style>
                .instagram-feed-section-title {
                    background-color: <?php echo $header_background; ?>;
                    color: <?php echo $header_font_color; ?>;
                }

                .instagram-feed-wrapper .sf-feed-header-icon svg {
                    fill: <?php echo $header_font_color; ?>;
                }

                .instagram-feed-wrapper a.sf-feed-more-link {
                    background-color: <?php echo $btn_background; ?>;
                    color: <?php echo $btn_font_color; ?>;
                }

                .instagram-feed-wrapper a.sf-feed-more-link:hover {
                    background-color: <?php echo $btn_background_hover; ?>;
                    color: <?php echo $btn_font_color_hover; ?>;
                }
            </style>
            <?php
            if ($atts['use_tags']) {
                $tags = explode(',', $atts['tags']);
                if (!$this->check_pro_key()) {
                    $tags_use[] = $tags[0];
                } else {
                    $tags_use = $tags;
                }

                foreach ($tags_use as $tag) {
                    $url = 'https://api.instagram.com/v1/tags/' . $tag . '/media/recent/?count=' . $atts['count'] . '&access_token=' . $access_token;
                    $resp = json_decode($this->url_get_contents($url));

                    if ($resp->meta->code === 200) {
                        $width = 100 / $atts['columns'];
                        ?>
                        <div class="instagram-feed-wrapper">
                            <?php
                            if($atts['header']) {
                                ?>
                                <div class="instagram-feed-section-title">
                                    <span class="sf-feed-header-icon">
                                        <svg viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1490 1426v-648h-135q20 63 20 131 0 126-64 232.5t-174 168.5-240 62q-197 0-337-135.5t-140-327.5q0-68 20-131h-141v648q0 26 17.5 43.5t43.5 17.5h1069q25 0 43-17.5t18-43.5zm-284-533q0-124-90.5-211.5t-218.5-87.5q-127 0-217.5 87.5t-90.5 211.5 90.5 211.5 217.5 87.5q128 0 218.5-87.5t90.5-211.5zm284-360v-165q0-28-20-48.5t-49-20.5h-174q-29 0-49 20.5t-20 48.5v165q0 29 20 49t49 20h174q29 0 49-20t20-49zm174-208v1142q0 81-58 139t-139 58h-1142q-81 0-139-58t-58-139v-1142q0-81 58-139t139-58h1142q81 0 139 58t58 139z"/></svg>
                                    </span>
                                    <span class="sf-feed-header-text">
                                        <?php echo $atts['header_text']; ?>
                                    </span>
                                </div>
                                <?php
                            }
                            
                            foreach ($resp->data as $post) {
                                ?>
                                <div class="instagram-feed-item instagram-feed-col-<?php echo $atts['columns']; ?>" style="padding:<?php echo $atts['padding']; ?>px;">
                                    <a href="<?php echo $post->link; ?>" target="_blank"><img src="<?php echo $post->images->standard_resolution->url; ?>" alt="<?php echo $post->caption->text; ?>" /></a>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                        <?php
                    }
                }
            } else {
                if ($user_tag) {
                    $url = 'https://api.instagram.com/v1/users/' . $user_id . '/media/recent/?access_token=' . $access_token;
                    $resp = json_decode($this->url_get_contents($url));

                    if ($resp->meta->code === 200) {
                        $width = 100 / $atts['columns'];
                        ?>
                        <div class="instagram-feed-wrapper">
                            <?php
                            if($atts['header']) {
                                ?>
                                <div class="instagram-feed-section-title">
                                    <span class="sf-feed-header-icon">
                                        <svg viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1490 1426v-648h-135q20 63 20 131 0 126-64 232.5t-174 168.5-240 62q-197 0-337-135.5t-140-327.5q0-68 20-131h-141v648q0 26 17.5 43.5t43.5 17.5h1069q25 0 43-17.5t18-43.5zm-284-533q0-124-90.5-211.5t-218.5-87.5q-127 0-217.5 87.5t-90.5 211.5 90.5 211.5 217.5 87.5q128 0 218.5-87.5t90.5-211.5zm284-360v-165q0-28-20-48.5t-49-20.5h-174q-29 0-49 20.5t-20 48.5v165q0 29 20 49t49 20h174q29 0 49-20t20-49zm174-208v1142q0 81-58 139t-139 58h-1142q-81 0-139-58t-58-139v-1142q0-81 58-139t139-58h1142q81 0 139 58t58 139z"/></svg>
                                    </span>
                                    <span class="sf-feed-header-text">
                                        <?php echo $atts['header_text']; ?>
                                    </span>
                                </div>
                                <?php
                            }
                            
                            if ($atts['profile']) {
                                $url2 = 'https://api.instagram.com/v1/users/' . $user_id . '/?access_token=' . $access_token;
                                $user_resp = json_decode($this->url_get_contents($url2));
                                if ($user_resp->meta->code === 200) {
                                    ?>
                                    <div class="instagram-feed-header">
                                        <a href="https://instagram.com/<?php echo $user_resp->data->username; ?>/" target="_blank">
                                            <div class="instagram-feed-header-profile-pic">
                                                <img src="<?php echo $user_resp->data->profile_picture; ?>" />
                                            </div>
                                            <div class="instagram-feed-header-profile-username">
                                                <p><?php echo '@' . $user_resp->data->username; ?></p>
                                            </div>
                                            <div class="instagram-feed-header-profile-bio">
                                                <p><?php echo $user_resp->data->bio; ?></p>
                                            </div>
                                        </a>
                                        <div style="clear:both;"></div>
                                    </div>
                                    <?php
                                }
                            }

                            $i = 1;
                            $tags = explode(',', $user_tag);
                            if (!$this->check_pro_key()) {
                                $tags_use[] = $tags[0];
                            } else {
                                $tags_use = $tags;
                            }

                            foreach ($resp->data as $post) {
                                $has_tag = false;
                                if ($i <= $atts['count']) {
                                    foreach ($tags_use as $tag) {
                                        if (in_array($tag, $post->tags)) {
                                            $has_tag = true;
                                        } else {
                                            if (!$has_tag) {
                                                $has_tag = false;
                                            }
                                        }
                                    }
                                    if ($has_tag) {
                                        $i++;
                                        ?>
                                        <div class="instagram-feed-item instagram-feed-col-<?php echo $atts['columns']; ?>" style="padding:<?php echo $atts['padding']; ?>px;">
                                            <a href="<?php echo $post->link; ?>" target="_blank"><img src="<?php echo $post->images->standard_resolution->url; ?>" alt="<?php echo $post->caption->text; ?>" /></a>
                                        </div>
                                        <?php
                                    }
                                } else {
                                    break;
                                }
                            }
                            ?>
                            <div style="clear:both;"></div>
                            <?php
                            if ($atts['more_link']) {
                                ?>
                                <div class="sf-feed-more-link-wrapper">
                                    <a class="sf-feed-more-link" href="https://instagram.com/<?php echo $user_resp->data->username; ?>/" target="_blank"><?php echo $atts['more_link_text']; ?></a>
                                </div>
                                <?php
                            }
                            ?>
                            <div style="clear:both;"></div>
                        </div>
                        <?php
                    }
                } else {
                    $url = 'https://api.instagram.com/v1/users/' . $user_id . '/media/recent/?count=' . $atts['count'] . '&access_token=' . $access_token;
                    $resp = json_decode($this->url_get_contents($url));

                    if ($resp->meta->code === 200) {
                        $width = 100 / $atts['columns'];
                        ?>
                        <div class="instagram-feed-wrapper">
                            <?php
                            if($atts['header']) {
                                ?>
                                <div class="instagram-feed-section-title">
                                    <span class="sf-feed-header-icon">
                                        <svg viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1490 1426v-648h-135q20 63 20 131 0 126-64 232.5t-174 168.5-240 62q-197 0-337-135.5t-140-327.5q0-68 20-131h-141v648q0 26 17.5 43.5t43.5 17.5h1069q25 0 43-17.5t18-43.5zm-284-533q0-124-90.5-211.5t-218.5-87.5q-127 0-217.5 87.5t-90.5 211.5 90.5 211.5 217.5 87.5q128 0 218.5-87.5t90.5-211.5zm284-360v-165q0-28-20-48.5t-49-20.5h-174q-29 0-49 20.5t-20 48.5v165q0 29 20 49t49 20h174q29 0 49-20t20-49zm174-208v1142q0 81-58 139t-139 58h-1142q-81 0-139-58t-58-139v-1142q0-81 58-139t139-58h1142q81 0 139 58t58 139z"/></svg>
                                    </span>
                                    <span class="sf-feed-header-text">
                                        <?php echo $atts['header_text']; ?>
                                    </span>
                                </div>
                                <?php
                            }
                            
                            if ($atts['profile']) {
                                $url2 = 'https://api.instagram.com/v1/users/' . $user_id . '/?access_token=' . $access_token;
                                $user_resp = json_decode($this->url_get_contents($url2));
                                if ($user_resp->meta->code === 200) {
                                    ?>
                                    <div class="instagram-feed-header">
                                        <a href="https://instagram.com/<?php echo $user_resp->data->username; ?>/" target="_blank">
                                            <div class="instagram-feed-header-profile-pic">
                                                <img src="<?php echo $user_resp->data->profile_picture; ?>" />
                                            </div>
                                            <div class="instagram-feed-header-profile-username">
                                                <p><?php echo '@' . $user_resp->data->username; ?></p>
                                            </div>
                                            <div class="instagram-feed-header-profile-bio">
                                                <p><?php echo $user_resp->data->bio; ?></p>
                                            </div>
                                        </a>
                                        <div style="clear:both;"></div>
                                    </div>
                                    <?php
                                }
                            }
                            foreach ($resp->data as $post) {
                                ?>
                                <div class="instagram-feed-item instagram-feed-col-<?php echo $atts['columns']; ?>" style="padding:<?php echo $atts['padding']; ?>px;">
                                    <a href="<?php echo $post->link; ?>" target="_blank"><img src="<?php echo $post->images->standard_resolution->url; ?>" alt="<?php echo $post->caption->text; ?>" /></a>
                                </div>
                                <?php
                            }
                            ?>
                            <div style="clear:both;"></div>
                            <?php
                            if ($atts['more_link']) {
                                ?>
                                <div class="sf-feed-more-link-wrapper">
                                    <a class="sf-feed-more-link" href="https://instagram.com/<?php echo $user_resp->data->username; ?>/" target="_blank"><?php echo $atts['more_link_text']; ?></a>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                        <?php
                    }
                }
            }
            $output_string = ob_get_contents();

            $cache_file_temp = plugin_dir_path(__FILE__) . 'instagram-feed-cache-temp.txt';
            file_put_contents($cache_file_temp, $output_string, LOCK_EX);
            rename($cache_file_temp, $cache_file);

            ob_end_clean();
        }
        return $output_string;
    }

    //Facebook Feed Display Shortcode
    public function facebook_feed_display($atts) {
        global $wpdb;

        $cache_file = plugin_dir_path(__FILE__) . 'facebook-feed-cache.txt';

        if (get_option("bvads_facebook_settings_change") == 1) {
            $ignore_cache = true;

            update_option("bvads_facebook_settings_change", 0);
        } else {
            $ignore_cache = false;
        }

        if (!$ignore_cache && file_exists($cache_file) && (filemtime($cache_file) > (time() - 60 * 15 ))) {
            //read from cache
            //less than 15 minutes old
            $output_string = $this->cache_file_get_contents($cache_file);
        } else {
            //cache outdated
            ob_start();

            if ($number_items = get_option("bvads_social_feed_facebook_number_items")) {
                if (!empty($number_items)) {
                    $count_default = $number_items;
                } else {
                    $count_default = 2;
                }
            } else {
                $count_default = 2;
            }

            if ($show_header = get_option("bvads_social_feed_facebook_show_header")) {
                $header_default = 1;
            } else {
                $header_default = 0;
            }

            if (get_option("bvads_social_feed_facebook_show_more_link")) {
                $show_more = get_option("bvads_social_feed_facebook_show_more_link");
            } else {
                $show_more = 0;
            }
            
            if (get_option("bvads_social_feed_facebook_header_text")) {
                $header_text = get_option("bvads_social_feed_facebook_header_text");
            } else {
                $header_text = 'Recent From Facebook';
            }
            
            if (get_option("bvads_social_feed_facebook_more_link_text")) {
                $more_link_text = get_option("bvads_social_feed_facebook_more_link_text");
            } else {
                $more_link_text = 'read more';
            }

            if (get_option("bvads_social_feed_facebook_header_background")) {
                $header_background = get_option("bvads_social_feed_facebook_header_background");
            } else {
                $header_background = '#B6B6B6';
            }

            if (get_option("bvads_social_feed_facebook_header_font_color")) {
                $header_font_color = get_option("bvads_social_feed_facebook_header_font_color");
            } else {
                $header_font_color = '#fff';
            }

            if (get_option("bvads_social_feed_facebook_border_bottom")) {
                $border_bottom = get_option("bvads_social_feed_facebook_border_bottom");
            } else {
                $border_bottom = '#ccc';
            }

            if (get_option("bvads_social_feed_facebook_border_bottom_weight")) {
                $border_bottom_weight = get_option("bvads_social_feed_facebook_border_bottom_weight");
            } else {
                $border_bottom_weight = 1;
            }

            if (get_option("bvads_social_feed_facebook_more_button_background")) {
                $btn_background = get_option("bvads_social_feed_facebook_more_button_background");
            } else {
                $btn_background = '#ccc';
            }

            if (get_option("bvads_social_feed_facebook_more_button_font_color")) {
                $btn_font_color = get_option("bvads_social_feed_facebook_more_button_font_color");
            } else {
                $btn_font_color = '#fff';
            }

            if (get_option("bvads_social_feed_facebook_more_button_font_color_hover")) {
                $btn_font_color_hover = get_option("bvads_social_feed_facebook_more_button_font_color_hover");
            } else {
                $btn_font_color_hover = '#fff';
            }

            if (get_option("bvads_social_feed_facebook_more_button_background_hover")) {
                $btn_background_hover = get_option("bvads_social_feed_facebook_more_button_background_hover");
            } else {
                $btn_background_hover = '#8C8C8C';
            }

            $page_id = get_option("bvads_social_feed_facebook_page_id");

            $atts = shortcode_atts(
                    array(
                'count' => $count_default,
                'header' => $header_default,
                'more_link' => $show_more,
                'header_text' => $header_text,
                'more_link_text' => $more_link_text
                    ), $atts, 'bvd-facebook-feed');

            if (!$this->check_pro_key()) {
                if ($atts['count'] > 2) {
                    $atts['count'] = 2;
                }
            }

            //Check Facebook creds
            $request = SFR_URL_FACEBOOK . "?sfr_uuid=" . get_option("bvads_social_feed_uuid") . "&callback=" . $this->callback_url() . "&action=verify";
            $data = $this->url_get_contents($request);
            $data = json_decode($data);
            //print_r($data);
            if ($data->verify->is_valid) { //Facebook creds are valid
                $access_token = get_option("bvads_facebook_oauth_token");
            } else {
                $request = SFR_URL_FACEBOOK . "?sfr_uuid=" . get_option("bvads_social_feed_uuid") . "&callback=" . $this->callback_url() . "&action=get_app_token";
                $data = $this->url_get_contents($request);
                $data = json_decode($data);

                $access_token = $data->token;
            }

            $total_limit = $atts['count'] + 10;
            $ch = curl_init("https://graph.facebook.com/" . $page_id . "/feed?access_token=" . $access_token . "&limit=" . $total_limit . "&fields=from,type,object_id,picture,full_picture,story,message,description,link,created_time");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $resp = curl_exec($ch);
            curl_close($ch);
            $resp = json_decode($resp, true);
            //echo 'Response:<br/><br/>';
            ?>
            <div class="sf-facebook-feed-container">
                <style>
                    .sf-facebook-feed-header {
                        background-color: <?php echo $header_background; ?>;
                        color: <?php echo $header_font_color; ?>;
                    }

                    .sf-facebook-feed-header .sf-feed-header-icon svg {
                        fill: <?php echo $header_font_color; ?>;
                    }

                    .sf-facebook-feed-item {
                        border-bottom-color: <?php echo $border_bottom; ?>;
                        border-bottom-width: <?php echo $border_bottom_weight; ?>px;
                    }

                    .sf-facebook-feed-container a.sf-feed-more-link {
                        background-color: <?php echo $btn_background; ?>;
                        color: <?php echo $btn_font_color; ?>;
                    }

                    .sf-facebook-feed-container a.sf-feed-more-link:hover {
                        background-color: <?php echo $btn_background_hover; ?>;
                        color: <?php echo $btn_font_color_hover; ?>;
                    }
                </style>
                <?php
                if ($atts['header']) {
                    ?>
                    <div class="sf-facebook-feed-header">
                        <span class="sf-feed-header-icon">
                            <svg viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1376 128q119 0 203.5 84.5t84.5 203.5v960q0 119-84.5 203.5t-203.5 84.5h-188v-595h199l30-232h-229v-148q0-56 23.5-84t91.5-28l122-1v-207q-63-9-178-9-136 0-217.5 80t-81.5 226v171h-200v232h200v595h-532q-119 0-203.5-84.5t-84.5-203.5v-960q0-119 84.5-203.5t203.5-84.5h960z"/></svg>
                        </span>
                        <span class="sf-feed-header-text">
                            <?php echo $atts['header_text']; ?>
                        </span>
                    </div>
                    <?php
                }
                ?>
                <div class="sf-facebook-feed-item-wrapper">
                    <?php
                    $i = 1;
                    foreach ($resp['data'] as $feed) {
                        if ($feed['from']['id'] == $page_id) {
                            if ($i <= $atts['count']) {
                                if (isset($feed['full_picture'])) {
                                    $image = $feed['full_picture'];
                                } elseif (isset($feed['picture'])) {
                                    $image = $feed['picture'];
                                } else {
                                    $image = '';
                                }

                                if (array_key_exists('story', $feed)) {
                                    $content = $feed['story'];
                                } else {
                                    $content = $feed['message'];
                                }

                                if ($feed['type'] == 'link') {
                                    if (isset($feed['description'])) {
                                        if (empty($content)) {
                                            $content .= '<span class="sf-facebook-feed-item-description-no-top">';
                                        } else {
                                            $content .= '<span class="sf-facebook-feed-item-description">';
                                        }
                                        $content .= $feed['description'];
                                        $content .= '</span>';
                                    }
                                }

                                $pos = strpos($content, 'http://');
                                if ($pos !== false) {
                                    $pos2 = strpos($content, ' ', $pos);
                                    if ($pos2 !== false) {
                                        $length = $pos2 - $pos;
                                        $url = substr($content, $pos, $length);
                                    } else {
                                        $pos3 = strpos($content, '. ', $pos);
                                        if ($pos3 !== false) {
                                            $length = $pos3 - $pos;
                                            $url = substr($content, $pos, $length);
                                        } else {
                                            $url = substr($content, $pos);
                                        }
                                    }
                                    $replace = '<a href="' . $url . '" target="_blank">' . $url . '</a>';
                                    $content = str_replace($url, $replace, $content);
                                }

                                $pos = strpos($content, 'https://');
                                if ($pos !== false) {
                                    $pos2 = strpos($content, ' ', $pos);
                                    if ($pos2 !== false) {
                                        $length = $pos2 - $pos;
                                        $url = substr($content, $pos, $length);
                                    } else {
                                        $pos3 = strpos($content, '. ', $pos);
                                        if ($pos3 !== false) {
                                            $length = $pos3 - $pos;
                                            $url = substr($content, $pos, $length);
                                        } else {
                                            $url = substr($content, $pos);
                                        }
                                    }
                                    $replace = '<a href="' . $url . '" target="_blank">' . $url . '</a>';
                                    $content = str_replace($url, $replace, $content);
                                }

                                $created_date = date('F j', strtotime($feed['created_time']));
                                $post_link = $feed['link'];
                                ?>
                                <div class="sf-facebook-feed-item">
                                    <?php
                                    if (!empty($image)) {
                                        ?>
                                        <div class="sf-facebook-feed-item-photo">
                                            <a href="<?php echo $post_link; ?>" target="_blank"><img src="<?php echo $image; ?>" /></a>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                    <div class="sf-facebook-feed-item-text-wrapper">
                                        <div class="sf-facebook-feed-item-content">
                                            <p><?php echo $content; ?></p>
                                        </div>
                                        <?php
                                        if ($feed['type'] == 'link') {
                                            ?>
                                            <div class="sf-facebook-feed-item-link">
                                                <a href="<?php echo $post_link; ?>"><?php echo $post_link; ?></a>
                                            </div>
                                            <?php
                                        }
                                        ?>
                                        <div class="sf-facebook-feed-item-date">
                                            <p><?php echo $created_date; ?></p>
                                        </div>
                                    </div>
                                    <div style="clear:both;"></div>
                                </div>
                                <?php
                            }
                            $i++;
                        }
                    }
                    ?>
                </div>
                <div style="clear:both;"></div>
                <?php
                if ($atts['more_link']) {
                    ?>
                    <div class="sf-feed-more-link-wrapper">
                        <a class="sf-feed-more-link" href="https://facebook.com/<?php echo $page_id; ?>/" target="_blank"><?php echo $atts['more_link_text']; ?></a>
                    </div>
                    <?php
                }
                ?>
                <div style="clear:both;"></div>
            </div>
            <?php
            $output_string = ob_get_contents();

            $cache_file_temp = plugin_dir_path(__FILE__) . 'facebook-feed-cache-temp.txt';
            file_put_contents($cache_file_temp, $output_string, LOCK_EX);
            rename($cache_file_temp, $cache_file);

            ob_end_clean();
        }
        return $output_string;
    }

    //Twitter Feed Display Shortcode
    public function twitter_feed_display($atts) {
        global $wpdb;

        $cache_file = plugin_dir_path(__FILE__) . 'twitter-feed-cache.txt';

        if (get_option("bvads_twitter_settings_change") == 1) {
            $ignore_cache = true;

            update_option("bvads_twitter_settings_change", 0);
        } else {
            $ignore_cache = false;
        }

        if (!$ignore_cache && file_exists($cache_file) && (filemtime($cache_file) > (time() - 60 * 15 ))) {
            //read from cache
            //less than 15 minutes old
            $output_string = $this->cache_file_get_contents($cache_file);
        } else {
            //cache outdated
            ob_start();

            if ($number_items = get_option("bvads_social_feed_twitter_number_items")) {
                if (!empty($number_items)) {
                    $count_default = $number_items;
                } else {
                    $count_default = 2;
                }
            } else {
                $count_default = 2;
            }

            if ($show_header = get_option("bvads_social_feed_twitter_show_header")) {
                $header_default = 1;
            } else {
                $header_default = 0;
            }

            if (get_option("bvads_social_feed_twitter_show_more_link")) {
                $show_more = get_option("bvads_social_feed_twitter_show_more_link");
            } else {
                $show_more = 0;
            }
            
            if (get_option("bvads_social_feed_twitter_header_text")) {
                $header_text = get_option("bvads_social_feed_twitter_header_text");
            } else {
                $header_text = 'Recent From Twitter';
            }
            
            if (get_option("bvads_social_feed_twitter_more_link_text")) {
                $more_link_text = get_option("bvads_social_feed_twitter_more_link_text");
            } else {
                $more_link_text = 'read more';
            }

            if (get_option("bvads_social_feed_twitter_header_background")) {
                $header_background = get_option("bvads_social_feed_twitter_header_background");
            } else {
                $header_background = '#B6B6B6';
            }

            if (get_option("bvads_social_feed_twitter_header_font_color")) {
                $header_font_color = get_option("bvads_social_feed_twitter_header_font_color");
            } else {
                $header_font_color = '#fff';
            }

            if (get_option("bvads_social_feed_twitter_border_bottom")) {
                $border_bottom = get_option("bvads_social_feed_twitter_border_bottom");
            } else {
                $border_bottom = '#ccc';
            }

            if (get_option("bvads_social_feed_twitter_border_bottom_weight")) {
                $border_bottom_weight = get_option("bvads_social_feed_twitter_border_bottom_weight");
            } else {
                $border_bottom_weight = 1;
            }

            if (get_option("bvads_social_feed_twitter_more_button_background")) {
                $btn_background = get_option("bvads_social_feed_twitter_more_button_background");
            } else {
                $btn_background = '#ccc';
            }

            if (get_option("bvads_social_feed_twitter_more_button_font_color")) {
                $btn_font_color = get_option("bvads_social_feed_twitter_more_button_font_color");
            } else {
                $btn_font_color = '#fff';
            }

            if (get_option("bvads_social_feed_twitter_more_button_font_color_hover")) {
                $btn_font_color_hover = get_option("bvads_social_feed_twitter_more_button_font_color_hover");
            } else {
                $btn_font_color_hover = '#fff';
            }

            if (get_option("bvads_social_feed_twitter_more_button_background_hover")) {
                $btn_background_hover = get_option("bvads_social_feed_twitter_more_button_background_hover");
            } else {
                $btn_background_hover = '#8C8C8C';
            }

            $user_name = get_option("bvads_twitter_screenname");

            $atts = shortcode_atts(
                    array(
                'count' => $count_default,
                'user_name' => $user_name,
                'header' => $header_default,
                'more_link' => $show_more,
                'header_text' => $header_text,
                'more_link_text' => $more_link_text
                    ), $atts, 'bvd-twitter-feed');

            if (!$this->check_pro_key()) {
                if ($atts['count'] > 2) {
                    $atts['count'] = 2;
                }
            }

            $request = SFR_URL_TWITTER . "?sfr_uuid=" . get_option("bvads_social_feed_uuid") . "&callback=" . $this->callback_url() . "&action=get_feed&item_count=" . $atts['count'] . "&user_name=" . $atts['user_name'];
            $data = $this->url_get_contents($request);
            $data = json_decode($data, true);
            /* echo 'Response:<br/><br/>';
              echo '<pre>';
              print_r($data);
              echo '</pre>'; */
            ?>
            <div class="sf-twitter-feed-container">
                <style>
                    .sf-twitter-feed-header {
                        background-color: <?php echo $header_background; ?>;
                        color: <?php echo $header_font_color; ?>;
                    }

                    .sf-twitter-feed-header .sf-feed-header-icon svg {
                        fill: <?php echo $header_font_color; ?>;
                    }

                    .sf-twitter-feed-item {
                        border-bottom-color: <?php echo $border_bottom; ?>;
                        border-bottom-width: <?php echo $border_bottom_weight; ?>px;
                    }

                    .sf-twitter-feed-container a.sf-feed-more-link {
                        background-color: <?php echo $btn_background; ?>;
                        color: <?php echo $btn_font_color; ?>;
                    }

                    .sf-twitter-feed-container a.sf-feed-more-link:hover {
                        background-color: <?php echo $btn_background_hover; ?>;
                        color: <?php echo $btn_font_color_hover; ?>;
                    }
                </style>
                <?php
                if ($atts['header']) {
                    ?>
                    <div class="sf-twitter-feed-header">
                        <span class="sf-feed-header-icon">
                            <svg viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1408 610q-56 25-121 34 68-40 93-117-65 38-134 51-61-66-153-66-87 0-148.5 61.5t-61.5 148.5q0 29 5 48-129-7-242-65t-192-155q-29 50-29 106 0 114 91 175-47-1-100-26v2q0 75 50 133.5t123 72.5q-29 8-51 8-13 0-39-4 21 63 74.5 104t121.5 42q-116 90-261 90-26 0-50-3 148 94 322 94 112 0 210-35.5t168-95 120.5-137 75-162 24.5-168.5q0-18-1-27 63-45 105-109zm256-194v960q0 119-84.5 203.5t-203.5 84.5h-960q-119 0-203.5-84.5t-84.5-203.5v-960q0-119 84.5-203.5t203.5-84.5h960q119 0 203.5 84.5t84.5 203.5z"/></svg>
                        </span>
                        <span class="sf-feed-header-text">
                            <?php echo $atts['header_text']; ?>
                        </span>
                    </div>
                    <?php
                }
                ?>
                <div class="sf-twitter-feed-item-wrapper">
                    <?php
                    $i = 1;
                    if ($data['feed']) {
                        foreach ($data['feed'] as $feed) {
                            if ($i <= $atts['count']) {
                                if (array_key_exists('media', $feed['entities'])) {
                                    $image = $feed['entities']['media'][0]['media_url_https'];
                                    $link = $feed['entities']['media'][0]['url'];
                                } else {
                                    $image = '';
                                    $link = $feed['entities']['urls']['url'];
                                }

                                $created_date = date('F j', strtotime($feed['created_at']));

                                $text = $feed['text'];

                                if (array_key_exists('urls', $feed['entities'])) {
                                    foreach ($feed['entities']['urls'] as $feed_url) {
                                        $length = $feed_url['indices'][1] - $feed_url['indices'][0];
                                        $url = substr($text, $feed_url['indices'][0], $length);
                                        $replace = '<a href="' . $feed_url['expanded_url'] . '" target="_blank">' . $url . '</a>';
                                        $text = str_replace($url, $replace, $text);
                                    }
                                }

                                if (array_key_exists('user_mentions', $feed['entities'])) {
                                    foreach ($feed['entities']['user_mentions'] as $user_mentions) {
                                        $length = $user_mentions['indices'][1] - $user_mentions['indices'][0];
                                        $user = substr($text, $user_mentions['indices'][0], $length);
                                        $replace = '<a href="https://twitter.com/' . $user_mentions['screen_name'] . '" target="_blank">' . $user . '</a>';
                                        $text = str_replace($user, $replace, $text);
                                    }
                                }

                                if (array_key_exists('hashtags', $feed['entities'])) {
                                    foreach ($feed['entities']['hashtags'] as $hashtags) {
                                        $length = $hashtags['indices'][1] - $hashtags['indices'][0];
                                        $hashtag = substr($text, $hashtags['indices'][0], $length);
                                        $replace = '<a href="https://twitter.com/hashtag/' . $hashtags['text'] . '?src=hash" target="_blank">' . $hashtag . '</a>';
                                        $text = str_replace($hashtag, $replace, $text);
                                    }
                                }

                                $pos = strpos($text, 'http://t.co/');
                                if ($pos !== false) {
                                    $url = substr($text, $pos);
                                    $replace = '<a href="' . $url . '" target="_blank">' . $url . '</a>';
                                    $text = str_replace($url, $replace, $text);
                                }
                                ?>
                                <div class="sf-twitter-feed-item">
                                    <?php
                                    if (!empty($image)) {
                                        ?>
                                        <div class="sf-twitter-feed-item-photo">
                                            <a href="<?php echo $link; ?>" target="_blank"><img src="<?php echo $image; ?>" /></a>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                    <div class="sf-twitter-feed-item-text-wrapper">
                                        <div class="sf-twitter-feed-item-content">
                                            <p><?php echo $text; ?></p>
                                        </div>
                                        <div class="sf-twitter-feed-item-date">
                                            <p><?php echo $created_date; ?></p>
                                        </div>
                                    </div>
                                    <div style="clear:both;"></div>
                                </div>
                                <?php
                            }
                            $i++;
                        }
                    }
                    ?>
                </div>
                <div style="clear:both;"></div>
                <?php
                if ($atts['more_link']) {
                    ?>
                    <div class="sf-feed-more-link-wrapper">
                        <a class="sf-feed-more-link" href="https://twitter.com/<?php echo $user_name; ?>/" target="_blank"><?php echo $atts['more_link_text']; ?></a>
                    </div>
                    <?php
                }
                ?>
                <div style="clear:both;"></div>
            </div>
            <?php
            $output_string = ob_get_contents();

            $cache_file_temp = plugin_dir_path(__FILE__) . 'twitter-feed-cache-temp.txt';
            file_put_contents($cache_file_temp, $output_string, LOCK_EX);
            rename($cache_file_temp, $cache_file);

            ob_end_clean();
        }
        return $output_string;
    }

    function showAdminMessages() {
        //Check Facebook creds
        $request = SFR_URL_FACEBOOK . "?sfr_uuid=" . get_option("bvads_social_feed_uuid") . "&callback=" . $this->callback_url() . "&action=verify";
        $data = $this->url_get_contents($request);
        $data = json_decode($data);
        //print_r($data);
        if (!$data->no_token) {
            if (!$data->verify->is_valid) {
                echo '<div id="message" class="updated"><p><strong>Social Feeds: Facebook token has expired. Your Facebook feed is still working but you may want to go to the <a href="' . get_admin_url(null, 'admin.php?page=bvd-social-feeds-facebook&tab=basic-settings') . '">Facebook page</a> to renew your token.</strong></p></div>';
            }
        }
    }

}

$bvdSF = new bvdSocialFeeds();
