## Edit Set Value ##

An API request must be of the following form:

```
 http://{url_to_software}/api/api.php?cmd=editSetValue&parameters
```

Certain parameters are required while some are optional. As is standard in URLs, all parameters are separated using the ampersand (&) character. The list of parameters and their possible values are enumerated below.

The API defines a request using the following URL parameters:

  * authMode (optional) - Type of authentication to be used.
  * key (required) - Key required for authentication.

Note that key is required only if authMode is set to key. If authMode is not set then the user must be logged in to make this API request.

  * id (required) - Id of the set value.
  * sId (required) - Id of the set.
  * valueId (required) - Id of the value.
  * rank (optional) - Rank for the entry.

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

1. This example request edits a set value.

```
 http://{path_to_software}/api/api.php?cmd=editSetValue&authMode=key&key={your_key}&id=9&sId=1&valueId=2
```

> Response:
```
{
    "success":true,
    "processTime":0.0099129676818848
}
```