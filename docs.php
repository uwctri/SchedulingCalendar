<?php
$HtmlPage = new HtmlPage();
$HtmlPage->addStylesheet("home.css", 'screen,print');
$HtmlPage->PrintHeader();
include APP_PATH_VIEWS . 'HomeTabs.php';
?>
<style>
    /* Global Container & Base Typography */
    #pagecontainer {
        max-width: 1440px;
    }

    body,
    #pagecontainer,
    #pagecontainer p,
    #pagecontainer td,
    #pagecontainer th,
    #pagecontainer li,
    #pagecontainer span {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }

    .projhdr h3 {
        color: #1a252f;
        font-weight: 700;
        letter-spacing: -0.02em;
        font-size: 24px !important;
    }

    .projhdr h3 i {
        color: #0d6efd;
    }

    /* Top Warning Banner */
    .alert-warning-docs {
        background-color: #fff8e6;
        border: 1px solid #ffd56b;
        border-left: 5px solid #f59f00;
        border-radius: 0.5rem;
        color: #7c5200;
        font-size: 15px !important;
        line-height: 1.6 !important;
    }

    .alert-warning-docs span,
    .alert-warning-docs div {
        font-size: 15px !important;
    }

    .alert-warning-docs a {
        color: #5c3b00;
        font-weight: 600;
        text-decoration: underline;
    }

    .alert-warning-docs a:hover {
        color: #000000;
    }

    /* Cards & Content Sections */
    .docs-card {
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
        scroll-margin-top: 5rem;
        margin-bottom: 2.25rem;
        background-color: #ffffff;
        overflow: hidden;
    }

    .docs-card-header {
        background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
        color: #ffffff;
        padding: 12px 18px !important;
        font-size: 18px !important;
        font-weight: 600 !important;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .docs-card-header code {
        background-color: rgba(255, 255, 255, 0.2) !important;
        color: #ffffff !important;
        font-size: 16px !important;
    }

    .docs-card-body {
        padding: 22px !important;
        font-size: 15px !important;
        color: #334155 !important;
        line-height: 1.7 !important;
    }

    .docs-card-body p,
    .docs-card-body li {
        font-size: 15px !important;
        line-height: 1.7 !important;
        color: #334155 !important;
    }

    .docs-card-body p {
        margin-bottom: 1.1rem;
    }

    .docs-card-body p:last-child {
        margin-bottom: 0;
    }

    .docs-card-body a {
        color: #0d6efd;
        text-decoration: underline;
        font-weight: 500;
    }

    .docs-card-body a:hover {
        color: #0a58ca;
    }

    /* Section Subheadings */
    .section-title {
        color: #0f172a;
        font-weight: 700;
        font-size: 17px !important;
        margin-top: 28px;
        margin-bottom: 12px;
        padding-bottom: 6px;
        border-bottom: 2px solid #e2e8f0;
        display: flex;
        align-items: center;
    }

    .section-title i {
        color: #0d6efd;
        margin-right: 0.5rem;
    }

    .subsection-title {
        color: #1e293b;
        font-weight: 700;
        font-size: 16px !important;
        margin-top: 20px;
        margin-bottom: 8px;
    }

    /* Feature Callouts */
    .feature-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-left: 4px solid #0d6efd;
        border-radius: 0.375rem;
        padding: 14px 18px !important;
        margin-bottom: 16px;
        font-size: 15px !important;
        line-height: 1.6 !important;
        color: #334155 !important;
    }

    .feature-card p,
    .feature-card div,
    .feature-card li,
    .feature-card ol,
    .feature-card ul {
        font-size: 15px !important;
        line-height: 1.6 !important;
    }

    .feature-card h6 {
        font-size: 16px !important;
        font-weight: 700 !important;
    }

    .feature-card.warning {
        border-left-color: #f59f00;
        background: #fffbeb;
    }

    .feature-card.success {
        border-left-color: #10b981;
        background: #f0fdf4;
    }

    .feature-card.info {
        border-left-color: #0284c7;
        background: #f0f9ff;
    }

    /* Tables */
    .docs-table {
        margin: 18px 0;
        border: 1px solid #e2e8f0;
        border-radius: 0.375rem;
        overflow: hidden;
    }

    .docs-table thead th {
        background-color: #1e293b;
        color: #ffffff;
        font-weight: 600;
        font-size: 14.5px !important;
        padding: 10px 14px !important;
        border-color: #334155;
    }

    .docs-table tbody td {
        padding: 10px 14px !important;
        vertical-align: top;
        font-size: 14.5px !important;
        line-height: 1.6 !important;
        border-color: #f1f5f9;
        color: #334155;
    }

    .docs-table tbody td p,
    .docs-table tbody td ul,
    .docs-table tbody td ol,
    .docs-table tbody td li {
        font-size: 14.5px !important;
        line-height: 1.6 !important;
    }

    .docs-table tbody td em {
        font-size: 14px !important;
        font-style: italic;
    }

    .docs-table tbody tr:nth-of-type(even) {
        background-color: #f8fafc;
    }

    .docs-table tbody tr:hover {
        background-color: #f1f5f9;
    }

    /* Code & Syntax */
    code {
        background-color: #f1f5f9;
        color: #d63384;
        padding: 3px 6px !important;
        border-radius: 0.25rem;
        font-size: 14px !important;
        font-weight: 600;
        font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    }

    pre {
        background-color: #0f172a;
        color: #f8fafc;
        border-radius: 0.5rem;
        padding: 16px 18px !important;
        margin: 18px 0;
        overflow-x: auto;
        font-size: 14px !important;
        line-height: 1.6 !important;
        border: 1px solid #1e293b;
        position: relative;
    }

    pre code {
        background: none !important;
        color: #e2e8f0 !important;
        padding: 0 !important;
        font-weight: 400 !important;
        font-family: inherit !important;
        font-size: 14px !important;
    }

    .code-comment {
        color: #94a3b8;
    }

    .code-keyword {
        color: #38bdf8;
    }

    .code-string {
        color: #a7f3d0;
    }

    /* Badges */
    .docs-card .badge,
    .badge {
        font-size: 13px !important;
        font-weight: 600 !important;
        padding: 5px 9px !important;
        border-radius: 0.25rem;
        letter-spacing: 0.02em;
    }

    /* Sticky Table of Contents (Right Navigation) */
    .toc-card {
        border: 1px solid #cbd5e1;
        border-radius: 0.5rem;
        background-color: #ffffff;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    }

    .toc-card-header {
        background-color: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 10px 14px !important;
        font-size: 14px !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #475569;
    }

    #toc {
        max-height: calc(100vh - 7rem);
        overflow-y: auto;
        padding: 8px 10px;
    }

    #toc a {
        display: flex;
        align-items: flex-start;
        padding: 7px 10px !important;
        color: #475569;
        text-decoration: none;
        font-size: 14px !important;
        font-weight: 500 !important;
        border-radius: 0.25rem;
        line-height: 1.4 !important;
        white-space: normal;
        word-break: normal;
        overflow-wrap: break-word;
        transition: all 0.15s ease-in-out;
    }

    #toc a i {
        margin-top: 3px;
        margin-right: 8px;
        flex-shrink: 0;
        font-size: 12px !important;
    }

    #toc a:hover {
        background-color: #f1f5f9;
        color: #0d6efd;
        padding-left: 14px !important;
    }

    #toc a.active {
        background-color: #e0f2fe;
        color: #0284c7;
        font-weight: 700 !important;
        border-left: 3px solid #0284c7;
    }

    /* Responsive adjustments */
    @media (max-width: 991px) {
        .sticky-toc-wrapper {
            position: static !important;
            margin-bottom: 2rem;
        }

        #toc {
            max-height: none;
        }
    }
