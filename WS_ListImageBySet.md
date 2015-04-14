## List Image By Set ##

An API request must be of the following form:

```
 http://{url_to_software}/api/api.php?cmd=listImageBySet&parameters
```

Certain parameters are required while some are optional. As is standard in URLs, all parameters are separated using the ampersand (&) character. The list of parameters and their possible values are enumerated below.

The API defines a request using the following URL parameters:

  * sId (optional) - Id of the set.

## Output Formats ##

  * [json](#JSON_Output_Formats.md) - Available


---

## Responses ##

Responses are returned in the format indicated by the output flag within the URL request's path.

> ### JSON Output Formats ###
    * success (bool) - If response was successful or not. If it false see [JSON Error Response](http://code.google.com/p/biodiversity-imageserver/wiki/jsonErrorResponse) for more details
    * processTime (float) - Time it takes to complete the request.
    * data (object) - Array of item objects.

  * data (object)
    * id (int) - Id of the set.
    * name (string) - Name of the set.
    * description (string) - Description of the set.
    * values (object) - Array of item objects.

  * values (object)
    * id (int) - Id of the set value.
    * value (string) - Name of set value.
    * rank (int) - Rank of set value.
    * images (object) - Array of item objects.

  * images (object)
    * id (int) - Id of the image.
    * filename (string) - File name of the image.
    * url (string) - URL of the image.


---

## Example Requests ##

1. This example request list image by set.

```
 http://{path_to_software}/api/api.php?cmd=listImageBySet
```

> Response:
```
{
    "success": true,
    "processTime": 0.01101017,
    "data": [
        {
            "id": 3,
            "name": "{set_name}",
            "description": "{set_description}",
            "values": [
                {
                    "id": 4,
                    "value": "{value}",
                    "rank": 1,
                    "images": [
                        {
                            "id": 50,
                            "filename": "picture.jpg",
                            "url": "http://{url_to_image}/{path}/picture.jpg"
                        }
                    ]
                }
            ]
        }
    ]
}
```