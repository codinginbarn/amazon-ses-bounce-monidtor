<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Amazon SES Feedback Monitor</title>
<style>
  body {
    font-family: sans-serif;
    background: #111;
    color: #eee;
    padding: 2em;
    line-height: 1.6;
    font-size: 18px;
  }
  h1, h2 {
    color: #0ff;
    margin: 0 0 0.75em 0;
  }
  h1 { margin-bottom: 1em; }
  section { margin-bottom: 2em; }
  table {
    width: 100%;
    border-collapse: collapse;
    background: #222;
    font-size: 1.05em;
    margin-bottom: 1.5em;
  }
  th, td {
    padding: 0.75em;
    border: 1px solid #555;
    text-align: left;
  }
  th {
    background: #333;
    color: #0ff;
  }
  tr:nth-child(even) {
    background: #1a1a1a;
  }
  .empty, .error {
    padding: 1em;
    background: #222;
    border: 1px solid #555;
    font-size: 1.1em;
    color: #ccc;
  }
  .error { color: #f88; border-color: #a55; }
</style>
</head>
<body>
<h1>Amazon SES Feedback Monitor</h1>

<section aria-labelledby="bounces-title">
  <h2 id="bounces-title">Bounces</h2>
  <div id="bounces" class="empty">Loading...</div>
</section>

<section aria-labelledby="complaints-title">
  <h2 id="complaints-title">Complaints</h2>
  <div id="complaints" class="empty">Loading...</div>
</section>

<script>
async function loadLog(file, containerId, headers, emptyMsg) {
  try {
    const res = await fetch(file + '?t=' + Date.now(), { cache: 'no-store' });
    if (!res.ok) {
      document.getElementById(containerId).innerHTML =
        `<div class="error">Error loading ${file} (${res.status})</div>`;
      return;
    }
    const text = await res.text();

    if (!text.trim()) {
      document.getElementById(containerId).innerHTML =
        `<div class="empty">${emptyMsg}</div>`;
      return;
    }

    const lines = text.trim().split('\n');
    let html = `<table><thead><tr>`;
    for (const h of headers) html += `<th>${h}</th>`;
    html += `</tr></thead><tbody>`;

    for (const line of lines) {
      const parts = line.split('|').map(p => p.trim());
      html += `<tr>`;
      for (let i = 0; i < headers.length; i++) {
        const cell = (parts[i] && parts[i].length) ? parts[i] : 'n/a';
        html += `<td>${cell}</td>`;
      }
      html += `</tr>`;
    }

    html += `</tbody></table>`;
    document.getElementById(containerId).innerHTML = html;
  } catch (e) {
    document.getElementById(containerId).innerHTML =
      `<div class="error">Error loading ${file}</div>`;
  }
}

function refreshAll() {
  // Must match the 11 fields written to bounces.log
  loadLog(
    'bounces.log',
    'bounces',
    [
      'Timestamp',
      'Bounced Email',
      'Subject',
      'Source',
      'Destinations',
      'Bounce Type',
      'SubType',
      'Status',
      'Action',
      'Diagnostic Code',
      'Reporting MTA'
    ],
    'No bounces yet'
  );

  // Must match the 9 fields written to complaints.log
  loadLog(
    'complaints.log',
    'complaints',
    [
      'Timestamp',
      'Complained Email',
      'Subject',
      'Source',
      'Destinations',
      'Feedback Type',
      'SubType',
      'User Agent',
      'Arrival Date'
    ],
    'No complaints yet'
  );
}

refreshAll();
setInterval(refreshAll, 90000);
</script>
</body>
</html>
