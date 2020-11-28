
CONTENTS OF THIS FILE
---------------------

-   Introduction
-   Requirements
-   Installation
-   How to use?

INTRODUCTION
------------

Stratus Meridian' App dashboard Apigee  Rules module provides the functional 
integration with the Rules module. For an example you can send an email when the
Apps status getting updated  from 
Apigee Apps Dashborad(`/admin/config/apigee-edge/apps-dashboard`).


REQUIREMENTS
------------

This module requires
- Rules (https://www.drupal.org/project/rules)
- Stratus Meridian's App Dashboard for Apigee (https://www.drupal.org/project/sm_appdashboard_apigee)

INSTALLATION
------------

Install as any Drupal module.
1. Download it using `composer require drupal/sm_appdashboard_apigee`
2. Enable the module from `/admin/modules/`

HOW TO USE?
-----------

After the installation of module you can got ahead and create new Rule from 
Rules configuration page (`/admin/config/workflow/rules`), Then you can find the
following options under the `React on Event` on Event selection 

1. `After updating team app status via apigee appsdashbord`
2. `After approving team app status via apigee appsdashbord`
3. `After revoking team app status via apigee appsdashbord`

By selecting one of the options from the above list, you can set an action 
to your rule set.