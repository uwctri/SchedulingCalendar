# Recalculate - Scheduling Calendar

## What does it do?

Work in progress project. The Scheduling Calendar is a Redcap EM that tracks provider availability and allows scheduling against that availaiblity. It is a re-write of an internal-only EM devolped for the UW Center for Tobacco Research and Intervention that we hope to make available via the Vanderbilt Redcap repo in the near future.

### Missing Features

* Send rejections back from PHP and show user
* Right side subject summary
* Setup all event logic (Branching, Additional Time, Linked Event)
* My Calendar Page - Visit names and Display names aren't going to pull correctly as the methods for getting the info assume PID
* Write the docs page

### Questions

* What issues might occur regarding dags and pulling subject names? Need to make sure we can't see those names
* Consider Locations as providers? Obvious work around for this already.
* Do we need an "Assigned Clinc" for anything? Maybe just a generic "show this data in Subject Summary" option?

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