</style>

<div class="projhdr mb-3">
    <h3><i class="fas fa-calendar-alt mr-2"></i> Scheduling & Availability Documentation</h3>
</div>

<div class="bg-white container-fluid m-0 p-0">
    <div class="row">
        <!-- Main Content Column -->
        <div class="col-lg-9 col-md-8 pr-lg-4">

            <!-- Preserved Top Warning Banner -->
            <div class="alert alert-warning-docs my-3 p-3">
                <div class="d-flex align-items-center">
                    <div class="mr-3 text-warning">
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                    </div>
                    <div>
                        <span class="font-weight-bold">This docs page is still a major work in progress. A lot of information is missing, poorly formatted, or just too vague. If you have questions you can reach out to the author at <a href="mailto:adam.nunez@ctri.wisc.edu">adam.nunez@ctri.wisc.edu</a>.</span>
                    </div>
                </div>
            </div>

            <!-- 1. Purpose & Overview -->
            <div id="purpose" class="docs-card" data-toc-title="Purpose & Overview">
                <div class="docs-card-header d-flex align-items-center">
                    <i class="fas fa-bullseye mr-2"></i> Purpose & Overview
                </div>
                <div class="docs-card-body">
                    <p>
                        The <strong>Scheduling & Availability</strong> External Module brings comprehensive appointment scheduling, provider availability management, and participant self-scheduling natively into REDCap. It allows study personnel and clinic providers to manage working hours across projects, while enabling study coordinators or research participants to book visits directly against real-time provider availability without leaving REDCap.
                    </p>
                    <p>
                        The primary goal is to eliminate third-party calendar software, separate scheduling platforms, and manual coordination friction. While REDCap includes a native Calendar tool, this module is engineered specifically for complex clinical trials and research environments requiring:
                    </p>
                    <div class="row mt-3 mb-2">
                        <div class="col-md-6 mb-3">
                            <div class="feature-card h-100">
                                <h6 class="font-weight-bold text-dark mb-1"><i class="fas fa-users-cog text-primary mr-1"></i> Multi-Provider Availability</h6>
                                Providers configure working hours, clinic locations, and recurrence rules with cross-study conflict detection.
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="feature-card h-100">
                                <h6 class="font-weight-bold text-dark mb-1"><i class="fas fa-tag text-primary mr-1"></i> Action Tag Self-Scheduling</h6>
                                Embed interactive scheduling widgets directly onto data entry forms and public surveys via <code>@SCHEDULING-CALENDAR</code>.
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="feature-card h-100">
                                <h6 class="font-weight-bold text-dark mb-1"><i class="fas fa-database text-primary mr-1"></i> Automated Instrument Writeback</h6>
                                Automatically populates appointment date, time, and provider username back to linked REDCap event instruments.
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="feature-card h-100">
                                <h6 class="font-weight-bold text-dark mb-1"><i class="fas fa-globe text-primary mr-1"></i> Timezone Awareness</h6>
                                Seamless timezone translation between local server storage, explicit study zones, and participant device timezones.
                            </div>
                        </div>
                    </div>
                    <p class="mt-2">
                        If you have questions, need assistance with configuration, or wish to propose new features, please contact the development team via <a href="mailto:adam.nunez@ctri.wisc.edu">email</a> or file a <a href="https://github.com/uwctri/SchedulingCalendar/issues/new" target="_blank" rel="noopener">GitHub issue</a>.
                    </p>
                </div>
            </div>

            <!-- 2. Workflow Views -->
            <div id="workflow" class="docs-card" data-toc-title="Workflow Views">
                <div class="docs-card-header d-flex align-items-center">
                    <i class="fas fa-compass mr-2"></i> Workflow Views
                </div>
                <div class="docs-card-body">
                    <p>
                        The module interface provides three dedicated operational views accessible via the navigation buttons in the top-right toolbar of the calendar page:
                    </p>

                    <div class="table-responsive docs-table mb-4">
                        <table class="table table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 20%;">Workflow View</th>
                                    <th style="width: 22%;">Target Role</th>
                                    <th style="width: 18%;">URL Parameter</th>
                                    <th>Primary Operational Capabilities</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Edit Availability</strong></td>
                                    <td>Providers & Calendar Admins</td>
                                    <td><code>type=edit</code></td>
                                    <td>Define recurring weekly working hours, set clinic locations, schedule vacations/closures, and manage provider rosters.</td>
                                </tr>
                                <tr>
                                    <td><strong>Schedule Appointments</strong></td>
                                    <td>Study Coordinators & Staff</td>
                                    <td><code>type=schedule</code></td>
                                    <td>Search participants, select visit types, check real-time availability slots, book visits, and trigger automated writeback.</td>
                                </tr>
                                <tr>
                                    <td><strong>My Calendar</strong></td>
                                    <td>Logged-In Providers</td>
                                    <td><code>type=my</code></td>
                                    <td>Consolidated personal agenda of upcoming appointments across all linked projects with direct links to participant records.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="section-title">
                        <i class="fas fa-user-clock"></i> 1. Edit Availability (<code>type=edit</code>)
                    </div>
                    <p>
                        The <strong>Edit Availability</strong> interface allows clinic staff and investigators to specify exactly when and where appointments may take place:
                    </p>
                    <ul class="pl-4 mb-3">
                        <li><strong>Manual Slot Placement:</strong> Click and drag across any calendar day or time range to create a discrete block of working hours. Each block can be assigned a specific clinic site or left open for general availability.</li>
                        <li><strong>Bulk Recurring Schedule Generator:</strong> Use the <strong>Bulk Edit</strong> modal to create repeating schedules across custom date ranges (e.g., Every Monday and Wednesday from 8:00 AM to 12:00 PM through December 31st).</li>
                        <li><strong>Exceptions & Vacations:</strong> Individual blocks can be adjusted, resized, or deleted at any time to account for holidays, illness, or clinic closures without impacting past appointments.</li>
                        <li><strong>Calendar Admin Oversight:</strong> Users designated as Calendar Admins can toggle between all study providers using the provider dropdown to configure or adjust schedules on their behalf.</li>
                    </ul>

                    <div class="section-title">
                        <i class="fas fa-calendar-check"></i> 2. Schedule Appointments (<code>type=schedule</code>)
                    </div>
                    <p>
                        The <strong>Schedule Appointments</strong> interface is the primary operational workspace for study coordinators and clinical research coordinators:
                    </p>
                    <ul class="pl-4 mb-3">
                        <li><strong>Participant Selection:</strong> Search and select an active study participant by record ID or display name. The sidebar automatically evaluates branching logic and displays eligible visit types.</li>
                        <li><strong>Protocol Visit Window Guides:</strong> If visit window fields are mapped in project settings, the calendar highlights the target scheduling window to help coordinators book protocol-compliant visit dates.</li>
                        <li><strong>Double-Booking Prevention:</strong> The scheduling engine dynamically checks provider availability blocks against existing bookings, ensuring no provider or exam room is double-booked.</li>
                        <li><strong>Automated Record Writeback:</strong> When an appointment is scheduled, the module writes the appointment date, start time, end time, and provider username directly to the linked REDCap instrument.</li>
                        <li><strong>Rescheduling & Cancellation:</strong> Existing appointments can be dragged to a new available slot or cancelled directly from the calendar modal, updating all linked instrument fields atomically.</li>
                    </ul>

                    <div class="section-title">
                        <i class="fas fa-calendar-alt"></i> 3. My Calendar (<code>type=my</code>)
                    </div>
                    <p>
                        The <strong>My Calendar</strong> interface gives providers an aggregated overview of their personal commitments:
                    </p>
                    <ul class="pl-4 mb-1">
                        <li><strong>Cross-Study Aggregation:</strong> Displays all upcoming appointments for the logged-in provider across all REDCap projects where the module is enabled.</li>
                        <li><strong>Direct Record Links:</strong> Clicking an appointment opens a summary card with participant details, visit type, clinic location, and a direct link to the participant's REDCap record.</li>
                        <li><strong>Export & Sync:</strong> Providers can subscribe to their personal calendar feed using the tokenized webcal subscription URL in Apple Calendar, Google Calendar, or Microsoft Outlook.</li>
                    </ul>
                </div>
            </div>

            <!-- 3. Action Tag Integration -->
            <div id="actiontag" class="docs-card" data-toc-title="Action Tag Integration">
                <div class="docs-card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-magic mr-2"></i> Action Tag Integration</span>
                    <code>@SCHEDULING-CALENDAR</code>
                </div>
                <div class="docs-card-body">
                    <p>
                        The <code>@SCHEDULING-CALENDAR</code> action tag embeds an interactive scheduling widget directly into REDCap data entry forms and public survey instruments. It connects form fields to provider availability in real time, enabling staff scheduling or participant self-scheduling without leaving the survey.
                    </p>

                    <div class="section-title">
                        <i class="fas fa-code"></i> Action Tag Syntax
                    </div>
                    <p>
                        Parameters are specified as labeled key-value pairs inside parentheses (e.g., <code>parameter="value"</code>, <code>parameter=true</code>, or piped variables <code>parameter="[field_name]"</code>):
                    </p>

                    <pre><code><span class="code-comment"># 1. Standard popup modal on a public survey (1-hour buffer from now):</span>
