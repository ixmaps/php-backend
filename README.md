## API Backend for IXmaps

#### This is used for, among other things:

- to act as an API for the geolocation of submitted Paris Traceroutes from CIRA
- to ingest traceroutes from the IXmapsClient
- to interface with the IXmaps website
  For more details, see [github.com/ixmaps/ecosystem](https://github.com/ixmaps/ecosystem)

#### Local development

1. `cp application/config.sample.php application/config.php`
2. update eg DB values in there as needed
3. `php -S 127.0.0.1:8001` to run a local server
4. modify the config.json _in the website2017 directory_ to have the url_base value point at http://localhost:8001 (no trailing slash!)

## License

Copyright (C) 2024 IXmaps.
This code and repository [github.com/ixmaps/php-backend](https://github.com/ixmaps/php-backend) are licensed under a GNU AGPL v3.0 license. These files are free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, version 3 of the License.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details [gnu.org/licenses](https://gnu.org/licenses/agpl.html).
