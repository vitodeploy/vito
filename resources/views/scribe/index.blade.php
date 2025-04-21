<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible" />
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
        <title>API Documentation</title>

        <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet" />

        <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.style.css") }}" media="screen" />
        <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.print.css") }}" media="print" />

        <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.10/lodash.min.js"></script>

        <link rel="stylesheet" href="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/styles/obsidian.min.css" />
        <script src="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/highlight.min.js"></script>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/jets/0.14.1/jets.min.js"></script>

        <style id="language-style">
            /* starts out as display none and is replaced with js later  */
            body .content .bash-example code {
                display: none;
            }
            body .content .javascript-example code {
                display: none;
            }
        </style>

        <script>
            var tryItOutBaseUrl = 'https://vito.test';
            var useCsrf = Boolean();
            var csrfUrl = '/sanctum/csrf-cookie';
        </script>
        <script src="{{ asset("/vendor/scribe/js/tryitout-5.2.0.js") }}"></script>

        <script src="{{ asset("/vendor/scribe/js/theme-default-5.2.0.js") }}"></script>
    </head>

    <body data-languages='["bash","javascript"]'>
        <a href="#" id="nav-button">
            <span>
                MENU
                <img src="{{ asset("/vendor/scribe/images/navbar.png") }}" alt="navbar-image" />
            </span>
        </a>
        <div class="tocify-wrapper">
            <div class="lang-selector">
                <button type="button" class="lang-button" data-language-name="bash">bash</button>
                <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
            </div>

            <div class="search">
                <input type="text" class="search" id="input-search" placeholder="Search" />
            </div>

            <div id="toc">
                <ul id="tocify-header-introduction" class="tocify-header">
                    <li class="tocify-item level-1" data-unique="introduction">
                        <a href="#introduction">Introduction</a>
                    </li>
                </ul>
                <ul id="tocify-header-authenticating-requests" class="tocify-header">
                    <li class="tocify-item level-1" data-unique="authenticating-requests">
                        <a href="#authenticating-requests">Authenticating requests</a>
                    </li>
                </ul>
                <ul id="tocify-header-cron-jobs" class="tocify-header">
                    <li class="tocify-item level-1" data-unique="cron-jobs">
                        <a href="#cron-jobs">cron-jobs</a>
                    </li>
                    <ul id="tocify-subheader-cron-jobs" class="tocify-subheader">
                        <li
                            class="tocify-item level-2"
                            data-unique="cron-jobs-GETapi-projects--project_id--servers--server_id--cron-jobs"
                        >
                            <a href="#cron-jobs-GETapi-projects--project_id--servers--server_id--cron-jobs">list</a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="cron-jobs-POSTapi-projects--project_id--servers--server_id--cron-jobs"
                        >
                            <a href="#cron-jobs-POSTapi-projects--project_id--servers--server_id--cron-jobs">create</a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="cron-jobs-GETapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-"
                        >
                            <a
                                href="#cron-jobs-GETapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-"
                            >
                                show
                            </a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="cron-jobs-DELETEapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-"
                        >
                            <a
                                href="#cron-jobs-DELETEapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-"
                            >
                                delete
                            </a>
                        </li>
                    </ul>
                </ul>
                <ul id="tocify-header-database-users" class="tocify-header">
                    <li class="tocify-item level-1" data-unique="database-users">
                        <a href="#database-users">database-users</a>
                    </li>
                    <ul id="tocify-subheader-database-users" class="tocify-subheader">
                        <li
                            class="tocify-item level-2"
                            data-unique="database-users-GETapi-projects--project_id--servers--server_id--database-users"
                        >
                            <a href="#database-users-GETapi-projects--project_id--servers--server_id--database-users">
                                list
                            </a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="database-users-POSTapi-projects--project_id--servers--server_id--database-users"
                        >
                            <a href="#database-users-POSTapi-projects--project_id--servers--server_id--database-users">
                                create
                            </a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="database-users-GETapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
                        >
                            <a
                                href="#database-users-GETapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
                            >
                                show
                            </a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="database-users-POSTapi-projects--project_id--servers--server_id--database-users--databaseUser_id--link"
                        >
                            <a
                                href="#database-users-POSTapi-projects--project_id--servers--server_id--database-users--databaseUser_id--link"
                            >
                                link
                            </a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="database-users-DELETEapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
                        >
                            <a
                                href="#database-users-DELETEapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
                            >
                                delete
                            </a>
                        </li>
                    </ul>
                </ul>
                <ul id="tocify-header-databases" class="tocify-header">
                    <li class="tocify-item level-1" data-unique="databases">
                        <a href="#databases">databases</a>
                    </li>
                    <ul id="tocify-subheader-databases" class="tocify-subheader">
                        <li
                            class="tocify-item level-2"
                            data-unique="databases-GETapi-projects--project_id--servers--server_id--databases"
                        >
                            <a href="#databases-GETapi-projects--project_id--servers--server_id--databases">list</a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="databases-POSTapi-projects--project_id--servers--server_id--databases"
                        >
                            <a href="#databases-POSTapi-projects--project_id--servers--server_id--databases">create</a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="databases-GETapi-projects--project_id--servers--server_id--databases--id-"
                        >
                            <a href="#databases-GETapi-projects--project_id--servers--server_id--databases--id-">
                                show
                            </a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="databases-DELETEapi-projects--project_id--servers--server_id--databases--database_id-"
                        >
                            <a
                                href="#databases-DELETEapi-projects--project_id--servers--server_id--databases--database_id-"
                            >
                                delete
                            </a>
                        </li>
                    </ul>
                </ul>
                <ul id="tocify-header-firewall-rules" class="tocify-header">
                    <li class="tocify-item level-1" data-unique="firewall-rules">
                        <a href="#firewall-rules">firewall-rules</a>
                    </li>
                    <ul id="tocify-subheader-firewall-rules" class="tocify-subheader">
                        <li
                            class="tocify-item level-2"
                            data-unique="firewall-rules-GETapi-projects--project_id--servers--server_id--firewall-rules"
                        >
                            <a href="#firewall-rules-GETapi-projects--project_id--servers--server_id--firewall-rules">
                                list
                            </a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="firewall-rules-POSTapi-projects--project_id--servers--server_id--firewall-rules"
                        >
                            <a href="#firewall-rules-POSTapi-projects--project_id--servers--server_id--firewall-rules">
                                create
                            </a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="firewall-rules-PUTapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                        >
                            <a
                                href="#firewall-rules-PUTapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                            >
                                edit
                            </a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="firewall-rules-GETapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                        >
                            <a
                                href="#firewall-rules-GETapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                            >
                                show
                            </a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="firewall-rules-DELETEapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                        >
                            <a
                                href="#firewall-rules-DELETEapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                            >
                                delete
                            </a>
                        </li>
                    </ul>
                </ul>
                <ul id="tocify-header-general" class="tocify-header">
                    <li class="tocify-item level-1" data-unique="general">
                        <a href="#general">general</a>
                    </li>
                    <ul id="tocify-subheader-general" class="tocify-subheader">
                        <li class="tocify-item level-2" data-unique="general-GETapi-health">
                            <a href="#general-GETapi-health">health-check</a>
                        </li>
                    </ul>
                </ul>
                <ul id="tocify-header-projects" class="tocify-header">
                    <li class="tocify-item level-1" data-unique="projects">
                        <a href="#projects">projects</a>
                    </li>
                    <ul id="tocify-subheader-projects" class="tocify-subheader">
                        <li class="tocify-item level-2" data-unique="projects-GETapi-projects">
                            <a href="#projects-GETapi-projects">list</a>
                        </li>
                        <li class="tocify-item level-2" data-unique="projects-POSTapi-projects">
                            <a href="#projects-POSTapi-projects">create</a>
                        </li>
                        <li class="tocify-item level-2" data-unique="projects-GETapi-projects--id-">
                            <a href="#projects-GETapi-projects--id-">show</a>
                        </li>
                        <li class="tocify-item level-2" data-unique="projects-PUTapi-projects--id-">
                            <a href="#projects-PUTapi-projects--id-">update</a>
                        </li>
                        <li class="tocify-item level-2" data-unique="projects-DELETEapi-projects--project_id-">
                            <a href="#projects-DELETEapi-projects--project_id-">delete</a>
                        </li>
                    </ul>
                </ul>
                <ul id="tocify-header-redirects" class="tocify-header">
                    <li class="tocify-item level-1" data-unique="redirects">
                        <a href="#redirects">redirects</a>
                    </li>
                    <ul id="tocify-subheader-redirects" class="tocify-subheader">
                        <li
                            class="tocify-item level-2"
                            data-unique="redirects-GETapi-projects--project_id--servers--server_id--sites--site_id--redirects"
                        >
                            <a
                                href="#redirects-GETapi-projects--project_id--servers--server_id--sites--site_id--redirects"
                            >
                                index
                            </a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="redirects-POSTapi-projects--project_id--servers--server_id--sites--site_id--redirects"
                        >
                            <a
                                href="#redirects-POSTapi-projects--project_id--servers--server_id--sites--site_id--redirects"
                            >
                                create
                            </a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="redirects-DELETEapi-projects--project_id--servers--server_id--sites--site_id--redirects--redirect_id-"
                        >
                            <a
                                href="#redirects-DELETEapi-projects--project_id--servers--server_id--sites--site_id--redirects--redirect_id-"
                            >
                                delete
                            </a>
                        </li>
                    </ul>
                </ul>
                <ul id="tocify-header-server-providers" class="tocify-header">
                    <li class="tocify-item level-1" data-unique="server-providers">
                        <a href="#server-providers">server-providers</a>
                    </li>
                    <ul id="tocify-subheader-server-providers" class="tocify-subheader">
                        <li
                            class="tocify-item level-2"
                            data-unique="server-providers-GETapi-projects--project_id--server-providers"
                        >
                            <a href="#server-providers-GETapi-projects--project_id--server-providers">list</a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="server-providers-POSTapi-projects--project_id--server-providers"
                        >
                            <a href="#server-providers-POSTapi-projects--project_id--server-providers">create</a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="server-providers-GETapi-projects--project_id--server-providers--serverProvider_id-"
                        >
                            <a
                                href="#server-providers-GETapi-projects--project_id--server-providers--serverProvider_id-"
                            >
                                show
                            </a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="server-providers-PUTapi-projects--project_id--server-providers--serverProvider_id-"
                        >
                            <a
                                href="#server-providers-PUTapi-projects--project_id--server-providers--serverProvider_id-"
                            >
                                update
                            </a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="server-providers-DELETEapi-projects--project_id--server-providers--serverProvider_id-"
                        >
                            <a
                                href="#server-providers-DELETEapi-projects--project_id--server-providers--serverProvider_id-"
                            >
                                delete
                            </a>
                        </li>
                    </ul>
                </ul>
                <ul id="tocify-header-servers" class="tocify-header">
                    <li class="tocify-item level-1" data-unique="servers">
                        <a href="#servers">servers</a>
                    </li>
                    <ul id="tocify-subheader-servers" class="tocify-subheader">
                        <li class="tocify-item level-2" data-unique="servers-GETapi-projects--project_id--servers">
                            <a href="#servers-GETapi-projects--project_id--servers">list</a>
                        </li>
                        <li class="tocify-item level-2" data-unique="servers-POSTapi-projects--project_id--servers">
                            <a href="#servers-POSTapi-projects--project_id--servers">create</a>
                        </li>
                        <li class="tocify-item level-2" data-unique="servers-GETapi-projects--project_id--servers--id-">
                            <a href="#servers-GETapi-projects--project_id--servers--id-">show</a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="servers-POSTapi-projects--project_id--servers--server_id--reboot"
                        >
                            <a href="#servers-POSTapi-projects--project_id--servers--server_id--reboot">reboot</a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="servers-POSTapi-projects--project_id--servers--server_id--upgrade"
                        >
                            <a href="#servers-POSTapi-projects--project_id--servers--server_id--upgrade">upgrade</a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="servers-DELETEapi-projects--project_id--servers--server_id-"
                        >
                            <a href="#servers-DELETEapi-projects--project_id--servers--server_id-">delete</a>
                        </li>
                    </ul>
                </ul>
                <ul id="tocify-header-services" class="tocify-header">
                    <li class="tocify-item level-1" data-unique="services">
                        <a href="#services">services</a>
                    </li>
                    <ul id="tocify-subheader-services" class="tocify-subheader">
                        <li
                            class="tocify-item level-2"
                            data-unique="services-GETapi-projects--project_id--servers--server_id--services"
                        >
                            <a href="#services-GETapi-projects--project_id--servers--server_id--services">list</a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="services-GETapi-projects--project_id--servers--server_id--services--id-"
                        >
                            <a href="#services-GETapi-projects--project_id--servers--server_id--services--id-">show</a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="services-POSTapi-projects--project_id--servers--server_id--services--service_id--start"
                        >
                            <a
                                href="#services-POSTapi-projects--project_id--servers--server_id--services--service_id--start"
                            >
                                start
                            </a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="services-POSTapi-projects--project_id--servers--server_id--services--service_id--stop"
                        >
                            <a
                                href="#services-POSTapi-projects--project_id--servers--server_id--services--service_id--stop"
                            >
                                stop
                            </a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="services-POSTapi-projects--project_id--servers--server_id--services--service_id--restart"
                        >
                            <a
                                href="#services-POSTapi-projects--project_id--servers--server_id--services--service_id--restart"
                            >
                                restart
                            </a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="services-POSTapi-projects--project_id--servers--server_id--services--service_id--enable"
                        >
                            <a
                                href="#services-POSTapi-projects--project_id--servers--server_id--services--service_id--enable"
                            >
                                enable
                            </a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="services-POSTapi-projects--project_id--servers--server_id--services--service_id--disable"
                        >
                            <a
                                href="#services-POSTapi-projects--project_id--servers--server_id--services--service_id--disable"
                            >
                                disable
                            </a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="services-DELETEapi-projects--project_id--servers--server_id--services--service_id-"
                        >
                            <a
                                href="#services-DELETEapi-projects--project_id--servers--server_id--services--service_id-"
                            >
                                delete
                            </a>
                        </li>
                    </ul>
                </ul>
                <ul id="tocify-header-sites" class="tocify-header">
                    <li class="tocify-item level-1" data-unique="sites">
                        <a href="#sites">sites</a>
                    </li>
                    <ul id="tocify-subheader-sites" class="tocify-subheader">
                        <li
                            class="tocify-item level-2"
                            data-unique="sites-GETapi-projects--project_id--servers--server_id--sites"
                        >
                            <a href="#sites-GETapi-projects--project_id--servers--server_id--sites">list</a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="sites-POSTapi-projects--project_id--servers--server_id--sites"
                        >
                            <a href="#sites-POSTapi-projects--project_id--servers--server_id--sites">create</a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="sites-GETapi-projects--project_id--servers--server_id--sites--id-"
                        >
                            <a href="#sites-GETapi-projects--project_id--servers--server_id--sites--id-">show</a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="sites-DELETEapi-projects--project_id--servers--server_id--sites--site_id-"
                        >
                            <a href="#sites-DELETEapi-projects--project_id--servers--server_id--sites--site_id-">
                                delete
                            </a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="sites-POSTapi-projects--project_id--servers--server_id--sites--site_id--load-balancer"
                        >
                            <a
                                href="#sites-POSTapi-projects--project_id--servers--server_id--sites--site_id--load-balancer"
                            >
                                load-balancer
                            </a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="sites-PUTapi-projects--project_id--servers--server_id--sites--site_id--aliases"
                        >
                            <a href="#sites-PUTapi-projects--project_id--servers--server_id--sites--site_id--aliases">
                                aliases
                            </a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="sites-POSTapi-projects--project_id--servers--server_id--sites--site_id--deploy"
                        >
                            <a href="#sites-POSTapi-projects--project_id--servers--server_id--sites--site_id--deploy">
                                deploy
                            </a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="sites-PUTapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
                        >
                            <a
                                href="#sites-PUTapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
                            >
                                deployment-script
                            </a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="sites-GETapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
                        >
                            <a
                                href="#sites-GETapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
                            >
                                deployment-script
                            </a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="sites-GETapi-projects--project_id--servers--server_id--sites--site_id--env"
                        >
                            <a href="#sites-GETapi-projects--project_id--servers--server_id--sites--site_id--env">
                                env
                            </a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="sites-PUTapi-projects--project_id--servers--server_id--sites--site_id--env"
                        >
                            <a href="#sites-PUTapi-projects--project_id--servers--server_id--sites--site_id--env">
                                env
                            </a>
                        </li>
                    </ul>
                </ul>
                <ul id="tocify-header-source-controls" class="tocify-header">
                    <li class="tocify-item level-1" data-unique="source-controls">
                        <a href="#source-controls">source-controls</a>
                    </li>
                    <ul id="tocify-subheader-source-controls" class="tocify-subheader">
                        <li
                            class="tocify-item level-2"
                            data-unique="source-controls-GETapi-projects--project_id--source-controls"
                        >
                            <a href="#source-controls-GETapi-projects--project_id--source-controls">list</a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="source-controls-POSTapi-projects--project_id--source-controls"
                        >
                            <a href="#source-controls-POSTapi-projects--project_id--source-controls">create</a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="source-controls-GETapi-projects--project_id--source-controls--sourceControl_id-"
                        >
                            <a href="#source-controls-GETapi-projects--project_id--source-controls--sourceControl_id-">
                                show
                            </a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="source-controls-PUTapi-projects--project_id--source-controls--sourceControl_id-"
                        >
                            <a href="#source-controls-PUTapi-projects--project_id--source-controls--sourceControl_id-">
                                update
                            </a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="source-controls-DELETEapi-projects--project_id--source-controls--sourceControl_id-"
                        >
                            <a
                                href="#source-controls-DELETEapi-projects--project_id--source-controls--sourceControl_id-"
                            >
                                delete
                            </a>
                        </li>
                    </ul>
                </ul>
                <ul id="tocify-header-ssh-keys" class="tocify-header">
                    <li class="tocify-item level-1" data-unique="ssh-keys">
                        <a href="#ssh-keys">ssh-keys</a>
                    </li>
                    <ul id="tocify-subheader-ssh-keys" class="tocify-subheader">
                        <li
                            class="tocify-item level-2"
                            data-unique="ssh-keys-GETapi-projects--project_id--servers--server_id--ssh-keys"
                        >
                            <a href="#ssh-keys-GETapi-projects--project_id--servers--server_id--ssh-keys">list</a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="ssh-keys-POSTapi-projects--project_id--servers--server_id--ssh-keys"
                        >
                            <a href="#ssh-keys-POSTapi-projects--project_id--servers--server_id--ssh-keys">create</a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="ssh-keys-DELETEapi-projects--project_id--servers--server_id--ssh-keys--sshKey_id-"
                        >
                            <a
                                href="#ssh-keys-DELETEapi-projects--project_id--servers--server_id--ssh-keys--sshKey_id-"
                            >
                                delete
                            </a>
                        </li>
                    </ul>
                </ul>
                <ul id="tocify-header-storage-providers" class="tocify-header">
                    <li class="tocify-item level-1" data-unique="storage-providers">
                        <a href="#storage-providers">storage-providers</a>
                    </li>
                    <ul id="tocify-subheader-storage-providers" class="tocify-subheader">
                        <li
                            class="tocify-item level-2"
                            data-unique="storage-providers-GETapi-projects--project_id--storage-providers"
                        >
                            <a href="#storage-providers-GETapi-projects--project_id--storage-providers">list</a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="storage-providers-POSTapi-projects--project_id--storage-providers"
                        >
                            <a href="#storage-providers-POSTapi-projects--project_id--storage-providers">create</a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="storage-providers-GETapi-projects--project_id--storage-providers--storageProvider_id-"
                        >
                            <a
                                href="#storage-providers-GETapi-projects--project_id--storage-providers--storageProvider_id-"
                            >
                                show
                            </a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="storage-providers-PUTapi-projects--project_id--storage-providers--storageProvider_id-"
                        >
                            <a
                                href="#storage-providers-PUTapi-projects--project_id--storage-providers--storageProvider_id-"
                            >
                                update
                            </a>
                        </li>
                        <li
                            class="tocify-item level-2"
                            data-unique="storage-providers-DELETEapi-projects--project_id--storage-providers--storageProvider_id-"
                        >
                            <a
                                href="#storage-providers-DELETEapi-projects--project_id--storage-providers--storageProvider_id-"
                            >
                                delete
                            </a>
                        </li>
                    </ul>
                </ul>
            </div>

            <ul class="toc-footer" id="toc-footer">
                <li style="padding-bottom: 5px">
                    <a href="{{ route("scribe.postman") }}">View Postman collection</a>
                </li>
                <li style="padding-bottom: 5px"><a href="{{ route("scribe.openapi") }}">View OpenAPI spec</a></li>
                <li><a href="http://github.com/knuckleswtf/scribe">Documentation powered by Scribe ✍</a></li>
            </ul>

            <ul class="toc-footer" id="last-updated">
                <li>Last updated: April 21, 2025</li>
            </ul>
        </div>

        <div class="page-wrapper">
            <div class="dark-box"></div>
            <div class="content">
                <h1 id="introduction">Introduction</h1>
                <p>VitoDeploy's API documentation.</p>
                <aside>
                    <strong>Base URL</strong>
                    :
                    <code>https://vito.test</code>
                </aside>
                <pre><code>This documentation aims to provide all the information you need to work with our API.

