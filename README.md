# Bayrol PoolAccess für IP-Symcon — Version 1.0.2

Eigenständiges, **lesendes** IP-Symcon-Modul für Bayrol PoolAccess (getesteter Anwendungsfall: Automatic Cl-pH). **Ohne Home Assistant, Docker und separaten MQTT-Broker.** MQTT wird über einen nativen IP-Symcon-WebSocket-Client mit der Bayrol-Cloud verbunden.

> Release-Kennzeichnung 1.0.2 bezeichnet die bereinigte, dokumentierte Distribution. Eine vollständige Prüfung auf weiteren Geräten oder auf jeder Symcon-Version wurde nicht durchgeführt. Vor produktivem Einsatz Messwerte mit der Bayrol-App gegenprüfen. Keine Dosier-/Steuerbefehle.

## Schnellstart

1. Dieses Repository in **GitLab oder GitHub** bereitstellen. `library.json` muss im Repository-Stammverzeichnis liegen.
2. In der IP-Symcon Verwaltungskonsole unter **Kerninstanzen → Modules** die Git-Clone-URL hinzufügen (`https://github.com/MCVnet/Symcon_Bayrol-Poolaccess/)`). Bei privaten Repositories muss Symcon auf Git zugreifen können.
3. Über **Instanz hinzufügen** den Typ **Bayrol Direct MQTT** erstellen.
4. Der native **WS Client** wird als Parent verbunden und automatisch für Bayrol eingerichtet, auch wenn die Parent-Verknüpfung erst kurz nach der Instanzerstellung verfügbar wird. Ein interner Einrichtungstimer überprüft den Client erneut. Falls Symcon noch keinen Parent verbunden hat, unter **Gateway ändern** einen **WS Client** anlegen bzw. zuweisen und die Bayrol-Instanz erneut übernehmen.
5. Der Client erhält automatisch URL `wss://www.bayrol-poolaccess.de:8083`, Header `Sec-WebSocket-Protocol: mqtt`, Binärmodus, Zertifikatsprüfung und Aktivierung. Ein zuvor als **MQTT Test** bezeichneter Client wird in **Bayrol PoolAccess – WebSocket** umbenannt. Eine abweichende, bereits konfigurierte URL wird aus Sicherheitsgründen **nicht** überschrieben.
6. Im Modul Seriennummer und Gerätetoken hinterlegen, **Dauerverbindung aktivieren**, **Messwerte aktiv anfordern** und Intervall 300 Sekunden einstellen. **Übernehmen**.
7. **Verbindung prüfen**, danach **Messwerte jetzt abrufen**. Die Variablen `pH-Wert`, `Redox (mV)`, `Wassertemperatur (°C)` und `Poolübersicht` kontrollieren.

## Geräte-Seriennummer und Token

**Seriennummer:** Am Typenschild bzw. am Automatic Cl-pH in den Menüs rund um **Webportal Verbindung / App & Webportal Verbindung** nachsehen; mit dem im Bayrol-PoolAccess-Webportal registrierten Gerät abgleichen. Das Beispiel `22ACL2-01813` ist kein universeller Wert.

**Gerätetoken:** Der 32-stellige Geräte-Authentifizierungstoken ist **nicht** das Webportal-Passwort, die Controller-ID oder die sechsstellige Registrierungs-PIN. Wenn er bereits in deiner selbst betriebenen Bayrol-MQTT-Bridge / deren Add-on-Konfiguration hinterlegt ist, kann der dortige Wert in die lokale Symcon-Konfiguration übernommen werden. Im Bayrol-Webportal ist seine Anzeige je nach Firmware/Portalversion nicht garantiert. Für die eigene Anlage kann er gegebenenfalls über die Browser-Entwicklerwerkzeuge im autorisierten PoolAccess-Gerätekontext ermittelt werden, wenn eine entsprechende Geräte-/App-Konfiguration diesen Wert tatsächlich bereitstellt. **Es gibt keinen für alle Versionen bestätigten Menüpfad zur Tokenanzeige.** Ist er nirgends zugänglich, Gerätedokumentation bzw. Bayrol-Support konsultieren; keine Tokenwerte erraten.

**Sicherheit:** Den Token wie ein Passwort behandeln, nicht in Screenshots, Tickets, Quellcode, öffentliche Protokolle oder Git-Commits aufnehmen. Screenshots vor Weitergabe anonymisieren. Wenn ein Token versehentlich veröffentlicht wurde, beim Hersteller nach Rotation/Widerruf fragen.

