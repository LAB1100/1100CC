# Changelog

To get the version for your current 1100CC installation, view `/APP/CORE/CMS/info/version.txt`

## VERSION 10.1

Initial release.

## VERSION 10.2

* Users: Improved status/error logging of users and their class.
* Language: Removed archaic language flags when working with language settings.
* SQL: Fixed DELETE query not working in PostgreSQL in cms_forms.php.
* Front-end Functions: Changed the JavaScript spread operator for ElementObjectByParameters in core_shed.js to better support a bit older browser versions.
* Parsing: Improved URL parsing in HTML and plain text in FormatBBCode.php.
* Various fixes, modernisation, and overall streamlining.

## VERSION 10.3

* Labels: Improved Labels parsing & printing. Changing and printing variables can now be done in a single call to getLabel.
* Jobs: Jobs are now tracked/run every second instead of by minute.
* SQL: Fixed uncaught MySQL Exceptions in DBMysql.php by turning them in a DBTrouble Exception, where they belong.
* Front-end Functions: AutoCompleter in multi-mode can now also be sortable in core_shed.js.
* Back-end Functions: Added/streamlined various array functions arrParseRecursive/arrFilterRecursive/arrValuesRecursive/arrHasValuesRecursive/arrHasKeysRecursive/arrMergeValues in core_operations.php.
* Various fixes, modernisation, and overall streamlining.

## VERSION x.x
