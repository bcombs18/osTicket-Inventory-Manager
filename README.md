# osTicket Inventory Manager

## Overview
- osTicket Inventory Manager adds Inventory Tracking functionality to your osTicket instance so you can quickly find information about assets deployed in your environment. Take a look at the currently included features below.

## Features
- Create custom global and personal queues
- Assign assets to osTicket users
- Customizeable data fields to fit your data needs
- Export customizeable reports
- Add notes to your assets
- Retire assets that are no longer deployed in your organization
- Access osTicket Inventory Manager with the API to create/update assets. A possible usecase for the API is to allow your imaging server to automatically create/update assets.

## Requirements
- PHP version 8.0 (PHP v8.1 is not supported)
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

## API Setup
- To enable the API for Asset Creation, you must first complete the following setup on your webserver. This process is required due to the osTicket Inventory Plugin being inaccessible to Single Sign On authentication for security purposes.
1. Move the entire **inventory-api** folder in the root of the **osTicket Inventory Manager** directory to **/osTicket/**.
2. Replace the **web.config** file in **/osTicket/** with the **web.config** file located in **/osTicket/inventory-api/**.
3. The API should now be accesible. See section below on API usage.

## API Usage
1. First, create a valid API key from "Inventory Manager" > "Settings" > "API". Be sure to check the "Can Create Assets" box.
2. You can then send a POST request to **[URI]/inventory-api/assets.json**. i.e. http://your.domain.com/osTicket/inventory-api/assets.json
3. Your payload needs to be in JSON format, see example below.
4. If your request is successful, you will receive a "201 Created" response.

**Note:** 
- If you modify the Inventory Form, all additional data fields will become available to the API.
- When osTicket Inventory Manager recieves a request, the serial number will be checked for existence. If an entry with the same serial number is found,           that asset will be updated instead of creating a new duplicate entry.

**Example JSON payload:**

    {
      'host_name': 'Computer Hostname',
      'manufacturer': 'Computer Manufacturer',
      'model': 'Computer Model',
      'serial_number': '1234ABCD',
      'location': 'Some location'
    }
                
**Curl example:**
                
    curl -d "{}" -H "X-API-Key: [API KEY HERE]" http://[uri]/inventory-api/assets.json

## Customization
### Adding Custom Data Fields
1. From the home-page of the plugin, click on "Settings". (You must have Administrator privileges for this button to be visible)
2. Select the "Inventory" form.
3. Add whatever fields you want to be able to track with the plugin.
4. Custom fields will be added to the importer automatically for use with customized CSV files.

### Creating Custom Queues
1. Go to "Admin Panel" > "Settings" > "Tickets" > "Queues"
2. At the bottom of the list of queues, you will see "Assets", "Assets / Retired Assets", "Assets / Active Assets", "Assets / "Unassigned Assets". 
3. You can create new queues from this menu, or modify the existing queues to show the data you want to see at a glance. **(Note there seems to be an issue with modifying osTicket queues where upon saving changes, an error message appears. Regardless of this error, your changes are still saved and are immediately available)**

## Screenshots
![Dashboard](/images/Dashboard.png)
![Dashboard-Preview](/images/Dashboard-Preview.png)
![Asset Info](/images/AssetInfo.png)
![Add Asset](/images/AddAsset.png)
![Import Assets](/images/Import.png)
![Advanced Search](/images/AdvancedSearch.png)
![Global Queues](/images/GlobalQueues.png)