## Instanzkonfiguration

| Abschnitt | Eigenschaft | Bedeutung / Empfehlung |
|---|---|---|
| 1 Gerätezugang | Seriennummer | Tatsächliche Gerätekennung, ohne Wildcards |
| 1 Gerätezugang | Gerätetoken | 32-stelliger Geräte-Token, vertraulich |
| 2 Verbindung | Dauerverbindung aktivieren | Für kontinuierliche Datenupdates einschalten |
| 2 Verbindung | Messwerte aktiv anfordern | Löst ausschließlich bekannte GET-Abfragen über MQTT aus |
| 2 Verbindung | Abfrageintervall | Standard 300 s, gültig 60–3600 s; nicht unnötig kurz wählen |
| 2 Verbindung | Veraltete Messwerte nach | Standard 900 s; reine Anzeige-/Qualitätskontrolle |
| 3 Visualisierung | Visu-Design | Light & Clean oder Dark & Premium |
| 4 Diagnose | Diagnosevariablen anzeigen | Bei Fehlersuche aktivieren; sonst ausgeblendet |

**Technische Defaults:** Das Subscription-Topic `d02/<Seriennummer>/v/#` wird intern gebildet. Die Bayrol-MQTT-Bridge nutzt Token als MQTT-Benutzername, Passwort `*`, Keepalive 60 s, persistente Session und lesende `/g/`-Anfragen mit QoS 0 und leerer Payload. Diese Felder müssen nicht manuell editiert werden.

## Messwerte und Datenmodell

| Variable | Antwortcode | Format | Hinweise |
|---|---|---|---|
| pH-Wert | `4.78` | Rohwert / 10 | Nur bei empfangenem Wert gültig |
| Redox (mV) | `4.82` | mV | Nur bei empfangenem Wert gültig |
| Wassertemperatur | `4.98` | Rohwert / 10, °C | Sensor kann optional sein |
| pH-Dosierleistung | `4.89` | Prozent | 0 kann tatsächlicher Wert sein |
| Redox-Dosierleistung | `4.90` | Prozent | 0 kann tatsächlicher Wert sein |
| Gerätestatus | `1` | Rohcode | Nicht ohne Codeliste interpretieren |
| Alarme | `10` | JSON/Text | Warnungen separat kontrollieren |
| pH-/Redox-Regelung, Kanister, Durchfluss | gerätespezifisch | Rohcode | Nicht automatisch als Ein/Aus oder Voll/Leer deuten |
| Firmware | `6.15` | Text | — |

**Wichtig:** Ein anfänglicher Nullwert bedeutet nicht automatisch einen echten Messwert. Verfügbarkeit und Zeitstempel beachten.

## Bedienung der Visu

Die Variable `Poolübersicht` zeigt pH, Redox und Temperatur. Der untere Bereich **Anlagendetails** ist standardmäßig geschlossen und kann in der HTMLBox aufgeklappt werden. Die Visu hat zwei Themes, ändert aber keine Messwertlogik. Das Gerätebild wird extern über Bayrol geladen; ohne Internet kann es fehlen.

## Update einer bestehenden Installation

1. Sicherung der Symcon-Konfiguration anlegen.
2. Repository-Dateien austauschen und committen (z. B. `Release 1.0.0: Direct MQTT`).
3. Unter **Kerninstanzen → Modules** die Bibliothek aktualisieren.
4. Vorhandene Bayrol-Instanz **nicht löschen**; sie nutzt dieselbe Modul-GUID, denselben Prefix und dieselben Variablen-Identifikatoren.
5. Instanz öffnen, **Übernehmen**, WS Client bei Bedarf neu verbinden, Werte kontrollieren.

**Wegfall des alten HTML-Moduls:** `BayrolAutomaticCl` ist im Release nicht mehr vorhanden. Für **bereits angelegte HTML-Instanzen** gilt: Vor dem Upgrade deren Bedarf prüfen und Daten archivieren; verwaiste Instanzen anschließend manuell und bewusst entfernen. **Bestehende MQTT-Instanzen** bleiben erhalten.

## Fehlerbehebung

