<?php

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('Notification')) {

    class Notification {

        var $id = '';
        var $output = 'OBJECT';
        var $notification = array();
        var $details;

        function __construct($id = '', $output = 'OBJECT') {
            $this->id = $id;
            $this->output = $output;
            $this->details = get_post($this->id, $this->output);
        }

        function Notification($id = '', $output = 'OBJECT') {
            $this->__construct($id, $output);
        }

        function get_notification() {

            $notification = get_post($this->id, $this->output);

            if (!empty($notification)) {

                if (!isset($notification->post_title) || $notification->post_title == '') {
                    $course->post_title = __('Untitled', 'cp');
                }

                return $notification;
            } else {
                return new stdClass();
            }
        }

        function get_notification_id_by_name($slug) {

            $args = array(
                'name' => $slug,
                'post_type' => 'notifications',
                'post_status' => 'any',
                'posts_per_page' => 1
            );

            $post = get_posts($args);

            if ($post) {
                return $post[0]->ID;
            } else {
                return false;
            }
        }

        function update_notification() {
            global $user_id, $wpdb;

            $course = get_post($this->id, $this->output);

            $post = array(
                'post_author' => $user_id,
                'post_content' => $_POST['notification_description'],
                'post_status' => 'publish',
                'post_title' => $_POST['notification_name'],
                'post_type' => 'notiications',
            );

            if (isset($_POST['notification_id'])) {
                $post['ID'] = $_POST['notification_id']; //If ID is set, wp_insert_post will do the UPDATE instead of insert
            }

            $post_id = wp_insert_post($post);

            //Update post meta
            if ($post_id != 0) {
                foreach ($_POST as $key => $value) {
                    if (preg_match("/meta_/i", $key)) {//every field name with prefix "meta_" will be saved as post meta automatically
                        update_post_meta($post_id, str_replace('meta_', '', $key), $value);
                    }
                }
            }

            return $post_id;
        }

        function delete_notification($force_delete = true) {
            wp_delete_post($this->id, $force_delete); //Whether to bypass trash and force deletion
            /* Delete all usermeta associated to the notification? */
        }

        function change_status($post_status) {
            $post = array(
                'ID' => $this->id,
                'post_status' => $post_status,
            );

            // Update the post status
            wp_update_post($post);
        }

    }

}
?>
