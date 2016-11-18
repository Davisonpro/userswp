<?php
/**
 * Template related functions
 *
 * This class defines all code necessary for UsersWP templates like login. register etc.
 *
 * @link       http://wpgeodirectory.com
 * @since      1.0.0
 *
 * @package    Users_WP
 * @subpackage Users_WP/includes
 */

/**
 * Define the templates functionality.
 *
 * @since      1.0.0
 * @package    Users_WP
 * @subpackage Users_WP/includes
 * @author     GeoDirectory Team <info@wpgeodirectory.com>
 */
class Users_WP_Templates {

    protected $loader;

    public function __construct($loader) {
        $this->loader = $loader;
    }

    public function uwp_locate_template( $template ) {
        
        switch ($template) {
            case 'register':
                return $this->uwp_generic_locate_template('register');
                break;

            case 'login':
                return $this->uwp_generic_locate_template('login');
                break;

            case 'forgot':
                return $this->uwp_generic_locate_template('forgot');
                break;

            case 'reset':
                return $this->uwp_generic_locate_template('reset');
                break;

            case 'account':
                return $this->uwp_generic_locate_template('account');
                break;

            case 'profile':
                return $this->uwp_generic_locate_template('profile');
                break;

            case 'users':
                return $this->uwp_generic_locate_template('users');
                break;
        }

        return false;
    }
    
    public function uwp_generic_locate_template($type = 'register') {
        
        $plugin_path = dirname( dirname( __FILE__ ) );
        
        $template = locate_template(array("userswp/".$type.".php"));
        if (!$template) {
            $template = $plugin_path . '/templates/'.$type.'.php';
        }
        $template = apply_filters('uwp_template_'.$type, $template);
        return $template;
    }

    public function access_checks() {
        global $post;

        if (!is_page()) {
            return false;
        }

        $current_page_id = $post->ID;
        
        $register_page = uwp_get_option('register_page', false);
        $login_page = uwp_get_option('login_page', false);
        $forgot_page = uwp_get_option('forgot_page', false);
        $reset_page = uwp_get_option('reset_page', false);

        $account_page = uwp_get_option('account_page', false);
        
        if (( $register_page && ((int) $register_page ==  $current_page_id )) ||
        ( $login_page && ((int) $login_page ==  $current_page_id ) ) ||
        ( $forgot_page && ((int) $forgot_page ==  $current_page_id ) ) ||
        ( $reset_page && ((int) $reset_page ==  $current_page_id ) )) {
            if (is_user_logged_in()) {
                $redirect_page_id = uwp_get_option('account_page', '');
                if (empty($redirect_page_id)) {
                    $redirect_to = home_url('/');
                } else {
                    $redirect_to = get_permalink($redirect_page_id);
                }
                $redirect_to = apply_filters('uwp_logged_in_redirect', $redirect_to);
                wp_redirect($redirect_to);
                exit();
            }
        } elseif ( $account_page && ((int) $account_page ==  $current_page_id ) ) {
            if (!is_user_logged_in()) {
                wp_redirect(get_permalink($login_page));
                exit();
            }
        } else {
            return false;
        }
        
        return false;
    }

    public function profile_redirect() {
        if (is_page()) {
            global $wp_query, $post;
            $current_page_id = $post->ID;
            $account_page = uwp_get_option('profile_page', false);
            if ( $account_page && ((int) $account_page ==  $current_page_id ) ) {

                if (isset($wp_query->query_vars['uwp_profile'])) {
                    //must be profile page
                    $username = $wp_query->query_vars['uwp_profile'];
                    if ( !username_exists( $username ) ) {
                        global $wp_query;
                        $wp_query->set_404();
                        status_header( 404 );
                        get_template_part( 404 ); exit();
                    }
                } else {
                    if (is_user_logged_in()) {
                        $user_id = get_current_user_id();
                        $profile_url = uwp_build_profile_tab_url($user_id);
                        wp_redirect( $profile_url );
                        exit();
                    } else {
                        wp_redirect( home_url('/') );
                        exit();
                    }

                }

            }
        }
    }

    public function logout_redirect() {
        $redirect_page_id = uwp_get_option('logout_redirect_to', '');
        if (empty($redirect_page_id)) {
            $redirect_to = home_url('/');
        } else {
            $redirect_to = get_permalink($redirect_page_id);
        }
        $redirect_to = apply_filters('uwp_logout_redirect', $redirect_to);
        wp_redirect( $redirect_to );
        exit();
    }

