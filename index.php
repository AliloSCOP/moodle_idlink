<?php
require(__DIR__ . '/../../config.php');

$idnumber = optional_param('idnum',null, PARAM_RAW_TRIMMED); // idnumber du module (course_modules.idnumber).
$courseidnum = optional_param('courseidnum', null, PARAM_RAW_TRIMMED); // idnumber du cours.
$showParentSection = optional_param('showparentsection', 0, PARAM_BOOL); // afficher la section parente.


// Si un courseidnum est fourni : on cherche uniquement dans ce cours via l'API rapide.
if ($courseidnum) {
    // Recherche du cours par idnumber.
    global $DB;
    $course = $DB->get_record('course', ['idnumber' => $courseidnum], '*', MUST_EXIST);
    require_login($course);

    if($idnumber === '' || $idnumber === null) {
        // Si l'idnumber est vide, on redirige vers le cours.
        redirect(new moodle_url('/course/view.php', ['id' => $course->id]));
    }

    $modinfo = get_fast_modinfo($course);
    foreach ($modinfo->get_cms() as $cm) {
        if ($cm->idnumber === $idnumber && $cm->uservisible) {
            // OK : l'utilisateur a le droit de voir, on redirige.
            if ($showParentSection && $cm->sectionnum > 0) {
                // Rediriger vers la section parente du module
                redirect(new moodle_url('/course/view.php', ['id' => $course->id, 'section' => $cm->sectionnum]));
            } else {
                // Rediriger directement vers le module
                redirect(new moodle_url('/mod/' . $cm->modname . '/view.php', ['id' => $cm->id]));
            }
        }
    }

    // Rien de visible/valide trouvé.
    print_error('activitynotfound', 'error', '', s($idnumber));
    exit;
}

// Sinon (pas de courseidnum), on fait une recherche DB minimale puis on vérifie l’accès avec get_fast_modinfo.
global $DB, $USER;

// On récupère toutes les CM partageant cet idnumber (il peut y en avoir plusieurs).
$sql = "SELECT cm.id, cm.course
          FROM {course_modules} cm
         WHERE cm.idnumber = :idnumber";
$candidates = $DB->get_records_sql($sql, ['idnumber' => $idnumber]);

if (!$candidates) {
    print_error('activitynotfound', 'error', '', s($idnumber));
    exit;
}

// On filtre par accès utilisateur (uservisible).
$matches = [];
foreach ($candidates as $rec) {
    $course = get_course($rec->course);
    require_login($course); // établit le contexte et la sess, lève exception si pas d’accès au cours.

    $modinfo = get_fast_modinfo($course, $USER->id);
    if (!isset($modinfo->cms[$rec->id])) {
        continue;
    }
    $cm = $modinfo->cms[$rec->id];
    if ($cm->uservisible) {
        $matches[] = $cm;
    }
}

$count = count($matches);
if ($count === 0) {
    print_error('nopermissions', 'error');
    exit;
} elseif ($count === 1) {
    $cm = $matches[0];
    if ($showParentSection && $cm->sectionnum > 0) {
        // Rediriger vers la section parente du module
        $course = get_course($cm->course);
        redirect(new moodle_url('/course/view.php', ['id' => $course->id, 'section' => $cm->sectionnum]));
    } else {
        // Rediriger directement vers le module
        redirect(new moodle_url('/mod/' . $cm->modname . '/view.php', ['id' => $cm->id]));
    }
    exit;
}

// Plusieurs correspondances : proposer un petit choix.
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/idlink/index.php', ['idnum' => $idnumber]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Sélection d’activité');
$PAGE->set_heading('Plusieurs activités trouvées');

echo $OUTPUT->header();
echo $OUTPUT->heading('Plusieurs activités correspondent à l’idnumber : ' . s($idnumber));

$list = [];
foreach ($matches as $cm) {
    $course = get_course($cm->course);
    if ($showParentSection && $cm->sectionnum > 0) {
        // Lien vers la section parente du module
        $url = new moodle_url('/course/view.php', ['id' => $course->id, 'section' => $cm->sectionnum]);
    } else {
        // Lien direct vers le module
        $url = new moodle_url('/mod/' . $cm->modname . '/view.php', ['id' => $cm->id]);
    }
    $list[] = html_writer::link($url, format_string($course->fullname) . ' — ' . format_string($cm->name));
}
echo html_writer::alist($list);

echo $OUTPUT->footer();
