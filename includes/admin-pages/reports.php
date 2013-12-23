<?php
global $coursepress;

$unit_module_main = new Unit_Module();
$page = $_GET['page'];
$s = (isset($_GET['s']) ? $_GET['s'] : '');

/* * **************************GENERATING REPORT******************************** */
if (isset($_POST['units']) && isset($_POST['users'])) {
    cp_suppress_errors();
    ob_end_clean();
    ob_start();
    $course_id = $_POST['course_id'];
    $course = new Course($course_id);
    $course_units = $course->get_units();
    $course_details = $course->get_course();
    $units_filter = $_POST['units'];

    if (is_numeric($units_filter)) {
        $course_units = array();
        $unit = new Unit($units_filter);
        $course_units[0] = $unit->get_unit();
    }

    $report_title = $course_details->post_title;

    if (isset($_POST['classes'])) {
        $report_classes = $_POST['classes'];
        if($report_classes == ''){
            $report_classes = __('Default Class', 'cp');
        }else{
            $report_classes .= __(' Class', 'cp');
        }
    } else {
        $report_classes = __('All Classes', 'cp');
    }
    
    $report_title = $report_title .= ' | '.$report_classes;
    ?>
    <h1 style="text-align:center;"><?php echo $course_details->post_title; ?></h1>
    <hr /><br />

    <?php
    $users_num = 0;
    foreach ($_POST['users'] as $user_id) {
        $current_row = 0;
        $overall_grade = 0;
        $responses = 0;

        $user_object = new Student($user_id);
        ?>
        <h2 style="text-align:center; color:#2396A0;"><?php echo $user_object->first_name . ' ' . $user_object->last_name; ?></h4>
        <?php
        foreach ($course_units as $course_unit) {
            ?>
            <table cellspacing="0" cellpadding="5">
                <tr>
                    <td colspan="4" style="background-color:#f5f5f5;"><?php echo $course_unit->post_title; ?></td>
                </tr>
            </table>
            <?php
            $module = new Unit_Module();
            $modules = $module->get_modules($course_unit->ID);

            $input_modules_count = 0;

            foreach ($modules as $mod) {
                $class_name = $mod->module_type;
                $module = new $class_name();

                if ($module->front_save) {

                    $input_modules_count++;
                }
            }

            if ($input_modules_count == 0) {
                ?>
                <table cellspacing="0" cellpadding="5">
                    <tr>
                        <td colspan="4" style="color:#ccc;"><?php _e('0 input modules in the selected unit.', 'cp'); ?></td>
                    </tr>
                </table>
                <?php
            }

            foreach ($modules as $mod) {
                $class_name = $mod->module_type;
                $module = new $class_name();

                if ($module->front_save) {
                    $response = $module->get_response($user_object->ID, $mod->ID);
                    $visibility_class = (count($response) >= 1 ? '' : 'less_visible_row');

                    $grade_data = $unit_module_main->get_response_grade($response->ID);
                    ?>
                    <table cellspacing="0" cellpadding="5">
                        <tr>
                            <td style="border-bottom: 1px solid #cccccc;">
                                <?php echo $module->label;
                                ?>
                            </td>

                            <td style="border-bottom: 1px solid #cccccc;">
                                <?php echo $mod->post_title; ?>
                            </td>

                            <td style="border-bottom: 1px solid #cccccc;">
                                <?php echo (count($response) >= 1 ? $response->post_date : __('Not submitted yet', 'cp')); ?>
                            </td>

                            <td style="border-bottom: 1px solid #cccccc;">
                                <?php
                                $grade = $grade_data['grade'];
                                $instructor_id = $grade_data['instructor'];
                                $instructor_name = get_userdata($instructor_id);
                                $grade_time = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $grade_data['time']);

                                if (count($response) >= 1) {
                                    if ($grade_data) {
                                        echo $grade . '%';
                                        $responses++;
                                        $overall_grade = $overall_grade + $grade;
                                    } else {
                                        _e('Pending grade', 'cp');
                                    }
                                } else {
                                    echo '0%';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php
                        $comment = $unit_module_main->get_response_comment($response->ID);
                        if (!empty($comment)) {
                            ?>
                            <tr>
                                <td colspan="4" style="background-color:#FF6600; color:#fff; margin-left:30px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $comment; ?></td>
                            </tr>
                            <?php
                        }
                        ?>

                    </table>
                    <?php
                    $current_row++;
                }//end front save
            }//end modules
        }//course units

        if ($current_row > 0) {
            ?>
            <table cellspacing="0" cellpadding="10">
                <tr>
                    <td colspan="2" style="background-color: #2396A0; color:#fff;">
                        <?php _e('Avarage response grade: ', 'cp'); ?>
                        <?php
                        if ($overall_grade > 0) {
                            echo round(($overall_grade / $responses), 2) . '%';
                        } else {
                            echo '0%';
                        }
                        ?>
                    </td>
                    <td colspan="2" style="text-align: right; background-color: #2396A0; color:#fff; font-weight: bold;">
                        <?php _e('TOTAL:', 'cp'); ?>
                        <?php
                        if ($overall_grade > 0) {
                            echo round(($overall_grade / $current_row), 2) . '%';
                        } else {
                            echo '0%';
                        }
                        ?>
                    </td>
                </tr>
            </table>

            <?php
        }
        ?>
        <!--<br pagebreak="true"/>-->
        <?php
        $users_num++;
    }//post users
    
    if($users_num == 1){
        $report_title = $report_title .= ' | '.$user_object->first_name . ' ' . $user_object->last_name;
    }else{
        $report_title = $report_title .= ' | '.__('All Students', 'cp');
    }
    
    
    $report_content = ob_get_clean();
    //$report_title = __('Report', 'cp');
    $report_name = __($report_title.'.pdf', 'cp');
    $coursepress->pdf_report($report_content, $report_name, $report_title);
}//generate report initiated
/* * ****************************END OF REPORT********************************** */

