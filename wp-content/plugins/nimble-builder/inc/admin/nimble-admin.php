<?php
namespace Nimble;
if ( ! defined( 'ABSPATH' ) ) exit;
add_action( 'plugins_loaded', '\Nimble\sek_versionning');
function sek_versionning() {
		$current_version = get_option( 'nimble_version' );
		if ( $current_version != NIMBLE_VERSION ) {
				update_option( 'nimble_version_upgraded_from', $current_version );
				update_option( 'nimble_version', NIMBLE_VERSION );
		}
		$started_with = get_option( 'nimble_started_with_version' );
		if ( empty( $started_with ) ) {
				update_option( 'nimble_started_with_version', $current_version );
		}
		$start_date = get_option( 'nimble_start_date' );
		if ( empty( $start_date ) ) {
				update_option( 'nimble_start_date', date("Y-m-d H:i:s") );
		}
}
add_action('admin_menu', '\Nimble\sek_plugin_menu');
function sek_plugin_menu() {
    if ( ! current_user_can( 'update_plugins' ) )
      return;
	  add_plugins_page(__( 'System infos', 'nimble-builder' ), __( 'System infos', 'nimble-builder' ), 'read', 'nimble-builder', '\Nimble\sek_plugin_page');
}

function sek_plugin_page() {
		?>
		<div class="wrap">
			<h3><?php _e( 'System Informations', 'nimble-builder' ); ?></h3>
			<h4 style="text-align: left"><?php _e( 'Please include your system informations when posting support requests.' , 'nimble-builder' ) ?></h4>
			<textarea readonly="readonly" onclick="this.focus();this.select()" id="system-info-textarea" name="tc-sysinfo" title="<?php _e( 'To copy the system infos, click below then press Ctrl + C (PC) or Cmd + C (Mac).', 'nimble-builder' ); ?>" style="width: 800px;min-height: 800px;font-family: Menlo,Monaco,monospace;background: 0 0;white-space: pre;overflow: auto;display:block;"><?php echo sek_config_infos(); ?></textarea>
		</div>
		<?php
}





/**
 * Get system info
 * Inspired by the system infos page for Easy Digital Download plugin
 * @return      string $return A string containing the info to output
 */
