# 🌍 Komunist

Utils/API for Italian comuni, provinces, and regions.

## Data

- data on comuni from https://github.com/matteocontrini/comuni-json/
- NUTS data from Istat (https://www.istat.it/en/)

## Endpoints

~~First of all, you need to pass a GET parameter called "access_token", with the value of "BN78FGH". This is just so if the API ever gets abused we can change it to disable access~~.

### GET /locations

Returns mangled locations, with an optional "type" attribute.

Example:

```
http://locations-api.test/api/v1/locations?access_token=BN78FGH // get all
http://locations-api.test/api/v1/locations?type=region&access_token=BN78FGH
http://locations-api.test/api/v1/locations?type=province&access_token=BN78FGH
http://locations-api.test/api/v1/locations?type=city&access_token=BN78FGH
```

You can also filter results by adding `region` and `province` as GET parameters, using the location ID.

For instance, if the ID for Tuscany is `ITI1`, you can get all its provinces with:

```
http://locations-api.test/api/v1/locations?type=province&region=ITI1&access_token=BN78FGH
```

Similarly, to get all cities in the province of Florence (ID `ITI14`):

```
http://locations-api.test/api/v1/locations?type=city&province=ITI14&access_token=BN78FGH
```

### GET /postcodes

Given a postcode, returns information about its city, including everything we know along with its province and region.

Example:

```
http://locations-api.test/api/v1/postcodes/50139?access_token=BN78FGH
```

## Run locally

You can quickly run a server without a database in the current directory with:

```bash
php -S localhost:8888
```

You can change "8888" into whatever port you want.
## Funding/donate
Donate to the project to become a backer/sponsor, and besides keeping the project alive your face/logo will appear here--along with a link to your website.

### Backers

Donate >= EUR 5/mo. to become a backer and support the project.

<span class="badge-patreon"><a href="https://www.patreon.com/bePatron?c=1739321" title="Donate to this project using Patreon"><img src="https://img.shields.io/badge/patreon-donate-yellow.svg" alt="Patreon donate button" /></a></span>

### Sponsors

Donate >= EUR 50/mo. to become a sponsor and make this project possible.

<span class="badge-patreon"><a href="https://www.patreon.com/bePatron?c=1739321" title="Donate to this project using Patreon"><img src="https://img.shields.io/badge/patreon-donate-yellow.svg" alt="Patreon donate button" /></a></span>

## License

This project is licensed under the MIT license. For licensing on data, see respective owners/projects.
