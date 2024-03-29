# Changelog

This changelog only lists notable changes. Check individual releases (tags) and their commits to see unlisted changes.

## v2.3.0

- Add simply_static_deploy_recursive_excludable filter
- Update readme.md

## v2.2.2

- Remove Cloudfront invalidation for deleted objects
- Do not hide generate button from simply static plugin
- Add safety precaution for deleting object feature

## v2.2.1

- PHP 8 compatible

## v2.2.0

- Implemented single recursive deploy functionality, see the README for details.
- Added a delete hook: When you delete a page, this page will be automatically deleted from S3 as well.

## v2.1.3

- Added a filter: `simply_static_deploy_single_additional_files`
- Added an action hook: `simply_static_deploy_complete`

Check the README for more information about these new hooks.

## v2.1.2

We do not use timezone settings to schedule deploys anymore, just UCT time.

## v2.1.1

Updated the README.

## v2.1.0 (2021-02-05)

-   Return of the `simply_static_deploy_modify_generated_files` action hook.
-   Single static deploy will always deploy files from 'Additional Files' Simply Static setting.
-   Only show single static deploy when jQuery is loaded.
-   Add possibility to trigger CloudFront invalidation seperately
-   Hide 'Generate' action from Simply Static plugin

## v2.0.0 (2020-10-19)

From version v2.0.0 each deploy task will be done in the background.
This means your browser window won't have to stay open while deploying. All the tasks will make separate requests, so the idle timeout limit of the server won't be reached.
It is now also possible to only deploy a single post. Because of these changes we removed the ability to trigger each task individually.

### Updating from v1.\*

Since major architectual changed have been made, the scheduled event action hook is changed.
This means you manually need to remove any existing scheduled actions. You can do this with the [WP Crontrol](https://nl.wordpress.org/plugins/wp-crontrol/) plugin.

Removed actions and filters.

`simply_static_deploy_php_execution_time` filter is removed, because it should not be needed anymore.

## v1.0.0 (2020-04-16)

The plugin version was bumped from `v0.2.x` to `v1.x.x` to improve updating via Composer, due to restrictions in the [caret version range](https://getcomposer.org/doc/articles/versions.md#caret-version-range-). From the docs:

> For pre-1.0 versions it also acts with safety in mind and treats `^0.3` as `>=0.3.0 <0.4.0`.
