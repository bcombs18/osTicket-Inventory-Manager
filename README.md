# osTicket Inventory Manager

## Overview
- osTicket Inventory Manager adds Inventory Tracking functionality to your osTicket instance so you can quickly find information about assets deployed in your environment. Take a look at the included features below. There's more to come!

## Requirements
- PHP version 8.0
- MySQL database 5.5
- osTicket v1.16.1

## Installation
1. Download and Unzip the osTicket Inventory Manager plugin.
2. Place the unzipped folder in /osTicket/include/plugins folder.
3. From the Inventory Manager Plugin root, copy the dispatcher.php file to your osTicket/scp directory.
4. Navigate to Admin Panel > Manage > Plugins. Choose "Add a New Plugin".
5. Select the osTicket Inventory Plugin and click install.
6. Enable the plugin and then click on the plugin to access the plugin settings.
7. Check "Staff Backend Interface" and select "Inventory" from the Forms dropdown.
8. You can now access the plugin interface from Agent Panel > Applications > Inventory.

## Features
- Create custom global and personal queues
- Assign assets to osTicket users
- Customizeable data fields to fit your data needs
- Export customizeable reports
- Add notes to your assets
- Retire assets that are no longer deployed in your organization

## Screenshots
![Dashboard](/images/Dashboard.png)
![Dashboard-Preview](/images/Dashboard-Preview.png)
![Asset Info](/images/AssetInfo.png)
![Add Asset](/images/AddAsset.png)
![Import Assets](/images/Import.png)
![Advanced Search](/images/AdvancedSearch.png)
![Global Queues](/images/GlobalQueues.png)
