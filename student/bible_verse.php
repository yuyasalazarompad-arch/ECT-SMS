<?php
/**
 * Student Bible Verse Component
 *
 * Uses:
 * - bible_verses
 * - bible_verse_settings
 *
 * Automatic mode: selects a verse based on the Philippine calendar date.
 * Manual mode: displays the verse selected by the administrator.
 */

if (!isset($conn) || !($conn instanceof mysqli)) {
    return;
}

if (!function_exists('getStudentBibleVerse')) {
    function getStudentBibleVerse(mysqli $conn): ?array
    {
        // Read the current Bible verse display settings.
        $settingsSql = "
            SELECT display_mode, manual_verse_id
            FROM bible_verse_settings
            WHERE setting_id = 1
            LIMIT 1
        ";

        $settingsResult = $conn->query($settingsSql);

        if (!$settingsResult) {
            return null;
        }

        $settings = $settingsResult->fetch_assoc();

        if (!$settings) {
            return null;
        }

        // MANUAL MODE: display the administrator's selected verse.
        if (
            $settings['display_mode'] === 'manual' &&
            !empty($settings['manual_verse_id'])
        ) {
            $stmt = $conn->prepare("
                SELECT verse_reference, verse_text
                FROM bible_verses
                WHERE verse_id = ?
                  AND is_active = 1
                LIMIT 1
            ");

            if (!$stmt) {
                return null;
            }

            $verseId = (int) $settings['manual_verse_id'];
            $stmt->bind_param("i", $verseId);
            $stmt->execute();

            $result = $stmt->get_result();
            $verse = $result->fetch_assoc();

            $stmt->close();

            if ($verse) {
                return $verse;
            }

            return null;
        }

        // AUTOMATIC MODE:
        // The verse changes according to the Philippine calendar date.
        $date = new DateTimeImmutable(
            'now',
            new DateTimeZone('Asia/Manila')
        );

        $dayNumber = (int) $date->format('z');

        $stmt = $conn->prepare("
            SELECT verse_reference, verse_text
            FROM bible_verses
            WHERE is_active = 1
            ORDER BY verse_id
        ");

        if (!$stmt) {
            return null;
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $verses = [];

        while ($row = $result->fetch_assoc()) {
            $verses[] = $row;
        }

        $stmt->close();

        if (empty($verses)) {
            return null;
        }

        // Rotate through the active verses, one per Philippine calendar day.
        $index = $dayNumber % count($verses);

        return $verses[$index];
    }
}

$studentBibleVerse = getStudentBibleVerse($conn);
?>

<?php if (!empty($studentBibleVerse)): ?>
    <section class="student-bible-verse">
        <div class="student-bible-verse__icon" aria-hidden="true">
            ✦
        </div>

        <div class="student-bible-verse__content">
            <p class="student-bible-verse__eyebrow">
                DAILY WORD
            </p>

            <h2>Bible Verse</h2>

            <blockquote>
                “<?= htmlspecialchars(
                    $studentBibleVerse['verse_text'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>”
            </blockquote>

            <p class="student-bible-verse__reference">
                <?= htmlspecialchars(
                    $studentBibleVerse['verse_reference'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
                <span>• King James Version</span>
            </p>
        </div>
    </section>

    <style>
        .student-bible-verse {
            display: flex;
            align-items: flex-start;
            gap: 18px;
            padding: 24px;
            margin: 22px 0;
            background: linear-gradient(135deg, #EAF3FA, #F8FBFE);
            border: 1px solid #D7E6F3;
            border-left: 5px solid #DC2626;
            border-radius: 16px;
            color: #12355B;
        }

        .student-bible-verse__icon {
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 44px;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: #12355B;
            color: #FFFFFF;
            font-size: 23px;
        }

        .student-bible-verse__content {
            min-width: 0;
        }

        .student-bible-verse__eyebrow {
            margin: 0 0 5px;
            color: #1E5A96;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.8px;
        }

        .student-bible-verse h2 {
            margin: 0 0 12px;
            color: #12355B;
            font-size: 20px;
            font-weight: 750;
        }

        .student-bible-verse blockquote {
            margin: 0;
            padding: 0;
            color: #354B61;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 16px;
            font-style: italic;
            line-height: 1.8;
        }

        .student-bible-verse__reference {
            margin: 14px 0 0;
            color: #12355B;
            font-size: 13px;
            font-weight: 750;
        }

        .student-bible-verse__reference span {
            color: #687B8D;
            font-weight: 400;
        }

        @media (max-width: 480px) {
            .student-bible-verse {
                gap: 12px;
                padding: 18px;
            }

            .student-bible-verse__icon {
                flex-basis: 36px;
                width: 36px;
                height: 36px;
            }
        }
    </style>
<?php endif; ?>