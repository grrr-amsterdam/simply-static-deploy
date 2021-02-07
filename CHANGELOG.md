# Changelog

This changelog only lists notable changes. Check individual releases (tags) and their commits to see unlisted changes.

## v1.0.0 (2020-04-16)

The plugin version was bumped from `v0.2.x` to `v1.x.x` to improve updating via Composer, due to restrictions in the [caret version range](https://getcomposer.org/doc/articles/versions.md#caret-version-range-). From the docs:

> For pre-1.0 versions it also acts with safety in mind and treats `^0.3` as `>=0.3.0 <0.4.0`.

## v2.0.0 (2020-10-19)

From version v2.0.0 each deploy task will be done in the background.
This means your browser window won't have to stay open while deploying. All the tasks will make separate requests, so the idle timeout limit of the server won't be reached.
It is now also possible to only deploy a single post. Because of these changes we removed the ability to trigger each task individually.

## v2.1.0 (2021-02-05)

With the last major release we removed the `simply_static_deploy_modify_generated_files` action hook,
but we never really wanted it to leave. But it got new complications because of the single deploy
feature that got a high priority.

Let's make it work!

[X] Let generated files by modified before deploy
[X] Single Deploy always with assets
[X] Single Deploy button should be hidden until js is loaded
[ ] Show progress
[ ] Create the possibility to invalidate cloudfront anytime
[ ] How can we make sure people use the right deploy button

### Updating from v1.\*

Since major architectual changed have been made, the scheduled event action hook is changed.
This means you manually need to remove any existing scheduled actions. You can do this with the [WP Crontrol](https://nl.wordpress.org/plugins/wp-crontrol/) plugin.

Removed actions and filters.

`simply_static_deploy_php_execution_time` filter is removed, because it should not be needed anymore.

`simply_static_deploy_modify_generated_files` action is removed, because this could have complications when deploying a single post
