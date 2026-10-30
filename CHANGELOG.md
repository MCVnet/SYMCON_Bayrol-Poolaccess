# Changelog

## 1.0.2 (2026-10-26)

- Fix: automatische WS-Client-Konfiguration erfolgt nachgelagert, falls Parent bei erstmaligem `ApplyChanges()` noch nicht verbunden ist.
- Wiederholungs-Timer `GatewaySetup` und öffentliche Reparaturfunktion `BAYMQ_ConfigureGateway(InstanzID)` eingeführt.
- WS-Client-Konfiguration: Bayrol-URL, `Sec-WebSocket-Protocol: mqtt`, Binärmodus, Zertifikatsprüfung, Aktivierung und eindeutiger Name.
- Bestehende fremde WS-Client-URLs werden nicht überschrieben.
- MQTT-Protokoll, Modul-GUID und Variablenidentifikatoren unverändert.

# Changelog

## 1.0.1 — 2026-10-26
- Automatische Konfiguration des nativen WS-Client-Parents beim Anlegen/Übernehmen.
- Bayrol-WebSocket-URL, MQTT-Subprotokoll-Header, Binärmodus, Zertifikatsprüfung und Aktivierung werden gesetzt.
- Historischen Parent-Namen „MQTT Test“ in „Bayrol PoolAccess – WebSocket“ umbenennen.
- Veralteten Modulalias „MQTT Test“ entfernt.
- Abweichend konfigurierte fremde WS-Clients werden nicht überschrieben.
- Installation und Parent-Verhalten dokumentiert.

## 1.0.0 — 2026-10-26
- HTML-Diagnosemodul aus der Distribution entfernt.
- Ein konsistenter Instanztyp „Bayrol PoolAccess“.
- Instanzkonfiguration in vier Bereiche gegliedert.
- Versionierung und Modulmetadaten bereinigt.
- Installations-, Upgrade-, Token-, Sicherheits- und Entwicklerdokumentation ergänzt.
- Zwei auswählbare Visu-Themes und lesender MQTT-Datenabruf aus 0.5.5 übernommen.
- Modul-IDs / bestehende Variablen-Identifikatoren für Migration beibehalten.
