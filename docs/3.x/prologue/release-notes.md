# Release Notes

## Migrating to Inertia and React

In this release, we have migrated the dashboard to use Inertia.js and React. This change allows for a more dynamic and
responsive user interface, improving the overall user experience. The new setup also enables easier development of
future features and enhancements.

## New dashboard UI

The dashboard has been completely redesigned to provide a more intuitive and user-friendly experience. The new UI
features a cleaner layout, improved navigation, and enhanced visual elements to help users manage their servers
more effectively.

## Plugins

Plugins are now supported in Vito, allowing users to extend the functionality of VitoDeploy. Users can now develop and
install official and community supported plugins to add new features or modify existing ones. This opens up a wide range
of possibilities for customization and enhancement of the VitoDeploy experience.

## Background jobs performance improvements

We've migrated the queue system from Laravel's default database queue to Horizon with Redis. Vito's queues were
previously running with only one worker, which caused delays in processing background jobs. Now, with the new setup,
VitoDeploy can handle multiple background jobs simultaneously, significantly improving the performance.

## Export and Import

The export and import functionality has been added to VitoDeploy, allowing users to easily export and backup their
server configurations and import them when needed. This feature is particularly useful for users who want to
migrate their Vito instance from machine to machine or for those who want to keep a backup of their server settings.

## Redis for Caching, Sessions, and Queues

Vito version 3.x uses Redis for caching, sessions, and queues. This change improves the performance and solves the
Database lock issues with SQLite. Redis is a powerful in-memory data structure store that provides high performance and
scalability for these tasks.