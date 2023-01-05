# Changelog

To get the version for your current 1100CC installation, view `/APP/CORE/CMS/info/version.txt`

## VERSION 10.1

Initial release.

## VERSION 10.2

* Users: Improved status/error logging of users and their class.
* Language: Removed archaic language flags when working with language settings.
* SQL: Fixed DELETE query not working in PostgreSQL in cms_forms.
* Front-end: Changed the JavaScript spread operator for ElementObjectByParameters to better support a bit older browser versions.
* Parsing: Improved URL parsing in HTML and plain text in FormatBBCode.
* Various fixes, modernisation, and overall streamlining.

## VERSION 10.3

* Labels: Improved Labels parsing & printing. Changing and printing variables can now be done in a single call to getLabel.
* Jobs: Jobs are now tracked/run every second instead of by minute.
* SQL: Fixed uncaught MySQL Exceptions in DBMysql by turning them in a DBTrouble Exception, where they belong.
* Front-end: AutoCompleter in multi-mode can now also be sortable.
* Back-end: Added/streamlined various array functions arrParseRecursive/arrFilterRecursive/arrValuesRecursive/arrHasValuesRecursive/arrHasKeysRecursive/arrMergeValues.
* Various fixes, modernisation, and overall streamlining.

## VERSION 10.4

* Response: Switched the parseDelay/parsePost regular expressions for memory optimised ones.
* Response: Updated the output and render parameters by changing OUTPUT_HTML to OUTPUT_XML to be able to caputure all XML-based languages, added OUTPUT_JSONP for external legacy APIs. Added RENDER_XML next to RENDER_HTML to do additional language-specific rendering.
* JSON Streaming: Renamed StreamJSON to StreamJSONInput and can now pause and resume streams. Added StreamJSONOutput to parse and stream objects/arrays to JSON without limitations to complexitity. This is similar to the existing Response Streaming but aimed at resource output only (i.e. files).
* Back-end: Updated module command (HOME) relaying to be able to configure any kind of route for a request.
* Front-end: Added the ability to track and abort any kind of running request.
* File Upload: Added the possibility to regulate the client-side maximum upload file size.
* WebSocket Server & Service: Updated and extended the WebSocket related classes to be able to do elaborate data exchanges between server processes and client response.
* WebSocket Client: Added a client class, tailored to communicate with other 1100CC sockets.
* Documentation Module: Added a new Documentation module to create and publish layered documentations or guides through 1100CC.
* Slider Module: Added two new modes to the slider module: 'flow' (horizontal paralax-like interaction) and 'scroll' (full-screen scrolling).
* Back-end: Implemented a heat-based request throttle to be able to limit requests from spamming clients.
* Front-end: Datatables can be ordered by multiple columns at the same time. 
* Front-end: Introducing a new class EmbedDocument to dynamically embed (iframe) other 1100CC sites as if they native to of the main document. This class would resize the parent document based on the embedded document flow and resize the embedded document based on the parent window size.
* Front-end: Developed class CaptureElementImage to capture multi-layer Canvas and SVG elements as a downloadable high-resolution image.
* Various fixes, modernisation, and overall streamlining.

## VERSION 10.5

* Front-end: Expanded class EmbedDocument into seperate classes DocumentEmbedded and DocumentEmbedding to allow for dynamic loading of 1100CC documents in any website using the snippet DocumentEmbeddingListener.
* Data Exchange: Implemented the .1100CC file format. The ExchangePackage class is able to package and process any 1100CC data and processes as an .1100CC file.
* File Upload: Added support for Data URLs to FileGet.
* Mediator: Improved CLI API and Job locking/mutex procedures.
* Various fixes, modernisation, and overall streamlining.

## VERSION x.x
