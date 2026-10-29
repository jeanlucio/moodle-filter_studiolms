# ✨ Features

**StudioLMS Widgets** is a text filter. A teacher types a short marker in some text of the course, and when the page is shown the marker is replaced by a widget with live data. Nothing is stored in the text but the marker, so the widget never goes stale.

| Marker | Where it works | What it shows |
|---|---|---|
| `[FORUMSTATS]` | The description of a forum | A bar with how much of the class has taken part in that forum |
| `[COURSEMAP]` | Any text in a course | The sections of the course as a path, by date or by the student's own progress |
| `[QUIZINFO]` | The description of a quiz | The rules of the quiz as they are now |

* 📊 **Forum participation (`[FORUMSTATS]`):** a bar and two lines: who has taken part and who has not yet, with the percentage and the count ("6 of 9"). Taking part means having sent at least one message (a topic or a reply) in that forum; deleted messages and private replies do not count. It counts the students of the course (those who hold `mod/forum:replypost` and are shown in the completion reports), and in a forum with separate groups each student sees the count of their own groups. A class smaller than the minimum (5 by default) is hidden from the students, so nobody can be identified, and the teacher sees it with a note.
* 🧭 **Course map (`[COURSEMAP]`):** the sections of the course as numbered steps, each a link to the section. The general section, the sections the viewer cannot see and subsections are left out. By default the current step is the one the course format marks (the highlighted topic, or the week of today). With `current=progress` the steps follow what the viewer has completed: a section is done when every activity with a completion rule is, the first one with something left is the current step, and a section with no activity that has a completion rule is left unmarked. A course with more than 12 sections is shown as tiles, and `style=tiles` or `style=steps` forces one.
* ✅ **Quiz rules (`[QUIZINFO]`):** cards with the number of questions (only real questions: a description is not a question), the time limit, the attempts, the grade to pass, the grading method (shown when more than one attempt is allowed) and the closing date. Each person sees their own rules: a student with an extension of time or more attempts sees theirs, and a colleague sees the defaults.
* ⚡ **Light on the site:** text with no `[` is returned at once, with no query. The forum counts are one aggregated query, cached for five minutes and refreshed when a message is posted, deleted or moved.
* 🔒 **Safe by design:** the target (the forum, the quiz or the course) always comes from the place where the text is shown, never from what is typed in the marker. Guests and visitors get no widget, and a marker where it does not apply is simply removed.
* ♿ **Accessible and themed:** the widgets use plain markup that survives Moodle's HTML cleaner, with text for the state of every step, and follow the theme.
