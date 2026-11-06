# Responsive HTMLBox – Release 1.0.1

Stand: 07.11.2026. Basis: bereitgestelltes GitHub-Release 1.0.0.

## Änderungen

- Messwertraster mit `auto-fit` und `minmax(min(100%,190px),1fr)`: je nach verfügbarer Breite eine, zwei oder drei Spalten. Maximalbreite von 1000 auf 1440 px erweitert.
- Fluide Abstände und Schriftgrößen mit `clamp`; kein Verkleinern auf 9-px-Beschriftungen.
- Kopfzeile, Verbindungsanzeige, Gerätebereich und Einheiten dürfen umbrechen.
- Lange Inhalte können umbrechen, Grid-/Flex-Kinder haben keine erzwungene Mindestbreite. Kein pauschales Verstecken horizontaler Überläufe.
- Detailzeilen bis 360 px einspaltig; ab 1000 px angepasste Spaltenproportionen. Tastaturfokus am nativen Summary sichtbar, Mindesthöhe 44 px.
- Themes, Farben, Icons, externes Gerätebild und natives `<details>` beibehalten. Keine JavaScript-Abhängigkeit im Dashboard.
- Modul-GUIDs, Variablen, MQTT, Statusbewertung und HTML-Erzeugung unverändert. Version 1.0.1 / Build 101 macht das Update erkennbar.

## Ausgeführte Prüfungen

| Prüfung | Ergebnis |
| --- | --- |
| JSON-Dateien aus dem Paket parsen | bestanden |
| Originalvergleich von module.php vor und nach dem CSS-Block | bytegleich; nur CSS geändert |
| Chrome Headless: Light und Dark | bestanden |
| Breiten 160, 200, 240, 280, 320, 360, 390, 480, 600, 768, 1024, 1440, 1920 px | bestanden |
| CSS-Zoom 100 % / 200 %, Details geschlossen / geöffnet | bestanden |
| Lange Rohcodes und Alarme, maximale Messwerte, fehlendes Gerätebild | bestanden |
| Dokument- und sichtbare Elementüberläufe | 104 Kombinationen, 0 Fehler |
| Release-ZIP: CRC und enthaltene Dateien | bestanden |

`tests/results.json` enthält das Browserergebnis. Die HTML-Testfixture verwendet das tatsächliche CSS des Moduls und nachgebildetes Dashboard-Markup, keine ausgeführte Symcon-Instanz. Die Doppel-Skalierung verwendet CSS `zoom`, nicht die native Browser-Zoomfunktion. Das offene Details-Attribut wird für den Test gesetzt; Interaktionen und Persistenz nach Dashboard-Aktualisierungen wurden nicht getestet.

## Reproduzieren

1. `python3 tests/responsive.py` im Repository ausführen; optional den Pfad zur ursprünglichen 1.0.0-ZIP als Argument übergeben, um den PHP-Vergleich zu wiederholen.
2. Die erzeugte `tests/layout.html` in Chrome öffnen. Das Ergebnis erscheint oben: `total: 104`, `failed: []`.
3. PHP-/JSON-Prüfung zusätzlich über den bestehenden GitHub-Actions-Workflow ausführen.

## Grenzen und Abnahme in Symcon

Lokal ist kein PHP-Interpreter verfügbar; daher wurde kein neuer PHP-Syntaxlauf ausgeführt. Der unveränderte PHP-Code außerhalb des CSS-Nowdocs wurde gegen das Original geprüft. Kein Test an einer laufenden IP-Symcon-Installation oder BAYROL-Cloud, kein Safari-/Firefox- oder physischer Gerätetest. Das externe Produktbild wurde nicht heruntergeladen.

In Symcon beide Themes in schmaler und breiter HTMLBox öffnen, Werte und Status prüfen, Details per Maus/Touch und Tastatur öffnen und schließen. Die Einbettung muss die Breite des HTMLBox-Dokuments an die Kachel weitergeben. Die Höhe bleibt inhaltabhängig: bei niedrigen Kacheln vertikal scrollen bzw. die Kachelhöhe vergrößern. Geöffnete Details können durch die bestehende Dashboard-Neugenerierung zurückgesetzt werden; dieses Verhalten wurde nicht geändert.
