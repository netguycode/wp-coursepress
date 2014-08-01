<?php
/*
  Plugin Name: CoursePress
  Plugin URI: http://premium.wpmudev.org/project/coursepress/
  Description: CoursePress turns WordPress into a powerful online learning platform. Set up online courses by creating learning units with quiz elements, video, audio etc. You can also assess student work, sell your courses and much much more.
  Author: WPMU DEV
  Author URI: http://premium.wpmudev.org
  Developers: Marko Miljus ( https://twitter.com/markomiljus ), Rheinard Korf ( https://twitter.com/rheinardkorf )
  Version: 1.0.3b
  TextDomain: cp
  Domain Path: /languages/
  WDP ID: N/A
  License: GNU General Public License ( Version 2 - GPLv2 )

  Copyright 2014 Incsub ( http://incsub.com )

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License ( Version 2 - GPLv2 ) as published by
  the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

if ( !defined('ABSPATH') )
    exit; // Exit if accessed directly
// Load the common functions
require_once( 'includes/functions.php' );

if ( !class_exists('CoursePress') ) {

    class CoursePress {

        private static $instance = null;
        var $version = '1.0.3b';
        var $name = 'CoursePress';
        var $dir_name = 'coursepress';
        var $location = '';
        var $plugin_dir = '';
        var $plugin_url = '';
        public $marketpress_active = false;

        function __construct() {

//setup our variables
            $this->init_vars();

//register themes directory
            $this->register_theme_directory();

//Register Globals
            $GLOBALS['plugin_dir'] = $this->plugin_dir;
            $GLOBALS['course_slug'] = $this->get_course_slug();
            $GLOBALS['units_slug'] = $this->get_units_slug();
            $GLOBALS['notifications_slug'] = $this->get_notifications_slug();
            $GLOBALS['module_slug'] = $this->get_module_slug();
            $GLOBALS['instructor_profile_slug'] = $this->get_instructor_profile_slug();
            $GLOBALS['enrollment_process_url'] = $this->get_enrollment_process_slug(true);
            $GLOBALS['signup_url'] = $this->get_signup_slug(true);

//Install plugin
            register_activation_hook(__FILE__, array( $this, 'install' ));

            global $last_inserted_unit_id; //$last_inserted_module_id
            global $last_inserted_front_page_module_id; //$last_inserted_module_id

            add_theme_support('post-thumbnails');

//Administration area
            if ( is_admin() ) {

// Support for WPMU DEV Dashboard plugin
                include_once( $this->plugin_dir . 'includes/external/dashboard/wpmudev-dash-notification.php' );

// Course search
                require_once( $this->plugin_dir . 'includes/classes/class.coursesearch.php' );

// Notificatioon search
                require_once( $this->plugin_dir . 'includes/classes/class.notificationsearch.php' );

// Contextual help
//require_once( $this->plugin_dir . 'includes/classes/class.help.php' );
// Search Students class
                require_once( $this->plugin_dir . 'includes/classes/class.studentsearch.php' );

// Search Instructor class
                require_once( $this->plugin_dir . 'includes/classes/class.instructorsearch.php' );

//Pagination Class
                require_once( $this->plugin_dir . 'includes/classes/class.pagination.php' );

//Tooltip Helper
                require_once( $this->plugin_dir . 'includes/classes/class.cp-helper-tooltip.php' );

//CoursePress Capabilities Class
                require_once( $this->plugin_dir . 'includes/classes/class.coursepress-capabilities.php' );

// Menu Meta Box
                require_once( $this->plugin_dir . 'includes/classes/class.menumetabox.php' );

//Listen to dynamic editor requests ( using on unit page in the admin )
                add_action('wp_ajax_dynamic_wp_editor', array( &$this, 'dynamic_wp_editor' ));

//Assing instructor ajax call
//add_action('wp_ajax_assign_instructor_capabilities', array(&$this, 'assign_instructor_capabilities'));
// Changed to perform an update instead of just assigning capabilities
// ::RK::
                add_action('wp_ajax_add_course_instructor', array( &$this, 'add_course_instructor' ));

// Using ajax to remove course instructor
                add_action('wp_ajax_remove_course_instructor', array( &$this, 'remove_course_instructor' ));

//Assign Course Setup auto-update ajax call
                add_action('wp_ajax_autoupdate_course_settings', array( &$this, 'autoupdate_course_settings' ));

//Does Course have an active Gateway?
                add_action('wp_ajax_course_has_gateway', array( &$this, 'course_has_gateway' ));

//Invite instructor ajax call
                add_action('wp_ajax_send_instructor_invite', array( &$this, 'send_instructor_invite' ));

//Change course state (draft / publish)
                add_action('wp_ajax_change_course_state', array( &$this, 'change_course_state' ));

//Change unit state (draft / publish)
                add_action('wp_ajax_change_unit_state', array( &$this, 'change_unit_state' ));

//Remove instructor invite ajax call
                add_action('wp_ajax_remove_instructor_invite', array( &$this, 'remove_instructor_invite' ));

// Using ajax to update course calendar
                add_action('wp_ajax_refresh_course_calendar', array( &$this, 'refresh_course_calendar' ));

                add_action('wp_ajax_nopriv_refresh_course_calendar', array( &$this, 'refresh_course_calendar' ));

                add_action('mp_gateway_settings', array( &$this, 'cp_marketpress_popup' ));
            }

//Output buffer hack
            add_action('init', array( &$this, 'output_buffer' ), 0);

//MarketPress Check
            add_action('init', array( &$this, 'marketpress_check' ), 0);


            add_action('init', array( &$this, 'debugging' ));

// Course Calendar
            require_once( $this->plugin_dir . 'includes/classes/class.coursecalendar.php' );

// Discusson class
            require_once( $this->plugin_dir . 'includes/classes/class.discussion.php' );

// Search Discusson class
            require_once( $this->plugin_dir . 'includes/classes/class.discussionsearch.php' );

// Instructor class
            require_once( $this->plugin_dir . 'includes/classes/class.instructor.php' );

// Unit class
            require_once( $this->plugin_dir . 'includes/classes/class.course.unit.php' );

// Course class
            require_once( $this->plugin_dir . 'includes/classes/class.course.php' );

// Notification class
            require_once( $this->plugin_dir . 'includes/classes/class.notification.php' );

// Student class
            require_once( $this->plugin_dir . 'includes/classes/class.student.php' );

// Unit module class
            require_once( $this->plugin_dir . 'includes/classes/class.course.unit.module.php' );

//Load unit modules
//$this->load_modules();

            add_action('init', array( &$this, 'load_modules' ), 11);

//Load Widgets

            add_action('init', array( &$this, 'load_widgets' ), 1);

// Shortcodes class
            require_once( $this->plugin_dir . 'includes/classes/class.shortcodes.php' );

// Virtual page class
            require_once( $this->plugin_dir . 'includes/classes/class.virtualpage.php' );


//Register custom post types
            add_action('init', array( &$this, 'register_custom_posts' ), 1);

//Listen to files download requests ( using in file module )
            add_action('init', array( &$this, 'check_for_force_download_file_request' ), 1);

//Localize the plugin
            add_action('plugins_loaded', array( &$this, 'localization' ), 9);

//Check for $_GET actions
            add_action('init', array( &$this, 'check_for_get_actions' ), 98);

//Add virtual pages
            add_action('init', array( &$this, 'create_virtual_pages' ), 99);

//Add custom image sizes
            add_action('init', array( &$this, 'add_custom_image_sizes' ));

//Add custom image sizes to media library
//add_filter( 'image_size_names_choose', array( &$this, 'add_custom_media_library_sizes' ) );
//Add plugin admin menu - Network
            add_action('network_admin_menu', array( &$this, 'add_admin_menu_network' ));

//Add plugin admin menu
            add_action('admin_menu', array( &$this, 'add_admin_menu' ));

//Check for admin notices
            add_action('admin_notices', array( &$this, 'admin_nopermalink_warning' ));

//Custom header actions
            add_action('wp_enqueue_scripts', array( &$this, 'header_actions' ));

//Custom footer actions

            add_action('wp_footer', array( &$this, 'footer_actions' ));

//add_action( 'admin_enqueue_scripts', array( &$this, 'add_jquery_ui' ) );
            add_action('admin_enqueue_scripts', array( &$this, 'admin_header_actions' ));

            add_action('load-coursepress_page_course_details', array( &$this, 'admin_coursepress_page_course_details' ));
            add_action('load-coursepress_page_settings', array( &$this, 'admin_coursepress_page_settings' ));
            add_action('load-toplevel_page_courses', array( &$this, 'admin_coursepress_page_courses' ));
            add_action('load-coursepress_page_notifications', array( &$this, 'admin_coursepress_page_notifications' ));
            add_action('load-coursepress_page_discussions', array( &$this, 'admin_coursepress_page_discussions' ));
            add_action('load-coursepress_page_reports', array( &$this, 'admin_coursepress_page_reports' ));
            add_action('load-coursepress_page_assessment', array( &$this, 'admin_coursepress_page_assessment' ));
            add_action('load-coursepress_page_students', array( &$this, 'admin_coursepress_page_students' ));
            add_action('load-coursepress_page_instructors', array( &$this, 'admin_coursepress_page_instructors' ));

            add_filter('login_redirect', array( &$this, 'login_redirect' ), 10, 3);
            add_filter('post_type_link', array( &$this, 'check_for_valid_post_type_permalinks' ), 10, 3);
            add_filter('comments_open', array( &$this, 'comments_open_filter' ), 10, 2);

            add_filter("comments_template", array( &$this, "no_comments_template" ));

// Load payment gateways ( to do )
//$this->load_payment_gateways();
//Load add-ons ( for future us, to do )
//$this->load_addons();
//update install script if necessary

            /* if ( get_option( 'coursepress_version' ) != $this->version ) {
              $this->install();
              } */

            add_action('wp', array( &$this, 'load_plugin_templates' ));
            add_filter('rewrite_rules_array', array( &$this, 'add_rewrite_rules' ));
            //add_action('wp_loaded', array( &$this, 'flush_rules' ));
//add_filter('generate_rewrite_rules', array( &$this, 'generate_rewrite_rules' ));
//add_action('init', array( &$this, 'do_rewrite' ));
            add_action('pre_get_posts', array( &$this, 'remove_canonical' ));
            add_action('wp_ajax_update_units_positions', array( $this, 'update_units_positions' ));
            add_filter('query_vars', array( $this, 'filter_query_vars' ));
            add_filter('get_edit_post_link', array( $this, 'courses_edit_post_link' ), 10, 3);
            add_action('parse_request', array( $this, 'action_parse_request' ));
            add_action('admin_init', array( &$this, 'coursepress_plugin_do_activation_redirect' ), 0);
            add_action('wp_login', array( &$this, 'set_latest_student_activity_upon_login' ), 10, 2);
            add_action('mp_order_paid', array( &$this, 'listen_for_paid_status_for_courses' ));
            add_action('parent_file', array( &$this, 'parent_file_correction' ));

// Update CoursePress login/logout menu item.
            add_filter('wp_nav_menu_objects', array( &$this, 'menu_metabox_navigation_links' ), 10, 2);

            //add_filter('wp_nav_menu_args', array( &$this, 'modify_nav_menu_args'), 10);

            if ( get_option('display_menu_items', 1) ) {
                add_filter('wp_nav_menu_objects', array( &$this, 'main_navigation_links' ), 10, 2);
            }

            if ( get_option('display_menu_items', 1) ) {

                $theme_location = 'primary';

                if ( !has_nav_menu($theme_location) ) {
                    $theme_locations = get_nav_menu_locations();
                    foreach ( ( array ) $theme_locations as $key => $location ) {
                        $theme_location = $key;
                        break;
                    }
                }

                if ( !has_nav_menu($theme_location) ) {
                    add_filter('wp_page_menu', array( &$this, 'main_navigation_links_fallback' ), 20, 2);
                }
            }

            add_filter('element_content_filter', array( &$this, 'element_content_img_filter' ), 98, 1);

            add_filter('element_content_filter', array( &$this, 'element_content_link_filter' ), 99, 1);

            add_action('wp_logout', array( &$this, 'redirect_after_logout' ));

            add_action('template_redirect', array( &$this, 'virtual_page_template' ));

            add_action('template_redirect', array( &$this, 'instructor_invite_confirmation' ));

