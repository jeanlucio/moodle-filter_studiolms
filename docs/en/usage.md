# 📖 Usage

## Writing a marker

Type the marker in the text where the widget should appear, on a line of its own. The text of the course is filtered when the page is shown, so there is nothing to save but the marker.

* In the **description of a forum**: `[FORUMSTATS]`
* In the **description of a quiz**: `[QUIZINFO]`
* In any **page, label, description or section summary** of a course: `[COURSEMAP]`

With **StudioLMS Editor** installed you do not have to type anything: the **Widgets** tab of its dialog inserts the marker as a block (see [Integrations](#integrations)).

## Options of the course map

An option is written after the name, as `name=value`:

| Option | Values | What it does |
|---|---|---|
| `current` | `date` (default), `progress` | `date`: the current step is the one the course format marks. `progress`: the steps follow what the viewer has completed |
| `style` | `auto` (default), `steps`, `tiles` | The look of the map. `auto` chooses tiles for a course with more than 12 sections |

For example: `[COURSEMAP current=progress style=tiles]`. A name or a value that is not in the list is ignored, and a marker with a name the filter does not know is removed.

## Where it must be on

The filter has to be on for the course (**Site administration > Plugins > Filters > Manage filters**, or **Filters** in the course). It is turned on at the first installation. Where the filter is off the marker is shown as typed.

## What the administrator can set

Under **Site administration > Plugins > Filters > StudioLMS widgets** each widget can be turned off for the whole site, and the **minimum class size** of the forum bar can be changed (5 by default). A widget that is off is also left out of the Widgets tab of the editor.
