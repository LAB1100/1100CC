# 1100CC

<img src="https://lab1100.com/css/image.png" width="100" height="100" />

1100CC is an open-source web application framework developed by [LAB1100](https://lab1100.com). 1100CC provides a front-end web and communication interface with a back-end management system that manages 1100CC's core functionalities and content. 1100CC is able to host and serve multiple websites and web-applications from one installation.

## Architecture

1100CC is built along 4 axis:

| Axis | Description |
| --- | --- |
| CORE | The main directory that contains all default operations and logic. |
| SITE | SITE has to be renamed to the name of a specific website or web application. SITE instances can link, extend on, or overrule what is contained in CORE. |
| HOME | CORE and SITE open in the HOME directory that translates to a SITE's front-end. |
| CMS | CORE and SITE can enter the CMS directory that translates to a SITE's back-end. |

Each SITE contains
* Directories
* User Groups (optional)
* Users (optional)
* Pages
* Page Templates
* Modules

Directories belong to a path (e.g. `//domain/directoryA/directoryB`). Directories are either publicly accessible or have a User Group assigned to them (e.g. `//domain` (root) and `//domain/directoryA` are publicly accessible while `//domain/directoryA/directoryB` requires a login from a User Group. User Groups can be an ascendant from another User Group. A User Group has their own Users.

### Communication protocols

* HTTP(S): access a SITE by web browser.
* API: Directly access SITE Modules through an API with User accreditation (OAuth 2) or without (publicly accessible).
* CLI: Spawn 1100CC processes that target specific SITE Modules and Methods.
* Websocket: create and connect to SITE Webservices.

## Functionalities

A couple of powerful feature highlights:

* Input & Output:
  * The JavaScript COMMANDS class contains five methods (popupCommand, quickCommand, messageCommand, formCommand, and dataTable) and logic to manage all forms of interaction and communication between the system and the browser.
  * Perform an elaborate array of (post-)processing tasks on both HTML, text and JSON output.
  * Stream input and output of JSON to handle large datasets while maintaining a low memory footprint.
  * Resize and cache large images on the fly.
  * Connect and communicate with server-side programs or processes, e.g.: execute and evaluate simple tasks, integrate multi-threaded processes in C++, or run dynamic code in Python/JavaScript/Ruby through interactive shells.
* Logging: output and store messages to client and system.
  * Status: Stream notifications to the client during a running request.
  * Msg: Log informative messages to the system and send messages to the client when a request has finished. 
  * Error: Throw and log errors with debugging information to the system and report (redacted) errors to the client.
* Multilingual:
  * Use labels to provide text output based on the applicable (user/system) configured language.
  * In-text parse and substitution of language labels (e.g. `<h1>[L][msg_hello_1100cc]</h1>`) and language codes (e.g. `[[EN]] Hello 1100CC [[DE]] Hallo 1100CC [[ZH]] 你好，1100CC`).
  * Fallback to a default language when a label in a specific label is missing.
* Jobs:
  * Run scheduled tasks (e.g. cleanup procedures), or perform specific tasks (e.g. caching of advanced procedures), that have to be managed and consistent in a parallel-threaded web environment.
* Other:
  * Dynamic client-side JavaScript code allocation advancing modular and scalable interfaces.

The [1100CC Guides](https://lab1100.com/1100cc/guides) walk you through 1100CC's basic functionalities.

## Requirements

Apart from a [LAMP](https://en.wikipedia.org/wiki/LAMP_(software_bundle)) server configuration, 1100CC does not rely on any external libraries for its core functionalities on the server-side. The client-side is written in native JavaScript and secondarily allows jQuery to be used for convenience (e.g. elaborate DOM-selection).

### Server

1100CC requires and runs on Linux distributions. 

#### Database

1100CC supports MySQL (5.7+), MariaDB (10.2+), and PostgreSQL (10+). 1100CC defaults to using MariaDB. 1100CC applies its own database abstraction layer using native [database extensions](http://php.net/manual/en/refs.database.php) with beneficial support for asynchronous calls to the database.

#### Webserver

1100CC requires Apache 2.4.13+.

#### PHP

1100CC requires PHP 8.0+.

#### Mailserver

To make use of the 1100CC mailing features it is recommended to setup a mailserver (e.g. Postfix) or configure an existing mailserver (e.g. SSMTP).

### Client

Any modern HTML5 browser will do: Firefox, Chrome, Edge, Safari.

## Installation

Follow the steps in the [SETUP](SETUP.md) file.

Check and follow the steps in the [UPDATE](UPDATE.md) file when updating from an existing installation, and check the [CHANGELOG](CHANGELOG.md) to see what has changed.

### Extension

1100CC can be extended by creating new Modules and Methods and by creating dedicated server-side services. By default 1100CC provides an environment in `./PROGRAMS` to create services using C++.

## License

1100CC is published under GNU Affero General Public License v3.0 (AGPLv3). 

See [LICENSE](LICENSE.txt).
