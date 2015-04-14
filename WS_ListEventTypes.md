## List Events Types ##

An API request must be of the following form:

```
 http://{url_to_software}/api/api.php?cmd=eventTypeList&parameters
```

Certain parameters are required while some are optional. As is standard in URLs, all parameters are separated using the ampersand (&) character. The list of parameters and their possible values are enumerated below.

The API defines a request using the following URL parameters:

  * start (optional) - Starting index of the results.
  * limit (optional) - Number of results to be returned.
  * eventTypeId (optional) - Filter based on eventTypeId.
  * title (optional) - Filter based on title.
  * searchFormat (optional) - exact, left, right or both `[default]`.
  * value (optional) - Custom filter value for field specified.

## Output Formats ##

  * [json](#JSON_Output_Formats.md) - Available


---

## Responses ##

Responses are returned in the format indicated by the output flag within the URL request's path.

> ### JSON Output Formats ###
    * success (bool) - If response was successful or not. If it false see [JSON Error Response](http://code.google.com/p/biodiversity-imageserver/wiki/jsonErrorResponse) for more details
    * processTime (float) - Time it takes to complete the request.
    * results (object) - Array of item objects.

  * results (object)
    * eventTypeId (int) - Db id of event type.
    * title (string) - Title of the event.
    * description (string) - Description of the event.
    * lastModifiedBy (int) - {lastModifiedBy}
    * modifiedTime (datetime) - Date time format YYYY-MM-DD HH-MM-SS.


---

## Example Requests ##

1. This example request list all the event types.

```
 http://{path_to_software}/api/api.php?cmd=eventTypeList
```

> Response:
```
{
    "success": true,
    "processTime": 0.00040197372436523,
    "results": [
        {
            "eventTypeId": 1,
            "title": "{title}",
            "description": "{description}",
            "lastModifiedBy": 1,
            "modifiedTime": "2012-05-22 04:11:11"
        }
    ]
}
```