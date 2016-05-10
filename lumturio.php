<?php
/**
 * Plugin Name: Lumturio WP Monitor
 * Plugin URI: https://lumturio.com
 * Description: Lumturio offers users powerful and reliable tools to monitor websites.
 * Version: 1.0.1
 * Author: Team Lumturio
 * Author URI: https://lumturio.com
 * License: GPL2
 */

define( 'WP_DEBUG', false );

/*
 * Plugin installation:
 * - Creation of uuid
 * - Creation of encryption token
 */
register_activation_hook( __FILE__, 'install_lumturio_plugin' );
function install_lumturio_plugin() {
    update_option( 'lumturio_site_uuid', lumturio_makeToken() );
    update_option( 'lumturio_site_encryption_token', lumturio_makeToken() );

    flush_rewrite_rules();
}

/*
 * Plugin deactivation, time to clean up our mess
 */
register_deactivation_hook( __FILE__, 'unistall_lumturio_plugin' );
function unistall_lumturio_plugin() {
    delete_option('lumturio_site_uuid');
    delete_option('lumturio_site_encryption_token');

    flush_rewrite_rules();
}

/*
 * Add the rewrite rule below so we can catch requires on
 * http://some.website/admin/reports/system_status/<unique_token>
 */
add_action('init', 'lumturio_plugin_add_endpoint', 0);
function lumturio_plugin_add_endpoint(){
    add_rewrite_rule('^admin/reports/system_status/?([a-zA-Z\d]+)?/?','index.php?__lumturio_api=1&lumturio_token=$matches[1]','top');


   /**************************************************************************
   * Flushes the permalink structure.
   * flush_rules is an extremely costly function in terms of performance, and
   * should only be run when changing the rule.
   *
   * @see http://codex.wordpress.org/Rewrite_API/flush_rules
   **************************************************************************/

   $rules = $GLOBALS['wp_rewrite']->wp_rewrite_rules();
   if(!isset($rules['^admin/reports/system_status/?([a-zA-Z\d]+)?/?'])) {
	global $wp_rewrite;
	$wp_rewrite->flush_rules();
   }
}

/*
 * If the rewrite rule above worked, put the values in these query vars
 * so we can access them more easily at a later stage
 */
add_filter('query_vars', 'lumturio_plugin_add_query_vars', 0);
function lumturio_plugin_add_query_vars($vars){
    $vars[] = '__lumturio_api';
    $vars[] = 'lumturio_token';
    return $vars;
}


/*
 * Check if the parameter is added by the query rules above
 */
add_action('parse_request', 'lumturio_plugin_listener', 0);
function lumturio_plugin_listener() {
    global $wp;
    if(isset($wp->query_vars['__lumturio_api'])) {
        $token = $wp->query_vars['lumturio_token'];
        if($token == get_option('lumturio_site_uuid')) {
            lumturio_system_report(true);
            die();
        } else {
            status_header( 404 );
            nocache_headers();
            include( get_query_template( '404' ) );
            die();
        }
    }
}

/*
 * This is what this whole module is about .. print out
 * everything we want to know about this site
 */
function lumturio_system_report($return_json = false) {
    $all_plugins = lumturio_get_all_plugins();

    foreach($all_plugins as $plugin_full_path => $plugin_data) {
        $has_update_data = lumturio_get_update_data_version($plugin_full_path);
        if($has_update_data) {
            $all_plugins[$plugin_full_path]['has_update'] = true;
            $all_plugins[$plugin_full_path]['update_data'] = $has_update_data;
        } else {
            $all_plugins[$plugin_full_path]['has_update'] = false;
        }
    }

    $no_keys_array = array();
    foreach($all_plugins as $plugin) {
        $no_keys_array[sanitize_title($plugin['name'])] = $plugin;
    }
    $all_plugins = $no_keys_array;


    global $wp_version;
    $res = array("engine" => "WORDPRESS", "wordpress_version" => $wp_version, "data" => $all_plugins, "status_message" => lumturio_get_update_message(), "last_checked" => lumturio_get_update_data_last_checked());

    if($return_json) {
        header('Content-Type: application/json');
        if (extension_loaded('mcrypt')) {

            $res['data'] = lumturio_plugin_encrypt(json_encode(array("system_status" => $res['data'])));
            $res['system_status'] = "encrypted";
            echo json_encode($res);
        } else {
            $res['system_status'] = $res['data'];
            unset($res['data']);
            echo json_encode($res);
        }
        die();
    } else {
        return $res;
    }

}

/*
 * Returns an array with the path to the enabled script
 * ["advanced-categories-widget/index.php","akismet/akismet.php","contact-form-7/wp-contact-form-7.php"]
 */
