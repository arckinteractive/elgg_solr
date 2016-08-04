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
**elgg_solr:index, annotation**

These hooks can be used to customize indexed fields