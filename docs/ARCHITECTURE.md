# Architektur und Wartung

## Modulstruktur

- `library.json`: Bibliotheks-GUID und Versionsmetadaten.
- `BayrolDirectMQTT/module.json`: Geräte-Modul, kompatibler WS-Parent, API-Prefix `BAYMQ`.
- `BayrolDirectMQTT/module.php`: Protokoll, Parser, Zustandsverwaltung und HTMLBox-Visu.
- `BayrolDirectMQTT/form.json`: vier logisch gegliederte Einstellungskategorien.

## Datenfluss

`BAYROL Cloud (wss://www.bayrol-poolaccess.de:8083)` → `nativer Symcon WS Client (binär)` → `BayrolDirectMQTT (MQTT 3.1.1)` → `Symcon Variablen` → `HTMLBox`.

Seit 1.0.2 fordert `GetCompatibleParents()` einen dedizierten nativen WS Client. URL, MQTT-Subprotokoll, Binärmodus und Zertifikatsprüfung sind Pflichtkonfiguration. `Active` ist bei Neuanlage vorgeschlagen und im Dialog änderbar. Die Management-Konsole übernimmt die Auswahl und Anlage. Die Schnittstelle erfordert Symcon 8.2.

Die MQTT-Anmeldung verwendet Geräte-Token als Benutzername und das Literal `*` als Passwort. Topics werden aus validierter Seriennummer gebaut. Keine Benutzereingaben werden als beliebige MQTT-PUBLISH-Befehle verwendet.

## Kompatibilität und Migration

Die Modul-GUID und die Idents der regulären Variablen bleiben beim Update erhalten. `MaintainVariable()` verwaltet die 20 optionalen Diagnosevariablen in `ApplyChanges()`. Bei eingeschalteter Diagnose bleiben vorhandene Variablen mit ihren IDs und ihrer Sichtbarkeit erhalten. Bei ausgeschalteter Diagnose werden sie entfernt. MQTT-Protokollzustand und Messwertgültigkeit sind unabhängig von den optionalen Variablen.

## Review-Checkliste

- [ ] `php -l BayrolDirectMQTT/module.php`
- [ ] JSON-Dateien parsebar; GUIDs konsistent
- [ ] Neuinstallation in Symcon 8.2: Der Dialog schlägt den erwarteten WS Client vor und legt ihn mit URL und MQTT-Subprotokoll an
- [ ] Update: Vorhandene Instanz und Messwertvariablen bleiben erhalten
- [ ] Diagnose anlegen, eine Variable manuell ausblenden und erneut übernehmen. Danach Diagnose abschalten und prüfen, dass die optionalen Variablen entfernt sind.
- [ ] CONNACK/SUBACK/GET/Publish/Ping im Symcon-Debug testen
- [ ] Keine Geheimnisse, Token oder persönliche Seriennummern im Git-Repo
- [ ] Visu in Symcon Desktop/Mobil jeweils Hell und Dunkel prüfen
- [ ] Symcon Modul Validator ausführen, bevor im Store veröffentlicht wird
