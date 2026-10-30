# Architektur und Wartung

## Modulstruktur

- `library.json`: Bibliotheks-GUID und Versionsmetadaten.
- `BayrolDirectMQTT/module.json`: Geräte-Modul, kompatibler WS-Parent, API-Prefix `BAYMQ`.
- `BayrolDirectMQTT/module.php`: Protokoll, Parser, Zustandsverwaltung und HTMLBox-Visu.
- `BayrolDirectMQTT/form.json`: vier logisch gegliederte Einstellungskategorien.

## Datenfluss

`BAYROL Cloud (wss://www.bayrol-poolaccess.de:8083)` → `nativer Symcon WS Client (binär)` → `BayrolDirectMQTT (MQTT 3.1.1)` → `Symcon Variablen` → `HTMLBox`.

Der WS Client wird über `ConnectParent()` verknüpft. `GatewaySetup` prüft anschließend die Konfiguration in wiederkehrenden Versuchen, bis ein geeigneter Parent verfügbar ist. Absichtliche Schutzmaßnahme: Eine bereits konfigurierte fremde URL wird nie überschrieben.

Die MQTT-Anmeldung verwendet Geräte-Token als Benutzername und das Literal `*` als Passwort. Topics werden aus validierter Seriennummer gebaut. Keine Benutzereingaben werden als beliebige MQTT-PUBLISH-Befehle verwendet.

## Kompatibilität und Migration

GUIDs und Variablen-Idents dürfen bei Minor-/Patch-Releases nicht geändert werden. Historische Diagnose-Identifikatoren verbleiben aus Updategründen erhalten, können verborgen werden. Das separate alte HTML-Diagnosemodul gehört nicht in das Repository.

## Review-Checkliste

- [ ] `php -l BayrolDirectMQTT/module.php`
- [ ] JSON-Dateien parsebar; GUIDs konsistent
- [ ] Neuinstallation: WS-Parent und MQTT-Subprotokoll initialisiert
- [ ] Update: vorhandene Instanz/Variablen bleiben erhalten
- [ ] CONNACK/SUBACK/GET/Publish/Ping im Symcon-Debug testen
- [ ] Keine Geheimnisse, Token oder persönliche Seriennummern im Git-Repo
- [ ] Visu in Symcon Desktop/Mobil jeweils Hell und Dunkel prüfen
- [ ] Symcon Modul Validator ausführen, bevor im Store veröffentlicht wird
