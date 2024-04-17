# Recalculate - Scheduling Calendar

## What does it do?

Work in progress project. The Scheduling Calendar is a Redcap EM that tracks provider availability and allows scheduling against that availaiblity. It is a re-write of an internal-only EM devolped for the UW Center for Tobacco Research and Intervention that we hope to make available via the Vanderbilt Redcap repo in the near future.

### Missing Features

* Setting to default the scheduled location to a static value or subject's assigned location
* Add a notes option to the sched popup
* Remove all future data for withdrawn subject
* ICS Export calendar (with optional cron for backups) (with config for extra data)

### Larger projects

* Filter all dropdowns for Provider(done) , Visit(Sched pop), Subject(Sched pop), Location(both pops, bar)
* Sort out what the location json should look, update the API. Should location actually be on project settings?
* Right side subject summary
* Right click on "my cal" event to go to the correct project
* Clicking, in general, should take you somewhere for appts
* Add docs to config.json

### Questions

* Do we need a quick way to filter only availaiblity of the current provider?
* Consider Locations as providers? Obvious work around for this already.
* We can't have a "My Calendar" Page that pulls in from multiple projects, we could have clashes with DAGs (Is this still an issue?)
* The My Calendar page will be difficult to set up as we need to pull appts from all projects. My PHP assumes the current PID.

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
