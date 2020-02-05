<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UsersWP_Admin_Setup_Wizard {

	private $step = '';

	private $steps = array();

	public function __construct() {

		add_action('admin_menu', array($this, 'admin_menus'));
		add_action('current_screen', array($this, 'setup_wizard'));
		add_action('uwp_wizard_content_use_userswp', array($this, 'content_use_userswp'));
		add_action('uwp_wizard_content_menus', array($this, 'content_menus'));
		add_action('uwp_wizard_content_dummy_users', array($this, 'content_dummy_users'));
		add_action('wp_ajax_uwp_wizard_setup_menu', array($this,'uwp_wizard_setup_menu'));
		add_action('admin_notices', array($this, 'setup_wizard_notice'));
		add_action( 'wp_loaded', array( $this, 'hide_wizard_notices' ) );

	}

	public function setup_wizard_notice() {

		$show_notice = get_option( "uwp_setup_wizard_notice" );

		if( isset($show_notice) && 1 == $show_notice) {
			?>
            <div id="message" class="updated notice-alt uwp-message">
                <p><?php _e( '<strong>Welcome to UsersWP</strong> &#8211; You&lsquo;re almost ready to start your site. :)', 'userswp' ); ?></p>
                <p class="submit">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=uwp-setup' ) ); ?>" class="button-primary"><?php _e( 'Run the Setup Wizard', 'userswp' ); ?></a>
                    <a class="button-secondary skip" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'uwp-hide-notice', 'install' ), 'uwp_hide_notices_nonce', '_uwp_notice_nonce' ) ); ?>"><?php _e( 'Skip setup', 'userswp' ); ?></a>
                </p>
            </div>
			<?php
		}

	}

	public function hide_wizard_notices() {

		if ( isset( $_GET['uwp-hide-notice'] ) && isset( $_GET['_uwp_notice_nonce'] ) ) {

			if ( ! wp_verify_nonce( $_GET['_uwp_notice_nonce'], 'uwp_hide_notices_nonce' ) ) {
				wp_die( __( 'Action failed. Please refresh the page and retry.', 'userswp' ) );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( __( 'Cheatin&#8217; huh?', 'userswp' ) );
			}

			delete_option('uwp_setup_wizard_notice');
		}
	}

	public function admin_menus(){
		add_dashboard_page( '', '', 'manage_options', 'uwp-setup', '' );
	}

	public function setup_wizard() {

		if ( empty( $_GET['page'] ) || 'uwp-setup' !== $_GET['page'] ) {
			return;
		}

		$suffix  = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		$default_steps = array(
			'introduction'     => array(
				'name'    => __( 'Introduction', 'userswp' ),
				'view'    => array( $this, 'setup_introduction' ),
				'handler' => '',
			),
			'content'          => array(
				'name'    => __( 'Content', 'userswp' ),
				'view'    => array( $this, 'setup_content' ),
				'handler' => array( $this, 'setup_content_save' ),
			),
			/*'recommend'        => array(
				'name'    => __( 'Recommend', 'userswp' ),
				'view'    => array( $this, 'setup_recommend' ),
				'handler' => array( $this, 'setup_recommend_save' ),
			),*/
			'next_steps'       => array(
				'name'    => __( 'Ready!', 'userswp' ),
				'view'    => array( $this, 'setup_ready' ),
				'handler' => '',
			),
		);

		$this->steps = apply_filters( 'uwp_setup_wizard_steps', $default_steps );
		$this->step      = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : current( array_keys( $this->steps ) );

		wp_enqueue_style('wp-admin' );
		$obj = WP_Font_Awesome_Settings::instance();
		$obj->enqueue_style();
		$obj->enqueue_scripts();
		wp_enqueue_style( 'uwp-setup-wizard-style', USERSWP_PLUGIN_URL . 'admin/assets/css/setup-wizard' . $suffix . '.css',array( 'dashicons', 'install', 'thickbox'),'','' );

		$required_scripts = array(
			'jquery',
			'jquery-ui-tooltip',
			'thickbox',
			'jquery-ui-progressbar'
		);

		wp_register_script( 'uwp-setup', USERSWP_PLUGIN_URL . 'admin/assets/js/setup-wizard' . $suffix . '.js', $required_scripts , USERSWP_VERSION );

		wp_localize_script( 'uwp-setup', 'uwp_wizard_obj',  array(
			'ajaxurl' => admin_url( 'admin-ajax.php' )
		));

		if ( ! empty( $_POST['save_step'] ) && isset( $this->steps[ $this->step ]['handler'] ) ) {
			call_user_func( $this->steps[ $this->step ]['handler'], $this );
		}

		ob_start();

		$this->setup_wizard_header();
		$this->setup_wizard_steps();
		$this->setup_wizard_content();
		$this->setup_wizard_footer();
		exit;
	}

	public function setup_wizard_header() {
		?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta name="viewport" content="width=device-width"/>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <title><?php esc_html_e( 'Userswp &rsaquo; Setup Wizard', 'userswp' ); ?></title>
			<?php do_action( 'admin_print_styles' ); ?>
			<?php wp_print_scripts( 'uwp-setup' ); ?>
			<?php do_action( 'admin_head' ); ?>
        </head>
        <body class="uwp-setup wp-core-ui">
        <h1 id="uwp-logo">
            <a href="https://userswp.io/"><span class="dashicons dashicons-groups"></span>
                <span><span class="text-secondary">Users</span><span class="text-primary">WP</span></span>
            </a>
        </h1>
		<?php
	}

	public function setup_wizard_steps() {

		$ouput_steps = $this->steps;

		array_shift( $ouput_steps );
		?>
        <ol class="uwp-setup-steps">
			<?php foreach ( $ouput_steps as $step_key => $step ) : ?>
                <li class="<?php
				if ( $step_key === $this->step ) {
					echo 'active';
				} elseif ( array_search( $this->step, array_keys( $this->steps ) ) > array_search( $step_key, array_keys( $this->steps ) ) ) {
					echo 'done';
				}
				?>">
					<?php echo esc_html( $step['name'] ); ?>
                </li>
			<?php endforeach; ?>
        </ol>
		<?php
	}

	public function setup_wizard_content() {
		echo '<div class="uwp-setup-content">';
		call_user_func( $this->steps[ $this->step ]['view'], $this );
		echo '</div>';
	}

	public function setup_wizard_footer() {

		if ( 'next_steps' === $this->step ) : ?>
            <p class="uwp-return-to-dashboard-wrap">
                <a class="uwp-return-to-dashboard" href="<?php echo esc_url( admin_url() ); ?>">
					<?php esc_html_e( 'Return to the WordPress Dashboard', 'userswp' ); ?>
                </a>
            </p>
		<?php endif; ?>
        </body>
        </html>
		<?php
	}

	public function get_next_step_link( $step = '' ) {
		if ( ! $step ) {
			$step = $this->step;
		}

		$keys = array_keys( $this->steps );
		if ( end( $keys ) === $step ) {
			return admin_url();
		}

		$step_index = array_search( $step, $keys );
		if ( false === $step_index ) {
			return '';
		}

		return add_query_arg( 'step', $keys[ $step_index + 1 ] );
	}

	public function setup_introduction() {
		?>
        <h2><?php esc_html_e( 'Thank you for choosing UsersWP!', 'userswp' ); ?></h2>
        <p><?php _e('This quick setup wizard will help you configure the basic settings. It\'s completely optional and shouldn\'t take longer than two minutes.','userswp'); ?></p>
        <p><?php _e('No time right now? If you don\'t want to go through the wizard, you can skip and return to the WordPress dashboard. Come back anytime if you change your mind!','userswp'); ?></p>

        <p class="uwp-setup-actions step">
            <a href="<?php echo esc_url( admin_url() ); ?>" class="button button-large"><?php esc_html_e( 'Not right now', 'userswp' ); ?></a>
            <a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button-primary button button-large button-next"><?php esc_html_e( "Let's go!", 'userswp' ); ?></a>
        </p>
		<?php
	}

	public function setup_content() {

		$wizard_content = array(
			'use_userswp'   => __( "How will you use UsersWP", "userswp" ),
			'menus'   => __( "Menus", "userswp" ),
			'dummy_users'   => __( "Dummy Users", "userswp" ),
		);

		$wizard_content = apply_filters( 'uwp_wizard_content', $wizard_content );
		?>
        <div class="uwp-wizard-content-parts">
            <ul>
				<?php
				foreach ( $wizard_content as $slug => $title ) {
					echo '<li class="uwp-sub-menu-tab"><a href="#' . esc_attr( $slug ) . '">' . esc_attr( $title ) . '</a></li>' . " \n";
				}
				?>
            </ul>
        </div>
        <form method="post">
			<?php
			foreach ( $wizard_content as $slug => $title ) {
				echo '<h2 class="uwp-settings-title "><a id="' . esc_attr( $slug ) . '"></a>' . esc_attr( $title ) . '</h2>' . " \n"; // line break adds a nice spacing
				do_action( "uwp_wizard_content_{$slug}" );
			}
			?>
            <p class="uwp-setup-actions step">
                <a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-large button-next"><?php esc_html_e( 'Skip this step', 'userswp' ); ?></a>
                <input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Continue', 'userswp' ); ?>" name="save_step"/>
				<?php wp_nonce_field( 'uwp-setup' ); ?>
            </p>
        </form>
		<?php
	}

	public function content_use_userswp() {
		?>
        <table class="form-table uwp-dummy-table">
            <tbody>
            <tr>
                <td>
                    <input type="checkbox" name="login_register_page" class="use-uwp-pages" value="1" checked>
                    <strong><?php _e( "Login/Register", "userswp" ); ?></strong></td>
                <td></td>
            </tr>
            <tr>
                <td>
                    <input type="checkbox" name="user_profiles_page" class="use-uwp-pages" value="1" checked>
                    <strong><?php _e( "User Profiles", "userswp" ); ?></strong></td>
                <td></td>
            </tr>
            <tr>
                <td>
                    <input type="checkbox" name="member_directory_page" class="use-uwp-pages" value="1" checked>
                    <strong><?php _e( "Members Directory", "userswp" ); ?></strong></td>
                <td></td>
            </tr>
            </tbody>
        </table>
		<?php
	}

	public function content_menus() {
		?>
        <table class="form-table uwp-dummy-table">
            <tbody>
            <tr>
                <td>
                    <strong><?php _e( "Select the theme main menu", "userswp" ); ?></strong>
                </td>
                <td style="width: 20%">
                    <strong><?php _e( "Action", "userswp" ); ?></strong>
                </td>
            </tr>
            <tr>
                <td>
					<?php
					$set_menus = get_nav_menu_locations();
					$set_menus = array_filter( $set_menus );

					if ( ! empty( $set_menus ) ) {

						echo "<select id='uwp_wizard_menu_id' data-type='add' class='uwp-select' >";

						foreach ( $set_menus as $menu_location => $menu_id ) {
							$selected = '';

							if ( strpos( strtolower( $menu_location ), 'primary' ) !== false || strpos( strtolower( $menu_location ), 'main' ) !== false ) {
								$selected = 'selected="selected"';
							}

							$menu_item = wp_get_nav_menus( $menu_id )[0];

							?>
                            <option value="<?php echo esc_attr( $menu_id ); ?>" <?php echo $selected; ?>>
								<?php echo esc_attr( $menu_item->name );
								if ( $selected ) {
									echo ' ';
									_e( '( Auto detected )', 'userswp' );
								} ?>
                            </option>
							<?php
						}

						echo "</select>";

					} else {
						$menus = get_registered_nav_menus();

						if ( ! empty( $menus ) ) {

							echo "<select id='uwp_wizard_menu_location' data-type='create' class='uwp-select' >";

							foreach ( $menus as $menu_slug => $menu_name ) {
								$selected = '';

								if ( strpos( strtolower( $menu_slug ), 'primary' ) !== false || strpos( strtolower( $menu_slug ), 'main' ) !== false ) {
									$selected = 'selected="selected"';
								}
								?>
                                <option value="<?php echo esc_attr( $menu_slug ); ?>" <?php echo $selected; ?>>
									<?php _e( 'Create new menu in:', 'userswp' );
									echo ' ' . esc_attr( $menu_name );
									if ( $selected ) {
										echo ' ';
										_e( '( Auto detected )', 'userswp' );
									} ?>
                                </option>
								<?php
							}
							echo "</select>";
						}
					}
					?>
                    <div class="notice inline notice-success notice-alt uwp-wizard-menu uwp-wizard-menu-result"></div>
                </td>
                <td>
                    <input type="button" value="<?php _e( "Insert menu items", "userswp" ); ?>" class="button-primary uwp_dummy_button" onclick="uwp_wizard_setup_menu('<?php echo wp_create_nonce( "uwp-wizard-setup-menu" ); ?>'); return false;" >
                </td>
            </tr>
            </tbody>
        </table>
		<?php

	}

	public function content_dummy_users() {

		$get_dummy_user_passowrd = $this->get_dummy_user_passowrd();
		$dummy_users = get_users( array( 'meta_key' => 'uwp_dummy_user', 'meta_value' => '1', 'fields' => array( 'ID' ) ) );
		$total_dummy_users = !empty( $dummy_users ) ? count($dummy_users) : 0;
		?>
        <table class="form-table uwp-dummy-table">
            <tbody>
            <tr>
                <td>
                    <p><?php echo sprintf(__('Dummy Users for Testing. Password for all dummy users: <strong>%s</strong>','userswp'),$get_dummy_user_passowrd); ?></p>
                </td>
                <td style="width: 20%">
                    <input style="display: <?php echo ( $total_dummy_users > 0 ) ? 'block' :'none'; ?>" type="button" value="<?php _e('Remove', 'userswp');?>" class="button-primary button uwp_diagnosis_button uwp_dummy_users_button uwp_remove_dummy_users_button" onclick="uwp_wizard_setup_dummy_users('<?php echo wp_create_nonce( "uwp_process_diagnosis" ); ?>','remove_dummy_users'); return false;"/>
                    <input style="display: <?php echo ( $total_dummy_users > 0 ) ? 'none' :'block'; ?>"type="button" value="<?php _e( "Create Users", "userswp" ); ?>" class="button-primary button uwp_diagnosis_button uwp_dummy_users_button uwp_add_dummy_users_button" onclick="uwp_wizard_setup_dummy_users('<?php echo wp_create_nonce( "uwp_process_diagnosis" ); ?>','add_dummy_users'); return false;" >
                </td>
            </tr>
            <tr>
                <td colspan="2" class="has-pbar">
                    <div id="uwp_diagnose_pb_add_dummy_users" class="uwp-pb-wrapper">
                        <div class="progressBar" style="display: none;"><div></div></div>
                    </div>
                    <div id="uwp_diagnose_add_dummy_users"></div>
                    <div id="uwp_diagnose_pb_remove_dummy_users" class="uwp-pb-wrapper">
                        <div class="progressBar" style="display: none;"><div></div></div>
                    </div>
                    <div id="uwp_diagnose_remove_dummy_users"></div>
                </td>
            </tr>
            </tbody>
        </table>
		<?php
	}

	public function get_dummy_user_passowrd(){
		return substr(hash( 'SHA256', AUTH_KEY . site_url() ), 0, 15);
	}

	public function setup_content_save() {
		check_admin_referer( 'uwp-setup' );

		if( !empty( $_REQUEST['step'] ) && 'content' === $_REQUEST['step'] ) {
			$this->save_content_step_data();
		}

		wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	public function save_content_step_data() {

		$login_register_page = !empty( $_POST['login_register_page'] ) ? $_POST['login_register_page'] : '';
		$user_profiles_page = !empty( $_POST['user_profiles_page'] ) ? $_POST['user_profiles_page'] : '';
		$member_directory_page = !empty( $_POST['member_directory_page'] ) ? $_POST['member_directory_page'] : '';

		$login_page = uwp_get_page_id('login_page');
		$register_page = uwp_get_page_id('register_page');
		$change_page = uwp_get_page_id('change_page');
		$forgot_page = uwp_get_page_id('forgot_page');
		$reset_page = uwp_get_page_id('reset_page');
		$profile_page = uwp_get_page_id('profile_page');
		$account_page = uwp_get_page_id('account_page');
		$users_page = uwp_get_page_id('users_page');
		$user_list_item_page = uwp_get_page_id('user_list_item_page');

		$login_pages = array($login_page,$register_page,$change_page,$forgot_page,$reset_page);

		if( !empty($login_pages) && count($login_pages) > 0 ) {
			$login_page_status = 'draft';
			foreach ( $login_pages as $login_page_id ) {

				if(!empty($login_register_page)) {
					$login_page_status = 'publish';
				}

				wp_update_post( array(
					'ID'          => $login_page_id,
					'post_status' => $login_page_status
				) );

			}
		}

		$profile_pages = array($profile_page,$account_page);

		if( !empty($profile_pages) && count($profile_pages) > 0 ) {
			$profile_page_status = 'draft';
			foreach ( $profile_pages as $profile_page_id ) {

				if(!empty($user_profiles_page)) {
					$profile_page_status = 'publish';
				}

				wp_update_post( array(
					'ID'          => $profile_page_id,
					'post_status' => $profile_page_status
				) );

			}
		}

		$users_pages = array($users_page,$user_list_item_page);

		if( !empty($users_pages) && count($users_pages) > 0 ) {
			$user_page_status = 'draft';
			foreach ( $users_pages as $user_page_id ) {

				if(!empty($member_directory_page)) {
					$user_page_status = 'publish';
				}

				wp_update_post( array(
					'ID'          => $user_page_id,
					'post_status' => $user_page_status
				) );

			}
		}

	}

	/*public function setup_recommend() {
		?>
        <form method="post">
            <div class="uwp-wizard-recommend">
                <h2 class="uwp-settings-title "><?php _e( "Recommended Plugins", "userswp" ); ?></h2>
                <p><?php _e( "Below are a few recommended plugins that will help you with your site.", "userswp" ); ?></p>
				<?php
				include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );

				$recommend_wp_plugins = self::get_recommend_wp_plugins();

				if ( ! empty( $recommend_wp_plugins ) ) {
					echo "<ul>";

					$installed_text = '<i class="fas fa-check-circle" aria-hidden="true"></i> '. __( 'Installed', 'userswp' );
					$installing_text = '<i class="fas fa-sync fa-spin" aria-hidden="true"></i> ' . __( 'Installing', 'userswp' );

					echo "<input type='hidden' id='uwp-installing-text' value='$installing_text' >";
					echo "<input type='hidden' id='uwp-installed-text' value='$installed_text' >";

					foreach ( $recommend_wp_plugins as $plugin ) {
						$status = install_plugin_install_status( array( "slug" => $plugin['slug'], "version" => "" ) );

						$plugin_status = isset( $status['status'] ) ? $status['status'] : '';
						$url           = isset( $status['url'] ) ? $status['url'] : '';

						if ( $plugin_status == 'install' ) {
							$checked        = "checked";
							$disabled       = "";
							$checkbox_class = "class='uwp_install_plugins'";
						} else {
							$checked        = "checked";
							$disabled       = "disabled";
							$checkbox_class = "";
						}

						$uwp_html_tip = '<span class="gd-help-tip dashicons dashicons-editor-help" title="' . $plugin['desc'] . '"></span>';
						echo "<li class='" . $plugin['slug'] . "'>";
						echo "<input type='checkbox' id='" . $plugin['slug'] . "' $checked $disabled $checkbox_class />";
						echo $plugin['name'] . " " . $uwp_html_tip;
						echo " | <a href='" . admin_url( "plugin-install.php?uwp_wizard_recommend=true&tab=plugin-information&plugin=" . $plugin['slug'] . '&TB_iframe=true&width=772&height=407' ) . "' class='thickbox'>".__('More info', 'userswp')."</a>";
						if ( $plugin_status == 'install' && $url ) {
							echo " | <span class='uwp-plugin-status' >( " . __( 'Tick to install', 'userswp' ) . " )</span>";
						} else {
							if ( ! empty( $plugin_status ) ) {
								$plugin_status = $installed_text;
							}
							echo " | <span class='uwp-plugin-status'>$plugin_status</span>";
						}
						echo "</li>";

					}
					echo "</ul>";
				}
				?>
            </div>
            <p class="uwp-setup-actions step">
				<?php wp_nonce_field( 'uwp-setup' ); ?>
                <a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-large button-next"><?php esc_html_e( 'Skip this step', 'userswp' ); ?></a>
                <input type="submit" class="button-primary button button-large button-next uwp-continue-recommend" value="<?php esc_attr_e( 'Continue', 'userswp' ); ?>" name="save_step"/>
                <input type="submit" class="button-primary button button-large button-next uwp-install-recommend" value="<?php esc_attr_e( 'Install', 'userwp' ); ?>" name="install_recommend" onclick="uwp_wizard_install_plugins('<?php echo wp_create_nonce( 'updates' ); ?>');return false;"/>
            </p>
        </form>
		<?php
	}*/

	public static function get_recommend_wp_plugins(){

		$plugins = array(
			'geodirectory' => array(
				'url'   => 'https://wordpress.org/plugins/geodirectory/',
				'slug'   => 'geodirectory',
				'name'   => 'GeoDirectory',
				'desc'   => __('Create a local directory, based on a single location, using the GeoDirectory.','userswp'),
			),
			'invoicing' => array(
				'url'   => 'https://wordpress.org/plugins/invoicing/',
				'slug'   => 'invoicing',
				'name'   => 'Invoicing',
				'desc'   => __('Create & Send Invoices, Manage Taxes & VAT. Collect One Time & Recurring Payments.','userswp'),
			),
			'ninja-forms' => array(
				'url'   => 'https://wordpress.org/plugins/ninja-forms/',
				'slug'   => 'ninja-forms',
				'name'   => 'Ninja Forms',
				'desc'   => __('Setup forms such as contact or booking forms for your listings.','userswp'),
			),
		);

		return $plugins;
	}

	/*public function setup_recommend_save() {
		check_admin_referer( 'uwp-setup' );
		wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}*/

	public function setup_ready(){
		$this->setup_ready_actions();
		?>
        <h2><?php esc_html_e( 'Awesome, you are ready to go!', 'userswp' ); ?></h2>
        <div class="uwp-message">
            <p><?php _e('Thank you for using UsersWP :)','userswp'); ?></p>
        </div>
        <div class="uwp-setup-next-steps-last">
            <h2><?php _e( 'Learn more', 'userswp' ); ?></h2>
            <ul>
                <li class="uwp-getting-started">
                    <a href="https://userswp.io/docs/?utm_source=setupwizard&utm_medium=product&utm_content=getting-started&utm_campaign=userswpplugin" target="_blank"><?php esc_html_e( 'Getting started guide', 'userswp' ); ?></a>
                </li>
                <li class="uwp-newsletter">
                    <a href="https://userswp.io/newsletter-signup/?utm_source=setupwizard&utm_medium=product&utm_content=newsletter&utm_campaign=userswpplugin" target="_blank"><?php esc_html_e( 'Get Userswp advice in your inbox', 'userswp' ); ?></a>
                </li>
                <li class="uwp-get-help">
                    <a href="https://userswp.io/support/?utm_source=setupwizard&utm_medium=product&utm_content=docs&utm_campaign=userswpplugin" target="_blank"><?php esc_html_e( 'Have questions? Get help.', 'userswp' ); ?></a>
                </li>
            </ul>
        </div>
		<?php
	}

	private function setup_ready_actions() {
		delete_option('uwp_setup_wizard_notice');
	}

	public function uwp_wizard_setup_menu() {

		check_ajax_referer( 'uwp-wizard-setup-menu', 'security' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$menu_id = isset($_REQUEST['menu_id']) ?  sanitize_title_with_dashes($_REQUEST['menu_id']) : '';
		$menu_location = isset($_REQUEST['menu_location']) ?  sanitize_title_with_dashes($_REQUEST['menu_location']) : '';
		$result = $this->setup_menu( $menu_id, $menu_location);

		if(is_wp_error( $result ) ){
			wp_send_json_error( $result->get_error_message() );
		}else{
			wp_send_json_success($result);
		}

		wp_die();

	}

	public static function setup_menu( $menu_id = '',$menu_location = '' ) {

		$menu_id = sanitize_title_with_dashes($menu_id);
		$menu_location = sanitize_title_with_dashes($menu_location);

		// confirm the sidebar_id is valid
		if(!$menu_id && !$menu_location){
			return new WP_Error( 'uwp-wizard-setup-menu', __( "The menu is not valid.", "userswp" ) );
		}

		$items_added = 0;
		$items_exist= 0;

		if($menu_id){

			$menu_exists = wp_get_nav_menu_object( $menu_id );

			if(!$menu_exists){
				return new WP_Error( 'uwp-wizard-setup-menu', __( "The menu is not valid.", "userswp" ) );
			}

			$current_menu_items = wp_get_nav_menu_items( $menu_id );

			$current_menu_titles = array();
			// get a list of current slugs so we don't add things twice.
			if(!empty($current_menu_items)){
				foreach($current_menu_items as $current_menu_item){
					if(!empty($current_menu_item->post_name)){
						$current_menu_titles[] = $current_menu_item->title;
					}
				}
			}

			$uwp_menus = new UsersWP_Menus();
			$uwp_menu_items = $uwp_menus->get_endpoints();

			if(!empty($uwp_menu_items)){
				foreach($uwp_menu_items as $menu_item_type){
					if(!empty($menu_item_type)){

						$menu_item_type = array_map('wp_setup_nav_menu_item', $menu_item_type);

						foreach($menu_item_type as $menu_item){

							if(!empty($current_menu_titles) && (in_array($menu_item->title,$current_menu_titles) || in_array(str_replace(" page",'',$menu_item->title),$current_menu_titles))){
								$items_exist++; continue 2;
							}

							// setup standard menu stuff
							$menu_item->{'menu-item-object-id'} = $menu_item->object_id;
							$menu_item->{'menu-item-object'} = $menu_item->object;
							$menu_item->{'menu-item-type'} = $menu_item->type;
							$menu_item->{'menu-item-status'} = 'publish';
							$menu_item->{'menu-item-classes'} = !empty($menu_item->classes) ? implode(" ",$menu_item->classes) : 'uwp-menu-item';
							if($menu_item->type=='custom'){
								$menu_item->{'menu-item-url'} = $menu_item->url;
								$menu_item->{'menu-item-title'} = $menu_item->title;
							}

							wp_update_nav_menu_item($menu_id, 0, $menu_item);
							$items_added++;
						}
					}
				}
			}

		} elseif($menu_location){

			$menuname = "UsersWP Menu";

			$menu_exists = wp_get_nav_menu_object( $menuname );

			// If it doesn't exist, let's create it.
			if( !$menu_exists) {
				$menu_id = wp_create_nav_menu( $menuname );

				$locations = get_theme_mod( 'nav_menu_locations' );

				if($menu_id){
					$locations[$menu_location] = $menu_id;
					set_theme_mod('nav_menu_locations', $locations);
					return self::setup_menu($menu_id);
				}

			}else{
				return new WP_Error( 'uwp-wizard-setup-menu', __( "Menu already exists.", "userswp" ) );
			}

		}

		if($items_added == 0 && $items_exist > 0){
			return __( 'Menu items already exist, none added.' , 'userswp' );
		}elseif($items_added  > 0){
			return __( 'Menu items added successfully.' , 'userswp' );
		}else{
			return __( 'Something went wrong, you can manually add items in Appearance > Menus' , 'userswp' );
		}


	}

}

new UsersWP_Admin_Setup_Wizard();