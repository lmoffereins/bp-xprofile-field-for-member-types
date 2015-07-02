<?php

/**
 * The BP XProfile Field For Member Types Plugin
 *
 * Requires BP 2.2
 *
 * @package BP XProfile Field For Member Types
 * @subpackage Main
 */

/**
 * Plugin Name:       BP XProfile Field For Member Types
 * Description:       Manage member type specific XProfile fields in BuddyPress
 * Plugin URI:        https://github.com/lmoffereins/bp-xprofile-field-for-member-types
 * Version:           1.0.0
 * Author:            Laurens Offereins
 * Author URI:        https://github.com/lmoffereins
 * Network:           true
 * Text Domain:       bp-xprofile-field-for-member-types
 * Domain Path:       /languages/
 * GitHub Plugin URI: lmoffereins/bp-xprofile-field-for-member-types
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BP_XProfile_Field_For_Member_Types' ) ) :
/**
 * Main Plugin Class
 *
 * @since 1.0.0
 */
final class BP_XProfile_Field_For_Member_Types {

	/**
	 * Setup and return the singleton pattern
	 *
	 * @since 1.0.0
	 *
	 * @uses BP_XProfile_Field_For_Member_Types::setup_actions()
	 * @return BP_XProfile_Field_For_Member_Types
	 */
	public static function instance() {

		// Store the instance locally
		static $instance = null;

		if ( null === $instance ) {
			$instance = new BP_XProfile_Field_For_Member_Types;
			$instance->setup_globals();
			$instance->setup_actions();
		}

		// Always return the instance
		return $instance;
	}

	/**
	 * Setup plugin structure and hooks
	 *
	 * @since 1.0.0
	 */
	private function __construct() { /* Do nothing here */ }

	/**
	 * Setup default class globals
	 *
	 * @since 1.0.0
	 */
	private function setup_globals() {

		/** Version **************************************************/

		$this->version    = '1.0.0';

		/** Plugin ***************************************************/

		$this->file       = __FILE__;
		$this->basename   = plugin_basename( $this->file );
		$this->plugin_dir = plugin_dir_path( $this->file );
		$this->plugin_url = plugin_dir_url(  $this->file );

		// Languages
		$this->lang_dir   = trailingslashit( $this->plugin_dir . 'languages' );

		/** Misc *****************************************************/

		$this->domain     = 'bp-xprofile-field-for-member-types';
	}

	/**
	 * Setup default plugin actions and filters
	 *
	 * @since 1.0.0
	 *
	 * @uses bp_is_active() To check whether xprofile component is active
	 */
	private function setup_actions() {

		// Require BP 2.2 and the XProfile component
		if ( version_compare( buddypress()->version, '2.2', '<' ) || ! bp_is_active( 'xprofile' ) )
			return;

		// Plugin
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// Main Logic
		add_filter( 'bp_xprofile_get_hidden_fields_for_user', array( $this, 'filter_hidden_fields' ), 10, 3 );

		// Metabox
		add_action( 'xprofile_field_after_submitbox', array( $this, 'field_display_member_type_metabox' ) );
		add_action( 'xprofile_field_after_save',      array( $this, 'field_save_member_type_metabox'    ) );

		// Admin: Profile Fields
		add_action( 'xprofile_admin_field_name_legend', array( $this, 'admin_field_legend' ) );

		// Fire plugin loaded hook
		do_action( 'bp_xprofile_field_for_member_types_loaded' );
	}

	/** Plugin ****************************************************************/

	/**
	 * Load the translation file for current language
	 *
	 * Note that custom translation files inside the Plugin folder will
	 * be removed on Plugin updates. If you're creating custom translation
	 * files, please use the global language folder.
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'plugin_locale' with {@link get_locale()} value
	 * @uses load_textdomain() To load the textdomain
	 * @uses load_plugin_textdomain() To load the plugin textdomain
	 */
	public function load_textdomain() {

		// Traditional WordPress plugin locale filter
		$locale        = apply_filters( 'plugin_locale', get_locale(), $this->domain );
		$mofile        = sprintf( '%1$s-%2$s.mo', $this->domain, $locale );

		// Setup paths to current locale file
		$mofile_local  = $this->lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/bp-xprofile-field-for-member-types/' . $mofile;

		// Look in global /wp-content/languages/bp-xprofile-field-for-member-types folder first
		load_textdomain( $this->domain, $mofile_global );

		// Look in global /wp-content/languages/plugins/ and local plugin languages folder
		load_plugin_textdomain( $this->domain, false, 'bp-xprofile-field-for-member-types/languages' );
	}

