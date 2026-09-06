# Scheduling & Availability - REDCap External Module

## What does it do?

The Scheduling & Availability Calendar is a REDCap External Module that tracks provider availability and allows scheduling appointments against that availability using an interactive calendar. It was originally developed at the UW Center for Tobacco Research and Intervention (UW-CTRI) and refined over several iterations based on clinic coordinator and study team feedback.

The core workflow is straightforward: providers (or calendar admins) set the times they are available to see participants, and study staff schedule visits into those open slots. The module enforces visit lengths, prevents double-booking, catches location conflicts, and writes appointment details directly back to the participant's record.

## Features

* **Availability Management:** Providers or calendar admins can block out working hours, clinic locations, or recurring schedules. Bulk editing tools let you add or remove availability blocks across date ranges quickly.
* **Visit Types & Conflict Prevention:** Define study visits with specific durations, recommended visit windows, and branching logic. The calendar ensures appointments only fit into matching open slots and prevents overlapping bookings for the same provider or location.
* **Automated Data Writeback:** When an appointment is scheduled, rescheduled, or cancelled, the module automatically updates the participant's record with the date, time, and assigned provider.
* **`@SCHEDULING-CALENDAR` Action Tag:** Embeds an interactive booking widget directly onto data entry forms or public surveys. Participants can self-schedule, or staff can book visits without leaving the form. Supports popup or inline layouts, custom date ranges, and field piping (such as dynamically filtering slots based on a selected clinic site).
* **Faux Providers & Room Booking:** Projects can define non-user providers using simple `code, Display Name` pairs. These are treated just like regular providers across the calendar, making it easy to book exam rooms, scanner suites, or external clinicians who do not have REDCap accounts.
* **Calendar Views & Search:** Day, week, month, and agenda views with filtering by participant, provider, clinic location, or visit type. URLs update as you change views or dates, making it easy to bookmark or share specific calendar views.
* **Cross-Project Availability Groups:** System settings allow providers shared across multiple research protocols to maintain a single availability pool, preventing cross-study double-booking.
* **Data Entry Trigger (DET) & Webhooks:** When enabled, the module fires REDCap's Data Entry Trigger on calendar changes, sending an HTTP POST payload whenever appointments or availability blocks are created, updated, or cancelled. The payload includes record IDs, visit types, timestamps, and assigned providers, making it easy to trigger integrations.
* **ICS Calendar Export:** Download or subscribe to calendar feeds to sync appointments with Outlook, Google Calendar, or Apple Calendar.

## Things to know before installing

* This EM creates a dedicated database table (`em_scheduling_calendar`) to store availability blocks and appointments.
* There is a fair amount of project configuration required to map your visit types, writeback fields, and locations. Detailed setup guides and action tag examples are available in the module's documentation page (`docs.php`) linked on the External Modules page.
* System settings are available if you need to share availability across multiple REDCap projects.

## Local Development & Build

See this [Docker Compose](https://github.com/123andy/redcap-docker-compose) for starting a local Redcap instance.

If you don't have NPM already then check [the node guide](https://nodejs.org/en/download/package-manager) and use it to setup the latest npm version. After setup you can...

```sh
cd webpack
npm install
npm run build # Or 'run watch' for dev
npm run publish # Build and zip for sharing. Assumes Windows WSL is setup.
```