&lt;aside&gt;As you scroll, you'll see code examples for working with the API in different programming languages in the dark area to the right (or as part of the content on mobile).
You can switch the language used with the tabs at the top right (or from the nav menu at the top left on mobile).&lt;/aside&gt;</code></pre>

                <h1 id="authenticating-requests">Authenticating requests</h1>
                <p>This API is not authenticated.</p>

                <h1 id="cron-jobs">cron-jobs</h1>

                <h2 id="cron-jobs-GETapi-projects--project_id--servers--server_id--cron-jobs">list</h2>

                <p></p>

                <p>Get all cron jobs.</p>

                <span id="example-requests-GETapi-projects--project_id--servers--server_id--cron-jobs">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request GET \
    --get "https://vito.test/api/projects/1/servers/32/cron-jobs" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/cron-jobs"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-GETapi-projects--project_id--servers--server_id--cron-jobs">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: 5,
            &quot;server_id&quot;: 1,
            &quot;command&quot;: &quot;ls -la&quot;,
            &quot;user&quot;: &quot;root&quot;,
            &quot;frequency&quot;: &quot;* * * * *&quot;,
            &quot;status&quot;: &quot;ready&quot;,
            &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
        },
        {
            &quot;id&quot;: 6,
            &quot;server_id&quot;: 1,
            &quot;command&quot;: &quot;ls -la&quot;,
            &quot;user&quot;: &quot;root&quot;,
            &quot;frequency&quot;: &quot;* * * * *&quot;,
            &quot;status&quot;: &quot;ready&quot;,
            &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
        }
    ],
    &quot;links&quot;: {
        &quot;first&quot;: &quot;/?page=1&quot;,
        &quot;last&quot;: &quot;/?page=1&quot;,
        &quot;prev&quot;: null,
        &quot;next&quot;: null
    },
    &quot;meta&quot;: {
        &quot;current_page&quot;: 1,
        &quot;from&quot;: 1,
        &quot;last_page&quot;: 1,
        &quot;links&quot;: [
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;&amp;laquo; Previous&quot;,
                &quot;active&quot;: false
            },
            {
                &quot;url&quot;: &quot;/?page=1&quot;,
                &quot;label&quot;: &quot;1&quot;,
                &quot;active&quot;: true
            },
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;Next &amp;raquo;&quot;,
                &quot;active&quot;: false
            }
        ],
        &quot;path&quot;: &quot;/&quot;,
        &quot;per_page&quot;: 25,
        &quot;to&quot;: 2,
        &quot;total&quot;: 2
    }
}</code>
 </pre>
                </span>
                <span id="execution-results-GETapi-projects--project_id--servers--server_id--cron-jobs" hidden>
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-GETapi-projects--project_id--servers--server_id--cron-jobs"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-GETapi-projects--project_id--servers--server_id--cron-jobs"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-GETapi-projects--project_id--servers--server_id--cron-jobs" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-GETapi-projects--project_id--servers--server_id--cron-jobs">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-GETapi-projects--project_id--servers--server_id--cron-jobs"
                    data-method="GET"
                    data-path="api/projects/{project_id}/servers/{server_id}/cron-jobs"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('GETapi-projects--project_id--servers--server_id--cron-jobs', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-GETapi-projects--project_id--servers--server_id--cron-jobs"
                            onclick="tryItOut('GETapi-projects--project_id--servers--server_id--cron-jobs');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-GETapi-projects--project_id--servers--server_id--cron-jobs"
                            onclick="cancelTryOut('GETapi-projects--project_id--servers--server_id--cron-jobs');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-GETapi-projects--project_id--servers--server_id--cron-jobs"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-green">GET</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/cron-jobs</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--cron-jobs"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--cron-jobs"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--cron-jobs"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--cron-jobs"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                </form>

                <h2 id="cron-jobs-POSTapi-projects--project_id--servers--server_id--cron-jobs">create</h2>

                <p></p>

                <p>Create a new cron job.</p>

                <span id="example-requests-POSTapi-projects--project_id--servers--server_id--cron-jobs">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request POST \
    "https://vito.test/api/projects/1/servers/32/cron-jobs" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"command\": \"consequatur\",
    \"user\": \"vito\",
    \"frequency\": \"* * * * *\"
}"
</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/cron-jobs"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "command": "consequatur",
    "user": "vito",
    "frequency": "* * * * *"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-POSTapi-projects--project_id--servers--server_id--cron-jobs">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;id&quot;: 5,
    &quot;server_id&quot;: 1,
    &quot;command&quot;: &quot;ls -la&quot;,
    &quot;user&quot;: &quot;root&quot;,
    &quot;frequency&quot;: &quot;* * * * *&quot;,
    &quot;status&quot;: &quot;ready&quot;,
    &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
    &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
}</code>
 </pre>
                </span>
                <span id="execution-results-POSTapi-projects--project_id--servers--server_id--cron-jobs" hidden>
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-POSTapi-projects--project_id--servers--server_id--cron-jobs"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-POSTapi-projects--project_id--servers--server_id--cron-jobs"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-POSTapi-projects--project_id--servers--server_id--cron-jobs" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-POSTapi-projects--project_id--servers--server_id--cron-jobs">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-POSTapi-projects--project_id--servers--server_id--cron-jobs"
                    data-method="POST"
                    data-path="api/projects/{project_id}/servers/{server_id}/cron-jobs"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('POSTapi-projects--project_id--servers--server_id--cron-jobs', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-POSTapi-projects--project_id--servers--server_id--cron-jobs"
                            onclick="tryItOut('POSTapi-projects--project_id--servers--server_id--cron-jobs');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-POSTapi-projects--project_id--servers--server_id--cron-jobs"
                            onclick="cancelTryOut('POSTapi-projects--project_id--servers--server_id--cron-jobs');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-POSTapi-projects--project_id--servers--server_id--cron-jobs"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-black">POST</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/cron-jobs</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--cron-jobs"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--cron-jobs"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--cron-jobs"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--cron-jobs"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>command</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="command"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--cron-jobs"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>user</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="user"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--cron-jobs"
                            value="vito"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Example:
                            <code>vito</code>
                        </p>
                        Must be one of:
                        <ul style="list-style-type: square">
                            <li><code>root</code></li>
                            <li><code>vito</code></li>
                        </ul>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>frequency</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="frequency"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--cron-jobs"
                            value="* * * * *"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Frequency of the cron job. Example:
                            <code>* * * * *</code>
                        </p>
                    </div>
                </form>

                <h2 id="cron-jobs-GETapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-">show</h2>

                <p></p>

                <p>Get a cron job by ID.</p>

                <span id="example-requests-GETapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request GET \
    --get "https://vito.test/api/projects/1/servers/32/cron-jobs/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/cron-jobs/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-GETapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;id&quot;: 5,
    &quot;server_id&quot;: 1,
    &quot;command&quot;: &quot;ls -la&quot;,
    &quot;user&quot;: &quot;root&quot;,
    &quot;frequency&quot;: &quot;* * * * *&quot;,
    &quot;status&quot;: &quot;ready&quot;,
    &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
    &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
}</code>
 </pre>
                </span>
                <span
                    id="execution-results-GETapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-"
                    hidden
                >
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-GETapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-GETapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span
                    id="execution-error-GETapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-"
                    hidden
                >
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-GETapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-GETapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-"
                    data-method="GET"
                    data-path="api/projects/{project_id}/servers/{server_id}/cron-jobs/{cronJob_id}"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('GETapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-GETapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-"
                            onclick="tryItOut('GETapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-GETapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-"
                            onclick="cancelTryOut('GETapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-GETapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-green">GET</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/cron-jobs/{cronJob_id}</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>cronJob_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="cronJob_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-"
                            value="17"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the cronJob. Example:
                            <code>17</code>
                        </p>
                    </div>
                </form>

                <h2 id="cron-jobs-DELETEapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-">
                    delete
                </h2>

                <p></p>

                <p>Delete cron job.</p>

                <span id="example-requests-DELETEapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request DELETE \
    "https://vito.test/api/projects/1/servers/32/cron-jobs/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/cron-jobs/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-DELETEapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-">
                    <blockquote>
                        <p>Example response (204):</p>
                    </blockquote>
                    <pre>
<code>Empty response</code>
 </pre>
                </span>
                <span
                    id="execution-results-DELETEapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-"
                    hidden
                >
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-DELETEapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-DELETEapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span
                    id="execution-error-DELETEapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-"
                    hidden
                >
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-DELETEapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-DELETEapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-"
                    data-method="DELETE"
                    data-path="api/projects/{project_id}/servers/{server_id}/cron-jobs/{cronJob_id}"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('DELETEapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-DELETEapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-"
                            onclick="tryItOut('DELETEapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-DELETEapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-"
                            onclick="cancelTryOut('DELETEapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-DELETEapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-red">DELETE</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/cron-jobs/{cronJob_id}</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>cronJob_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="cronJob_id"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--cron-jobs--cronJob_id-"
                            value="17"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the cronJob. Example:
                            <code>17</code>
                        </p>
                    </div>
                </form>

                <h1 id="database-users">database-users</h1>

                <h2 id="database-users-GETapi-projects--project_id--servers--server_id--database-users">list</h2>

                <p></p>

                <p>Get all database users.</p>

                <span id="example-requests-GETapi-projects--project_id--servers--server_id--database-users">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request GET \
    --get "https://vito.test/api/projects/1/servers/32/database-users" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/database-users"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-GETapi-projects--project_id--servers--server_id--database-users">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: 19,
            &quot;server_id&quot;: 1,
            &quot;username&quot;: &quot;graciela37&quot;,
            &quot;databases&quot;: [],
            &quot;host&quot;: &quot;%&quot;,
            &quot;status&quot;: &quot;creating&quot;,
            &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
        },
        {
            &quot;id&quot;: 20,
            &quot;server_id&quot;: 1,
            &quot;username&quot;: &quot;vconn&quot;,
            &quot;databases&quot;: [],
            &quot;host&quot;: &quot;%&quot;,
            &quot;status&quot;: &quot;creating&quot;,
            &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
        }
    ],
    &quot;links&quot;: {
        &quot;first&quot;: &quot;/?page=1&quot;,
        &quot;last&quot;: &quot;/?page=1&quot;,
        &quot;prev&quot;: null,
        &quot;next&quot;: null
    },
    &quot;meta&quot;: {
        &quot;current_page&quot;: 1,
        &quot;from&quot;: 1,
        &quot;last_page&quot;: 1,
        &quot;links&quot;: [
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;&amp;laquo; Previous&quot;,
                &quot;active&quot;: false
            },
            {
                &quot;url&quot;: &quot;/?page=1&quot;,
                &quot;label&quot;: &quot;1&quot;,
                &quot;active&quot;: true
            },
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;Next &amp;raquo;&quot;,
                &quot;active&quot;: false
            }
        ],
        &quot;path&quot;: &quot;/&quot;,
        &quot;per_page&quot;: 25,
        &quot;to&quot;: 2,
        &quot;total&quot;: 2
    }
}</code>
 </pre>
                </span>
                <span id="execution-results-GETapi-projects--project_id--servers--server_id--database-users" hidden>
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-GETapi-projects--project_id--servers--server_id--database-users"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-GETapi-projects--project_id--servers--server_id--database-users"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-GETapi-projects--project_id--servers--server_id--database-users" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-GETapi-projects--project_id--servers--server_id--database-users">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-GETapi-projects--project_id--servers--server_id--database-users"
                    data-method="GET"
                    data-path="api/projects/{project_id}/servers/{server_id}/database-users"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('GETapi-projects--project_id--servers--server_id--database-users', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-GETapi-projects--project_id--servers--server_id--database-users"
                            onclick="tryItOut('GETapi-projects--project_id--servers--server_id--database-users');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-GETapi-projects--project_id--servers--server_id--database-users"
                            onclick="cancelTryOut('GETapi-projects--project_id--servers--server_id--database-users');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-GETapi-projects--project_id--servers--server_id--database-users"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-green">GET</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/database-users</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--database-users"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--database-users"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--database-users"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--database-users"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                </form>

                <h2 id="database-users-POSTapi-projects--project_id--servers--server_id--database-users">create</h2>

                <p></p>

                <p>Create a new database user.</p>

                <span id="example-requests-POSTapi-projects--project_id--servers--server_id--database-users">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request POST \
    "https://vito.test/api/projects/1/servers/32/database-users" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"username\": \"consequatur\",
    \"password\": \"O[2UZ5ij-e\\/dl4m{o,\",
    \"host\": \"%\"
}"
</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/database-users"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "username": "consequatur",
    "password": "O[2UZ5ij-e\/dl4m{o,",
    "host": "%"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-POSTapi-projects--project_id--servers--server_id--database-users">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;id&quot;: 19,
    &quot;server_id&quot;: 1,
    &quot;username&quot;: &quot;nolan.jaylan&quot;,
    &quot;databases&quot;: [],
    &quot;host&quot;: &quot;%&quot;,
    &quot;status&quot;: &quot;creating&quot;,
    &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
    &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
}</code>
 </pre>
                </span>
                <span id="execution-results-POSTapi-projects--project_id--servers--server_id--database-users" hidden>
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-POSTapi-projects--project_id--servers--server_id--database-users"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-POSTapi-projects--project_id--servers--server_id--database-users"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-POSTapi-projects--project_id--servers--server_id--database-users" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-POSTapi-projects--project_id--servers--server_id--database-users">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-POSTapi-projects--project_id--servers--server_id--database-users"
                    data-method="POST"
                    data-path="api/projects/{project_id}/servers/{server_id}/database-users"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('POSTapi-projects--project_id--servers--server_id--database-users', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-POSTapi-projects--project_id--servers--server_id--database-users"
                            onclick="tryItOut('POSTapi-projects--project_id--servers--server_id--database-users');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-POSTapi-projects--project_id--servers--server_id--database-users"
                            onclick="cancelTryOut('POSTapi-projects--project_id--servers--server_id--database-users');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-POSTapi-projects--project_id--servers--server_id--database-users"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-black">POST</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/database-users</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--database-users"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--database-users"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--database-users"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--database-users"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>username</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="username"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--database-users"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>password</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="password"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--database-users"
                            value="O[2UZ5ij-e/dl4m{o,"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Example:
                            <code>O[2UZ5ij-e/dl4m{o,</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>host</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="host"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--database-users"
                            value="%"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Host, if it is a remote user. Example:
                            <code>%</code>
                        </p>
                    </div>
                </form>

                <h2
                    id="database-users-GETapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
                >
                    show
                </h2>

                <p></p>

                <p>Get a database user by ID.</p>

                <span
                    id="example-requests-GETapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
                >
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request GET \
    --get "https://vito.test/api/projects/1/servers/32/database-users/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/database-users/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span
                    id="example-responses-GETapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
                >
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;id&quot;: 19,
    &quot;server_id&quot;: 1,
    &quot;username&quot;: &quot;carolyne.luettgen&quot;,
    &quot;databases&quot;: [],
    &quot;host&quot;: &quot;%&quot;,
    &quot;status&quot;: &quot;creating&quot;,
    &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
    &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
}</code>
 </pre>
                </span>
                <span
                    id="execution-results-GETapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
                    hidden
                >
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-GETapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-GETapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span
                    id="execution-error-GETapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
                    hidden
                >
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-GETapi-projects--project_id--servers--server_id--database-users--databaseUser_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-GETapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
                    data-method="GET"
                    data-path="api/projects/{project_id}/servers/{server_id}/database-users/{databaseUser_id}"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('GETapi-projects--project_id--servers--server_id--database-users--databaseUser_id-', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-GETapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
                            onclick="tryItOut('GETapi-projects--project_id--servers--server_id--database-users--databaseUser_id-');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-GETapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
                            onclick="cancelTryOut('GETapi-projects--project_id--servers--server_id--database-users--databaseUser_id-');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-GETapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-green">GET</small>
                        <b>
                            <code>api/projects/{project_id}/servers/{server_id}/database-users/{databaseUser_id}</code>
                        </b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>databaseUser_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="databaseUser_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
                            value="17"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the databaseUser. Example:
                            <code>17</code>
                        </p>
                    </div>
                </form>

                <h2
                    id="database-users-POSTapi-projects--project_id--servers--server_id--database-users--databaseUser_id--link"
                >
                    link
                </h2>

                <p></p>

                <p>Link to databases</p>

                <span
                    id="example-requests-POSTapi-projects--project_id--servers--server_id--database-users--databaseUser_id--link"
                >
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request POST \
    "https://vito.test/api/projects/1/servers/32/database-users/17/link" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"databases\": \"consequatur\"
}"
</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/database-users/17/link"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "databases": "consequatur"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span
                    id="example-responses-POSTapi-projects--project_id--servers--server_id--database-users--databaseUser_id--link"
                >
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;id&quot;: 19,
    &quot;server_id&quot;: 1,
    &quot;username&quot;: &quot;carolyne.luettgen&quot;,
    &quot;databases&quot;: [],
    &quot;host&quot;: &quot;%&quot;,
    &quot;status&quot;: &quot;creating&quot;,
    &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
    &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
}</code>
 </pre>
                </span>
                <span
                    id="execution-results-POSTapi-projects--project_id--servers--server_id--database-users--databaseUser_id--link"
                    hidden
                >
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-POSTapi-projects--project_id--servers--server_id--database-users--databaseUser_id--link"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-POSTapi-projects--project_id--servers--server_id--database-users--databaseUser_id--link"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span
                    id="execution-error-POSTapi-projects--project_id--servers--server_id--database-users--databaseUser_id--link"
                    hidden
                >
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-POSTapi-projects--project_id--servers--server_id--database-users--databaseUser_id--link">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-POSTapi-projects--project_id--servers--server_id--database-users--databaseUser_id--link"
                    data-method="POST"
                    data-path="api/projects/{project_id}/servers/{server_id}/database-users/{databaseUser_id}/link"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('POSTapi-projects--project_id--servers--server_id--database-users--databaseUser_id--link', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-POSTapi-projects--project_id--servers--server_id--database-users--databaseUser_id--link"
                            onclick="tryItOut('POSTapi-projects--project_id--servers--server_id--database-users--databaseUser_id--link');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-POSTapi-projects--project_id--servers--server_id--database-users--databaseUser_id--link"
                            onclick="cancelTryOut('POSTapi-projects--project_id--servers--server_id--database-users--databaseUser_id--link');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-POSTapi-projects--project_id--servers--server_id--database-users--databaseUser_id--link"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-black">POST</small>
                        <b>
                            <code>
                                api/projects/{project_id}/servers/{server_id}/database-users/{databaseUser_id}/link
                            </code>
                        </b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--database-users--databaseUser_id--link"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--database-users--databaseUser_id--link"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--database-users--databaseUser_id--link"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--database-users--databaseUser_id--link"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>databaseUser_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="databaseUser_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--database-users--databaseUser_id--link"
                            value="17"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the databaseUser. Example:
                            <code>17</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>databases</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="databases"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--database-users--databaseUser_id--link"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Array of database names to link to the user. Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                </form>

                <h2
                    id="database-users-DELETEapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
                >
                    delete
                </h2>

                <p></p>

                <p>Delete database user.</p>

                <span
                    id="example-requests-DELETEapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
                >
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request DELETE \
    "https://vito.test/api/projects/1/servers/32/database-users/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/database-users/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span
                    id="example-responses-DELETEapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
                >
                    <blockquote>
                        <p>Example response (204):</p>
                    </blockquote>
                    <pre>
<code>Empty response</code>
 </pre>
                </span>
                <span
                    id="execution-results-DELETEapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
                    hidden
                >
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-DELETEapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-DELETEapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span
                    id="execution-error-DELETEapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
                    hidden
                >
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-DELETEapi-projects--project_id--servers--server_id--database-users--databaseUser_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-DELETEapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
                    data-method="DELETE"
                    data-path="api/projects/{project_id}/servers/{server_id}/database-users/{databaseUser_id}"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('DELETEapi-projects--project_id--servers--server_id--database-users--databaseUser_id-', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-DELETEapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
                            onclick="tryItOut('DELETEapi-projects--project_id--servers--server_id--database-users--databaseUser_id-');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-DELETEapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
                            onclick="cancelTryOut('DELETEapi-projects--project_id--servers--server_id--database-users--databaseUser_id-');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-DELETEapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-red">DELETE</small>
                        <b>
                            <code>api/projects/{project_id}/servers/{server_id}/database-users/{databaseUser_id}</code>
                        </b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>databaseUser_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="databaseUser_id"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--database-users--databaseUser_id-"
                            value="17"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the databaseUser. Example:
                            <code>17</code>
                        </p>
                    </div>
                </form>

                <h1 id="databases">databases</h1>

                <h2 id="databases-GETapi-projects--project_id--servers--server_id--databases">list</h2>

                <p></p>

                <p>Get all databases.</p>

                <span id="example-requests-GETapi-projects--project_id--servers--server_id--databases">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request GET \
    --get "https://vito.test/api/projects/1/servers/32/databases" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/databases"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-GETapi-projects--project_id--servers--server_id--databases">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: 21,
            &quot;server_id&quot;: 1,
            &quot;name&quot;: &quot;carolyne.luettgen&quot;,
            &quot;status&quot;: &quot;ready&quot;,
            &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
        },
        {
            &quot;id&quot;: 22,
            &quot;server_id&quot;: 1,
            &quot;name&quot;: &quot;orville77&quot;,
            &quot;status&quot;: &quot;ready&quot;,
            &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
        }
    ],
    &quot;links&quot;: {
        &quot;first&quot;: &quot;/?page=1&quot;,
        &quot;last&quot;: &quot;/?page=1&quot;,
        &quot;prev&quot;: null,
        &quot;next&quot;: null
    },
    &quot;meta&quot;: {
        &quot;current_page&quot;: 1,
        &quot;from&quot;: 1,
        &quot;last_page&quot;: 1,
        &quot;links&quot;: [
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;&amp;laquo; Previous&quot;,
                &quot;active&quot;: false
            },
            {
                &quot;url&quot;: &quot;/?page=1&quot;,
                &quot;label&quot;: &quot;1&quot;,
                &quot;active&quot;: true
            },
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;Next &amp;raquo;&quot;,
                &quot;active&quot;: false
            }
        ],
        &quot;path&quot;: &quot;/&quot;,
        &quot;per_page&quot;: 25,
        &quot;to&quot;: 2,
        &quot;total&quot;: 2
    }
}</code>
 </pre>
                </span>
                <span id="execution-results-GETapi-projects--project_id--servers--server_id--databases" hidden>
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-GETapi-projects--project_id--servers--server_id--databases"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-GETapi-projects--project_id--servers--server_id--databases"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-GETapi-projects--project_id--servers--server_id--databases" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-GETapi-projects--project_id--servers--server_id--databases">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-GETapi-projects--project_id--servers--server_id--databases"
                    data-method="GET"
                    data-path="api/projects/{project_id}/servers/{server_id}/databases"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('GETapi-projects--project_id--servers--server_id--databases', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-GETapi-projects--project_id--servers--server_id--databases"
                            onclick="tryItOut('GETapi-projects--project_id--servers--server_id--databases');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-GETapi-projects--project_id--servers--server_id--databases"
                            onclick="cancelTryOut('GETapi-projects--project_id--servers--server_id--databases');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-GETapi-projects--project_id--servers--server_id--databases"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-green">GET</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/databases</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--databases"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--databases"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--databases"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--databases"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                </form>

                <h2 id="databases-POSTapi-projects--project_id--servers--server_id--databases">create</h2>

                <p></p>

                <p>Create a new database.</p>

                <span id="example-requests-POSTapi-projects--project_id--servers--server_id--databases">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request POST \
    "https://vito.test/api/projects/1/servers/32/databases" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"consequatur\",
    \"charset\": \"consequatur\",
    \"collation\": \"consequatur\"
}"
</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/databases"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "consequatur",
    "charset": "consequatur",
    "collation": "consequatur"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-POSTapi-projects--project_id--servers--server_id--databases">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;id&quot;: 21,
    &quot;server_id&quot;: 1,
    &quot;name&quot;: &quot;carolyne.luettgen&quot;,
    &quot;status&quot;: &quot;ready&quot;,
    &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
    &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
}</code>
 </pre>
                </span>
                <span id="execution-results-POSTapi-projects--project_id--servers--server_id--databases" hidden>
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-POSTapi-projects--project_id--servers--server_id--databases"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-POSTapi-projects--project_id--servers--server_id--databases"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-POSTapi-projects--project_id--servers--server_id--databases" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-POSTapi-projects--project_id--servers--server_id--databases">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-POSTapi-projects--project_id--servers--server_id--databases"
                    data-method="POST"
                    data-path="api/projects/{project_id}/servers/{server_id}/databases"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('POSTapi-projects--project_id--servers--server_id--databases', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-POSTapi-projects--project_id--servers--server_id--databases"
                            onclick="tryItOut('POSTapi-projects--project_id--servers--server_id--databases');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-POSTapi-projects--project_id--servers--server_id--databases"
                            onclick="cancelTryOut('POSTapi-projects--project_id--servers--server_id--databases');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-POSTapi-projects--project_id--servers--server_id--databases"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-black">POST</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/databases</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--databases"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--databases"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--databases"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--databases"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>name</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="name"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--databases"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>charset</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="charset"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--databases"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>collation</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="collation"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--databases"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                </form>

                <h2 id="databases-GETapi-projects--project_id--servers--server_id--databases--id-">show</h2>

                <p></p>

                <p>Get a database by ID.</p>

                <span id="example-requests-GETapi-projects--project_id--servers--server_id--databases--id-">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request GET \
    --get "https://vito.test/api/projects/1/servers/32/databases/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/databases/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-GETapi-projects--project_id--servers--server_id--databases--id-">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;id&quot;: 21,
    &quot;server_id&quot;: 1,
    &quot;name&quot;: &quot;carolyne.luettgen&quot;,
    &quot;status&quot;: &quot;ready&quot;,
    &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
    &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
}</code>
 </pre>
                </span>
                <span id="execution-results-GETapi-projects--project_id--servers--server_id--databases--id-" hidden>
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-GETapi-projects--project_id--servers--server_id--databases--id-"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-GETapi-projects--project_id--servers--server_id--databases--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-GETapi-projects--project_id--servers--server_id--databases--id-" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-GETapi-projects--project_id--servers--server_id--databases--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-GETapi-projects--project_id--servers--server_id--databases--id-"
                    data-method="GET"
                    data-path="api/projects/{project_id}/servers/{server_id}/databases/{id}"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('GETapi-projects--project_id--servers--server_id--databases--id-', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-GETapi-projects--project_id--servers--server_id--databases--id-"
                            onclick="tryItOut('GETapi-projects--project_id--servers--server_id--databases--id-');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-GETapi-projects--project_id--servers--server_id--databases--id-"
                            onclick="cancelTryOut('GETapi-projects--project_id--servers--server_id--databases--id-');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-GETapi-projects--project_id--servers--server_id--databases--id-"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-green">GET</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/databases/{id}</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--databases--id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--databases--id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--databases--id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--databases--id-"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--databases--id-"
                            value="17"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the database. Example:
                            <code>17</code>
                        </p>
                    </div>
                </form>

                <h2 id="databases-DELETEapi-projects--project_id--servers--server_id--databases--database_id-">
                    delete
                </h2>

                <p></p>

                <p>Delete database.</p>

                <span id="example-requests-DELETEapi-projects--project_id--servers--server_id--databases--database_id-">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request DELETE \
    "https://vito.test/api/projects/1/servers/32/databases/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/databases/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span
                    id="example-responses-DELETEapi-projects--project_id--servers--server_id--databases--database_id-"
                >
                    <blockquote>
                        <p>Example response (204):</p>
                    </blockquote>
                    <pre>
<code>Empty response</code>
 </pre>
                </span>
                <span
                    id="execution-results-DELETEapi-projects--project_id--servers--server_id--databases--database_id-"
                    hidden
                >
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-DELETEapi-projects--project_id--servers--server_id--databases--database_id-"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-DELETEapi-projects--project_id--servers--server_id--databases--database_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span
                    id="execution-error-DELETEapi-projects--project_id--servers--server_id--databases--database_id-"
                    hidden
                >
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-DELETEapi-projects--project_id--servers--server_id--databases--database_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-DELETEapi-projects--project_id--servers--server_id--databases--database_id-"
                    data-method="DELETE"
                    data-path="api/projects/{project_id}/servers/{server_id}/databases/{database_id}"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('DELETEapi-projects--project_id--servers--server_id--databases--database_id-', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-DELETEapi-projects--project_id--servers--server_id--databases--database_id-"
                            onclick="tryItOut('DELETEapi-projects--project_id--servers--server_id--databases--database_id-');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-DELETEapi-projects--project_id--servers--server_id--databases--database_id-"
                            onclick="cancelTryOut('DELETEapi-projects--project_id--servers--server_id--databases--database_id-');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-DELETEapi-projects--project_id--servers--server_id--databases--database_id-"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-red">DELETE</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/databases/{database_id}</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--databases--database_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--databases--database_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--databases--database_id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--databases--database_id-"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>database_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="database_id"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--databases--database_id-"
                            value="17"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the database. Example:
                            <code>17</code>
                        </p>
                    </div>
                </form>

                <h1 id="firewall-rules">firewall-rules</h1>

                <h2 id="firewall-rules-GETapi-projects--project_id--servers--server_id--firewall-rules">list</h2>

                <p></p>

                <p>Get all firewall rules.</p>

                <span id="example-requests-GETapi-projects--project_id--servers--server_id--firewall-rules">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request GET \
    --get "https://vito.test/api/projects/1/servers/32/firewall-rules" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/firewall-rules"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-GETapi-projects--project_id--servers--server_id--firewall-rules">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: 97,
            &quot;name&quot;: &quot;dolores&quot;,
            &quot;server_id&quot;: 1,
            &quot;type&quot;: &quot;allow&quot;,
            &quot;protocol&quot;: &quot;tcp&quot;,
            &quot;port&quot;: 40770,
            &quot;source&quot;: &quot;199.76.131.15&quot;,
            &quot;mask&quot;: &quot;24&quot;,
            &quot;note&quot;: &quot;test&quot;,
            &quot;status&quot;: &quot;creating&quot;,
            &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
        },
        {
            &quot;id&quot;: 98,
            &quot;name&quot;: &quot;laborum&quot;,
            &quot;server_id&quot;: 1,
            &quot;type&quot;: &quot;allow&quot;,
            &quot;protocol&quot;: &quot;tcp&quot;,
            &quot;port&quot;: 14235,
            &quot;source&quot;: &quot;100.14.146.200&quot;,
            &quot;mask&quot;: &quot;24&quot;,
            &quot;note&quot;: &quot;test&quot;,
            &quot;status&quot;: &quot;creating&quot;,
            &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
        }
    ],
    &quot;links&quot;: {
        &quot;first&quot;: &quot;/?page=1&quot;,
        &quot;last&quot;: &quot;/?page=1&quot;,
        &quot;prev&quot;: null,
        &quot;next&quot;: null
    },
    &quot;meta&quot;: {
        &quot;current_page&quot;: 1,
        &quot;from&quot;: 1,
        &quot;last_page&quot;: 1,
        &quot;links&quot;: [
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;&amp;laquo; Previous&quot;,
                &quot;active&quot;: false
            },
            {
                &quot;url&quot;: &quot;/?page=1&quot;,
                &quot;label&quot;: &quot;1&quot;,
                &quot;active&quot;: true
            },
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;Next &amp;raquo;&quot;,
                &quot;active&quot;: false
            }
        ],
        &quot;path&quot;: &quot;/&quot;,
        &quot;per_page&quot;: 25,
        &quot;to&quot;: 2,
        &quot;total&quot;: 2
    }
}</code>
 </pre>
                </span>
                <span id="execution-results-GETapi-projects--project_id--servers--server_id--firewall-rules" hidden>
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-GETapi-projects--project_id--servers--server_id--firewall-rules"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-GETapi-projects--project_id--servers--server_id--firewall-rules"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-GETapi-projects--project_id--servers--server_id--firewall-rules" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-GETapi-projects--project_id--servers--server_id--firewall-rules">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-GETapi-projects--project_id--servers--server_id--firewall-rules"
                    data-method="GET"
                    data-path="api/projects/{project_id}/servers/{server_id}/firewall-rules"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('GETapi-projects--project_id--servers--server_id--firewall-rules', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-GETapi-projects--project_id--servers--server_id--firewall-rules"
                            onclick="tryItOut('GETapi-projects--project_id--servers--server_id--firewall-rules');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-GETapi-projects--project_id--servers--server_id--firewall-rules"
                            onclick="cancelTryOut('GETapi-projects--project_id--servers--server_id--firewall-rules');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-GETapi-projects--project_id--servers--server_id--firewall-rules"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-green">GET</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/firewall-rules</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--firewall-rules"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--firewall-rules"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--firewall-rules"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--firewall-rules"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                </form>

                <h2 id="firewall-rules-POSTapi-projects--project_id--servers--server_id--firewall-rules">create</h2>

                <p></p>

                <p>Create a new firewall rule.</p>

                <span id="example-requests-POSTapi-projects--project_id--servers--server_id--firewall-rules">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request POST \
    "https://vito.test/api/projects/1/servers/32/firewall-rules" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"consequatur\",
    \"type\": \"allow\",
    \"protocol\": \"tcp\",
    \"port\": \"consequatur\",
    \"source\": \"consequatur\",
    \"mask\": \"0\"
}"
</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/firewall-rules"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "consequatur",
    "type": "allow",
    "protocol": "tcp",
    "port": "consequatur",
    "source": "consequatur",
    "mask": "0"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-POSTapi-projects--project_id--servers--server_id--firewall-rules">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;id&quot;: 97,
    &quot;name&quot;: &quot;dolores&quot;,
    &quot;server_id&quot;: 1,
    &quot;type&quot;: &quot;allow&quot;,
    &quot;protocol&quot;: &quot;tcp&quot;,
    &quot;port&quot;: 40770,
    &quot;source&quot;: &quot;199.76.131.15&quot;,
    &quot;mask&quot;: &quot;24&quot;,
    &quot;note&quot;: &quot;test&quot;,
    &quot;status&quot;: &quot;creating&quot;,
    &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
    &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
}</code>
 </pre>
                </span>
                <span id="execution-results-POSTapi-projects--project_id--servers--server_id--firewall-rules" hidden>
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-POSTapi-projects--project_id--servers--server_id--firewall-rules"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-POSTapi-projects--project_id--servers--server_id--firewall-rules"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-POSTapi-projects--project_id--servers--server_id--firewall-rules" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-POSTapi-projects--project_id--servers--server_id--firewall-rules">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-POSTapi-projects--project_id--servers--server_id--firewall-rules"
                    data-method="POST"
                    data-path="api/projects/{project_id}/servers/{server_id}/firewall-rules"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('POSTapi-projects--project_id--servers--server_id--firewall-rules', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-POSTapi-projects--project_id--servers--server_id--firewall-rules"
                            onclick="tryItOut('POSTapi-projects--project_id--servers--server_id--firewall-rules');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-POSTapi-projects--project_id--servers--server_id--firewall-rules"
                            onclick="cancelTryOut('POSTapi-projects--project_id--servers--server_id--firewall-rules');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-POSTapi-projects--project_id--servers--server_id--firewall-rules"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-black">POST</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/firewall-rules</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--firewall-rules"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--firewall-rules"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--firewall-rules"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--firewall-rules"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>name</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="name"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--firewall-rules"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>type</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="type"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--firewall-rules"
                            value="allow"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Example:
                            <code>allow</code>
                        </p>
                        Must be one of:
                        <ul style="list-style-type: square">
                            <li><code>allow</code></li>
                            <li><code>deny</code></li>
                        </ul>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>protocol</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="protocol"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--firewall-rules"
                            value="tcp"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Example:
                            <code>tcp</code>
                        </p>
                        Must be one of:
                        <ul style="list-style-type: square">
                            <li><code>tcp</code></li>
                            <li><code>udp</code></li>
                        </ul>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>port</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="port"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--firewall-rules"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>source</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp;
                        <i>optional</i>
                        &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="source"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--firewall-rules"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>mask</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="mask"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--firewall-rules"
                            value="0"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Mask for source IP. Example:
                            <code>0</code>
                        </p>
                    </div>
                </form>

                <h2
                    id="firewall-rules-PUTapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                >
                    edit
                </h2>

                <p></p>

                <p>Update an existing firewall rule.</p>

                <span
                    id="example-requests-PUTapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                >
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request PUT \
    "https://vito.test/api/projects/1/servers/32/firewall-rules/94" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"consequatur\",
    \"type\": \"allow\",
    \"protocol\": \"tcp\",
    \"port\": \"consequatur\",
    \"source\": \"consequatur\",
    \"mask\": \"0\"
}"
</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/firewall-rules/94"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "consequatur",
    "type": "allow",
    "protocol": "tcp",
    "port": "consequatur",
    "source": "consequatur",
    "mask": "0"
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span
                    id="example-responses-PUTapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                >
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;id&quot;: 97,
    &quot;name&quot;: &quot;dolores&quot;,
    &quot;server_id&quot;: 1,
    &quot;type&quot;: &quot;allow&quot;,
    &quot;protocol&quot;: &quot;tcp&quot;,
    &quot;port&quot;: 40770,
    &quot;source&quot;: &quot;199.76.131.15&quot;,
    &quot;mask&quot;: &quot;24&quot;,
    &quot;note&quot;: &quot;test&quot;,
    &quot;status&quot;: &quot;creating&quot;,
    &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
    &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
}</code>
 </pre>
                </span>
                <span
                    id="execution-results-PUTapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                    hidden
                >
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-PUTapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-PUTapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span
                    id="execution-error-PUTapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                    hidden
                >
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-PUTapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-PUTapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                    data-method="PUT"
                    data-path="api/projects/{project_id}/servers/{server_id}/firewall-rules/{firewallRule_id}"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('PUTapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-PUTapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                            onclick="tryItOut('PUTapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-PUTapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                            onclick="cancelTryOut('PUTapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-PUTapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-darkblue">PUT</small>
                        <b>
                            <code>api/projects/{project_id}/servers/{server_id}/firewall-rules/{firewallRule_id}</code>
                        </b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="PUTapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="PUTapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="PUTapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="PUTapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>firewallRule_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="firewallRule_id"
                            data-endpoint="PUTapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                            value="94"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the firewallRule. Example:
                            <code>94</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>name</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="name"
                            data-endpoint="PUTapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>type</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="type"
                            data-endpoint="PUTapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                            value="allow"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Example:
                            <code>allow</code>
                        </p>
                        Must be one of:
                        <ul style="list-style-type: square">
                            <li><code>allow</code></li>
                            <li><code>deny</code></li>
                        </ul>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>protocol</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="protocol"
                            data-endpoint="PUTapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                            value="tcp"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Example:
                            <code>tcp</code>
                        </p>
                        Must be one of:
                        <ul style="list-style-type: square">
                            <li><code>tcp</code></li>
                            <li><code>udp</code></li>
                        </ul>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>port</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="port"
                            data-endpoint="PUTapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>source</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp;
                        <i>optional</i>
                        &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="source"
                            data-endpoint="PUTapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>mask</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="mask"
                            data-endpoint="PUTapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                            value="0"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Mask for source IP. Example:
                            <code>0</code>
                        </p>
                    </div>
                </form>

                <h2
                    id="firewall-rules-GETapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                >
                    show
                </h2>

                <p></p>

                <p>Get a firewall rule by ID.</p>

                <span
                    id="example-requests-GETapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                >
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request GET \
    --get "https://vito.test/api/projects/1/servers/32/firewall-rules/94" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/firewall-rules/94"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span
                    id="example-responses-GETapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                >
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;id&quot;: 97,
    &quot;name&quot;: &quot;laborum&quot;,
    &quot;server_id&quot;: 1,
    &quot;type&quot;: &quot;allow&quot;,
    &quot;protocol&quot;: &quot;tcp&quot;,
    &quot;port&quot;: 14235,
    &quot;source&quot;: &quot;100.14.146.200&quot;,
    &quot;mask&quot;: &quot;24&quot;,
    &quot;note&quot;: &quot;test&quot;,
    &quot;status&quot;: &quot;creating&quot;,
    &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
    &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
}</code>
 </pre>
                </span>
                <span
                    id="execution-results-GETapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                    hidden
                >
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-GETapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-GETapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span
                    id="execution-error-GETapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                    hidden
                >
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-GETapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-GETapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                    data-method="GET"
                    data-path="api/projects/{project_id}/servers/{server_id}/firewall-rules/{firewallRule_id}"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('GETapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-GETapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                            onclick="tryItOut('GETapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-GETapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                            onclick="cancelTryOut('GETapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-GETapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-green">GET</small>
                        <b>
                            <code>api/projects/{project_id}/servers/{server_id}/firewall-rules/{firewallRule_id}</code>
                        </b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>firewallRule_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="firewallRule_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                            value="94"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the firewallRule. Example:
                            <code>94</code>
                        </p>
                    </div>
                </form>

                <h2
                    id="firewall-rules-DELETEapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                >
                    delete
                </h2>

                <p></p>

                <p>Delete firewall rule.</p>

                <span
                    id="example-requests-DELETEapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                >
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request DELETE \
    "https://vito.test/api/projects/1/servers/32/firewall-rules/94" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/firewall-rules/94"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span
                    id="example-responses-DELETEapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                >
                    <blockquote>
                        <p>Example response (204):</p>
                    </blockquote>
                    <pre>
<code>Empty response</code>
 </pre>
                </span>
                <span
                    id="execution-results-DELETEapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                    hidden
                >
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-DELETEapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-DELETEapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span
                    id="execution-error-DELETEapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                    hidden
                >
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-DELETEapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-DELETEapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                    data-method="DELETE"
                    data-path="api/projects/{project_id}/servers/{server_id}/firewall-rules/{firewallRule_id}"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('DELETEapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-DELETEapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                            onclick="tryItOut('DELETEapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-DELETEapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                            onclick="cancelTryOut('DELETEapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-DELETEapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-red">DELETE</small>
                        <b>
                            <code>api/projects/{project_id}/servers/{server_id}/firewall-rules/{firewallRule_id}</code>
                        </b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>firewallRule_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="firewallRule_id"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--firewall-rules--firewallRule_id-"
                            value="94"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the firewallRule. Example:
                            <code>94</code>
                        </p>
                    </div>
                </form>

                <h1 id="general">general</h1>

                <h2 id="general-GETapi-health">health-check</h2>

                <p></p>

                <span id="example-requests-GETapi-health">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request GET \
    --get "https://vito.test/api/health" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/health"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-GETapi-health">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <details class="annotation">
                        <summary style="cursor: pointer">
                            <small
                                onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'"
                            >
                                Show headers
                            </small>
                        </summary>
                        <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
x-ratelimit-limit: 60
x-ratelimit-remaining: 57
access-control-allow-origin: *
 </code></pre>
                    </details>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;version&quot;: &quot;2.5.0&quot;
}</code>
 </pre>
                </span>
                <span id="execution-results-GETapi-health" hidden>
                    <blockquote>
                        Received response
                        <span id="execution-response-status-GETapi-health"></span>
                        :
                    </blockquote>
                    <pre class="json"><code id="execution-response-content-GETapi-health"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-GETapi-health" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-GETapi-health">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-GETapi-health"
                    data-method="GET"
                    data-path="api/health"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('GETapi-health', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-GETapi-health"
                            onclick="tryItOut('GETapi-health');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-GETapi-health"
                            onclick="cancelTryOut('GETapi-health');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-GETapi-health"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-green">GET</small>
                        <b><code>api/health</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="GETapi-health"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="GETapi-health"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                </form>

                <h1 id="projects">projects</h1>

                <h2 id="projects-GETapi-projects">list</h2>

                <p></p>

                <p>Get all projects.</p>

                <span id="example-requests-GETapi-projects">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request GET \
    --get "https://vito.test/api/projects" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-GETapi-projects">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: 3,
            &quot;name&quot;: &quot;Nash Corwin&quot;,
            &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
        },
        {
            &quot;id&quot;: 4,
            &quot;name&quot;: &quot;Patience Douglas&quot;,
            &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
        }
    ],
    &quot;links&quot;: {
        &quot;first&quot;: &quot;/?page=1&quot;,
        &quot;last&quot;: &quot;/?page=1&quot;,
        &quot;prev&quot;: null,
        &quot;next&quot;: null
    },
    &quot;meta&quot;: {
        &quot;current_page&quot;: 1,
        &quot;from&quot;: 1,
        &quot;last_page&quot;: 1,
        &quot;links&quot;: [
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;&amp;laquo; Previous&quot;,
                &quot;active&quot;: false
            },
            {
                &quot;url&quot;: &quot;/?page=1&quot;,
                &quot;label&quot;: &quot;1&quot;,
                &quot;active&quot;: true
            },
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;Next &amp;raquo;&quot;,
                &quot;active&quot;: false
            }
        ],
        &quot;path&quot;: &quot;/&quot;,
        &quot;per_page&quot;: 25,
        &quot;to&quot;: 2,
        &quot;total&quot;: 2
    }
}</code>
 </pre>
                </span>
                <span id="execution-results-GETapi-projects" hidden>
                    <blockquote>
                        Received response
                        <span id="execution-response-status-GETapi-projects"></span>
                        :
                    </blockquote>
                    <pre class="json"><code id="execution-response-content-GETapi-projects"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-GETapi-projects" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-GETapi-projects">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-GETapi-projects"
                    data-method="GET"
                    data-path="api/projects"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('GETapi-projects', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-GETapi-projects"
                            onclick="tryItOut('GETapi-projects');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-GETapi-projects"
                            onclick="cancelTryOut('GETapi-projects');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-GETapi-projects"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-green">GET</small>
                        <b><code>api/projects</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="GETapi-projects"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="GETapi-projects"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                </form>

                <h2 id="projects-POSTapi-projects">create</h2>

                <p></p>

                <p>Create a new project.</p>

                <span id="example-requests-POSTapi-projects">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request POST \
    "https://vito.test/api/projects" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"consequatur\"
}"
</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "consequatur"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-POSTapi-projects">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;id&quot;: 3,
    &quot;name&quot;: &quot;Dr. Cornelius Luettgen V&quot;,
    &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
    &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
}</code>
 </pre>
                </span>
                <span id="execution-results-POSTapi-projects" hidden>
                    <blockquote>
                        Received response
                        <span id="execution-response-status-POSTapi-projects"></span>
                        :
                    </blockquote>
                    <pre class="json"><code id="execution-response-content-POSTapi-projects"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-POSTapi-projects" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-POSTapi-projects">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-POSTapi-projects"
                    data-method="POST"
                    data-path="api/projects"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('POSTapi-projects', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-POSTapi-projects"
                            onclick="tryItOut('POSTapi-projects');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-POSTapi-projects"
                            onclick="cancelTryOut('POSTapi-projects');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-POSTapi-projects"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-black">POST</small>
                        <b><code>api/projects</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="POSTapi-projects"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="POSTapi-projects"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>name</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="name"
                            data-endpoint="POSTapi-projects"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            The name of the project. Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                </form>

                <h2 id="projects-GETapi-projects--id-">show</h2>

                <p></p>

                <p>Get a project by ID.</p>

                <span id="example-requests-GETapi-projects--id-">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request GET \
    --get "https://vito.test/api/projects/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-GETapi-projects--id-">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;id&quot;: 3,
    &quot;name&quot;: &quot;Orville Satterfield&quot;,
    &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
    &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
}</code>
 </pre>
                </span>
                <span id="execution-results-GETapi-projects--id-" hidden>
                    <blockquote>
                        Received response
                        <span id="execution-response-status-GETapi-projects--id-"></span>
                        :
                    </blockquote>
                    <pre class="json"><code id="execution-response-content-GETapi-projects--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-GETapi-projects--id-" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-GETapi-projects--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-GETapi-projects--id-"
                    data-method="GET"
                    data-path="api/projects/{id}"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('GETapi-projects--id-', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-GETapi-projects--id-"
                            onclick="tryItOut('GETapi-projects--id-');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-GETapi-projects--id-"
                            onclick="cancelTryOut('GETapi-projects--id-');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-GETapi-projects--id-"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-green">GET</small>
                        <b><code>api/projects/{id}</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="GETapi-projects--id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="GETapi-projects--id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="id"
                            data-endpoint="GETapi-projects--id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                </form>

                <h2 id="projects-PUTapi-projects--id-">update</h2>

                <p></p>

                <p>Update project.</p>

                <span id="example-requests-PUTapi-projects--id-">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request PUT \
    "https://vito.test/api/projects/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"consequatur\"
}"
</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "consequatur"
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-PUTapi-projects--id-">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;id&quot;: 3,
    &quot;name&quot;: &quot;Dr. Cornelius Luettgen V&quot;,
    &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
    &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
}</code>
 </pre>
                </span>
                <span id="execution-results-PUTapi-projects--id-" hidden>
                    <blockquote>
                        Received response
                        <span id="execution-response-status-PUTapi-projects--id-"></span>
                        :
                    </blockquote>
                    <pre class="json"><code id="execution-response-content-PUTapi-projects--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-PUTapi-projects--id-" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-PUTapi-projects--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-PUTapi-projects--id-"
                    data-method="PUT"
                    data-path="api/projects/{id}"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('PUTapi-projects--id-', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-PUTapi-projects--id-"
                            onclick="tryItOut('PUTapi-projects--id-');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-PUTapi-projects--id-"
                            onclick="cancelTryOut('PUTapi-projects--id-');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-PUTapi-projects--id-"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-darkblue">PUT</small>
                        <b><code>api/projects/{id}</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="PUTapi-projects--id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="PUTapi-projects--id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="id"
                            data-endpoint="PUTapi-projects--id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>name</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="name"
                            data-endpoint="PUTapi-projects--id-"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            The name of the project. Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                </form>

                <h2 id="projects-DELETEapi-projects--project_id-">delete</h2>

                <p></p>

                <p>Delete project.</p>

                <span id="example-requests-DELETEapi-projects--project_id-">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request DELETE \
    "https://vito.test/api/projects/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-DELETEapi-projects--project_id-">
                    <blockquote>
                        <p>Example response (204):</p>
                    </blockquote>
                    <pre>
<code>Empty response</code>
 </pre>
                </span>
                <span id="execution-results-DELETEapi-projects--project_id-" hidden>
                    <blockquote>
                        Received response
                        <span id="execution-response-status-DELETEapi-projects--project_id-"></span>
                        :
                    </blockquote>
                    <pre class="json"><code id="execution-response-content-DELETEapi-projects--project_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-DELETEapi-projects--project_id-" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-DELETEapi-projects--project_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-DELETEapi-projects--project_id-"
                    data-method="DELETE"
                    data-path="api/projects/{project_id}"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('DELETEapi-projects--project_id-', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-DELETEapi-projects--project_id-"
                            onclick="tryItOut('DELETEapi-projects--project_id-');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-DELETEapi-projects--project_id-"
                            onclick="cancelTryOut('DELETEapi-projects--project_id-');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-DELETEapi-projects--project_id-"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-red">DELETE</small>
                        <b><code>api/projects/{project_id}</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="DELETEapi-projects--project_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="DELETEapi-projects--project_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="DELETEapi-projects--project_id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                </form>

                <h1 id="redirects">redirects</h1>

                <h2 id="redirects-GETapi-projects--project_id--servers--server_id--sites--site_id--redirects">index</h2>

                <p></p>

                <p>Get all redirects.</p>

                <span id="example-requests-GETapi-projects--project_id--servers--server_id--sites--site_id--redirects">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request GET \
    --get "https://vito.test/api/projects/1/servers/32/sites/17/redirects" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/sites/17/redirects"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-GETapi-projects--project_id--servers--server_id--sites--site_id--redirects">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: 11,
            &quot;site_id&quot;: 1,
            &quot;mode&quot;: 308,
            &quot;from&quot;: &quot;dolores&quot;,
            &quot;to&quot;: &quot;http://dibbert.com/eius-est-dolor-dolores-minus-voluptatem-quisquam&quot;,
            &quot;status&quot;: &quot;ready&quot;,
            &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
        },
        {
            &quot;id&quot;: 12,
            &quot;site_id&quot;: 1,
            &quot;mode&quot;: 302,
            &quot;from&quot;: &quot;sed&quot;,
            &quot;to&quot;: &quot;http://williamson.net/fugit-facilis-perferendis-dolores-molestias.html&quot;,
            &quot;status&quot;: &quot;ready&quot;,
            &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
        }
    ],
    &quot;links&quot;: {
        &quot;first&quot;: &quot;/?page=1&quot;,
        &quot;last&quot;: &quot;/?page=1&quot;,
        &quot;prev&quot;: null,
        &quot;next&quot;: null
    },
    &quot;meta&quot;: {
        &quot;current_page&quot;: 1,
        &quot;from&quot;: 1,
        &quot;last_page&quot;: 1,
        &quot;links&quot;: [
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;&amp;laquo; Previous&quot;,
                &quot;active&quot;: false
            },
            {
                &quot;url&quot;: &quot;/?page=1&quot;,
                &quot;label&quot;: &quot;1&quot;,
                &quot;active&quot;: true
            },
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;Next &amp;raquo;&quot;,
                &quot;active&quot;: false
            }
        ],
        &quot;path&quot;: &quot;/&quot;,
        &quot;per_page&quot;: 25,
        &quot;to&quot;: 2,
        &quot;total&quot;: 2
    }
}</code>
 </pre>
                </span>
                <span
                    id="execution-results-GETapi-projects--project_id--servers--server_id--sites--site_id--redirects"
                    hidden
                >
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-GETapi-projects--project_id--servers--server_id--sites--site_id--redirects"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-GETapi-projects--project_id--servers--server_id--sites--site_id--redirects"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span
                    id="execution-error-GETapi-projects--project_id--servers--server_id--sites--site_id--redirects"
                    hidden
                >
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-GETapi-projects--project_id--servers--server_id--sites--site_id--redirects">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-GETapi-projects--project_id--servers--server_id--sites--site_id--redirects"
                    data-method="GET"
                    data-path="api/projects/{project_id}/servers/{server_id}/sites/{site_id}/redirects"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('GETapi-projects--project_id--servers--server_id--sites--site_id--redirects', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-GETapi-projects--project_id--servers--server_id--sites--site_id--redirects"
                            onclick="tryItOut('GETapi-projects--project_id--servers--server_id--sites--site_id--redirects');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-GETapi-projects--project_id--servers--server_id--sites--site_id--redirects"
                            onclick="cancelTryOut('GETapi-projects--project_id--servers--server_id--sites--site_id--redirects');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-GETapi-projects--project_id--servers--server_id--sites--site_id--redirects"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-green">GET</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/sites/{site_id}/redirects</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--sites--site_id--redirects"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--sites--site_id--redirects"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--sites--site_id--redirects"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--sites--site_id--redirects"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>site_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="site_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--sites--site_id--redirects"
                            value="17"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the site. Example:
                            <code>17</code>
                        </p>
                    </div>
                </form>

                <h2 id="redirects-POSTapi-projects--project_id--servers--server_id--sites--site_id--redirects">
                    create
                </h2>

                <p></p>

                <p>Create a new redirect.</p>

                <span id="example-requests-POSTapi-projects--project_id--servers--server_id--sites--site_id--redirects">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request POST \
    "https://vito.test/api/projects/1/servers/32/sites/17/redirects" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"from\": \"consequatur\",
    \"to\": \"consequatur\",
    \"mode\": 302
}"
</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/sites/17/redirects"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "from": "consequatur",
    "to": "consequatur",
    "mode": 302
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span
                    id="example-responses-POSTapi-projects--project_id--servers--server_id--sites--site_id--redirects"
                >
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;"></code>
 </pre>
                </span>
                <span
                    id="execution-results-POSTapi-projects--project_id--servers--server_id--sites--site_id--redirects"
                    hidden
                >
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-POSTapi-projects--project_id--servers--server_id--sites--site_id--redirects"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-POSTapi-projects--project_id--servers--server_id--sites--site_id--redirects"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span
                    id="execution-error-POSTapi-projects--project_id--servers--server_id--sites--site_id--redirects"
                    hidden
                >
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-POSTapi-projects--project_id--servers--server_id--sites--site_id--redirects">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-POSTapi-projects--project_id--servers--server_id--sites--site_id--redirects"
                    data-method="POST"
                    data-path="api/projects/{project_id}/servers/{server_id}/sites/{site_id}/redirects"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('POSTapi-projects--project_id--servers--server_id--sites--site_id--redirects', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-POSTapi-projects--project_id--servers--server_id--sites--site_id--redirects"
                            onclick="tryItOut('POSTapi-projects--project_id--servers--server_id--sites--site_id--redirects');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-POSTapi-projects--project_id--servers--server_id--sites--site_id--redirects"
                            onclick="cancelTryOut('POSTapi-projects--project_id--servers--server_id--sites--site_id--redirects');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-POSTapi-projects--project_id--servers--server_id--sites--site_id--redirects"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-black">POST</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/sites/{site_id}/redirects</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites--site_id--redirects"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites--site_id--redirects"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites--site_id--redirects"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites--site_id--redirects"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>site_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="site_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites--site_id--redirects"
                            value="17"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the site. Example:
                            <code>17</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>from</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="from"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites--site_id--redirects"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>to</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="to"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites--site_id--redirects"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>mode</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="mode"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites--site_id--redirects"
                            value="302"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Example:
                            <code>302</code>
                        </p>
                        Must be one of:
                        <ul style="list-style-type: square">
                            <li><code>301</code></li>
                            <li><code>302</code></li>
                            <li><code>307</code></li>
                            <li><code>308</code></li>
                        </ul>
                    </div>
                </form>

                <h2
                    id="redirects-DELETEapi-projects--project_id--servers--server_id--sites--site_id--redirects--redirect_id-"
                >
                    delete
                </h2>

                <p></p>

                <p>Delete a redirect.</p>

                <span
                    id="example-requests-DELETEapi-projects--project_id--servers--server_id--sites--site_id--redirects--redirect_id-"
                >
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request DELETE \
    "https://vito.test/api/projects/1/servers/32/sites/17/redirects/9" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/sites/17/redirects/9"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span
                    id="example-responses-DELETEapi-projects--project_id--servers--server_id--sites--site_id--redirects--redirect_id-"
                >
                    <blockquote>
                        <p>Example response (204):</p>
                    </blockquote>
                    <pre>
<code>Empty response</code>
 </pre>
                </span>
                <span
                    id="execution-results-DELETEapi-projects--project_id--servers--server_id--sites--site_id--redirects--redirect_id-"
                    hidden
                >
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-DELETEapi-projects--project_id--servers--server_id--sites--site_id--redirects--redirect_id-"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-DELETEapi-projects--project_id--servers--server_id--sites--site_id--redirects--redirect_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span
                    id="execution-error-DELETEapi-projects--project_id--servers--server_id--sites--site_id--redirects--redirect_id-"
                    hidden
                >
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-DELETEapi-projects--project_id--servers--server_id--sites--site_id--redirects--redirect_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-DELETEapi-projects--project_id--servers--server_id--sites--site_id--redirects--redirect_id-"
                    data-method="DELETE"
                    data-path="api/projects/{project_id}/servers/{server_id}/sites/{site_id}/redirects/{redirect_id}"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('DELETEapi-projects--project_id--servers--server_id--sites--site_id--redirects--redirect_id-', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-DELETEapi-projects--project_id--servers--server_id--sites--site_id--redirects--redirect_id-"
                            onclick="tryItOut('DELETEapi-projects--project_id--servers--server_id--sites--site_id--redirects--redirect_id-');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-DELETEapi-projects--project_id--servers--server_id--sites--site_id--redirects--redirect_id-"
                            onclick="cancelTryOut('DELETEapi-projects--project_id--servers--server_id--sites--site_id--redirects--redirect_id-');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-DELETEapi-projects--project_id--servers--server_id--sites--site_id--redirects--redirect_id-"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-red">DELETE</small>
                        <b>
                            <code>
                                api/projects/{project_id}/servers/{server_id}/sites/{site_id}/redirects/{redirect_id}
                            </code>
                        </b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--sites--site_id--redirects--redirect_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--sites--site_id--redirects--redirect_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--sites--site_id--redirects--redirect_id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--sites--site_id--redirects--redirect_id-"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>site_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="site_id"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--sites--site_id--redirects--redirect_id-"
                            value="17"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the site. Example:
                            <code>17</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>redirect_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="redirect_id"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--sites--site_id--redirects--redirect_id-"
                            value="9"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the redirect. Example:
                            <code>9</code>
                        </p>
                    </div>
                </form>

                <h1 id="server-providers">server-providers</h1>

                <h2 id="server-providers-GETapi-projects--project_id--server-providers">list</h2>

                <p></p>

                <span id="example-requests-GETapi-projects--project_id--server-providers">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request GET \
    --get "https://vito.test/api/projects/1/server-providers" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/server-providers"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-GETapi-projects--project_id--server-providers">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: 4,
            &quot;project_id&quot;: null,
            &quot;global&quot;: true,
            &quot;name&quot;: &quot;quo&quot;,
            &quot;provider&quot;: &quot;custom&quot;,
            &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
        },
        {
            &quot;id&quot;: 5,
            &quot;project_id&quot;: null,
            &quot;global&quot;: true,
            &quot;name&quot;: &quot;sed&quot;,
            &quot;provider&quot;: &quot;digitalocean&quot;,
            &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
        }
    ],
    &quot;links&quot;: {
        &quot;first&quot;: &quot;/?page=1&quot;,
        &quot;last&quot;: &quot;/?page=1&quot;,
        &quot;prev&quot;: null,
        &quot;next&quot;: null
    },
    &quot;meta&quot;: {
        &quot;current_page&quot;: 1,
        &quot;from&quot;: 1,
        &quot;last_page&quot;: 1,
        &quot;links&quot;: [
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;&amp;laquo; Previous&quot;,
                &quot;active&quot;: false
            },
            {
                &quot;url&quot;: &quot;/?page=1&quot;,
                &quot;label&quot;: &quot;1&quot;,
                &quot;active&quot;: true
            },
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;Next &amp;raquo;&quot;,
                &quot;active&quot;: false
            }
        ],
        &quot;path&quot;: &quot;/&quot;,
        &quot;per_page&quot;: 25,
        &quot;to&quot;: 2,
        &quot;total&quot;: 2
    }
}</code>
 </pre>
                </span>
                <span id="execution-results-GETapi-projects--project_id--server-providers" hidden>
                    <blockquote>
                        Received response
                        <span id="execution-response-status-GETapi-projects--project_id--server-providers"></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-GETapi-projects--project_id--server-providers"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-GETapi-projects--project_id--server-providers" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-GETapi-projects--project_id--server-providers">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-GETapi-projects--project_id--server-providers"
                    data-method="GET"
                    data-path="api/projects/{project_id}/server-providers"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('GETapi-projects--project_id--server-providers', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-GETapi-projects--project_id--server-providers"
                            onclick="tryItOut('GETapi-projects--project_id--server-providers');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-GETapi-projects--project_id--server-providers"
                            onclick="cancelTryOut('GETapi-projects--project_id--server-providers');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-GETapi-projects--project_id--server-providers"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-green">GET</small>
                        <b><code>api/projects/{project_id}/server-providers</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="GETapi-projects--project_id--server-providers"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="GETapi-projects--project_id--server-providers"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="GETapi-projects--project_id--server-providers"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                </form>

                <h2 id="server-providers-POSTapi-projects--project_id--server-providers">create</h2>

                <p></p>

                <span id="example-requests-POSTapi-projects--project_id--server-providers">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request POST \
    "https://vito.test/api/projects/1/server-providers" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"provider\": \"consequatur\",
    \"name\": \"consequatur\",
    \"token\": \"consequatur\",
    \"key\": \"consequatur\",
    \"secret\": \"consequatur\"
}"
</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/server-providers"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "provider": "consequatur",
    "name": "consequatur",
    "token": "consequatur",
    "key": "consequatur",
    "secret": "consequatur"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-POSTapi-projects--project_id--server-providers">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;id&quot;: 4,
    &quot;project_id&quot;: null,
    &quot;global&quot;: true,
    &quot;name&quot;: &quot;dolores&quot;,
    &quot;provider&quot;: &quot;digitalocean&quot;,
    &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
    &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
}</code>
 </pre>
                </span>
                <span id="execution-results-POSTapi-projects--project_id--server-providers" hidden>
                    <blockquote>
                        Received response
                        <span id="execution-response-status-POSTapi-projects--project_id--server-providers"></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-POSTapi-projects--project_id--server-providers"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-POSTapi-projects--project_id--server-providers" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-POSTapi-projects--project_id--server-providers">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-POSTapi-projects--project_id--server-providers"
                    data-method="POST"
                    data-path="api/projects/{project_id}/server-providers"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('POSTapi-projects--project_id--server-providers', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-POSTapi-projects--project_id--server-providers"
                            onclick="tryItOut('POSTapi-projects--project_id--server-providers');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-POSTapi-projects--project_id--server-providers"
                            onclick="cancelTryOut('POSTapi-projects--project_id--server-providers');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-POSTapi-projects--project_id--server-providers"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-black">POST</small>
                        <b><code>api/projects/{project_id}/server-providers</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="POSTapi-projects--project_id--server-providers"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="POSTapi-projects--project_id--server-providers"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="POSTapi-projects--project_id--server-providers"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>provider</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="provider"
                            data-endpoint="POSTapi-projects--project_id--server-providers"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            The provider (aws, linode, hetzner, digitalocean, vultr, ...) Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>name</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="name"
                            data-endpoint="POSTapi-projects--project_id--server-providers"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            The name of the server provider. Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>token</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="token"
                            data-endpoint="POSTapi-projects--project_id--server-providers"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            The token if provider requires api token Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>key</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="key"
                            data-endpoint="POSTapi-projects--project_id--server-providers"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            The key if provider requires key Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>secret</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="secret"
                            data-endpoint="POSTapi-projects--project_id--server-providers"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            The secret if provider requires key Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                </form>

                <h2 id="server-providers-GETapi-projects--project_id--server-providers--serverProvider_id-">show</h2>

                <p></p>

                <span id="example-requests-GETapi-projects--project_id--server-providers--serverProvider_id-">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request GET \
    --get "https://vito.test/api/projects/1/server-providers/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/server-providers/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-GETapi-projects--project_id--server-providers--serverProvider_id-">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;id&quot;: 4,
    &quot;project_id&quot;: null,
    &quot;global&quot;: true,
    &quot;name&quot;: &quot;voluptatem&quot;,
    &quot;provider&quot;: &quot;vultr&quot;,
    &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
    &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
}</code>
 </pre>
                </span>
                <span id="execution-results-GETapi-projects--project_id--server-providers--serverProvider_id-" hidden>
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-GETapi-projects--project_id--server-providers--serverProvider_id-"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-GETapi-projects--project_id--server-providers--serverProvider_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-GETapi-projects--project_id--server-providers--serverProvider_id-" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-GETapi-projects--project_id--server-providers--serverProvider_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-GETapi-projects--project_id--server-providers--serverProvider_id-"
                    data-method="GET"
                    data-path="api/projects/{project_id}/server-providers/{serverProvider_id}"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('GETapi-projects--project_id--server-providers--serverProvider_id-', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-GETapi-projects--project_id--server-providers--serverProvider_id-"
                            onclick="tryItOut('GETapi-projects--project_id--server-providers--serverProvider_id-');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-GETapi-projects--project_id--server-providers--serverProvider_id-"
                            onclick="cancelTryOut('GETapi-projects--project_id--server-providers--serverProvider_id-');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-GETapi-projects--project_id--server-providers--serverProvider_id-"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-green">GET</small>
                        <b><code>api/projects/{project_id}/server-providers/{serverProvider_id}</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="GETapi-projects--project_id--server-providers--serverProvider_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="GETapi-projects--project_id--server-providers--serverProvider_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="GETapi-projects--project_id--server-providers--serverProvider_id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>serverProvider_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="serverProvider_id"
                            data-endpoint="GETapi-projects--project_id--server-providers--serverProvider_id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the serverProvider. Example:
                            <code>1</code>
                        </p>
                    </div>
                </form>

                <h2 id="server-providers-PUTapi-projects--project_id--server-providers--serverProvider_id-">update</h2>

                <p></p>

                <span id="example-requests-PUTapi-projects--project_id--server-providers--serverProvider_id-">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request PUT \
    "https://vito.test/api/projects/1/server-providers/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"consequatur\",
    \"global\": false
}"
</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/server-providers/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "consequatur",
    "global": false
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-PUTapi-projects--project_id--server-providers--serverProvider_id-">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;id&quot;: 4,
    &quot;project_id&quot;: null,
    &quot;global&quot;: true,
    &quot;name&quot;: &quot;dolores&quot;,
    &quot;provider&quot;: &quot;digitalocean&quot;,
    &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
    &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
}</code>
 </pre>
                </span>
                <span id="execution-results-PUTapi-projects--project_id--server-providers--serverProvider_id-" hidden>
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-PUTapi-projects--project_id--server-providers--serverProvider_id-"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-PUTapi-projects--project_id--server-providers--serverProvider_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-PUTapi-projects--project_id--server-providers--serverProvider_id-" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-PUTapi-projects--project_id--server-providers--serverProvider_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-PUTapi-projects--project_id--server-providers--serverProvider_id-"
                    data-method="PUT"
                    data-path="api/projects/{project_id}/server-providers/{serverProvider_id}"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('PUTapi-projects--project_id--server-providers--serverProvider_id-', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-PUTapi-projects--project_id--server-providers--serverProvider_id-"
                            onclick="tryItOut('PUTapi-projects--project_id--server-providers--serverProvider_id-');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-PUTapi-projects--project_id--server-providers--serverProvider_id-"
                            onclick="cancelTryOut('PUTapi-projects--project_id--server-providers--serverProvider_id-');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-PUTapi-projects--project_id--server-providers--serverProvider_id-"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-darkblue">PUT</small>
                        <b><code>api/projects/{project_id}/server-providers/{serverProvider_id}</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="PUTapi-projects--project_id--server-providers--serverProvider_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="PUTapi-projects--project_id--server-providers--serverProvider_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="PUTapi-projects--project_id--server-providers--serverProvider_id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>serverProvider_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="serverProvider_id"
                            data-endpoint="PUTapi-projects--project_id--server-providers--serverProvider_id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the serverProvider. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>name</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="name"
                            data-endpoint="PUTapi-projects--project_id--server-providers--serverProvider_id-"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            The name of the server provider. Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>global</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="global"
                            data-endpoint="PUTapi-projects--project_id--server-providers--serverProvider_id-"
                            value=""
                            data-component="body"
                        />
                        <br />
                        <p>
                            Accessible in all projects Example:
                            <code>false</code>
                        </p>
                        Must be one of:
                        <ul style="list-style-type: square">
                            <li><code>1</code></li>
                            <li><code></code></li>
                        </ul>
                    </div>
                </form>

                <h2 id="server-providers-DELETEapi-projects--project_id--server-providers--serverProvider_id-">
                    delete
                </h2>

                <p></p>

                <span id="example-requests-DELETEapi-projects--project_id--server-providers--serverProvider_id-">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request DELETE \
    "https://vito.test/api/projects/1/server-providers/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/server-providers/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-DELETEapi-projects--project_id--server-providers--serverProvider_id-">
                    <blockquote>
                        <p>Example response (204):</p>
                    </blockquote>
                    <pre>
<code>Empty response</code>
 </pre>
                </span>
                <span
                    id="execution-results-DELETEapi-projects--project_id--server-providers--serverProvider_id-"
                    hidden
                >
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-DELETEapi-projects--project_id--server-providers--serverProvider_id-"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-DELETEapi-projects--project_id--server-providers--serverProvider_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-DELETEapi-projects--project_id--server-providers--serverProvider_id-" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-DELETEapi-projects--project_id--server-providers--serverProvider_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-DELETEapi-projects--project_id--server-providers--serverProvider_id-"
                    data-method="DELETE"
                    data-path="api/projects/{project_id}/server-providers/{serverProvider_id}"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('DELETEapi-projects--project_id--server-providers--serverProvider_id-', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-DELETEapi-projects--project_id--server-providers--serverProvider_id-"
                            onclick="tryItOut('DELETEapi-projects--project_id--server-providers--serverProvider_id-');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-DELETEapi-projects--project_id--server-providers--serverProvider_id-"
                            onclick="cancelTryOut('DELETEapi-projects--project_id--server-providers--serverProvider_id-');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-DELETEapi-projects--project_id--server-providers--serverProvider_id-"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-red">DELETE</small>
                        <b><code>api/projects/{project_id}/server-providers/{serverProvider_id}</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="DELETEapi-projects--project_id--server-providers--serverProvider_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="DELETEapi-projects--project_id--server-providers--serverProvider_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="DELETEapi-projects--project_id--server-providers--serverProvider_id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>serverProvider_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="serverProvider_id"
                            data-endpoint="DELETEapi-projects--project_id--server-providers--serverProvider_id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the serverProvider. Example:
                            <code>1</code>
                        </p>
                    </div>
                </form>

                <h1 id="servers">servers</h1>

                <h2 id="servers-GETapi-projects--project_id--servers">list</h2>

                <p></p>

                <p>Get all servers in a project.</p>

                <span id="example-requests-GETapi-projects--project_id--servers">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request GET \
    --get "https://vito.test/api/projects/1/servers" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-GETapi-projects--project_id--servers">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: 33,
            &quot;project_id&quot;: 1,
            &quot;user_id&quot;: 1,
            &quot;provider_id&quot;: null,
            &quot;name&quot;: &quot;Maiya Connelly&quot;,
            &quot;ssh_user&quot;: &quot;vito&quot;,
            &quot;ip&quot;: &quot;7.83.102.177&quot;,
            &quot;local_ip&quot;: &quot;130.245.181.91&quot;,
            &quot;port&quot;: 22,
            &quot;os&quot;: &quot;ubuntu_22&quot;,
            &quot;type&quot;: &quot;regular&quot;,
            &quot;type_data&quot;: null,
            &quot;provider&quot;: &quot;custom&quot;,
            &quot;provider_data&quot;: null,
            &quot;public_key&quot;: &quot;test&quot;,
            &quot;status&quot;: &quot;ready&quot;,
            &quot;auto_update&quot;: null,
            &quot;available_updates&quot;: 0,
            &quot;security_updates&quot;: null,
            &quot;progress&quot;: 100,
            &quot;progress_step&quot;: null,
            &quot;updates&quot;: 0,
            &quot;last_update_check&quot;: null,
            &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
        },
        {
            &quot;id&quot;: 34,
            &quot;project_id&quot;: 1,
            &quot;user_id&quot;: 1,
            &quot;provider_id&quot;: null,
            &quot;name&quot;: &quot;Dr. Kyler Runolfsdottir DVM&quot;,
            &quot;ssh_user&quot;: &quot;vito&quot;,
            &quot;ip&quot;: &quot;106.112.51.73&quot;,
            &quot;local_ip&quot;: &quot;248.246.77.93&quot;,
            &quot;port&quot;: 22,
            &quot;os&quot;: &quot;ubuntu_22&quot;,
            &quot;type&quot;: &quot;regular&quot;,
            &quot;type_data&quot;: null,
            &quot;provider&quot;: &quot;custom&quot;,
            &quot;provider_data&quot;: null,
            &quot;public_key&quot;: &quot;test&quot;,
            &quot;status&quot;: &quot;ready&quot;,
            &quot;auto_update&quot;: null,
            &quot;available_updates&quot;: 0,
            &quot;security_updates&quot;: null,
            &quot;progress&quot;: 100,
            &quot;progress_step&quot;: null,
            &quot;updates&quot;: 0,
            &quot;last_update_check&quot;: null,
            &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
        }
    ],
    &quot;links&quot;: {
        &quot;first&quot;: &quot;/?page=1&quot;,
        &quot;last&quot;: &quot;/?page=1&quot;,
        &quot;prev&quot;: null,
        &quot;next&quot;: null
    },
    &quot;meta&quot;: {
        &quot;current_page&quot;: 1,
        &quot;from&quot;: 1,
        &quot;last_page&quot;: 1,
        &quot;links&quot;: [
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;&amp;laquo; Previous&quot;,
                &quot;active&quot;: false
            },
            {
                &quot;url&quot;: &quot;/?page=1&quot;,
                &quot;label&quot;: &quot;1&quot;,
                &quot;active&quot;: true
            },
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;Next &amp;raquo;&quot;,
                &quot;active&quot;: false
            }
        ],
        &quot;path&quot;: &quot;/&quot;,
        &quot;per_page&quot;: 25,
        &quot;to&quot;: 2,
        &quot;total&quot;: 2
    }
}</code>
 </pre>
                </span>
                <span id="execution-results-GETapi-projects--project_id--servers" hidden>
                    <blockquote>
                        Received response
                        <span id="execution-response-status-GETapi-projects--project_id--servers"></span>
                        :
                    </blockquote>
                    <pre class="json"><code id="execution-response-content-GETapi-projects--project_id--servers"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-GETapi-projects--project_id--servers" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-GETapi-projects--project_id--servers">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-GETapi-projects--project_id--servers"
                    data-method="GET"
                    data-path="api/projects/{project_id}/servers"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('GETapi-projects--project_id--servers', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-GETapi-projects--project_id--servers"
                            onclick="tryItOut('GETapi-projects--project_id--servers');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-GETapi-projects--project_id--servers"
                            onclick="cancelTryOut('GETapi-projects--project_id--servers');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-GETapi-projects--project_id--servers"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-green">GET</small>
                        <b><code>api/projects/{project_id}/servers</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="GETapi-projects--project_id--servers"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="GETapi-projects--project_id--servers"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="GETapi-projects--project_id--servers"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                </form>

                <h2 id="servers-POSTapi-projects--project_id--servers">create</h2>

                <p></p>

                <p>Create a new server.</p>

                <span id="example-requests-POSTapi-projects--project_id--servers">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request POST \
    "https://vito.test/api/projects/1/servers" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"provider\": \"consequatur\",
    \"server_provider\": \"hetzner\",
    \"region\": \"consequatur\",
    \"plan\": \"consequatur\",
    \"ip\": \"consequatur\",
    \"port\": \"consequatur\",
    \"name\": \"consequatur\",
    \"os\": \"consequatur\",
    \"webserver\": \"none\",
    \"database\": \"mariadb104\",
    \"php\": \"8.0\"
}"
</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "provider": "consequatur",
    "server_provider": "hetzner",
    "region": "consequatur",
    "plan": "consequatur",
    "ip": "consequatur",
    "port": "consequatur",
    "name": "consequatur",
    "os": "consequatur",
    "webserver": "none",
    "database": "mariadb104",
    "php": "8.0"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-POSTapi-projects--project_id--servers">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;id&quot;: 33,
    &quot;project_id&quot;: 1,
    &quot;user_id&quot;: 1,
    &quot;provider_id&quot;: null,
    &quot;name&quot;: &quot;Dr. Cornelius Luettgen V&quot;,
    &quot;ssh_user&quot;: &quot;vito&quot;,
    &quot;ip&quot;: &quot;226.187.235.251&quot;,
    &quot;local_ip&quot;: &quot;18.62.212.253&quot;,
    &quot;port&quot;: 22,
    &quot;os&quot;: &quot;ubuntu_22&quot;,
    &quot;type&quot;: &quot;regular&quot;,
    &quot;type_data&quot;: null,
    &quot;provider&quot;: &quot;custom&quot;,
    &quot;provider_data&quot;: null,
    &quot;public_key&quot;: &quot;test&quot;,
    &quot;status&quot;: &quot;ready&quot;,
    &quot;auto_update&quot;: null,
    &quot;available_updates&quot;: 0,
    &quot;security_updates&quot;: null,
    &quot;progress&quot;: 100,
    &quot;progress_step&quot;: null,
    &quot;updates&quot;: 0,
    &quot;last_update_check&quot;: null,
    &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
    &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
}</code>
 </pre>
                </span>
                <span id="execution-results-POSTapi-projects--project_id--servers" hidden>
                    <blockquote>
                        Received response
                        <span id="execution-response-status-POSTapi-projects--project_id--servers"></span>
                        :
                    </blockquote>
                    <pre class="json"><code id="execution-response-content-POSTapi-projects--project_id--servers"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-POSTapi-projects--project_id--servers" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-POSTapi-projects--project_id--servers">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-POSTapi-projects--project_id--servers"
                    data-method="POST"
                    data-path="api/projects/{project_id}/servers"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('POSTapi-projects--project_id--servers', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-POSTapi-projects--project_id--servers"
                            onclick="tryItOut('POSTapi-projects--project_id--servers');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-POSTapi-projects--project_id--servers"
                            onclick="cancelTryOut('POSTapi-projects--project_id--servers');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-POSTapi-projects--project_id--servers"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-black">POST</small>
                        <b><code>api/projects/{project_id}/servers</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="POSTapi-projects--project_id--servers"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="POSTapi-projects--project_id--servers"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="POSTapi-projects--project_id--servers"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>provider</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="provider"
                            data-endpoint="POSTapi-projects--project_id--servers"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            The server provider type Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_provider</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="server_provider"
                            data-endpoint="POSTapi-projects--project_id--servers"
                            value="hetzner"
                            data-component="body"
                        />
                        <br />
                        <p>
                            If the provider is not custom, the ID of the server provider profile Example:
                            <code>hetzner</code>
                        </p>
                        Must be one of:
                        <ul style="list-style-type: square">
                            <li><code>custom</code></li>
                            <li><code>hetzner</code></li>
                            <li><code>digitalocean</code></li>
                            <li><code>linode</code></li>
                            <li><code>vultr</code></li>
                        </ul>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>region</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="region"
                            data-endpoint="POSTapi-projects--project_id--servers"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Provider region if the provider is not custom Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>plan</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="plan"
                            data-endpoint="POSTapi-projects--project_id--servers"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Provider plan if the provider is not custom Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>ip</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="ip"
                            data-endpoint="POSTapi-projects--project_id--servers"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            SSH IP address if the provider is custom Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>port</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="port"
                            data-endpoint="POSTapi-projects--project_id--servers"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            SSH Port if the provider is custom Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>name</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="name"
                            data-endpoint="POSTapi-projects--project_id--servers"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            The name of the server. Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>os</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="os"
                            data-endpoint="POSTapi-projects--project_id--servers"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            The os of the server Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>webserver</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="webserver"
                            data-endpoint="POSTapi-projects--project_id--servers"
                            value="none"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Web server Example:
                            <code>none</code>
                        </p>
                        Must be one of:
                        <ul style="list-style-type: square">
                            <li><code>none</code></li>
                            <li><code>nginx</code></li>
                        </ul>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>database</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="database"
                            data-endpoint="POSTapi-projects--project_id--servers"
                            value="mariadb104"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Database Example:
                            <code>mariadb104</code>
                        </p>
                        Must be one of:
                        <ul style="list-style-type: square">
                            <li><code>none</code></li>
                            <li><code>mysql57</code></li>
                            <li><code>mysql80</code></li>
                            <li><code>mariadb103</code></li>
                            <li><code>mariadb104</code></li>
                            <li><code>mariadb103</code></li>
                            <li><code>postgresql12</code></li>
                            <li><code>postgresql13</code></li>
                            <li><code>postgresql14</code></li>
                            <li><code>postgresql15</code></li>
                            <li><code>postgresql16</code></li>
                        </ul>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>php</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="php"
                            data-endpoint="POSTapi-projects--project_id--servers"
                            value="8.0"
                            data-component="body"
                        />
                        <br />
                        <p>
                            PHP version Example:
                            <code>8.0</code>
                        </p>
                        Must be one of:
                        <ul style="list-style-type: square">
                            <li><code>7.0</code></li>
                            <li><code>7.1</code></li>
                            <li><code>7.2</code></li>
                            <li><code>7.3</code></li>
                            <li><code>7.4</code></li>
                            <li><code>8.0</code></li>
                            <li><code>8.1</code></li>
                            <li><code>8.2</code></li>
                            <li><code>8.3</code></li>
                        </ul>
                    </div>
                </form>

                <h2 id="servers-GETapi-projects--project_id--servers--id-">show</h2>

                <p></p>

                <p>Get a server by ID.</p>

                <span id="example-requests-GETapi-projects--project_id--servers--id-">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request GET \
    --get "https://vito.test/api/projects/1/servers/32" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-GETapi-projects--project_id--servers--id-">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;id&quot;: 33,
    &quot;project_id&quot;: 1,
    &quot;user_id&quot;: 1,
    &quot;provider_id&quot;: null,
    &quot;name&quot;: &quot;Brandy Reichel&quot;,
    &quot;ssh_user&quot;: &quot;vito&quot;,
    &quot;ip&quot;: &quot;26.180.121.142&quot;,
    &quot;local_ip&quot;: &quot;122.175.6.215&quot;,
    &quot;port&quot;: 22,
    &quot;os&quot;: &quot;ubuntu_22&quot;,
    &quot;type&quot;: &quot;regular&quot;,
    &quot;type_data&quot;: null,
    &quot;provider&quot;: &quot;custom&quot;,
    &quot;provider_data&quot;: null,
    &quot;public_key&quot;: &quot;test&quot;,
    &quot;status&quot;: &quot;ready&quot;,
    &quot;auto_update&quot;: null,
    &quot;available_updates&quot;: 0,
    &quot;security_updates&quot;: null,
    &quot;progress&quot;: 100,
    &quot;progress_step&quot;: null,
    &quot;updates&quot;: 0,
    &quot;last_update_check&quot;: null,
    &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
    &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
}</code>
 </pre>
                </span>
                <span id="execution-results-GETapi-projects--project_id--servers--id-" hidden>
                    <blockquote>
                        Received response
                        <span id="execution-response-status-GETapi-projects--project_id--servers--id-"></span>
                        :
                    </blockquote>
                    <pre class="json"><code id="execution-response-content-GETapi-projects--project_id--servers--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-GETapi-projects--project_id--servers--id-" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-GETapi-projects--project_id--servers--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-GETapi-projects--project_id--servers--id-"
                    data-method="GET"
                    data-path="api/projects/{project_id}/servers/{id}"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('GETapi-projects--project_id--servers--id-', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-GETapi-projects--project_id--servers--id-"
                            onclick="tryItOut('GETapi-projects--project_id--servers--id-');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-GETapi-projects--project_id--servers--id-"
                            onclick="cancelTryOut('GETapi-projects--project_id--servers--id-');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-GETapi-projects--project_id--servers--id-"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-green">GET</small>
                        <b><code>api/projects/{project_id}/servers/{id}</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="GETapi-projects--project_id--servers--id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="GETapi-projects--project_id--servers--id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="GETapi-projects--project_id--servers--id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="id"
                            data-endpoint="GETapi-projects--project_id--servers--id-"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                </form>

                <h2 id="servers-POSTapi-projects--project_id--servers--server_id--reboot">reboot</h2>

                <p></p>

                <p>Reboot a server.</p>

                <span id="example-requests-POSTapi-projects--project_id--servers--server_id--reboot">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request POST \
    "https://vito.test/api/projects/1/servers/32/reboot" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/reboot"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-POSTapi-projects--project_id--servers--server_id--reboot">
                    <blockquote>
                        <p>Example response (204):</p>
                    </blockquote>
                    <pre>
<code>Empty response</code>
 </pre>
                </span>
                <span id="execution-results-POSTapi-projects--project_id--servers--server_id--reboot" hidden>
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-POSTapi-projects--project_id--servers--server_id--reboot"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-POSTapi-projects--project_id--servers--server_id--reboot"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-POSTapi-projects--project_id--servers--server_id--reboot" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-POSTapi-projects--project_id--servers--server_id--reboot">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-POSTapi-projects--project_id--servers--server_id--reboot"
                    data-method="POST"
                    data-path="api/projects/{project_id}/servers/{server_id}/reboot"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('POSTapi-projects--project_id--servers--server_id--reboot', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-POSTapi-projects--project_id--servers--server_id--reboot"
                            onclick="tryItOut('POSTapi-projects--project_id--servers--server_id--reboot');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-POSTapi-projects--project_id--servers--server_id--reboot"
                            onclick="cancelTryOut('POSTapi-projects--project_id--servers--server_id--reboot');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-POSTapi-projects--project_id--servers--server_id--reboot"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-black">POST</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/reboot</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--reboot"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--reboot"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--reboot"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--reboot"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                </form>

                <h2 id="servers-POSTapi-projects--project_id--servers--server_id--upgrade">upgrade</h2>

                <p></p>

                <p>Upgrade server.</p>

                <span id="example-requests-POSTapi-projects--project_id--servers--server_id--upgrade">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request POST \
    "https://vito.test/api/projects/1/servers/32/upgrade" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/upgrade"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-POSTapi-projects--project_id--servers--server_id--upgrade">
                    <blockquote>
                        <p>Example response (204):</p>
                    </blockquote>
                    <pre>
<code>Empty response</code>
 </pre>
                </span>
                <span id="execution-results-POSTapi-projects--project_id--servers--server_id--upgrade" hidden>
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-POSTapi-projects--project_id--servers--server_id--upgrade"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-POSTapi-projects--project_id--servers--server_id--upgrade"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-POSTapi-projects--project_id--servers--server_id--upgrade" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-POSTapi-projects--project_id--servers--server_id--upgrade">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-POSTapi-projects--project_id--servers--server_id--upgrade"
                    data-method="POST"
                    data-path="api/projects/{project_id}/servers/{server_id}/upgrade"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('POSTapi-projects--project_id--servers--server_id--upgrade', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-POSTapi-projects--project_id--servers--server_id--upgrade"
                            onclick="tryItOut('POSTapi-projects--project_id--servers--server_id--upgrade');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-POSTapi-projects--project_id--servers--server_id--upgrade"
                            onclick="cancelTryOut('POSTapi-projects--project_id--servers--server_id--upgrade');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-POSTapi-projects--project_id--servers--server_id--upgrade"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-black">POST</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/upgrade</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--upgrade"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--upgrade"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--upgrade"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--upgrade"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                </form>

                <h2 id="servers-DELETEapi-projects--project_id--servers--server_id-">delete</h2>

                <p></p>

                <p>Delete server.</p>

                <span id="example-requests-DELETEapi-projects--project_id--servers--server_id-">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request DELETE \
    "https://vito.test/api/projects/1/servers/32" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-DELETEapi-projects--project_id--servers--server_id-">
                    <blockquote>
                        <p>Example response (204):</p>
                    </blockquote>
                    <pre>
<code>Empty response</code>
 </pre>
                </span>
                <span id="execution-results-DELETEapi-projects--project_id--servers--server_id-" hidden>
                    <blockquote>
                        Received response
                        <span id="execution-response-status-DELETEapi-projects--project_id--servers--server_id-"></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-DELETEapi-projects--project_id--servers--server_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-DELETEapi-projects--project_id--servers--server_id-" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-DELETEapi-projects--project_id--servers--server_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-DELETEapi-projects--project_id--servers--server_id-"
                    data-method="DELETE"
                    data-path="api/projects/{project_id}/servers/{server_id}"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('DELETEapi-projects--project_id--servers--server_id-', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-DELETEapi-projects--project_id--servers--server_id-"
                            onclick="tryItOut('DELETEapi-projects--project_id--servers--server_id-');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-DELETEapi-projects--project_id--servers--server_id-"
                            onclick="cancelTryOut('DELETEapi-projects--project_id--servers--server_id-');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-DELETEapi-projects--project_id--servers--server_id-"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-red">DELETE</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id-"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                </form>

                <h1 id="services">services</h1>

                <h2 id="services-GETapi-projects--project_id--servers--server_id--services">list</h2>

                <p></p>

                <p>Get all services.</p>

                <span id="example-requests-GETapi-projects--project_id--servers--server_id--services">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request GET \
    --get "https://vito.test/api/projects/1/servers/32/services" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/services"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-GETapi-projects--project_id--servers--server_id--services">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: null,
            &quot;server_id&quot;: 1,
            &quot;type&quot;: &quot;webserver&quot;,
            &quot;type_data&quot;: null,
            &quot;name&quot;: &quot;nginx&quot;,
            &quot;version&quot;: null,
            &quot;unit&quot;: null,
            &quot;status&quot;: &quot;ready&quot;,
            &quot;is_default&quot;: null,
            &quot;created_at&quot;: null,
            &quot;updated_at&quot;: null
        },
        {
            &quot;id&quot;: null,
            &quot;server_id&quot;: 1,
            &quot;type&quot;: &quot;webserver&quot;,
            &quot;type_data&quot;: null,
            &quot;name&quot;: &quot;nginx&quot;,
            &quot;version&quot;: null,
            &quot;unit&quot;: null,
            &quot;status&quot;: &quot;ready&quot;,
            &quot;is_default&quot;: null,
            &quot;created_at&quot;: null,
            &quot;updated_at&quot;: null
        }
    ],
    &quot;links&quot;: {
        &quot;first&quot;: &quot;/?page=1&quot;,
        &quot;last&quot;: &quot;/?page=1&quot;,
        &quot;prev&quot;: null,
        &quot;next&quot;: null
    },
    &quot;meta&quot;: {
        &quot;current_page&quot;: 1,
        &quot;from&quot;: 1,
        &quot;last_page&quot;: 1,
        &quot;links&quot;: [
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;&amp;laquo; Previous&quot;,
                &quot;active&quot;: false
            },
            {
                &quot;url&quot;: &quot;/?page=1&quot;,
                &quot;label&quot;: &quot;1&quot;,
                &quot;active&quot;: true
            },
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;Next &amp;raquo;&quot;,
                &quot;active&quot;: false
            }
        ],
        &quot;path&quot;: &quot;/&quot;,
        &quot;per_page&quot;: 25,
        &quot;to&quot;: 2,
        &quot;total&quot;: 2
    }
}</code>
 </pre>
                </span>
                <span id="execution-results-GETapi-projects--project_id--servers--server_id--services" hidden>
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-GETapi-projects--project_id--servers--server_id--services"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-GETapi-projects--project_id--servers--server_id--services"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-GETapi-projects--project_id--servers--server_id--services" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-GETapi-projects--project_id--servers--server_id--services">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-GETapi-projects--project_id--servers--server_id--services"
                    data-method="GET"
                    data-path="api/projects/{project_id}/servers/{server_id}/services"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('GETapi-projects--project_id--servers--server_id--services', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-GETapi-projects--project_id--servers--server_id--services"
                            onclick="tryItOut('GETapi-projects--project_id--servers--server_id--services');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-GETapi-projects--project_id--servers--server_id--services"
                            onclick="cancelTryOut('GETapi-projects--project_id--servers--server_id--services');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-GETapi-projects--project_id--servers--server_id--services"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-green">GET</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/services</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--services"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--services"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--services"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--services"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                </form>

                <h2 id="services-GETapi-projects--project_id--servers--server_id--services--id-">show</h2>

                <p></p>

                <p>Get a service by ID.</p>

                <span id="example-requests-GETapi-projects--project_id--servers--server_id--services--id-">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request GET \
    --get "https://vito.test/api/projects/1/servers/32/services/184" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/services/184"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-GETapi-projects--project_id--servers--server_id--services--id-">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;id&quot;: null,
    &quot;server_id&quot;: 1,
    &quot;type&quot;: &quot;webserver&quot;,
    &quot;type_data&quot;: null,
    &quot;name&quot;: &quot;nginx&quot;,
    &quot;version&quot;: null,
    &quot;unit&quot;: null,
    &quot;status&quot;: &quot;ready&quot;,
    &quot;is_default&quot;: null,
    &quot;created_at&quot;: null,
    &quot;updated_at&quot;: null
}</code>
 </pre>
                </span>
                <span id="execution-results-GETapi-projects--project_id--servers--server_id--services--id-" hidden>
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-GETapi-projects--project_id--servers--server_id--services--id-"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-GETapi-projects--project_id--servers--server_id--services--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-GETapi-projects--project_id--servers--server_id--services--id-" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-GETapi-projects--project_id--servers--server_id--services--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-GETapi-projects--project_id--servers--server_id--services--id-"
                    data-method="GET"
                    data-path="api/projects/{project_id}/servers/{server_id}/services/{id}"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('GETapi-projects--project_id--servers--server_id--services--id-', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-GETapi-projects--project_id--servers--server_id--services--id-"
                            onclick="tryItOut('GETapi-projects--project_id--servers--server_id--services--id-');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-GETapi-projects--project_id--servers--server_id--services--id-"
                            onclick="cancelTryOut('GETapi-projects--project_id--servers--server_id--services--id-');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-GETapi-projects--project_id--servers--server_id--services--id-"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-green">GET</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/services/{id}</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--services--id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--services--id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--services--id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--services--id-"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--services--id-"
                            value="184"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the service. Example:
                            <code>184</code>
                        </p>
                    </div>
                </form>

                <h2 id="services-POSTapi-projects--project_id--servers--server_id--services--service_id--start">
                    start
                </h2>

                <p></p>

                <p>Start service.</p>

                <span
                    id="example-requests-POSTapi-projects--project_id--servers--server_id--services--service_id--start"
                >
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request POST \
    "https://vito.test/api/projects/1/servers/32/services/184/start" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/services/184/start"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span
                    id="example-responses-POSTapi-projects--project_id--servers--server_id--services--service_id--start"
                >
                    <blockquote>
                        <p>Example response (204):</p>
                    </blockquote>
                    <pre>
<code>Empty response</code>
 </pre>
                </span>
                <span
                    id="execution-results-POSTapi-projects--project_id--servers--server_id--services--service_id--start"
                    hidden
                >
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-POSTapi-projects--project_id--servers--server_id--services--service_id--start"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-POSTapi-projects--project_id--servers--server_id--services--service_id--start"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span
                    id="execution-error-POSTapi-projects--project_id--servers--server_id--services--service_id--start"
                    hidden
                >
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-POSTapi-projects--project_id--servers--server_id--services--service_id--start">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-POSTapi-projects--project_id--servers--server_id--services--service_id--start"
                    data-method="POST"
                    data-path="api/projects/{project_id}/servers/{server_id}/services/{service_id}/start"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('POSTapi-projects--project_id--servers--server_id--services--service_id--start', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-POSTapi-projects--project_id--servers--server_id--services--service_id--start"
                            onclick="tryItOut('POSTapi-projects--project_id--servers--server_id--services--service_id--start');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-POSTapi-projects--project_id--servers--server_id--services--service_id--start"
                            onclick="cancelTryOut('POSTapi-projects--project_id--servers--server_id--services--service_id--start');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-POSTapi-projects--project_id--servers--server_id--services--service_id--start"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-black">POST</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/services/{service_id}/start</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--services--service_id--start"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--services--service_id--start"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--services--service_id--start"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--services--service_id--start"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>service_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="service_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--services--service_id--start"
                            value="184"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the service. Example:
                            <code>184</code>
                        </p>
                    </div>
                </form>

                <h2 id="services-POSTapi-projects--project_id--servers--server_id--services--service_id--stop">stop</h2>

                <p></p>

                <p>Stop service.</p>

                <span
                    id="example-requests-POSTapi-projects--project_id--servers--server_id--services--service_id--stop"
                >
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request POST \
    "https://vito.test/api/projects/1/servers/32/services/184/stop" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/services/184/stop"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span
                    id="example-responses-POSTapi-projects--project_id--servers--server_id--services--service_id--stop"
                >
                    <blockquote>
                        <p>Example response (204):</p>
                    </blockquote>
                    <pre>
<code>Empty response</code>
 </pre>
                </span>
                <span
                    id="execution-results-POSTapi-projects--project_id--servers--server_id--services--service_id--stop"
                    hidden
                >
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-POSTapi-projects--project_id--servers--server_id--services--service_id--stop"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-POSTapi-projects--project_id--servers--server_id--services--service_id--stop"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span
                    id="execution-error-POSTapi-projects--project_id--servers--server_id--services--service_id--stop"
                    hidden
                >
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-POSTapi-projects--project_id--servers--server_id--services--service_id--stop">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-POSTapi-projects--project_id--servers--server_id--services--service_id--stop"
                    data-method="POST"
                    data-path="api/projects/{project_id}/servers/{server_id}/services/{service_id}/stop"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('POSTapi-projects--project_id--servers--server_id--services--service_id--stop', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-POSTapi-projects--project_id--servers--server_id--services--service_id--stop"
                            onclick="tryItOut('POSTapi-projects--project_id--servers--server_id--services--service_id--stop');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-POSTapi-projects--project_id--servers--server_id--services--service_id--stop"
                            onclick="cancelTryOut('POSTapi-projects--project_id--servers--server_id--services--service_id--stop');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-POSTapi-projects--project_id--servers--server_id--services--service_id--stop"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-black">POST</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/services/{service_id}/stop</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--services--service_id--stop"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--services--service_id--stop"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--services--service_id--stop"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--services--service_id--stop"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>service_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="service_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--services--service_id--stop"
                            value="184"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the service. Example:
                            <code>184</code>
                        </p>
                    </div>
                </form>

                <h2 id="services-POSTapi-projects--project_id--servers--server_id--services--service_id--restart">
                    restart
                </h2>

                <p></p>

                <p>Restart service.</p>

                <span
                    id="example-requests-POSTapi-projects--project_id--servers--server_id--services--service_id--restart"
                >
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request POST \
    "https://vito.test/api/projects/1/servers/32/services/184/restart" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/services/184/restart"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span
                    id="example-responses-POSTapi-projects--project_id--servers--server_id--services--service_id--restart"
                >
                    <blockquote>
                        <p>Example response (204):</p>
                    </blockquote>
                    <pre>
<code>Empty response</code>
 </pre>
                </span>
                <span
                    id="execution-results-POSTapi-projects--project_id--servers--server_id--services--service_id--restart"
                    hidden
                >
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-POSTapi-projects--project_id--servers--server_id--services--service_id--restart"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-POSTapi-projects--project_id--servers--server_id--services--service_id--restart"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span
                    id="execution-error-POSTapi-projects--project_id--servers--server_id--services--service_id--restart"
                    hidden
                >
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-POSTapi-projects--project_id--servers--server_id--services--service_id--restart">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-POSTapi-projects--project_id--servers--server_id--services--service_id--restart"
                    data-method="POST"
                    data-path="api/projects/{project_id}/servers/{server_id}/services/{service_id}/restart"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('POSTapi-projects--project_id--servers--server_id--services--service_id--restart', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-POSTapi-projects--project_id--servers--server_id--services--service_id--restart"
                            onclick="tryItOut('POSTapi-projects--project_id--servers--server_id--services--service_id--restart');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-POSTapi-projects--project_id--servers--server_id--services--service_id--restart"
                            onclick="cancelTryOut('POSTapi-projects--project_id--servers--server_id--services--service_id--restart');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-POSTapi-projects--project_id--servers--server_id--services--service_id--restart"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-black">POST</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/services/{service_id}/restart</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--services--service_id--restart"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--services--service_id--restart"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--services--service_id--restart"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--services--service_id--restart"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>service_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="service_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--services--service_id--restart"
                            value="184"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the service. Example:
                            <code>184</code>
                        </p>
                    </div>
                </form>

                <h2 id="services-POSTapi-projects--project_id--servers--server_id--services--service_id--enable">
                    enable
                </h2>

                <p></p>

                <p>Enable service.</p>

                <span
                    id="example-requests-POSTapi-projects--project_id--servers--server_id--services--service_id--enable"
                >
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request POST \
    "https://vito.test/api/projects/1/servers/32/services/184/enable" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/services/184/enable"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span
                    id="example-responses-POSTapi-projects--project_id--servers--server_id--services--service_id--enable"
                >
                    <blockquote>
                        <p>Example response (204):</p>
                    </blockquote>
                    <pre>
<code>Empty response</code>
 </pre>
                </span>
                <span
                    id="execution-results-POSTapi-projects--project_id--servers--server_id--services--service_id--enable"
                    hidden
                >
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-POSTapi-projects--project_id--servers--server_id--services--service_id--enable"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-POSTapi-projects--project_id--servers--server_id--services--service_id--enable"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span
                    id="execution-error-POSTapi-projects--project_id--servers--server_id--services--service_id--enable"
                    hidden
                >
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-POSTapi-projects--project_id--servers--server_id--services--service_id--enable">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-POSTapi-projects--project_id--servers--server_id--services--service_id--enable"
                    data-method="POST"
                    data-path="api/projects/{project_id}/servers/{server_id}/services/{service_id}/enable"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('POSTapi-projects--project_id--servers--server_id--services--service_id--enable', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-POSTapi-projects--project_id--servers--server_id--services--service_id--enable"
                            onclick="tryItOut('POSTapi-projects--project_id--servers--server_id--services--service_id--enable');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-POSTapi-projects--project_id--servers--server_id--services--service_id--enable"
                            onclick="cancelTryOut('POSTapi-projects--project_id--servers--server_id--services--service_id--enable');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-POSTapi-projects--project_id--servers--server_id--services--service_id--enable"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-black">POST</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/services/{service_id}/enable</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--services--service_id--enable"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--services--service_id--enable"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--services--service_id--enable"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--services--service_id--enable"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>service_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="service_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--services--service_id--enable"
                            value="184"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the service. Example:
                            <code>184</code>
                        </p>
                    </div>
                </form>

                <h2 id="services-POSTapi-projects--project_id--servers--server_id--services--service_id--disable">
                    disable
                </h2>

                <p></p>

                <p>Disable service.</p>

                <span
                    id="example-requests-POSTapi-projects--project_id--servers--server_id--services--service_id--disable"
                >
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request POST \
    "https://vito.test/api/projects/1/servers/32/services/184/disable" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/services/184/disable"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span
                    id="example-responses-POSTapi-projects--project_id--servers--server_id--services--service_id--disable"
                >
                    <blockquote>
                        <p>Example response (204):</p>
                    </blockquote>
                    <pre>
<code>Empty response</code>
 </pre>
                </span>
                <span
                    id="execution-results-POSTapi-projects--project_id--servers--server_id--services--service_id--disable"
                    hidden
                >
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-POSTapi-projects--project_id--servers--server_id--services--service_id--disable"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-POSTapi-projects--project_id--servers--server_id--services--service_id--disable"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span
                    id="execution-error-POSTapi-projects--project_id--servers--server_id--services--service_id--disable"
                    hidden
                >
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-POSTapi-projects--project_id--servers--server_id--services--service_id--disable">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-POSTapi-projects--project_id--servers--server_id--services--service_id--disable"
                    data-method="POST"
                    data-path="api/projects/{project_id}/servers/{server_id}/services/{service_id}/disable"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('POSTapi-projects--project_id--servers--server_id--services--service_id--disable', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-POSTapi-projects--project_id--servers--server_id--services--service_id--disable"
                            onclick="tryItOut('POSTapi-projects--project_id--servers--server_id--services--service_id--disable');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-POSTapi-projects--project_id--servers--server_id--services--service_id--disable"
                            onclick="cancelTryOut('POSTapi-projects--project_id--servers--server_id--services--service_id--disable');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-POSTapi-projects--project_id--servers--server_id--services--service_id--disable"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-black">POST</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/services/{service_id}/disable</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--services--service_id--disable"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--services--service_id--disable"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--services--service_id--disable"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--services--service_id--disable"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>service_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="service_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--services--service_id--disable"
                            value="184"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the service. Example:
                            <code>184</code>
                        </p>
                    </div>
                </form>

                <h2 id="services-DELETEapi-projects--project_id--servers--server_id--services--service_id-">delete</h2>

                <p></p>

                <p>Delete service.</p>

                <span id="example-requests-DELETEapi-projects--project_id--servers--server_id--services--service_id-">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request DELETE \
    "https://vito.test/api/projects/1/servers/32/services/184" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/services/184"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-DELETEapi-projects--project_id--servers--server_id--services--service_id-">
                    <blockquote>
                        <p>Example response (204):</p>
                    </blockquote>
                    <pre>
<code>Empty response</code>
 </pre>
                </span>
                <span
                    id="execution-results-DELETEapi-projects--project_id--servers--server_id--services--service_id-"
                    hidden
                >
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-DELETEapi-projects--project_id--servers--server_id--services--service_id-"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-DELETEapi-projects--project_id--servers--server_id--services--service_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span
                    id="execution-error-DELETEapi-projects--project_id--servers--server_id--services--service_id-"
                    hidden
                >
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-DELETEapi-projects--project_id--servers--server_id--services--service_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-DELETEapi-projects--project_id--servers--server_id--services--service_id-"
                    data-method="DELETE"
                    data-path="api/projects/{project_id}/servers/{server_id}/services/{service_id}"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('DELETEapi-projects--project_id--servers--server_id--services--service_id-', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-DELETEapi-projects--project_id--servers--server_id--services--service_id-"
                            onclick="tryItOut('DELETEapi-projects--project_id--servers--server_id--services--service_id-');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-DELETEapi-projects--project_id--servers--server_id--services--service_id-"
                            onclick="cancelTryOut('DELETEapi-projects--project_id--servers--server_id--services--service_id-');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-DELETEapi-projects--project_id--servers--server_id--services--service_id-"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-red">DELETE</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/services/{service_id}</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--services--service_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--services--service_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--services--service_id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--services--service_id-"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>service_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="service_id"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--services--service_id-"
                            value="184"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the service. Example:
                            <code>184</code>
                        </p>
                    </div>
                </form>

                <h1 id="sites">sites</h1>

                <h2 id="sites-GETapi-projects--project_id--servers--server_id--sites">list</h2>

                <p></p>

                <p>Get all sites.</p>

                <span id="example-requests-GETapi-projects--project_id--servers--server_id--sites">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request GET \
    --get "https://vito.test/api/projects/1/servers/32/sites" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/sites"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-GETapi-projects--project_id--servers--server_id--sites">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: 50,
            &quot;server_id&quot;: 1,
            &quot;source_control_id&quot;: null,
            &quot;type&quot;: &quot;laravel&quot;,
            &quot;type_data&quot;: null,
            &quot;domain&quot;: &quot;test.com&quot;,
            &quot;aliases&quot;: null,
            &quot;web_directory&quot;: &quot;/&quot;,
            &quot;path&quot;: &quot;/home&quot;,
            &quot;php_version&quot;: &quot;8.2&quot;,
            &quot;repository&quot;: null,
            &quot;branch&quot;: &quot;main&quot;,
            &quot;status&quot;: &quot;ready&quot;,
            &quot;port&quot;: null,
            &quot;user&quot;: &quot;vito&quot;,
            &quot;progress&quot;: 100,
            &quot;created_at&quot;: &quot;2025-04-21T18:40:20.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-04-21T18:40:20.000000Z&quot;
        },
        {
            &quot;id&quot;: 51,
            &quot;server_id&quot;: 1,
            &quot;source_control_id&quot;: null,
            &quot;type&quot;: &quot;laravel&quot;,
            &quot;type_data&quot;: null,
            &quot;domain&quot;: &quot;test.com&quot;,
            &quot;aliases&quot;: null,
            &quot;web_directory&quot;: &quot;/&quot;,
            &quot;path&quot;: &quot;/home&quot;,
            &quot;php_version&quot;: &quot;8.2&quot;,
            &quot;repository&quot;: null,
            &quot;branch&quot;: &quot;main&quot;,
            &quot;status&quot;: &quot;ready&quot;,
            &quot;port&quot;: null,
            &quot;user&quot;: &quot;vito&quot;,
            &quot;progress&quot;: 100,
            &quot;created_at&quot;: &quot;2025-04-21T18:40:20.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-04-21T18:40:20.000000Z&quot;
        }
    ],
    &quot;links&quot;: {
        &quot;first&quot;: &quot;/?page=1&quot;,
        &quot;last&quot;: &quot;/?page=1&quot;,
        &quot;prev&quot;: null,
        &quot;next&quot;: null
    },
    &quot;meta&quot;: {
        &quot;current_page&quot;: 1,
        &quot;from&quot;: 1,
        &quot;last_page&quot;: 1,
        &quot;links&quot;: [
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;&amp;laquo; Previous&quot;,
                &quot;active&quot;: false
            },
            {
                &quot;url&quot;: &quot;/?page=1&quot;,
                &quot;label&quot;: &quot;1&quot;,
                &quot;active&quot;: true
            },
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;Next &amp;raquo;&quot;,
                &quot;active&quot;: false
            }
        ],
        &quot;path&quot;: &quot;/&quot;,
        &quot;per_page&quot;: 25,
        &quot;to&quot;: 2,
        &quot;total&quot;: 2
    }
}</code>
 </pre>
                </span>
                <span id="execution-results-GETapi-projects--project_id--servers--server_id--sites" hidden>
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-GETapi-projects--project_id--servers--server_id--sites"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-GETapi-projects--project_id--servers--server_id--sites"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-GETapi-projects--project_id--servers--server_id--sites" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-GETapi-projects--project_id--servers--server_id--sites">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-GETapi-projects--project_id--servers--server_id--sites"
                    data-method="GET"
                    data-path="api/projects/{project_id}/servers/{server_id}/sites"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('GETapi-projects--project_id--servers--server_id--sites', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-GETapi-projects--project_id--servers--server_id--sites"
                            onclick="tryItOut('GETapi-projects--project_id--servers--server_id--sites');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-GETapi-projects--project_id--servers--server_id--sites"
                            onclick="cancelTryOut('GETapi-projects--project_id--servers--server_id--sites');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-GETapi-projects--project_id--servers--server_id--sites"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-green">GET</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/sites</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--sites"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--sites"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--sites"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--sites"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                </form>

                <h2 id="sites-POSTapi-projects--project_id--servers--server_id--sites">create</h2>

                <p></p>

                <p>Create a new site.</p>

                <span id="example-requests-POSTapi-projects--project_id--servers--server_id--sites">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request POST \
    "https://vito.test/api/projects/1/servers/32/sites" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"type\": \"load-balancer\",
    \"domain\": \"consequatur\",
    \"aliases\": [
        \"consequatur\"
    ],
    \"php_version\": \"7.4\",
    \"web_directory\": \"public\",
    \"source_control\": \"consequatur\",
    \"repository\": \"organization\\/repository\",
    \"branch\": \"main\",
    \"composer\": true,
    \"version\": \"5.2.1\",
    \"user\": \"consequatur\",
    \"method\": \"ip-hash\"
}"
</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/sites"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "type": "load-balancer",
    "domain": "consequatur",
    "aliases": [
        "consequatur"
    ],
    "php_version": "7.4",
    "web_directory": "public",
    "source_control": "consequatur",
    "repository": "organization\/repository",
    "branch": "main",
    "composer": true,
    "version": "5.2.1",
    "user": "consequatur",
    "method": "ip-hash"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-POSTapi-projects--project_id--servers--server_id--sites">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;id&quot;: 50,
    &quot;server_id&quot;: 1,
    &quot;source_control_id&quot;: null,
    &quot;type&quot;: &quot;laravel&quot;,
    &quot;type_data&quot;: null,
    &quot;domain&quot;: &quot;test.com&quot;,
    &quot;aliases&quot;: null,
    &quot;web_directory&quot;: &quot;/&quot;,
    &quot;path&quot;: &quot;/home&quot;,
    &quot;php_version&quot;: &quot;8.2&quot;,
    &quot;repository&quot;: null,
    &quot;branch&quot;: &quot;main&quot;,
    &quot;status&quot;: &quot;ready&quot;,
    &quot;port&quot;: null,
    &quot;user&quot;: &quot;vito&quot;,
    &quot;progress&quot;: 100,
    &quot;created_at&quot;: &quot;2025-04-21T18:40:20.000000Z&quot;,
    &quot;updated_at&quot;: &quot;2025-04-21T18:40:20.000000Z&quot;
}</code>
 </pre>
                </span>
                <span id="execution-results-POSTapi-projects--project_id--servers--server_id--sites" hidden>
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-POSTapi-projects--project_id--servers--server_id--sites"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-POSTapi-projects--project_id--servers--server_id--sites"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-POSTapi-projects--project_id--servers--server_id--sites" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-POSTapi-projects--project_id--servers--server_id--sites">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-POSTapi-projects--project_id--servers--server_id--sites"
                    data-method="POST"
                    data-path="api/projects/{project_id}/servers/{server_id}/sites"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('POSTapi-projects--project_id--servers--server_id--sites', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-POSTapi-projects--project_id--servers--server_id--sites"
                            onclick="tryItOut('POSTapi-projects--project_id--servers--server_id--sites');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-POSTapi-projects--project_id--servers--server_id--sites"
                            onclick="cancelTryOut('POSTapi-projects--project_id--servers--server_id--sites');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-POSTapi-projects--project_id--servers--server_id--sites"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-black">POST</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/sites</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>type</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="type"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites"
                            value="load-balancer"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Example:
                            <code>load-balancer</code>
                        </p>
                        Must be one of:
                        <ul style="list-style-type: square">
                            <li><code>php</code></li>
                            <li><code>php-blank</code></li>
                            <li><code>phpmyadmin</code></li>
                            <li><code>laravel</code></li>
                            <li><code>wordpress</code></li>
                            <li><code>load-balancer</code></li>
                        </ul>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>domain</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="domain"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>aliases</code></b>
                        &nbsp;&nbsp;
                        <small>string[]</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="aliases[0]"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites"
                            data-component="body"
                        />
                        <input
                            type="text"
                            style="display: none"
                            name="aliases[1]"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites"
                            data-component="body"
                        />
                        <br />
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>php_version</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="php_version"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites"
                            value="7.4"
                            data-component="body"
                        />
                        <br />
                        <p>
                            One of the installed PHP Versions Example:
                            <code>7.4</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>web_directory</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="web_directory"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites"
                            value="public"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Required for PHP and Laravel sites Example:
                            <code>public</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>source_control</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="source_control"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Source control ID, Required for Sites which support source control Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>repository</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="repository"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites"
                            value="organization/repository"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Repository, Required for Sites which support source control Example:
                            <code>organization/repository</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>branch</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="branch"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites"
                            value="main"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Branch, Required for Sites which support source control Example:
                            <code>main</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>composer</code></b>
                        &nbsp;&nbsp;
                        <small>boolean</small>
                        &nbsp; &nbsp;
                        <label
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites"
                            style="display: none"
                        >
                            <input
                                type="radio"
                                name="composer"
                                value="true"
                                data-endpoint="POSTapi-projects--project_id--servers--server_id--sites"
                                data-component="body"
                            />
                            <code>true</code>
                        </label>
                        <label
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites"
                            style="display: none"
                        >
                            <input
                                type="radio"
                                name="composer"
                                value="false"
                                data-endpoint="POSTapi-projects--project_id--servers--server_id--sites"
                                data-component="body"
                            />
                            <code>false</code>
                        </label>
                        <br />
                        <p>
                            Run composer if site supports composer Example:
                            <code>true</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>version</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="version"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites"
                            value="5.2.1"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Version, if the site type requires a version like PHPMyAdmin Example:
                            <code>5.2.1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>user</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="user"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            user, to isolate the website under a new user Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>method</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="method"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites"
                            value="ip-hash"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Load balancer method, Required if the site type is Load balancer Example:
                            <code>ip-hash</code>
                        </p>
                        Must be one of:
                        <ul style="list-style-type: square">
                            <li><code>round-robin</code></li>
                            <li><code>least-connections</code></li>
                            <li><code>ip-hash</code></li>
                        </ul>
                    </div>
                </form>

                <h2 id="sites-GETapi-projects--project_id--servers--server_id--sites--id-">show</h2>

                <p></p>

                <p>Get a site by ID.</p>

                <span id="example-requests-GETapi-projects--project_id--servers--server_id--sites--id-">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request GET \
    --get "https://vito.test/api/projects/1/servers/32/sites/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/sites/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-GETapi-projects--project_id--servers--server_id--sites--id-">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;id&quot;: 50,
    &quot;server_id&quot;: 1,
    &quot;source_control_id&quot;: null,
    &quot;type&quot;: &quot;laravel&quot;,
    &quot;type_data&quot;: null,
    &quot;domain&quot;: &quot;test.com&quot;,
    &quot;aliases&quot;: null,
    &quot;web_directory&quot;: &quot;/&quot;,
    &quot;path&quot;: &quot;/home&quot;,
    &quot;php_version&quot;: &quot;8.2&quot;,
    &quot;repository&quot;: null,
    &quot;branch&quot;: &quot;main&quot;,
    &quot;status&quot;: &quot;ready&quot;,
    &quot;port&quot;: null,
    &quot;user&quot;: &quot;vito&quot;,
    &quot;progress&quot;: 100,
    &quot;created_at&quot;: &quot;2025-04-21T18:40:20.000000Z&quot;,
    &quot;updated_at&quot;: &quot;2025-04-21T18:40:20.000000Z&quot;
}</code>
 </pre>
                </span>
                <span id="execution-results-GETapi-projects--project_id--servers--server_id--sites--id-" hidden>
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-GETapi-projects--project_id--servers--server_id--sites--id-"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-GETapi-projects--project_id--servers--server_id--sites--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-GETapi-projects--project_id--servers--server_id--sites--id-" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-GETapi-projects--project_id--servers--server_id--sites--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-GETapi-projects--project_id--servers--server_id--sites--id-"
                    data-method="GET"
                    data-path="api/projects/{project_id}/servers/{server_id}/sites/{id}"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('GETapi-projects--project_id--servers--server_id--sites--id-', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-GETapi-projects--project_id--servers--server_id--sites--id-"
                            onclick="tryItOut('GETapi-projects--project_id--servers--server_id--sites--id-');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-GETapi-projects--project_id--servers--server_id--sites--id-"
                            onclick="cancelTryOut('GETapi-projects--project_id--servers--server_id--sites--id-');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-GETapi-projects--project_id--servers--server_id--sites--id-"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-green">GET</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/sites/{id}</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--sites--id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--sites--id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--sites--id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--sites--id-"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--sites--id-"
                            value="17"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the site. Example:
                            <code>17</code>
                        </p>
                    </div>
                </form>

                <h2 id="sites-DELETEapi-projects--project_id--servers--server_id--sites--site_id-">delete</h2>

                <p></p>

                <p>Delete site.</p>

                <span id="example-requests-DELETEapi-projects--project_id--servers--server_id--sites--site_id-">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request DELETE \
    "https://vito.test/api/projects/1/servers/32/sites/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/sites/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-DELETEapi-projects--project_id--servers--server_id--sites--site_id-">
                    <blockquote>
                        <p>Example response (204):</p>
                    </blockquote>
                    <pre>
<code>Empty response</code>
 </pre>
                </span>
                <span id="execution-results-DELETEapi-projects--project_id--servers--server_id--sites--site_id-" hidden>
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-DELETEapi-projects--project_id--servers--server_id--sites--site_id-"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-DELETEapi-projects--project_id--servers--server_id--sites--site_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-DELETEapi-projects--project_id--servers--server_id--sites--site_id-" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-DELETEapi-projects--project_id--servers--server_id--sites--site_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-DELETEapi-projects--project_id--servers--server_id--sites--site_id-"
                    data-method="DELETE"
                    data-path="api/projects/{project_id}/servers/{server_id}/sites/{site_id}"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('DELETEapi-projects--project_id--servers--server_id--sites--site_id-', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-DELETEapi-projects--project_id--servers--server_id--sites--site_id-"
                            onclick="tryItOut('DELETEapi-projects--project_id--servers--server_id--sites--site_id-');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-DELETEapi-projects--project_id--servers--server_id--sites--site_id-"
                            onclick="cancelTryOut('DELETEapi-projects--project_id--servers--server_id--sites--site_id-');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-DELETEapi-projects--project_id--servers--server_id--sites--site_id-"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-red">DELETE</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/sites/{site_id}</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--sites--site_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--sites--site_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--sites--site_id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--sites--site_id-"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>site_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="site_id"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--sites--site_id-"
                            value="17"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the site. Example:
                            <code>17</code>
                        </p>
                    </div>
                </form>

                <h2 id="sites-POSTapi-projects--project_id--servers--server_id--sites--site_id--load-balancer">
                    load-balancer
                </h2>

                <p></p>

                <p>Update load balancer.</p>

                <span
                    id="example-requests-POSTapi-projects--project_id--servers--server_id--sites--site_id--load-balancer"
                >
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request POST \
    "https://vito.test/api/projects/1/servers/32/sites/17/load-balancer" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"method\": \"ip-hash\",
    \"servers\": [
        \"consequatur\"
    ]
}"
</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/sites/17/load-balancer"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "method": "ip-hash",
    "servers": [
        "consequatur"
    ]
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span
                    id="example-responses-POSTapi-projects--project_id--servers--server_id--sites--site_id--load-balancer"
                >
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;"></code>
 </pre>
                </span>
                <span
                    id="execution-results-POSTapi-projects--project_id--servers--server_id--sites--site_id--load-balancer"
                    hidden
                >
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-POSTapi-projects--project_id--servers--server_id--sites--site_id--load-balancer"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-POSTapi-projects--project_id--servers--server_id--sites--site_id--load-balancer"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span
                    id="execution-error-POSTapi-projects--project_id--servers--server_id--sites--site_id--load-balancer"
                    hidden
                >
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-POSTapi-projects--project_id--servers--server_id--sites--site_id--load-balancer">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-POSTapi-projects--project_id--servers--server_id--sites--site_id--load-balancer"
                    data-method="POST"
                    data-path="api/projects/{project_id}/servers/{server_id}/sites/{site_id}/load-balancer"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('POSTapi-projects--project_id--servers--server_id--sites--site_id--load-balancer', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-POSTapi-projects--project_id--servers--server_id--sites--site_id--load-balancer"
                            onclick="tryItOut('POSTapi-projects--project_id--servers--server_id--sites--site_id--load-balancer');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-POSTapi-projects--project_id--servers--server_id--sites--site_id--load-balancer"
                            onclick="cancelTryOut('POSTapi-projects--project_id--servers--server_id--sites--site_id--load-balancer');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-POSTapi-projects--project_id--servers--server_id--sites--site_id--load-balancer"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-black">POST</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/sites/{site_id}/load-balancer</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites--site_id--load-balancer"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites--site_id--load-balancer"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites--site_id--load-balancer"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites--site_id--load-balancer"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>site_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="site_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites--site_id--load-balancer"
                            value="17"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the site. Example:
                            <code>17</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>method</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="method"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites--site_id--load-balancer"
                            value="ip-hash"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Load balancer method, Required if the site type is Load balancer Example:
                            <code>ip-hash</code>
                        </p>
                        Must be one of:
                        <ul style="list-style-type: square">
                            <li><code>round-robin</code></li>
                            <li><code>least-connections</code></li>
                            <li><code>ip-hash</code></li>
                        </ul>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>servers</code></b>
                        &nbsp;&nbsp;
                        <small>string[]</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="servers[0]"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites--site_id--load-balancer"
                            data-component="body"
                        />
                        <input
                            type="text"
                            style="display: none"
                            name="servers[1]"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites--site_id--load-balancer"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Array of servers including server, port, weight, backup. (server is the local IP of the
                            server)
                        </p>
                    </div>
                </form>

                <h2 id="sites-PUTapi-projects--project_id--servers--server_id--sites--site_id--aliases">aliases</h2>

                <p></p>

                <p>Update aliases.</p>

                <span id="example-requests-PUTapi-projects--project_id--servers--server_id--sites--site_id--aliases">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request PUT \
    "https://vito.test/api/projects/1/servers/32/sites/17/aliases" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"aliases\": [
        \"consequatur\"
    ]
}"
</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/sites/17/aliases"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "aliases": [
        "consequatur"
    ]
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-PUTapi-projects--project_id--servers--server_id--sites--site_id--aliases">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;"></code>
 </pre>
                </span>
                <span
                    id="execution-results-PUTapi-projects--project_id--servers--server_id--sites--site_id--aliases"
                    hidden
                >
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-PUTapi-projects--project_id--servers--server_id--sites--site_id--aliases"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-PUTapi-projects--project_id--servers--server_id--sites--site_id--aliases"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span
                    id="execution-error-PUTapi-projects--project_id--servers--server_id--sites--site_id--aliases"
                    hidden
                >
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-PUTapi-projects--project_id--servers--server_id--sites--site_id--aliases">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-PUTapi-projects--project_id--servers--server_id--sites--site_id--aliases"
                    data-method="PUT"
                    data-path="api/projects/{project_id}/servers/{server_id}/sites/{site_id}/aliases"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('PUTapi-projects--project_id--servers--server_id--sites--site_id--aliases', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-PUTapi-projects--project_id--servers--server_id--sites--site_id--aliases"
                            onclick="tryItOut('PUTapi-projects--project_id--servers--server_id--sites--site_id--aliases');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-PUTapi-projects--project_id--servers--server_id--sites--site_id--aliases"
                            onclick="cancelTryOut('PUTapi-projects--project_id--servers--server_id--sites--site_id--aliases');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-PUTapi-projects--project_id--servers--server_id--sites--site_id--aliases"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-darkblue">PUT</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/sites/{site_id}/aliases</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="PUTapi-projects--project_id--servers--server_id--sites--site_id--aliases"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="PUTapi-projects--project_id--servers--server_id--sites--site_id--aliases"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="PUTapi-projects--project_id--servers--server_id--sites--site_id--aliases"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="PUTapi-projects--project_id--servers--server_id--sites--site_id--aliases"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>site_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="site_id"
                            data-endpoint="PUTapi-projects--project_id--servers--server_id--sites--site_id--aliases"
                            value="17"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the site. Example:
                            <code>17</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>aliases</code></b>
                        &nbsp;&nbsp;
                        <small>string[]</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="aliases[0]"
                            data-endpoint="PUTapi-projects--project_id--servers--server_id--sites--site_id--aliases"
                            data-component="body"
                        />
                        <input
                            type="text"
                            style="display: none"
                            name="aliases[1]"
                            data-endpoint="PUTapi-projects--project_id--servers--server_id--sites--site_id--aliases"
                            data-component="body"
                        />
                        <br />
                        <p>Array of aliases</p>
                    </div>
                </form>

                <h2 id="sites-POSTapi-projects--project_id--servers--server_id--sites--site_id--deploy">deploy</h2>

                <p></p>

                <p>Run site deployment script</p>

                <span id="example-requests-POSTapi-projects--project_id--servers--server_id--sites--site_id--deploy">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request POST \
    "https://vito.test/api/projects/1/servers/32/sites/17/deploy" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/sites/17/deploy"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-POSTapi-projects--project_id--servers--server_id--sites--site_id--deploy">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;"></code>
 </pre>
                </span>
                <span
                    id="execution-results-POSTapi-projects--project_id--servers--server_id--sites--site_id--deploy"
                    hidden
                >
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-POSTapi-projects--project_id--servers--server_id--sites--site_id--deploy"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-POSTapi-projects--project_id--servers--server_id--sites--site_id--deploy"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span
                    id="execution-error-POSTapi-projects--project_id--servers--server_id--sites--site_id--deploy"
                    hidden
                >
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-POSTapi-projects--project_id--servers--server_id--sites--site_id--deploy">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-POSTapi-projects--project_id--servers--server_id--sites--site_id--deploy"
                    data-method="POST"
                    data-path="api/projects/{project_id}/servers/{server_id}/sites/{site_id}/deploy"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('POSTapi-projects--project_id--servers--server_id--sites--site_id--deploy', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-POSTapi-projects--project_id--servers--server_id--sites--site_id--deploy"
                            onclick="tryItOut('POSTapi-projects--project_id--servers--server_id--sites--site_id--deploy');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-POSTapi-projects--project_id--servers--server_id--sites--site_id--deploy"
                            onclick="cancelTryOut('POSTapi-projects--project_id--servers--server_id--sites--site_id--deploy');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-POSTapi-projects--project_id--servers--server_id--sites--site_id--deploy"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-black">POST</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/sites/{site_id}/deploy</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites--site_id--deploy"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites--site_id--deploy"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites--site_id--deploy"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites--site_id--deploy"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>site_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="site_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--sites--site_id--deploy"
                            value="17"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the site. Example:
                            <code>17</code>
                        </p>
                    </div>
                </form>

                <h2 id="sites-PUTapi-projects--project_id--servers--server_id--sites--site_id--deployment-script">
                    deployment-script
                </h2>

                <p></p>

                <p>Update site deployment script</p>

                <span
                    id="example-requests-PUTapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
                >
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request PUT \
    "https://vito.test/api/projects/1/servers/32/sites/17/deployment-script" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"script\": \"consequatur\"
}"
</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/sites/17/deployment-script"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "script": "consequatur"
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span
                    id="example-responses-PUTapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
                >
                    <blockquote>
                        <p>Example response (204):</p>
                    </blockquote>
                    <pre>
<code>Empty response</code>
 </pre>
                </span>
                <span
                    id="execution-results-PUTapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
                    hidden
                >
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-PUTapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-PUTapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span
                    id="execution-error-PUTapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
                    hidden
                >
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-PUTapi-projects--project_id--servers--server_id--sites--site_id--deployment-script">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-PUTapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
                    data-method="PUT"
                    data-path="api/projects/{project_id}/servers/{server_id}/sites/{site_id}/deployment-script"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('PUTapi-projects--project_id--servers--server_id--sites--site_id--deployment-script', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-PUTapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
                            onclick="tryItOut('PUTapi-projects--project_id--servers--server_id--sites--site_id--deployment-script');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-PUTapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
                            onclick="cancelTryOut('PUTapi-projects--project_id--servers--server_id--sites--site_id--deployment-script');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-PUTapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-darkblue">PUT</small>
                        <b>
                            <code>api/projects/{project_id}/servers/{server_id}/sites/{site_id}/deployment-script</code>
                        </b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="PUTapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="PUTapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="PUTapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="PUTapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>site_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="site_id"
                            data-endpoint="PUTapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
                            value="17"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the site. Example:
                            <code>17</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>script</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="script"
                            data-endpoint="PUTapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Content of the deployment script Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                </form>

                <h2 id="sites-GETapi-projects--project_id--servers--server_id--sites--site_id--deployment-script">
                    deployment-script
                </h2>

                <p></p>

                <p>Get site deployment script content</p>

                <span
                    id="example-requests-GETapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
                >
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request GET \
    --get "https://vito.test/api/projects/1/servers/32/sites/17/deployment-script" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/sites/17/deployment-script"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span
                    id="example-responses-GETapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
                >
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;"></code>
 </pre>
                </span>
                <span
                    id="execution-results-GETapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
                    hidden
                >
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-GETapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-GETapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span
                    id="execution-error-GETapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
                    hidden
                >
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-GETapi-projects--project_id--servers--server_id--sites--site_id--deployment-script">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-GETapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
                    data-method="GET"
                    data-path="api/projects/{project_id}/servers/{server_id}/sites/{site_id}/deployment-script"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('GETapi-projects--project_id--servers--server_id--sites--site_id--deployment-script', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-GETapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
                            onclick="tryItOut('GETapi-projects--project_id--servers--server_id--sites--site_id--deployment-script');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-GETapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
                            onclick="cancelTryOut('GETapi-projects--project_id--servers--server_id--sites--site_id--deployment-script');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-GETapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-green">GET</small>
                        <b>
                            <code>api/projects/{project_id}/servers/{server_id}/sites/{site_id}/deployment-script</code>
                        </b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>site_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="site_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--sites--site_id--deployment-script"
                            value="17"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the site. Example:
                            <code>17</code>
                        </p>
                    </div>
                </form>

                <h2 id="sites-GETapi-projects--project_id--servers--server_id--sites--site_id--env">env</h2>

                <p></p>

                <p>Get site .env file content</p>

                <span id="example-requests-GETapi-projects--project_id--servers--server_id--sites--site_id--env">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request GET \
    --get "https://vito.test/api/projects/1/servers/32/sites/17/env" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/sites/17/env"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-GETapi-projects--project_id--servers--server_id--sites--site_id--env">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;env&quot;: &quot;APP_NAME=Laravel\\nAPP_ENV=production&quot;
    }
}</code>
 </pre>
                </span>
                <span
                    id="execution-results-GETapi-projects--project_id--servers--server_id--sites--site_id--env"
                    hidden
                >
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-GETapi-projects--project_id--servers--server_id--sites--site_id--env"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-GETapi-projects--project_id--servers--server_id--sites--site_id--env"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-GETapi-projects--project_id--servers--server_id--sites--site_id--env" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-GETapi-projects--project_id--servers--server_id--sites--site_id--env">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-GETapi-projects--project_id--servers--server_id--sites--site_id--env"
                    data-method="GET"
                    data-path="api/projects/{project_id}/servers/{server_id}/sites/{site_id}/env"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('GETapi-projects--project_id--servers--server_id--sites--site_id--env', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-GETapi-projects--project_id--servers--server_id--sites--site_id--env"
                            onclick="tryItOut('GETapi-projects--project_id--servers--server_id--sites--site_id--env');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-GETapi-projects--project_id--servers--server_id--sites--site_id--env"
                            onclick="cancelTryOut('GETapi-projects--project_id--servers--server_id--sites--site_id--env');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-GETapi-projects--project_id--servers--server_id--sites--site_id--env"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-green">GET</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/sites/{site_id}/env</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--sites--site_id--env"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--sites--site_id--env"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--sites--site_id--env"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--sites--site_id--env"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>site_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="site_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--sites--site_id--env"
                            value="17"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the site. Example:
                            <code>17</code>
                        </p>
                    </div>
                </form>

                <h2 id="sites-PUTapi-projects--project_id--servers--server_id--sites--site_id--env">env</h2>

                <p></p>

                <p>Update site .env file</p>

                <span id="example-requests-PUTapi-projects--project_id--servers--server_id--sites--site_id--env">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request PUT \
    "https://vito.test/api/projects/1/servers/32/sites/17/env" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"env\": \"consequatur\"
}"
</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/sites/17/env"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "env": "consequatur"
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-PUTapi-projects--project_id--servers--server_id--sites--site_id--env">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;"></code>
 </pre>
                </span>
                <span
                    id="execution-results-PUTapi-projects--project_id--servers--server_id--sites--site_id--env"
                    hidden
                >
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-PUTapi-projects--project_id--servers--server_id--sites--site_id--env"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-PUTapi-projects--project_id--servers--server_id--sites--site_id--env"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-PUTapi-projects--project_id--servers--server_id--sites--site_id--env" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-PUTapi-projects--project_id--servers--server_id--sites--site_id--env">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-PUTapi-projects--project_id--servers--server_id--sites--site_id--env"
                    data-method="PUT"
                    data-path="api/projects/{project_id}/servers/{server_id}/sites/{site_id}/env"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('PUTapi-projects--project_id--servers--server_id--sites--site_id--env', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-PUTapi-projects--project_id--servers--server_id--sites--site_id--env"
                            onclick="tryItOut('PUTapi-projects--project_id--servers--server_id--sites--site_id--env');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-PUTapi-projects--project_id--servers--server_id--sites--site_id--env"
                            onclick="cancelTryOut('PUTapi-projects--project_id--servers--server_id--sites--site_id--env');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-PUTapi-projects--project_id--servers--server_id--sites--site_id--env"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-darkblue">PUT</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/sites/{site_id}/env</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="PUTapi-projects--project_id--servers--server_id--sites--site_id--env"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="PUTapi-projects--project_id--servers--server_id--sites--site_id--env"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="PUTapi-projects--project_id--servers--server_id--sites--site_id--env"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="PUTapi-projects--project_id--servers--server_id--sites--site_id--env"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>site_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="site_id"
                            data-endpoint="PUTapi-projects--project_id--servers--server_id--sites--site_id--env"
                            value="17"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the site. Example:
                            <code>17</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>env</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="env"
                            data-endpoint="PUTapi-projects--project_id--servers--server_id--sites--site_id--env"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Content of the .env file Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                </form>

                <h1 id="source-controls">source-controls</h1>

                <h2 id="source-controls-GETapi-projects--project_id--source-controls">list</h2>

                <p></p>

                <span id="example-requests-GETapi-projects--project_id--source-controls">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request GET \
    --get "https://vito.test/api/projects/1/source-controls" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/source-controls"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-GETapi-projects--project_id--source-controls">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: 5,
            &quot;project_id&quot;: null,
            &quot;global&quot;: true,
            &quot;name&quot;: &quot;Dr. Cornelius Luettgen V&quot;,
            &quot;provider&quot;: &quot;github&quot;,
            &quot;created_at&quot;: &quot;2025-04-21T18:40:20.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-04-21T18:40:20.000000Z&quot;
        },
        {
            &quot;id&quot;: 6,
            &quot;project_id&quot;: null,
            &quot;global&quot;: true,
            &quot;name&quot;: &quot;Orville Satterfield&quot;,
            &quot;provider&quot;: &quot;github&quot;,
            &quot;created_at&quot;: &quot;2025-04-21T18:40:20.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-04-21T18:40:20.000000Z&quot;
        }
    ],
    &quot;links&quot;: {
        &quot;first&quot;: &quot;/?page=1&quot;,
        &quot;last&quot;: &quot;/?page=1&quot;,
        &quot;prev&quot;: null,
        &quot;next&quot;: null
    },
    &quot;meta&quot;: {
        &quot;current_page&quot;: 1,
        &quot;from&quot;: 1,
        &quot;last_page&quot;: 1,
        &quot;links&quot;: [
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;&amp;laquo; Previous&quot;,
                &quot;active&quot;: false
            },
            {
                &quot;url&quot;: &quot;/?page=1&quot;,
                &quot;label&quot;: &quot;1&quot;,
                &quot;active&quot;: true
            },
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;Next &amp;raquo;&quot;,
                &quot;active&quot;: false
            }
        ],
        &quot;path&quot;: &quot;/&quot;,
        &quot;per_page&quot;: 25,
        &quot;to&quot;: 2,
        &quot;total&quot;: 2
    }
}</code>
 </pre>
                </span>
                <span id="execution-results-GETapi-projects--project_id--source-controls" hidden>
                    <blockquote>
                        Received response
                        <span id="execution-response-status-GETapi-projects--project_id--source-controls"></span>
                        :
                    </blockquote>
                    <pre class="json"><code id="execution-response-content-GETapi-projects--project_id--source-controls"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-GETapi-projects--project_id--source-controls" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-GETapi-projects--project_id--source-controls">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-GETapi-projects--project_id--source-controls"
                    data-method="GET"
                    data-path="api/projects/{project_id}/source-controls"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('GETapi-projects--project_id--source-controls', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-GETapi-projects--project_id--source-controls"
                            onclick="tryItOut('GETapi-projects--project_id--source-controls');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-GETapi-projects--project_id--source-controls"
                            onclick="cancelTryOut('GETapi-projects--project_id--source-controls');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-GETapi-projects--project_id--source-controls"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-green">GET</small>
                        <b><code>api/projects/{project_id}/source-controls</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="GETapi-projects--project_id--source-controls"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="GETapi-projects--project_id--source-controls"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="GETapi-projects--project_id--source-controls"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                </form>

                <h2 id="source-controls-POSTapi-projects--project_id--source-controls">create</h2>

                <p></p>

                <span id="example-requests-POSTapi-projects--project_id--source-controls">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request POST \
    "https://vito.test/api/projects/1/source-controls" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"provider\": \"gitlab\",
    \"name\": \"consequatur\",
    \"token\": \"consequatur\",
    \"url\": \"http:\\/\\/kunze.biz\\/iste-laborum-eius-est-dolor.html\",
    \"username\": \"consequatur\",
    \"password\": \"O[2UZ5ij-e\\/dl4m{o,\"
}"
</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/source-controls"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "provider": "gitlab",
    "name": "consequatur",
    "token": "consequatur",
    "url": "http:\/\/kunze.biz\/iste-laborum-eius-est-dolor.html",
    "username": "consequatur",
    "password": "O[2UZ5ij-e\/dl4m{o,"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-POSTapi-projects--project_id--source-controls">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;id&quot;: 5,
    &quot;project_id&quot;: null,
    &quot;global&quot;: true,
    &quot;name&quot;: &quot;Lonny Ankunding&quot;,
    &quot;provider&quot;: &quot;github&quot;,
    &quot;created_at&quot;: &quot;2025-04-21T18:40:20.000000Z&quot;,
    &quot;updated_at&quot;: &quot;2025-04-21T18:40:20.000000Z&quot;
}</code>
 </pre>
                </span>
                <span id="execution-results-POSTapi-projects--project_id--source-controls" hidden>
                    <blockquote>
                        Received response
                        <span id="execution-response-status-POSTapi-projects--project_id--source-controls"></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-POSTapi-projects--project_id--source-controls"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-POSTapi-projects--project_id--source-controls" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-POSTapi-projects--project_id--source-controls">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-POSTapi-projects--project_id--source-controls"
                    data-method="POST"
                    data-path="api/projects/{project_id}/source-controls"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('POSTapi-projects--project_id--source-controls', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-POSTapi-projects--project_id--source-controls"
                            onclick="tryItOut('POSTapi-projects--project_id--source-controls');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-POSTapi-projects--project_id--source-controls"
                            onclick="cancelTryOut('POSTapi-projects--project_id--source-controls');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-POSTapi-projects--project_id--source-controls"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-black">POST</small>
                        <b><code>api/projects/{project_id}/source-controls</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="POSTapi-projects--project_id--source-controls"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="POSTapi-projects--project_id--source-controls"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="POSTapi-projects--project_id--source-controls"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>provider</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="provider"
                            data-endpoint="POSTapi-projects--project_id--source-controls"
                            value="gitlab"
                            data-component="body"
                        />
                        <br />
                        <p>
                            The provider Example:
                            <code>gitlab</code>
                        </p>
                        Must be one of:
                        <ul style="list-style-type: square">
                            <li><code>gitlab</code></li>
                            <li><code>github</code></li>
                            <li><code>bitbucket</code></li>
                        </ul>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>name</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="name"
                            data-endpoint="POSTapi-projects--project_id--source-controls"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            The name of the storage provider. Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>token</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="token"
                            data-endpoint="POSTapi-projects--project_id--source-controls"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            The token if provider requires api token Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>url</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="url"
                            data-endpoint="POSTapi-projects--project_id--source-controls"
                            value="http://kunze.biz/iste-laborum-eius-est-dolor.html"
                            data-component="body"
                        />
                        <br />
                        <p>
                            The URL if the provider is Gitlab and it is self-hosted Example:
                            <code>http://kunze.biz/iste-laborum-eius-est-dolor.html</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>username</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="username"
                            data-endpoint="POSTapi-projects--project_id--source-controls"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            The username if the provider is Bitbucket Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>password</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="password"
                            data-endpoint="POSTapi-projects--project_id--source-controls"
                            value="O[2UZ5ij-e/dl4m{o,"
                            data-component="body"
                        />
                        <br />
                        <p>
                            The password if the provider is Bitbucket Example:
                            <code>O[2UZ5ij-e/dl4m{o,</code>
                        </p>
                    </div>
                </form>

                <h2 id="source-controls-GETapi-projects--project_id--source-controls--sourceControl_id-">show</h2>

                <p></p>

                <span id="example-requests-GETapi-projects--project_id--source-controls--sourceControl_id-">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request GET \
    --get "https://vito.test/api/projects/1/source-controls/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/source-controls/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-GETapi-projects--project_id--source-controls--sourceControl_id-">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;id&quot;: 5,
    &quot;project_id&quot;: null,
    &quot;global&quot;: true,
    &quot;name&quot;: &quot;Dr. Enoch Harber II&quot;,
    &quot;provider&quot;: &quot;github&quot;,
    &quot;created_at&quot;: &quot;2025-04-21T18:40:20.000000Z&quot;,
    &quot;updated_at&quot;: &quot;2025-04-21T18:40:20.000000Z&quot;
}</code>
 </pre>
                </span>
                <span id="execution-results-GETapi-projects--project_id--source-controls--sourceControl_id-" hidden>
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-GETapi-projects--project_id--source-controls--sourceControl_id-"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-GETapi-projects--project_id--source-controls--sourceControl_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-GETapi-projects--project_id--source-controls--sourceControl_id-" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-GETapi-projects--project_id--source-controls--sourceControl_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-GETapi-projects--project_id--source-controls--sourceControl_id-"
                    data-method="GET"
                    data-path="api/projects/{project_id}/source-controls/{sourceControl_id}"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('GETapi-projects--project_id--source-controls--sourceControl_id-', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-GETapi-projects--project_id--source-controls--sourceControl_id-"
                            onclick="tryItOut('GETapi-projects--project_id--source-controls--sourceControl_id-');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-GETapi-projects--project_id--source-controls--sourceControl_id-"
                            onclick="cancelTryOut('GETapi-projects--project_id--source-controls--sourceControl_id-');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-GETapi-projects--project_id--source-controls--sourceControl_id-"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-green">GET</small>
                        <b><code>api/projects/{project_id}/source-controls/{sourceControl_id}</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="GETapi-projects--project_id--source-controls--sourceControl_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="GETapi-projects--project_id--source-controls--sourceControl_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="GETapi-projects--project_id--source-controls--sourceControl_id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>sourceControl_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="sourceControl_id"
                            data-endpoint="GETapi-projects--project_id--source-controls--sourceControl_id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the sourceControl. Example:
                            <code>1</code>
                        </p>
                    </div>
                </form>

                <h2 id="source-controls-PUTapi-projects--project_id--source-controls--sourceControl_id-">update</h2>

                <p></p>

                <span id="example-requests-PUTapi-projects--project_id--source-controls--sourceControl_id-">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request PUT \
    "https://vito.test/api/projects/1/source-controls/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"consequatur\",
    \"token\": \"consequatur\",
    \"url\": \"http:\\/\\/kunze.biz\\/iste-laborum-eius-est-dolor.html\",
    \"username\": \"consequatur\",
    \"password\": \"O[2UZ5ij-e\\/dl4m{o,\",
    \"global\": false
}"
</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/source-controls/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "consequatur",
    "token": "consequatur",
    "url": "http:\/\/kunze.biz\/iste-laborum-eius-est-dolor.html",
    "username": "consequatur",
    "password": "O[2UZ5ij-e\/dl4m{o,",
    "global": false
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-PUTapi-projects--project_id--source-controls--sourceControl_id-">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;id&quot;: 5,
    &quot;project_id&quot;: null,
    &quot;global&quot;: true,
    &quot;name&quot;: &quot;Lonny Ankunding&quot;,
    &quot;provider&quot;: &quot;github&quot;,
    &quot;created_at&quot;: &quot;2025-04-21T18:40:20.000000Z&quot;,
    &quot;updated_at&quot;: &quot;2025-04-21T18:40:20.000000Z&quot;
}</code>
 </pre>
                </span>
                <span id="execution-results-PUTapi-projects--project_id--source-controls--sourceControl_id-" hidden>
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-PUTapi-projects--project_id--source-controls--sourceControl_id-"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-PUTapi-projects--project_id--source-controls--sourceControl_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-PUTapi-projects--project_id--source-controls--sourceControl_id-" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-PUTapi-projects--project_id--source-controls--sourceControl_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-PUTapi-projects--project_id--source-controls--sourceControl_id-"
                    data-method="PUT"
                    data-path="api/projects/{project_id}/source-controls/{sourceControl_id}"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('PUTapi-projects--project_id--source-controls--sourceControl_id-', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-PUTapi-projects--project_id--source-controls--sourceControl_id-"
                            onclick="tryItOut('PUTapi-projects--project_id--source-controls--sourceControl_id-');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-PUTapi-projects--project_id--source-controls--sourceControl_id-"
                            onclick="cancelTryOut('PUTapi-projects--project_id--source-controls--sourceControl_id-');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-PUTapi-projects--project_id--source-controls--sourceControl_id-"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-darkblue">PUT</small>
                        <b><code>api/projects/{project_id}/source-controls/{sourceControl_id}</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="PUTapi-projects--project_id--source-controls--sourceControl_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="PUTapi-projects--project_id--source-controls--sourceControl_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="PUTapi-projects--project_id--source-controls--sourceControl_id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>sourceControl_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="sourceControl_id"
                            data-endpoint="PUTapi-projects--project_id--source-controls--sourceControl_id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the sourceControl. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>name</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="name"
                            data-endpoint="PUTapi-projects--project_id--source-controls--sourceControl_id-"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            The name of the storage provider. Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>token</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="token"
                            data-endpoint="PUTapi-projects--project_id--source-controls--sourceControl_id-"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            The token if provider requires api token Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>url</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="url"
                            data-endpoint="PUTapi-projects--project_id--source-controls--sourceControl_id-"
                            value="http://kunze.biz/iste-laborum-eius-est-dolor.html"
                            data-component="body"
                        />
                        <br />
                        <p>
                            The URL if the provider is Gitlab and it is self-hosted Example:
                            <code>http://kunze.biz/iste-laborum-eius-est-dolor.html</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>username</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="username"
                            data-endpoint="PUTapi-projects--project_id--source-controls--sourceControl_id-"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            The username if the provider is Bitbucket Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>password</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="password"
                            data-endpoint="PUTapi-projects--project_id--source-controls--sourceControl_id-"
                            value="O[2UZ5ij-e/dl4m{o,"
                            data-component="body"
                        />
                        <br />
                        <p>
                            The password if the provider is Bitbucket Example:
                            <code>O[2UZ5ij-e/dl4m{o,</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>global</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="global"
                            data-endpoint="PUTapi-projects--project_id--source-controls--sourceControl_id-"
                            value=""
                            data-component="body"
                        />
                        <br />
                        <p>
                            Accessible in all projects Example:
                            <code>false</code>
                        </p>
                        Must be one of:
                        <ul style="list-style-type: square">
                            <li><code>1</code></li>
                            <li><code></code></li>
                        </ul>
                    </div>
                </form>

                <h2 id="source-controls-DELETEapi-projects--project_id--source-controls--sourceControl_id-">delete</h2>

                <p></p>

                <span id="example-requests-DELETEapi-projects--project_id--source-controls--sourceControl_id-">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request DELETE \
    "https://vito.test/api/projects/1/source-controls/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/source-controls/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-DELETEapi-projects--project_id--source-controls--sourceControl_id-">
                    <blockquote>
                        <p>Example response (204):</p>
                    </blockquote>
                    <pre>
<code>Empty response</code>
 </pre>
                </span>
                <span id="execution-results-DELETEapi-projects--project_id--source-controls--sourceControl_id-" hidden>
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-DELETEapi-projects--project_id--source-controls--sourceControl_id-"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-DELETEapi-projects--project_id--source-controls--sourceControl_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-DELETEapi-projects--project_id--source-controls--sourceControl_id-" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-DELETEapi-projects--project_id--source-controls--sourceControl_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-DELETEapi-projects--project_id--source-controls--sourceControl_id-"
                    data-method="DELETE"
                    data-path="api/projects/{project_id}/source-controls/{sourceControl_id}"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('DELETEapi-projects--project_id--source-controls--sourceControl_id-', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-DELETEapi-projects--project_id--source-controls--sourceControl_id-"
                            onclick="tryItOut('DELETEapi-projects--project_id--source-controls--sourceControl_id-');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-DELETEapi-projects--project_id--source-controls--sourceControl_id-"
                            onclick="cancelTryOut('DELETEapi-projects--project_id--source-controls--sourceControl_id-');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-DELETEapi-projects--project_id--source-controls--sourceControl_id-"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-red">DELETE</small>
                        <b><code>api/projects/{project_id}/source-controls/{sourceControl_id}</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="DELETEapi-projects--project_id--source-controls--sourceControl_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="DELETEapi-projects--project_id--source-controls--sourceControl_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="DELETEapi-projects--project_id--source-controls--sourceControl_id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>sourceControl_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="sourceControl_id"
                            data-endpoint="DELETEapi-projects--project_id--source-controls--sourceControl_id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the sourceControl. Example:
                            <code>1</code>
                        </p>
                    </div>
                </form>

                <h1 id="ssh-keys">ssh-keys</h1>

                <h2 id="ssh-keys-GETapi-projects--project_id--servers--server_id--ssh-keys">list</h2>

                <p></p>

                <p>Get all ssh keys.</p>

                <span id="example-requests-GETapi-projects--project_id--servers--server_id--ssh-keys">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request GET \
    --get "https://vito.test/api/projects/1/servers/32/ssh-keys" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/ssh-keys"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-GETapi-projects--project_id--servers--server_id--ssh-keys">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: 2,
            &quot;user&quot;: {
                &quot;id&quot;: 1,
                &quot;name&quot;: &quot;Saeed Vaziry&quot;,
                &quot;email&quot;: &quot;demo@vitodeploy.com&quot;,
                &quot;created_at&quot;: &quot;2024-12-19T23:19:20.000000Z&quot;,
                &quot;updated_at&quot;: &quot;2025-04-19T21:24:56.000000Z&quot;
            },
            &quot;name&quot;: &quot;Prof. Aurelia Buckridge MD&quot;,
            &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
        },
        {
            &quot;id&quot;: 3,
            &quot;user&quot;: {
                &quot;id&quot;: 1,
                &quot;name&quot;: &quot;Saeed Vaziry&quot;,
                &quot;email&quot;: &quot;demo@vitodeploy.com&quot;,
                &quot;created_at&quot;: &quot;2024-12-19T23:19:20.000000Z&quot;,
                &quot;updated_at&quot;: &quot;2025-04-19T21:24:56.000000Z&quot;
            },
            &quot;name&quot;: &quot;Jaylan Lakin&quot;,
            &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
        }
    ],
    &quot;links&quot;: {
        &quot;first&quot;: &quot;/?page=1&quot;,
        &quot;last&quot;: &quot;/?page=1&quot;,
        &quot;prev&quot;: null,
        &quot;next&quot;: null
    },
    &quot;meta&quot;: {
        &quot;current_page&quot;: 1,
        &quot;from&quot;: 1,
        &quot;last_page&quot;: 1,
        &quot;links&quot;: [
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;&amp;laquo; Previous&quot;,
                &quot;active&quot;: false
            },
            {
                &quot;url&quot;: &quot;/?page=1&quot;,
                &quot;label&quot;: &quot;1&quot;,
                &quot;active&quot;: true
            },
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;Next &amp;raquo;&quot;,
                &quot;active&quot;: false
            }
        ],
        &quot;path&quot;: &quot;/&quot;,
        &quot;per_page&quot;: 25,
        &quot;to&quot;: 2,
        &quot;total&quot;: 2
    }
}</code>
 </pre>
                </span>
                <span id="execution-results-GETapi-projects--project_id--servers--server_id--ssh-keys" hidden>
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-GETapi-projects--project_id--servers--server_id--ssh-keys"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-GETapi-projects--project_id--servers--server_id--ssh-keys"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-GETapi-projects--project_id--servers--server_id--ssh-keys" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-GETapi-projects--project_id--servers--server_id--ssh-keys">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-GETapi-projects--project_id--servers--server_id--ssh-keys"
                    data-method="GET"
                    data-path="api/projects/{project_id}/servers/{server_id}/ssh-keys"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('GETapi-projects--project_id--servers--server_id--ssh-keys', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-GETapi-projects--project_id--servers--server_id--ssh-keys"
                            onclick="tryItOut('GETapi-projects--project_id--servers--server_id--ssh-keys');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-GETapi-projects--project_id--servers--server_id--ssh-keys"
                            onclick="cancelTryOut('GETapi-projects--project_id--servers--server_id--ssh-keys');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-GETapi-projects--project_id--servers--server_id--ssh-keys"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-green">GET</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/ssh-keys</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--ssh-keys"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--ssh-keys"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--ssh-keys"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="GETapi-projects--project_id--servers--server_id--ssh-keys"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                </form>

                <h2 id="ssh-keys-POSTapi-projects--project_id--servers--server_id--ssh-keys">create</h2>

                <p></p>

                <p>Deploy ssh key to server.</p>

                <span id="example-requests-POSTapi-projects--project_id--servers--server_id--ssh-keys">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request POST \
    "https://vito.test/api/projects/1/servers/32/ssh-keys" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"key_id\": \"consequatur\",
    \"name\": \"consequatur\",
    \"public_key\": \"consequatur\"
}"
</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/ssh-keys"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "key_id": "consequatur",
    "name": "consequatur",
    "public_key": "consequatur"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-POSTapi-projects--project_id--servers--server_id--ssh-keys">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;id&quot;: 2,
    &quot;user&quot;: {
        &quot;id&quot;: 1,
        &quot;name&quot;: &quot;Saeed Vaziry&quot;,
        &quot;email&quot;: &quot;demo@vitodeploy.com&quot;,
        &quot;created_at&quot;: &quot;2024-12-19T23:19:20.000000Z&quot;,
        &quot;updated_at&quot;: &quot;2025-04-19T21:24:56.000000Z&quot;
    },
    &quot;name&quot;: &quot;Dr. Cornelius Luettgen V&quot;,
    &quot;created_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;,
    &quot;updated_at&quot;: &quot;2025-04-21T18:40:19.000000Z&quot;
}</code>
 </pre>
                </span>
                <span id="execution-results-POSTapi-projects--project_id--servers--server_id--ssh-keys" hidden>
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-POSTapi-projects--project_id--servers--server_id--ssh-keys"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-POSTapi-projects--project_id--servers--server_id--ssh-keys"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-POSTapi-projects--project_id--servers--server_id--ssh-keys" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-POSTapi-projects--project_id--servers--server_id--ssh-keys">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-POSTapi-projects--project_id--servers--server_id--ssh-keys"
                    data-method="POST"
                    data-path="api/projects/{project_id}/servers/{server_id}/ssh-keys"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('POSTapi-projects--project_id--servers--server_id--ssh-keys', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-POSTapi-projects--project_id--servers--server_id--ssh-keys"
                            onclick="tryItOut('POSTapi-projects--project_id--servers--server_id--ssh-keys');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-POSTapi-projects--project_id--servers--server_id--ssh-keys"
                            onclick="cancelTryOut('POSTapi-projects--project_id--servers--server_id--ssh-keys');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-POSTapi-projects--project_id--servers--server_id--ssh-keys"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-black">POST</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/ssh-keys</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--ssh-keys"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--ssh-keys"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--ssh-keys"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--ssh-keys"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>key_id</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="key_id"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--ssh-keys"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            The ID of the key. Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>name</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="name"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--ssh-keys"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Key name, required if key_id is not provided. Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>public_key</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="public_key"
                            data-endpoint="POSTapi-projects--project_id--servers--server_id--ssh-keys"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            Public Key, required if key_id is not provided. Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                </form>

                <h2 id="ssh-keys-DELETEapi-projects--project_id--servers--server_id--ssh-keys--sshKey_id-">delete</h2>

                <p></p>

                <p>Delete ssh key from server.</p>

                <span id="example-requests-DELETEapi-projects--project_id--servers--server_id--ssh-keys--sshKey_id-">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request DELETE \
    "https://vito.test/api/projects/1/servers/32/ssh-keys/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/servers/32/ssh-keys/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-DELETEapi-projects--project_id--servers--server_id--ssh-keys--sshKey_id-">
                    <blockquote>
                        <p>Example response (204):</p>
                    </blockquote>
                    <pre>
<code>Empty response</code>
 </pre>
                </span>
                <span
                    id="execution-results-DELETEapi-projects--project_id--servers--server_id--ssh-keys--sshKey_id-"
                    hidden
                >
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-DELETEapi-projects--project_id--servers--server_id--ssh-keys--sshKey_id-"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-DELETEapi-projects--project_id--servers--server_id--ssh-keys--sshKey_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span
                    id="execution-error-DELETEapi-projects--project_id--servers--server_id--ssh-keys--sshKey_id-"
                    hidden
                >
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-DELETEapi-projects--project_id--servers--server_id--ssh-keys--sshKey_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-DELETEapi-projects--project_id--servers--server_id--ssh-keys--sshKey_id-"
                    data-method="DELETE"
                    data-path="api/projects/{project_id}/servers/{server_id}/ssh-keys/{sshKey_id}"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('DELETEapi-projects--project_id--servers--server_id--ssh-keys--sshKey_id-', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-DELETEapi-projects--project_id--servers--server_id--ssh-keys--sshKey_id-"
                            onclick="tryItOut('DELETEapi-projects--project_id--servers--server_id--ssh-keys--sshKey_id-');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-DELETEapi-projects--project_id--servers--server_id--ssh-keys--sshKey_id-"
                            onclick="cancelTryOut('DELETEapi-projects--project_id--servers--server_id--ssh-keys--sshKey_id-');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-DELETEapi-projects--project_id--servers--server_id--ssh-keys--sshKey_id-"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-red">DELETE</small>
                        <b><code>api/projects/{project_id}/servers/{server_id}/ssh-keys/{sshKey_id}</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--ssh-keys--sshKey_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--ssh-keys--sshKey_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--ssh-keys--sshKey_id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>server_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="server_id"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--ssh-keys--sshKey_id-"
                            value="32"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the server. Example:
                            <code>32</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>sshKey_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="sshKey_id"
                            data-endpoint="DELETEapi-projects--project_id--servers--server_id--ssh-keys--sshKey_id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the sshKey. Example:
                            <code>1</code>
                        </p>
                    </div>
                </form>

                <h1 id="storage-providers">storage-providers</h1>

                <h2 id="storage-providers-GETapi-projects--project_id--storage-providers">list</h2>

                <p></p>

                <span id="example-requests-GETapi-projects--project_id--storage-providers">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request GET \
    --get "https://vito.test/api/projects/1/storage-providers" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/storage-providers"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-GETapi-projects--project_id--storage-providers">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: 5,
            &quot;project_id&quot;: null,
            &quot;global&quot;: true,
            &quot;name&quot;: &quot;dolores&quot;,
            &quot;provider&quot;: &quot;local&quot;,
            &quot;created_at&quot;: &quot;2025-04-21T18:40:20.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-04-21T18:40:20.000000Z&quot;
        },
        {
            &quot;id&quot;: 6,
            &quot;project_id&quot;: null,
            &quot;global&quot;: true,
            &quot;name&quot;: &quot;dignissimos&quot;,
            &quot;provider&quot;: &quot;dropbox&quot;,
            &quot;created_at&quot;: &quot;2025-04-21T18:40:20.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-04-21T18:40:20.000000Z&quot;
        }
    ],
    &quot;links&quot;: {
        &quot;first&quot;: &quot;/?page=1&quot;,
        &quot;last&quot;: &quot;/?page=1&quot;,
        &quot;prev&quot;: null,
        &quot;next&quot;: null
    },
    &quot;meta&quot;: {
        &quot;current_page&quot;: 1,
        &quot;from&quot;: 1,
        &quot;last_page&quot;: 1,
        &quot;links&quot;: [
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;&amp;laquo; Previous&quot;,
                &quot;active&quot;: false
            },
            {
                &quot;url&quot;: &quot;/?page=1&quot;,
                &quot;label&quot;: &quot;1&quot;,
                &quot;active&quot;: true
            },
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;Next &amp;raquo;&quot;,
                &quot;active&quot;: false
            }
        ],
        &quot;path&quot;: &quot;/&quot;,
        &quot;per_page&quot;: 25,
        &quot;to&quot;: 2,
        &quot;total&quot;: 2
    }
}</code>
 </pre>
                </span>
                <span id="execution-results-GETapi-projects--project_id--storage-providers" hidden>
                    <blockquote>
                        Received response
                        <span id="execution-response-status-GETapi-projects--project_id--storage-providers"></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-GETapi-projects--project_id--storage-providers"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-GETapi-projects--project_id--storage-providers" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-GETapi-projects--project_id--storage-providers">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-GETapi-projects--project_id--storage-providers"
                    data-method="GET"
                    data-path="api/projects/{project_id}/storage-providers"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('GETapi-projects--project_id--storage-providers', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-GETapi-projects--project_id--storage-providers"
                            onclick="tryItOut('GETapi-projects--project_id--storage-providers');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-GETapi-projects--project_id--storage-providers"
                            onclick="cancelTryOut('GETapi-projects--project_id--storage-providers');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-GETapi-projects--project_id--storage-providers"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-green">GET</small>
                        <b><code>api/projects/{project_id}/storage-providers</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="GETapi-projects--project_id--storage-providers"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="GETapi-projects--project_id--storage-providers"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="GETapi-projects--project_id--storage-providers"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                </form>

                <h2 id="storage-providers-POSTapi-projects--project_id--storage-providers">create</h2>

                <p></p>

                <span id="example-requests-POSTapi-projects--project_id--storage-providers">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request POST \
    "https://vito.test/api/projects/1/storage-providers" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"provider\": \"consequatur\",
    \"name\": \"consequatur\",
    \"token\": \"consequatur\",
    \"key\": \"consequatur\",
    \"secret\": \"consequatur\"
}"
</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/storage-providers"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "provider": "consequatur",
    "name": "consequatur",
    "token": "consequatur",
    "key": "consequatur",
    "secret": "consequatur"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-POSTapi-projects--project_id--storage-providers">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;id&quot;: 5,
    &quot;project_id&quot;: null,
    &quot;global&quot;: true,
    &quot;name&quot;: &quot;dolores&quot;,
    &quot;provider&quot;: &quot;local&quot;,
    &quot;created_at&quot;: &quot;2025-04-21T18:40:20.000000Z&quot;,
    &quot;updated_at&quot;: &quot;2025-04-21T18:40:20.000000Z&quot;
}</code>
 </pre>
                </span>
                <span id="execution-results-POSTapi-projects--project_id--storage-providers" hidden>
                    <blockquote>
                        Received response
                        <span id="execution-response-status-POSTapi-projects--project_id--storage-providers"></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-POSTapi-projects--project_id--storage-providers"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-POSTapi-projects--project_id--storage-providers" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-POSTapi-projects--project_id--storage-providers">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-POSTapi-projects--project_id--storage-providers"
                    data-method="POST"
                    data-path="api/projects/{project_id}/storage-providers"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('POSTapi-projects--project_id--storage-providers', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-POSTapi-projects--project_id--storage-providers"
                            onclick="tryItOut('POSTapi-projects--project_id--storage-providers');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-POSTapi-projects--project_id--storage-providers"
                            onclick="cancelTryOut('POSTapi-projects--project_id--storage-providers');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-POSTapi-projects--project_id--storage-providers"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-black">POST</small>
                        <b><code>api/projects/{project_id}/storage-providers</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="POSTapi-projects--project_id--storage-providers"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="POSTapi-projects--project_id--storage-providers"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="POSTapi-projects--project_id--storage-providers"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>provider</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="provider"
                            data-endpoint="POSTapi-projects--project_id--storage-providers"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            The provider (aws, linode, hetzner, digitalocean, vultr, ...) Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>name</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="name"
                            data-endpoint="POSTapi-projects--project_id--storage-providers"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            The name of the storage provider. Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>token</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="token"
                            data-endpoint="POSTapi-projects--project_id--storage-providers"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            The token if provider requires api token Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>key</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="key"
                            data-endpoint="POSTapi-projects--project_id--storage-providers"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            The key if provider requires key Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>secret</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="secret"
                            data-endpoint="POSTapi-projects--project_id--storage-providers"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            The secret if provider requires key Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                </form>

                <h2 id="storage-providers-GETapi-projects--project_id--storage-providers--storageProvider_id-">show</h2>

                <p></p>

                <span id="example-requests-GETapi-projects--project_id--storage-providers--storageProvider_id-">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request GET \
    --get "https://vito.test/api/projects/1/storage-providers/3" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/storage-providers/3"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-GETapi-projects--project_id--storage-providers--storageProvider_id-">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;id&quot;: 5,
    &quot;project_id&quot;: null,
    &quot;global&quot;: true,
    &quot;name&quot;: &quot;facilis&quot;,
    &quot;provider&quot;: &quot;dropbox&quot;,
    &quot;created_at&quot;: &quot;2025-04-21T18:40:20.000000Z&quot;,
    &quot;updated_at&quot;: &quot;2025-04-21T18:40:20.000000Z&quot;
}</code>
 </pre>
                </span>
                <span id="execution-results-GETapi-projects--project_id--storage-providers--storageProvider_id-" hidden>
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-GETapi-projects--project_id--storage-providers--storageProvider_id-"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-GETapi-projects--project_id--storage-providers--storageProvider_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-GETapi-projects--project_id--storage-providers--storageProvider_id-" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-GETapi-projects--project_id--storage-providers--storageProvider_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-GETapi-projects--project_id--storage-providers--storageProvider_id-"
                    data-method="GET"
                    data-path="api/projects/{project_id}/storage-providers/{storageProvider_id}"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('GETapi-projects--project_id--storage-providers--storageProvider_id-', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-GETapi-projects--project_id--storage-providers--storageProvider_id-"
                            onclick="tryItOut('GETapi-projects--project_id--storage-providers--storageProvider_id-');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-GETapi-projects--project_id--storage-providers--storageProvider_id-"
                            onclick="cancelTryOut('GETapi-projects--project_id--storage-providers--storageProvider_id-');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-GETapi-projects--project_id--storage-providers--storageProvider_id-"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-green">GET</small>
                        <b><code>api/projects/{project_id}/storage-providers/{storageProvider_id}</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="GETapi-projects--project_id--storage-providers--storageProvider_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="GETapi-projects--project_id--storage-providers--storageProvider_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="GETapi-projects--project_id--storage-providers--storageProvider_id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>storageProvider_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="storageProvider_id"
                            data-endpoint="GETapi-projects--project_id--storage-providers--storageProvider_id-"
                            value="3"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the storageProvider. Example:
                            <code>3</code>
                        </p>
                    </div>
                </form>

                <h2 id="storage-providers-PUTapi-projects--project_id--storage-providers--storageProvider_id-">
                    update
                </h2>

                <p></p>

                <span id="example-requests-PUTapi-projects--project_id--storage-providers--storageProvider_id-">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request PUT \
    "https://vito.test/api/projects/1/storage-providers/3" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"consequatur\",
    \"global\": true
}"
</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/storage-providers/3"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "consequatur",
    "global": true
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-PUTapi-projects--project_id--storage-providers--storageProvider_id-">
                    <blockquote>
                        <p>Example response (200):</p>
                    </blockquote>
                    <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;id&quot;: 5,
    &quot;project_id&quot;: null,
    &quot;global&quot;: true,
    &quot;name&quot;: &quot;dolores&quot;,
    &quot;provider&quot;: &quot;local&quot;,
    &quot;created_at&quot;: &quot;2025-04-21T18:40:20.000000Z&quot;,
    &quot;updated_at&quot;: &quot;2025-04-21T18:40:20.000000Z&quot;
}</code>
 </pre>
                </span>
                <span id="execution-results-PUTapi-projects--project_id--storage-providers--storageProvider_id-" hidden>
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-PUTapi-projects--project_id--storage-providers--storageProvider_id-"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-PUTapi-projects--project_id--storage-providers--storageProvider_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span id="execution-error-PUTapi-projects--project_id--storage-providers--storageProvider_id-" hidden>
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-PUTapi-projects--project_id--storage-providers--storageProvider_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-PUTapi-projects--project_id--storage-providers--storageProvider_id-"
                    data-method="PUT"
                    data-path="api/projects/{project_id}/storage-providers/{storageProvider_id}"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('PUTapi-projects--project_id--storage-providers--storageProvider_id-', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-PUTapi-projects--project_id--storage-providers--storageProvider_id-"
                            onclick="tryItOut('PUTapi-projects--project_id--storage-providers--storageProvider_id-');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-PUTapi-projects--project_id--storage-providers--storageProvider_id-"
                            onclick="cancelTryOut('PUTapi-projects--project_id--storage-providers--storageProvider_id-');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-PUTapi-projects--project_id--storage-providers--storageProvider_id-"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-darkblue">PUT</small>
                        <b><code>api/projects/{project_id}/storage-providers/{storageProvider_id}</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="PUTapi-projects--project_id--storage-providers--storageProvider_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="PUTapi-projects--project_id--storage-providers--storageProvider_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="PUTapi-projects--project_id--storage-providers--storageProvider_id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>storageProvider_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="storageProvider_id"
                            data-endpoint="PUTapi-projects--project_id--storage-providers--storageProvider_id-"
                            value="3"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the storageProvider. Example:
                            <code>3</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>name</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="name"
                            data-endpoint="PUTapi-projects--project_id--storage-providers--storageProvider_id-"
                            value="consequatur"
                            data-component="body"
                        />
                        <br />
                        <p>
                            The name of the storage provider. Example:
                            <code>consequatur</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>global</code></b>
                        &nbsp;&nbsp;
                        <small>string</small>
                        &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="global"
                            data-endpoint="PUTapi-projects--project_id--storage-providers--storageProvider_id-"
                            value=""
                            data-component="body"
                        />
                        <br />
                        <p>
                            Accessible in all projects Example:
                            <code>true</code>
                        </p>
                        Must be one of:
                        <ul style="list-style-type: square">
                            <li><code>1</code></li>
                            <li><code></code></li>
                        </ul>
                    </div>
                </form>

                <h2 id="storage-providers-DELETEapi-projects--project_id--storage-providers--storageProvider_id-">
                    delete
                </h2>

                <p></p>

                <span id="example-requests-DELETEapi-projects--project_id--storage-providers--storageProvider_id-">
                    <blockquote>Example request:</blockquote>

                    <div class="bash-example">
                        <pre><code class="language-bash">curl --request DELETE \
    "https://vito.test/api/projects/1/storage-providers/3" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>
                    </div>

                    <div class="javascript-example">
                        <pre><code class="language-javascript">const url = new URL(
    "https://vito.test/api/projects/1/storage-providers/3"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre>
                    </div>
                </span>

                <span id="example-responses-DELETEapi-projects--project_id--storage-providers--storageProvider_id-">
                    <blockquote>
                        <p>Example response (204):</p>
                    </blockquote>
                    <pre>
<code>Empty response</code>
 </pre>
                </span>
                <span
                    id="execution-results-DELETEapi-projects--project_id--storage-providers--storageProvider_id-"
                    hidden
                >
                    <blockquote>
                        Received response
                        <span
                            id="execution-response-status-DELETEapi-projects--project_id--storage-providers--storageProvider_id-"
                        ></span>
                        :
                    </blockquote>
                    <pre
                        class="json"
                    ><code id="execution-response-content-DELETEapi-projects--project_id--storage-providers--storageProvider_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
                </span>
                <span
                    id="execution-error-DELETEapi-projects--project_id--storage-providers--storageProvider_id-"
                    hidden
                >
                    <blockquote>Request failed with error:</blockquote>
                    <pre><code id="execution-error-message-DELETEapi-projects--project_id--storage-providers--storageProvider_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
                </span>
                <form
                    id="form-DELETEapi-projects--project_id--storage-providers--storageProvider_id-"
                    data-method="DELETE"
                    data-path="api/projects/{project_id}/storage-providers/{storageProvider_id}"
                    data-authed="0"
                    data-hasfiles="0"
                    data-isarraybody="0"
                    autocomplete="off"
                    onsubmit="event.preventDefault(); executeTryOut('DELETEapi-projects--project_id--storage-providers--storageProvider_id-', this);"
                >
                    <h3>
                        Request&nbsp;&nbsp;&nbsp;
                        <button
                            type="button"
                            style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-tryout-DELETEapi-projects--project_id--storage-providers--storageProvider_id-"
                            onclick="tryItOut('DELETEapi-projects--project_id--storage-providers--storageProvider_id-');"
                        >
                            Try it out ⚡
                        </button>
                        <button
                            type="button"
                            style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-canceltryout-DELETEapi-projects--project_id--storage-providers--storageProvider_id-"
                            onclick="cancelTryOut('DELETEapi-projects--project_id--storage-providers--storageProvider_id-');"
                            hidden
                        >
                            Cancel 🛑
                        </button>
                        &nbsp;&nbsp;
                        <button
                            type="submit"
                            style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin"
                            id="btn-executetryout-DELETEapi-projects--project_id--storage-providers--storageProvider_id-"
                            data-initial-text="Send Request 💥"
                            data-loading-text="⏱ Sending..."
                            hidden
                        >
                            Send Request 💥
                        </button>
                    </h3>
                    <p>
                        <small class="badge badge-red">DELETE</small>
                        <b><code>api/projects/{project_id}/storage-providers/{storageProvider_id}</code></b>
                    </p>
                    <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Content-Type</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Content-Type"
                            data-endpoint="DELETEapi-projects--project_id--storage-providers--storageProvider_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>Accept</code></b>
                        &nbsp;&nbsp; &nbsp; &nbsp;
                        <input
                            type="text"
                            style="display: none"
                            name="Accept"
                            data-endpoint="DELETEapi-projects--project_id--storage-providers--storageProvider_id-"
                            value="application/json"
                            data-component="header"
                        />
                        <br />
                        <p>
                            Example:
                            <code>application/json</code>
                        </p>
                    </div>
                    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>project_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="project_id"
                            data-endpoint="DELETEapi-projects--project_id--storage-providers--storageProvider_id-"
                            value="1"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the project. Example:
                            <code>1</code>
                        </p>
                    </div>
                    <div style="padding-left: 28px; clear: unset">
                        <b style="line-height: 2"><code>storageProvider_id</code></b>
                        &nbsp;&nbsp;
                        <small>integer</small>
                        &nbsp; &nbsp;
                        <input
                            type="number"
                            style="display: none"
                            step="any"
                            name="storageProvider_id"
                            data-endpoint="DELETEapi-projects--project_id--storage-providers--storageProvider_id-"
                            value="3"
                            data-component="url"
                        />
                        <br />
                        <p>
                            The ID of the storageProvider. Example:
                            <code>3</code>
                        </p>
                    </div>
                </form>
            </div>
            <div class="dark-box">
                <div class="lang-selector">
                    <button type="button" class="lang-button" data-language-name="bash">bash</button>
                    <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                </div>
            </div>
        </div>
    </body>
</html>
