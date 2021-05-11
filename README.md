# WordPress - WP Plugin Tuxedo API Importer

https://api.tuxedoticket.ca/documentation

## Generate a Beamer auth token

```bash

curl -X POST "https://api.tuxedoticket.ca/v1/authentication" -H "accept: application/json" -H "Content-Type: application/json" -d "{\"accountName\":\"tuxedo-denisepelletier"\",\"password\":\"XXX\",\"username\":\"tuxedo-denisepelletier-readonly\"}""

```