    public function uwp_template_fields($form_type) {

        global $wpdb;
        $table_name = $wpdb->prefix . 'uwp_custom_fields';

        $fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM " . $table_name . " WHERE form_type = %s AND is_active = '1' ORDER BY sort_order ASC", array($form_type)));

        if (!empty($fields)) {
            foreach ($fields as $field) {
                $this->uwp_template_fields_html($field, $form_type);
            }
        }
    }

    public function uwp_template_fields_html($field, $form_type) {

        $user_id = get_current_user_id();

        $value = $field->default_value;
        if ($form_type == 'account') {
            $user_data = get_userdata($user_id);

            if ($field->htmlvar_name == 'uwp_account_email') {
                $value = $user_data->user_email;
            } elseif ($field->htmlvar_name == 'uwp_account_password') {
                $value = '';
                $field->is_required = 0;
            } elseif ($field->htmlvar_name == 'uwp_account_confirm_password') {
                $value = '';
                $field->is_required = 0;
            } elseif ($field->htmlvar_name == 'uwp_account_first_name') {
                $value = $user_data->first_name;
            } elseif ($field->htmlvar_name == 'uwp_account_last_name') {
                $value = $user_data->last_name;
            } else {
                $value = uwp_get_usermeta($user_id, $field->htmlvar_name, '');
            }


        }

        if (empty($value)) {
            $value = "";
        }

        $html = apply_filters("uwp_form_input_html_{$field->field_type}", "", $field, $value, $form_type);

        if (empty($html)) {
            ?>
            <input name="<?php echo $field->htmlvar_name; ?>"
                   class="<?php echo $field->css_class; ?>"
                   placeholder="<?php echo $field->site_title; ?>"
                <?php if ($field->is_required == 1) { echo 'required="required"'; } ?>
                   type="<?php echo $field->field_type; ?>"
                   value="<?php echo $value; ?>">
            <?php
        } else {
            echo $html;
        }
    }

    public function uwp_author_page_content($content) {
        if (is_author()) {
            return do_shortcode('[uwp_profile]');
        } else {
            return $content;
        }

    }

    public function uwp_form_input_datepicker($html, $field, $value, $form_type){

        // Check if there is a field specific filter.
        if(has_filter("uwp_form_input_html_datepicker_{$field->htmlvar_name}")){
            $html = apply_filters("uwp_form_input_html_datepicker_{$field->htmlvar_name}", $html, $field, $value, $form_type);
        }

        // If no html then we run the standard output.
        if(empty($html)) {

            ob_start(); // Start  buffering;

            $extra_fields = unserialize($field->extra_fields);

            if ($extra_fields['date_format'] == '')
                $extra_fields['date_format'] = 'yy-mm-dd';

            $date_format = $extra_fields['date_format'];
            $jquery_date_format  = $date_format;


            // check if we need to change the format or not
            $date_format_len = strlen(str_replace(' ', '', $date_format));
            if($date_format_len>5){// if greater then 5 then it's the old style format.

                $search = array('dd','d','DD','mm','m','MM','yy'); //jQuery UI datepicker format
                $replace = array('d','j','l','m','n','F','Y');//PHP date format

                $date_format = str_replace($search, $replace, $date_format);
            }else{
                $jquery_date_format = uwp_date_format_php_to_jqueryui( $jquery_date_format );
            }

            if($value=='0000-00-00'){$value='';}//if date not set, then mark it empty
            $value = uwp_date($value, 'Y-m-d', $date_format);

            ?>
            <script type="text/javascript">

                jQuery(function () {

                    jQuery("#<?php echo $field->htmlvar_name;?>").datepicker({changeMonth: true, changeYear: true <?php

                    echo apply_filters("uwp_datepicker_extra_{$field->htmlvar_name}",'');?>});

                    jQuery("#<?php echo $field->htmlvar_name;?>").datepicker("option", "dateFormat", '<?php echo $jquery_date_format;?>');

                    <?php if(!empty($value)){?>
                    jQuery("#<?php echo $field->htmlvar_name;?>").datepicker("setDate", '<?php echo $value;?>');
                    <?php } ?>

                });

            </script>
            <div id="<?php echo $field->htmlvar_name;?>_row"
                 class="<?php if ($field->is_required) echo 'required_field';?> uwp_form_row clearfix uwp-fieldset-details">
                <label>

                    <?php $site_title = __($field->site_title, 'uwp');
                    echo (trim($site_title)) ? $site_title : '&nbsp;'; ?>
                    <?php if ($field->is_required) echo '<span>*</span>';?>
                </label>

                <input name="<?php echo $field->htmlvar_name;?>" id="<?php echo $field->htmlvar_name;?>"
                       value="<?php echo esc_attr($value);?>" type="text" class="uwp_textfield"/>

                <span class="uwp_message_note"><?php _e($field->help_text, 'uwp');?></span>
                <?php if ($field->is_required) { ?>
                    <span class="uwp_message_error"><?php _e($field->required_msg, 'uwp'); ?></span>
                <?php } ?>
            </div>

            <?php
            $html = ob_get_clean();
        }

        return $html;
    }


