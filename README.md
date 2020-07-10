
CONTENTS OF THIS FILE
---------------------

-   Introduction
-   Requirements
-   Installation
-   Configuration

INTRODUCTION
------------

Stratus Meridian' App dashboard for Apigee integrates with Apigee Edge module 
and provides a dashboard for Apps created on Developer portal. 
The following steps can be performed using this module

1. Approve or Revoke access to any App via Developer portal, 
instead of performing this action on Edge
2. Search for Apps by name
3. Search and Sort Apps by date created, date modified
4. Apigee Team Apps compatible

REQUIREMENTS
------------

This module requires
- Apigee Edge (https://www.drupal.org/project/apigee_edge)

INSTALLATION
----------------

Install as any Drupal module.
1. Download it using `composer require drupal/sm_appdashboard_apigee`
2. Enable the module from `/admin/modules/`

CONFIGURATION
-----------------
Once module is enabled, you will be prompted to **re-sync data from Edge**. 
Once completed you can navigate to the dashboard from `/admin/config/apigee-edge/apps-dashboard`
