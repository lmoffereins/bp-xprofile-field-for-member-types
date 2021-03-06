=== BP XProfile Field For Member Types ===
Contributors: Offereins
Tags: buddypress, xprofile, member type, field, member, user, type
Requires at least: 4.0, BP 2.2
Tested up to: 4.2.2, BP 2.3.4
Stable tag: 1.1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Proof of concept for the implementation of member-type specific profile fields in BuddyPress. The idea behind the plugin is transformed and included in BuddyPress 2.4, making this plugin obsolete. See ticket [5192](https://buddypress.trac.wordpress.org/ticket/5192) and changeset [1022](https://buddypress.trac.wordpress.org/changeset/10022) for the implementation details.

== Installation ==

If you download BP XProfile Field For Member Types manually, make sure it is uploaded to "/wp-content/plugins/bp-xprofile-field-for-member-types/". Activate BP XProfile Field For Member Types in the "Plugins" admin panel using the "Activate" link. 

== Changelog ==

= 1.1.2 =
* Added display a notice when no member type is selected for the field

= 1.1.1 =
* Fixed checking version BP 2.4+
* Fixed displaying member types field label

= 1.1.0 =
* Bring the plugin logic in sync with BP 2.4, which will make this plugin obsolete. 

= 1.0.1 =
* Fixed setting member types for new a new field. Created a temporary workaround for #BP6545. See #2

= 1.0.0 =
* Initial release
