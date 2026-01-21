# Changelog

Go to [1100CC Release](http://lab1100.com/1100cc/release) to view the pre-release/development version of the changelog.

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

* Front-end: Expanded class EmbedDocument into separate classes DocumentEmbedded and DocumentEmbedding to allow for dynamic loading of 1100CC documents in any website using the snippet DocumentEmbeddingListener.
* Data Exchange: Implemented the .1100CC file format. The ExchangePackage class is able to package and process any 1100CC data and processes as an .1100CC file.
* File Upload: Added support for Data URLs to FileGet.
* Mediator: Improved CLI API and Job locking/mutex procedures.
* Various fixes, modernisation, and overall streamlining.

## VERSION 10.6

* JSON/Object Traversal: Created new class TraverseJSON to traverse any JSON/Object(/Array) to collect and process specific values based on a given JSON Path. The syntax of a Path follows the baseline of JSON.
* Feed Module: Added a new Feed module to publish streams of information. A Feed Entry can have a short text, a media item, and the option to link to internal or external pages/URLs. A Feed can be ordered and has various layout/styling options using the 1100CC Tagging system.
* URI Translation: Extended the URI Translation service to transparently translate between dynamic or custom URIs and their native 1100CC URL counterparts.
* Sitemap: Implemented modular sitemap generation through the new GenerateSitemap class and related Job. Resulting URLs leverage URI Translation and are canonical-aware.
* Commands: Implemented the ability to dynamically merge multiple Commands (requests) into one request, with each retaining their own logic.
* Back-end: Added new class ParseXML2JSON to have full control over serialising XML to JSON.
* Various fixes, modernisation, and overall streamlining.

## VERSION 10.7

* JSON/Object Traversal: Extended the class TraverseJSON to be able to include custom text in a Path's collection using the special operator '+'. YAML is now a supported format to provide JSON-compatible syntax.
* File Archive: Improved the class FileArchive to dynamically write and read files and folders on-the-fly.
* Response HTML: Moved all interaction and transformation relating to (X)HTML to its own class HTMLDocument.
* .htaccess: Improved parsing of HOST to be able to control all its aspects through the alias map.
* Response: Added OUTPUT_CSV to be able to apply the dynamic processing of Response to CSV output as well. Improved location and header handling wherever requested within 1100CC.
* Various fixes, modernisation, and overall streamlining.

## VERSION 10.8

* Front-end: Integrated support for shadow DOM. The function ASSETS.createDocumentHost takes an element and can dynamically include relevant CSS rules from the live sheet. Essential element traversal functions (e.g. onStage, hasElement, getElementsSelector, getElementClosestSelector) have been updated to support traversing crossing shadow DOM and regular DOM.
* Slider Module: The front-end carousel viewer has been redeveloped with a light-weight and yet more powerful implementation. The old JavaScript library has been removed.
* Front-end: MapScroller has been extended with support for multi-layer backgrounds. Layers have controls for opacity and dynamic attribution. Additional URL template attributes have been added to support various tile services (e.g. WMS/WMTS).
* Mediator: Added fallback procedure (attachFallback). This complements the existing listener (attach) and locking (attachLock) procedures. Even when a process is not able to recover, a fallback method with optional parameters can be called.
* Back-end: Added the possibility to run setup or initialisation routines, that should not be Jobs, that can be called by administrators. Setup routines that can be declared/defined by any module.
* Various fixes, modernisation, and overall streamlining.

## VERSION 10.9

* API: Created base module class api_io to be used with any configured 1100CC API. The module provides extendable input/output functionalities and endpoint descriptors for e.g. OpenAPI.
* Front-end: Developed new loading animators for wait and download, themed 'line chasing dot'.
* Database SQL: Changed and added the necessary functions (class DBFunctions, functions with naming DateTime and NumericTime) to interchangeably generate, store, and compare datetime/timestamps with and without microsecond precision in and outside the database.
* Database SQL: Increased database agnosticity, 'hot-swappable' support for MySQL/MariaDB/PostgreSQL. Full control over primary, secondary (optionally asynchronous), and transactional connections.
* Various fixes, modernisation, and overall streamlining.

## VERSION x.x
