# Notification Channels

## Introduction

Vito provides integration with some most used platforms to send notifications to you. You can add multiple channels to
your account or project.

## Supported Channels

- Slack
- Discord
- Email
- Telegram

### Slack

To add a Slack channel you will need to enter Slack's webhook URL which you can get it by creating a Slack app.

### Discord

To add a Discord channel you will need to enter Discord's webhook URL.

### Email

To add an Email channel you just need to enter the email address that you want to send the notifications to it.

:::warning
You need to [configure](../getting-started/configuration.md#email) Vito to use an email driver first.
:::

### Telegram

To add a Telegram channel you will first need to create a bot with `@BotFather` and then start the bot or add it to a
channel and then get the `chat_id` of that chat/channel and enter the `bot_token` and the `chat_id` to add it to your
Vito instance.

## Scope

notification channels can be created under a specific project or globally.

If you create a notification channel under a project, it will only be available for that project.

If you create a notification channel globally, it will be available for all projects.

The reason of this feature is when you add a new user to VitoDeploy, you can control which notification channel they can
access.
