# Scheduling Calendar - Redcap External Module

## What does it do?

The Scheduling Calendar is a Redcap EM that tracks provider availability and allows scheduling against that availaiblity. It is a re-write of an internal-only EM devolped for the UW Center for Tobacco Research and Intervention that went through various iterations and, based on feedback, we've arrived here. Basic workflow consists of provider selecting times of the day that they are availabile via a calendar, other users can then later schedule against that time for some number of configured visits. The EM prevents obvious issues like double scheduling, location conflicts, enforces visit time, and is easily searchable based on subject, provider, visit, or location of the visit. 

## Things to know before installing

* This EM will create a new table in your database used to track provider availability and scheduled events for the module. The table can be easily removed by an adminstrator if you decide you don't want to use the EM.
* There is a considerable amout of configuration and reading required to use this. Feel free to email the author or open a github issue if you have questions. The documentation link can be found on the External Modules page when the EM is enabled on a project.
* System settings exist that allow for sharing availability across projects.

## Local Development & Build

See this [Docker Compose](https://github.com/123andy/redcap-docker-compose) for starting a local Redcap instnace.

If you don't have NPM already then check [the node guide](https://nodejs.org/en/download/package-manager) and use it to setup the latest npm version. After setup you can...

```sh
cd webpack
npm install
npm run build # Or 'run watch' for dev
npm run publish # Build and zip for sharing. Assumes Windows WSL is setup.
```
