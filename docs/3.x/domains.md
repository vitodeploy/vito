# Domains

## Introduction

One of the most frequent actions you need to do when deploying a new website on a new server is to point your domain to that server. For that you need to login to your domain registrar or DNS provider and change the NS records of that domain to point to your server's IP address.

Vito now supports managing your domains' NS records directly from VitoDeploy. You can add your [DNS provider](./settings/dns-providers.md) to VitoDeploy and then add your domain from the selected DNS provider. Vito will fetch all existing NS records and you can manage them directly from VitoDeploy.

## Add domain

To add a new domain, go to the `/domains` page and click on the `Add Domain` button. Then select the DNS provider you've connected and enter your domain name.

## Scope

Domains are project-scoped. This means that when you add a new domain, it will be only available for that project.

When you add a new user to VitoDeploy and assign them to a project, they will be able to see and manage the domains of that project.

## Manage Records

Once you've added a domain, you can manage its NS records directly from VitoDeploy. You can add, edit, and delete NS records as needed.

When you add or edit a record, Vito will automatically update the record on your DNS provider.

When you delete a record, Vito will also delete the record from your DNS provider.

## Remove Domain

You have the option to remove a domain from VitoDeploy. Removing a domain will remove it from VitoDeploy, but it will not delete the domain or its records from your DNS provider.