// Setup TinyMCE callback
            add_filter('tiny_mce_before_init', array( &$this, 'init_tiny_mce_listeners' ));

            add_action('show_user_profile', array( &$this, 'instructor_extra_profile_fields' ));
            add_action('edit_user_profile', array( &$this, 'instructor_extra_profile_fields' ));
            add_action('personal_options_update', array( &$this, 'instructor_save_extra_profile_fields' ));
            add_action('edit_user_profile_update', array( &$this, 'instructor_save_extra_profile_fields' ));
        }

        function flush_rules() {
            global $wp_rewrite;
            $wp_rewrite->flush_rules();
        }

        function instructor_save_extra_profile_fields( $user_id ) {
            if ( !current_user_can('edit_user', $user_id) )
                return false;

            if ( $_POST['cp_instructor_capabilities'] == 'grant' ) {
                update_user_meta($user_id, 'role_ins', 'instructor');
                CoursePress::instance()->assign_instructor_capabilities($user_id);
            } else {
                delete_user_meta($user_id, 'role_ins', 'instructor');
                CoursePress::instance()->drop_instructor_capabilities($user_id);
            }
        }

        function instructor_extra_profile_fields( $user ) {
            ?>
            <h3><?php _e('Instructor Capabilities'); ?></h3>

            <?php
            $has_instructor_role = get_user_meta($user->ID, 'role_ins', true);
            ?>
            <table class="form-table">
                <tr>
                    <th><label for="instructor_capabilities"><?php _e('Capabilities', 'cp'); ?></label></th>

                    <td>
                        <input type="radio" name="cp_instructor_capabilities" value="grant" <?php echo ($has_instructor_role ? 'checked' : ''); ?>><?php _e('Granted Instructor Capabilities') ?><br /><br />
                        <input type="radio" name="cp_instructor_capabilities" value="revoke" <?php echo (!$has_instructor_role ? 'checked' : ''); ?>><?php _e('Revoked Instructor Capabilities') ?><br />
                    </td>
                </tr>

            </table>
            <?php
        }

        function restore_capabilities( $user ) {
            $user->add_cap('manage_network');
            $user->add_cap('manage_sites');
            $user->add_cap('manage_network_users');
            $user->add_cap('manage_network_plugins');
            $user->add_cap('manage_network_themes');
            $user->add_cap('manage_network_options');
            $user->add_cap('unfiltered_html');
            $user->add_cap('activate_plugins');
            $user->add_cap('create_users');
            $user->add_cap('delete_plugins');
            $user->add_cap('delete_themes');
            $user->add_cap('delete_users');
            $user->add_cap('edit_files');
            $user->add_cap('edit_plugins');
            $user->add_cap('edit_theme_options');
            $user->add_cap('edit_themes');
            $user->add_cap('edit_users');
            $user->add_cap('export');
            $user->add_cap('import');
            $user->add_cap('install_plugins');
            $user->add_cap('install_themes');
            $user->add_cap('list_users');
            $user->add_cap('manage_options');
            $user->add_cap('promote_users');
            $user->add_cap('remove_users');
            $user->add_cap('switch_themes');
            $user->add_cap('update_core');
            $user->add_cap('update_plugins');
            $user->add_cap('update_themes');
            $user->add_cap('edit_dashboard');
            $user->add_cap('moderate_comments');
            $user->add_cap('manage_categories');
            $user->add_cap('manage_links');
            $user->add_cap('edit_others_posts');
            $user->add_cap('edit_pages');
            $user->add_cap('edit_others_pages');
            $user->add_cap('edit_published_pages');
            $user->add_cap('publish_pages');
            $user->add_cap('delete_pages');
            $user->add_cap('delete_others_pages');
            $user->add_cap('delete_published_pages');
            $user->add_cap('delete_others_posts');
            $user->add_cap('delete_private_posts');
            $user->add_cap('edit_private_posts');
            $user->add_cap('read_private_posts');
            $user->add_cap('delete_private_pages');
            $user->add_cap('edit_private_pages');
            $user->add_cap('read_private_pages');
            $user->add_cap('edit_published_posts');
            $user->add_cap('upload_files');
            $user->add_cap('publish_posts');
            $user->add_cap('delete_published_posts');
            $user->add_cap('edit_posts');
            $user->add_cap('delete_posts');
            $user->add_cap('read');


            // Fix admin role
            $role = get_role('administrator');
            $role->add_cap('read');

            // Add ALL instructor capabilities
            $admin_capabilities = array_keys(CoursePress_Capabilities::$capabilities['instructor']);
            foreach ( $admin_capabilities as $cap ) {
                $role->add_cap($cap);
                $user->add_cap($cap);
            }
        }

        function debugging() {
            // $user = wp_get_current_user();
            // $this->restore_capabilities( $user );
            // $this->assign_instructor_capabilities( $user->ID );
            // cp_write_log( $user->allcaps );
            // cp_write_log( get_role('administrator')->capabilities['coursepress_settings_cap'] );
        }

        function cp_marketpress_popup() {
            if ( ( isset($_GET['cp_admin_ref']) && $_GET['cp_admin_ref'] == 'cp_course_creation_page' ) || ( isset($_POST['cp_admin_ref']) && $_POST['cp_admin_ref'] == 'cp_course_creation_page' ) ) {
                ?>
                <input type="hidden" name="cp_admin_ref" value="cp_course_creation_page" />
                <?php
            }
        }

        function install_and_activate_plugin( $plugin ) {
            $current = get_option('active_plugins');
            $plugin = plugin_basename(trim($plugin));

            if ( !in_array($plugin, $current) ) {
                $current[] = $plugin;
                sort($current);
                do_action('activate_plugin', trim($plugin));
                update_option('active_plugins', $current);
                do_action('activate_' . trim($plugin));
                do_action('activated_plugin', trim($plugin));
            }

            return null;
        }

        function virtual_page_template() {
            global $post;

            if ( isset($post) && $post->post_type == 'virtual_page' ) {
                $theme_file = locate_template(array( 'page.php' ));
                if ( $theme_file != '' ) {
                    include( TEMPLATEPATH . "/page.php" );
                    exit;
                }
            }
        }

        function register_theme_directory() {
            global $wp_theme_directories;
            register_theme_directory($this->plugin_dir . '/themes/');
        }

        /* Fix for the broken images in the Unit elements content */

        function redirect_after_logout() {
            // if ( defined('DOING_AJAX') && DOING_AJAX ) {
            //     cp_write_log('ajax');
            // }
            if ( get_option('use_custom_login_form', 1) ) {
                $url = get_option('cp_custom_login_url', trailingslashit(site_url() . '/' . $this->get_login_slug()));
                wp_redirect($url);
                exit;
            }
        }

        function element_content_img_filter( $content ) {
            return preg_replace_callback('#(<img\s[^>]*src)="([^"]+)"#', "callback_img", $content);
        }

        function element_content_link_filter( $content ) {
            return preg_replace_callback('#(<a\s[^>]*href)="([^"]+)".*<img#', "callback_link", $content);
        }

        function is_preview( $unit_id, $page_num = false ) {
            if ( isset($_GET['try']) ) {

                $unit = new Unit($unit_id);
                $course = new Course($unit->details->post_parent);

                if ( $page_num ) {
                    $paged = $page_num;
                } else {
                    $paged = $wp->query_vars['paged'] ? absint($wp->query_vars['paged']) : 1;
                }

                $preview_unit = $course->details->preview_unit_boxes;
                $preview_page = $course->details->preview_page_boxes;

                if ( isset($preview_unit[$unit_id]) && $preview_unit[$unit_id] == 'on' ) {

                    if ( isset($preview_page[$unit_id . '_' . $paged]) && $preview_page[$unit_id . '_' . $paged] == 'on' ) {
                        return true;
                    } else {
                        return false;
                    }
                }
            } else {
                return false;
            }
        }

        function check_access( $course_id, $unit_id = false ) {

            // if( defined('DOING_AJAX') && DOING_AJAX ) { cp_write_log('doing ajax'); }
// $page_num not set...
// @TODO: implement $page_num and remove next line.

            if ( $this->is_preview($unit_id) ) {
//have access
            } else {
                if ( !current_user_can('manage_options') ) {
                    $student = new Student(get_current_user_id());
                    if ( !$student->has_access_to_course($course_id) ) {
                        wp_redirect(get_permalink($course_id));
                        exit;
                    }
                }
            }
            return true;
        }

        function comments_open_filter( $open, $post_id ) {
            $current_post = get_post($post_id);
            if ( $current_post->post_type == 'discussions' ) {
                return true;
            }
        }

        function add_custom_image_sizes() {
            // if( defined('DOING_AJAX') && DOING_AJAX ) { cp_write_log('doing ajax'); }
            if ( function_exists('add_image_size') ) {
                $course_image_width = get_option('course_image_width', 235);
                $course_image_height = get_option('course_image_height', 225);
                add_image_size('course_thumb', $course_image_width, $course_image_height, true);
            }
        }

        function add_custom_media_library_sizes( $sizes ) {
            $sizes['course_thumb'] = __('Course Image');
            return $sizes;
        }

        /* highlight the proper top level menu */

        function parent_file_correction( $parent_file ) {
            global $current_screen;

            $taxonomy = $current_screen->taxonomy;
            $post_type = $current_screen->post_type;

            if ( $taxonomy == 'course_category' ) {
                $parent_file = 'courses';
            }
            return $parent_file;
        }

        /* change Edit link for courses post type */

        function courses_edit_post_link( $url, $post_id, $context ) {
            if ( get_post_type($post_id) == 'course' ) {
                $url = trailingslashit(get_admin_url()) . 'admin.php?page=course_details&course_id=' . $post_id;
            }
            return $url;
        }

        /* Save last student activity ( upon login ) */

        function set_latest_student_activity_upon_login( $user_login, $user ) {
            $this->set_latest_activity($user->data->ID);
        }

        /* Save last student activity */

        function set_latest_activity( $user_id ) {
            update_user_meta($user_id, 'latest_activity', current_time('timestamp'));
        }

        /* Force requested file downlaod */

        function check_for_force_download_file_request() {

            // if( defined('DOING_AJAX') && DOING_AJAX ) { cp_write_log('doing ajax'); }

            if ( isset($_GET['fdcpf']) ) {
                ob_start();

                require_once( $this->plugin_dir . 'includes/classes/class.encryption.php' );
                $encryption = new CP_Encryption();
                $requested_file = $encryption->decode($_GET['fdcpf']);

                $requested_file_obj = wp_check_filetype($requested_file);
                header('Pragma: public');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Cache-Control: private', false);
                header('Content-Type: ' . $requested_file_obj["type"]);
                header('Content-Disposition: attachment; filename="' . basename($requested_file) . '"');
                header('Content-Transfer-Encoding: binary');
                header('Connection: close');

                echo wp_remote_retrieve_body(wp_remote_get($requested_file), array( 'user-agent' => $this->name . ' / ' . $this->version . ';' ));
                exit();
            }
        }

        /* Retrieve wp_editor dynamically ( using in unit admin ) */

        function dynamic_wp_editor() {

            $editor_id = ( ( isset($_GET['rand_id']) ? $_GET['rand_id'] : rand(1, 9999) ) );

            $args = array(
                "textarea_name" => ( isset($_GET['module_name']) ? $_GET['module_name'] : '' ) . "_content[]",
                "textarea_rows" => 4,
                "quicktags" => false,
                "teeny" => true,
            );


            wp_editor(htmlspecialchars_decode(( isset($_GET['editor_content']) ? $_GET['editor_content'] : '')), $editor_id, $args);

            exit;
        }

        function load_plugin_templates() {
            global $wp_query;

            if ( get_query_var('course') != '' ) {
                add_filter('the_content', array( &$this, 'add_custom_before_course_single_content' ), 1);
                //add_filter('the_excerpt', array( &$this, 'add_custom_before_course_single_content' ), 1);
            }
            //var_dump($wp_query);
            if ( get_post_type() == 'course' && is_archive() ) {
                add_filter('the_content', array( &$this, 'courses_archive_custom_content' ), 1);
                add_filter('the_excerpt', array( &$this, 'courses_archive_custom_content' ), 1);
                add_filter('get_the_excerpt', array( &$this, 'courses_archive_custom_content' ), 1);
            }

            if ( is_post_type_archive('course') ) {
                add_filter('post_type_archive_title', array( &$this, 'courses_archive_title' ), 1);
            }
        }

        function remove_canonical( $wp_query ) {
            global $wp_query;
            if ( is_admin() )
                return;

//stop canonical problems with virtual pages redirecting
            $page = get_query_var('pagename');
            $course = get_query_var('course');

            if ( $page == 'dashboard' or $course !== '' ) {
                remove_action('template_redirect', 'redirect_canonical');
            }
        }

        function action_parse_request( &$wp ) {

            /* Show Discussion single template */
            if ( array_key_exists('discussion_name', $wp->query_vars) ) {

                $vars['discussion_name'] = $wp->query_vars['discussion_name'];
                $vars['course_id'] = Course::get_course_id_by_name($wp->query_vars['coursename']);
            }

            /* Add New Discussion template */

            if ( array_key_exists('discussion_archive', $wp->query_vars) || ( array_key_exists('discussion_name', $wp->query_vars) && $wp->query_vars['discussion_name'] == $this->get_discussion_slug_new() ) ) {
                $vars['course_id'] = Course::get_course_id_by_name($wp->query_vars['coursename']);

                if ( ( array_key_exists('discussion_name', $wp->query_vars) && $wp->query_vars['discussion_name'] == $this->get_discussion_slug_new() ) ) {
                    $this->units_archive_subpage = 'discussions';

                    $theme_file = locate_template(array( 'page-add-new-discussion.php' ));

                    if ( $theme_file != '' ) {
                        require_once( $theme_file );
                        exit;
                    } else {
                        $args = array(
                            'slug' => $wp->request,
                            'title' => __('Add New Discussion', 'cp'),
                            'content' => $this->get_template_details($this->plugin_dir . 'includes/templates/page-add-new-discussion.php', $vars),
                            'type' => 'discussion',
                            'is_page' => TRUE,
                            'is_singular' => TRUE,
                            'is_archive' => FALSE
                        );
                        $pg = new CoursePress_Virtual_Page($args);
                    }
                } else {

                    /* Archive Discussion template */
                    $this->units_archive_subpage = 'discussions';
                    $theme_file = locate_template(array( 'archive-discussions.php' ));

                    if ( $theme_file != '' ) {
//do_shortcode( '[course_notifications_loop]' );
                        require_once( $theme_file );
                        exit;
                    } else {
                        $args = array(
                            'slug' => $wp->request,
                            'title' => __('Discussion', 'cp'),
                            'content' => $this->get_template_details($this->plugin_dir . 'includes/templates/course-discussion-archive.php', $vars),
                            'type' => 'discussions',
                            'is_page' => TRUE,
                            'is_singular' => TRUE,
                            'is_archive' => FALSE
                        );
                        $pg = new CoursePress_Virtual_Page($args);
                        do_shortcode('[course_discussion_loop]');
                    }
                }
            }

            /* Show Instructor single template only if the user is an instructor of at least 1 course */
            if ( array_key_exists('instructor_username', $wp->query_vars) && 0 < Instructor::get_courses_number(get_userdatabynicename($wp->query_vars['instructor_username'])->ID) ) {
                $vars = array();
                $vars['instructor_username'] = $wp->query_vars['instructor_username'];
                $vars['user'] = get_userdatabynicename($wp->query_vars['instructor_username']);

                $theme_file = locate_template(array( 'single-instructor.php' ));

// $course_count = Instructor::get_courses_number( $vars['user']->ID );
// if ( $course_count <= 1 ) {
// 	exit;
// }

                if ( $theme_file != '' ) {
                    require_once( $theme_file );
                    exit;
                } else {

                    $args = array(
                        'slug' => $wp->request,
                        'title' => __($vars['user']->display_name, 'cp'),
                        'content' => $this->get_template_details($this->plugin_dir . 'includes/templates/instructor-single.php', $vars),
                        'type' => 'virtual_page'
                    );

                    $pg = new CoursePress_Virtual_Page($args);
                }

                $this->set_latest_activity(get_current_user_id());
            }

            /* Show Units archive template */
            if ( array_key_exists('coursename', $wp->query_vars) && !array_key_exists('unitname', $wp->query_vars) ) {

                $units_archive_page = false;
                $units_archive_grades_page = false;
                $notifications_archive_page = false;
                $units_workbook_page = false;

                $url = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

                if ( preg_match('/' . $this->get_units_slug() . '/', $url) ) {
                    $units_archive_page = true;
                }

                if ( preg_match('/' . $this->get_grades_slug() . '/', $url) ) {
                    $units_archive_grades_page = true;
                }

                if ( preg_match('/' . $this->get_workbook_slug() . '/', $url) ) {
                    $units_workbook_page = true;
                }

                if ( preg_match('/' . $this->get_notifications_slug() . '/', $url) ) {
                    $notifications_archive_page = true;
                }

                $vars = array();
                $vars['course_id'] = Course::get_course_id_by_name($wp->query_vars['coursename']);

                if ( $notifications_archive_page ) {
                    $this->units_archive_subpage = 'notifications';

                    $theme_file = locate_template(array( 'archive-notifications.php' ));

                    if ( $theme_file != '' ) {
                        do_shortcode('[course_notifications_loop]');
                        require_once( $theme_file );
                        exit;
                    } else {
                        $args = array(
                            'slug' => $wp->request,
                            'title' => __('Notifications', 'cp'),
                            'content' => $this->get_template_details($this->plugin_dir . 'includes/templates/course-notifications-archive.php', $vars),
                            'type' => 'notifications',
                            'is_page' => TRUE,
                            'is_singular' => TRUE,
                            'is_archive' => FALSE
                        );

                        $pg = new CoursePress_Virtual_Page($args);
                        do_shortcode('[course_notifications_loop]');
                    }
                }

                if ( $units_archive_page ) {

                    $this->units_archive_subpage = 'units';

                    $theme_file = locate_template(array( 'archive-unit.php' ));

                    if ( $theme_file != '' ) {
                        do_shortcode('[course_units_loop]');
                        require_once( $theme_file );
                        exit;
                    } else {
                        $args = array(
                            'slug' => $wp->request,
                            'title' => __('Course Units', 'cp'),
                            'content' => $this->get_template_details($this->plugin_dir . 'includes/templates/course-units-archive.php', $vars),
                            'type' => 'unit',
                            'is_page' => TRUE,
                            'is_singular' => TRUE,
                            'is_archive' => FALSE
                        );
                        $pg = new CoursePress_Virtual_Page($args);
                        do_shortcode('[course_units_loop]');
                    }
                    $this->set_latest_activity(get_current_user_id());
                }

                if ( $units_archive_grades_page ) {

                    $this->units_archive_subpage = 'grades';

                    $theme_file = locate_template(array( 'archive-unit-grades.php' ));

                    if ( $theme_file != '' ) {
                        do_shortcode('[course_units_loop]');
                        require_once( $theme_file );
                        exit;
                    } else {
                        $args = array(
                            'slug' => $wp->request,
                            'title' => __('Course Grades', 'cp'),
                            'content' => $this->get_template_details($this->plugin_dir . 'includes/templates/course-units-archive-grades.php', $vars),
                            'type' => 'unit',
                            'is_page' => TRUE,
                            'is_singular' => TRUE,
                            'is_archive' => FALSE
                        );
                        $pg = new CoursePress_Virtual_Page($args);
                        do_shortcode('[course_units_loop]');
                    }
                    $this->set_latest_activity(get_current_user_id());
                }

                if ( $units_workbook_page ) {

                    $this->units_archive_subpage = 'workbook';

                    $theme_file = locate_template(array( 'archive-unit-workbook.php' ));
//wp_enqueue_style( 'font_awesome', $this->plugin_url . 'css/font-awesome.css' );
                    if ( $theme_file != '' ) {
                        do_shortcode('[course_units_loop]');
                        require_once( $theme_file );
                        exit;
                    } else {
                        do_shortcode('[course_units_loop]');
                        $args = array(
                            'slug' => $wp->request,
                            'title' => __('Workbook', 'cp'),
                            'content' => $this->get_template_details($this->plugin_dir . 'includes/templates/archive-unit-workbook.php', $vars),
                            'type' => 'unit',
                            'is_page' => TRUE,
                            'is_singular' => TRUE,
                            'is_archive' => FALSE
                        );
                        $pg = new CoursePress_Virtual_Page($args);
                        do_shortcode('[course_units_loop]');
                    }
                    $this->set_latest_activity(get_current_user_id());
                }
            }


            /* Show Unit single template */
            if ( array_key_exists('coursename', $wp->query_vars) && array_key_exists('unitname', $wp->query_vars) ) {
                $vars = array();
                $unit = new Unit();

                $vars['course_id'] = Course::get_course_id_by_name($wp->query_vars['coursename']);
                $vars['unit_id'] = $unit->get_unit_id_by_name($wp->query_vars['unitname']);

//$this->set_course_visited( get_current_user_id(), Course::get_course_id_by_name( $wp->query_vars['coursename'] ) );

                $unit = new Unit($vars['unit_id']);

                $this->set_unit_visited(get_current_user_id(), $vars['unit_id']);

                $theme_file = locate_template(array( 'single-unit.php' ));

                $forced_previous_completion_template = locate_template(array( 'single-previous-unit.php' ));

                if ( !$unit->is_unit_available($vars['unit_id']) ) {
                    if ( $forced_previous_completion_template != '' ) {
                        do_shortcode('[course_unit_single]'); //required for getting unit results
                        require_once( $forced_previous_completion_template );
                        exit;
                    } else {
                        $args = array(
                            'slug' => $wp->request,
                            'title' => $unit->details->post_title,
                            'content' => __('This Unit is not available at the moment. Please check back later.', 'cp'),
                            'type' => 'page',
                            'is_page' => TRUE,
                            'is_singular' => FALSE,
                            'is_archive' => FALSE
                        );

                        $pg = new CoursePress_Virtual_Page($args);
                    }
                } else {

                    if ( $theme_file != '' ) {
                        do_shortcode('[course_unit_single]'); //required for getting unit results
                        require_once( $theme_file );
                        exit;
                    } else {
                        $args = array(
                            'slug' => $wp->request,
                            'title' => $unit->details->post_title,
                            'content' => $this->get_template_details($this->plugin_dir . 'includes/templates/course-units-single.php', $vars),
                            'type' => 'unit',
                            'is_page' => TRUE,
                            'is_singular' => TRUE,
                            'is_archive' => FALSE
                        );

                        $pg = new CoursePress_Virtual_Page($args);
                    }
                    $this->set_latest_activity(get_current_user_id());
                }
            }
        }

        function set_course_visited( $user_ID, $course_ID ) {
            $get_old_values = get_user_meta($user_ID, 'visited_courses', false);
            if ( !cp_in_array_r($course_ID, $get_old_values) ) {
                $get_old_values[] = $course_ID;
            }
            update_user_meta($user_ID, 'visited_courses', $get_old_values);
        }

        /* Set that student read unit */

        function set_unit_visited( $user_ID, $unit_ID ) {
            $get_old_values = get_user_meta($user_ID, 'visited_units', true);
            $get_new_values = explode('|', $get_old_values);

            if ( !cp_in_array_r($unit_ID, $get_new_values) ) {
                $get_old_values = $get_old_values . '|' . $unit_ID;
                update_user_meta($user_ID, 'visited_units', $get_old_values);
            }
        }

        function filter_query_vars( $query_vars ) {
            $query_vars[] = 'coursename';
            $query_vars[] = 'unitname';
            $query_vars[] = 'instructor_username';
            $query_vars[] = 'discussion_name';
            $query_vars[] = 'discussion_archive';
            $query_vars[] = 'notifications_archive';
            $query_vars[] = 'grades_archive';
            $query_vars[] = 'workbook';
            $query_vars[] = 'discussion_action';
            $query_vars[] = 'paged';

            return $query_vars;
        }

        function add_rewrite_rules( $rules ) {
            $new_rules = array();

            $new_rules['^' . $this->get_course_slug() . '/([^/]*)/' . $this->get_discussion_slug() . '/page/([^/]*)/?'] = 'index.php?page_id=-1&coursename=$matches[1]&discussion_archive&paged=$matches[2]'; ///page/?( [0-9]{1,} )/?$
            $new_rules['^' . $this->get_course_slug() . '/([^/]*)/' . $this->get_discussion_slug() . '/([^/]*)/?'] = 'index.php?page_id=-1&coursename=$matches[1]&discussion_name=$matches[2]';
            $new_rules['^' . $this->get_course_slug() . '/([^/]*)/' . $this->get_discussion_slug()] = 'index.php?page_id=-1&coursename=$matches[1]&discussion_archive';


            $new_rules['^' . $this->get_course_slug() . '/([^/]*)/' . $this->get_grades_slug()] = 'index.php?page_id=-1&coursename=$matches[1]&grades_archive';
            $new_rules['^' . $this->get_course_slug() . '/([^/]*)/' . $this->get_workbook_slug()] = 'index.php?page_id=-1&coursename=$matches[1]&workbook';

            $new_rules['^' . $this->get_course_slug() . '/([^/]*)/' . $this->get_units_slug() . '/([^/]*)/page/([^/]*)/?'] = 'index.php?page_id=-1&coursename=$matches[1]&unitname=$matches[2]&paged=$matches[3]'; ///page/?( [0-9]{1,} )/?$
            $new_rules['^' . $this->get_course_slug() . '/([^/]*)/' . $this->get_units_slug() . '/([^/]*)/?'] = 'index.php?page_id=-1&coursename=$matches[1]&unitname=$matches[2]';
            $new_rules['^' . $this->get_course_slug() . '/([^/]*)/' . $this->get_units_slug()] = 'index.php?page_id=-1&coursename=$matches[1]';

            $new_rules['^' . $this->get_course_slug() . '/([^/]*)/' . $this->get_notifications_slug() . '/page/([^/]*)/?'] = 'index.php?page_id=-1&coursename=$matches[1]&notifications_archive&paged=$matches[2]'; ///page/?( [0-9]{1,} )/?$
            $new_rules['^' . $this->get_course_slug() . '/([^/]*)/' . $this->get_notifications_slug()] = 'index.php?page_id=-1&coursename=$matches[1]&notifications_archive';

            $new_rules['^' . $this->get_instructor_profile_slug() . '/([^/]*)/?'] = 'index.php?page_id=-1&instructor_username=$matches[1]';
//Remove potential conflicts between single and virtual page on single site
            /* if ( !is_multisite() ) {
              unset( $rules['( [^/]+ )( /[0-9]+ )?/?$'] );
              } */

            return array_merge($new_rules, $rules);
        }

        function add_custom_before_course_single_content( $content ) {
            if ( get_post_type() == 'course' ) {
                if ( is_single() ) {
                    if ( $theme_file = locate_template(array( 'single-course.php' )) ) {
//template will take control of the look so don't do anything
                    } else {

                        wp_enqueue_style('front_course_single', $this->plugin_url . 'css/front_course_single.css', array(), $this->version);

                        if ( locate_template(array( 'single-course.php' )) ) {//add custom content in the single template ONLY if the post type doesn't already has its own template
//just output the content
                        } else {
                            $prepend_content = $this->get_template_details($this->plugin_dir . 'includes/templates/single-course-before-details.php');
                            $content = do_shortcode($prepend_content . $content);
                        }
                    }
                }
            }

            return $content;
        }

        function courses_archive_custom_content( $content ) {
            global $post, $content_shown;
            /* if ( locate_template( array( 'archive-course.php' ) ) ) {
              return $post->post_excerpt;
              } else {

              } */

            if ( !isset($content_shown[$GLOBALS['post']->ID]) || $content_shown[$GLOBALS['post']->ID] !== 1 ) {//make sure that we don't apply the filter on more than one content / excerpt on the page per post
                include( $this->plugin_dir . 'includes/templates/archive-courses-single.php' );
                if ( !isset($content_shown[$GLOBALS['post']->ID]) ) {
                    $content_shown[$GLOBALS['post']->ID] = 1;
                } else {
                    $content_shown[$GLOBALS['post']->ID] ++;
                }
            }
        }

        function courses_archive_title( $title ) {
            return __('All Courses', 'cp');
        }

        function get_template_details( $template, $args = array() ) {
            ob_start();
            extract($args);
            include_once( $template );
            return ob_get_clean();
        }

        function update_units_positions() {
            global $wpdb;

            $positions = explode(",", $_REQUEST['positions']);
            $response = '';
            $i = 1;
            foreach ( $positions as $position ) {
                $response .= 'Position #' . $i . ': ' . $position . '<br />';
                update_post_meta($position, 'unit_order', $i);
                $i++;
            }
//echo $response; //just for debugging purposes
            die();
        }

        function dev_check_current_screen() {
            if ( !is_admin() )
                return;

            global $current_screen;

            print_r($current_screen);
        }

        function plugin_activation() {

// Register types to register the rewrite rules  
            $this->register_custom_posts();

// Then flush them  
            flush_rewrite_rules();

//First install
            first_install();

//Welcome Screen
//$this->coursepress_plugin_do_activation_redirect();
        }

        function install() {
            update_option('display_menu_items', 1);
            $this->coursepress_plugin_activate();
            update_option('coursepress_version', $this->version);
            $this->add_user_roles_and_caps(); //This setting is saved to the database ( in table wp_options, field wp_user_roles ), so it might be better to run this on theme/plugin activation
//Set default course groups
            if ( !get_option('course_groups') ) {
                $default_groups = range('A', 'Z');
                update_option('course_groups', $default_groups);
            }

//Redirect to Create New Course page
            require( ABSPATH . WPINC . '/pluggable.php' );

//add_action( 'admin_init', 'my_plugin_redirect' );


            $this->plugin_activation();
        }

        function coursepress_plugin_do_activation_redirect() {
            // if( defined('DOING_AJAX') && DOING_AJAX ) { cp_write_log('doing ajax'); }
            if ( get_option('coursepress_plugin_do_first_activation_redirect', false) ) {
                ob_start();
                delete_option('coursepress_plugin_do_first_activation_redirect');
                wp_redirect(admin_url('admin.php?page=courses&quick_setup'));
                ob_end_clean();
                exit;
            }
        }

        function coursepress_plugin_activate() {
            add_option('coursepress_plugin_do_first_activation_redirect', true);
        }

        /* SLUGS */

        function set_course_slug( $slug = '' ) {
            if ( $slug == '' ) {
                update_option('coursepress_course_slug', get_course_slug());
            } else {
                update_option('coursepress_course_slug', $slug);
            }
        }

        function get_course_slug( $url = false ) {
            $default_slug_value = 'courses';
            if ( !$url ) {
                return get_option('coursepress_course_slug', $default_slug_value);
            } else {
                return site_url() . '/' . get_option('coursepress_course_slug', $default_slug_value);
            }
        }

        function get_module_slug() {
            $default_slug_value = 'module';
            return get_option('coursepress_module_slug', $default_slug_value);
        }

        function get_units_slug() {
            $default_slug_value = 'units';
            return get_option('coursepress_units_slug', $default_slug_value);
        }

        function get_notifications_slug() {
            $default_slug_value = 'notifications';
            return get_option('coursepress_notifications_slug', $default_slug_value);
        }

        function get_discussion_slug() {
            $default_slug_value = 'discussion';
            return get_option('coursepress_discussion_slug', $default_slug_value);
        }

        function get_grades_slug() {
            $default_slug_value = 'grades';
            return get_option('coursepress_grades_slug', $default_slug_value);
        }

        function get_workbook_slug() {
            $default_slug_value = 'workbook';
            return get_option('coursepress_workbook_slug', $default_slug_value);
        }

        function get_discussion_slug_new() {
            $default_slug_value = 'add_new_discussion';
            return get_option('coursepress_discussion_slug_new', $default_slug_value);
        }

        function get_enrollment_process_slug( $url = false ) {
            $default_slug_value = 'enrollment-process';
            if ( !$url ) {
                return get_option('enrollment_process_slug', $default_slug_value);
            } else {
                return site_url() . '/' . get_option('enrollment_process_slug', $default_slug_value);
            }
        }

        function get_student_dashboard_slug( $url = false ) {
            $default_slug_value = 'courses-dashboard';
            if ( !$url ) {
                return get_option('student_dashboard_slug', $default_slug_value);
            } else {
                return site_url() . '/' . get_option('student_dashboard_slug', $default_slug_value);
            }
        }

        function get_student_settings_slug( $url = false ) {
            $default_slug_value = 'settings';
            if ( !$url ) {
                return get_option('student_settings_slug', $default_slug_value);
            } else {
                return site_url() . '/' . get_option('student_settings_slug', $default_slug_value);
            }
        }

        function get_instructor_profile_slug() {
            $default_slug_value = 'instructor';
            return get_option('instructor_profile_slug', $default_slug_value);
        }

        function get_login_slug( $url = false ) {
            $default_slug_value = 'student-login';
            if ( !$url ) {
                return get_option('student_login', $default_slug_value);
            } else {
                return site_url() . '/' . get_option('student_login', $default_slug_value);
            }
        }

        function get_signup_slug( $url = false ) {
            $default_slug_value = 'courses-signup';
            if ( !$url ) {
                return get_option('signup_slug', $default_slug_value);
            } else {
                return site_url() . '/' . get_option('signup_slug', $default_slug_value);
            }
        }

        function localization() {
// Load up the localization file if we're using WordPress in a different language
            if ( $this->location == 'mu-plugins' ) {
                load_muplugin_textdomain('cp', '/languages/');
            } else if ( $this->location == 'subfolder-plugins' ) {
                load_plugin_textdomain('cp', false, '/' . $this->plugin_dir . '/languages/');
            } else if ( $this->location == 'plugins' ) {
                load_plugin_textdomain('cp', false, '/languages/');
            }
        }

        function init_vars() {
//setup proper directories
            if ( defined('WP_PLUGIN_URL') && defined('WP_PLUGIN_DIR') && file_exists(WP_PLUGIN_DIR . '/' . $this->dir_name . '/' . basename(__FILE__)) ) {
                $this->location = 'subfolder-plugins';
                $this->plugin_dir = WP_PLUGIN_DIR . '/' . $this->dir_name . '/';
                $this->plugin_url = plugins_url('/', __FILE__);
            } else if ( defined('WP_PLUGIN_URL') && defined('WP_PLUGIN_DIR') && file_exists(WP_PLUGIN_DIR . '/' . basename(__FILE__)) ) {
                $this->location = 'plugins';
                $this->plugin_dir = WP_PLUGIN_DIR . '/';
                $this->plugin_url = plugins_url('/', __FILE__);
            } else if ( is_multisite() && defined('WPMU_PLUGIN_URL') && defined('WPMU_PLUGIN_DIR') && file_exists(WPMU_PLUGIN_DIR . '/' . basename(__FILE__)) ) {
                $this->location = 'mu-plugins';
                $this->plugin_dir = WPMU_PLUGIN_DIR;
                $this->plugin_url = WPMU_PLUGIN_URL;
            } else {
                wp_die(sprintf(__('There was an issue determining where %s is installed. Please reinstall it.', 'cp'), $this->name));
            }
        }

