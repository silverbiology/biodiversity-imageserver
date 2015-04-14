## List Images ##

An API request must be of the following form:

```
 http://{url_to_software}/api/api.php?cmd=imageList&parameters
```

Certain parameters are required while some are optional. As is standard in URLs, all parameters are separated using the ampersand (&) character. The list of parameters and their possible values are enumerated below.

The API defines a request using the following URL parameters:

  * start (optional) - Starting index of the result.
  * limit (optional) - Number of results.
  * order (optional) - {order}
  * showOCR (optional) - {showOCR}
  * filter (optional) - {filter}
  * imageId (optional) - Id of the image.
  * searchFormat (optional) - exact, left, right, both. Default both.
  * value (optional) - {value}
  * sort (optional) - {sort}
  * dir (optional) - {dir}
  * code (optional) - {code}
  * characters (optional) - {characters}
  * browse (optional) - {browse}
  * searchValue (optional) - {searchValue}
  * searchType (optional) - {searchType}

## Output Formats ##

  * [json](#JSON_Output_Formats.md) - Available


---

## Responses ##

Responses are returned in the format indicated by the output flag within the URL request's path.

> ### JSON Output Formats ###
    * success (bool) - If response was successful or not. If it false see [JSON Error Response](http://code.google.com/p/biodiversity-imageserver/wiki/jsonErrorResponse) for more details
    * processTime (float) - Time it takes to complete the request.
    * totalCount (int) - Number of results.
    * records (object) - Array of item objects.

  * record (object)
    * imageId (int) - Id of the image.
    * filename (string) - Filename of the image.
    * timestampModified (datetime) - Last modified time stamp.
    * barcode (string) - Barcode of the image.
    * width (int) - Width of the image.
    * height (int) - Height of the image.
    * family (string) - {Family}
    * genus (string) - {Genus}
    * specificEpithet (string) - {SpecificEpithet}
    * flickrPlantId (int) - {flickrPlantId}
    * flickrModified (datetime) - {flickrModified}
    * flickrDetails (string) - {flickrDetails}
    * picassaPlantId (int) - {picassaPlantId}
    * picassaModified (datetime) - {picassaModified}
    * gTileProcessed (int) - {gTileProcessed}
    * zoomEnabled (int) - {zoomEnabled}
    * processed (int) - {processed}
    * boxFlag (int) - {boxFlag}
    * ocrFlag (int) - {ocrFlag}
    * rating (float) - {rating}
    * nameFinderFlag (int) - {nameFinderFlag }
    * nameFinderValue (string) - {nameFinderValue}
    * scientificName (string) - {scientificName}
    * collectionCode (string) - {collectionCode}
    * globalUniqueIdentifier (string) - {globalUniqueIdentifier}
    * path (string) - Path to the image.
    * ext (string) - File extension of the image.
    * enFlag - {enFlag}


---

## Example Requests ##

1. This example request lists all images.

```
 http://{path_to_software}/api/api.php?cmd=imageList
```

> Response:
```
{
    "success": true,
    "processTime": 0.09375,
    "totalCount": 1,
    "data": [
        {
            "imageId": 18,
            "filename": "{filename}",
            "timestampModified": "2012-05-17 07:52:53",
            "barcode": "{barcode}",
            "width": {width},
            "height": {height},
            "family": "{family}",
            "genus": "{genus}",
            "specificEpithet": "{SpecificEpithet}",
            "flickrPlantId": 0,
            "flickrModified": "0000-00-00 00:00:00",
            "flickrDetails": "",
            "picassaPlantId": 0,
            "picassaModified": "0000-00-00 00:00:00",
            "gTileProcessed": 0,
            "zoomEnabled": 0,
            "processed": 0,
            "boxFlag": 1,
            "ocrFlag": 0,
            "rating": 3.5,
            "nameFinderFlag": 0,
            "nameFinderValue": "",
            "scientificName": "",
            "collectionCode": "",
            "globalUniqueIdentifier": "",
            "path": "http://{path}/",
            "ext": "{ext}"
        }
    ]
}
```