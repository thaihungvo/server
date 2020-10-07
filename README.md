# Stacks Server (Public Beta)

**Stacks Server** is a PHP server for the [Stacks Task Manager](https://stacks.rocks) app.
More information can be found at the [official site](https://stacks.rocks).

This repository holds the source code for **Stacks Server** only.

## Server Requirements

Apache or Nginx with PHP version 7.2 or higher is required, with the following extensions installed:

- [intl](http://php.net/manual/en/intl.requirements.php)
- [libcurl](http://php.net/manual/en/curl.requirements.php) if you plan to use the HTTP\CURLRequest library
- [mbstring](http://php.net/manual/en/mbstring.installation.php)

Additionally, make sure that the following extensions are enabled in your PHP:

- json (enabled by default - don't turn it off)
- xml (enabled by default - don't turn it off)
- [mysqlnd](http://php.net/manual/en/mysqlnd.install.php)

> Make sure your server supports `HTTP/2` and [SSE](https://en.wikipedia.org/wiki/Server-sent_events) to enable automatic data polling from the **Stacks Client**

## Installation

### Download
Download the repository by going in the [Releases](https://github.com/stacks-task-manager/server/releases) section found on the right sidebar and download the `ZIP` file for the latest release 


### Configuration

|   |      Path      |  Line | Description |
|----------|:-------------:|------:|:------|
|**Database**| `app/Config/Database.php`|`32`|The default database connection.|
|**Base URL** | `app/Config/App.php`| `24`| If this is not set then Stacks Server will try guess the protocol, domain and path to your installation. However, you should always configure this explicitly and never rely on auto-guessing, especially in production environments. |
| **Application Timezone** | `app/Config/App.php` | `102` | The default timezone that will be used in your application to display dates with the date helper, and can be retrieved through `app_timezone()` |
| **JWT Authentication Tokens** | `app/Config/Constants.php` | `85` | [JSON Web Tokens](https://jwt.io/) are an open, industry standard RFC 7519 method for representing claims securely between two parties. [Example](https://www.grc.com/passwords.htm) on how to generate a good token. |

### Database
Import the latest `sql` file from the `db` folder either using [phpMyAdmin](https://www.phpmyadmin.net/) or any other `MySQL` client of your choosing.

The databse comes prepacked with 3 test users:

* `admin@stacks.server` - `12345`
* `l.skywalker@resistance.com` - `12345`
* `d.vader@theempire.com` - `12345`

### Permissions
Make sure all your files are using a permission of `644` for the files an `755` for the folders. The `writable` folder used for storing `cache`, `logs`, `session` data and `uploads` should be set to either `775` or `776`.


## Client configuration
**Stacks Client** can add and connect to multiple **Stack Servers** by enabling the `Beta Features` option from the `Preferences`. Follow these steps to enable the `Beta Features` and connect to your **Stacks Server**:

* open the preferences by pressing `CMD + ,` on Mac and `CTRL + ,` on Windows and Linux
* scroll down to the last preferences section called `BETA`
* toggle on the option called `Enable app Beta features`
* a new `User` icon should now be visible in the toolbar
* click the `User` button and select `Add a new account`
* a popup will appear asking for:
	* the **Stacks Server** URL (make sure you leave out the last `/` from the URL. E.g.: `https://mywebsite.com/my-stacks-server`)
	* username
	* password

## Documentation

Since Stacks Server is based on CodeiIgniter 4 please check the official [User Guide](https://codeigniter4.github.io/userguide/).

The current **in-progress** User Guide can be found [here](https://codeigniter4.github.io/CodeIgniter4/).
As with the rest of the framework, it is a work in progress, and will see changes over time to structure, explanations, etc.

You might also be interested in the [API documentation](https://codeigniter4.github.io/api/) for the framework components.

## Contributing

We **are** accepting contributions from the community!

We will try to manage the process somewhat, by adding a ["help wanted" label](https://github.com/stacks-task-manager/server/labels/help%20wanted) to those that we are
specifically interested in at any point in time. Join the discussion for those issues and let us know if you want to take the lead on one of them.

At this time, we are not looking for out-of-scope contributions, only those that would be considered part of our controlled evolution!

Please read the [_Contributing to Stacks Server_](https://github.com/stacks-task-manager/server/blob/master/contributing.md) section in the user guide.

## Testing
Stacks Server comes with a complete [Postman](https://github.com/stacks-task-manager/server/releases) requests collection ready to use.

Follow these steps to start using calling **Stacks Server** routes from **Postman**

- open **Postman**
- click on the `Import` button on the top left corner
- drag the 2 `JSON` files from the `postman` folder onto the opened modal window
	- `Stacks.postman_collection.json`
	- `Stacks.postman_environment.json`
- configure your server endpoing:
	1. open the **Manage Enviroments** window by either pressing `Option + CMS + E` on Mac, `CTRL + Alt + E` on Windows and Linux or by clicking the button top right near the `eye` iconed button
	2. click on `Stacks`
	3. change both the `initial value` and `current value` of the first variable called `server` to your servers URL
	4. click `Update` (bottom right) to save the configuration and then close the modal window
- start the testing by running the `Login` request first