## Get Image ##

An API request must be of the following form:

```
 http://{url_to_software}/api/api.php?cmd=get_image&parameters
```

Certain parameters are required while some are optional. As is standard in URLs, all parameters are separated using the ampersand (&) character. The list of parameters and their possible values are enumerated below.

Allowed Image Types: JPG, PNG, GIF, TIFF

The API defines a request using the following URL parameters:

  * image\_id (required) - Id of the image.
  * barcode (required) - Barcode of the image.
  * size (required) - Size of the returned image (s, m, l).
  * width (required) - Width of the returned image in pixels.
  * height (required) - Height of the returned image.
  * type (optional) - Type of the returned image.

**Note**
  * You must provide either image\_id or barcode but not both.
  * You must provide either size or width and height but not both.

## Output Formats ##

> If the request is successful, the image will be returned with the required MIME type. If an error occurs, a JSON response will be returned indicating the error.

  * [json](#JSON_Output_Formats.md) - Available


---

## Responses ##

Responses are returned in the format indicated by the output flag within the URL request's path.

> ### JSON Output Formats ###
    * success (bool) - If response was successful or not. If it false see [JSON Error Response](http://code.google.com/p/biodiversity-imageserver/wiki/jsonErrorResponse) for more details
    * processTime (float) - Time it takes to complete the request.


---

## Example Requests ##

1. This example request returns an image with the specified size..

```
 http://{path_to_software}/api/api.php?cmd=get_image&image_id=18&size=s
```

> Response:

{image}