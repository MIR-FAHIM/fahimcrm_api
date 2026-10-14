# React Admin Prospect Team Panel

Implement a Prospect Teams panel in the existing React admin application. Reuse the current API client, routing, UI components, and authenticated session. Add the page at `/admin/prospect-teams` and a navigation entry in the existing admin area. Permit active users with role names `Admin` or `Super Admin`; the backend also enforces this on the four admin endpoints.

This panel manages internal employee assignments from `ProspectConcernPersonTeam`. It does not manage external prospect contact people. Read the new `team_members` field, not `concern_persons`.

## APIs

Send the logged-in administrator's `token` header and `Accept: application/json` on every request. Send assignment IDs as numbers and flags as JSON booleans.

### List prospects and teams

`GET /api/prospect-concern-person/admin/prospects`

Optional query parameters:

- `search`: prospect name or address.
- `employee_id`: prospects actively assigned to this employee.
- `stage_id`: prospect stage.
- `team_status`: `all`, `assigned`, or `unassigned`, based on active assignment rows.
- `page`: default 1.
- `per_page`: default 20, maximum 100.

The list contains active records whose `type` is `prospect`. It includes prospects without any team members. Every row includes both active and inactive assignment rows.

Response shape:

```json
{
  "status": "success",
  "data": [
    {
      "id": 150,
      "prospect_name": "ABC Company",
      "address": "Dhaka",
      "stage_id": 1,
      "stage": { "id": 1, "stage_name": "New" },
      "team_members_count": 1,
      "active_team_members_count": 1,
      "team_members": [
        {
          "id": 45,
          "prospect_id": 150,
          "employee_id": 2,
          "is_active": true,
          "notify": false,
          "created_at": "2026-10-14T04:00:00.000000Z",
          "updated_at": "2026-10-14T04:00:00.000000Z",
          "employee": {
            "id": 2,
            "name": "Employee Name",
            "email": "employee@example.com",
            "photo": null,
            "isActive": true
          }
        }
      ]
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 1,
    "last_page": 1,
    "from": 1,
    "to": 1
  }
}
```

`stage` and `employee` may be null. `team_members_count` counts all assignment rows; `active_team_members_count` counts active assignment rows. Employee account status is separately available in `employee.isActive`.

### Add members to one prospect

`POST /api/prospect-concern-person/admin/add`

```json
{
  "prospect_id": 150,
  "employees": [
    { "employee_id": 2, "is_active": true },
    { "employee_id": 3, "is_active": true }
  ]
}
```

Allow 1-100 distinct active employees. Existing members are updated/reactivated; other members remain assigned. Optional `notify` is a boolean. If omitted, an existing member retains its value; a new member defaults to false. A successful response has `status: "success"`, `message`, and `data` containing the affected assignment rows.

### Remove a member

`POST /api/prospect-concern-person/admin/remove`

```json
{ "prospect_id": 150, "employee_id": 2 }
```

This deletes only that prospect/employee assignment. Send `employee_id`, not the assignment row's `id`. A missing assignment returns HTTP 404. Removal also works if the employee record has been deleted.

### Bulk assign one employee

`POST /api/prospect-concern-person/admin/bulk-add`

```json
{
  "employee_id": 2,
  "prospect_ids": [150, 151, 152]
}
```

Select one active employee and 1-500 distinct, active prospect IDs. Optional `notify` applies only to newly created assignments and defaults to false. Existing notification flags are preserved. No immediate push notification is sent by these endpoints.

Bulk assignment adds missing assignments, reactivates inactive assignments, and skips existing active assignments. It preserves all other team members. Validation failure prevents the whole request from being applied.

Successful response:

```json
{
  "status": "success",
  "message": "Bulk prospect team assignment completed.",
  "employee_id": 2,
  "data": { "total": 3, "added": 1, "reactivated": 1, "skipped": 1 }
}
```

### Dropdown sources

- `GET /api/get-all-employee`: read the `data` array. Allow adding employees only when `isActive` is true or 1. Retain inactive employees in filter options if needed to find their existing assignments.
- `GET /api/get-prospect-stages`: read the `data` array; display `stage_name` and submit `id`.

## Panel Requirements

- Use a compact table with selection checkboxes, prospect name, stage, address, members, active member count, and actions. Link the prospect name to the existing details route.
- Provide debounced search, employee filter, stage filter, assignment filter, and server pagination. Reset page to 1 when a filter changes. Cancel or ignore stale list responses.
- Display member names/avatars and assignment status. Show inactive assignments separately from inactive employee accounts. Handle null employees with an employee-ID fallback and allow removal.
- Add a per-prospect Manage Members drawer/modal. Show current members, searchable employee selection, Add action, and remove icon buttons with tooltips and confirmation. Offer reactivation for inactive assignments whose employee account is active.
- For the add picker, omit employees already actively assigned to the prospect. Load existing members from the list row; no extra detail request is necessary.
- Add a bulk toolbar after rows are selected: selected count, clear selection, a searchable employee picker, and Assign action. Keep the bulk target employee separate from the employee filter.
- Preserve explicit selection across pagination using a set/map of IDs. The header checkbox selects only the visible page. Clear selection when filters change. Do not interpret select-all as all database records. Enforce the 500-prospect limit.
- Confirm the target employee and selected count before bulk assignment. Do not submit until an employee and prospects have been selected.
- After bulk success, show the added/reactivated/skipped counts, clear selection, and refetch the current filtered list. Existing active assignments count as skipped, not failures. Do not calculate these totals locally.
- Refresh after single add/remove. If removal leaves the current page beyond the last page, move to the last valid page. Never remove a team locally before server success.
- Include loading, empty-list, no-results, submitting, and error states. Disable duplicate submissions. Preserve selection and form state on failure.
- Treat `status === "success"` as success. Handle HTTP 422 `errors` from validation; HTTP 403 `status: "failed"` from insufficient role; HTTP 404 for a missing assignment; and HTTP 450 token errors through the existing session-expiry flow. Do not silently hide request failures.
- Keep all other prospect pages and their external contact-person flows working. Use the new admin endpoints for this panel.

Verify list/search/pagination, access restrictions, add/remove, reactivation, repeated bulk assignment, failure handling, and selections across pages. Run the project's normal checks/build and report the files changed.
