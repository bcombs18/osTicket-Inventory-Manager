# osTicket Inventory Manager

## Requirements
- PHP version 8.0
- MySQL database 5.5
- osTicket v1.16.1

## Installation
1. Download and Unzip the osTicket Inventory Manager plugin.
2. Place the unzipped folder in /osTicket/include/plugins folder.
3. Move the dispatcher.php file from the osTicket Inventory plugin root to the osTicket/scp directory.
4. Move the scp.js file from osTicket-Inventory-Manager/assets/js to osTicket/scp/js (replace the original).
5. Navigate to Admin Panel > Manage > Plugins. Choose "Add a New Plugin".
6. Select the osTicket Inventory Plugin and click install.
7. Enable the plugin and then click on the plugin to access the plugin settings.
8. Check "Staff Backend Interface" and select "Inventory" from the Forms dropdown.
9. You can now access the plugin interface from Agent Panel > Applications > Inventory.
