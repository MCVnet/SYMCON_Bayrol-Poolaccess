# Bayrol PoolAccess – IP-Symcon

![Release](https://img.shields.io/badge/release-v1.0.0-167c9c) ![PHP lint](https://img.shields.io/badge/check-PHP%20syntax-2e9656)

**Direkte, lesende Cloud-Anbindung** einer BAYROL Automatic Cl-pH an IP-Symcon via MQTT over WebSocket. Keine externe MQTT-Bridge, kein Home Assistant und kein eigener MQTT-Broker erforderlich.

> Unabhängiges Community-Projekt, kein offizielles BAYROL-Produkt. **Keine Schreib- oder Dosiersteuerung.** Kommunikation mit dem BAYROL-Cloudserver erforderlich; dessen Schnittstelle ist nicht als stabile öffentliche API zugesichert.

## Installation über GitHub

1. Den Inhalt dieses Repositorys im Stammverzeichnis des GitHub-Repositories veröffentlichen (nicht in einem zusätzlichen Ordner). `library.json` liegt direkt im Root.
2. In IP-Symcon: **Kerninstanzen → Module → Hinzufügen**. GitHub-Repository-URL angeben, z. B. `https://github.com/DEIN-ACCOUNT/DEIN-REPO.git`.
3. Nach dem Laden über **Instanz hinzufügen → Bayrol Direct MQTT** die Instanz anlegen.
4. Unter **Gateway ändern** kontrollieren, ob ein nativer **WS Client** zugeordnet wurde. Wenn nicht, einen WS Client erzeugen und zuweisen.
5. Der Einrichtungs-Timer versucht, einen leeren WS Client automatisch zu konfigurieren. **Unbedingt im WS Client kontrollieren:**

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

**Wichtig:** Wenn der WS Client noch leer ist, zunächst die Verknüpfung prüfen und `BAYMQ_ConfigureGateway(<Instanz-ID>);` einmal über die Symcon-PHP-Konsole ausführen oder die Bayrol-Instanz erneut übernehmen. Clients mit einer *abweichenden, bereits gesetzten* URL werden absichtlich nicht überschrieben. Die automatische Einrichtung ist nicht in einer realen Neuinstallation verifiziert.

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
| 4. Diagnose | ShowDiagnostics | Aus; nur für Fehlersuche aktivieren |

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

- Kompaktes Dashboard mit pH, Redox, Wassertemperatur und zwei Themes (Hell/Dunkel).
- Status und Dosierleistung; Detailbereich unter **Anlagendetails anzeigen** aufklappbar.
- Das Gerätebild wird von der BAYROL-Website geladen und kann bei fehlender Erreichbarkeit oder Änderung des Bildpfades entfallen. Bildrechte liegen beim jeweiligen Rechteinhaber.

## Updates von vorhandenen Modulen

1. **Backup** der Symcon-Installation anlegen.
2. `git pull` / GitHub-Repository aktualisieren; anschließend Bibliothek unter **Module** aktualisieren.
3. Bestehende Bayrol MQTT-Instanz **nicht löschen**. Modul-GUID, Prefix `BAYMQ` und die Variablen-Idents bleiben unverändert.
4. Instanz öffnen → **Übernehmen**; WS Client prüfen; Daten empfangen.
5. Das alte **HTML-Diagnose-Modul** ist **nicht** Bestandteil dieses Repositorys; ggf. alte Instanzen separat und kontrolliert entfernen.

## Fehlerbehebung

| Symptom | Ursache / Prüfung |
|---|---|
| WS Client leer | Zugeordneter Parent fehlt oder wurde erst nach `Create()` verbunden. Gateway erneut zuweisen, `BAYMQ_ConfigureGateway(ID)` ausführen. |
| WS Client nicht verbunden | URL, Typ Binär, Header und Zertifikate prüfen; Internetzugang. |
| MQTT-Anmeldung abgelehnt | Gerätetoken/Seriennummer prüfen, Token nicht in Logs kopieren. |
| SUBACK, keine Werte | Topic muss mit `/v/#` enden; danach manuell Messwerte abrufen. |
| Messwerte 0 | Empfangsflags `HasPH`, `HasRedox`, `HasTemperature` und Updatezeiten prüfen. |
| Visu-Bild fehlt | BAYROL Bildserver erreichbar? |

## Entwicklungsstand / Sicherheitsgrenzen

- PHP-Modul: `BayrolDirectMQTT`, Typ 3 (Device), nativer WS Client als Parent; Module-SDK `IPSModule`.
- Verwendet öffentlich dokumentierte IP-Symcon-Struktur `library.json`, `module.json`, `module.php`, `form.json` und Datenfluss-GUIDs.
- Die MQTT-Integration ist nur **lesend**; bestehende Abfragen und Datenmodell wurden bewusst nicht grundlegend umgebaut.
- **Statischer Test**: PHP-Lint, JSON-Syntax, GUID-Referenzen und ZIP-Struktur. **Nicht getestet**: Symcon-8.x-Laufzeit, erstmalige automatische Parent-Konfiguration, Cloud-Verfügbarkeit, alle Gerätefirmwares. Für ein offizielles Store-Release den **Symcon Modul Validator** und reale Gerätetests ausführen.
- Vorlage für die MQTT-Cloud-Protokollintegration: [tdenolle/bayrol-poolaccess-mqtt](https://github.com/tdenolle/bayrol-poolaccess-mqtt).

Siehe [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md), [CHANGELOG.md](CHANGELOG.md) und [LICENSE-NOTICE.md](LICENSE-NOTICE.md).