@SCHEDULING-CALENDAR(visit="baseline_visit", start="now +1h", end="+30d", mode="popup")

<span class="code-comment"># 2. Survey self-scheduling with smart variable event resolution and participant reschedule/cancel enabled:</span>
@SCHEDULING-CALENDAR(visit="[event-name]", start="+1d", end="+14d", allow_reschedule=true, allow_cancel=true, btn_text="Choose Appointment Time")

<span class="code-comment"># 3. Embedded inline accordion with dynamic field piping and staff provider hiding:</span>
@SCHEDULING-CALENDAR(visit="visit_1_arm_1", start="[screening_date]", end="+45d", provider="[assigned_staff]", location="[clinic_site]", mode="inline", hide_provider=true)</code></pre>

                    <div class="section-title">
                        <i class="fas fa-table"></i> Complete Parameter Reference
                    </div>
                    <div class="table-responsive docs-table">
                        <table class="table table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 18%;">Parameter</th>
                                    <th style="width: 15%;">Default</th>
                                    <th>Description & Supported Values</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>visit</code></td>
                                    <td><span class="badge bg-danger text-white">Required</span></td>
                                    <td>
                                        The visit identifier. Supports three formats:
                                        <ul class="mb-0 mt-1 pl-3">
                                            <li><strong>Internal Coded Value:</strong> Coded visit value from project settings (e.g., <code>visit="v1"</code>).</li>
                                            <li><strong>Smart Variable:</strong> Evaluates dynamically to the current event (e.g., <code>visit="[event-name]"</code>).</li>
                                            <li><strong>Literal Event Name:</strong> Unique REDCap event name linked to the visit (e.g., <code>visit="visit_1_arm_1"</code>).</li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>start</code></td>
                                    <td><code>"now"</code></td>
                                    <td>
                                        Start boundary of the scheduling window. Excludes past times. Accepts:
                                        <ul class="mb-0 mt-1 pl-3">
                                            <li>Relative time buffers: <code>"now"</code>, <code>"now +1h"</code>, <code>"now +30m"</code>, <code>"+2h"</code>.</li>
                                            <li>Relative date offsets: <code>"+1d"</code>, <code>"+2w"</code>, <code>"today"</code>.</li>
                                            <li>Fixed ISO dates: <code>"2026-10-01"</code>.</li>
                                            <li>Piped date fields: <code>start="[baseline_date]"</code>.</li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>end</code></td>
                                    <td><code>"+30d"</code></td>
                                    <td>
                                        End boundary of the scheduling window. Accepts relative offsets (<code>"+14d"</code>, <code>"+2m"</code>), fixed ISO dates (<code>"2026-11-15"</code>), or piped fields (<code>end="[window_end]"</code>).
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>duration</code></td>
                                    <td><em>Visit default</em></td>
                                    <td>Override visit duration in minutes (e.g., <code>duration=45</code>). If omitted, uses the visit length configured in project settings.</td>
                                </tr>
                                <tr>
                                    <td><code>provider</code></td>
                                    <td><em>All providers</em></td>
                                    <td>Filter slots to specific provider username(s). Supports a single user (<code>provider="dr_smith"</code>), comma-separated users (<code>provider="user1,user2"</code>), or piped fields (<code>provider="[assigned_doctor]"</code>).</td>
                                </tr>
                                <tr>
                                    <td><code>location</code></td>
                                    <td><em>All locations</em></td>
                                    <td>Filter slots to specific clinic location codes (e.g., <code>location="clinic_east"</code>, <code>location="east,west"</code>, or <code>location="[site_field]"</code>).</td>
                                </tr>
                                <tr>
                                    <td><code>mode</code></td>
                                    <td><code>"popup"</code></td>
                                    <td>
                                        Display presentation mode:
                                        <ul class="mb-0 mt-1 pl-3">
                                            <li><code>"popup"</code>: Renders a trigger button opening a full 2-column Slot Explorer modal with date navigation, location/provider filters, and Jump-to-Date picker.</li>
                                            <li><code>"inline"</code>: Embeds a single-expand accordion directly inside the form/survey page layout.</li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>allow_reschedule</code></td>
                                    <td><code>false</code></td>
                                    <td>When set to <code>true</code>, displays a "Reschedule" button on booked appointment cards allowing participants or staff to change their appointment time.</td>
                                </tr>
                                <tr>
                                    <td><code>allow_cancel</code></td>
                                    <td><code>false</code></td>
                                    <td>When set to <code>true</code>, displays a "Cancel" button on booked appointment cards with confirmation modal to cancel the scheduled visit.</td>
                                </tr>
                                <tr>
                                    <td><code>hide_provider</code></td>
                                    <td><code>false</code></td>
                                    <td>When set to <code>true</code>, suppresses provider names on slot cards, appointment cards, and toolbar filters (ideal for anonymous or team-based participant surveys).</td>
                                </tr>
                                <tr>
                                    <td><code>save_mode</code></td>
                                    <td><code>"on_submit"</code></td>
                                    <td>
                                        Booking transaction behavior:
                                        <ul class="mb-0 mt-1 pl-3">
                                            <li><code>"on_submit"</code>: Attaches booking data to the form and commits atomically upon form/survey submission.</li>
                                            <li><code>"immediate"</code>: Commits the booking immediately via AJAX as soon as a slot is selected.</li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>btn_text</code></td>
                                    <td><code>"Schedule Appointment"</code></td>
                                    <td>Custom text for the trigger button when using <code>mode="popup"</code> (e.g., <code>btn_text="Select Visit Time"</code>).</td>
                                </tr>
                                <tr>
                                    <td><code>timezone</code></td>
                                    <td><code>"browser"</code></td>
                                    <td>
                                        Target display timezone for appointment times. Defaults to <code>"browser"</code>, which automatically detects the participant's device timezone via <code>Intl.DateTimeFormat</code>. Accepts explicit IANA identifiers (<code>"America/Chicago"</code>) or <code>"local"</code> for server time.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="section-title">
                        <i class="fas fa-layer-group"></i> Practical Implementation Recipes
                    </div>
                    
                    <div class="feature-card mb-3">
                        <h6 class="font-weight-bold text-dark mb-1"><i class="fas fa-user-check text-primary mr-1"></i> Recipe A: Public Screening Self-Scheduling</h6>
                        <p class="mb-2">Allow new study candidates to book their screening visit directly at the end of a public eligibility survey:</p>
                        <pre class="mb-0"><code>@SCHEDULING-CALENDAR(visit="screening", start="now +2h", end="+14d", mode="popup", btn_text="Book Screening Visit")</code></pre>
                    </div>

                    <div class="feature-card mb-3">
                        <h6 class="font-weight-bold text-dark mb-1"><i class="fas fa-calendar-alt text-success mr-1"></i> Recipe B: Longitudinal Protocol Visit with Reschedule</h6>
                        <p class="mb-2">Embed self-scheduling on a 6-month follow-up survey invitation with dynamic window dates and self-service reschedule allowed:</p>
                        <pre class="mb-0"><code>@SCHEDULING-CALENDAR(visit="[event-name]", start="[window_start_date]", end="[window_end_date]", allow_reschedule=true, allow_cancel=true, btn_text="Select or Change Appointment")</code></pre>
                    </div>

                    <div class="feature-card mb-3">
                        <h6 class="font-weight-bold text-dark mb-1"><i class="fas fa-stethoscope text-info mr-1"></i> Recipe C: Staff Data Entry Form with Dynamic Piped Filters</h6>
                        <p class="mb-2">Coordinator selects an assigned provider and clinic site from dropdowns on the form; the action tag automatically re-queries slots for that provider and site:</p>
                        <pre class="mb-0"><code>@SCHEDULING-CALENDAR(visit="v2_exam", start="today", end="+60d", provider="[assigned_doc]", location="[clinic_site]", mode="inline")</code></pre>
                    </div>

                    <div class="section-title">
                        <i class="fas fa-sync-alt"></i> Dynamic Field Piping & Reactivity
                    </div>
                    <p>
                        When using piped variables such as <code>provider="[assigned_doctor]"</code> or <code>location="[clinic_site]"</code>, the scheduler widget automatically attaches DOM change listeners to the referenced input elements. When a user changes the clinic site dropdown or assigned provider field, the calendar widget re-queries available slots and refreshes the display instantly without requiring a page reload.
                    </p>

                    <div class="section-title">
                        <i class="fas fa-shield-alt"></i> Save Modes: Transactional vs Immediate
                    </div>
                    <p>
                        The module supports two booking execution strategies depending on survey design:
                    </p>
                    <ul class="pl-4 mb-1">
                        <li><strong><code>save_mode="on_submit"</code> (Default):</strong> When a participant chooses a time slot, the booking details are stored in a hidden input field named <code>__scheduling_calendar_booking</code>. The booking is committed only when the participant clicks the survey "Submit" button. If the participant abandons the survey without submitting, no orphaned appointment is created.</li>
                        <li><strong><code>save_mode="immediate"</code>:</strong> The appointment is written to the database immediately via AJAX upon selecting a slot. This mode is suitable for dedicated single-purpose scheduling pages where appointment confirmation must occur instantly.</li>
                    </ul>
                </div>
            </div>

            <!-- 4. Project Configuration -->
            <div id="config" class="docs-card" data-toc-title="Project Configuration">
                <div class="docs-card-header d-flex align-items-center">
                    <i class="fas fa-sliders-h mr-2"></i> Project Configuration
                </div>
                <div class="docs-card-body">
                    <p>
                        Project Configuration defines how the scheduling module connects to your specific study protocol, participant forms, and clinic workflows. Here you designate administrative privileges, configure which REDCap events correspond to schedulable study visits, map automated writeback fields so appointment dates and provider usernames populate directly into participant instruments, and set protocol visit windows, clinic locations, and study timezones.
                    </p>

                    <div class="section-title">
                        <i class="fas fa-cog"></i> General Settings
                    </div>
                    <div class="feature-card mb-2">
                        <strong>Calendar Admin:</strong> Designate specific REDCap users who may edit any provider's availability, reschedule any appointment, and access the Admin Tools (Data Clean Up, ICS Export, User Colors).
                    </div>
                    <div class="feature-card mb-2">
                        <strong>Unschedulable Users:</strong> Users who retain project rights or historical data but should not appear as selectable providers in scheduling filters or dropdowns.
                    </div>
                    <div class="feature-card mb-2">
                        <strong>Trigger DET:</strong> Dispatches an HTTP POST payload to the project's Data Entry Trigger endpoint upon availability or appointment changes.
                    </div>
                    <div class="feature-card mb-2">
                        <strong>Name Field & Withdraw Flag:</strong> Variable representing participant display names (e.g., <code>full_name</code>) and withdraw flag field to exclude withdrawn participants from scheduling searches.
                    </div>
                    <div class="feature-card mb-2">
                        <strong>Default Location & Location Source:</strong> Configure default clinic assignment (Blank, Field, or Static) and define where clinic location options are sourced. <strong>Field Choices</strong> is the standard and most common choice for studies (reading directly from your existing REDCap dropdown/radio fields), with <strong>Local JSON</strong> available for advanced sub-room hierarchies.
                    </div>
                    <div class="feature-card">
                        <strong>Timezones:</strong> Newline-separated list of valid <code>tzDatabase</code> timezone identifiers paired with friendly labels (one entry per line, e.g., <code>America/Chicago, Central Time</code>). When configured, a timezone switcher appears on the calendar header.
                    </div>

                    <div class="section-title">
                        <i class="fas fa-calendar-plus"></i> Schedulable Visit Types
                    </div>
                    <p>
                        The Schedulable Visits configuration is a repeatable block defining every appointment type offered by the study:
                    </p>
                    <ul class="pl-4 mb-3">
                        <li><strong>Display Name:</strong> The human-readable title displayed in dropdown menus, modal headers, and calendar blocks (e.g., <em>Baseline Clinical Exam</em>).</li>
                        <li><strong>Internal Coded Value:</strong> A short, unique slug identifying the visit (e.g., <code>v1_baseline</code>). Used in action tags (<code>visit="v1_baseline"</code>) and API queries.</li>
                        <li><strong>Linked Event:</strong> The REDCap longitudinal event associated with this visit for automated writeback.</li>
                        <li><strong>Duration (Minutes):</strong> The standard duration of this appointment type. Provider availability slots are partitioned into increments of this duration.</li>
                        <li><strong>Allow Additional Time:</strong> When enabled, coordinators can extend the appointment duration during booking if a participant requires extra accommodations.</li>
                        <li><strong>Allow Any Location:</strong> Bypasses physical location constraints; ideal for phone screening visits, telehealth check-ins, or remote questionnaires.</li>
                        <li><strong>Branching Logic:</strong> Conditional logic evaluated against participant data to determine eligibility (e.g., <code>[consent_signed] = '1' and [withdrawn] &lt;&gt; '1'</code>).</li>
                    </ul>

                    <div class="section-title">
                        <i class="fas fa-pen-nib"></i> Automated Writeback Engine
                    </div>
                    <p>
                        When an appointment is scheduled via the calendar interface or an action tag, the module commits data directly to the participant's record on the linked event:
                    </p>
                    <div class="table-responsive docs-table mb-2">
                        <table class="table table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 25%;">Writeback Target</th>
                                    <th style="width: 25%;">Expected REDCap Field Type</th>
                                    <th>Description & Writeback Behavior</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Date/Time Writeback</strong></td>
                                    <td><code>text</code> with date/datetime validation</td>
                                    <td>Populated with the scheduled visit date and start time (supports <code>datetime_ymd</code>, <code>datetime_seconds_ymd</code>, or <code>date_ymd</code>).</td>
                                </tr>
                                <tr>
                                    <td><strong>Provider Writeback</strong></td>
                                    <td><code>text</code> or <code>dropdown</code></td>
                                    <td>Populated with the booked provider's REDCap username.</td>
                                </tr>
                                <tr>
                                    <td><strong>Visit Window Start & End</strong></td>
                                    <td><code>text</code> with date validation</td>
                                    <td>Protocol-defined window boundary dates, displayed as reference guides on the scheduling calendar.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p>
                        When an appointment is cancelled or rescheduled, the module automatically clears or updates the corresponding instrument fields atomically.
                    </p>
                </div>
            </div>

            <!-- 5. System Configuration -->
            <div id="sys" class="docs-card" data-toc-title="System Configuration">
                <div class="docs-card-header d-flex align-items-center">
                    <i class="fas fa-server mr-2"></i> System Configuration
                </div>
                <div class="docs-card-body">
                    <p>
                        System-level configuration is required when the module is first installed to enable it across the REDCap institution. Going forward, accessing system configuration in the Control Center is <strong>only needed if shared availability is used</strong> across multiple projects. By default, every project operates independently using its own local availability pool without requiring ongoing administrator maintenance; system settings are only used when defining shared Availability Groups, mapping projects to unified pools to prevent cross-study double-booking, or configuring instance-wide global availability.
                    </p>

                    <div class="section-title">
                        <i class="fas fa-network-wired"></i> Multi-Project Availability Pooling
                    </div>
                    <p>
                        In academic medical centers and clinical research organizations, providers, research coordinators, and specialized equipment (e.g., MRI scanners, phlebotomy suites) are often shared across multiple research protocols. If each study operated in complete isolation, shared staff would frequently be double-booked.
                    </p>
                    <p>
                        The module solves this with <strong>Availability Groups</strong>:
                    </p>

                    <div class="feature-card mb-3">
                        <h6 class="font-weight-bold text-dark mb-1"><i class="fas fa-project-diagram text-primary mr-1"></i> How Availability Groups Work</h6>
                        <ol class="pl-3 mb-1">
                            <li>An administrator creates an Availability Group code (e.g., <code>CTRI_NURSING</code>).</li>
                            <li>The administrator maps project IDs (e.g., PID <code>101</code>, PID <code>105</code>, PID <code>219</code>) to that group.</li>
                            <li>A provider defines their weekly working hours once in any of the mapped projects.</li>
                            <li>When an appointment is booked for that provider in Project <code>101</code>, the time slot is automatically blocked out across Projects <code>105</code> and <code>219</code>.</li>
                        </ol>
                    </div>

                    <div class="section-title">
                        <i class="fas fa-cogs"></i> System Configuration Options
                    </div>
                    <div class="feature-card mb-2">
                        <strong>Availability Groups:</strong> Defines shared availability group codes and display names across projects (e.g., <code>CLINIC_MD</code>, <code>PHLEB_NURSE</code>).
                    </div>
                    <div class="feature-card mb-2">
                        <strong>Project Availability Group Mapping:</strong> Maps group codes to specific REDCap project IDs, enabling providers to maintain a single unified availability pool utilized across multiple independent studies.
                    </div>
                    <div class="feature-card mb-2">
                        <strong>Allow Global Group:</strong> Enables a shared global availability group accessible by all projects across the REDCap instance.
                    </div>
                    <div class="feature-card">
                        <strong>Prevent Local Group:</strong> Disables project-specific local availability groups, enforcing adherence to standardized institutional availability groups.
                    </div>
                </div>
            </div>

            <!-- 6. Administrative Tools -->
            <div id="admin" class="docs-card" data-toc-title="Administrative Tools">
                <div class="docs-card-header d-flex align-items-center">
                    <i class="fas fa-tools mr-2"></i> Administrative Tools
                </div>
                <div class="docs-card-body">
                    <p>
                        Users designated as Calendar Admins in Project Settings access maintenance utilities via the wrench icon on the calendar navigation bar:
                    </p>

                    <div class="section-title">
                        <i class="fas fa-trash-alt"></i> 1. Data Clean Up Tool
                    </div>
                    <p>
                        The Data Clean Up utility provides maintenance operations for long-running longitudinal studies:
                    </p>
                    <ul class="pl-4 mb-3">
                        <li><strong>Historical Availability Purge:</strong> Deletes past unbooked availability blocks prior to a chosen cutoff date, reducing database size and optimizing calendar query performance.</li>
                        <li><strong>Withdrawn Participant Cancellation:</strong> Scans the study participant list against the configured withdrawal flag and cancels pending scheduled appointments for withdrawn subjects, freeing up slots for active participants.</li>
                        <li><strong>Safe Execution:</strong> Operations require confirmation modals and produce audit log entries detailing deleted availability and cancelled visits.</li>
                    </ul>

                    <div class="section-title">
                        <i class="fas fa-calendar-alt"></i> 2. ICS Export & Live Calendar Subscription Feed
                    </div>
                    <p>
                        Providers and clinic managers can synchronize their scheduled appointments with external calendar applications:
                    </p>
                    <ul class="pl-4 mb-3">
                        <li><strong>Live Webcal Feed:</strong> Provides a secure, tokenized URL (e.g., <code>webcal://redcap.example.edu/...&token=xyz</code>) for live synchronization in Microsoft Outlook, Google Calendar, and Apple Calendar.</li>
                        <li><strong>Automatic Updates:</strong> When appointments are scheduled, rescheduled, or cancelled in REDCap, external calendars update automatically during their routine polling intervals.</li>
                        <li><strong>Static .ICS Export:</strong> Generates a standard <code>.ics</code> file snapshot for offline record-keeping, institutional reporting, or manual import.</li>
                    </ul>

                    <div class="section-title">
                        <i class="fas fa-palette"></i> 3. User Color Configuration
                    </div>
                    <p>
                        Calendar Admins can assign custom hex color badges to providers:
                    </p>
                    <ul class="pl-4 mb-1">
                        <li><strong>Custom Hex Palette:</strong> Choose from a built-in palette or enter custom hex color codes for each provider.</li>
                        <li><strong>Visual Distinction:</strong> Provider color badges persist across the Day, Week, Month, and Agenda calendar views, allowing coordinators to visually distinguish providers at a glance on busy clinic days.</li>
                    </ul>
                </div>
            </div>

            <!-- 7. Location Settings Structure -->
            <div id="locs" class="docs-card" data-toc-title="Location Configuration">
                <div class="docs-card-header d-flex align-items-center">
                    <i class="fas fa-map-marked-alt mr-2"></i> Location Configuration & Sources
                </div>
                <div class="docs-card-body">
                    <div class="alert alert-info border-0 mb-4" style="background-color: #e0f2fe; border-left: 4px solid #0284c7 !important; border-radius: 6px; color: #0369a1; font-size: 15px;">
                        <strong><i class="fas fa-info-circle mr-1"></i> Standard Setup:</strong> <strong>Field Choices</strong> is the most common, typical, and recommended choice for REDCap projects. If your project already has a dropdown or radio button for clinic location (e.g., <code>clinic_site</code>), selecting Field Choices requires zero JSON setup.
                    </div>

                    <p>
                        The module supports three distinct location sources configured in Project Settings:
                    </p>
                    <ul class="pl-4 mb-3">
                        <li>
                            <strong>Field Choices (Most Common & Recommended):</strong> Reads clinic locations directly from a REDCap dropdown or radio field in your data dictionary. The module automatically imports all choice codes and labels (e.g., <code>1, Main Hospital | 2, West Clinic</code>). When you edit or add options in the REDCap Online Designer, the scheduling calendar updates automatically without needing JSON configuration or manual synchronization.
                        </li>
                        <li>
                            <strong>Local JSON (Advanced Configuration):</strong> Structured JSON entered directly into project settings. This advanced option is only required if your study needs nested sub-locations (e.g. specific exam rooms or phlebotomy chairs), explicit telehealth modality flags (<code>"in_person": false</code>), or deactivation flags (<code>"active": false</code>) to retire former clinic sites.
                        </li>
                        <li>
                            <strong>Another Project PID (Master Repository):</strong> Points to a separate REDCap project designated as a centralized master clinic directory shared across multiple research studies at an institution.
                        </li>
                    </ul>

                    <div class="section-title">
                        <i class="fas fa-file-code"></i> Location JSON Schema Reference
                    </div>
                    <div class="table-responsive docs-table mb-3">
                        <table class="table table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 20%;">Property</th>
                                    <th style="width: 15%;">Type</th>
                                    <th>Description & Usage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>key</code> (Object Key)</td>
                                    <td><code>string</code></td>
                                    <td>Unique location code stored in the database (e.g., <code>"CLINIC_MAIN"</code>, <code>"WFH"</code>).</td>
                                </tr>
                                <tr>
                                    <td><code>name</code></td>
                                    <td><code>string</code></td>
                                    <td>Human-readable clinic name displayed on calendar events, slot cards, and dropdown filters.</td>
                                </tr>
                                <tr>
                                    <td><code>active</code></td>
                                    <td><code>boolean</code></td>
                                    <td>When set to <code>false</code>, hides the location from new availability blocks while preserving historical appointments.</td>
                                </tr>
                                <tr>
                                    <td><code>in_person</code></td>
                                    <td><code>boolean</code></td>
                                    <td>Distinguishes physical clinic facilities (<code>true</code>) from virtual, phone, or telehealth modalities (<code>false</code>).</td>
                                </tr>
                                <tr>
                                    <td><code>sub</code></td>
                                    <td><code>object</code></td>
                                    <td>Optional nested dictionary defining specific wings, suites, or exam rooms within the main facility.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="section-title">
                        <i class="fas fa-code"></i> Structured JSON Configuration Example
                    </div>
                    <pre><code>{
  <span class="code-comment">// Virtual / telehealth visit code</span>
  <span class="code-keyword">"WFH"</span>: {
    <span class="code-string">"name"</span>: <span class="code-string">"Telehealth / Phone Visit"</span>,
    <span class="code-string">"active"</span>: <span class="code-keyword">true</span>,
    <span class="code-string">"in_person"</span>: <span class="code-keyword">false</span>
  },
  <span class="code-comment">// Main medical center with sub-wings and suites</span>
  <span class="code-keyword">"CLINIC_MAIN"</span>: {
    <span class="code-string">"name"</span>: <span class="code-string">"Main Hospital Facility"</span>,
    <span class="code-string">"active"</span>: <span class="code-keyword">true</span>,
    <span class="code-string">"in_person"</span>: <span class="code-keyword">true</span>,
    <span class="code-string">"sub"</span>: {
      <span class="code-keyword">"EXAM_1"</span>: {
        <span class="code-string">"name"</span>: <span class="code-string">"Exam Suite 101"</span>,
        <span class="code-string">"active"</span>: <span class="code-keyword">true</span>
      },
      <span class="code-keyword">"LAB_A"</span>: {
        <span class="code-string">"name"</span>: <span class="code-string">"Phlebotomy Lab A"</span>,
        <span class="code-string">"active"</span>: <span class="code-keyword">true</span>
      }
    }
  },
  <span class="code-keyword">"CLINIC_SATELLITE"</span>: {
    <span class="code-string">"name"</span>: <span class="code-string">"Satellite Research Clinic"</span>,
    <span class="code-string">"active"</span>: <span class="code-keyword">true</span>,
    <span class="code-string">"in_person"</span>: <span class="code-keyword">true</span>
  }
}</code></pre>
                </div>
            </div>

            <!-- 8. Timezone Architecture -->
            <div id="timezones" class="docs-card" data-toc-title="Timezone Architecture">
                <div class="docs-card-header d-flex align-items-center">
                    <i class="fas fa-clock mr-2"></i> Timezone Architecture
                </div>
                <div class="docs-card-body">
                    <p>
                        The module implements a 3-tier timezone architecture designed for multi-center clinical trials and geographically distributed research:
                    </p>

                    <div class="feature-card mb-3">
                        <h6 class="font-weight-bold text-dark mb-1"><i class="fas fa-database text-primary mr-1"></i> Tier 1: Server-Local Storage Standard</h6>
                        <p class="mb-0">
                            All database timestamps (<code>time_start</code>, <code>time_end</code>) are stored in the REDCap web server's local timezone. Storing server-local time guarantees consistency, prevents daylight saving conversion anomalies, and avoids epoch timestamp corruption.
                        </p>
                    </div>

                    <div class="feature-card mb-3">
                        <h6 class="font-weight-bold text-dark mb-1"><i class="fas fa-desktop text-success mr-1"></i> Tier 2: Dynamic Coordinator Timezone Switcher</h6>
                        <p class="mb-0">
                            When timezones are configured in project settings, a timezone selector appears on the calendar header. Coordinators can switch between study site timezones on the fly; all calendar events, popups, and slot boundaries recalculate and render in the selected zone instantly.
                        </p>
                    </div>

                    <div class="feature-card mb-3">
                        <h6 class="font-weight-bold text-dark mb-1"><i class="fas fa-mobile-alt text-info mr-1"></i> Tier 3: Participant Device Auto-Detection</h6>
                        <p class="mb-0">
                            In public surveys using <code>@SCHEDULING-CALENDAR</code>, the widget defaults to <code>timezone="browser"</code>. The client-side script queries <code>Intl.DateTimeFormat().resolvedOptions().timeZone</code> to resolve the participant's device timezone (e.g., <code>America/New_York</code>). All slots appear in the participant's local time, eliminating timezone conversion confusion for research subjects.
                        </p>
                    </div>

                    <div class="section-title">
                        <i class="fas fa-list-ol"></i> Configuring Project Timezones
                    </div>
                    <p>
                        In Project Settings under <strong>Timezones</strong>, enter each supported timezone on its own line using the format <code>TimezoneIdentifier, Friendly Label</code> (separated by newlines, not pipes):
                    </p>
                    <pre><code>America/Chicago, Central Time
America/New_York, Eastern Time
America/Denver, Mountain Time
America/Los_Angeles, Pacific Time</code></pre>
                </div>
            </div>

            <!-- 9. Data Entry Trigger (DET) -->
            <div id="det" class="docs-card" data-toc-title="Data Entry Trigger (DET)">
                <div class="docs-card-header d-flex align-items-center">
                    <i class="fas fa-network-wired mr-2"></i> Data Entry Trigger (DET) & Webhooks
                </div>
                <div class="docs-card-body">
                    <p>
                        In REDCap, outbound event notifications are natively termed <strong>Data Entry Triggers (DET)</strong>, which operate architecturally as outbound <strong>webhooks</strong>. When <strong>Trigger DET</strong> is enabled in Project Settings, the module dispatches an HTTP POST request to the project's configured Data Entry Trigger URL whenever availability blocks or appointments are created, updated, rescheduled, or cancelled.
                    </p>

                    <div class="section-title">
                        <i class="fas fa-file-code"></i> Data Entry Trigger POST Payload Schema
                    </div>
                    <div class="table-responsive docs-table mb-3">
                        <table class="table table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 20%;">Field</th>
                                    <th style="width: 15%;">Type</th>
                                    <th>Description & Example Values</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>project_id</code></td>
                                    <td><code>integer</code></td>
                                    <td>REDCap project ID (e.g., <code>219</code>).</td>
                                </tr>
                                <tr>
                                    <td><code>resource</code></td>
                                    <td><code>string</code></td>
                                    <td>Entity type: <code>"Appointment"</code> or <code>"Availability"</code>.</td>
                                </tr>
                                <tr>
                                    <td><code>crud</code></td>
                                    <td><code>string</code></td>
                                    <td>Action performed: <code>"create"</code>, <code>"update"</code>, or <code>"delete"</code>.</td>
                                </tr>
                                <tr>
                                    <td><code>start</code> / <code>end</code></td>
                                    <td><code>string</code></td>
                                    <td>Timestamp in server time format (<code>"YYYY-MM-DD HH:MM:SS"</code>).</td>
                                </tr>
                                <tr>
                                    <td><code>providers</code></td>
                                    <td><code>array</code></td>
                                    <td>List of provider REDCap usernames (e.g., <code>["dr_smith"]</code>).</td>
                                </tr>
                                <tr>
                                    <td><code>subjects</code></td>
                                    <td><code>array</code></td>
                                    <td>List of participant record IDs (e.g., <code>["9001"]</code>).</td>
                                </tr>
                                <tr>
                                    <td><code>visits</code></td>
                                    <td><code>array</code></td>
                                    <td>List of internal visit type codes (e.g., <code>["v1_baseline"]</code>).</td>
                                </tr>
                                <tr>
                                    <td><code>id</code></td>
                                    <td><code>integer</code></td>
                                    <td>Primary key ID in table <code>em_scheduling_calendar</code>.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <pre><code>{
  <span class="code-string">"redcap_url"</span>: <span class="code-string">"https://redcap.example.edu"</span>,
  <span class="code-string">"project_url"</span>: <span class="code-string">"https://redcap.example.edu/redcap_v14.0.0/index.php?pid=219"</span>,
  <span class="code-string">"project_id"</span>: 219,
  <span class="code-string">"username"</span>: <span class="code-string">"coordinator_user"</span>,
  <span class="code-string">"resource"</span>: <span class="code-string">"Appointment"</span>,
  <span class="code-string">"crud"</span>: <span class="code-string">"create"</span>,
  <span class="code-string">"msg"</span>: <span class="code-string">"Appointment scheduled"</span>,
  <span class="code-string">"start"</span>: <span class="code-string">"2026-10-15 09:00:00"</span>,
  <span class="code-string">"end"</span>: <span class="code-string">"2026-10-15 10:00:00"</span>,
  <span class="code-string">"providers"</span>: [<span class="code-string">"dr_smith"</span>],
  <span class="code-string">"locations"</span>: [<span class="code-string">"CLINIC_MAIN"</span>],
  <span class="code-string">"subjects"</span>: [<span class="code-string">"9001"</span>],
  <span class="code-string">"visits"</span>: [<span class="code-string">"v1_baseline"</span>],
  <span class="code-string">"notes"</span>: <span class="code-string">"Participant requested morning appointment"</span>,
  <span class="code-string">"id"</span>: 142
}</code></pre>

                    <div class="section-title">
                        <i class="fab fa-python"></i> Receiver Implementation (Python, FastAPI & Twilio)
                    </div>
                    <p>
                        REDCap transmits the DET notification via an HTTP POST request. Because REDCap's cURL request has a 10-second timeout, the receiver responds with an <strong>HTTP 200 acknowledgment immediately</strong> and offloads all processing to FastAPI's <code>BackgroundTasks</code>.
                    </p>
                    <p>
                        In the background worker, CSV column headers are dynamically pulled directly from the incoming DET POST parameters, an SMS notification is dispatched via Twilio using event details, and additional institutional integrations (e.g. EHR/FHIR sync, email alerts) can be connected:
                    </p>
                    <pre><code><span class="code-comment"># Install requirements: pip install fastapi uvicorn twilio</span>
<span class="code-keyword">import</span> csv
<span class="code-keyword">from</span> datetime <span class="code-keyword">import</span> datetime
<span class="code-keyword">from</span> pathlib <span class="code-keyword">import</span> Path
<span class="code-keyword">from</span> fastapi <span class="code-keyword">import</span> FastAPI, BackgroundTasks, Request, HTTPException
<span class="code-keyword">from</span> twilio.rest <span class="code-keyword">import</span> Client

app = FastAPI(title=<span class="code-string">"REDCap Scheduling DET Receiver"</span>)
CSV_FILE = Path(<span class="code-string">"scheduling_events.csv"</span>)

<span class="code-keyword">def</span> <span class="code-function">log_to_csv</span>(payload: dict):
    <span class="code-comment"># Pull CSV columns dynamically from the parameters sent in the DET POST</span>
    file_exists = CSV_FILE.exists()
    fieldnames = list(payload.keys())

    <span class="code-comment"># If CSV exists, merge existing headers with any new keys from this post</span>
    <span class="code-keyword">if</span> file_exists:
        <span class="code-keyword">with</span> open(CSV_FILE, mode=<span class="code-string">"r"</span>, newline=<span class="code-string">""</span>, encoding=<span class="code-string">"utf-8"</span>) <span class="code-keyword">as</span> f:
            reader = csv.reader(f)
            existing_headers = next(reader, [])
            fieldnames = existing_headers + [k <span class="code-keyword">for</span> k <span class="code-keyword">in</span> fieldnames <span class="code-keyword">if</span> k <span class="code-keyword">not in</span> existing_headers]

    <span class="code-keyword">with</span> open(CSV_FILE, mode=<span class="code-string">"a"</span>, newline=<span class="code-string">""</span>, encoding=<span class="code-string">"utf-8"</span>) <span class="code-keyword">as</span> f:
        writer = csv.DictWriter(f, fieldnames=fieldnames, extrasaction=<span class="code-string">"ignore"</span>)
        <span class="code-keyword">if not</span> file_exists:
            writer.writeheader()
        writer.writerow(payload)

<span class="code-keyword">def</span> <span class="code-function">send_twilio_sms</span>(payload: dict):
    <span class="code-comment"># Example: Dispatch SMS via Twilio using captured DET event details</span>
    account_sid = <span class="code-string">"AC_FAKE_TWILIO_ACCOUNT_SID"</span>
    auth_token = <span class="code-string">"fake_twilio_auth_token"</span>
    client = Client(account_sid, auth_token)

    resource = payload.get(<span class="code-string">"resource"</span>, <span class="code-string">"Calendar Event"</span>)
    action = payload.get(<span class="code-string">"crud"</span>, <span class="code-string">"updated"</span>)
    start_time = payload.get(<span class="code-string">"start"</span>, <span class="code-string">"scheduled time"</span>)
    subjects = payload.get(<span class="code-string">"subjects"</span>) <span class="code-keyword">or</span> payload.get(<span class="code-string">"record"</span>, <span class="code-string">"participant"</span>)

    sms_body = f<span class="code-string">"REDCap Alert: {resource} {action} for record {subjects} at {start_time}."</span>
    
    client.messages.create(
        body=sms_body,
        from_=<span class="code-string">"+15551234567"</span>,  <span class="code-comment"># Fake Twilio sender number</span>
        to=<span class="code-string">"+15559876543"</span>      <span class="code-comment"># Fake recipient phone number</span>
    )

<span class="code-keyword">def</span> <span class="code-function">process_det_event</span>(payload: dict):
    <span class="code-comment"># 1. Log event parameters to CSV</span>
    log_to_csv(payload)

    <span class="code-comment"># 2. Example: Send SMS notification via Twilio</span>
    send_twilio_sms(payload)

    <span class="code-comment"># -------------------------------------------------------------</span>
    <span class="code-comment"># NOTE: Other integrations could go here!</span>
    <span class="code-comment"># Examples:</span>
    <span class="code-comment">#  - Synchronize appointment with institutional EHR / FHIR endpoints</span>
    <span class="code-comment">#  - Send staff calendar invites via Microsoft Graph or Google Calendar</span>
    <span class="code-comment">#  - Post real-time audit notifications to an MS Teams or Slack channel</span>
    <span class="code-comment">#  - Enqueue ingestion tasks in an institutional data warehouse</span>
    <span class="code-comment"># -------------------------------------------------------------</span>

<span class="code-keyword">@app.post</span>(<span class="code-string">"/det"</span>)
<span class="code-keyword">async def</span> <span class="code-function">receive_det</span>(background_tasks: BackgroundTasks, request: Request):
    <span class="code-comment"># Capture all parameters from HTTP POST (form-data with JSON fallback)</span>
    form_data = <span class="code-keyword">await</span> request.form()
    payload = dict(form_data)
    <span class="code-keyword">if not</span> payload:
        <span class="code-keyword">try</span>:
            payload = <span class="code-keyword">await</span> request.json()
        <span class="code-keyword">except</span> Exception:
            payload = {}

    <span class="code-keyword">if not</span> payload:
        <span class="code-keyword">raise</span> HTTPException(status_code=400, detail=<span class="code-string">"No payload received in POST request"</span>)

    payload[<span class="code-string">"received_at"</span>] = datetime.now().isoformat()

    <span class="code-comment"># Run processing in background so REDCap receives an immediate HTTP 200 response</span>
    background_tasks.add_task(process_det_event, payload)

    <span class="code-keyword">return</span> {<span class="code-string">"status"</span>: <span class="code-string">"success"</span>, <span class="code-string">"message"</span>: <span class="code-string">"DET event scheduled for background processing"</span>}

<span class="code-comment"># Run receiver: uvicorn main:app --host 0.0.0.0 --port 8000</span></code></pre>
                </div>
            </div>

            <!-- 10. URL Deep Linking -->
            <div id="query" class="docs-card" data-toc-title="URL Deep Linking">
                <div class="docs-card-header d-flex align-items-center">
                    <i class="fas fa-link mr-2"></i> URL Deep Linking
                </div>
                <div class="docs-card-body">
                    <p>
                        You can construct direct links to specific calendar views from REDCap instrument descriptive text fields, automated survey invitations, or external dashboards using URL query parameters:
                    </p>

                    <div class="table-responsive docs-table mb-3">
                        <table class="table table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 22%;">Parameter</th>
                                    <th>Function & Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>record</code> or <code>id</code></td>
                                    <td>Pre-selects a subject record ID on page load. Automatically opens scheduling sidebar for that record.</td>
                                </tr>
                                <tr>
                                    <td><code>date</code></td>
                                    <td>Sets the initial calendar date view (format <code>YYYY-MM-DD</code>). Defaults to today.</td>
                                </tr>
                                <tr>
                                    <td><code>type</code></td>
                                    <td>Sets initial operational view mode: <code>schedule</code> (default scheduling view), <code>edit</code> (availability management), or <code>my</code> (my personal calendar).</td>
                                </tr>
                                <tr>
                                    <td><code>tz</code></td>
                                    <td>Pre-selects an active timezone identifier on page load (e.g., <code>tz=America/Chicago</code>).</td>
                                </tr>
                                <tr>
                                    <td><code>refer</code></td>
                                    <td>Set to <code>true</code> or an encoded URL. Displays a "Return to Workflow" button in the calendar toolbar allowing staff to navigate back to their previous page with one click.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="section-title">
                        <i class="fas fa-external-link-alt"></i> Deep Linking Examples
                    </div>
                    <p><strong>Open scheduling view for current participant:</strong></p>
                    <pre><code>[redcap-version-url]ExternalModules/?prefix=scheduling_calendar&page=index&pid=[project-id]&record=[record-name]</code></pre>

                    <p><strong>Open scheduling view for a specific date with return navigation:</strong></p>
                    <pre><code>[redcap-version-url]ExternalModules/?prefix=scheduling_calendar&page=index&pid=[project-id]&record=[record-name]&date=[target_visit_date]&refer=true</code></pre>
                </div>
            </div>

        </div>

        <!-- Sticky Right Navigation Column -->
        <div class="col-lg-3 col-md-4">
            <div class="sticky-top sticky-toc-wrapper" style="top: 1.5rem; z-index: 100;">
                <div class="toc-card">
                    <div class="toc-card-header d-flex align-items-center">
                        <i class="fas fa-list-ul mr-2 text-primary"></i> Contents
                    </div>
                    <nav id="toc"></nav>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    // Format code blocks (strip leading whitespace if needed)
    $("#pagecontainer pre code").each((_, el) => {
        let lines = $(el).text().split('\n')
        if (lines.length > 0 && lines[0].trim().length === 0) lines.shift()
        if (lines.length > 0 && lines[lines.length - 1].trim().length === 0) lines.pop()
        let minIndent = null
        lines.forEach(line => {
            if (line.trim().length === 0) return
            let indent = line.search(/\S/)
            if (indent !== -1 && (minIndent === null || indent < minIndent)) minIndent = indent
        })
        if (minIndent && minIndent > 0) {
            $(el).text(lines.map(line => line.slice(minIndent)).join('\n'))
        }
    })

    // Dynamically generate Table of Contents from docs-cards
    $(".docs-card[id]").each((_, el) => {
        let id = $(el).attr('id')
        if (id === 'toc') return
        let titleText = $(el).attr('data-toc-title')
        if (!titleText) {
            let headerEl = $(el).find('.docs-card-header').clone()
            headerEl.find('.badge, i').remove()
            titleText = headerEl.text().trim()
        }
        if (!titleText) titleText = id
        $("#toc").append(`<div><a href="#${id}"><i class="fas fa-chevron-right text-muted"></i><span>${titleText}</span></a></div>`)
    })

    // Highlight active TOC item on scroll
    $(window).on('scroll', function() {
        let scrollPos = $(document).scrollTop() + 100
        let currentId = ""
        $(".docs-card[id]").each(function() {
            let top = $(this).offset().top
            let height = $(this).outerHeight()
            if (scrollPos >= top && scrollPos < top + height) {
                currentId = $(this).attr('id')
            }
        })
        if (currentId) {
            $("#toc a").removeClass("active")
            $(`#toc a[href="#${currentId}"]`).addClass("active")
        }
    })
</script>

<?php
$HtmlPage->PrintFooter();
