<?php

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('Course')) {

    class Course {

        var $id = '';
        var $output = 'OBJECT';
        var $course = array();
        var $details;

        function __construct($id = '', $output = 'OBJECT') {
            $this->id = $id;
            $this->output = $output;
            $this->details = get_post($this->id, $this->output);
        }

        function Course($id = '', $output = 'OBJECT') {
            $this->__construct($id, $output);
        }

        function get_course() {

            $course = get_post($this->id, $this->output);

            if (!empty($course)) {

                if (!isset($course->post_title) || $course->post_title == '') {
                    $course->post_title = __('Untitled', 'cp');
                }
                if ($course->post_status == 'private' || $course->post_status == 'draft') {
                    $course->post_status = __('unpublished', 'cp');
                }

                $course->class_size = get_post_meta($this->id, 'class_size', true);

                return $course;
            } else {
                return new stdClass();
            }
        }

        function get_course_id_by_name($slug) {

            $args = array(
                'name' => $slug,
                'post_type' => 'course',
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

        function update_course() {
            global $user_id, $wpdb;

            $course = get_post($this->id, $this->output);

            $post_status = 'publish';

            if ($_POST['course_name'] != '' && $_POST['course_name'] != __('Untitled', 'cp') && $_POST['course_description'] != '') {
                if ($course->post_status != 'publish') {
                    $post_status = 'private';
                }
            } else {
                $post_status = 'draft';
            }

            $post = array(
                'post_author' => $user_id,
                'post_excerpt' => $_POST['course_excerpt'],
                'post_content' => $_POST['course_description'],
                'post_status' => $post_status,
                'post_title' => $_POST['course_name'],
                'post_type' => 'course',
            );

            if (isset($_POST['course_id'])) {
                $post['ID'] = $_POST['course_id']; //If ID is set, wp_insert_post will do the UPDATE instead of insert
            }

            $post_id = wp_insert_post($post);

            //Update post meta
            if ($post_id != 0) {
                foreach ($_POST as $key => $value) {
                    if (preg_match("/meta_/i", $key)) {//every field name with prefix "meta_" will be saved as post meta automatically
                        update_post_meta($post_id, str_replace('meta_', '', $key), $value);
                    }
                }

                $old_post_meta = get_post_meta($post_id, 'instructors', false); //Get last instructor ID array in order to compare with posted one

                if (serialize(array($_POST['instructor'])) !== serialize($old_post_meta)) {//If instructors IDs don't match
                    delete_post_meta($post_id, 'instructors');
                    delete_user_meta_by_key('course_' . $post_id);
                }

                update_post_meta($post_id, 'instructors', $_POST['instructor']); //Save instructors for the Course

                if (isset($_POST['instructor'])) {
                    foreach ($_POST['instructor'] as $instructor_id) {
                        update_user_meta($instructor_id, 'course_' . $post_id, $post_id); //Link courses and instructors (in order to avoid custom tables) for easy MySql queries (get instructor stats, his courses, etc.)
                    }
                }
            }

            return $post_id;
        }

        function delete_course($force_delete = true) {

            $wpdb;

            wp_delete_post($this->id, $force_delete); //Whether to bypass trash and force deletion

            /* Delete all usermeta associated to the course */
            delete_user_meta_by_key('course_' . $this->id);
            delete_user_meta_by_key('enrolled_course_date_' . $this->id);
            delete_user_meta_by_key('enrolled_course_class_' . $this->id);
            delete_user_meta_by_key('enrolled_course_group_' . $this->id);
        }

        function can_show_permalink() {
            $course = $this->get_course();
            if ($course->post_status !== 'draft') {
                return true;
            } else {
                return false;
            }
        }

        function get_course_instructors() {
            $args = array(
                'blog_id' => $GLOBALS['blog_id'],
                'role' => 'instructor',
                'meta_key' => 'course_' . $this->id,
                'meta_value' => $this->id,
                'meta_compare' => '',
                'meta_query' => array(),
                'include' => array(),
                'exclude' => array(),
                'orderby' => 'display_name',
                'order' => 'ASC',
                'offset' => '',
                'search' => '',
                'number' => '',
                'count_total' => false,
            );

            return get_users($args);
        }

        function change_status($post_status) {
            $post = array(
                'ID' => $this->id,
                'post_status' => $post_status,
            );

            // Update the post status
            wp_update_post($post);
        }

        function get_units($course_id = '', $status = 'any') {

            if ($course_id == '') {
                $course_id = $this->id;
            }

            $args = array(
                'category' => '',
                'order' => 'ASC',
                'post_type' => 'unit',
                'post_mime_type' => '',
                'post_parent' => '',
                'post_status' => $status,
                'meta_key' => 'unit_order',
                'orderby' => 'meta_value_num',
                'posts_per_page' => '-1',
                'meta_query' => array(
                    array(
                        'key' => 'course_id',
                        'value' => $course_id
                    ),
                )
            );

            $units = get_posts($args);

            return $units;
        }

        function get_permalink($course_id = '') {
            if ($course_id == '') {
                $course_id = $this->id;
            }
            return get_permalink($course_id);
        }
        
        function get_permalink_to_do($course_id = '') {
            global $course_slug;
            global $units_slug;

            if ($course_id == '') {
                $course_id = get_post_meta($post_id, 'course_id', true);
            }

            $course = new Course($course_id);
            $course = $course->get_course();

            $unit_permalink = site_url() . '/' . $course_slug . '/' . $course->post_name . '/' . $units_slug . '/' . $this->details->post_name . '/';
            return $unit_permalink;
        }

        function get_number_of_students($course_id = '') {
            if ($course_id == '') {
                $course_id = $this->id;
            }

            $args = array(
                /* 'role' => 'student', */
                'meta_key' => 'enrolled_course_class_' . $course_id,
            );

            $wp_user_search = new WP_User_Query($args);
            return count($wp_user_search->get_results());
        }

    }

}
?>