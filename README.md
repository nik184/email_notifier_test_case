# PHP EMAIL NOTIFICATION SERVICE

To create a base with test data, run the following command

```bash
$ php set_up.php
```

To start sending notifications run

```bash
$ php email_sender.php
```

This command can notify 500,000 emails in one execution and will take about an hour to complete.
So to not miss any expiring subscription, you can run the script once every hour using a line like this in your crontab file
```
5 1-22 * * * php /path/to/this/directory/email_sender.php
```
This way you can cover up to 11,000,000 emails per day

N.B. To execute these scripts you should have installed php with sqlite3 extension
