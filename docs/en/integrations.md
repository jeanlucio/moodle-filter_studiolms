# 🔗 Integrations

The filter needs nothing else. The other two plugins of the [StudioLMS family](https://jeanlucio.github.io/studiolms/) use it when it is there; this plugin does not know them.

| Plugin | How it uses the filter |
|---|---|
| [`tiny_studiolms`](https://jeanlucio.github.io/moodle-tiny_studiolms/) (StudioLMS Editor) | A **Widgets** tab in the dialog inserts the three markers as blocks. It asks the filter which widgets are on and whether the filter is active in the course, and shows a warning when it is not. Without the filter the tab does not appear |
| [`local_studiolms`](https://jeanlucio.github.io/moodle-local_studiolms/) (StudioLMS Course Builder) | When it generates a quiz it writes `[QUIZINFO]` in the description, so the rules stay up to date. It does so only where the filter is installed, has the widget on and is active in the course; otherwise it writes the numbers once |