    public function uwp_form_input_select($html, $field, $value, $form_type){

        // Check if there is a field specific filter.
        if(has_filter("uwp_form_input_html_select_{$field->htmlvar_name}")){
            $html = apply_filters("uwp_form_input_html_select_{$field->htmlvar_name}", $html, $field, $value, $form_type);
        }

        // If no html then we run the standard output.
        if(empty($html)) {

            ob_start(); // Start  buffering;

            ?>
            <div id="<?php echo $field->htmlvar_name;?>_row"
                 class="<?php if ($field->is_required) echo 'required_field';?> uwp_form_row">
                <label>
                    <?php $site_title = __($field->site_title, 'uwp');
                    echo (trim($site_title)) ? $site_title : '&nbsp;'; ?>
                    <?php if ($field->is_required) echo '<span>*</span>';?>
                </label>
                <?php
                $option_values_arr = uwp_string_values_to_options($field->option_values, true);
                $select_options = '';
                if (!empty($option_values_arr)) {
                    foreach ($option_values_arr as $option_row) {
                        if (isset($option_row['optgroup']) && ($option_row['optgroup'] == 'start' || $option_row['optgroup'] == 'end')) {
                            $option_label = isset($option_row['label']) ? $option_row['label'] : '';

                            $select_options .= $option_row['optgroup'] == 'start' ? '<optgroup label="' . esc_attr($option_label) . '">' : '</optgroup>';
                        } else {
                            $option_label = isset($option_row['label']) ? $option_row['label'] : '';
                            $option_value = isset($option_row['value']) ? $option_row['value'] : '';
                            $selected = $option_value == $value ? 'selected="selected"' : '';

                            $select_options .= '<option value="' . esc_attr($option_value) . '" ' . $selected . '>' . $option_label . '</option>';
                        }
                    }
                }
                ?>
                <select name="<?php echo $field->htmlvar_name;?>" id="<?php echo $field->htmlvar_name;?>"
                        class="uwp_textfield"
                        data-placeholder="<?php echo __('Choose', 'uwp') . ' ' . $site_title . '&hellip;';?>"
                        ><?php echo $select_options;?>
                </select>
                <span class="uwp_message_note"><?php _e($field->help_text, 'uwp');?></span>
                <?php if ($field->is_required) { ?>
                    <span class="uwp_message_error"><?php _e($field->required_msg, 'uwp'); ?></span>
                <?php } ?>
            </div>

            <?php
            $html = ob_get_clean();
        }

        return $html;
    }

