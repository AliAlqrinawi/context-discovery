<!-- cell-16 -->
# Code review · one commit

You are reviewing one commit from a Laravel application (PHP 8.x, Laravel 12). Below, after these instructions, are exactly three files, each between a `===== BEGIN <name> =====`
line and a `===== END <name> =====` line. They are the only material you may use:

| File | What it is |
|---|---|
| `change.diff` | the commit's unified diff — the change under review |
| `context-bundle.md` | excerpts from the repository, prepared automatically for this review: pieces of code the change refers to, and one-line notes where something could not be looked up |
| `context-diagnostics.txt` | the preparation's own log of what it searched for and what it settled without including anything |

You have no repository, no file access outside these three files, no ability to run code, no
web, and no prior knowledge of this project. Answer from the three files alone. If they are not
enough to be sure, say so — that is a legitimate answer.

## Answer these five questions, in this order, in exactly this format

```
Q1: YES | NO | CANNOT_TELL
Q2: <one sentence stating the defect, or ->
Q3: <path/to/File.php::member> | NONE
Q4: HIGH | MEDIUM | LOW
Q5: "<exact quotation>" | NONE_VISIBLE
```

| | Question | How to answer |
|---|---|---|
| **Q1** | Does this change contain a **correctness defect** — code that will behave wrongly at runtime, silently lose or corrupt data, expose the wrong thing, or break an existing behaviour it did not mean to break? | `YES`, `NO`, or `CANNOT_TELL` if the material genuinely does not let you decide |
| **Q2** | If YES, state the defect in **one sentence** naming the mechanism — what goes wrong, under what input or state, and where. | Free text, or `-` |
| **Q3** | What is the **single most important piece of code not shown** in these files that you would need in order to be sure of Q1? | One `path/to/File.php::member` (or a path, or a config key), or `NONE` if the material is sufficient |
| **Q4** | Your confidence in Q1. | `HIGH`, `MEDIUM` or `LOW` |
| **Q5** | Quote the **exact text** from any of the three files that you based Q1 on. | A verbatim quotation — it will be checked mechanically against the files — or `NONE_VISIBLE` |

## Rules

1. Read all of `change.diff` before the other two files, then read those in full.
2. Q2 names a mechanism, not a category. "Possible null handling issue" is not an answer;
   "`update()` passes `$request->input('description')` which is `null` when the field is
   omitted, so an omitted field clears the column" is.
3. Q3 is **one** thing, the most important. If you would want several, pick the one whose
   absence most limits your Q1.
4. Q5 must be a substring of one of the three files below, character for character. Do not paraphrase.
5. Do not propose fixes, do not comment on style, do not summarise the change. Five lines.
6. Answer once. You will not be asked to revise.

---

===== BEGIN change.diff =====
diff --git a/database/seeders/SettingSeeder.php b/database/seeders/SettingSeeder.php
index b9b23e0..85d0338 100644
--- a/database/seeders/SettingSeeder.php
+++ b/database/seeders/SettingSeeder.php
@@ -10,6 +10,8 @@ class SettingSeeder extends Seeder
     public function run(): void
     {
         $settings = [
+            ['key' => 'restaurant_name_ar', 'value' => 'أبو السيد', 'type' => 'text', 'group' => 'general', 'label_ar' => 'اسم المطعم (عربي)', 'label_en' => 'Restaurant Name (Arabic)'],
+            ['key' => 'restaurant_name_en', 'value' => 'Abou El Sid', 'type' => 'text', 'group' => 'general', 'label_ar' => 'اسم المطعم (إنجليزي)', 'label_en' => 'Restaurant Name (English)'],
             ['key' => 'whatsapp_number', 'value' => '+966551718800', 'type' => 'phone', 'group' => 'contact', 'label_ar' => 'رقم واتساب', 'label_en' => 'WhatsApp Number'],
             ['key' => 'phone_jeddah', 'value' => '0551718800', 'type' => 'phone', 'group' => 'contact', 'label_ar' => 'هاتف جدة', 'label_en' => 'Jeddah Phone'],
             ['key' => 'phone_riyadh', 'value' => '0581041912', 'type' => 'phone', 'group' => 'contact', 'label_ar' => 'هاتف الرياض', 'label_en' => 'Riyadh Phone'],
diff --git a/tests/Feature/RepositoriesTest.php b/tests/Feature/RepositoriesTest.php
index ab0f9e2..ab89f4a 100644
--- a/tests/Feature/RepositoriesTest.php
+++ b/tests/Feature/RepositoriesTest.php
@@ -114,7 +114,7 @@ public function test_setting_repository_get_all_keyed_by_key(): void
 
         $result = $repo->getAll();
 
-        $this->assertCount(17, $result);
+        $this->assertCount(19, $result);
         $this->assertTrue($result->has('whatsapp_number'));
     }
 
===== END change.diff =====

===== BEGIN context-bundle.md =====
# Context bundle

bundle_version 2 · budget 8000 / used 0 tokens

## Dropped

Nothing was dropped.
===== END context-bundle.md =====

===== BEGIN context-diagnostics.txt =====

===== END context-diagnostics.txt =====
