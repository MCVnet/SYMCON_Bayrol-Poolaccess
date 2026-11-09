# Bayrol PoolAccess – IP-Symcon

![Release](https://img.shields.io/badge/release-v1.0.3-167c9c) ![PHP lint](https://img.shields.io/badge/check-PHP%20syntax-2e9656)

**Direkte, lesende Cloud-Anbindung** einer BAYROL Automatic Cl-pH an IP-Symcon via MQTT over WebSocket. Keine externe MQTT-Bridge, kein Home Assistant und kein eigener MQTT-Broker erforderlich.

> Unabhängiges Community-Projekt, kein offizielles BAYROL-Produkt. **Keine Schreib- oder Dosiersteuerung.** Kommunikation mit dem BAYROL-Cloudserver erforderlich; dessen Schnittstelle ist nicht als stabile öffentliche API zugesichert.

## Installation über GitHub

Ab Version 1.0.2 wird **IP-Symcon 8.2 oder neuer** benötigt.

1. Den Inhalt dieses Repositorys im Stammverzeichnis des GitHub-Repositories veröffentlichen (nicht in einem zusätzlichen Ordner). `library.json` liegt direkt im Root.
2. In IP-Symcon: **Kerninstanzen → Module → Hinzufügen**. GitHub-Repository-URL angeben, z. B. `https://github.com/DEIN-ACCOUNT/DEIN-REPO.git`.
3. Nach dem Laden über **Instanz hinzufügen → Bayrol Direct MQTT** die Instanz anlegen.
4. Im Erstellungsdialog den vorgeschlagenen **WS Client** auswählen oder von der Management-Konsole anlegen lassen.
5. Im WS Client die vom Modul geforderten Verbindungsdaten prüfen. **Aktiv** ist bei der Neuanlage vorausgewählt.

   | Feld | Erwarteter Wert |
   |---|---|
   | Aktiv | Ein |
   | URL | `wss://www.bayrol-poolaccess.de:8083` |
   | Typ | Binär (`1`) |
   | Zertifikate verifizieren | Ein |
   | Header Name | `Sec-WebSocket-Protocol` |
   | Header Wert | `mqtt` |

6. In der Bayrol-Instanz **Seriennummer** und **Gerätetoken** eintragen; anschließend **Dauerverbindung aktivieren** und **Messwerte aktiv anfordern** auswählen. **Übernehmen** klicken.
7. **Verbindung prüfen** und **Messwerte jetzt abrufen**; die Variable **Poolübersicht** öffnet die Visu. Die erste Messung kann vom Cloudserver abhängen.

## Token und Seriennummer der eigenen Anlage

- **Seriennummer:** Typenschild des Geräts oder Bayrol-Menü **App & Webportal Verbindung**, ggf. im eigenen PoolAccess-Portal abgleichen.
- **Token:** Ein **32-stelliger Hexadezimalwert**, der als MQTT-Benutzername dient. Er ist **nicht** das Bayrol-Kontopasswort und nicht die sechsstellige Webportal-PIN.
- Falls ein Token bereits in der **eigenen, autorisierten** PoolAccess-MQTT-Integration vorliegt, kann dieser in der Symcon-Instanz verwendet werden. Bei der eigenen Anlage können unter Umständen Browser-Entwicklerwerkzeuge helfen, sofern das Portal den Token tatsächlich an den authentifizierten Browser ausliefert. Einen offiziell bestätigten, allgemeingültigen Menüpfad zur Token-Ausgabe gibt es nicht. Im Zweifel BAYROL Support kontaktieren.
- Token niemals in GitHub, Screenshots, Supporttickets oder Logs veröffentlichen. **Nicht** im Repository hinterlegen.

## Instanzkonfiguration

| Bereich | Einstellung | Standard / Zweck |
|---|---|---|
| 1. Gerätezugang | DeviceSerial | Seriennummer; daraus wird `d02/<Seriennummer>/v/#` gebildet |
| 1. Gerätezugang | DeviceToken | Geheimnis, 32 Hex-Zeichen |
| 2. Verbindung | Active | Deaktiviert bis die Zugangsdaten gesetzt sind |
| 2. Verbindung | ActiveReads | Aktiv; liest bekannte Bayrol-Werte |
| 2. Verbindung | ReadInterval | 300 Sekunden; Grenzen 60–3600 |
| 2. Verbindung | StaleAfter | 900 Sekunden; Status veralteter Messwerte |
| 3. Visu | VisualTheme | `light` = Light & Clean / `dark` = Dark & Premium |
| 3. Visu | VisualLayout | Standard oder Mini-Kachel |
| 3. Visu | TileTitle | Eigener Titel für die Mini-Kachel. Leer verwendet „Bayrol PoolAccess“. |
| 4. Diagnose | ShowDiagnostics | Aus. Legt bei Bedarf 20 technische Diagnosevariablen an. |

Die Sichtbarkeit angelegter Diagnosevariablen lässt sich im Objektbaum einstellen. Beim Abschalten werden diese Variablen gelöscht. Beim erneuten Einschalten erhalten sie neue Objekt-IDs. Grundstatus, Messwerte, Zeitstempel und Visu sind unabhängig vom Diagnosemodus. Ab Version 1.0.2 bleibt die laufende MQTT-Verbindung beim Umschalten der Diagnose erhalten.

Die MQTT-Verbindung ist **MQTT 3.1.1 mit WebSocket-Subprotokoll `mqtt`**, Benutzername = Token, Passwort `*`, mit persistenter Session. Empfangsthema `d02/<Seriennummer>/v/#`, aktive Leseanfragen werden nur an bekannte `g/`-Topics gesendet.

## Sichtbare Variablen

| Kennung | Inhalt | Bayrol-Wertekennung |
|---|---|---|
| PH | pH / 10 | `4.78` |
| Redox | Redox (mV) | `4.82` |
| WaterTemperature | Temperatur °C / 10 | `4.98` |
| PHDoseRate / RedoxDoseRate | Dosierleistung in % | `4.89` / `4.90` |
| SystemStatus | Gerätestatus **Rohcode** | `1` |
| Alarms | Meldungen/JSON | `10` |
| FlowState | Durchfluss-Rohcode | `5.29` |
| PHEnabled / RedoxEnabled | Regelung-Rohcode | `5.42` / `5.154` |
| PHCanister / RedoxCanister | Kanister-Rohcode | `5.80` / `5.169` |
| Firmware | Firmware | `6.15` |
| Dashboard | HTMLBox **Poolübersicht** mit aufklappbaren Details | – |

Alte Identifikatoren bleiben für bestehende Automationen und historische Daten erhalten. Ein initiales `0` ist nicht zwangsläufig ein gültiger Messwert; zur Validierung Empfangs-Flags und Zeitstempel prüfen. Rohcodes nicht ohne verlässliche Codeliste übersetzen.

## Visu

Unter **3 · Visualisierung → Kachellayout** lässt sich die **Mini-Kachel** auswählen. Sie zeigt den Verbindungsstatus und die drei Messwerte auf bis zu 280 px Breite. Dosierung, Alarme und Datenqualität stehen unter **Details**. Der **Titel der Mini-Kachel** lässt sich frei ändern. Bei bestehenden Instanzen bleibt zunächst das Standardlayout ausgewählt.

Ab Version 1.0.1 passt sich die HTMLBox an ihre verfügbare Breite an: eine bis drei Messwertspalten, fluide Größen und umbrechende Texte. Beide Themes und die aufklappbaren Details bleiben erhalten. Schmale oder niedrige Kacheln benötigen bei Bedarf vertikales Scrollen; die Kachelhöhe wird in Symcon eingestellt. Details zu Änderungen und Prüfung: [Responsive Tests](docs/RESPONSIVE-TESTS.md).


- Kompaktes Dashboard mit pH, Redox, Wassertemperatur und zwei Themes (Hell/Dunkel).
- Status und Dosierleistung; Detailbereich unter **Anlagendetails anzeigen** aufklappbar.
- Das Gerätebild wird von der BAYROL-Website geladen und kann bei fehlender Erreichbarkeit oder Änderung des Bildpfades entfallen. Bildrechte liegen beim jeweiligen Rechteinhaber.

## Updates von vorhandenen Modulen

1. **Backup** der Symcon-Installation anlegen.
2. `git pull` / GitHub-Repository aktualisieren; anschließend Bibliothek unter **Module** aktualisieren.
3. Bestehende Bayrol MQTT-Instanz weiterverwenden. Falls Skripte, Links oder Archive auf technische Diagnosevariablen verweisen, `ShowDiagnostics` vor dem Update aktivieren und übernehmen, damit die Variablen erhalten bleiben. Bei ausgeschalteter Diagnose werden die 20 bisherigen Diagnosevariablen beim Anwenden der Modulkonfiguration gelöscht.
4. Instanz öffnen und **Übernehmen** wählen. Den vorhandenen WS Client prüfen. Bei fehlender oder abweichender Konfiguration unter **Gateway ändern** einen passenden Client auswählen oder anlegen.
5. Das alte **HTML-Diagnose-Modul** ist **nicht** Bestandteil dieses Repositorys; ggf. alte Instanzen separat und kontrolliert entfernen.

## Fehlerbehebung

| Symptom | Ursache / Prüfung |
|---|---|
| WS Client fehlt oder ist leer | Unter **Gateway ändern** einen passenden WS Client auswählen oder über den Dialog anlegen. |
| WS Client nicht verbunden | URL, Typ Binär, Header und Zertifikate prüfen; Internetzugang. |
| MQTT-Anmeldung abgelehnt | Gerätetoken/Seriennummer prüfen, Token nicht in Logs kopieren. |
| SUBACK, keine Werte | Topic muss mit `/v/#` enden; danach manuell Messwerte abrufen. |
| Messwerte 0 | Empfangsflags `HasPH`, `HasRedox`, `HasTemperature` und Updatezeiten prüfen. |
| Visu-Bild fehlt | BAYROL Bildserver erreichbar? |

## Entwicklungsstand / Sicherheitsgrenzen

- PHP-Modul: `BayrolDirectMQTT`, Typ 3 (Device), nativer WS Client als Parent; Module-SDK `IPSModule`.
- Verwendet öffentlich dokumentierte IP-Symcon-Struktur `library.json`, `module.json`, `module.php`, `form.json` und Datenfluss-GUIDs.
- Die MQTT-Integration ist nur **lesend**; bestehende Abfragen und Datenmodell wurden bewusst nicht grundlegend umgebaut.
- **Prüfungen für 1.0.3:** PHP-Syntax, JSON, ZIP und simulierter MQTT-Ablauf mit ein- und ausgeschalteter Diagnose. Der Erstellungsdialog, das Update einer bestehenden Instanz und die Live-Verbindung sind in IP-Symcon praktisch zu prüfen.
- Vorlage für die MQTT-Cloud-Protokollintegration: [tdenolle/bayrol-poolaccess-mqtt](https://github.com/tdenolle/bayrol-poolaccess-mqtt).

Siehe [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md), [CHANGELOG.md](CHANGELOG.md) und [LICENSE-NOTICE.md](LICENSE-NOTICE.md).
