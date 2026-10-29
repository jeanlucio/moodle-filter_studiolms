# Changelog — filter_studiolms

All notable changes to this plugin are documented here.

---

## [v1.0.0] — 2026-10-29

First public release.

### Added
- `[FORUMSTATS]`: how much of the class has taken part in a forum, as a bar. It respects groups, and a class
  too small to be shown is hidden from students and visible to teachers.
- `[COURSEMAP]`: the sections of the course as a path, by date, by the progress of each student or as tiles.
  Hidden and restricted sections do not show to those who cannot see them.
- `[QUIZINFO]`: the rules of a quiz as they are now (questions, attempts, time limit and grading method),
  with the time limit that applies to whoever is looking.
- Options written inside the marker, and an administration page to turn each widget on or off for the site.
- The markers are what the Widgets tab of the StudioLMS editor inserts, and the StudioLMS course builder
  writes the quiz marker into the quizzes it creates.
- No AI, no other plugin required. Support for Moodle 4.5 to 5.3.
