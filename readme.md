# Recalculate - Scheduling Calendar

## What does it do?

Work in progress project. The Scheduling Calendar is a Redcap EM that tracks provider availability and allows scheduling against that availaiblity. It is a re-write of an internal-only EM devolped for the UW Center for Tobacco Research and Intervention that we hope to make available via the Vanderbilt Redcap repo in the near future.

## Things to know before installing

* This EM will create a new table in your database used to track provider availability and scheduled events for the module. The table can be easily removed by an adminstrator if you decide you don't want to use the EM.
* There is a considerable amout of configuration and reading required to use this. Feel free to email the author or open a github issue if you have questions.
* System settings exist that allow for sharing availability across projects.

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

Note: We assume Windows with WSL in the publish script, drop the "WSL -e" if on linux. You may need to install 'zip' via your package manager.

```sh
npm run publish
```