//Load unit elements / modules / building blocks and other add-ons and plugins
        function load_modules() {

            // if( defined('DOING_AJAX') && DOING_AJAX ) { cp_write_log('doing ajax'); }

            global $mem_modules, $front_page_modules;

            if ( is_dir($this->plugin_dir . 'includes/unit-modules') ) {
                if ( $dh = opendir($this->plugin_dir . 'includes/unit-modules') ) {
                    $mem_modules = array();
                    while ( ( $module = readdir($dh) ) !== false ) {
                        if ( substr($module, -4) == '.php' ) {
                            $mem_modules[] = $module;
                        }
                    }
                    closedir($dh);
                    sort($mem_modules);

                    foreach ( $mem_modules as $mem_module )
                        include_once( $this->plugin_dir . 'includes/unit-modules/' . $mem_module );
                }
            }

            // Not sure if this is a good idea.
            // if ( !$this->is_marketpress_active() && !$this->is_marketpress_lite_active() && !$this->is_marketpress_lite_active() ) {
            //     $this->install_and_activate_plugin('/' . $this->dir_name . '/marketpress.php');
            // }


            do_action('coursepress_modules_loaded');
        }

        function load_widgets() {

            // if( defined('DOING_AJAX') && DOING_AJAX ) { cp_write_log('doing ajax'); }

            if ( is_dir($this->plugin_dir . '/includes/widgets') ) {
                if ( $dh = opendir($this->plugin_dir . '/includes/widgets') ) {
                    $widgets = array();
                    while ( ( $widget = readdir($dh) ) !== false )
                        if ( substr($widget, -4) == '.php' )
                            $widgets[] = $widget;
                    closedir($dh);
                    sort($widgets);

                    foreach ( $widgets as $widget ) {
                        include_once( $this->plugin_dir . '/includes/widgets/' . $widget );
                    }
                }
            }
        }

        function add_admin_menu_network() {
//special menu for network admin
        }

