## Process Queue ##

An API request must be of the following form:

```
 http://{url_to_software}/api/api.php?cmd=processQueue&parameters
```

Certain parameters are required while some are optional. As is standard in URLs, all parameters are separated using the ampersand (&) character. The list of parameters and their possible values are enumerated below.

The API defines a request using the following URL parameters:

  * limit (optional) - Number of items to be processed from queue.
  * stop (optional) - {stop}
  * imageId (optional) - JSON array containing image ids to be processed.

> Note that if no parameters are provided, all items in process queue with process type 'all' will be processed.

## Output Formats ##

  * [json](#JSON_Output_Formats.md) - Available


---

## Responses ##

Responses are returned in the format indicated by the output flag within the URL request's path.

> ### JSON Output Formats ###
    * success (bool) - If response was successful or not. If it false see [JSON Error Response](http://code.google.com/p/biodiversity-imageserver/wiki/jsonErrorResponse) for more details
    * processTime (float) - Time it takes to complete the request.
    * totalCount (int) - Number of records processed.


---

## Example Requests ##

1. This example request process all records in process queue with process type 'all'.

```
 http://{path_to_software}/api/api.php?cmd=processQueue
```

> Response:
```
{
    "success":true,
    "processTime":0.00035810470581055,
    "totalCount":1
}
```