	/** Main Logic ************************************************************/

	/**
	 * Return the field ids that are not visible for the displayed and current user
	 *
	 * The displayed user must have a member type of the field in order to show the field. 
	 * If the check fails, the field is added to the hidden fields collection.
	 *
	 * @since 1.0.0
	 *
	 * @param array $hidden_fields Hidden field ids
	 * @param int $displayed_user_id Displayed user ID
	 * @param int $current_user_id Loggedin user ID
	 * @return array Hidden field ids
	 */
	public function filter_hidden_fields( $hidden_fields, $displayed_user_id, $current_user_id ) {
		global $wpdb, $bp;

		// Hidden = All - Visible for displayed user AND current user
		$all_fields = array_map( 'intval', (array) $wpdb->get_col( "SELECT id FROM {$bp->profile->table_name_fields}" ) );

		foreach ( $all_fields as $k => $field_id ) {

			// Is displayed user not a member? Remove field
			if ( ! $this->has_user_field_member_type( $field_id, $displayed_user_id ) ) {
				$hidden_fields[] = $field_id;
			}
		}

		// Sanitize return value
		$hidden_fields = array_unique( $hidden_fields );

		return $hidden_fields;
	}

	/**
	 * Return whether the user has one of the field's member types
	 *
	 * @since 1.0.0
	 *
	 * @uses bp_displayed_user_id()
	 * @uses BP_XProfile_Field_For_Member_Types::get_xprofile_member_types()
	 * @uses bp_get_member_type()
	 *
	 * @param int|object $field_group_id Field ID or 
	 * @param int $user_id Optional. User ID. Defaults to the displayed user.
	 * @return bool User has field's member type
	 */
	public function has_user_field_member_type( $field_id, $user_id = 0 ) {

		// Get field ID
		if ( is_object( $field_id ) ) {
			$field_id = $field_id->id;
		}

		// The primary field is for all, so bail
		if ( 1 === (int) $field_id )
			return true;

		// Default to displayed user
		if ( ! is_numeric( $user_id ) ) {
			$user_id = bp_displayed_user_id();
		}

		// Get the field's member types
		if ( $member_types = $this->get_xprofile_member_types( $field_id, 'field' ) ) {

			// Default to 'none' when the user has no member type(s)
			if ( ! $u_member_types = bp_get_member_type( $user_id, false ) ) {
				$u_member_types = array( 'none' );
			}

			// Validate user by the field's member types
			$validate = array_intersect( $member_types, $u_member_types );

			// Return whether we have any matches
			return ! empty( $validate );

		// No member types were assigned, so user validates
		} else {
			return true;
		}
	}

	/** CRUD ******************************************************************/

	/**
	 * Return a field's or group's assigned member types meta value
	 *
	 * @since 1.0.0
	 *
	 * @uses bp_xprofile_get_meta()
	 *
	 * @param int $object_id Field or group ID
	 * @param string $meta_type Type of meta, either 'field' or 'group'
	 * @return array Field or group member type names
	 */
	public function get_xprofile_member_types( $object_id, $meta_type ) {

		// Get all meta instances of 'member-type' meta
		$meta = bp_xprofile_get_meta( $object_id, $meta_type, 'member-type', false );

		// Sanitize meta
		if ( empty( $meta ) ) {
			$meta = array();
		}

		return $meta;
	}

	/**
	 * Update a field's or group's member types meta value
	 *
	 * @since 1.0.0
	 *
	 * @uses BP_XProfile_Field_For_Member_Types::get_xprofile_member_types()
	 * @uses bp_xprofile_delete_meta()
	 * @uses bp_xprofile_add_meta()
	 * 
	 * @param int $object_id Field or group ID
	 * @param string $meta_type Type of meta, either 'field' or 'group'
	 * @param array $selected_types Selected member type names
	 * @return bool Update success or failure
	 */
	public function update_xprofile_member_types( $object_id, $meta_type, $selected_types ) {
		$current_types = $this->get_xprofile_member_types( $object_id, $meta_type );

		// Delete unselected types
		foreach ( $current_types as $type ) {
			if ( ! in_array( $type, $selected_types ) ) {
				bp_xprofile_delete_meta( $object_id, $meta_type, 'member-type', $type, false );
			}
		}

		// Add new selected types
		foreach ( $selected_types as $type ) {
			if ( ! in_array( $type, $current_types ) ) {
				bp_xprofile_add_meta( $object_id, $meta_type, 'member-type', $type, false );
			}
		}

		return true;
	}