if (isset($_POST['action']) && isset($_POST['users'])) {
    check_admin_referer('bulk-students');

    $action = $_POST['action'];
    foreach ($_POST['users'] as $user_value) {

        if (is_numeric($user_value)) {

            $student_id = (int) $user_value;
            $student = new Student($student_id);

            switch (addslashes($action)) {
                case 'delete':
                    if (current_user_can('coursepress_delete_students_cap')) {
                        $student->delete_student();
                        $message = __('Selected students has been removed successfully.', 'cp');
                    }
                    break;

                case 'unenroll':
                    if (current_user_can('coursepress_unenroll_students_cap')) {
                        $student->unenroll_from_all_courses();
                        $message = __('Selected students has been unenrolled from all courses successfully.', 'cp');
                    }
                    break;
            }
        }
    }
}

if (isset($_GET['page_num'])) {
    $page_num = $_GET['page_num'];
} else {
    $page_num = 1;
}

if (isset($_GET['s'])) {
    $usersearch = $_GET['s'];
} else {
    $usersearch = '';
}


// Query the users
$wp_user_search = new Student_Search($usersearch, $page_num);
?>
<div class="wrap nosubsub">
    <div class="icon32 icon32-posts-page" id="icon-edit-pages"><br></div>
    <h2><?php _e('Reports', 'cp'); ?></h2>

    <?php
    if (isset($message)) {
        ?>
        <div id="message" class="updated fade"><p><?php echo $message; ?></p></div>
        <?php
    }
    ?>

    <div class="tablenav">

        <!--<div class="alignright actions new-actions">
            <form method="get" action="?page=<?php echo esc_attr($page); ?>" class="search-form">
                <p class="search-box">
                    <input type='hidden' name='page' value='<?php echo esc_attr($page); ?>' />
                    <label class="screen-reader-text"><?php _e('Search Students', 'cp'); ?>:</label>
                    <input type="text" value="<?php echo esc_attr($s); ?>" name="s">
                    <input type="submit" class="button" value="<?php _e('Search Students', 'cp'); ?>">
                </p>
            </form>
        </div>-->

        <!--<form method="post" action="?page=<?php echo esc_attr($page); ?>" id="posts-filter">

        <?php wp_nonce_field('bulk-students'); ?>

            <div class="alignleft actions">
        <?php if (current_user_can('coursepress_unenroll_students_cap') || current_user_can('coursepress_delete_students_cap')) { ?>
                                                                                                                                                                                                            <select name="action">
                                                                                                                                                                                                                <option selected="selected" value=""><?php _e('Bulk Actions', 'cp'); ?></option>
            <?php if (current_user_can('coursepress_delete_students_cap')) { ?>
                                                                                                                                                                                                                                                                                                                                                                                                            <option value="delete"><?php _e('Delete', 'cp'); ?></option>
            <?php } ?>
            <?php if (current_user_can('coursepress_unenroll_students_cap')) { ?>
                                                                                                                                                                                                                                                                                                                                                                                                            <option value="unenroll"><?php _e('Unenroll from all courses', 'cp'); ?></option>
            <?php } ?>
                                                                                                                                                                                                            </select>
                                                                                                                                                                                                            <input type="submit" class="button-secondary action" id="doaction" name="doaction" value="<?php _e('Apply', 'cp'); ?>" />
        <?php } ?>
            </div>

        </form>-->


        <br class="clear">

    </div><!--/tablenav-->

    <div class="tablenav">
        <form method="get" id="course-filter">
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']); ?>" />
            <input type="hidden" name="page_num" value="<?php echo esc_attr($page_num); ?>" />
            <div class="alignleft actions">
                <select name="course_id" id="dynamic_courses">

                    <?php
                    $args = array(
                        'post_type' => 'course',
                        'post_status' => 'any',
                        'posts_per_page' => -1
                    );

                    $courses = get_posts($args);
                    $courses_with_students = 0;
                    $course_num = 0;
                    $first_course_id = 0;

                    foreach ($courses as $course) {
                        if ($course_num == 0) {
                            $first_course_id = $course->ID;
                        }

                        $course_obj = new Course($course->ID);
                        $course_object = $course_obj->get_course();
                        if ($course_obj->get_number_of_students() >= 1) {
                            $courses_with_students++;
                            ?>
                            <option value="<?php echo $course->ID; ?>" <?php echo ((isset($_GET['course_id']) && $_GET['course_id'] == $course->ID) ? 'selected="selected"' : ''); ?>><?php echo $course->post_title; ?></option>
                            <?php
                        }
                        $course_num++;
                    }

                    if ($courses_with_students == 0) {
                        ?>
                        <option value=""><?php _e('0 courses with enrolled students.', 'cp'); ?></option>
                        <?php
                    }
                    ?>
                </select>
                <?php
                $current_course_id = 0;
                if (isset($_GET['course_id'])) {
                    $current_course_id = $_GET['course_id'];
                } else {
                    $current_course_id = $first_course_id;
                }
                ?>

                <?php
                if ($current_course_id !== 0) {//courses exists, at least one 
                    $course = new Course($current_course_id);
                    $course_units = $course->get_units();

                    if (count($course_units) >= 1) {

                        //search for students
                        if (isset($_GET['classes'])) {
                            $classes = $_GET['classes'];
                        } else {
                            $classes = 'all';
                        }
                        ?>
                        <select name="classes" id="dynamic_classes" name="dynamic_classes">
                            <option value="all" <?php selected($classes, 'all', true); ?>><?php _e('All Classes', 'cp'); ?></option>
                            <option value="" <?php selected($classes, '', true); ?>><?php _e('Default', 'cp'); ?></option>
                            <?php
                            $course_classes = get_post_meta($current_course_id, 'course_classes', true);
                            foreach ($course_classes as $course_class) {
                                ?>
                                <option value="<?php echo $course_class; ?>" <?php selected($classes, $course_class, true); ?>><?php echo $course_class; ?></option>
                                <?php
                            }
                            ?>
                        </select>

                        <?php
                    }
                }
                ?>

            </div>
        </form>
    </div><!--tablenav-->

    <?php
    $columns = array(
        "ID" => __('Student ID', 'cp'),
        "user_firstname" => __('First Name', 'cp'),
        "user_lastname" => __('Surname', 'cp'),
        //"latest_activity" => __('Latest Activity', 'cp'),
        "responses" => __('Responses', 'cp'),
        "avarage_grade" => __('Avarage Grade', 'cp'),
        "report" => __('Report', 'cp'),
    );

    $col_sizes = array(
        '8', '10', '10', '10', '10', '5'//, '15'
    );
    ?>
    <form method="post" id="generate-report">
        <input type="hidden" name="course_id" value="<?php echo $current_course_id; ?>" />
        <table cellspacing="0" class="widefat fixed shadow-table">
            <thead>
                <tr>
                    <th style="" class="manage-column column-cb check-column" width="1%" id="cb" scope="col"><input type="checkbox"></th>
                    <?php
                    $n = 0;
                    foreach ($columns as $key => $col) {
                        ?>
                        <th style="" class="manage-column column-<?php echo $key; ?>" width="<?php echo $col_sizes[$n] . '%'; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
                        <?php
                        $n++;
                    }
                    ?>
                </tr>
            </thead>

            <tbody>
                <?php
                $style = '';

