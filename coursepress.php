<?php
/*
  Plugin Name: CoursePress
  Plugin URI: http://premium.wpmudev.org/project/coursepress/
  Description: CoursePress turns WordPress into a powerful learning management system. Set up online courses, create learning units, invite/enroll students to a course. More coming soon!
  Author: Marko Miljus (Incsub)
  Author URI: http://premium.wpmudev.org
  Version: 0.9.2 b
  TextDomain: cp
  Domain Path: /languages/
  WDP ID: XXX
  License: GNU General Public License (Version 2 - GPLv2)

  Copyright 2007-2013 Incsub (http://incsub.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
  the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('CoursePress')) {

    class CoursePress {

        var $version = '0.9.2 b';
        var $name = 'CoursePress';
        var $dir_name = 'coursepress';
        var $location = '';
        var $plugin_dir = '';
        var $plugin_url = '';

        function CoursePress() {
            $this->__construct();
        }

        function __construct() {

            //setup our variables
            $this->init_vars();

            //Register Globals
            $GLOBALS['plugin_dir'] = $this->plugin_dir;
            $GLOBALS['course_slug'] = $this->get_course_slug();
            $GLOBALS['units_slug'] = $this->get_units_slug();
            $GLOBALS['module_slug'] = $this->get_module_slug();
            $GLOBALS['instructor_profile_slug'] = $this->get_instructor_profile_slug();
            $GLOBALS['enrollment_process_url'] = $this->get_enrollment_process_slug(true);
            $GLOBALS['signup_url'] = $this->get_signup_slug(true);

            // Load the common functions
            require_once('includes/functions.php');

            //install plugin
            register_activation_hook(__FILE__, array($this, 'install'));

            global $last_inserted_unit_id;

            //Administration area
            if (is_admin()) {

                // Support for WPMU DEV Dashboard plugin
                include_once( $this->plugin_dir . 'includes/external/wpmudev-dash-notification.php' );

                // Course search
                require_once( $this->plugin_dir . 'includes/classes/class.coursesearch.php' );

                // Contextual help
                require_once( $this->plugin_dir . 'includes/classes/class.help.php' );

                // Search Students class
                require_once( $this->plugin_dir . 'includes/classes/class.studentsearch.php' );

                // Search Instructor class
                require_once( $this->plugin_dir . 'includes/classes/class.instructorsearch.php' );

                //Pagination Class
                require_once( $this->plugin_dir . 'includes/classes/class.pagination.php');

                add_action('wp_ajax_dynamic_wp_editor', array(&$this, 'dynamic_wp_editor'));
            }

            // Instructor class
            require_once( $this->plugin_dir . 'includes/classes/class.instructor.php' );

            // Unit class
            require_once( $this->plugin_dir . 'includes/classes/class.course.unit.php' );

            // Course class
            require_once( $this->plugin_dir . 'includes/classes/class.course.php' );

            // Student class
            require_once( $this->plugin_dir . 'includes/classes/class.student.php' );

            // Unit module class
            require_once( $this->plugin_dir . 'includes/classes/class.course.unit.module.php' );

            //Load unit modules
            $this->load_modules();

            // Shortcodes class
            require_once( $this->plugin_dir . 'includes/classes/class.shortcodes.php' );

            // Virtual page class
            require_once( $this->plugin_dir . 'includes/classes/class.virtualpage.php' );

            //Localize the plugin
            add_action('plugins_loaded', array(&$this, 'localization'), 9);

            //Output buffer hack
            //add_action('init', array(&$this, 'output_buffer'), 0);
            //Register custom post types
            add_action('init', array(&$this, 'register_custom_posts'), 1);

            //Add virtual pages
            add_action('init', array(&$this, 'create_virtual_pages'), 99);

            //Check for $_GET actions
            add_action('init', array(&$this, 'check_for_get_actions'), 98);

            //Add plugin admin menu - Network
            add_action('network_admin_menu', array(&$this, 'add_admin_menu_network'));

            //Add plugin admin menu
            add_action('admin_menu', array(&$this, 'add_admin_menu'));

            //Check for admin notices
            add_action('admin_notices', array(&$this, 'admin_nopermalink_warning'));

            //Custom header actions
            add_action('wp_enqueue_scripts', array(&$this, 'header_actions'));


            add_action('admin_enqueue_scripts', array(&$this, 'admin_header_actions'));
            add_action('load-coursepress_page_course_details', array(&$this, 'admin_coursepress_page_course_details'));
            add_action('load-coursepress_page_settings', array(&$this, 'admin_coursepress_page_settings'));
            add_action('load-toplevel_page_courses', array(&$this, 'admin_coursepress_page_courses'));
            add_action('load-coursepress_page_reports', array(&$this, 'admin_coursepress_page_reports'));
            add_action('load-coursepress_page_students', array(&$this, 'admin_coursepress_page_students'));
            add_action('load-coursepress_page_instructors', array(&$this, 'admin_coursepress_page_instructors'));

            add_filter('login_redirect', array(&$this, 'login_redirect'), 10, 3);

            add_filter('comments_open', array(&$this, 'check_for_discussion_form'), 10, 2);
            //add_filter('comment_post_redirect', array(&$this, 'check_for_comment_redirect_after_post'), 10, 2);
            add_filter('post_type_link', array(&$this, 'check_for_valid_post_type_permalinks'), 10, 3);

            add_filter('get_comment_link', array(&$this, 'get_comment_link'), 10, 3);
            add_filter('comments_template', array(&$this, 'comments_template'));

            add_filter('upload_mimes', array(&$this, 'add_custom_upload_mimes'));
            // Add Filter Hook  
            add_filter('post_mime_types', array(&$this, 'modify_post_mime_types'));

            // Load payment gateways
            $this->load_payment_gateways();

            //Load add-ons
            $this->load_addons();

            //update install script if necessary
            /* if (get_option('coursepress_version') != $this->version) {
              $this->install();
              } */

            add_action('wp', array(&$this, 'load_plugin_templates'));
            add_filter('rewrite_rules_array', array(&$this, 'add_rewrite_rules'));
            add_action('pre_get_posts', array(&$this, 'remove_canonical'));
            add_action('option_rewrite_rules', array(&$this, 'check_rewrite_rules'));
            add_action('wp_ajax_update_units_positions', array($this, 'update_units_positions'));
            add_filter('query_vars', array($this, 'units_filter_query_vars'));
            add_action('parse_request', array($this, 'action_parse_request'));
            add_action('admin_init', array(&$this, 'coursepress_plugin_do_activation_redirect'));

            if (get_option('display_menu_items', 1)) {
                add_filter('wp_nav_menu_objects', array(&$this, 'main_navigation_links'), 10, 2);
            }
        }

        function add_custom_upload_mimes($existing_mimes = array()) {
            // Add file extension 'extension' with mime type 'mime/type'
            $existing_mimes['ai'] = 'application/postscript';
            $existing_mimes['psd'] = 'application/octet-stream';

            return $existing_mimes;
        }

        function modify_post_mime_types($post_mime_types) {
            $post_mime_types['application/postscript'] = array(__('AIs'), __('Manage AIs'), _n_noop('AI <span class="count">(%s)</span>', 'AIs <span class="count">(%s)</span>'));
            $post_mime_types['application/octet-stream'] = array(__('PSDs'), __('Manage PSDs'), _n_noop('PSD <span class="count">(%s)</span>', 'PSDs <span class="count">(%s)</span>'));

            // then we return the $post_mime_types variable  
            return $post_mime_types;
        }

        function dynamic_wp_editor() {
            $id = 'editor_' . rand(0, 999) . rand(0, 999);
            $args = array("textarea_name" => $id . "_content[]", "textarea_rows" => 5);

            wp_editor('', $id, $args);
            ?>
            <script>
                //init quicktags

                /*quicktags({id:<?php echo $id; ?>});
                             
                 //init tinymce
                 tinyMCE.init({
                 theme : "advanced",
                 skin: "wp_theme",
                 buttons:"strong,em,link,block,del,ins,img,ul,ol,li,code,more,close"
                 // other options here
                 });
                             
                 tinyMCE.execCommand('mceAddControl', false, '<?php echo $id; ?>');
                 //tinymce.init(tinyMCEPreInit.mceInit['<?php echo $id; ?>']);*/
            </script>
            <?php
            die(0);
        }

        function load_plugin_templates() {
            global $wp_query;

            if (get_query_var('course') != '') {
                add_filter('the_content', array(&$this, 'add_custom_before_course_single_content'), 1);
            }

            if (get_post_type() == 'course' && is_archive()) {
                add_filter('the_content', array(&$this, 'courses_archive_custom_content'), 1);
            }
        }

        function remove_canonical($wp_query) {
            global $wp_query;
            if (is_admin())
                return;

            //stop canonical problems with virtual pages redirecting
            $page = get_query_var('pagename');
            $course = get_query_var('course');

            if ($page == 'dashboard' or $course !== '') {
                remove_action('template_redirect', 'redirect_canonical');
            }
        }

        function action_parse_request(&$wp) {

            /* Show Instructor single template */
            if (array_key_exists('instructor_username', $wp->query_vars)) {
                $vars = array();
                $vars['instructor_username'] = $wp->query_vars['instructor_username'];
                $vars['user'] = get_userdatabynicename($wp->query_vars['instructor_username']);

                $theme_file = locate_template(array('instructor-single.php'));

                if ($theme_file != '') {
                    require_once($theme_file);
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
            }

            /* Show Units archive template */
            if (array_key_exists('coursename', $wp->query_vars) && !array_key_exists('unitname', $wp->query_vars)) {

                $vars = array();
                $course = new Course();

                $vars['course_id'] = $course->get_course_id_by_name($wp->query_vars['coursename']);

                /* $theme_file = locate_template(array('archive-unit.php'));

                  if ($theme_file != '') {
                  do_shortcode('[course_units_loop]');
                  require_once($theme_file);
                  exit;
                  } else {
                  $args = array(
                  'slug' => $wp->request,
                  'title' => __('Course Units', 'cp'),
                  'content' => $this->get_template_details($this->plugin_dir . 'includes/templates/course-units-archive.php', $vars),
                  'is_page' => FALSE,
                  'is_singular' => FALSE,
                  'is_archive' => TRUE
                  );

                  $pg = new CoursePress_Virtual_Page($args);
                  do_shortcode('[course_units_loop]');
                  } */
                $theme_file = locate_template(array('archive-unit.php'));

                if ($theme_file != '') {
                    do_shortcode('[course_units_loop]');
                    require_once($theme_file);
                    exit;
                } else {

                    $args = array(
                        'slug' => $wp->request,
                        'title' => __('Course Units', 'cp'),
                        'content' => $this->get_template_details($this->plugin_dir . 'includes/templates/course-units-archive.php', $vars),
                        'type' => 'unit',
                        'is_page' => FALSE,
                        'is_singular' => FALSE,
                        'is_archive' => TRUE
                    );
                    $pg = new CoursePress_Virtual_Page($args);
                    do_shortcode('[course_units_loop]');
                }
            }

            /* Show Unit single template */
            if (array_key_exists('coursename', $wp->query_vars) && array_key_exists('unitname', $wp->query_vars)) {

                $vars = array();
                $course = new Course();
                $unit = new Unit();

                $vars['course_id'] = $course->get_course_id_by_name($wp->query_vars['coursename']);
                $vars['unit_id'] = $unit->get_unit_id_by_name($wp->query_vars['unitname']);

                $unit = new Unit($vars['unit_id']);

                $theme_file = locate_template(array('single-unit.php'));

                if ($theme_file != '') {
                    do_shortcode('[course_unit_single]'); //required for getting unit results
                    require_once($theme_file);
                    exit;
                } else {

                    $args = array(
                        'slug' => $wp->request,
                        'title' => $unit->details->post_title,
                        'content' => $this->get_template_details($this->plugin_dir . 'includes/templates/course-units-single.php', $vars),
                        'type' => 'unit',
                        'is_page' => FALSE,
                        'is_singular' => TRUE,
                        'is_archive' => FALSE
                    );

                    $pg = new CoursePress_Virtual_Page($args);
                }
            }
        }

        function units_filter_query_vars($query_vars) {
            $query_vars[] = 'coursename';
            $query_vars[] = 'unitname';
            $query_vars[] = 'instructor_username';
            return $query_vars;
        }

        function add_rewrite_rules($rules) {

            $new_rules = array();
            $new_rules['^' . $this->get_course_slug() . '/([^/]*)/' . $this->get_units_slug() . '/([^/]*)/?'] = 'index.php?page_id=-1&coursename=$matches[1]&unitname=$matches[2]';
            $new_rules['^' . $this->get_course_slug() . '/([^/]*)/' . $this->get_units_slug()] = 'index.php?page_id=-1&coursename=$matches[1]';
            $new_rules['^' . $this->get_instructor_profile_slug() . '/([^/]*)/?'] = 'index.php?page_id=-1&instructor_username=$matches[1]';

            return array_merge($new_rules, $rules);
        }

        function add_custom_before_course_single_content($content) {
            if (get_post_type() == 'course') {
                if (is_single()) {

                    wp_enqueue_style('front_course_single', $this->plugin_url . 'css/front_course_single.css');

                    if (locate_template(array('single-course.php'))) {//add custom content in the single template ONLY if the post type doesn't already has its own template
                        //just output the content
                    } else {
                        $prepend_content = $this->get_template_details($this->plugin_dir . 'includes/templates/single-course-before-details.php');

                        $content = $prepend_content . $content;
                    }
                }
            }
            return $content;
        }

        function courses_archive_custom_content($content) {
            global $post;
            $content = $post->post_excerpt;
            return $content;
        }

        function get_template_details($template, $args = array()) {
            ob_start();
            extract($args);
            require_once($template);
            return ob_get_clean();
        }

        function load_custom_template($template_path) {
            if (get_post_type() == 'course') {
                if (is_single()) {
                    // checks if the file exists in the theme first,
                    // otherwise serve the file from the plugin
                    //if ($theme_file = locate_template(array('single-course.php'))) {
                    //    $template_path = $theme_file;
                    //} else {
                    //$template_path = $this->plugin_dir . 'includes/templates/single-course.php';
                    //}
                }
            }
            return $template_path;
        }

        function update_units_positions() {
            global $wpdb;

            $positions = explode(",", $_REQUEST['positions']);
            $response = '';
            $i = 1;
            foreach ($positions as $position) {
                $response .= 'Position #' . $i . ': ' . $position . '<br />';
                update_post_meta($position, 'unit_order', $i);
                $i++;
            }
            //echo $response; //just for debugging purposes
            die();
        }

        function check_rewrite_rules() {
            flush_rewrite_rules();
        }

        function flush_rewrite_rules() {
            flush_rewrite_rules();
        }

        function dev_check_current_screen() {
            if (!is_admin())
                return;

            global $current_screen;

            print_r($current_screen);
        }

        function install() {
            update_option('display_menu_items', 0); //T-E-M-P-O-R-A-R-Y J-U-S-T O-N L-I-V-E D-E-V, otherwise it will be active by default
            $this->coursepress_plugin_activate();
            update_option('coursepress_version', $this->version);
            $this->add_user_roles_and_caps(); //This setting is saved to the database (in table wp_options, field wp_user_roles), so it might be better to run this on theme/plugin activation
            //Set default course groups
            if (!get_option('course_groups')) {
                $default_groups = range('A', 'Z');
                update_option('course_groups', $default_groups);
            }

            //Redirect to Create New Course page
            require(ABSPATH . WPINC . '/pluggable.php');

            add_action('admin_init', 'my_plugin_redirect');
        }

        function coursepress_plugin_do_activation_redirect() {
            if (get_option('coursepress_plugin_do_first_activation_redirect', false)) {
                delete_option('coursepress_plugin_do_first_activation_redirect');
                wp_redirect(trailingslashit(site_url()) . 'wp-admin/admin.php?page=courses&quick_setup');
                exit;
            }
        }

        function coursepress_plugin_activate() {
            add_option('coursepress_plugin_do_first_activation_redirect', true);
        }

        function set_course_slug($slug = '') {
            if ($slug == '') {
                update_option('coursepress_course_slug', get_course_slug());
            } else {
                update_option('coursepress_course_slug', $slug);
            }
        }

        function get_course_slug() {
            $default_slug_value = 'courses';
            return get_option('coursepress_course_slug', $default_slug_value);
        }

        function get_module_slug() {
            $default_slug_value = 'module';
            return get_option('coursepress_module_slug', $default_slug_value);
        }

        function get_units_slug() {
            $default_slug_value = 'units';
            return get_option('coursepress_units_slug', $default_slug_value);
        }

        function get_enrollment_process_slug($url = false) {
            $default_slug_value = 'enrollment-process';
            if (!$url) {
                return get_option('enrollment_process_slug', $default_slug_value);
            } else {
                return site_url() . '/' . get_option('enrollment_process_slug', $default_slug_value);
            }
        }

        function get_student_dashboard_slug($url = false) {
            $default_slug_value = 'courses-dashboard';
            if (!$url) {
                return get_option('student_dashboard_slug', $default_slug_value);
            } else {
                return site_url() . '/' . get_option('student_dashboard_slug', $default_slug_value);
            }
        }

        function get_student_settings_slug($url = false) {
            $default_slug_value = 'settings';
            if (!$url) {
                return get_option('student_settings_slug', $default_slug_value);
            } else {
                return site_url() . '/' . get_option('student_settings_slug', $default_slug_value);
            }
        }

        function get_instructor_profile_slug() {
            $default_slug_value = 'instructor';
            return get_option('instructor_profile_slug', $default_slug_value);
        }

        function get_signup_slug($url = false) {
            $default_slug_value = 'courses-signup';
            if (!$url) {
                return get_option('signup_slug', $default_slug_value);
            } else {
                return site_url() . '/' . get_option('signup_slug', $default_slug_value);
            }
        }

        function localization() {
            // Load up the localization file if we're using WordPress in a different language
            if ($this->location == 'mu-plugins') {
                load_muplugin_textdomain('cp', '/languages/');
            } else if ($this->location == 'subfolder-plugins') {
                load_plugin_textdomain('cp', false, '/' . $this->plugin_dir . '/languages/');
            } else if ($this->location == 'plugins') {
                load_plugin_textdomain('cp', false, '/languages/');
            }
        }

        function init_vars() {
            //setup proper directories
            if (defined('WP_PLUGIN_URL') && defined('WP_PLUGIN_DIR') && file_exists(WP_PLUGIN_DIR . '/' . $this->dir_name . '/' . basename(__FILE__))) {
                $this->location = 'subfolder-plugins';
                $this->plugin_dir = WP_PLUGIN_DIR . '/' . $this->dir_name . '/';
                $this->plugin_url = plugins_url('/', __FILE__);
            } else if (defined('WP_PLUGIN_URL') && defined('WP_PLUGIN_DIR') && file_exists(WP_PLUGIN_DIR . '/' . basename(__FILE__))) {
                $this->location = 'plugins';
                $this->plugin_dir = WP_PLUGIN_DIR . '/';
                $this->plugin_url = plugins_url('/', __FILE__);
            } else if (is_multisite() && defined('WPMU_PLUGIN_URL') && defined('WPMU_PLUGIN_DIR') && file_exists(WPMU_PLUGIN_DIR . '/' . basename(__FILE__))) {
                $this->location = 'mu-plugins';
                $this->plugin_dir = WPMU_PLUGIN_DIR;
                $this->plugin_url = WPMU_PLUGIN_URL;
            } else {
                wp_die(sprintf(__('There was an issue determining where %s is installed. Please reinstall it.', 'cp'), $this->name));
            }
        }

        //Load payment gateways
        function load_payment_gateways() {
            if (is_dir($this->plugin_dir . 'includes/gateways')) {
                if ($dh = opendir($this->plugin_dir . 'includes/gateways')) {
                    $mem_gateways = array();
                    while (( $gateway = readdir($dh) ) !== false)
                        if (substr($gateway, -4) == '.php')
                            $mem_gateways[] = $gateway;
                    closedir($dh);
                    sort($mem_gateways);

                    foreach ($mem_gateways as $mem_gateway)
                        include_once( $this->plugin_dir . 'includes/gateways/' . $mem_gateway );
                }
            }

            do_action('coursepress_gateways_loaded');
        }

        //Load plugin add-ons
        function load_addons() {
            if (is_dir($this->plugin_dir . 'includes/add-ons')) {
                if ($dh = opendir($this->plugin_dir . 'includes/add-ons')) {
                    $mem_addons = array();
                    while (( $addon = readdir($dh) ) !== false)
                        if (substr($addon, -4) == '.php')
                            $mem_addons[] = $addon;
                    closedir($dh);
                    sort($mem_addons);

                    foreach ($mem_addons as $mem_addon)
                        include_once( $this->plugin_dir . 'includes/add-ons/' . $mem_addon );
                }
            }

            do_action('coursepress_addons_loaded');
        }

        //Load unit modules
        function load_modules() {
            global $mem_modules;

            if (is_dir($this->plugin_dir . 'includes/unit-modules')) {
                if ($dh = opendir($this->plugin_dir . 'includes/unit-modules')) {
                    $mem_modules = array();
                    while (( $module = readdir($dh) ) !== false)
                        if (substr($module, -4) == '.php')
                            $mem_modules[] = $module;
                    closedir($dh);
                    sort($mem_modules);

                    foreach ($mem_modules as $mem_module)
                        include_once( $this->plugin_dir . 'includes/unit-modules/' . $mem_module );
                }
            }

            do_action('coursepress_modules_loaded');
        }

        function add_admin_menu_network() {
            
        }

        //Add plugin admin menu items
        function add_admin_menu() {

            // Add the menu page
            add_menu_page($this->name, $this->name, 'coursepress_dashboard_cap', 'courses', array(&$this, 'coursepress_courses_admin'), $this->plugin_url . 'images/coursepress-icon.png');

            do_action('coursepress_add_menu_items_up');

            // Add the sub menu items
            add_submenu_page('courses', __('Courses', 'cp'), __('Courses', 'cp'), 'coursepress_courses_cap', 'courses', array(&$this, 'coursepress_courses_admin'));
            do_action('coursepress_add_menu_items_after_courses');

            if (isset($_GET['page']) && $_GET['page'] == 'course_details' && isset($_GET['course_id'])) {
                $new_or_current_course_menu_item_title = __('Course', 'cp');
            } else {
                $new_or_current_course_menu_item_title = __('New Course', 'cp');
            }

            add_submenu_page('courses', $new_or_current_course_menu_item_title, $new_or_current_course_menu_item_title, 'coursepress_courses_cap', 'course_details', array(&$this, 'coursepress_course_details_admin'));
            do_action('coursepress_add_menu_items_after_new_courses');

            add_submenu_page('courses', __('Instructors', 'cp'), __('Instructors', 'cp'), 'coursepress_instructors_cap', 'instructors', array(&$this, 'coursepress_instructors_admin'));
            do_action('coursepress_add_menu_items_after_instructors');

            add_submenu_page('courses', __('Students', 'cp'), __('Students', 'cp'), 'coursepress_students_cap', 'students', array(&$this, 'coursepress_students_admin'));
            do_action('coursepress_add_menu_items_after_instructors');

            /* add_submenu_page('courses', __('Assessment', 'cp'), __('Assessment', 'cp'), 'coursepress_assessment_cap', 'assessment', array(&$this, 'coursepress_assessment_admin'));
              do_action('coursepress_add_menu_items_after_assessment');

              add_submenu_page('courses', __('Reports', 'cp'), __('Reports', 'cp'), 'coursepress_reports_cap', 'reports', array(&$this, 'coursepress_reports_admin'));
              do_action('coursepress_add_menu_items_after_instructors');
             */

            add_submenu_page('courses', __('Settings', 'cp'), __('Settings', 'cp'), 'coursepress_settings_cap', 'settings', array(&$this, 'coursepress_settings_admin'));
            do_action('coursepress_add_menu_items_after_settings');

            do_action('coursepress_add_menu_items_down');
        }

        function register_custom_posts() {

            //Register Courses post type
            $args = array(
                'labels' => array('name' => __('Courses', 'cp'),
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
                'capability_type' => 'post',
                'query_var' => true,
                'rewrite' => array('slug' => $this->get_course_slug(), 'with_front' => false)
            );

            register_post_type('course', $args);

            //Register Units post type
            $args = array(
                'labels' => array('name' => __('Units', 'cp'),
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
                'capability_type' => 'post',
                //Add later rewrite for customizable slugs!!!
                'query_var' => true
            );

            register_post_type('unit', $args);

            //Register Modules (Unit Module) post type
            $args = array(
                'labels' => array('name' => __('Modules', 'cp'),
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
                'show_ui' => true,
                'publicly_queryable' => false,
                'capability_type' => 'post',
                'query_var' => true
            );

            register_post_type('module', $args);

            //Register Modules Responses (Unit Module Responses) post type
            $args = array(
                'labels' => array('name' => __('Module Responses', 'cp'),
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
                'show_ui' => true,
                'publicly_queryable' => false,
                'capability_type' => 'post',
                'query_var' => true
            );

            register_post_type('module_reponse', $args);

            do_action('after_custom_post_types');
        }

        //Add new roles and user capabilities
        function add_user_roles_and_caps() {
            global $user, $wp_roles;

            /* ------------------------- Add Instructor role and capabilities */

            add_role('instructor', 'Instructor');

            $role = get_role('instructor');
            $role->add_cap('read');

            /* =============== General plugin menu capabilities ================ */

            $role->add_cap('coursepress_dashboard_cap'); //access to plugin menu
            $role->add_cap('coursepress_courses_cap'); //access to courses
            $role->add_cap('coursepress_instructors_cap'); //access to instructors
            $role->add_cap('coursepress_students_cap'); //access to students
            //$role->add_cap('coursepress_reports_cap'); //access to reports
            $role->add_cap('coursepress_settings_cap'); //access to settings

            /* =============== Action capabilities ============== */

            /* - Courses capabilities */

            $role->add_cap('coursepress_create_course_cap'); //create new courses
            //$role->add_cap('coursepress_update_course_cap'); //update courses
            $role->add_cap('coursepress_update_my_course_cap'); //update courses where the instructor is an author
            //$role->add_cap('coursepress_delete_course_cap'); //delete courses
            $role->add_cap('coursepress_delete_my_course_cap'); //delete courses where instructor is an author
            //$role->add_cap('coursepress_change_course_status_cap'); //change course statuses
            $role->add_cap('coursepress_change_my_course_status_cap'); //change course statuses where instructor is author

            /* - Courses > Units capabilities */

            $role->add_cap('coursepress_create_course_unit_cap'); //create new course units
            //$role->add_cap('coursepress_update_course_unit_cap'); //update course units
            $role->add_cap('coursepress_update_my_course_unit_cap'); //update course units where the instructor is an author (of the unit)
            //$role->add_cap('coursepress_delete_course_units_cap'); //delete course units
            $role->add_cap('coursepress_delete_my_course_units_cap'); //delete course units where instructor is an author (of a the unit)
            //$role->add_cap('coursepress_change_course_unit_status_cap'); //change course unit statuses
            $role->add_cap('coursepress_change_my_course_unit_status_cap'); //change course unit statuses where instructor is author

            /* - Instructors capabilities */

            //$role->add_cap('coursepress_assign_and_assign_instructor_course_cap'); //assign and unassign instructors to a course
            $role->add_cap('coursepress_assign_and_assign_instructor_my_course_cap'); //assign and ununassign course instructors where the instructor is an author

            /* - Course Classes capabilities */

            //$role->add_cap('coursepress_add_new_classes_cap'); //Add new course classes
            $role->add_cap('coursepress_add_new_my_classes_cap'); //Add new course classes to courses where the instructor is an author
            //$role->add_cap('coursepress_delete_classes_cap'); //Delete course classes
            $role->add_cap('coursepress_delete_my_classes_cap'); //Delete course classes where course author is the instructor

            /* - Students capabilities */

            //$role->add_cap('coursepress_invite_students_cap'); //Invite students to a course
            $role->add_cap('coursepress_invite_my_students_cap'); //invite students to courses where the instructor is an author (of a course)
            //$role->add_cap('coursepress_unenroll_students_cap'); //Unenroll students from classes
            $role->add_cap('coursepress_unenroll_my_students_cap'); //Unenroll students from classes where the instructor is an author of the course
            //$role->add_cap('coursepress_add_move_students_cap'); //Add/Move students from class to class
            $role->add_cap('coursepress_add_move_my_students_cap'); //Add/Move students from class to class where the instructor is an author of the course
            //$role->add_cap('coursepress_change_students_group_class_cap'); //Change student's group and class
            $role->add_cap('coursepress_change_my_students_group_class_cap'); //Change student's group and class where the instructor is an author of the course
            $role->add_cap('coursepress_add_new_students_cap'); //Add new users with students role to blog
            $role->add_cap('coursepress_delete_students_cap'); //Delete users with Student role

            /* - Settings > Groups capabilities */
            //$role->add_cap('coursepress_settings_groups_page_cap'); //Access to group settings page
            $role->add_cap('coursepress_settings_shortcode_page_cap'); //View shortcode page
            //$role->add_cap('coursepress_send_bulk_my_students_email_cap'); //Send bulk emails
            $role->add_cap('coursepress_send_bulk_students_email_cap'); //Send bulk emails only to courses made by the instructor


            /* ---------------------------- ADD Role Student and capabilities */
            add_role('student', 'Student');

            $role = get_role('student');
            $role->add_cap('read');

            /* ---------------------- Add initial capabilities for the admins */
            $role = get_role('administrator');
            $role->add_cap('read');

            /* =============== General plugin menu capabilities ================ */

            $role->add_cap('coursepress_dashboard_cap'); //access to plugin menu
            $role->add_cap('coursepress_courses_cap'); //access to courses
            $role->add_cap('coursepress_instructors_cap'); //access to instructors
            $role->add_cap('coursepress_students_cap'); //access to students
            $role->add_cap('coursepress_reports_cap'); //access to reports
            $role->add_cap('coursepress_settings_cap'); //access to settings

            /* =============== Action capabilities ============== */

            /* - Courses capabilities */

            $role->add_cap('coursepress_create_course_cap'); //create new courses
            $role->add_cap('coursepress_update_course_cap'); //update courses
            $role->add_cap('coursepress_update_my_course_cap'); //update courses where the instructor is an author
            $role->add_cap('coursepress_delete_course_cap'); //delete courses
            $role->add_cap('coursepress_delete_my_course_cap'); //delete courses where instructor is an author
            $role->add_cap('coursepress_change_course_status_cap'); //change course statuses
            $role->add_cap('coursepress_change_my_course_status_cap'); //change course statuses where instructor is author

            /* - Courses > Units capabilities */

            $role->add_cap('coursepress_create_course_unit_cap'); //create new course units
            $role->add_cap('coursepress_update_course_unit_cap'); //update course units
            $role->add_cap('coursepress_view_all_units_cap'); //view units in every course made by any instructor
            $role->add_cap('coursepress_update_my_course_unit_cap'); //update course units where the instructor is an author (of the unit)
            $role->add_cap('coursepress_delete_course_units_cap'); //delete course units
            $role->add_cap('coursepress_delete_my_course_units_cap'); //delete course units where instructor is an author (of a the unit)
            $role->add_cap('coursepress_change_course_unit_status_cap'); //change course unit statuses
            $role->add_cap('coursepress_change_my_course_unit_status_cap'); //change course unit statuses where instructor is author

            /* - Instructors capabilities */

            $role->add_cap('coursepress_assign_and_assign_instructor_course_cap'); //assign and unassign instructors to a course
            $role->add_cap('coursepress_assign_and_assign_instructor_my_course_cap'); //assign and ununassign course instructors where the instructor is an author

            /* - Course Classes capabilities */

            $role->add_cap('coursepress_add_new_classes_cap'); //Add new course classes
            $role->add_cap('coursepress_add_new_my_classes_cap'); //Add new course classes to courses where the instructor is an author
            $role->add_cap('coursepress_delete_classes_cap'); //Delete course classes
            $role->add_cap('coursepress_delete_my_classes_cap'); //Delete course classes where course author is the instructor

            /* - Students capabilities */

            $role->add_cap('coursepress_invite_students_cap'); //Invite students to a course
            $role->add_cap('coursepress_invite_my_students_cap'); //invite students to courses where the instructor is an author (of a course)
            $role->add_cap('coursepress_unenroll_students_cap'); //Unenroll students from classes
            $role->add_cap('coursepress_unenroll_my_students_cap'); //Unenroll students from classes where the instructor is an author of the course
            $role->add_cap('coursepress_add_move_students_cap'); //Add/Move students from class to class
            $role->add_cap('coursepress_add_move_my_students_cap'); //Add/Move students from class to class where the instructor is an author of the course
            $role->add_cap('coursepress_change_students_group_class_cap'); //Change student's group and class
            $role->add_cap('coursepress_change_my_students_group_class_cap'); //Change student's group and class where the instructor is an author of the course
            $role->add_cap('coursepress_add_new_students_cap'); //Add new users with students role to blog
            $role->add_cap('coursepress_delete_students_cap'); //Delete users with Student role
            $role->add_cap('coursepress_send_bulk_my_students_email_cap'); //Send bulk emails
            $role->add_cap('coursepress_send_bulk_students_email_cap'); //Send bulk emails only to courses made by the instructor

            /* - Settings > Groups capabilities */
            $role->add_cap('coursepress_settings_groups_page_cap'); //Access to group settings page
            $role->add_cap('coursepress_settings_shortcode_page_cap'); //View shortcode page
        }

        //Functions for handling admin menu pages

        function coursepress_courses_admin() {
            include_once($this->plugin_dir . 'includes/admin-pages/courses.php');
        }

        function coursepress_course_details_admin() {
            include_once($this->plugin_dir . 'includes/admin-pages/courses-details.php');
        }

        function coursepress_instructors_admin() {
            include_once($this->plugin_dir . 'includes/admin-pages/instructors.php');
        }

        function coursepress_students_admin() {
            include_once($this->plugin_dir . 'includes/admin-pages/students.php');
        }

        function coursepress_assessment_admin() {
            //include_once($this->plugin_dir . 'includes/admin-pages/assessment.php');
        }

        function coursepress_reports_admin() {
            //include_once($this->plugin_dir . 'includes/admin-pages/reports.php');
        }

        function coursepress_settings_admin() {
            include_once($this->plugin_dir . 'includes/admin-pages/settings.php');
        }

        /* Functions for handling tab pages */

        function show_courses_details_overview() {
            include_once($this->plugin_dir . 'includes/admin-pages/courses-details-overview.php');
        }

        function show_courses_details_units() {
            include_once($this->plugin_dir . 'includes/admin-pages/courses-details-units.php');
        }

        function show_courses_details_students() {
            include_once($this->plugin_dir . 'includes/admin-pages/courses-details-students.php');
        }

        function show_settings_general() {
            include_once($this->plugin_dir . 'includes/admin-pages/settings-general.php');
        }

        function show_settings_groups() {
            include_once($this->plugin_dir . 'includes/admin-pages/settings-groups.php');
        }

        function show_settings_payment() {
            include_once($this->plugin_dir . 'includes/admin-pages/settings-payment.php');
        }

        function show_settings_shortcodes() {
            include_once($this->plugin_dir . 'includes/admin-pages/settings-shortcodes.php');
        }

        function show_settings_instructor_capabilities() {
            include_once($this->plugin_dir . 'includes/admin-pages/settings-instructor-capabilities.php');
        }

        function show_settings_email() {
            include_once($this->plugin_dir . 'includes/admin-pages/settings-email.php');
        }

        function show_unit_details() {
            include_once($this->plugin_dir . 'includes/admin-pages/unit-details.php');
        }

        /* Custom header actions */

        function header_actions() {//front
            wp_enqueue_script('coursepress_front', $this->plugin_url . 'js/coursepress-front.js', array('jquery'), false, false);
            wp_localize_script('coursepress_front', 'student', array(
                'unenroll_alert' => __('Please confirm that you want to un-enroll from the course. If you un-enroll, you will no longer be able to see your records for this course.', 'cp'),
            ));

            if (!is_admin()) {
                wp_enqueue_style('front_general', $this->plugin_url . 'css/front_general.css');
            }
        }

        function admin_header_actions() {
            wp_enqueue_style('admin_general', $this->plugin_url . 'css/admin_general.css');

            wp_enqueue_script('jquery-ui-datepicker');
            wp_enqueue_script('jquery-ui', 'http://code.jquery.com/ui/1.10.3/jquery-ui.js', array('jquery'), '1.10.3'); //need to change this to built-in 
            //wp_enqueue_script("jquery");
            //wp_enqueue_script("jquery-ui");
            //wp_enqueue_script("jquery-ui-core");

            wp_enqueue_script('courses_bulk', $this->plugin_url . 'js/coursepress-admin.js', array('jquery', 'jquery-ui'), false, false);
            wp_localize_script('courses_bulk', 'coursepress', array(
                'delete_instructor_alert' => __('Please confirm that you want to remove the instructor from this course?', 'cp'),
                'delete_course_alert' => __('Please confirm that you want to permanently delete the course?', 'cp'),
                'unenroll_student_alert' => __('Please confirm that you want to un-enroll student from this course. If you un-enroll, you will no longer be able to see student\'s records for this course.', 'cp'),
                'delete_unit_alert' => __('Please confirm that you want to permanently delete the unit?', 'cp'),
                'active_student_tab' => (isset($_REQUEST['active_student_tab']) ? $_REQUEST['active_student_tab'] : 0)
            ));
        }

        function admin_coursepress_page_course_details() {
            wp_enqueue_script('courses-units', $this->plugin_url . 'js/coursepress-courses.js', array('jquery', 'jquery-ui'), false, false);
            wp_localize_script('courses-units', 'coursepress_units', array(
                'unenroll_class_alert' => __('Please confirm that you want to un-enroll all students from this class?', 'cp'),
                'delete_class' => __('Please confirm that you want to permanently delete the class? All students form this class will be moved to the Default class automatically.', 'cp'),
            ));
            wp_enqueue_style('jquery-ui-admin', 'http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css'); //need to change this to built-in
            wp_enqueue_style('admin_coursepress_page_course_details', $this->plugin_url . 'css/admin_coursepress_page_course_details.css');
        }

        function admin_coursepress_page_settings() {
            wp_enqueue_script('settings_groups', $this->plugin_url . 'js/admin-settings-groups.js', array('jquery'), false, false);
            wp_localize_script('settings_groups', 'group_settings', array(
                'remove_string' => __('Remove', 'cp'),
                'delete_group_alert' => __('Please confirm that you want to permanently delete the group?', 'cp')
            ));
        }

        function admin_coursepress_page_courses() {
            wp_enqueue_style('courses', $this->plugin_url . 'css/admin_coursepress_page_courses.css');
        }

        function admin_coursepress_page_reports() {
            wp_enqueue_style('reports', $this->plugin_url . 'css/admin_coursepress_page_reports.css');
            wp_enqueue_script('reports-admin', $this->plugin_url . 'js/reports-admin.js');
            wp_enqueue_style('jquery-ui-admin', 'http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css'); //need to change this to built-in
            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-tabs');
        }

        function admin_coursepress_page_students() {
            wp_enqueue_style('students', $this->plugin_url . 'css/admin_coursepress_page_students.css');
            wp_enqueue_style('instructors', $this->plugin_url . 'css/admin_coursepress_page_instructors.css');
            wp_enqueue_script('students', $this->plugin_url . 'js/students-admin.js', array('jquery'), false, false);
            wp_localize_script('students', 'student', array(
                'delete_student_alert' => __('Please confirm that you want to remove the student and the all associated records?', 'cp'),
            ));
        }

        function admin_coursepress_page_instructors() {
            wp_enqueue_style('instructors', $this->plugin_url . 'css/admin_coursepress_page_instructors.css');
            wp_enqueue_script('instructors', $this->plugin_url . 'js/instructors-admin.js', array('jquery'), false, false);
            wp_localize_script('instructors', 'instructor', array(
                'delete_instructors_alert' => __('Please confirm that you want to remove the instructor and the all associated records?', 'cp'),
            ));
        }

        function create_virtual_pages() {

            $url = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

            //Enrollment process page
            if (preg_match('/' . $this->get_enrollment_process_slug() . '/', $url)) {

                $theme_file = locate_template(array('enrollment-process.php'));

                if ($theme_file != '') {
                    require_once($theme_file);
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
            }

            //Custom signup page
            if (preg_match('/' . $this->get_signup_slug() . '/', $url)) {

                $theme_file = locate_template(array('student-signup.php'));

                if ($theme_file != '') {
                    require_once($theme_file);
                    exit;
                } else {

                    $args = array(
                        'slug' => $this->get_signup_slug(),
                        'title' => __('Sign Up', 'cp'),
                        'content' => $this->get_template_details($this->plugin_dir . 'includes/templates/student-signup.php'),
                        'type' => 'virtual_page'
                    );
                    $pg = new CoursePress_Virtual_Page($args);
                }
            }

            //Student Dashboard page
            if (preg_match('/' . $this->get_student_dashboard_slug() . '/', $url)) {

                $theme_file = locate_template(array('student-dashboard.php'));

                if ($theme_file != '') {
                    require_once($theme_file);
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
            }

            //Student Settings page
            if (preg_match('/' . $this->get_student_settings_slug() . '/', $url)) {

                $theme_file = locate_template(array('student-settings.php'));

                if ($theme_file != '') {
                    require_once($theme_file);
                    exit;
                } else {

                    $args = array(
                        'slug' => $this->get_student_settings_slug(),
                        'title' => __('Dashboard - Settings', 'cp'),
                        'content' => $this->get_template_details($this->plugin_dir . 'includes/templates/student-settings.php'),
                        'type' => 'virtual_page'
                    );

                    $pg = new CoursePress_Virtual_Page($args);
                }
            }
        }

        function check_for_get_actions() {
            if (isset($_GET['unenroll']) && is_numeric($_GET['unenroll'])) {
                $student = new Student(get_current_user_id());
                $student->unenroll_from_course($_GET['unenroll']);
            }
        }

        function admin_nopermalink_warning() {
            //shows a warning notice to admins if pretty permalinks are disabled
            if (current_user_can('manage_options') && !get_option('permalink_structure')) {
                echo '<div class="error"><p>' . __('<strong>' . $this->name . ' is almost ready</strong>. You must <a href="options-permalink.php">update your permalink structure</a> to something other than the default for it to work.', 'cp') . '</p></div>';
            }
        }

        //adds our links to custom theme nav menus using wp_nav_menu()
        function main_navigation_links($sorted_menu_items, $args) {
            $is_in = is_user_logged_in();
            /* Course */

            $courses = new stdClass;

            $courses->title = __('Courses', 'cp');
            $courses->menu_item_parent = 0;
            $courses->ID = '';
            $courses->db_id = '';
            $courses->url = trailingslashit(site_url() . '/' . $this->get_course_slug());
            $sorted_menu_items[] = $courses;

            /* Student Dashboard page */

            if ($is_in) {
                $dashboard = new stdClass;

                $dashboard->title = __('Dashboard', 'cp');
                $dashboard->menu_item_parent = 0;
                $dashboard->ID = '';
                $dashboard->db_id = -9998;
                $dashboard->url = trailingslashit(site_url() . '/' . $this->get_student_dashboard_slug());
                $sorted_menu_items[] = $dashboard;

                /* Student Dashboard > Courses page */

                $dashboard_courses = new stdClass;
                $dashboard_courses->title = __('Courses', 'cp');
                $dashboard_courses->menu_item_parent = -9998;
                $dashboard_courses->ID = '';
                $dashboard_courses->db_id = '';
                $dashboard_courses->url = trailingslashit(site_url() . '/' . $this->get_student_dashboard_slug());
                $sorted_menu_items[] = $dashboard_courses;

                /* Student Dashboard > Settings page */

                $settings = new stdClass;

                $settings->title = __('Settings', 'cp');
                $settings->menu_item_parent = -9998;
                $settings->ID = '';
                $settings->db_id = '';
                $settings->url = trailingslashit(site_url() . '/' . $this->get_student_settings_slug());
                $sorted_menu_items[] = $settings;
            }

            /* Sign up page */

            $signup = new stdClass;

            if (!$is_in) {
                $signup->title = __('Sign Up', 'cp');
                $signup->menu_item_parent = 0;
                $signup->ID = '';
                $signup->db_id = '';
                $signup->url = trailingslashit(site_url() . '/' . $this->get_signup_slug());
                $sorted_menu_items[] = $signup;
            }

            /* Log in / Log out links */

            $login = new stdClass;
            if ($is_in) {
                $login->title = __('Log Out', 'cp');
            } else {
                $login->title = __('Log In', 'cp');
            }

            $login->menu_item_parent = 0;
            $login->ID = '';
            $login->db_id = '';
            $login->url = $is_in ? wp_logout_url() : wp_login_url();

            $sorted_menu_items[] = $login;

            return $sorted_menu_items;
        }

        function login_redirect($redirect_to, $request, $user) {
            global $user;

            if (isset($user->roles) && is_array($user->roles)) {
                //check for students
                if (in_array("student", $user->roles)) {
                    // redirect them to the default place
                    return trailingslashit(site_url()) . trailingslashit($this->get_student_dashboard_slug());
                } else {
                    return $redirect_to;
                }
            } else {
                return $redirect_to;
            }
        }

        function comments_template($template) {
            global $wp_query, $withcomments, $post, $wpdb, $id, $comment, $user_login, $user_ID, $user_identity, $overridden_cpage;
            if (get_post_type($id) == 'course') {
                $template = $this->plugin_dir . 'includes/templates/no-comments.php';
            }
            return $template;
        }

        function check_for_discussion_form($open = null, $post_id = null, $cat = null, $days_old = '') {
            global $wp, $post_id;

            if (is_array($wp->query_vars)) {
                if (array_key_exists('unitname', $wp->query_vars)) {
                    $unit = new Unit();
                    $post_id = $unit->get_unit_id_by_name($wp->query_vars['unitname']);
                } else if (array_key_exists('coursename', $wp->query_vars)) {
                    $course = new Course();
                    $post_id = $course->get_course_id_by_name($wp->query_vars['coursename']);
                } else {
                    $post_id = $post_id;
                }


                if (get_post_type($post_id) == 'virtual_page') {
                    return false;
                }
                /* if (get_post_type($post_id) == 'course') {
                  if (get_permalink($post_id) != curPageURL()) {
                  return true;
                  } else {
                  return false; //we want to have separate page to show comments
                  }
                  } */

                if (get_post_type($post_id) == 'unit') {
                    return false;
                }
            } else {
                return true;
            }
        }

        function check_for_comment_redirect_after_post($location) {
            /* if (isset($_POST['comment_post_ID'])) {
              return $location . '?redirect_discussion_of_unit_to=' . $_POST['comment_post_ID'];
              } */
            return $location;
        }

        function check_for_valid_post_type_permalinks($permalink, $post, $leavename) {
            if (get_post_type($post->ID) == 'unit') {
                $unit = new Unit($post->ID);
                return $unit->get_permalink();
            } else {
                return $permalink;
            }
        }

        function output_buffer() {
            ob_start();
        }

        function get_comment_link($link, $comment, $args) {
            if (get_post_type($comment->comment_post_ID) == 'unit') {
                $link = trailingslashit(get_permalink($comment->comment_post_ID)) . '#comment-' . $comment->comment_ID;
                return $link;
            }/* else if (get_post_type($comment->comment_post_ID) == 'course') {
              $link = trailingslashit(get_permalink($comment->comment_post_ID)) . '/units/#comment-' . $comment->comment_ID;
              } */
            return $link;
        }

    }

}

global $coursepress;
$coursepress = new CoursePress();
?>