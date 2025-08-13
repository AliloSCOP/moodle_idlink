# Moodle IdLink Plugin

This plugin allows you to quickly redirect users to a Moodle activity using the activity's `idnumber` and optionally the course's `idnumber`.

Why doing this ? It's a way of managing 'universal' links that don't use courses and activity ids, and that can be preserved when the course is exported to another Moodle instance.

## Features

- Redirects to the activity view page if the user has access.
- Supports filtering by course using the course's `idnumber`.
- If multiple activities match, presents a selection list.
- Handles permissions and visibility checks.

## Usage

Call the plugin via URL:

```
/local/idlink/index.php?idnum=ACTIVITY_IDNUMBER&courseidnum=COURSE_IDNUMBER
```

- `idnum`: The `idnumber` of the activity (from `course_modules.idnumber`).
- `courseidnum`: (optional) The `idnumber` of the course (from `course.idnumber`).

If `courseidnum` is provided, the search is restricted to that course.

## Installation

1. Copy the plugin folder to `local/idlink` in your Moodle installation.
2. Visit the admin notifications page to complete installation.

## Requirements

- Moodle 5.x

## License

MIT
