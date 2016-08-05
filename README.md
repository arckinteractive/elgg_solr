# elgg_solr

Speed up site search by using a dedicated Solr search index

 * Search Entities
 * Search tags (exact match)
 * Search File contents

This plugin follows the structure of the default Elgg search plugin, can be extended using the same search plugin hooks.


## Dependencies

 * This plugin depends on the default elgg search plugin (bundled with Elgg)
 * This plugin depends on the vroom plugin - https://github.com/jumbojett/vroom


## Installation

 1. Download the plugin and upload it to `/mod/elgg_solr`,
    or install with composer `composer require arckinteractive/elgg_solr:~2.0`

 2. In Admin > Plugins, reorder the `elgg_solr` plugin to be positioned under `search` plugin, and enable it

 3. Create a new Solr instance and configure it:
	* Make sure that `/<instance>/conf/solrconfig.xml` is set to use classic index schema: `<schemaFactory class="ClassicIndexSchemaFactory"></schemaFactory>`
	* Copy contents of `schema.xml` (or `schema.solr5.xml` for Solr 5+) included in the root of the plugin to `/<instance>/conf/schema.xml`

 4. Update `elgg_solr` plugin settings to point to the new Solr instance

 5. Trigger a reindex from the plugin setting page

 6. Ensure that daily cron is configured and active


## Plugin hooks

**search, <search_type>**
**search, <entity_type>**
**search, <entity_type>:<entity_subtype>**

These hooks can be used to modify search criteria


**elgg_solr:index, <entity_type>**
**elgg_solr:index, <entity_type>:<entity_subtype>**
**elgg_solr:index, annotation**

These hooks can be used to customize indexed fields


**elgg_solr:can_index, annotation**

Allows to add annotations to index by name

## Indexing

### Tuning indexed values

Indexed values can be customized using `'elgg_solr:index',<entity_type>'` hook:

```php
elgg_register_plugin_hook_handler('elgg_solr:index', 'object', function($hook, $type, $return, $params) {

	$entity = elgg_extract('entity', $params);
	if (!$entity instanceof Event) {
		return;
	}

	$return->custom_field_s = $entity->custom_field;
	$return->start_time_i = $entity->start_time;
	return $return;
});
```

### Indexing file contents

For file objects that are not of subtype `file`, use `elgg_solr_register_solr_entity_type()`:

```php
// contents of file objects can be indexed with the default callback
elgg_solr_register_solr_entity_type('object', 'custom_file', 'elgg_solr_add_update_file');
```

### Adding annotations to index

To add an annotation to the index, add annotation name to the return of the `'elgg_solr:can_index','annotation'`:

```php
elgg_register_plugin_hook_handler('elgg_solr:can_index', 'annotation', function($hook, $type, $return) {
	$return[] = 'revision';
	return $return;
});
```

## Indexed values

### Entity

 * `id` - guid
 * `type` - entity type
 * `subtype` - entity subtype
 * `owner_guid` - guid of the owner
 * `container_guid` - guid of the container
 * `access_id` - access level
 * `title` - title
 * `name` - name
 * `description` - description
 * `time_created` - timestamp of the creation
 * `time_updated_i` - timestamp of the last update
 * `enabled` - is entity enabled
 * `tag_<tag_name>_ss` - tags for registered tag metadata names
 * `has_pic_b` - flag indicating that entity has an uploaded icon

### User

In addition to Entity fields:

 * `username` - username
 * `profile_<field_name>_s` - profile fields with a single value
 * `profile_<field_name>_ss` - profile fields with multiple values (tags)
 * `groups_is` - guids of groups a user is member of
 * `groups_count_i` - total number of groups a user is a member of
 * `friends_is` - guids of user's friends
 * `friends_count_i` - total number of friends
 * `last_login_i` - timestamp of the last login
 * `last_action_i` - timestamp of the last activity
 * `has_pic_b` - flag indicating that user has an avatar

### Group

In addition to Entity fields:

 * `group_<field_name>_s` - profile fields with a single value
 * `group_<field_name>_ss` - profile fields with multiple values (tags)
 * `members_is` - guids of group members
 * `members_count_i` - total number of group members

### File

To index file contents, enable extraction (`extract_handler`) in plugin settings.

In addition to Entity fields:

 * `simpletype_s` - simple type (e.g. `image`, `document` etc)
 * `mimetype_s` - detected mime type (e.g. `application/json`)
 * `originalfilename_s` - original name of the uploaded file
 * `filesize_i` - file size in bytes
>>>>>>> cs
