<?php

class section_break_module extends Unit_Module {

    var $order = 1;
    var $name = 'section_break_module';
    var $label = 'Section Break';
    var $description = '';
    var $front_save = false;
    var $response_type = '';
    var $visible = false;

    function __construct() {
        $this->on_create();
    }

    function page_break_module() {
        $this->__construct();
    }

    function front_main($data) {
        ?>
        <hr class="<?php echo $this->name; ?> front-single-module<?php echo ($this->front_save == true ? '-save' : ''); ?>" />
        <?php
    }

    function admin_main($data) {
        ?>

        <div class="<?php if (empty($data)) { ?>draggable-<?php } ?>module-holder-<?php echo $this->name; ?> module-holder-title" <?php if (empty($data)) { ?>style="display:none;"<?php } ?>>

            <h3 class="module-title sidebar-name">
                <span class="h3-label">

                    <span class="h3-label-left"><?php echo (isset($data->post_title) && $data->post_title !== '' ? $data->post_title : $this->label); ?></span>
                    <span class="page-break-dashed"></span>
                    <span class="page-break-right-fix">...</span>
                    <span class="h3-label-right"><?php echo $this->label; ?></span>
                    <?php
                    if (isset($data->ID)) {
                        parent::get_module_delete_link($data->ID);
                    } else {
                        parent::get_module_remove_link();
                    }
                    ?>
                </span>
            </h3>

            <input type="hidden" name="<?php echo $this->name; ?>_module_order[]" class="module_order" value="<?php echo (isset($data->module_order) ? $data->module_order : 999); ?>" />
            <input type="hidden" name="module_type[]" value="<?php echo $this->name; ?>" />
            <input type="hidden" name="<?php echo $this->name; ?>_id[]" value="<?php echo (isset($data->ID) ? $data->ID : ''); ?>" />
            <input type="hidden" name="<?php echo $this->name; ?>_title[]" value="<?php echo esc_attr(isset($data->post_title) ? $data->post_title : ''); ?>" />

        </div>

        <?php
    }

    function on_create() {
        $this->description = __('Inserts section break (<hr> element)', 'cp');
        $this->save_module_data();
        parent::additional_module_actions();
    }

    function save_module_data() {
        global $wpdb, $last_inserted_unit_id;

        if (isset($_POST['module_type'])) {

            foreach (array_keys($_POST['module_type']) as $module_type => $module_value) {

                if ($module_value == $this->name) {
                    $data = new stdClass();
                    $data->ID = '';
                    $data->unit_id = '';
                    $data->title = '';
                    $data->excerpt = '';
                    $data->content = '';
                    $data->metas = array();
                    $data->metas['module_type'] = $this->name;
                    $data->post_type = 'module';

                    if (isset($_POST[$this->name . '_id'])) {
                        foreach ($_POST[$this->name . '_id'] as $key => $value) {
                            $data->ID = $_POST[$this->name . '_id'][$key];
                            $data->unit_id = ((isset($_POST['unit_id']) and (isset($_POST['unit']) && $_POST['unit'] != '')) ? $_POST['unit_id'] : $last_inserted_unit_id);
                            $data->title = $_POST[$this->name . '_title'][$key];
                            $data->content = '';
                            $data->metas['module_order'] = $_POST[$this->name . '_module_order'][$key];
                            parent::update_module($data);
                        }
                    }
                }
            }
        }
    }

}

coursepress_register_module('section_break_module', 'section_break_module', 'output');
?>