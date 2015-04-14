## List Events ##

An API request must be of the following form:

```
 http://{url_to_software}/api/api.php?cmd=eventList&parameters
```

Certain parameters are required while some are optional. As is standard in URLs, all parameters are separated using the ampersand (&) character. The list of parameters and their possible values are enumerated below.

The API defines a request using the following URL parameters:

  * start (optional) - Starting index of the results.
  * limit (optional) - Number of results to be returned.
  * eventId (optional) - Filter based on eventId.
  * eventTypeId (optional) - Filter based on eventTypeId.
  * geographyId (optional) - Filter based on geoId.
  * searchFormat (optional) - exact, left, right, both. Defaults to both.
  * value (optional) - Custom filter value for field specified.


## Output Formats ##

  * [json](#JSON_Output_Formats.md) - Available


---

## Responses ##

Responses are returned in the format indicated by the output flag within the URL request's path.

> ### JSON Output Formats ###
    * success (bool) - If response was successful or not. If it false see [JSON Error Response](http://code.google.com/p/biodiversity-imageserver/wiki/jsonErrorResponse) for more details
    * processTime (float) - Time it takes to complete the request.
    * totalCount (int) - total count of records
    * results (object) - Array of item objects.

  * results (object)
    * eventId (int) - Db id of the event.
    * geographyId (int) - geoId of the event.
    * eventDate (datetime) - Date time format YYYY-MM-DD HH-MM-SS.
    * eventTypeId (int) - Db id of event type.
    * title (string) - Title of the event.
    * description (string) - Description of the event.


---

## Example Requests ##

1. This example request list all the events.

```
 http://{path_to_software}/api/api.php?cmd=eventList
```

> Response:
```
{
    "success": true,
    "processTime": 0.00039505958557129,
    "totalCount": 3,
    "results": [
        {
            "eventId": 3,
            "geographyId": 0,
            "eventDate": "2012-05-30 04:28:15",
            "eventTypeId": 1,
            "title": "{title}",
            "description": "{description}"
        }
    ]
}
```