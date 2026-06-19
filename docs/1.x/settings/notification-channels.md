# Notification Channels

We've integrated the following channels with Vito so you can connect and get notified about your Servers' statuses and the result of each action that you're doing with Vito.

- Slack
- Discord
- Email
- Telegram

## Slack

To add a Slack channel you will need to enter Slack's webhook URL which you can get it by creating a Slack app.

## Discord

To add a Discord channel you will need to enter Discord's webhook URL.

## Email

To add an Email channel you just need to enter the email address that you want to send the notifications to it.

But before that, You need to configure Vito to use an email driver first.

To configure it, Enter your Email Server's info into `.env` file in the Vito instance and under `/home/vito/your-domain/.env` path.

You will need to configure the following env variables according to the driver you choose.

For example, For an SMTP server do the following:

```
MAIL_MAILER=smtp
MAIL_HOST=your-mail-server-host
MAIL_PORT=your-mail-server-port
MAIL_USERNAME=your-mail-server-username
MAIL_PASSWORD=your-mail-server-password
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=sender-email@yourdomain
MAIL_FROM_NAME="${APP_NAME}"
```

To add more drivers, Please follow [Laravel's Official Documentation](https://laravel.com/docs/10.x/mail#configuration)

## Telegram

To add a Telegram channel you will first need to create a bot with `@BotFather` and then start the bot or add it to a channel and then get the `chat_id` of that chat/channel and enter the `bot_token` and the `chat_id` to add it to your Vito instance.