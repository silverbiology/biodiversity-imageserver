## List Collections ##

An API request must be of the following form:

```
 http://{url_to_software}/api/api.php?cmd=collectionList
```

Certain parameters are required while some are optional. As is standard in URLs, all parameters are separated using the ampersand (&) character. The list of parameters and their possible values are enumerated below.

The API defines a request using the following URL parameters:

  * start (optional) - Starting index of the result.
  * limit (optional) - Number of results.
  * collectionId (optional) - {collectionId}
  * searchFormat(optional) - {searchFormat}
  * value (optional) - {value}
  * group (optional) - {group}
  * dir (optional) - {dir}
  * code (optional) - {code}

## Output Formats ##

  * [json](#JSON_Output_Formats.md) - Available


---

## Responses ##

Responses are returned in the format indicates by the output flag within the URL request's path.

> ### JSON Output Formats ###
    * success (bool) - If response was successful or not. If it false see [JSON Error Response](http://code.google.com/p/biodiversity-imageserver/wiki/jsonErrorResponse) for more details
    * processTime (float) - Time it takes to complete the request.
    * totalCount (int) - Total count of Collections.
    * records (object) - Array of item objects.

  * records (object)
    * collectionId (int) - Db id of the collection.
    * name (string) - Name of the collection.
    * code(string) - Code of the collection.
    * collectionSize (int) - Size of the collection.


---

## Example Requests ##

1. This example request for the list of available storage device(s).

```
 http://{path_to_software}/api/api.php?cmd=collectionList
```

> Response:
```
{
    "success": true,
    "processTime": 0.0010240077972412,
    "totalCount": 12,
    "records": [
        {
            "collectionId": 1,
            "name": "ROM's Green Plant Herbarium",
            "code": "TRT",
            "collectionSize": 2000
        }
        ...
    ]
}
```