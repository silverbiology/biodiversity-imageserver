## List Storage Devices ##

An API request must be of the following form:

```
 http://{url_to_software}/api/api.php?cmd=storageDeviceList
```

No URL parameters are required along with this API request.

## Output Formats ##

  * [json](#JSON_Output_Formats.md) - Available


---

## Responses ##

Responses are returned in the format indicates by the output flag within the URL request's path.

> ### JSON Output Formats ###
    * success (bool) - If response was successful or not. If it false see [JSON Error Response](http://code.google.com/p/biodiversity-imageserver/wiki/jsonErrorResponse) for more details
    * processTime (float) - Time it takes to complete the request.
    * records (object) - Array of item objects.

  * records (object)
    * storageDeviceId (int) - Db id of the storage device.
    * name (string) - Name of the storage device.
    * description (string) - Brief description of the storage device.
    * type (string) - Type of the storage device.
    * baseUrl (string) - Base url of the storage device.
    * basePath (string) - Path used for storage within storage device.
    * userName (string) - User name used for authentication.
    * password (string) - Password used for authentication.
    * key (string) - Key used for authentication.
    * active (bool) - Current status of the storage device.
    * defaultStorage (int) - Indicates default storage device with value 1.
    * extra2 (reserved) - Reserved for future use.

---

## Example Requests ##

1. This example request for the list of available storage device(s).

```
 http://{path_to_software}/api/api.php?cmd=storageDeviceList
```

> Response:
```
{
    "success": true,
    "processTime": 7.605553E-5,
    "records": [
        {
            "storageDeviceId": 1,
            "name": "Amazon S3",
            "description": "Amazon Simple Storage Service",
            "type": "S3",
            "baseUrl": "http://{base_url}/",
            "basePath": "{base_path}",
            "userName": "{user_name}",
            "password": "{password}",
            "key": "{key}",
            "active": "true",
            "defaultStorage": 1,
            "extra2": "{blank}"
        }
    ]
}
```