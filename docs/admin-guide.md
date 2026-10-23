# Admin guide

Sign in at `/login` with your staff email, password, and authenticator code. On a phone, open **Menu** for the rest of the sections. The session ends after 30 idle minutes unless Settings changes that.

Owners and managers see the full operations menu. Porters see Tonight and Security only. Settings is owner-only.

## Dashboard

Shows live properties, active doors, tonight’s routes, open issues, and this month’s invoices. Revenue is the amount those invoices say the properties owe. Profit subtracts labor from clocked hours, per-site supplies, gas, the vehicle reserve, and a share of monthly fixed costs. If there are no invoices yet, those money figures stay at zero. The dashboard does not invent a forecast.

## Pipeline

Stages run from lead, to contacted, walkthrough, proposal, signed, live, and lost. Add a property with **New lead**, or import a CSV. The expected columns are `name`, `address`, `units`, `manager`, `phone`, `email`, `website`, and `has_valet_trash` (`yes`, `no`, or `unknown`). A header row named `EXAMPLE` in the downloadable template is a sample and is skipped when the name is exactly `EXAMPLE`. Duplicate names are skipped, and so is the template row whose name starts with EXAMPLE. Grouping by management company is optional and can be typed on the import or created from the pipeline page.

A proposal sent from the public website lands here as a lead and in **Inbox**.

## Properties

Signed and live communities are listed under Properties. Each record holds units, the pricing model, the property share, service nights, the pickup window, compactor location, contract dates, buildings, and contacts.

**Gate and access notes** are for getting the porter through the gate. Owners and managers can read them. A porter can read them only for a property on their route or on their active weekly template. They are not included in the leasing-office roster export or the weekly issue report. Put the code only in that field. The form warns against pasting it into an ordinary note.

## Residents

Each door has a unit, a name, a start date, a cancel date, and a status (`pending`, `active`, or `cancelled`). Import a CSV with `unit`, `name`, `building`, `start_date`, `cancel_date`, and `status`. Importing again updates the same unit.

**Export for the leasing office** downloads the month you pick. A door is on the bill when it was active for any part of that month. The file has no gate column. Cells that start with `=`, `+`, `-`, or `@` are prefixed so a spreadsheet does not treat them as formulas.

Opt-in billing uses this roster. Cancel with 30 days’ notice, ending at the next month-end, by setting the cancel date. The free 30-day trial is recorded with the trial dates on the property. Buildings included in a trial are the ones you choose to operate. The price is not prorated for a holiday or a weather night.

## Team

Add managers and porters with a phone, a pay rate, and availability. Porters default to $15 an hour until you change the rate. Only an owner can create another owner. The last active owner cannot be removed. New people enroll their own authenticator the first time they sign in. You can also log hours by hand when a clock-in was missed. Billing uses closed clock records, not a guessed night length.

## Schedule

A route is one porter, one date, a start time (default 8:00 PM), and an ordered list of properties. Time is about 1 minute per door plus 45 minutes at the site. Both numbers are in Settings, and a property can override them. Doors are the active roster when it has rows, otherwise the planned-door count, otherwise the unit count for a community-wide site.

Porters needed is the estimate divided by the 120-minute pickup window, rounded up. The page shows the estimated finish and a Google Maps directions link for the stop order. Each stop also has its own map link.

**Weekly template** repeats by weekday. **Generate** builds the next several weeks and skips Saturday, Sunday, and holidays marked “service off.” Martin Luther King Jr. Day 2027 is on the calendar as a normal service night. Generating again does not duplicate a template that already has that date.

## Tonight

Owners and managers can open any route. Porters open only routes assigned to them. The page reminds them that trash belongs in the can, with any second bag on top of the can. On an extra-bag night, extra bags go only on top of the can, never on the ground or beside it. A loose bag on the ground is reported to the office as the violation "loose bag (not in/on can)." Check a door as picked up, missed, or a violation. A missed door or a violation opens an issue. A photo can be attached (JPEG, PNG, or WebP, up to 5 MB). Clock in when the route starts and clock out when the bags are in the compactor. A manager can mark stops. Clock-in is the assigned porter, or an owner covering the route.

## Issues

Missed pickups from the public form, and misses or violations from the route, share one list. Status is open, scheduled return, or resolved. **Weekly report** is a printable page and a PDF for one property and one week. It lists issues and leaves gate notes out.

The operating rule, also on the public site: a bag that was out by 8:00 PM and was missed is a same-day return if the office or the resident reports it by 10:00 AM the next day. A late set-out waits for the next service night. Violation notices are courtesy, then a copy to the office, then the office decides. The porter does not decide a lease penalty.

## Billing

Generate a month for every signed or live property, or for one property. An opt-in invoice is billable doors × $35, minus the property share. A per-door community price is units × that rate, with no share. A flat community price is the flat amount, with no share. Drafts and sent invoices refresh if you generate again. Paid and void invoices are left alone.

Due date is the remittance day (default the 10th) of that service month. A draft or sent invoice past that date shows as late. Mark an invoice sent, paid, or void.

Profit for a site is amount due, minus porter hours × wage × 1.10 payroll, minus that site’s supplies, gas, and vehicle reserve, minus a share of monthly fixed costs. The share is the fixed total divided by the number of non-void invoices that month. Default planning numbers, all editable in Settings, are supplies $40, gas $90, vehicle reserve $25, and fixed costs $349 (general liability $60, auto $234, phone $30, admin $25). Labor uses hours that were actually clocked. Hours on a route with several properties are split by each property’s estimated minutes.

## Reviews and inbox

**Reviews** holds submissions from the public form. Approve to publish. Reject to keep it off the site. Nothing is published by default, and the software does not ship with sample quotes.

**Inbox** is walkthrough, proposal, party and event cleanup, and move-out requests. Update the status as you contact them. The matching lead is already on the pipeline.

## Settings and audit

Settings holds the business name, owner name, phone, email, address, service area, insurance text, opt-in price, default share, bag limits, route timing, wages, cost assumptions, remittance day, and session timeout. Clearing phone, email, address, or insurance removes them from the public site.

Holidays are listed with a service-off flag and a short instruction. Add the next year’s dates before December.

**Audit** records sign-ins, roster exports, setting changes, review decisions, and similar actions. It does not store the text of gate notes.
