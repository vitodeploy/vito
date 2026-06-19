# Workflows & Automations

## Introduction

Workflows are a powerful way to automate repetitive tasks and streamline your processes. With Vito's workflow system, you can create custom workflows that trigger actions for the designed workflow.

You can automate almost anything with Workflows. For example, You can Create a server on a cloud provider, and then install services, configure it for your website deployment, and then deploy your website.

## Actions

Vito provides a variety of built-in actions that you can use to create your workflows. Some of the most common actions include:

- Create Server: Create a new server on a cloud provider.
- Install Service: Install a service on a server.
- Create Site: Create a new site on a server.
- Create Database and Database User: Create a new database and database user on a server.
- Deploy Website: Run the deployment script to deploy your website.
- Run Command: Run a custom command on a server.
- Notify: Send a notification to a specified channel (e.g., Slack, Email).
- HTTP Call: Make an HTTP request to a specified URL.

:::tip
You can write your own custom action using Vito's plugin system. See the [Plugins documentation](./plugins) for more information.
:::

## Action Inputs and Outputs

Actions can be chained together and feed each other with their outputs.

For example, the "Create Server" action outputs the server ID, which can be used as an input for the "Install Service" action to specify which server to install the service on.

You will see the incoming inputs from other actions' output when you chain them. For example, if you connect Create Server to Install Service, if you then open the Install Service action, you will see the server ID is being shown as a possible input.

## Flow

Every action in a workflow can have 2 outgoing links. The first outgoing link is for the success flow, and the second outgoing link is for the failure flow.

You can link the success flow to another action to continue the workflow if the action is successful. You can link the failure flow to another action to handle the error if the action fails. For example, you can notify the team if an action fails or even Run a command to rollback the previous actions.

Every workflow must have a starting action. You can mark any action as the starting action by clicking on the action and then clicking on the Flag icon.

:::warning
Workflows do not support loops. If any link creates a loop, you won't be able to save the workflow.
:::

## Running Workflows

To run a workflow, you can navigate to the workflows page and click on the Run Workflow dropdown button.

Vito will ask to confirm or change the starting action's inputs. After confirming, the workflow will start running.

Workflow logs will be accessible on the details page of the workflow run. You can enable or disable the verbose logging from the workflow run modal.