    public function uwp_form_input_multiselect($html, $field, $value, $form_type){

        // Check if there is a field specific filter.
        if(has_filter("uwp_form_input_html_multiselect_{$field->htmlvar_name}")){
            $html = apply_filters("uwp_form_input_html_multiselect_{$field->htmlvar_name}", $html, $field, $value, $form_type);
        }


        if(empty($html)) {

            ob_start(); // Start  buffering;

            $multi_display = 'select';
            if (!empty($field->extra_fields)) {
                $multi_display = unserialize($field->extra_fields);
            }
            ?>
            <div id="<?php echo $field->htmlvar_name;?>_row"
                 class="<?php if ($field->is_required) echo 'required_field';?> uwp_form_row">
                <label>
                    <?php $site_title = __($field->site_title, 'uwp');
                    echo (trim($site_title)) ? $site_title : '&nbsp;'; ?>
                    <?php if ($field->is_required) echo '<span>*</span>';?>
                </label>
                <input type="hidden" name="uwp_field_<?php echo $field->htmlvar_name;?>" value="1"/>
                <?php if ($multi_display == 'select') { ?>
                <div class="uwp_multiselect_list">
                    <select name="<?php echo $field->htmlvar_name;?>[]" id="<?php echo $field->htmlvar_name;?>"
                            multiple="multiple" class="uwp_chosen_select"
                            data-placeholder="<?php _e('Select', 'uwp'); ?>"
                            >
                        <?php
                        } else {
                            ?>
                            <ul class="uwp_multi_choice">
                            <?php
                        }

                        $option_values_arr = geodir_string_values_to_options($field->option_values, true);
                        $select_options = '';
                        if (!empty($option_values_arr)) {
                            foreach ($option_values_arr as $option_row) {
                                if (isset($option_row['optgroup']) && ($option_row['optgroup'] == 'start' || $option_row['optgroup'] == 'end')) {
                                    $option_label = isset($option_row['label']) ? $option_row['label'] : '';

                                    if ($multi_display == 'select') {
                                        $select_options .= $option_row['optgroup'] == 'start' ? '<optgroup label="' . esc_attr($option_label) . '">' : '</optgroup>';
                                    } else {
                                        $select_options .= $option_row['optgroup'] == 'start' ? '<li>' . $option_label . '</li>' : '';
                                    }
                                } else {
                                    $option_label = isset($option_row['label']) ? $option_row['label'] : '';
                                    $option_value = isset($option_row['value']) ? $option_row['value'] : '';
                                    $selected = $option_value == $value ? 'selected="selected"' : '';
                                    $selected = '';
                                    $checked = '';

                                    if ((!is_array($value) && trim($value) != '') || (is_array($value) && !empty($value))) {
                                        if (!is_array($value)) {
                                            $value_array = explode(',', $value);
                                        } else {
                                            $value_array = $value;
                                        }

                                        if (is_array($value_array)) {
                                            if (in_array($option_value, $value_array)) {
                                                $selected = 'selected="selected"';
                                                $checked = 'checked="checked"';
                                            }
                                        }
                                    }

                                    if ($multi_display == 'select') {
                                        $select_options .= '<option value="' . esc_attr($option_value) . '" ' . $selected . '>' . $option_label . '</option>';
                                    } else {
                                        $select_options .= '<li><input name="' . $field->name . '[]" ' . $checked . ' value="' . esc_attr($option_value) . '" class="uwp-' . $multi_display . '" type="' . $multi_display . '" />&nbsp;' . $option_label . ' </li>';
                                    }
                                }
                            }
                        }
                        echo $select_options;

                        if ($multi_display == 'select') { ?></select></div>
            <?php } else { ?>
                </ul>
            <?php } ?>
                <?php if ($field->is_required) { ?>
                    <span class="geodir_message_error"><?php _e($field->required_msg, 'uwp'); ?></span>
                <?php } ?>
            </div>
            <?php
            $html = ob_get_clean();
        }

        return $html;
    }

    public function uwp_form_input_file($html, $field, $value, $form_type){

        // Check if there is a field specific filter.
        if(has_filter("uwp_form_input_html_file_{$field->htmlvar_name}")){
            $html = apply_filters("uwp_form_input_html_file_{$field->htmlvar_name}", $html, $field, $value, $form_type);
        }

        // If no html then we run the standard output.
        if(empty($html)) {

            ob_start(); // Start  buffering;

            ?>
            <div id="<?php echo $field->htmlvar_name;?>_row"
                 class="<?php if ($field->is_required) echo 'required_field';?> uwp_form_<?php echo $field->field_type; ?>_row">
                <label>
                    <?php $site_title = __($field->site_title, 'uwp');
                    echo (trim($site_title)) ? $site_title : '&nbsp;'; ?>
                    <?php if ($field->is_required) echo '<span>*</span>';?>
                </label>
                <input name="<?php echo $field->htmlvar_name; ?>"
                       class="<?php echo $field->css_class; ?>"
                       placeholder="<?php echo $field->site_title; ?>"
                    <?php if ($field->is_required == 1) { echo 'required="required"'; } ?>
                       type="<?php echo $field->field_type; ?>"
                       value="<?php echo $value; ?>">
                <span class="uwp_message_note"><?php _e($field->help_text, 'uwp');?></span>
                <?php if ($field->is_required) { ?>
                    <span class="uwp_message_error"><?php _e($field->required_msg, 'uwp'); ?></span>
                <?php } ?>
            </div>

            <?php
            $html = ob_get_clean();
        }

        return $html;
    }