| Problem | Prüfung |
|---|---|
| WS-Client nicht verbunden | URL, Zertifikatsprüfung, Header `Sec-WebSocket-Protocol: mqtt`, aktivierte Schnittstelle |
| CONNECT abgelehnt | Seriennummer/Token und MQTT-Sitzung; keine Zugangsdaten ins Log posten |
| SUBACK, aber keine Messwerte | Kontrollieren, ob automatisch `d02/<Seriennummer>/v/#` abonniert wurde; Empfangstopic darf nicht bei `/v/` enden |
| Nur Statuscode `17.3` | GET-Abfrage ausführen und unter Diagnose empfangene PUBLISH-Pakete prüfen |
| Werte bleiben 0 | Empfangszeitstempel und Flags `pH-Wert empfangen` usw. prüfen |
| Bild fehlt | Zugriff auf den externen Bayrol-Bildhost prüfen |
| Werte veraltet | MQTT-Verbindung und Bayrol-Cloud/Anlage kontrollieren |

## Entwicklung und Kompatibilität

- Modultyp: `Bayrol Direct MQTT` (`BayrolDirectMQTT`-PHP-Klasse), Datenfluss über nativen WebSocket-Client.
- Modul-ID, Datenfluss-GUIDs, Variablen-Identifikatoren und API-Präfix `BAYMQ` sind aus Kompatibilitätsgründen unverändert.
- MQTT-Binärframes werden vom Modul verpackt und über die WS-Parent-Instanz übertragen.
- Ausschließlich bekannte Leseanfragen; keine Schreibkommandos, keine Steuerung von Dosierpumpen.
- Technische Quelle: https://github.com/tdenolle/bayrol-poolaccess-mqtt (MIT). Der Originalcode wird nicht als eigene Python-Komponente mitgeliefert.
- Release ist gegen PHP-Syntax und Paket-Metadaten geprüft, nicht als allgemeine Zertifizierung für beliebige Symcon-/Bayrol-Versionen zu verstehen.

## Installation über Module Store

Die Git-Installation funktioniert auch ohne Store-Freigabe. Eine tatsächliche **Veröffentlichung im Symcon Module Store** muss separat über den offiziellen Einreichungsprozess erfolgen; dieses ZIP behauptet keine bereits erfolgte Freigabe.

## WebSocket-Autokonfiguration (ab 1.0.1)

Beim Speichern der Bayrol-Instanz wird der zugewiesene native WS Client überprüft. Ist die URL leer, werden Bayrol-URL, MQTT-Subprotokoll-Header, binärer Transport, TLS-Zertifikatsprüfung und `Active=true` gesetzt und anschließend die Parent-Konfiguration übernommen. Bei bereits passender Bayrol-URL werden unvollständige Einstellungen ergänzt. Ein Client mit einer anderen URL wird nicht verändert. Ein vorhandener Parent mit dem Namen `MQTT Test` wird in `Bayrol PoolAccess – WebSocket` umbenannt. Die automatische Konfiguration ändert keine Bayrol-Zugangstoken, Variablenidentifikatoren oder Modul-IDs.

**Kontrolle bei einer Neuanlage:** In der Bayrol-Instanz unter **Gateway ändern** muss ein nativer WS Client verbunden sein. Falls Symcon keinen anlegt, einmal einen WS Client zuweisen und die Bayrol-Instanz mit **Übernehmen** speichern. Anschließend die fünf Werte URL, Header, Type, VerifyCertificate und Active im WS Client kontrollieren.

## Automatische Einrichtung des WebSocket-Clients (ab 1.0.2)

Bei einer Neuanlage kann Symcon die `ApplyChanges()`-Routine ausführen, bevor die per `ConnectParent()` angelegte WS-Client-Verbindung in `ConnectionID` sichtbar wird. Ab 1.0.2 startet deshalb ein separater `GatewaySetup`-Timer (alle zwei Sekunden bis zum Erfolg), der die Parent-Instanz prüft und nur deren Standard-/Bayrol-Konfiguration initialisiert. Nach erfolgreicher Einrichtung stoppt sich der Timer.

Sollte der WS Client nach der Installation leer bleiben, in der Bayrol-Instanz **Gateway ändern** prüfen, ob der richtige **WS Client** zugewiesen ist. Anschließend in der Bayrol-Instanz **Übernehmen** oder per Symcon-PHP `BAYMQ_ConfigureGateway(<InstanzID>);` ausführen. Einen bereits auf eine andere URL konfigurierten WS-Client überschreibt das Modul absichtlich nicht.
