## Barcode Detect ##

Uses the [ZBar library](http://zbar.sourceforge.net/).

_It supports many popular symbologies (types of bar codes) including EAN-13/UPC-A, UPC-E, EAN-8, Code 128, Code 39, Interleaved 2 of 5 and QR Code._

An API request must be of the following form:

```
 http://{url_to_software}/api/api.php?cmd=imageDetectBarcode&parameters
```

Certain parameters are required while some are optional. As is standard in URLs, all parameters are separated using the ampersand (&) character. The list of parameters and their possible values are enumerated below.

The API defines a request using the following URL parameters:

  * imageId (required) — This is the db id for the image.
  * barcode (required) - This is barcode of the image.
  * force (optional) - This will rerun the analysis and rewrite the cache.  Could be used if a new version of ZBar is released.

Note that you must provide either imageId or barcode but not both.

## Output Formats ##

  * [json](#JSON_Output_Formats.md) - Available


---

## Responses ##

Responses are returned in the format indicates by the output flag within the URL request's path.

> ### JSON Output Formats ###
    * success (bool) - If response was successful or not. If it false see [JSON Error Response](http://code.google.com/p/biodiversity-imageserver/wiki/jsonErrorResponse) for more details
    * processTime (float) - Time it takes to complete the request.
    * totalCount (int) - Number of barcodes returned.
    * lastTested (int) - Time the zbar was ran.
    * software (string) - Name of the barcode software.
    * version (float) - Version of the software.
    * results (object) - Array of item objects.

  * results (object)
    * code(String) - The barcode format
    * value(String)


---

## Example Requests ##

1. This example request for the barcode(s).  We can see that the request and the results match.

```
 http://{path_to_software}/api/api.php?cmd=imageDetectBarcode&barcode=USMS000018152
```

> Response:
```
{
    "success": true,
    "processTime": 0.07549786567688,
    "totalCount": 1,
    "lastTested": 1335738650,
    "software": "zbarimg",
    "version": "0.10",
    "results": [
        {
            "code": "CODE-128",
            "value": "USMS000018152"
        }
    ]
}
```