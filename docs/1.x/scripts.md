# Scripts

Scripts allows you to create bash scripts and execute them on your servers any time you want.

## Create Script

Navigate to `/scripts` via the sidebar navigation and there you can create a new script.

To create a script you need to name it and provide the content in bash script format.

### Define Variables

VitoDeploy allows you to define variables in your script.

To define a variable you can just wrap them into `${YOUR_VARIABLE_NAME}` inside the content of your script.

An example script with variables:

```bash
echo "Hello ${NAME}"
```

## Execute Script

To execute a script, Go to the scripts list and hit the `⚡` icon and select the target server that you want to execute
the script on it.

You can also select the system user that you want to execute the script with.

:::warning
Be careful when executing scripts with root user, as it can cause damage to your server.
:::

## Script Logs

After the script execution, you can see the logs of the script execution you can go to the Executions list of the script
and see the output of your script execution.