    public function uwp_form_input_textarea($html, $field, $value, $form_type){

        // Check if there is a field specific filter.
        if(has_filter("uwp_form_input_html_file_{$field->htmlvar_name}")){
            $html = apply_filters("uwp_form_input_html_file_{$field->htmlvar_name}", $html, $field, $value, $form_type);
        }

        // If no html then we run the standard output.
        if(empty($html)) {

            ob_start(); // Start  buffering;

            ?>
            <div id="<?php echo $field->htmlvar_name;?>_row"
                 class="<?php if ($field->is_required) echo 'required_field';?> uwp_form_<?php echo $field->field_type; ?>_row">
                <label>
                    <?php $site_title = __($field->site_title, 'uwp');
                    echo (trim($site_title)) ? $site_title : '&nbsp;'; ?>
                    <?php if ($field->is_required) echo '<span>*</span>';?>
                </label>
                <textarea name="<?php echo $field->htmlvar_name; ?>"
                          class="<?php echo $field->css_class; ?>"
                          placeholder="<?php echo $field->site_title; ?>"
                    <?php if ($field->is_required == 1) { echo 'required="required"'; } ?>
                          type="<?php echo $field->field_type; ?>"
                          rows="4"><?php echo $value; ?></textarea>
                <span class="uwp_message_note"><?php _e($field->help_text, 'uwp');?></span>
                <?php if ($field->is_required) { ?>
                    <span class="uwp_message_error"><?php _e($field->required_msg, 'uwp'); ?></span>
                <?php } ?>
            </div>

            <?php
            $html = ob_get_clean();
        }

        return $html;
    }

    public function uwp_setup_nav_menu_item( $menu_item ) {

        if ( is_admin() ) {
            return $menu_item;
        }

        // Prevent a notice error when using the customizer
        $menu_classes = $menu_item->classes;

        if ( is_array( $menu_classes ) ) {
            $menu_classes = implode( ' ', $menu_item->classes );
            $str = 'users-wp-menu ';
            if (strpos($menu_classes, 'users-wp-menu ') !== false) {
                $menu_classes = str_replace($str, '', $menu_classes);
            }
        }

        $register_slug = $this->uwp_get_page_slug('register_page');
        $login_slug = $this->uwp_get_page_slug('login_page');
        $account_slug = $this->uwp_get_page_slug('account_page');
        $forgot_slug = $this->uwp_get_page_slug('forgot_page');
        $logout_slug = "logout";

        $register_class = "users-wp-{$register_slug}-nav";
        $login_class = "users-wp-{$login_slug}-nav";
        $account_class = "users-wp-{$account_slug}-nav";
        $forgot_class = "users-wp-{$forgot_slug}-nav";
        $logout_class = "users-wp-{$logout_slug}-nav";

        switch ( $menu_classes ) {
            case $register_class:
                if ( is_user_logged_in() ) {
                    $menu_item->_invalid = true;
                } else {
                    $menu_item->url = get_permalink(uwp_get_option('register_page', 0));
                }
                break;
            case $login_class:
                if ( is_user_logged_in() ) {
                    $menu_item->_invalid = true;
                } else {
                    $menu_item->url = get_permalink(uwp_get_option('login_page', 0));
                }
                break;
            case $account_class:
                if ( ! is_user_logged_in() ) {
                    $menu_item->_invalid = true;
                } else {
                    $menu_item->url = get_permalink(uwp_get_option('account_page', 0));
                }
                break;
            case $forgot_class:
                if ( is_user_logged_in() ) {
                    $menu_item->_invalid = true;
                } else {
                    $menu_item->url = get_permalink(uwp_get_option('forgot_page', 0));
                }
                break;
            case $logout_class:
                if ( ! is_user_logged_in() ) {
                    $menu_item->_invalid = true;
                } else {
                    $menu_item->url = $this->uwp_logout_url();
                }
                break;
        }

        return $menu_item;

    }

    public function uwp_get_page_slug($page_type = 'register_page') {
        $page_id = uwp_get_option($page_type, 0);
        if ($page_id) {
            $slug = get_post_field( 'post_name', get_post($page_id) );
        } else {
            $slug = false;
        }
        return $slug;

    }

    public function uwp_logout_url( $custom_redirect = null ) {

        $redirect = null;

        if ( !empty( $custom_redirect ) ) {
            $redirect = esc_url( $custom_redirect );
        } else if ( uwp_get_option('logout_redirect_to', false) ) {
            $redirect = esc_url( get_permalink( uwp_get_option('logout_redirect_to', 0) ) );
        }

        return wp_logout_url( apply_filters( 'uwp_logout_url', $redirect, $custom_redirect ) );

    }

    public function uwp_activation_redirect() {

        if (get_option('uwp_activation_redirect', false)) {

            delete_option('uwp_activation_redirect');

            wp_redirect(admin_url('admin.php?page=uwp&tab=main&subtab=info'));

        }

    }

}