//Add plugin admin menu items
        function add_admin_menu() {

// Add the menu page
            if ( current_user_can('manage_options') || current_user_can('coursepress_dashboard_cap') ) {
                add_menu_page($this->name, $this->name, 'coursepress_dashboard_cap', 'courses', array( &$this, 'coursepress_courses_admin' ), $this->plugin_url . 'images/coursepress-icon.png');
            }
            do_action('coursepress_add_menu_items_up');

// Add the sub menu items
            if ( current_user_can('manage_options') || current_user_can('coursepress_courses_cap') ) {
                add_submenu_page('courses', __('Courses', 'cp'), __('Courses', 'cp'), 'coursepress_courses_cap', 'courses', array( &$this, 'coursepress_courses_admin' ));
            }
            do_action('coursepress_add_menu_items_after_courses');

            if ( isset($_GET['page']) && $_GET['page'] == 'course_details' && isset($_GET['course_id']) ) {
                $new_or_current_course_menu_item_title = __('Course', 'cp');
            } else {
                $new_or_current_course_menu_item_title = __('New Course', 'cp');
            }

            if ( current_user_can('manage_options') || current_user_can('coursepress_courses_cap') ) {
                add_submenu_page('courses', $new_or_current_course_menu_item_title, $new_or_current_course_menu_item_title, 'coursepress_courses_cap', 'course_details', array( &$this, 'coursepress_course_details_admin' ));
            }
            do_action('coursepress_add_menu_items_after_new_courses');

//add_submenu_page( 'courses', __( 'Categories', 'cp' ), __( 'Categories', 'cp' ), 'coursepress_courses_cap', 'edit-tags.php?taxonomy=course_category&post_type=course' );
//do_action( 'coursepress_add_menu_items_after_course_categories' );

            if ( current_user_can('manage_options') || current_user_can('coursepress_instructors_cap') ) {
                add_submenu_page('courses', __('Instructors', 'cp'), __('Instructors', 'cp'), 'coursepress_instructors_cap', 'instructors', array( &$this, 'coursepress_instructors_admin' ));
            }
            do_action('coursepress_add_menu_items_after_instructors');

            if ( current_user_can('manage_options') || current_user_can('coursepress_students_cap') ) {
                add_submenu_page('courses', __('Students', 'cp'), __('Students', 'cp'), 'coursepress_students_cap', 'students', array( &$this, 'coursepress_students_admin' ));
            }
            do_action('coursepress_add_menu_items_after_instructors');

            $main_module = new Unit_Module();
            $count = $main_module->get_ungraded_response_count();

            if ( $count == 0 ) {
                $count_output = '';
            } else {
                $count_output = '&nbsp;<span class="update-plugins"><span class="updates-count count-' . $count . '">' . $count . '</span></span>';
            }

            if ( current_user_can('manage_options') || current_user_can('coursepress_assessment_cap') ) {
                add_submenu_page('courses', __('Assessment', 'cp'), __('Assessment', 'cp') . $count_output, 'coursepress_assessment_cap', 'assessment', array( &$this, 'coursepress_assessment_admin' ));
            }
            do_action('coursepress_add_menu_items_after_assessment');

            if ( current_user_can('manage_options') || current_user_can('coursepress_reports_cap') ) {
                add_submenu_page('courses', __('Reports', 'cp'), __('Reports', 'cp'), 'coursepress_reports_cap', 'reports', array( &$this, 'coursepress_reports_admin' ));
            }
            do_action('coursepress_add_menu_items_after_reports');

            if ( current_user_can('manage_options') || current_user_can('coursepress_notifications_cap') ) {
                add_submenu_page('courses', __('Notifications', 'cp'), __('Notifications', 'cp'), 'coursepress_notifications_cap', 'notifications', array( &$this, 'coursepress_notifications_admin' ));
            }
            do_action('coursepress_add_menu_items_after_course_notifications');

            if ( current_user_can('manage_options') || current_user_can('coursepress_discussions_cap') ) {
                add_submenu_page('courses', __('Discussions', 'cp'), __('Discussions', 'cp'), 'coursepress_discussions_cap', 'discussions', array( &$this, 'coursepress_discussions_admin' ));
            }
            do_action('coursepress_add_menu_items_after_course_discussions');

            if ( current_user_can('manage_options') || current_user_can('coursepress_settings_cap') ) {
                add_submenu_page('courses', __('Settings', 'cp'), __('Settings', 'cp'), 'coursepress_settings_cap', 'settings', array( &$this, 'coursepress_settings_admin' ));
            }
            do_action('coursepress_add_menu_items_after_settings');

            do_action('coursepress_add_menu_items_down');
        }

        function register_custom_posts() {


// Register custom taxonomy
            register_taxonomy('course_category', 'course', apply_filters('cp_register_course_category', array(
                "hierarchical" => true,
                'label' => __('Course Categories', 'cp'),
                'singular_label' => __('Course Category', 'cp') )
                    )
            );

//Register Courses post type
            $args = array(
                'labels' => array( 'name' => __('Courses', 'cp'),
                    'singular_name' => __('Course', 'cp'),
                    'add_new' => __('Create New', 'cp'),
                    'add_new_item' => __('Create New Course', 'cp'),
                    'edit_item' => __('Edit Course', 'cp'),
                    'edit' => __('Edit', 'cp'),
                    'new_item' => __('New Course', 'cp'),
                    'view_item' => __('View Course', 'cp'),
                    'search_items' => __('Search Courses', 'cp'),
                    'not_found' => __('No Courses Found', 'cp'),
                    'not_found_in_trash' => __('No Courses found in Trash', 'cp'),
                    'view' => __('View Course', 'cp')
                ),
                'public' => false,
                'has_archive' => true,
                'show_ui' => false,
                'publicly_queryable' => true,
                'capability_type' => 'course',
                'map_meta_cap' => true,
                'query_var' => true,
                'rewrite' => array(
                    'slug' => $this->get_course_slug(),
                    'with_front' => false
                ),
                'supports' => array( 'thumbnail' )
            );

            register_post_type('course', $args);
//add_theme_support( 'post-thumbnails' );
//Register Units post type
            $args = array(
                'labels' => array( 'name' => __('Units', 'cp'),
                    'singular_name' => __('Unit', 'cp'),
                    'add_new' => __('Create New', 'cp'),
                    'add_new_item' => __('Create New Unit', 'cp'),
                    'edit_item' => __('Edit Unit', 'cp'),
                    'edit' => __('Edit', 'cp'),
                    'new_item' => __('New Unit', 'cp'),
                    'view_item' => __('View Unit', 'cp'),
                    'search_items' => __('Search Units', 'cp'),
                    'not_found' => __('No Units Found', 'cp'),
                    'not_found_in_trash' => __('No Units found in Trash', 'cp'),
                    'view' => __('View Unit', 'cp')
                ),
                'public' => false,
                'show_ui' => false,
                'publicly_queryable' => false,
                'capability_type' => 'unit',
                'map_meta_cap' => true,
                'query_var' => true
            );

            register_post_type('unit', $args);

//Register Modules ( Unit Module ) post type
            $args = array(
                'labels' => array( 'name' => __('Modules', 'cp'),
                    'singular_name' => __('Module', 'cp'),
                    'add_new' => __('Create New', 'cp'),
                    'add_new_item' => __('Create New Module', 'cp'),
                    'edit_item' => __('Edit Module', 'cp'),
                    'edit' => __('Edit', 'cp'),
                    'new_item' => __('New Module', 'cp'),
                    'view_item' => __('View Module', 'cp'),
                    'search_items' => __('Search Modules', 'cp'),
                    'not_found' => __('No Modules Found', 'cp'),
                    'not_found_in_trash' => __('No Modules found in Trash', 'cp'),
                    'view' => __('View Module', 'cp')
                ),
                'public' => false,
                'show_ui' => false,
                'publicly_queryable' => false,
                'capability_type' => 'module',
                'map_meta_cap' => true,
                'query_var' => true
            );

            register_post_type('module', $args);

//Register Modules Responses ( Unit Module Responses ) post type
            $args = array(
                'labels' => array( 'name' => __('Module Responses', 'cp'),
                    'singular_name' => __('Module Response', 'cp'),
                    'add_new' => __('Create New', 'cp'),
                    'add_new_item' => __('Create New Response', 'cp'),
                    'edit_item' => __('Edit Response', 'cp'),
                    'edit' => __('Edit', 'cp'),
                    'new_item' => __('New Response', 'cp'),
                    'view_item' => __('View Response', 'cp'),
                    'search_items' => __('Search Responses', 'cp'),
                    'not_found' => __('No Module Responses Found', 'cp'),
                    'not_found_in_trash' => __('No Responses found in Trash', 'cp'),
                    'view' => __('View Response', 'cp')
                ),
                'public' => false,
                'show_ui' => false,
                'publicly_queryable' => false,
                'capability_type' => 'module_response',
                'map_meta_cap' => true,
                'query_var' => true
            );

            register_post_type('module_response', $args);

//Register Notifications post type
            $args = array(
                'labels' => array( 'name' => __('Notifications', 'cp'),
                    'singular_name' => __('Notification', 'cp'),
                    'add_new' => __('Create New', 'cp'),
                    'add_new_item' => __('Create New Notification', 'cp'),
                    'edit_item' => __('Edit Notification', 'cp'),
                    'edit' => __('Edit', 'cp'),
                    'new_item' => __('New Notification', 'cp'),
                    'view_item' => __('View Notification', 'cp'),
                    'search_items' => __('Search Notifications', 'cp'),
                    'not_found' => __('No Notifications Found', 'cp'),
                    'not_found_in_trash' => __('No Notifications found in Trash', 'cp'),
                    'view' => __('View Notification', 'cp')
                ),
                'public' => false,
                'show_ui' => false,
                'publicly_queryable' => false,
                'capability_type' => 'notification',
                'map_meta_cap' => true,
                'query_var' => true,
                'rewrite' => array( 'slug' => trailingslashit($this->get_course_slug()) . '%course%/' . $this->get_notifications_slug() )
            );

            register_post_type('notifications', $args);

//Register Discussion post type
            $args = array(
                'labels' => array( 'name' => __('Discussions', 'cp'),
                    'singular_name' => __('Discussions', 'cp'),
                    'add_new' => __('Create New', 'cp'),
                    'add_new_item' => __('Create New Discussion', 'cp'),
                    'edit_item' => __('Edit Discussion', 'cp'),
                    'edit' => __('Edit', 'cp'),
                    'new_item' => __('New Discussion', 'cp'),
                    'view_item' => __('View Discussion', 'cp'),
                    'search_items' => __('Search Discussions', 'cp'),
                    'not_found' => __('No Discussions Found', 'cp'),
                    'not_found_in_trash' => __('No Discussions found in Trash', 'cp'),
                    'view' => __('View Discussion', 'cp')
                ),
                'public' => false,
                //'has_archive' => true,
                'show_ui' => false,
                'publicly_queryable' => false,
                'capability_type' => 'discussion',
                'map_meta_cap' => true,
                'query_var' => true,
                'rewrite' => array( 'slug' => trailingslashit($this->get_course_slug()) . '%course%/' . $this->get_discussion_slug() )
            );

            register_post_type('discussions', $args);

            do_action('after_custom_post_types');
        }

        /**
         *
         *
         * ::RK::
         */
        function course_has_gateway() {

            $gateways = get_option('mp_settings', false);
            if ( !empty($gateways) ) {
                $gateways = !empty($gateways['gateways']['allowed']) ? true : false;
            }

            $ajax_response = array( 'has_gateway' => $gateways );
            $ajax_status = 1;

            $response = array(
                'what' => 'instructor_invite',
                'action' => 'instructor_invite',
                'id' => $ajax_status,
                'data' => json_encode($ajax_response),
            );
            $xmlResponse = new WP_Ajax_Response($response);
            $xmlResponse->send();
        }

        /**
         * Handles AJAX call for Course Settings auto-update.
         *
         * ::RK::
         */
        function autoupdate_course_settings() {

            if ( isset($_POST['course_id']) && isset($_POST['course_nonce']) && isset($_POST['required_cap']) && defined('DOING_AJAX') && DOING_AJAX ) {
                /*
                  http://codex.wordpress.org/Plugin_API/Filter_Reference/tiny_mce_before_init
                  http://www.tinymce.com/wiki.php/API3:event.tinymce.Editor.onChange
                 */

                $ajax_response = array();

                if ( ( $_POST['course_id'] || 0 == $_POST['course_id'] ) && wp_verify_nonce($_POST['course_nonce'], 'auto-update-' . $_POST['course_id']) &&
                        sha1('can_update_course' . $_POST['course_nonce']) == $_POST['required_cap'] ) {

                    $course = new Course(( int ) $_POST['course_id']);
                    if ( $course->details ) {
                        $course->data['status'] = $course->details->post_status;
                    } else {
                        $course->data['status'] = 'draft';
                    }

                    if ( !empty($_POST['uid']) && 0 == ( int ) $_POST['course_id'] ) {
                        $course->data['uid'] = ( int ) $_POST['uid'];
                        $ajax_response['instructor'] = ( int ) $_POST['uid'];
                    }

                    $course_id = $course->update_course();
                    $mp_product_id = $course->mp_product_id();

                    $ajax_response['success'] = true;
                    $ajax_response['course_id'] = $course_id;
                    $ajax_response['mp_product_id'] = $mp_product_id;
                    $ajax_response['nonce'] = wp_create_nonce('auto-update-' . $course_id);
                    $ajax_response['cap'] = sha1('can_update_course' . $ajax_response['nonce']);
                } else {
                    $ajax_response['success'] = false;
                    $ajax_response['reason'] = __('Invalid request. Security check failed.', 'cp');
                }

                $response = array(
                    'what' => 'instructor_invite',
                    'action' => 'instructor_invite',
                    'id' => 1, // success status
                    'data' => json_encode($ajax_response),
                );
                $xmlResponse = new WP_Ajax_Response($response);
                $xmlResponse->send();
            }
        }

        function change_course_state() {
            // current_user_can('manage_options') may not always be accurate because its an ajax request
            if ( isset($_POST['course_state']) && isset($_POST['course_id']) && isset($_POST['course_nonce']) && isset($_POST['required_cap']) && defined('DOING_AJAX') && DOING_AJAX ) {

                $ajax_response = array();

                if ( $_POST['course_id'] && wp_verify_nonce($_POST['course_nonce'], 'toggle-' . $_POST['course_id']) &&
                        sha1('can_change_course_state' . $_POST['course_nonce']) == $_POST['required_cap'] ) {
                    $course = new Course(( int ) $_POST['course_id']);
                    $course->change_status($_POST['course_state']);
                    $ajax_response['toggle'] = true;
                    $ajax_response['nonce'] = wp_create_nonce('toggle-' . ( int ) $_POST['course_id']);
                    $ajax_response['cap'] = sha1('can_change_course_state' . $ajax_response['nonce']);
                } else {
                    $ajax_response['toggle'] = false;
                    $ajax_response['reason'] = __('Invalid request. Security check failed.', 'cp');
                }

                $response = array(
                    'what' => 'instructor_invite',
                    'action' => 'instructor_invite',
                    'id' => 1, // success status
                    'data' => json_encode($ajax_response),
                );
                $xmlResponse = new WP_Ajax_Response($response);
                $xmlResponse->send();
            }
        }

        function change_unit_state() {
            if ( isset($_POST['unit_state']) && isset($_POST['unit_id']) && isset($_POST['unit_nonce']) && isset($_POST['required_cap']) && defined('DOING_AJAX') && DOING_AJAX ) {

                $ajax_response = array();

                if ( $_POST['unit_id'] && wp_verify_nonce($_POST['unit_nonce'], 'toggle-' . $_POST['unit_id']) &&
                        sha1('can_change_course_unit_state' . $_POST['unit_nonce']) == $_POST['required_cap'] ) {
                    $unit = new Unit(( int ) $_POST['unit_id']);
                    $unit->change_status($_POST['unit_state']);

                    $ajax_response['toggle'] = true;
                    $ajax_response['nonce'] = wp_create_nonce('toggle-' . ( int ) $_POST['unit_id']);
                    $ajax_response['cap'] = sha1('can_change_course_unit_state' . $ajax_response['nonce']);
                } else {
                    $ajax_response['toggle'] = false;
                    $ajax_response['reason'] = __('Invalid request. Security check failed.', 'cp');
                }

                $response = array(
                    'what' => 'instructor_invite',
                    'action' => 'instructor_invite',
                    'id' => 1, // success status
                    'data' => json_encode($ajax_response),
                );
                $xmlResponse = new WP_Ajax_Response($response);
                $xmlResponse->send();
            }
        }

        /**
         *
         *
         * ::RK::
         */
        function add_course_instructor() {

            $ajax_response = array();

            $instructors = get_post_meta($_POST['course_id'], 'instructors', true);
            $user_id = $_POST['user_id'];
            $course_id = $_POST['course_id'];

            $exists = false;
            if ( is_array($instructors) ) {
                foreach ( $instructors as $instructor ) {
                    if ( $instructor == $user_id ) {
                        $instructor_course_id = get_user_meta($user_id, 'course_' . $course_id);
                        if ( !empty($instructor_course_id) ) {
                            $exists = true;
                        };
                    }
                }
            }

            // User is not yet an instructor
            if ( !$exists ) {
                // Assign Instructor capabilities

                $this->assign_instructor_capabilities($user_id);

                $instructors[] = $user_id;
                update_post_meta($course_id, 'instructors', $instructors);
                update_user_meta($user_id, 'course_' . $course_id, $course_id);

                $ajax_response['instructors'] = json_encode($instructors);
                $ajax_response['instructor_added'] = true;

                $user_info = get_userdata($user_id);

                $ajax_response['instructor_gravatar'] = get_avatar($user_id, 80, "", $user_info->display_name);
                $ajax_response['instructor_name'] = $user_info->display_name;
            } else {
                $ajax_response['instructor_added'] = false;
                $ajax_response['reason'] = __('Instructor already added.', 'cp');
            }

            $response = array(
                'what' => 'instructor_invite',
                'action' => 'instructor_invite',
                'id' => 1, // success status
                'data' => json_encode($ajax_response),
            );
            $xmlResponse = new WP_Ajax_Response($response);
            $xmlResponse->send();
        }

        /**
         *
         *
         * ::RK::
         */
        function remove_course_instructor() {

            $ajax_response = array();

            $user_id = $_POST['user_id'];
            $course_id = $_POST['course_id'];

            $instructors = get_post_meta($course_id, 'instructors', true);

            $updated_instructors = array();
            foreach ( $instructors as $instructor ) {
                if ( $instructor != $user_id ) {
                    $updated_instructors[] = $instructor;
                }
            }
            update_post_meta($course_id, 'instructors', $updated_instructors);
            delete_user_meta($user_id, 'course_' . $course_id, $course_id);

            $instructor = new Instructor($user_id);

// If user is no longer an instructor of any courses, remove his capabilities.
            $assigned_courses_ids = $instructor->get_assigned_courses_ids();
            if ( empty($assigned_courses_ids) ) {
                $this->drop_instructor_capabilities($user_id);
            }

            $ajax_response['instructor_removed'] = true;

            $response = array(
                'what' => 'instructor_invite',
                'action' => 'instructor_invite',
                'id' => 1, // success status
                'data' => json_encode($ajax_response),
            );
            $xmlResponse = new WP_Ajax_Response($response);
            $xmlResponse->send();
        }

        /**
         *
         *
         * ::RK::
         */
        function send_instructor_invite() {

            $email_args['email_type'] = 'instructor_invitation';
            $email_args['first_name'] = sanitize_text_field($_POST['first_name']);
            $email_args['last_name'] = sanitize_text_field($_POST['last_name']);
            $email_args['instructor_email'] = sanitize_email($_POST['email']);

            $user = get_user_by('email', $email_args['instructor_email']);
            if ( $user ) {
                $email_args['user'] = $user;
            }
            if ( !empty($_POST['course_id']) ) {
                $email_args['course_id'] = ( int ) $_POST['course_id'];

                $ajax_response = array();
                $ajax_status = 1; //success
// Get the invite meta for this course and add the new invite
                $invite_exists = false;
                if ( $instructor_invites = get_post_meta($email_args['course_id'], 'instructor_invites', true) ) {
                    foreach ( $instructor_invites as $i ) {
                        $invite_exists = array_search($email_args['instructor_email'], $i);
                    }
                } else {
                    $instructor_invites = array();
                }

                if ( !$invite_exists ) {

// Generate invite code.
                    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    $invite_code = '';
                    for ( $i = 0; $i < 20; $i++ ) {
                        $invite_code .= $characters[rand(0, strlen($characters) - 1)];
                    }
// Save the invite in the course meta. Hash will be used for user authentication.
                    $email_args['invite_code'] = $invite_code;
                    $invite_hash = sha1($email_args['instructor_email'] . $email_args['invite_code']);

                    $email_args['invite_hash'] = $invite_hash;

                    if ( coursepress_send_email($email_args) ) {

                        $invite = array(
                            'first_name' => $email_args['first_name'],
                            'last_name' => $email_args['last_name'],
                            'email' => $email_args['instructor_email'],
                            'code' => $email_args['invite_code'],
                            'hash' => $email_args['invite_hash'],
                        );

                        $instructor_invites[$email_args['invite_code']] = $invite;

                        update_post_meta($email_args['course_id'], 'instructor_invites', $instructor_invites);

                        if ( ( current_user_can('coursepress_assign_and_assign_instructor_course_cap') ) || ( current_user_can('coursepress_assign_and_assign_instructor_my_course_cap') && $course->details->post_author == get_current_user_id() ) ) {
                            $ajax_response['capability'] = true;
                        } else {
                            $ajax_response['capability'] = false;
                        }

                        $ajax_response['data'] = $invite;
                        $ajax_response['content'] = '<i class="fa fa-check status status-success"></i> ' . __('Invitation successfully sent.', 'cp');
                    } else {
                        $ajax_status = new WP_Error('mail_fail', __('Email failed to send.', 'cp'));
                        $ajax_response['content'] = '<i class="fa fa-exclamation status status-fail"></i> ' . __('Email failed to send.', 'cp');
                    }
                } else {
                    $ajax_response['content'] = '<i class="fa fa-info-circle status status-exist"></i> ' . __('Invitation already exists.', 'cp');
                    ;
                }


                $response = array(
                    'what' => 'instructor_invite',
                    'action' => 'instructor_invite',
                    'id' => $ajax_status,
                    'data' => json_encode($ajax_response),
                );
                $xmlResponse = new WP_Ajax_Response($response);
                $xmlResponse->send();
            }
        }

        function remove_instructor_invite() {
            $ajax_response = array();
            $ajax_status = 1; //success

            if ( !empty($_POST['course_id']) ) {
                $course_id = ( int ) $_POST['course_id'];
                $invite_code = sanitize_text_field($_POST['invite_code']);
                $instructor_invites = get_post_meta($course_id, 'instructor_invites', true);

                unset($instructor_invites[$invite_code]);

                update_post_meta($course_id, 'instructor_invites', $instructor_invites);

                $ajax_response['content'] = __('Instructor invitation cancelled.', 'cp');
            }

            $response = array(
                'what' => 'remove_instructor_invite',
                'action' => 'remove_instructor_invite',
                'id' => $ajax_status,
                'data' => json_encode($ajax_response),
            );
            $xmlResponse = new WP_Ajax_Response($response);
            $xmlResponse->send();
        }

        function instructor_invite_confirmation() {
            if ( isset($_GET['action']) && 'course_invite' == $_GET['action'] ) {

                get_header();

                if ( is_user_logged_in() ) {

                    $current_user = wp_get_current_user();
                    $hash = sha1($current_user->user_email . $_GET['c']);

                    if ( $hash == $_GET['h'] ) {

                        $instructors = get_post_meta($_GET['course_id'], 'instructors', true);
                        $invites = get_post_meta($_GET['course_id'], 'instructor_invites', true);

                        foreach ( $invites as $key => $invite ) {
                            if ( $_GET['c'] == $invite['code'] ) {

                                $exists = false;
                                foreach ( $instructors as $instructor ) {
                                    if ( $instructor == $current_user->ID ) {
                                        $exists = true;
//exit;
                                    }
                                }

                                if ( !$exists ) {
// Assign Instructor capabilities
                                    $this->assign_instructor_capabilities($current_user->ID);

                                    $instructors[] = $current_user->ID;
                                    update_post_meta($_GET['course_id'], 'instructors', $instructors);
                                    update_user_meta($current_user->ID, 'course_' . $_GET['course_id'], $_GET['course_id']);
                                    unset($invites[$key]);
                                    update_post_meta($_GET['course_id'], 'instructor_invites', $invites);

                                    $course_link = '<a href="' . admin_url('admin.php?page=course_details&course_id=' . $_GET['course_id']) . '">' . get_the_title($_GET['course_id']) . '</a>';

                                    echo sprintf(__('<h3>Invitation activated.</h3>
										<p>Congratulations. You are now an instructor in the following course:</p>
										<p>%s</p>
									', 'cp'), $course_link);
                                }
                                break;
                            }
                        }

//wp_redirect(admin_url('admin.php?page=course_details&tab=overview&course_id=' . $_GET['course_id']) . '">' . get_the_title($_GET['course_id']));
                    } else {
                        echo __('
							<h3>Invalid Invitation</h3>
							<p>This invitation link is not associated with your email address.</p>
							<p>Please contact your course administator and ask them to send a new invitation to the email address that you have associated with your account.</p>
						', 'cp');
                    }
                } else {
                    echo __('
						<h3>Login Required</h3>
						<p>To accept your invitation request you will need to be logged in.</p>
						<p>Please login with the account associated with this email.</p>
					', 'cp');

                    wp_login_form();

                    echo sprintf(__('
						<p>If you do not have an account please click on the %s link to create a new account. When you have registered for your new account, please click on the invitation link in the original email again.</p>
					', 'cp'), wp_register('', '', false));
                }

                get_footer();
                exit;
            }
        }

        /**
         * Create a listener for TinyMCE change event 
         *
         */
        function init_tiny_mce_listeners( $initArray ) {

            $detect_pages = array(
                'coursepress_page_course_details',
            );

            $page = get_current_screen()->id;
            $tab = empty($_GET['tab']) ? '' : $_GET['tab'];

            if ( in_array($page, $detect_pages) ) {
                $initArray['setup'] = 'function( ed ) {
							ed.on( \'init\', function( args ) {
								jQuery( \'#\' + ed.id + \'_parent\' ).bind( \'mousemove\',function ( evt ) {
																		cp_editor_mouse_move( ed, evt );
																	} );
							} );
							ed.on( \'keydown\', function( args ) {
								cp_editor_key_down( ed, \'' . $page . '\', \'' . $tab . '\' );
							} );
						}';
            }

            return $initArray;
        }

        function refresh_course_calendar() {
            $ajax_response = array();
            $ajax_status = 1; //success

            if ( !empty($_POST['date']) && !empty($_POST['course_id']) ) {

                $date = getdate(strtotime(str_replace('-', '/', $_POST['date'])));
                $pre = !empty($_POST['pre_text']) ? $_POST['pre_text'] : false;
                $next = !empty($_POST['next_text']) ? $_POST['next_text'] : false;

                $calendar = new Course_Calendar(array( 'course_id' => $_POST['course_id'], 'month' => $date['mon'], 'year' => $date['year'] ));

                $html = '';
                if ( $pre && $next ) {
                    $html = $calendar->create_calendar($pre, $next);
                } else {
                    $html = $calendar->create_calendar();
                }

                $ajax_response['calendar'] = $html;
            }

            $response = array(
                'what' => 'refresh_course_calendar',
                'action' => 'refresh_course_calendar',
                'id' => $ajax_status,
                'data' => json_encode($ajax_response),
            );
            $xmlResponse = new WP_Ajax_Response($response);
            $xmlResponse->send();
        }

        function assign_instructor_capabilities( $user_id ) {

            //updated to using CoursePress settings
            // The default capabilities for an instructor
            $default = array_keys(CoursePress_Capabilities::$capabilities['instructor'], 1);

            $instructor_capabilities = get_option('coursepress_instructor_capabilities', $default);

            $role = new WP_User($user_id);

            update_user_meta($user_id, 'role_ins', 'instructor');

            $role->add_cap('can_edit_posts');
            $role->add_cap('read');

            foreach ( $instructor_capabilities as $cap ) {
                $role->add_cap($cap);
            }
        }

        function drop_instructor_capabilities( $user_id ) {

            if ( user_can($user_id, 'manage_options') ) {
                exit;
            }

            $role = new Instructor($user_id);

            delete_user_meta($user_id, 'role_ins', 'instructor');

            $role->remove_cap('can_edit_posts');
            $role->remove_cap('read');

            $capabilities = array_keys(CoursePress_Capabilities::$capabilities['instructor']);
            foreach ( $capabilities as $cap ) {
                $role->remove_cap($cap);
            }

            CoursePress_Capabilities::grant_private_caps($user_id);
        }

//Add new roles and user capabilities
        function add_user_roles_and_caps() {
            global $user, $wp_roles;

            /* ---------------------- Add initial capabilities for the admins */
            $role = get_role('administrator');
            $role->add_cap('read');

            // Add ALL instructor capabilities
            $admin_capabilities = array_keys(CoursePress_Capabilities::$capabilities['instructor']);
            foreach ( $admin_capabilities as $cap ) {
                $role->add_cap($cap);
            }

            CoursePress_Capabilities::drop_private_caps($user_id);
        }

//Functions for handling admin menu pages

        function coursepress_courses_admin() {
            include_once( $this->plugin_dir . 'includes/admin-pages/courses.php' );
        }

        function coursepress_course_details_admin() {
            include_once( $this->plugin_dir . 'includes/admin-pages/courses-details.php' );
        }

        function coursepress_instructors_admin() {
            include_once( $this->plugin_dir . 'includes/admin-pages/instructors.php' );
        }

        function coursepress_students_admin() {
            include_once( $this->plugin_dir . 'includes/admin-pages/students.php' );
        }

        function coursepress_assessment_admin() {
            include_once( $this->plugin_dir . 'includes/admin-pages/assessment.php' );
        }

        function coursepress_notifications_admin() {
            include_once( $this->plugin_dir . 'includes/admin-pages/notifications.php' );
        }

        function coursepress_discussions_admin() {
            include_once( $this->plugin_dir . 'includes/admin-pages/discussions.php' );
        }

        function coursepress_reports_admin() {
            include_once( $this->plugin_dir . 'includes/admin-pages/reports.php' );
        }

        function coursepress_settings_admin() {
            include_once( $this->plugin_dir . 'includes/admin-pages/settings.php' );
        }

        /* Functions for handling tab pages */

        function show_courses_details_overview() {
            include_once( $this->plugin_dir . 'includes/admin-pages/courses-details-overview.php' );
        }

        function show_courses_details_units() {
            include_once( $this->plugin_dir . 'includes/admin-pages/courses-details-units.php' );
        }

        function show_courses_details_students() {
            include_once( $this->plugin_dir . 'includes/admin-pages/courses-details-students.php' );
        }

        function show_settings_general() {
            include_once( $this->plugin_dir . 'includes/admin-pages/settings-general.php' );
        }

        function show_settings_groups() {
            include_once( $this->plugin_dir . 'includes/admin-pages/settings-groups.php' );
        }

        function show_settings_payment() {
            include_once( $this->plugin_dir . 'includes/admin-pages/settings-payment.php' );
        }

        function show_settings_shortcodes() {
            include_once( $this->plugin_dir . 'includes/admin-pages/settings-shortcodes.php' );
        }

        function show_settings_instructor_capabilities() {
            include_once( $this->plugin_dir . 'includes/admin-pages/settings-instructor-capabilities.php' );
        }

        function show_settings_email() {
            include_once( $this->plugin_dir . 'includes/admin-pages/settings-email.php' );
        }

        function show_unit_details( $unit_page_num = 1, $active_element = 1 ) {
            require_once( $this->plugin_dir . 'includes/admin-pages/unit-details.php' );
        }

        /* Custom header actions */

        function header_actions() {//front
            global $post;
            wp_enqueue_style('font_awesome', $this->plugin_url . 'css/font-awesome.css');
            wp_enqueue_script('coursepress_front', $this->plugin_url . 'js/coursepress-front.js', array( 'jquery' ));
            wp_enqueue_script('coursepress_calendar', $this->plugin_url . 'js/coursepress-calendar.js', array( 'jquery' ));
            if ( $post && !$this->is_preview($post->ID) ) {
                wp_enqueue_script('coursepress_front_elements', $this->plugin_url . 'js/coursepress-front-elements.js', array( 'jquery' ));
            }
            $course_id = do_shortcode('[get_parent_course_id]');
            $units_archive_url = is_numeric($course_id) ? get_permalink($course_id) . trailingslashit($this->get_units_slug()) : '';

            wp_localize_script('coursepress_front', 'front_vars', array(
                'withdraw_alert' => __('Please confirm that you want to withdraw from the course. If you withdraw, you will no longer be able to see your records for this course.', 'cp'),
                'units_archive_url' => $units_archive_url
            ));

            if ( !is_admin() ) {
                wp_enqueue_style('front_general', $this->plugin_url . 'css/front_general.css', array(), $this->version);
            }

            wp_enqueue_script('coursepress-knob', $this->plugin_url . 'js/jquery.knob.js', array(), '20120207', true);
        }

        /* Custom footer actions */

        function footer_actions() {
            if ( ( isset($_GET['saved']) && $_GET['saved'] == 'ok' ) ) {
                ?>
                <div class="save_elements_message_ok">
                    <?php _e('The data has been saved successfully.', 'cp'); ?>
                </div>
                <?php
            }
        }

        /* Add required jQuery scripts */

        function add_jquery_ui() {
            wp_enqueue_script('jquery');
            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-widget');
            wp_enqueue_script('jquery-ui-mouse');
            wp_enqueue_script('jquery-ui-accordion');
            wp_enqueue_script('jquery-ui-autocomplete');
            wp_enqueue_script('jquery-ui-slider');
            wp_enqueue_script('jquery-ui-tabs');
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('jquery-ui-draggable');
            wp_enqueue_script('jquery-ui-droppable');
            wp_enqueue_script('jquery-ui-datepicker');
            wp_enqueue_script('jquery-ui-resize');
            wp_enqueue_script('jquery-ui-dialog');
            wp_enqueue_script('jquery-ui-button');
        }

        function admin_header_actions() {
            global $wp_version;

            /* Adding menu icon font */
            if ( $wp_version >= 3.8 ) {
                wp_register_style('cp-38', $this->plugin_url . 'css/admin-icon.css');
                wp_enqueue_style('cp-38');
            }

            if ( is_admin() ) {
                if ( ( isset($_GET['cp_admin_ref']) && $_GET['cp_admin_ref'] == 'cp_course_creation_page' ) || ( isset($_POST['cp_admin_ref']) && $_POST['cp_admin_ref'] == 'cp_course_creation_page' ) ) {
                    wp_enqueue_style('admin_coursepress_marketpress_popup', $this->plugin_url . 'css/admin_marketpress_popup.css', array(), $this->version);
                }
            }

//wp_enqueue_style( 'open_sans', 'http://fonts.googleapis.com/css?family=Open+Sans:400,300,700' );
// wp_enqueue_style( 'font_awesome', 'http://netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css' );
            wp_enqueue_style('font_awesome', $this->plugin_url . 'css/font-awesome.css');
            wp_enqueue_style('admin_general', $this->plugin_url . 'css/admin_general.css', array(), $this->version);
            wp_enqueue_style('admin_general_responsive', $this->plugin_url . 'css/admin_general_responsive.css', array(), $this->version);
            /* wp_enqueue_script( 'jquery-ui-datepicker' );
              wp_enqueue_script( 'jquery-ui-accordion' );
              wp_enqueue_script( 'jquery-ui-sortable' );
              wp_enqueue_script( 'jquery-ui-resizable' );
              wp_enqueue_script( 'jquery-ui-draggable' );
              wp_enqueue_script( 'jquery-ui-droppable' ); */
//add_action( 'wp_enqueue_scripts', array( &$this, 'add_jquery_ui' ) );
            wp_enqueue_script('jquery');
//wp_enqueue_script( 'jquery-ui-core' );
            wp_enqueue_script('jquery-ui', 'http://code.jquery.com/ui/1.10.3/jquery-ui.js', array( 'jquery' ), '1.10.3'); //need to change this to built-in 
            wp_enqueue_script('jquery-ui-spinner');

// CryptoJS.MD5
            wp_enqueue_script('cryptojs-md5', $this->plugin_url . 'js/md5.js');

            // Responsive Video
            wp_enqueue_script('responsive-video', $this->plugin_url . 'js/responsive-video.js');


            if ( isset($_GET['page']) ) {
                $page = isset($_GET['page']);
            } else {
                $page = '';
            }

            $this->add_jquery_ui();

            if ( $page == 'course_details' || $page == 'settings' ) {
                wp_enqueue_style('cp_settings', $this->plugin_url . 'css/settings.css', array(), $this->version);
                wp_enqueue_style('cp_settings_responsive', $this->plugin_url . 'css/settings_responsive.css', array(), $this->version);
                wp_enqueue_style('cp_tooltips', $this->plugin_url . 'css/tooltips.css', array(), $this->version);
                wp_enqueue_script('cp-plugins', $this->plugin_url . 'js/plugins.js', array( 'jquery' ), $this->version);
                wp_enqueue_script('cp-tooltips', $this->plugin_url . 'js/tooltips.js', array( 'jquery' ), $this->version);
                wp_enqueue_script('cp-settings', $this->plugin_url . 'js/settings.js', array( 'jquery', 'jquery-ui', 'jquery-ui-spinner' ), $this->version);
                wp_enqueue_script('cp-chosen-config', $this->plugin_url . 'js/chosen-config.js', array( 'cp-settings' ), $this->version, true);
            }

            if ( $page == 'courses' || $page == 'course_details' || $page == 'instructors' || $page == 'students' || $page == 'assessment' || $page == 'reports' || $page == 'settings' || ( isset($_GET['taxonomy']) && $_GET['taxonomy'] == 'course_category' ) ) {
                wp_enqueue_script('courses_bulk', $this->plugin_url . 'js/coursepress-admin.js');
                wp_localize_script('courses_bulk', 'coursepress', array(
                    'delete_instructor_alert' => __('Please confirm that you want to remove the instructor from this course?', 'cp'),
                    'delete_pending_instructor_alert' => __('Please confirm that you want to cancel the invite. Instuctor will receive a warning when trying to activate.', 'cp'),
                    'delete_course_alert' => __('Please confirm that you want to permanently delete the course, its units, unit elements and responses?', 'cp'),
                    'delete_notification_alert' => __('Please confirm that you want to permanently delete the notification?', 'cp'),
                    'delete_discussion_alert' => __('Please confirm that you want to permanently delete the discussion?', 'cp'),
                    'withdraw_student_alert' => __('Please confirm that you want to withdraw student from this course. If you withdraw, you will no longer be able to see student\'s records for this course.', 'cp'),
                    'delete_unit_alert' => __('Please confirm that you want to permanently delete the unit, its elements and responses?', 'cp'),
                    'active_student_tab' => ( isset($_REQUEST['active_student_tab']) ? $_REQUEST['active_student_tab'] : 0 ),
                    'delete_module_alert' => __('Please confirm that you want to permanently delete selected element and its responses?', 'cp'),
                    'delete_unit_page_and_elements_alert' => __('Please confirm that you want to permanently delete this unit page, all its elements and student responses?', 'cp'),
                    'remove_unit_page_and_elements_alert' => __('Please confirm that you want to remove this unit page and all its elements?', 'cp'),
                    'remove_module_alert' => __('Please confirm that you want to remove selected element?', 'cp'),
                    'delete_unit_page_label' => __('Delete unit page and all elements', 'cp'),
                    'remove_row' => __('Remove', 'cp'),
                    'empty_class_name' => __('Class name cannot be empty', 'cp'),
                    'duplicated_class_name' => __('Class name already exists', 'cp'),
                    'course_taxonomy_screen' => ( isset($_GET['taxonomy']) && $_GET['taxonomy'] == 'course_category' ? true : false ),
                    'unit_page_num' => (isset($_GET['unit_page_num']) && $_GET['unit_page_num'] !== '' ? $_GET['unit_page_num'] : 1),
                    'allowed_video_extensions' => wp_get_video_extensions(),
                    'allowed_audio_extensions' => wp_get_audio_extensions(),
                    'allowed_image_extensions' => wp_get_image_extensions()
                ));
            }
        }

        function admin_coursepress_page_course_details() {
            wp_enqueue_script('courses-units', $this->plugin_url . 'js/coursepress-courses.js');

            wp_localize_script('courses-units', 'coursepress_units', array(
                'withdraw_class_alert' => __('Please confirm that you want to withdraw all students from this class?', 'cp'),
                'delete_class' => __('Please confirm that you want to permanently delete the class? All students form this class will be moved to the Default class automatically.', 'cp'),
                'setup_gateway' => __("You have selected 'This is a Paid Course'.\n In order to continue you must first setup a payment gateway by clicking on 'Setup Payment Gateways'", 'cp'),
                'unit_setup_prompt' => __('<div>You have successfully completed your Basic Course Setup.</div><div>This can be changed anytime by clicking on "Course Overview".</div><div>Add and create <strong>Units</strong> for your course and add <strong>Students</strong>.</div><div>You must have at least <strong>one</strong> unit created to publish the course.</div>', 'cp'),
                'required_course_name' => __('<strong>Course Name</strong> is a required field.', 'cp'),
                'required_course_excerpt' => __('<strong>Course Excerpt</strong> is a required field.', 'cp'),
                'required_course_description' => __('<strong>Course Description</strong> is a required field.', 'cp'),
                'required_course_start' => __('<strong>Course Start Date</strong> is a required field.', 'cp'),
                'required_course_end' => __('<strong>Course Start Date</strong> is a required field when "This course has no end date" is <strong>not</strong> selected.', 'cp'),
                'required_enrollment_start' => __('<strong>Enrollment Start Date</strong> is a required field when "Users can enroll anytime" is <strong>not</strong> selected.', 'cp'),
                'required_enrollment_end' => __('<strong>Enrollment End Date</strong> is a required field when "Users can enroll anytime" is <strong>not</strong> selected.', 'cp'),
                'required_course_class_size' => __('Value can not be 0 if "Limit class size" is selected.', 'cp'),
                'required_course_passcode' => __('<strong>Pass Code</strong> required when "Anyone with a pass code" is selected', 'cp'),
                'required_gateway' => __('<strong>Payment Gateway</strong> needs to be setup before you can sell this course.', 'cp'),
                'required_price' => __('<strong>Price</strong> is a required field when "This is a Paid Course" is selected.', 'cp'),
                'required_sale_price' => __('<strong>Sale Price</strong> is a required field when "Enable Sale Price" is selected.', 'cp'),
                'section_error' => __('There is some information missing or incorrect. Please check your input and try again.', 'cp'),
            ));

            wp_enqueue_style('jquery-ui-admin', $this->plugin_url . 'css/jquery-ui.css');
            wp_enqueue_style('admin_coursepress_page_course_details', $this->plugin_url . 'css/admin_coursepress_page_course_details.css', array(), $this->version);
            wp_enqueue_style('admin_coursepress_page_course_details_responsive', $this->plugin_url . 'css/admin_coursepress_page_course_details_responsive.css', array(), $this->version);
        }

        function admin_coursepress_page_settings() {
            wp_enqueue_script('settings_groups', $this->plugin_url . 'js/admin-settings-groups.js');
            wp_localize_script('settings_groups', 'group_settings', array(
                'remove_string' => __('Remove', 'cp'),
                'delete_group_alert' => __('Please confirm that you want to permanently delete the group?', 'cp')
            ));
        }

        function admin_coursepress_page_courses() {
            wp_enqueue_style('courses', $this->plugin_url . 'css/admin_coursepress_page_courses.css', array(), $this->version);
            wp_enqueue_style('courses_responsive', $this->plugin_url . 'css/admin_coursepress_page_courses_responsive.css', array(), $this->version);
        }

        function admin_coursepress_page_notifications() {
            wp_enqueue_style('notifications', $this->plugin_url . 'css/admin_coursepress_page_notifications.css', array(), $this->version);
            wp_enqueue_style('notifications_responsive', $this->plugin_url . 'css/admin_coursepress_page_notifications_responsive.css', array(), $this->version);
        }

        function admin_coursepress_page_discussions() {
            wp_enqueue_style('discussions', $this->plugin_url . 'css/admin_coursepress_page_discussions.css', array(), $this->version);
            wp_enqueue_style('discussions_responsive', $this->plugin_url . 'css/admin_coursepress_page_discussions_responsive.css', array(), $this->version);
        }

        function admin_coursepress_page_reports() {
            wp_enqueue_style('reports', $this->plugin_url . 'css/admin_coursepress_page_reports.css', array(), $this->version);
            wp_enqueue_style('reports_responsive', $this->plugin_url . 'css/admin_coursepress_page_reports_responsive.css', array(), $this->version);
            wp_enqueue_script('reports-admin', $this->plugin_url . 'js/reports-admin.js');
            wp_enqueue_style('jquery-ui-admin', $this->plugin_url . 'css/jquery-ui.css'); //need to change this to built-in
            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-tabs');
        }

        function admin_coursepress_page_assessment() {
            wp_enqueue_style('assessment', $this->plugin_url . 'css/admin_coursepress_page_assessment.css', array(), $this->version);
            wp_enqueue_style('assessment_responsive', $this->plugin_url . 'css/admin_coursepress_page_assessment_responsive.css', array(), $this->version);
            wp_enqueue_script('assessment-admin', $this->plugin_url . 'js/assessment-admin.js');
            wp_enqueue_style('jquery-ui-admin', $this->plugin_url . 'css/jquery-ui.css');
            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-tabs');
        }

        function admin_coursepress_page_students() {
            wp_enqueue_style('students', $this->plugin_url . 'css/admin_coursepress_page_students.css', array(), $this->version);
            wp_enqueue_style('students_responsive', $this->plugin_url . 'css/admin_coursepress_page_students_responsive.css', array(), $this->version);
            wp_enqueue_script('students', $this->plugin_url . 'js/students-admin.js');
            wp_localize_script('students', 'student', array(
                'delete_student_alert' => __('Please confirm that you want to remove the student and the all associated records?', 'cp'),
            ));
        }

        function admin_coursepress_page_instructors() {
            wp_enqueue_style('instructors', $this->plugin_url . 'css/admin_coursepress_page_instructors.css', array(), $this->version);
            wp_enqueue_style('instructors_responsive', $this->plugin_url . 'css/admin_coursepress_page_instructors_responsive.css', array(), $this->version);
            wp_enqueue_script('instructors', $this->plugin_url . 'js/instructors-admin.js');
            wp_localize_script('instructors', 'instructor', array(
                'delete_instructors_alert' => __('Please confirm that you want to remove the instructor and the all associated records?', 'cp'),
            ));
        }

        function create_virtual_pages() {

            // if( defined('DOING_AJAX') && DOING_AJAX ) { cp_write_log('doing ajax'); }

            $url = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

//Enrollment process page
            if ( preg_match('/' . $this->get_enrollment_process_slug() . '/', $url) ) {

                $theme_file = locate_template(array( 'enrollment-process.php' ));

                if ( $theme_file != '' ) {
                    require_once( $theme_file );
                    exit;
                } else {

                    $args = array(
                        'slug' => $this->get_enrollment_process_slug(),
                        'title' => __('Enrollment', 'cp'),
                        'content' => $this->get_template_details($this->plugin_dir . 'includes/templates/enrollment-process.php'),
                        'type' => 'virtual_page'
                    );

                    $pg = new CoursePress_Virtual_Page($args);
                }
                $this->set_latest_activity(get_current_user_id());
            }


//Custom login page
            if ( preg_match('/' . $this->get_login_slug() . '/', $url) ) {

                $theme_file = locate_template(array( 'student-login.php' ));

                if ( $theme_file != '' ) {
                    require_once( $theme_file );
                    exit;
                } else {

                    $args = array(
                        'slug' => $this->get_login_slug(),
                        'title' => __('Login', 'cp'),
                        'content' => $this->get_template_details($this->plugin_dir . 'includes/templates/student-login.php'),
                        'type' => 'virtual_page',
                        'is_page' => TRUE,
                    );
                    $pg = new CoursePress_Virtual_Page($args);
                }
                $this->set_latest_activity(get_current_user_id());
            }

//Custom signup page
            if ( preg_match('/' . $this->get_signup_slug() . '/', $url) ) {

                $theme_file = locate_template(array( 'student-signup.php' ));

                if ( $theme_file != '' ) {
                    require_once( $theme_file );
                    exit;
                } else {

                    $args = array(
                        'slug' => $this->get_signup_slug(),
                        'title' => __('Sign Up', 'cp'),
                        'content' => $this->get_template_details($this->plugin_dir . 'includes/templates/student-signup.php'),
                        'type' => 'virtual_page',
                        'is_page' => TRUE,
                    );
                    $pg = new CoursePress_Virtual_Page($args);
                }
                $this->set_latest_activity(get_current_user_id());
            }

//Student Dashboard page
            if ( preg_match('/' . $this->get_student_dashboard_slug() . '/', $url) ) {

                $theme_file = locate_template(array( 'student-dashboard.php' ));

                if ( $theme_file != '' ) {
                    require_once( $theme_file );
                    exit;
                } else {

                    $args = array(
                        'slug' => $this->get_student_dashboard_slug(),
                        'title' => __('Dashboard - Courses', 'cp'),
                        'content' => $this->get_template_details($this->plugin_dir . 'includes/templates/student-dashboard.php'),
                        'type' => 'virtual_page'
                    );
                    $pg = new CoursePress_Virtual_Page($args);
                }
                $this->set_latest_activity(get_current_user_id());
            }

//Student Settings page
            if ( preg_match('/' . $this->get_student_settings_slug() . '/', $url) ) {

                $theme_file = locate_template(array( 'student-settings.php' ));

                if ( $theme_file != '' ) {
                    require_once( $theme_file );
                    exit;
                } else {

                    $args = array(
                        'slug' => $this->get_student_settings_slug(),
                        'title' => __('Dashboard - My Profile', 'cp'),
                        'content' => $this->get_template_details($this->plugin_dir . 'includes/templates/student-settings.php'),
                        'type' => 'virtual_page'
                    );

                    $pg = new CoursePress_Virtual_Page($args);
                }
                $this->set_latest_activity(get_current_user_id());
            }
        }

        function check_for_get_actions() {

            if ( isset($_GET['withdraw']) && is_numeric($_GET['withdraw']) ) {
                $student = new Student(get_current_user_id());
                $student->withdraw_from_course($_GET['withdraw']);
            }
        }

//shows a warning notice to admins if pretty permalinks are disabled
        function admin_nopermalink_warning() {
            if ( current_user_can('manage_options') && !get_option('permalink_structure') ) {
                echo '<div class="error"><p>' . __('<strong>' . $this->name . ' is almost ready</strong>. You must <a href="options-permalink.php">update your permalink structure</a> to something other than the default for it to work.', 'cp') . '</p></div>';
            }
        }

// updates login/logout navigation link
        function menu_metabox_navigation_links( $sorted_menu_items, $args ) {
            $is_in = is_user_logged_in();

            $new_menu_items = array();
            foreach ( $sorted_menu_items as $menu_item ) {
// LOGIN / LOGOUT
                if ( CoursePress::instance()->get_login_slug(true) == $menu_item->url && $is_in ) {
                    $menu_item->post_title = __('Log Out', 'cp');
                    $menu_item->title = $menu_item->post_title;
                    $menu_item->url = wp_logout_url();
                }

// Remove personalised items
                if ( ( CoursePress::instance()->get_student_dashboard_slug(true) == $menu_item->url ||
                        CoursePress::instance()->get_student_settings_slug(true) == $menu_item->url ) &&
                        !$is_in ) {
                    continue;
                }

                $new_menu_items[] = $menu_item;
            }

            return $new_menu_items;
        }

//adds our links to custom theme nav menus using wp_nav_menu()
        function main_navigation_links( $sorted_menu_items, $args ) {
            if ( !is_admin() ) {

                $theme_location = 'primary';
//print_r(get_nav_menu_locations());
                if ( !has_nav_menu($theme_location) ) {
                    $theme_locations = get_nav_menu_locations();
                    foreach ( ( array ) $theme_locations as $key => $location ) {
                        $theme_location = $key;
                        break;
                    }
                }

                if ( $args->theme_location == $theme_location ) {//put extra menu items only in primary ( most likely header ) menu
                    $is_in = is_user_logged_in();

                    $courses = new stdClass;

                    $courses->title = __('Courses', 'cp');
                    $courses->menu_item_parent = 0;
                    $courses->ID = 'cp-courses';
                    $courses->db_id = '';
                    $courses->url = trailingslashit(site_url() . '/' . $this->get_course_slug());
                    $sorted_menu_items[] = $courses;

                    /* Student Dashboard page */

                    if ( $is_in ) {
                        $dashboard = new stdClass;

                        $dashboard->title = __('Dashboard', 'cp');
                        $dashboard->menu_item_parent = 0;
                        $dashboard->ID = 'cp-dashboard';
                        $dashboard->db_id = -9998;
                        $dashboard->url = trailingslashit(site_url() . '/' . $this->get_student_dashboard_slug());
                        $sorted_menu_items[] = $dashboard;
                        $dashboard->classes[] = 'dropdown';

                        /* Student Dashboard > Courses page */

                        $dashboard_courses = new stdClass;
                        $dashboard_courses->title = __('My Courses', 'cp');
                        $dashboard_courses->menu_item_parent = -9998;
                        $dashboard_courses->ID = 'cp-dashboard-courses';
                        $dashboard_courses->db_id = '';
                        $dashboard_courses->url = trailingslashit(site_url() . '/' . $this->get_student_dashboard_slug());
                        $sorted_menu_items[] = $dashboard_courses;

                        /* Student Dashboard > Settings page */

                        $settings = new stdClass;

                        $settings->title = __('My Profile', 'cp');
                        $settings->menu_item_parent = -9998;
                        $settings->ID = 'cp-dashboard-settings';
                        $settings->db_id = '';
                        $settings->url = trailingslashit(site_url() . '/' . $this->get_student_settings_slug());
                        $sorted_menu_items[] = $settings;
                    }

                    /* Sign up page */

                    // $signup = new stdClass;
                    //
                    // if ( !$is_in ) {
                    //     $signup->title = __('Sign Up', 'cp');
                    //     $signup->menu_item_parent = 0;
                    //     $signup->ID = 'cp-signup';
                    //     $signup->db_id = '';
                    //     $signup->url = trailingslashit(site_url() . '/' . $this->get_signup_slug());
                    //     $sorted_menu_items[] = $signup;
                    // }

                    /* Log in / Log out links */

                    $login = new stdClass;
                    if ( $is_in ) {
                        $login->title = __('Log Out', 'cp');
                    } else {
                        $login->title = __('Log In', 'cp');
                    }

                    $login->menu_item_parent = 0;
                    $login->ID = 'cp-logout';
                    $login->db_id = '';
                    $login->url = $is_in ? wp_logout_url() : ( get_option('use_custom_login_form', 1) ? trailingslashit(site_url() . '/' . $this->get_login_slug()) : wp_login_url() );

                    $sorted_menu_items[] = $login;
                }

                return $sorted_menu_items;
            }
        }

        function main_navigation_links_fallback( $current_menu ) {

            if ( !is_admin() ) {
                $is_in = is_user_logged_in();

                $courses = new stdClass;

                $courses->title = __('Courses', 'cp');
                $courses->menu_item_parent = 0;
                $courses->ID = 'cp-courses';
                $courses->db_id = '';
                $courses->url = trailingslashit(site_url() . '/' . $this->get_course_slug());
                $main_sorted_menu_items[] = $courses;

                /* Student Dashboard page */

                if ( $is_in ) {
                    $dashboard = new stdClass;

                    $dashboard->title = __('Dashboard', 'cp');
                    $dashboard->menu_item_parent = 0;
                    $dashboard->ID = 'cp-dashboard';
                    $dashboard->db_id = -9998;
                    $dashboard->url = trailingslashit(site_url() . '/' . $this->get_student_dashboard_slug());
                    $main_sorted_menu_items[] = $dashboard;

                    /* Student Dashboard > Courses page */

                    $dashboard_courses = new stdClass;
                    $dashboard_courses->title = __('My Courses', 'cp');
                    $dashboard_courses->menu_item_parent = -9998;
                    $dashboard_courses->ID = 'cp-dashboard-courses';
                    $dashboard_courses->db_id = '';
                    $dashboard_courses->url = trailingslashit(site_url() . '/' . $this->get_student_dashboard_slug());
                    $sub_sorted_menu_items[] = $dashboard_courses;

                    /* Student Dashboard > Settings page */

                    $settings = new stdClass;

                    $settings->title = __('My Profile', 'cp');
                    $settings->menu_item_parent = -9998;
                    $settings->ID = 'cp-dashboard-settings';
                    $settings->db_id = '';
                    $settings->url = trailingslashit(site_url() . '/' . $this->get_student_settings_slug());
                    $sub_sorted_menu_items[] = $settings;
                }

                /* Sign up page */

                // $signup = new stdClass;
                //
                // if ( !$is_in ) {
                //     $signup->title = __('Sign Up', 'cp');
                //     $signup->menu_item_parent = 0;
                //     $signup->ID = 'cp-signup';
                //     $signup->db_id = '';
                //     $signup->url = trailingslashit(site_url() . '/' . $this->get_signup_slug());
                //     $main_sorted_menu_items[] = $signup;
                // }

                /* Log in / Log out links */

                $login = new stdClass;
                if ( $is_in ) {
                    $login->title = __('Log Out', 'cp');
                } else {
                    $login->title = __('Log In', 'cp');
                }

                $login->menu_item_parent = 0;
                $login->ID = 'cp-logout';
                $login->db_id = '';
                $login->url = $is_in ? wp_logout_url() : ( get_option('use_custom_login_form', 1) ? trailingslashit(site_url() . '/' . $this->get_login_slug()) : wp_login_url() );

                $main_sorted_menu_items[] = $login;
                ?>
                <div class="menu">
                    <ul class='nav-menu'>
                        <?php
                        foreach ( $main_sorted_menu_items as $menu_item ) {
                            ?>
                            <li class='menu-item-<?php echo $menu_item->ID; ?>'><a id="<?php echo $menu_item->ID; ?>" href="<?php echo $menu_item->url; ?>"><?php echo $menu_item->title; ?></a>
                                <?php if ( $menu_item->db_id !== '' ) { ?>
                                    <ul class="sub-menu dropdown-menu">
                                        <?php
                                        foreach ( $sub_sorted_menu_items as $menu_item ) {
                                            ?>
                                            <li class='menu-item-<?php echo $menu_item->ID; ?>'><a id="<?php echo $menu_item->ID; ?>" href="<?php echo $menu_item->url; ?>"><?php echo $menu_item->title; ?></a></li>
                                            <?php } ?>
                                    </ul>
                                <?php } ?>
                            </li>
                            <?php
                        }
                        ?>
                    </ul>
                </div>

                <?php
            }
        }

        function login_redirect( $redirect_to, $request, $user ) {

            if ( defined('DOING_AJAX') && 'DOING_AJAX ' ) {
                exit;
            }
            global $user;

            if ( isset($user->ID) ) {

                if ( current_user_can('manage_options') ) {
                    return admin_url();
                } else {
                    $role_s = get_user_meta($user->ID, 'role', true);
                    $role_i = get_user_meta($user->ID, 'role_ins', true);

                    if ( $role_i == 'instructor' ) {
                        return admin_url();
                    } else if ( $role_s == 'student' || $role_s == false || $role_s == '' ) {
                        return trailingslashit(site_url()) . trailingslashit($this->get_student_dashboard_slug());
                    } else {//unknown case
                        return admin_url();
                    }
                }
            }
        }

        function no_comments_template( $template ) {
            global $post;

            if ( 'virtual_page' == $post->post_type ) {
                $template = $this->plugin_dir . 'includes/templates/no-comments.php';
            }

            return $template;
        }

        function comments_template( $template ) {
            global $wp_query, $withcomments, $post, $wpdb, $id, $comment, $user_login, $user_ID, $user_identity, $overridden_cpage;

            if ( get_post_type($id) == 'course' ) {
                $template = $this->plugin_dir . 'includes/templates/no-comments.php';
            }
            return $template;
        }

        function check_for_valid_post_type_permalinks( $permalink, $post, $leavename ) {
            if ( get_post_type($post->ID) == 'discussions' ) {
                $course_id = get_post_meta($post->ID, 'course_id', true);
                if ( !empty($course_id) ) {
                    $course_obj = new Course($course_id);
                    $course = $course_obj->get_course();
                    return str_replace('%course%', $course->post_name, $permalink);
                } else {
                    return $permalink;
                }
            } else if ( get_post_type($post->ID) == 'notifications' ) {
                $course_id = get_post_meta($post->ID, 'course_id', true);
                if ( !empty($course_id) ) {
                    $course_obj = new Course($course_id);
                    $course = $course_obj->get_course();
                    return str_replace('%course%', $course->post_name, $permalink);
                } else {
                    return $permalink;
                }
            } else if ( get_post_type($post->ID) == 'unit' ) {
                $unit = new Unit($post->ID);
                return $unit->get_permalink();
            } else {
                return $permalink;
            }
        }

        function output_buffer() {
            // if( defined('DOING_AJAX') && DOING_AJAX ) { cp_write_log('doing ajax'); }
            ob_start();
        }

        /* Check if user is currently active on the website */

        function user_is_currently_active( $user_id, $latest_activity_in_minutes = 5 ) {
            if ( empty($user_id) ) {
                exit;
            }
            $latest_user_activity = get_user_meta($user_id, 'latest_activity', true);
            $current_time = current_time('timestamp');

            $minutes_ago = round(abs($current_time - $latest_user_activity) / 60, 2);

            if ( $minutes_ago <= $latest_activity_in_minutes ) {
                return true;
            } else {
                return false;
            }
        }

        /* Check if MarketPress plugin is installed and active ( using in Course Overview ) */

        function is_marketpress_active() {
            $plugins = get_option('active_plugins');

            if ( is_multisite() ) {
                $active_sitewide_plugins = get_site_option("active_sitewide_plugins");
            } else {
                $active_sitewide_plugins = array();
            }

            $required_plugin = 'marketpress/marketpress.php';

            if ( in_array($required_plugin, $plugins) || is_plugin_network_active($required_plugin) || preg_grep('/^marketpress.*/', $plugins) || preg_array_key_exists('/^marketpress.*/', $active_sitewide_plugins) ) {
                return true;
            } else {
                return false;
            }
        }

        /* Check if MarketPress Lite plugin is installed and active */

        function is_marketpress_lite_active() {
            $plugins = get_option('active_plugins');

            if ( is_multisite() ) {
                $active_sitewide_plugins = get_site_option("active_sitewide_plugins");
            } else {
                $active_sitewide_plugins = array();
            }

            $required_plugin = 'wordpress-ecommerce/marketpress.php';

            if ( in_array($required_plugin, $plugins) || is_plugin_network_active($required_plugin) || preg_grep('/^marketpress.*/', $plugins) || preg_array_key_exists('/^marketpress.*/', $active_sitewide_plugins) ) {
                return true;
            } else {
                return false;
            }
        }

        /* Check if MarketPress Lite ( included in CoursePress ) plugin is installed and active */

        function is_cp_marketpress_lite_active() {
            $plugins = get_option('active_plugins');

            if ( is_multisite() ) {
                $active_sitewide_plugins = get_site_option("active_sitewide_plugins");
            } else {
                $active_sitewide_plugins = array();
            }

            $required_plugin = 'coursepress/marketpress.php';

            if ( in_array($required_plugin, $plugins) || is_plugin_network_active($required_plugin) || preg_grep('/^marketpress.*/', $plugins) || preg_array_key_exists('/^marketpress.*/', $active_sitewide_plugins) ) {
                return true;
            } else {
                return false;
            }
        }

        function marketpress_check() {
            if ( CoursePress::instance()->is_marketpress_lite_active() || CoursePress::instance()->is_cp_marketpress_lite_active() || CoursePress::instance()->is_marketpress_active() ) {
                CoursePress::instance()->marketpress_active = true;
            } else {
                CoursePress::instance()->marketpress_active = false;
            }
        }

        /* Check if Chat plugin is installed and activated ( using in Chat unit module ) */

        function is_chat_plugin_active() {
            $plugins = get_option('active_plugins');

            if ( is_multisite() ) {
                $active_sitewide_plugins = get_site_option("active_sitewide_plugins");
            } else {
                $active_sitewide_plugins = array();
            }

            $required_plugin = 'wordpress-chat/wordpress-chat.php';

            if ( in_array($required_plugin, $plugins) || is_plugin_network_active($required_plugin) || preg_grep('/^wordpress-chat.*/', $plugins) || preg_array_key_exists('/^wordpress-chat.*/', $active_sitewide_plugins) ) {
                return true;
            } else {
                return false;
            }
        }

        /* Listen for MarketPress purchase status changes */

        function listen_for_paid_status_for_courses( $order ) {
            global $mp;

            $purchase_order = $mp->get_order($order->ID);
            $product_id = key($purchase_order->mp_cart_info);

            $course_details = Course::get_course_by_marketpress_product_id($product_id);

            if ( $course_details && !empty($course_details) ) {
                $student = new Student($order->post_author);
                $student->enroll_in_course($course_details->ID);
            }
        }

        /* Make PDF report */

        function pdf_report( $report = '', $report_name = '', $report_title = 'Student Report', $preview = false ) {
//ob_end_clean();
            ob_start();

            include_once( $this->plugin_dir . 'includes/external/tcpdf/config/lang/eng.php' );
            require_once( $this->plugin_dir . 'includes/external/tcpdf/tcpdf.php' );

// create new PDF document
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
            $pdf->SetCreator($this->name);
            $pdf->SetTitle($report_title);
            $pdf->SetKeywords('');

// remove default header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

// set default monospaced font
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

//set margins
            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

//set auto page breaks
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

//set image scale factor
//$pdf->setImageScale( PDF_IMAGE_SCALE_RATIO );
//set some language-dependent strings
            $pdf->setLanguageArray($l);
// ---------------------------------------------------------
// set font
            $pdf->SetFont('helvetica', '', 12);
// add a page
            $pdf->AddPage();
            $html = '';
            $html .= make_clickable(wpautop($report));
// output the HTML content
            $pdf->writeHTML($html, true, false, true, false, '');
//Close and output PDF document

            ob_get_clean();

            if ( $preview ) {
                $pdf->Output($report_name, 'I');
            } else {
                $pdf->Output($report_name, 'D');
            }

            exit;
        }

        public static function instance( $instance = null ) {
            if ( !$instance || 'CoursePress' != get_class($instance) ) {
                if ( is_null(self::$instance) ) {
                    self::$instance = new CoursePress();
                }
            } else {
                if ( is_null(self::$instance) ) {
                    self::$instance = $instance;
                }
            }
            return self::$instance;
        }

    }

}

CoursePress::instance(new CoursePress());
global $coursepress;
$coursepress = CoursePress::instance();
?>
