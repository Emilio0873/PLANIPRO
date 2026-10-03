<?php
require_once __DIR__ . '/../../includes/init.php';
$user = require_role('entreprise');
$pageTitle = 'Calendrier';
$eventsUrl = url('actions/calendrier_events.php');
$extraHead = '';
$extraScripts = '<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.15/locales-all.global.min.js"></script>
<script src="' . e(asset('js/calendar.js')) . '?v=4"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
  if (window.PlaniproCalendar) {
    window.PlaniproCalendar.init(document.getElementById("calendar"), ' . json_encode($eventsUrl) . ');
  }
});
</script>';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="card planning-card planning-card--bare">
    <div id="calendar" class="calendar-host"></div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
