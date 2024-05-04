# Recalculate - Scheduling Calendar

## What does it do?

Work in progress project. The Scheduling Calendar is a Redcap EM that tracks provider availability and allows scheduling against that availaiblity. It is a re-write of an internal-only EM devolped for the UW Center for Tobacco Research and Intervention that we hope to make available via the Vanderbilt Redcap repo in the near future.

### Missing Features

* Finish basic subject summary
* Allow arbitary data to be displayed for the summary
* Setup all event logic for summary (Branching, Linked Event)
* When an event is scheduled write the time back to event
* Write the docs page
* What issues might occur regarding dags and pulling subject names? Need to make sure we can't see those names

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

Publish for distribution

Note: We assume Windows with WSL in the publish script, drop the "WSL -e" if on linux

```sh
npm run publish
```