function lumturio_get_active_plugins() {
    return get_option('active_plugins');
}


function lumturio_get_all_plugins() {
    if ( ! function_exists( 'get_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $all_plugins = get_plugins();
    $active_plugins = lumturio_get_active_plugins();

    $return_array = array();
    //now that we have all plugins, let's kick out those who are not activated
    foreach($all_plugins as $plugin => $plugin_data) {
        if(in_array($plugin, $active_plugins)) {
            $return_array[$plugin] = array("name" => $plugin_data['Name'], "version" => $plugin_data['Version'], "enabled" => true);
        } else {
            $return_array[$plugin] = array("name" => $plugin_data['Name'], "version" => $plugin_data['Version'], "enabled" => false);
        }
    }

    return $return_array;
}

/*
 * returns array with counts:plugins,themes,wordpress,translations,total and title:message
 */
function lumturio_get_update_message() {
    $all_counters = wp_get_update_data();
    return $all_counters['title'];
}

function lumturio_get_update_data_version($plugin_full_path) {
    $update_data = get_site_transient('update_plugins');

    if(!isset($update_data->response))
        return false;

    $response = $update_data->response;

    foreach($response as $plugin) {
        if($plugin->plugin == $plugin_full_path) {
            return array("new_version" => $plugin->new_version, "plugin_url" => $plugin->url, "download" => $plugin->package);
        }
    }

    return false;
}

/*
 * Returns int last unix time checked for updates
 */
function lumturio_get_update_data_last_checked() {
    $update_data = get_site_transient('update_plugins');
    return $update_data->last_checked;
}

/*
 * All code below is to manage the WP admin interface
 */
add_action('admin_init', 'lumturio_plugin_settings' );
function lumturio_plugin_settings() {
    register_setting( 'lumturio-plugin-settings-group', 'lumturio_site_uuid' );
    register_setting( 'lumturio-plugin-settings-group', 'lumturio_site_encryption_token' );
}


add_action('admin_menu', 'lumturio_plugin_menu');
function lumturio_plugin_menu() {
    add_menu_page('Lumturio Settings', 'Lumturio', 'administrator', 'lumturio-plugin-settings', 'lumturio_settings_page', 'dashicons-admin-network');
}

function lumturio_settings_page() { ?>
    <div class="wrap">
    <h2>Lumturio Site Manager</h2>
    <form method="post" action="options.php">
        <?php //settings_fields( 'lumturio-plugin-settings-group' ); ?>
        <?php //do_settings_sections( 'lumturio-plugin-settings-group' ); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Site UUID</th>
                <td><span><?php echo esc_attr( get_option('lumturio_site_uuid') ) . "-" .esc_attr( get_option('lumturio_site_encryption_token') ); ?></span></td>
            </tr>
        </table>

        <table>
            <thead>
                <th>Plugin Name</th>
                <th>Version</th>
                <th>Target</th>
                <th>Enabled</th>
            </thead>
            <tbody>
            <?php foreach(lumturio_system_report()['data'] as $plugin) { ?>
            <tr>
                <td><?php echo $plugin['name'];?></td>
                <td><?php echo $plugin['version'];?></td>
                <td><?php if( $plugin['has_update'] ) { echo "<span style='color:red'>" . $plugin['update_data']['new_version'] . "</span>"; } else { echo $plugin['version'];}?></td>
                <td><?php if($plugin['enabled']) { echo "<span style='color:red'>YES</span>"; } else { echo "NO"; };?></td>
            </tr>
        <?php } ?>
            </tbody>
        </table>
        <?php //submit_button(); ?>
    </form>
    </div><?php
    flush_rewrite_rules();
}

/*
 * Helper function to create 'Random' tokens when plugin is activated.
 */
function lumturio_makeToken() {
    $chars = array_merge(range(0, 9),
        range('a', 'z'),
        range('A', 'Z'),
        range(0, 99));

    shuffle($chars);

    $token = "";
    for ($i = 0; $i < 8; $i++) {
        $token .= $chars[$i];
    }

    return $token;
}

/**
 * Encrypt a plaintext message.
 */
function lumturio_plugin_encrypt($plaintext) {
    $key = hash("SHA256", esc_attr( get_option('lumturio_site_encryption_token') ), TRUE);

    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

    $plaintext_utf8 = utf8_encode($plaintext);

    $ciphertext = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $plaintext_utf8, MCRYPT_MODE_CBC, $iv);

    $ciphertext = $iv . $ciphertext;

    return base64_encode($ciphertext);
}
