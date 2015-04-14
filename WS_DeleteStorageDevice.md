An API request must be of the following form:

```
 http://{url_to_software}/api/api.php?cmd=storageDeviceDelete&parameters
```

Certain parameters are required while some are optional. As is standard in URLs, all parameters are separated using the ampersand (&) character. The list of parameters and their possible values are enumerated below.

The API defines a request using the following URL parameters:

  * storageDeviceId (required) - The id of the storage device to be deleted.

## Output Formats ##

  * [json](#JSON_Output_Formats.md) - Available


---

## Responses ##

Responses are returned in the format indicated by the output flag within the URL request's path.

> ### JSON Output Formats ###
    * success (bool) - If response was successful or not. If it false see [JSON Error Response](http://code.google.com/p/biodiversity-imageserver/wiki/jsonErrorResponse) for more details
    * processTime (float) - Time it takes to complete the request.


---

## Example Requests ##

1. This example request creates a new storage device.

```
 http://{path_to_software}/api/api.php?cmd=storageDeviceDelete&storageDeviceId=3
```

> Response:
```
{
    "success":true,
    "processTime":0.0085549354553223
}
```