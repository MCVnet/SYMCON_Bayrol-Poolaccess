# Changelog

## 1.0.2 (2026-11-09)

- Die Management-Konsole wählt oder erstellt den WS Client anhand von `GetCompatibleParents()`. Mindestversion ist IP-Symcon 8.2.
- Die 20 optionalen Diagnosevariablen werden mit `MaintainVariable()` angelegt und beim Abschalten der Diagnose gelöscht. Die Sichtbarkeit vorhandener Variablen lässt sich im Objektbaum einstellen.
- Regressionstest für die Parent-Anforderung, den Diagnosewechsel und den simulierten MQTT-Ablauf ergänzt.

## 1.0.1 (2026-11-07)

- HTMLBox für schmale Symcon-Kacheln, Smartphones, Tablets und Desktop überarbeitet.
- Flexibles Messwertraster, fluide Größen und Abstände; umbrechende Kopfzeile und Statusanzeige.
- Details unter 361 px einspaltig; lange Rohcodes und Alarme umbrechen ohne Abschneiden.
- Light & Clean, Dark & Premium, Icons und native Details erhalten; MQTT unverändert.
- Testumfang und Grenzen: `docs/RESPONSIVE-TESTS.md`.

## 1.0.0 (GitHub Release, 2026-10-30)

- GitHub-taugliche SDK-Struktur, konsistente Dokumentation, Installationsanleitung und Architekturhinweise.
- MQTT-Funktionsumfang und Kompatibilitäts-IDs aus dem zuletzt erstellten 1.0.2-Entwicklungsstand übernommen.
- Bereinigte WS-Client-Konfiguration, Statusprüfung und diagnostische Fehlerbehandlung.
- Zwei Themes, aufklappbarer Detailbereich, HTML-Diagnosemodul entfernt.
- Versionsmetadaten gemäß angefragter **1.0**-Hauptversion auf **1.0.0** gesetzt.

**Hinweis:** Dies ist eine neue GitHub-Release-Linie aus dem Entwicklungsstand 1.0.2, keine Aussage, dass die ältere 1.0.2-Neuinstallation bereits real getestet wurde.
