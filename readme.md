# Recalculate - Scheduling Calendar

## What does it do?

Work in progress project. The Scheduling Calendar is a Redcap EM that tracks provider availability and allows scheduling against that availaiblity. It is a re-write of an internal-only EM devolped for the UW Center for Tobacco Research and Intervention that we hope to make available via the Vanderbilt Redcap repo in the near future.

### Missing Features

* Issue w/ Appt details only opening every 2nd click
* Make titles looks nicer and not overflow container

* Admin tools (Started)
  * Cleanup Remove all future data for withdrawn subject & Purge old availability
  * ICS Export calendar (with optional cron for backups) (with config for extra data)

* Sort out what the location json should look, update the API. Should location actually be on project settings?
* Setting to default the scheduled location to a static value or subject's assigned location
* Filter Location dropdowns and search bar

* Right side subject summary
* Add docs to config.json
* My Calendar Page - Visit names and Display names aren't going to pull correctly as the methods for getting the info assume PID

### Questions

* What issues might occur regarding dags and pulling subject names? Need to make sure we can't see those names
* Do we need a quick way to filter only availaiblity of the current provider?
* Consider Locations as providers? Obvious work around for this already.

### DET Integration

TODO

### Location Settings Structure

TODO

## Local Development & Build

See this [Docker Compose](https://github.com/123andy/redcap-docker-compose) for starting a local Redcap instnace.

If you don't have NPM already then install [NVM](https://github.com/nvm-sh/nvm) and use it to setup the latest npm version.

```sh
nvm install 21.7.0
nvm use 21.7.0
```

Install and build

```sh
cd webpack
npm install
npm run build
```