function sek_config_infos() {
		global $wpdb;

		if ( !class_exists( 'Browser' ) ) {
				require_once( NIMBLE_BASE_PATH . '/inc/libs/browser.php' );
		}

		$browser = new \Browser();
		$theme_data   = wp_get_theme();
		$theme        = $theme_data->Name . ' ' . $theme_data->Version;
		$parent_theme = $theme_data->Template;
		if ( ! empty( $parent_theme ) ) {
			$parent_theme_data = wp_get_theme( $parent_theme );
			$parent_theme      = $parent_theme_data->Name . ' ' . $parent_theme_data->Version;
		}

		$return  = '### Begin System Infos (Generated ' . date( 'Y-m-d H:i:s' ) . ') ###' . "";
		$return .= "\n" .'------------ SITE INFO' . "\n";
		$return .= 'Site URL:                 ' . site_url() . "\n";
		$return .= 'Home URL:                 ' . home_url() . "\n";
		$return .= 'Multisite:                ' . ( is_multisite() ? 'Yes' : 'No' ) . "\n";
		$return .= "\n\n" . '------------ USER BROWSER' . "\n";
		$return .= $browser;

		$locale = get_locale();
		$return .= "\n\n" . '------------ WORDPRESS CONFIG' . "\n";
		$return .= 'WP Version:               ' . get_bloginfo( 'version' ) . "\n";
		$return .= 'Language:                 ' . ( !empty( $locale ) ? $locale : 'en_US' ) . "\n";
		$return .= 'Permalink Structure:      ' . ( get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : 'Default' ) . "\n";
		$return .= 'Active Theme:             ' . $theme . "\n";
		if ( $parent_theme !== $theme ) {
			$return .= 'Parent Theme:             ' . $parent_theme . "\n";
		}
		$return .= 'Show On Front:            ' . get_option( 'show_on_front' ) . "\n";
		if( get_option( 'show_on_front' ) == 'page' ) {
			$front_page_id = get_option( 'page_on_front' );
			$blog_page_id = get_option( 'page_for_posts' );

			$return .= 'Page On Front:            ' . ( $front_page_id != 0 ? get_the_title( $front_page_id ) . ' (#' . $front_page_id . ')' : 'Unset' ) . "\n";
			$return .= 'Page For Posts:           ' . ( $blog_page_id != 0 ? get_the_title( $blog_page_id ) . ' (#' . $blog_page_id . ')' : 'Unset' ) . "\n";
		}

		$return .= 'ABSPATH:                  ' . ABSPATH . "\n";

		$return .= 'WP_DEBUG:                 ' . ( defined( 'WP_DEBUG' ) ? WP_DEBUG ? 'Enabled' : 'Disabled' : 'Not set' ) . "\n";
		$return .= 'WP Memory Limit:          ' . ( sek_let_to_num( WP_MEMORY_LIMIT )/( 1024 ) ) ."MB" . "\n";
		$return .= "\n\n" . '------------ NIMBLE CONFIGURATION' . "\n";
		$return .= 'Version:                  ' . NIMBLE_VERSION . "\n";
		$return .= 'Upgraded From:            ' . get_option( 'nimble_version_upgraded_from', 'None' ) . "\n";
		$return .= 'Started With:             ' . get_option( 'nimble_started_with_version', 'None' ) . "\n";
		$updates = get_plugin_updates();
		$muplugins = get_mu_plugins();
		if( count( $muplugins ) > 0 ) {
			$return .= "\n\n" . '------------ MU PLUGINS' . "\n";

			foreach( $muplugins as $plugin => $plugin_data ) {
				$return .= $plugin_data['Name'] . ': ' . $plugin_data['Version'] . "\n";
			}
		}
		$return .= "\n\n" . '------------ WP ACTIVE PLUGINS' . "\n";

		$plugins = get_plugins();
		$active_plugins = get_option( 'active_plugins', array() );

		foreach( $plugins as $plugin_path => $plugin ) {
			if( !in_array( $plugin_path, $active_plugins ) )
				continue;

			$update = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[$plugin_path]->update->new_version . ')' : '';
			$return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
		}
		$return .= "\n\n" . '------------ WP INACTIVE PLUGINS' . "\n";

		foreach( $plugins as $plugin_path => $plugin ) {
			if( in_array( $plugin_path, $active_plugins ) )
				continue;

			$update = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[$plugin_path]->update->new_version . ')' : '';
			$return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
		}

		if( is_multisite() ) {
			$return .= "\n\n" . '------------ NETWORK ACTIVE PLUGINS' . "\n";

			$plugins = wp_get_active_network_plugins();
			$active_plugins = get_site_option( 'active_sitewide_plugins', array() );

			foreach( $plugins as $plugin_path ) {
				$plugin_base = plugin_basename( $plugin_path );

				if( !array_key_exists( $plugin_base, $active_plugins ) )
					continue;

				$update = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[$plugin_path]->update->new_version . ')' : '';
				$plugin  = get_plugin_data( $plugin_path );
				$return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
			}
		}
		$return .= "\n\n" . '------------ WEBSERVER CONFIG' . "\n";
		$return .= 'PHP Version:              ' . PHP_VERSION . "\n";
		$return .= 'MySQL Version:            ' . $wpdb->db_version() . "\n";
		$return .= 'Webserver Info:           ' . $_SERVER['SERVER_SOFTWARE'] . "\n";
    $return .= 'Writing Permissions:      ' . sek_get_write_permissions_status() . "\n";
		$return .= "\n\n" . '------------ PHP CONFIG' . "\n";
		$return .= 'Memory Limit:             ' . ini_get( 'memory_limit' ) . "\n";
		$return .= 'Upload Max Size:          ' . ini_get( 'upload_max_filesize' ) . "\n";
		$return .= 'Post Max Size:            ' . ini_get( 'post_max_size' ) . "\n";
		$return .= 'Upload Max Filesize:      ' . ini_get( 'upload_max_filesize' ) . "\n";
		$return .= 'Time Limit:               ' . ini_get( 'max_execution_time' ) . "\n";
		$return .= 'Max Input Vars:           ' . ini_get( 'max_input_vars' ) . "\n";
		$return .= 'Display Errors:           ' . ( ini_get( 'display_errors' ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A' ) . "\n";
		$return .= 'PHP Arg Separator:        ' . ini_get( 'arg_separator.output' ) . "\n";
		$return .= 'PHP Allow URL File Open:  ' . ini_get( 'allow_url_fopen' ) . "\n";

		$return .= "\n\n" . '### End System Infos ###';

		return $return;
}


/**
 * Does Size Conversions
 */
function sek_let_to_num( $v ) {
		$l   = substr( $v, -1 );
		$ret = substr( $v, 0, -1 );

		switch ( strtoupper( $l ) ) {
			case 'P': // fall-through
			case 'T': // fall-through
			case 'G': // fall-through
			case 'M': // fall-through
			case 'K': // fall-through
				$ret *= 1024;
				break;
			default:
				break;
		}
		return $ret;
}



function sek_get_write_permissions_status() {
		$permission_issues = array();
    $writing_path_candidates = array();
		$wp_upload_dir = wp_upload_dir();
		if ( $wp_upload_dir['error'] ) {
			  $permission_issues[] = 'WordPress root uploads folder';
		}

		$nimble_css_folder_path = $wp_upload_dir['basedir'] . '/' . NIMBLE_CSS_FOLDER_NAME;

		if ( is_dir( $nimble_css_folder_path ) ) {
				$writing_path_candidates[ $nimble_css_folder_path ] = 'Nimble uploads folder';
		}
    $writing_path_candidates[ ABSPATH ] = 'WP root directory';

		foreach ( $writing_path_candidates as $dir => $description ) {
				if ( ! is_writable( $dir ) ) {
						$permission_issues[] = $description;
				}
		}

		if ( $permission_issues ) {
				$message = 'NOK => issues with : ';
				$message .= implode( ' and ', $permission_issues );
		} else {
				$message = 'OK';
		}

		return $message;
}
add_action( 'admin_init' , '\Nimble\sek_admin_style' );
function sek_admin_style() {
		if ( skp_is_customizing() )
			return;
		wp_enqueue_style(
				'nimble-admin-css',
				sprintf(
						'%1$s/assets/admin/css/%2$s' ,
						NIMBLE_BASE_URL,
						'nimble-admin.css'
				),
				array(),
				NIMBLE_ASSETS_VERSION,
				'all'
		);
}
add_action( 'admin_notices'                         , '\Nimble\sek_may_be_display_update_notice');
add_action( 'wp_ajax_dismiss_nimble_update_notice'  ,  '\Nimble\sek_dismiss_update_notice_action' );
foreach ( array( 'wptexturize', 'convert_smilies', 'wpautop') as $callback ) {
	if ( function_exists( $callback ) )
			add_filter( 'sek_update_notice', $callback );
}


/**
* @hook : admin_notices
*/
function sek_may_be_display_update_notice() {
		if ( defined('NIMBLE_SHOW_UPDATE_NOTICE_FOR_VERSION') && NIMBLE_SHOW_UPDATE_NOTICE_FOR_VERSION !== NIMBLE_VERSION )
			return;
		if ( ! sek_welcome_notice_is_dismissed() )
			return;

		$last_update_notice_values  = get_option( 'nimble_last_update_notice' );
		$show_new_notice = false;
		$display_ct = 5;

		if ( ! $last_update_notice_values || ! is_array($last_update_notice_values) ) {
				$last_update_notice_values = array( "version" => NIMBLE_VERSION, "display_count" => 0 );
				update_option( 'nimble_last_update_notice', $last_update_notice_values );
				if ( sek_user_started_before_version( NIMBLE_VERSION ) ) {
						$show_new_notice = true;
				}
		}

		$_db_version          = $last_update_notice_values["version"];
		$_db_displayed_count  = $last_update_notice_values["display_count"];
		if ( version_compare( NIMBLE_VERSION, $_db_version , '>' ) ) {
				if ( $_db_displayed_count < $display_ct ) {
						$show_new_notice = true;
						(int) $_db_displayed_count++;
						$last_update_notice_values["display_count"] = $_db_displayed_count;
						update_option( 'nimble_last_update_notice', $last_update_notice_values );
				}
				else {
						$new_val  = array( "version" => NIMBLE_VERSION, "display_count" => 0 );
						update_option('nimble_last_update_notice', $new_val );
				}//end else
		}//end if

		if ( ! $show_new_notice )
			return;

		ob_start();
			?>
			<div class="updated czr-update-notice" style="position:relative;">
				<?php
					printf('<h3>%1$s %2$s %3$s %4$s :D</h3>',
							__( "Thanks, you successfully upgraded", 'nimble-builder'),
							'Nimble Builder',
							__( "to version", 'nimble-builder'),
							NIMBLE_VERSION
					);
				?>
				<?php
					printf( '<h4>%1$s <a class="" href="%2$s" title="%3$s" target="_blank">%3$s &raquo;</a></h4>',
							'',//__( "Let us introduce the new features we've been working on.", 'text_doma'),
							NIMBLE_RELEASE_NOTE_URL,
							__( "Read the detailled release notes" , 'nimble-builder' )
					);
				?>
				<p style="text-align:right;position: absolute;font-size: 1.1em;<?php echo is_rtl()? 'left' : 'right';?>: 7px;bottom: -6px;">
				<?php printf('<a href="#" title="%1$s" class="nimble-dismiss-update-notice"> ( %1$s <strong>X</strong> ) </a>',
						__('close' , 'nimble-builder')
					);
				?>
				</p>
				<!-- <p>
					<?php
					?>
				</p> -->
			</div>
      <?php
      $_html = ob_get_contents();
      if ($_html) ob_end_clean();
      echo apply_filters( 'sek_update_notice', $_html );
      ?>
			<script type="text/javascript" id="nimble-dismiss-update-notice">
				( function($){
					var _ajax_action = function( $_el ) {
							var AjaxUrl = "<?php echo admin_url( 'admin-ajax.php' ); ?>",
									_query  = {
											action  : 'dismiss_nimble_update_notice',
											dismissUpdateNoticeNonce :  "<?php echo wp_create_nonce( 'dismiss-update-notice-nonce' ); ?>"
									},
									$ = jQuery,
									request = $.post( AjaxUrl, _query );

							request.fail( function ( response ) {});
							request.done( function( response ) {
								if ( '0' === response )
									return;
								if ( '-1' === response )
									return;

								$_el.closest('.updated').slideToggle('fast');
							});
					};//end of fn
					$( function($) {
						$('.nimble-dismiss-update-notice').click( function( e ) {
							e.preventDefault();
							_ajax_action( $(this) );
						} );
					} );

				})( jQuery );
			</script>
			<?php
}


/**
* hook : wp_ajax_dismiss_nimble_update_notice
* => sets the last_update_notice to the current Nimble version when user click on dismiss notice link
*/
function sek_dismiss_update_notice_action() {
		check_ajax_referer( 'dismiss-update-notice-nonce', 'dismissUpdateNoticeNonce' );
		$new_val  = array( "version" => NIMBLE_VERSION, "display_count" => 0 );
		update_option( 'nimble_last_update_notice', $new_val );
		wp_die( 1 );
}
/* beautify admin notice text using some defaults the_content filter callbacks */
foreach ( array( 'wptexturize', 'convert_smilies' ) as $callback ) {
		add_filter( 'nimble_update_notice', $callback );
}
function sek_welcome_notice_is_dismissed() {
		$dismissed = get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true );
		$dismissed_array = array_filter( explode( ',', (string) $dismissed ) );
		return in_array( NIMBLE_WELCOME_NOTICE_ID, $dismissed_array );
}

add_action( 'admin_notices', '\Nimble\sek_render_welcome_notice' );
function sek_render_welcome_notice() {
		if ( ! current_user_can( 'customize' ) )
			return;

		if ( sek_welcome_notice_is_dismissed() )
			return;
		if ( sek_site_has_nimble_sections_created() ) {
				$dismissed = get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true );
				$dismissed_array = array_filter( explode( ',', (string) $dismissed ) );
				$dismissed_array[] = NIMBLE_WELCOME_NOTICE_ID;
				$dismissed = implode( ',', $dismissed_array );
				update_user_meta( get_current_user_id(), 'dismissed_wp_pointers', $dismissed );
				return;
		}
		$notice_id = NIMBLE_WELCOME_NOTICE_ID;
		?>
		<div class="nimble-welcome-notice notice notice-info is-dismissible" id="<?php echo esc_attr( $notice_id ); ?>">
			<div class="notice-dismiss"></div>
			<div class="nimble-welcome-icon-holder">
				<img class="nimble-welcome-icon" src="<?php echo NIMBLE_BASE_URL.'/assets/img/nimble/nimble_banner.svg?ver='.NIMBLE_VERSION; ?>" alt="<?php esc_html_e( 'Nimble Builder', 'nimble-builder' ); ?>" />
			</div>
			<h1><?php echo apply_filters( 'nimble_update_notice', __('Welcome to the Nimble Builder for WordPress :D', 'nimble-builder' ) ); ?></h1>
			<h3><?php _e( 'The Nimble Builder takes the native WordPress customizer to a level you\'ve never seen before.', 'nimble-builder' ); ?></h3>
			<h3><?php _e( 'Nimble allows you to drag and drop content modules, or pre-built section templates, into <u>any context</u> of your site, including search results or 404 pages. You can edit your pages in <i>real time</i> from the live customizer, and then publish when you are happy of the result, or save for later.', 'nimble-builder' ); ?></h3>
			<h3><?php _e( 'The plugin automatically creates fluid and responsive sections for a pixel-perfect rendering on smartphones and tablets, without the need to add complex code.', 'nimble-builder' ); ?></h3>
			<?php printf( '<a href="%1$s" target="_blank" class="button button-primary button-hero"><span class="dashicons dashicons-admin-appearance"></span> %2$s</a>',
					esc_url( add_query_arg(
							array(
								array( 'autofocus' => array( 'section' => '__content_picker__' ) ),
								'return' => urlencode( remove_query_arg( wp_removable_query_args(), wp_unslash( $_SERVER['REQUEST_URI'] ) ) )
							),
							admin_url( 'customize.php' )
					) ),
					__( 'Start creating content in live preview', 'nimble-builder' )
			); ?>
			<div class="nimble-link-to-doc">
				<?php printf( '<div class="nimble-doc-link-wrap">%1$s <a href="%2$s" target="_blank" class="">%3$s</a>.</div>',
						__('Or', 'nimble-builder'),
						esc_url( add_query_arg(
								array(
									'utm_source' => 'usersite',
									'utm_medium' => 'link',
									'utm_campaign' => 'nimble-welcome-notice'
								),
								'docs.presscustomizr.com/article/337-getting-started-with-the-nimble-builder-plugin'
						) ),
						__( 'read the getting started guide', 'nimble-builder' )
				); ?>
			</div>
		</div>

		<script>
		jQuery( function( $ ) {
			$( <?php echo wp_json_encode( "#$notice_id" ); ?> ).on( 'click', '.notice-dismiss', function() {
				$.post( ajaxurl, {
					pointer: <?php echo wp_json_encode( $notice_id ); ?>,
					action: 'dismiss-wp-pointer'
				} );
			} );
		} );
		</script>
		<style type="text/css">
			.nimble-welcome-notice {
				padding: 38px;
			}
			.nimble-welcome-notice .dashicons {
				line-height: 44px;
			}
			.nimble-welcome-icon-holder {
				width: 550px;
				height: 200px;
				float: left;
				margin: 0 38px 38px 0;
			}
			.nimble-welcome-icon {
				width: 100%;
				height: 100%;
				display: block;
			}
			.nimble-welcome-notice h1 {
				font-weight: bold;
			}
			.nimble-welcome-notice h3 {
				font-size: 16px;
				font-weight: 500;
			}
			.nimble-link-to-doc {
				position: relative;
				display: inline-block;
				width: 200px;
				height: 46px;
			}
			.nimble-link-to-doc .nimble-doc-link-wrap {
				position: absolute;
				bottom: 0;
			}

		</style>
		<?php
}