//search for students
                if (isset($_GET['classes'])) {
                    $classes = $_GET['classes'];
                } else {
                    $classes = 'all';
                }

                if ($classes !== 'all') {
                    $args = array(
                        'meta_query' => array(
                            array(
                                'key' => 'enrolled_course_class_' . $current_course_id,
                                'value' => $classes,
                            ))
                    );
                } else {
                    $args = array(
                        'meta_query' => array(
                            array(
                                'key' => 'enrolled_course_class_' . $current_course_id
                            ))
                    );
                }

                $additional_url_args = array();
                $additional_url_args['course_id'] = $current_course_id;
                $additional_url_args['classes'] = urlencode($classes);

                $student_search = new Student_Search('', $page_num, array(), $args, $additional_url_args);

                foreach ($student_search->get_results() as $user) {

                    $user_object = new Student($user->ID);
                    $roles = $user_object->roles;
                    $role = array_shift($roles);

                    $style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';
                    ?>
                    <tr id='user-<?php echo $user_object->ID; ?>' <?php echo $style; ?>>
                        <th scope='row' class='check-column'>
                            <input type='checkbox' name='users[]' id='user_<?php echo $user_object->ID; ?>' value='<?php echo $user_object->ID; ?>' />
                        </th>
                        <td <?php echo $style; ?>><?php echo $user_object->ID; ?></td>
                        <td <?php echo $style; ?>><?php echo $user_object->first_name; ?></td>
                        <td <?php echo $style; ?>><?php echo $user_object->last_name; ?></td>

                        <td <?php echo $style; ?>><?php echo $user_object->get_number_of_responses($current_course_id); ?></td>
                        <td <?php echo $style; ?>><?php echo $user_object->get_avarage_response_grade($current_course_id) . '%'; ?></td>
                        <td <?php echo $style; ?>><a class="pdf">&nbsp;</a></td>
                    </tr>

                    <?php
                }
                ?>
                <?php
                if (count($wp_user_search->get_results()) == 0) {
                    ?>
                    <tr><td colspan="8"><div class="zero"><?php _e('No students found.', 'cp'); ?></div></td></tr>
                    <?php
                }
                ?>
            </tbody>
        </table>

        <div class="tablenav">
            <div class="alignleft actions">
                <select name="units">
                    <option value=""><?php _e('All Units') ?></option>
                    <?php
                    $course = new Course($current_course_id);
                    $course_units = $course->get_units();
                    foreach ($course_units as $course_unit) {
                        ?>
                        <option value="<?php echo $course_unit->ID; ?>"><?php echo $course_unit->post_title; ?></option>
                        <?php
                    }
                    ?>

                </select>
                <?php submit_button('Generate Report', 'primary', 'generate_report_button', false); ?>
            </div>

            <div class="tablenav-pages"><?php $student_search->page_links(); ?></div>

        </div><!--/tablenav-->
    </form>



</div>