	/** Metabox ***************************************************************/

	/**
	 * Output the metabox for field assigned member types
	 *
	 * Since BP 2.1.0.
	 *
	 * @since 1.0.0
	 *
	 * @param BP_XProfile_Field $field Current XProfile field
	 */
	public function field_display_member_type_metabox( $field ) {

		// The primary field is for all, so bail
		if ( 1 === (int) $field->id )
			return;

		// Bail when no member types are registered
		if ( ! $member_types = bp_get_member_types( array(), 'objects' ) )
			return;

		// Get the field's member types
		$obj_member_types = ! empty( $field->id ) ? $this->get_xprofile_member_types( $field->id, 'field' ) : array();

		?>

		<div id="for_member_types" class="postbox">
			<h3><?php _e( 'Member Types', 'bp-xprofile-field-for-member-types' ); ?></h3>
			<div class="inside">
				<p class="description"><?php _e( 'To make this field exclusively available to users, select theirs from the following member types.', 'bp-xprofile-field-for-member-types' ); ?></p>

				<ul>
					<li>
						<label>
							<input name="member-types[]" type="checkbox" value="none" <?php checked( in_array( 'none', $obj_member_types ) ); ?>/>
							<em><?php _e( 'No member type', 'bp-xprofile-field-for-member-types' ); ?></em>
						</label>
					</li>

					<?php foreach ( $member_types as $member_type ) : ?>
					<li>
						<label>
							<input name="member-types[]" type="checkbox" value="<?php echo $member_type->name; ?>" <?php checked( in_array( $member_type->name, $obj_member_types ) ); ?>/>
							<?php echo $member_type->labels['singular_name']; ?>
						</label>
					</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<?php wp_nonce_field( 'member-types', '_wpnonce_for_member_types' ); ?>
		</div>

		<?php
	}

	/**
	 * Save the metabox for field assigned member types
	 *
	 * @since 1.0.0
	 *
	 * @uses BP_Xprofile_For_Member_Types::update_xprofile_member_types()
	 *
	 * @param BP_XProfile_Field $field Saved XProfile field
	 */
	public function field_save_member_type_metabox( $field ) {

		// Bail when nonce does not verify
		if ( ! isset( $_REQUEST['_wpnonce_for_member_types'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce_for_member_types'], 'member-types' ) )
			return;

		// Get posted values
		$member_types = isset( $_REQUEST['member-types'] ) ? (array) $_REQUEST['member-types'] : array();

		// Update changes
		$this->update_xprofile_member_types( $field->id, 'field', $member_types );
	}

	/** Admin: Profile Fields *************************************************/

	/**
	 * Display the selected member types per field on the Profile Fields screen
	 *
	 * @since 1.0.0
	 *
	 * @uses BP_XProfile_Field_For_Member_Types::get_xprofile_member_types()
	 * @uses bp_get_meber_types()
	 * @param BP_XProfile_Field $field Field object
	 */
	public function admin_field_legend( $field ) {

		// Bail when the field has no member types
		if ( ! $member_types = $this->get_xprofile_member_types( $field->id, 'field' ) )
			return;

		// Get selected type labels
		$types = bp_get_member_types( array(), 'objects' );
		$types = array_intersect_key( $types, array_flip( $member_types ) );
		$types = wp_list_pluck( $types, 'labels' );
		$types = wp_list_pluck( $types, 'singular_name' );

		if ( in_array( 'none', $member_types ) ) {
			/* translators: 'No member type' selection */
			$types = array_merge( array( __( 'None', 'bp-xprofile-field-for-member-types' ) ), $types );
		}

		// Construct legend
		$legend = sprintf( __( 'Member Types: %s', 'bp-xprofile-field-for-member-types' ), implode( ', ', $types ) );

		// Output legend <span>
		echo '<span class="member-types">(' . $legend . ')</span>';
	}
}

/**
 * Initiate plugin class and return singleton
 *
 * @since 1.0.0
 *
 * @return BP_XProfile_Field_For_Member_Types
 */
function bp_xprofile_field_for_member_types() {
	return BP_XProfile_Field_For_Member_Types::instance();
}

// Fire it up!
add_action( 'bp_loaded', 'bp_xprofile_field_for_member_types' );

endif; // class_exists
