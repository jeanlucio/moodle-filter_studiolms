# 🧪 Automated Tests

* **PHPUnit:** **60 tests** (239 assertions), run in CI on Moodle 4.5, 5.0, 5.1, 5.2 and 5.3. Line coverage of the logic is **98%** (350 of 356 lines, measured on 29 October 2026 with Xdebug). What is left is six lines: the empty upgrade file of a first version and a few defensive branches.
* **Behat:** three features, 13 scenarios: the forum bar (the share of the class, how it follows the messages, the minimum class size, the note to the teacher, a marker in the wrong activity and a teacher who types the marker in a new forum), the course map and the quiz rules (including a change made later by the teacher).
* **Cases that matter:** a name like `A & B > 3` in a section under both values of `formatstringstriptags`; the cache refreshed only by the event that changes the count (each event was checked by removing its observer); a student with an extension of time who sees theirs while a colleague sees the default; progress that counts the activities inside a subsection.
* **Queries:** a baseline of the number of queries of each test, which would flag a loop that queries once for each student or section. It flagged nothing.
