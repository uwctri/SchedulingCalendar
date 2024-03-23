Work in progress project. The Scheduling Calendar is a Redcap EM that tracks provider availability and allows scheduling against that availaiblity. It is a re-write of an internal-only EM that we hope to make available via the repo.

## Issues

* We can't have a "My Calendar" Page that pulls in from multiple projects, we could have clashes with DAGs

## Don't forget Features

* Setting to default the scheduled location to a static value or subject's assigned location
* Popup for scheduling an event
* Filter the scheduling drop down for Event in a way similar to the side bar
* Right side subject summary
* Need a good fast way to add availability
* Remove all future data for withdrawn subject
* Export calendar (via an API too)
* Whole settings page

## SOT settings

* Add a new study
* Location tree
* ICS export config
* Calendar Admin
* Unschedulable Users

## Availability Format

"2022-07-05 07:45":{"study":"code","loc":"CTRIcode","length":"120"}

## Running locally

If you don't have NPM install nodist and use it to setup the latest npm version
`
nvm install 21.7.0
nvm use 21.7.0
`

Setup the build
`
cd webpack
npm install
npm run build
`
