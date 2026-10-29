# 🔐 Security & Privacy

* **The target comes from the place, never from the marker.** The course, the forum and the quiz are taken from the context of the text being shown, and the widget refuses when the viewer cannot see that forum or quiz. The text of a marker cannot point at another course or activity.
* **Options by allow-list:** an option name is letters only, a value without quotes holds only letters, digits, underscore and hyphen, the options are at most 200 characters, and a value that is not one of the expected ones falls back to the default.
* **Output is escaped:** the templates print with the escaping tag, never the raw one, and a section name is turned into plain text before it reaches them.
* **Nobody else's data is shown.** The forum bar shows only counts of the class, respects separate groups, and is hidden from students when the class is smaller than the minimum, so no one can be told apart. The map and the quiz rules read only what belongs to the person who is looking: their own completion and their own extensions.
* **Privacy:** the filter creates no table and saves no preference. Its only cache holds aggregated counts of a forum, and it declares that it stores no personal data (Privacy API).
* **No external service, no AI, no library of third parties.**
* **Audited:** the plugin was audited on 29 October 2026 and had no security finding; the two code-quality findings (an activity inside a subsection that the progress map left out, and a test that did not fail without its observer) were fixed.
