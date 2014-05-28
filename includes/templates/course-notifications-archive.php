<?php
/**
 * The notifications archive template file
 * 
 * @package CoursePress
 */
global $coursepress, $wp;
$course_id = do_shortcode('[get_parent_course_id]');

//redirect to the parent course page if not enrolled
$coursepress->check_access($course_id);
?>

        <?php
        do_shortcode('[course_unit_archive_submenu]');
        ?>

        <div class="clearfix"></div>

        <ul class="notification-archive-list">
            <?php
            $page = (isset($wp->query_vars['paged'])) ? $wp->query_vars['paged'] : 1;

            $query_args = array(
                'category' => '',
                'order' => 'DESC',
                'post_type' => 'notifications',
                'post_mime_type' => '',
                'post_parent' => '',
                'post_status' => 'publish',
                'orderby' => 'meta_value_num',
                'paged' => $page,
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key' => 'course_id',
                        'value' => $course_id
                    ),
                    array(
                        'key' => 'course_id',
                        'value' => ''
                    ),
                )
            );

            query_posts($query_args);
            ?>
            <?php if (have_posts()) { ?>
                <?php
                while (have_posts()) {
                    the_post();
                    ?>
                    <li>
                        <div class="notification-archive-single-meta">
                            <div class="notification-date"><span class="date-part-one"><?php echo get_the_date('M'); ?></span><span class="date-part-two"><?php echo get_the_date('j'); ?></span></div>
                            <span class="notification-meta-devider"></span>
                            <div class="notification-time"><?php the_time(); ?></div>
                        </div>
                        <div class="notification-archive-single">
                            <h1 class="notification-title"><?php the_title(); ?></h1>
                            <div class="notification_author"><?php the_author(); ?></div>
                            <div class="notification-content"><?php the_content(); ?></div>
                        </div>
                        <div class="clearfix"></div>
                    </li>
        <?php
    }
} else {
    ?>
                <h1 class="zero-course-units"><?php _e("0 notifications. Please check back later."); ?></h1>
                <?php
            }
            ?>
        </ul>
        <br clear="all" />
<?php coursepress_numeric_posts_nav('navigation-pagination'); ?>