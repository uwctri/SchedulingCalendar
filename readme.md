Work in progress project. The Scheduling Calendar is a Redcap EM that tracks provider availability and allows scheduling against that availaiblity. It is a re-write of an internal-only EM that we hope to make available via the repo.

## Missing Features

* PHP isn't parsing BOOLS correctly. Verify availaiblityCodes is correct after fix.
* Do we need a quick way to filter only availaiblity of the current provider?
* Sort out what the location json should look, update the API. Should location actually be on project settings?
* Filter all dropdowns (searchbar too) for Provider, Visit, Subject, Location
* Full Scheduling workflow
* Setting to default the scheduled location to a static value or subject's assigned location
* Popup for scheduling an event
* Right side subject summary
* Remove all future data for withdrawn subject
* ICS Export calendar (with optional cron for backups) (with config for extra data)
* Right click on "my cal" event to go to the correct project

* Consider Locations as providers? Obvious work around for this already.
* We can't have a "My Calendar" Page that pulls in from multiple projects, we could have clashes with DAGs (Is this still an issue?)
* The My Calendar page will be difficult to set up as we need to pull appts from all projects. My PHP assumes the current PID.

## Notes to Users

* Explain location strucutre
* Docs for DET post

## Running locally

See this [Docker Compose](https://github.com/123andy/redcap-docker-compose) for starting a local Redcap instnace.

If you don't have NPM install NVM and use it to setup the latest npm version.

```
nvm install 21.7.0
nvm use 21.7.0
```

Setup the build

```
cd webpack
npm install
npm run build
```
