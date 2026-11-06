<?php
/** Bayrol MQTT over native IP-Symcon WebSocket client, read only. Version 1.0.0 – direkte, lesende MQTT-Integration mit wählbarem Visu-Design. */
class BayrolDirectMQTT extends IPSModule
{
    private const WSC = '{D68FD31F-0E90-7019-F16C-1949BD3079EF}';
    private const SEND = '{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}';
    public function Create()
    {
        parent::Create();
        $this->RegisterPropertyString('DeviceSerial', '');
        $this->RegisterPropertyString('DeviceToken', '');
        $this->RegisterPropertyString('MQTTPassword', '*'); // Legacy setting, ignored; upstream uses literal *
        // Historical SubscribeTopic property retained solely for seamless upgrades; no longer used.
        $this->RegisterPropertyString('SubscribeTopic', '');
        $this->RegisterPropertyBoolean('ShowDiagnostics', false);
        $this->RegisterPropertyString('VisualTheme', 'light');
        $this->RegisterPropertyInteger('StaleAfter', 900);
        $this->RegisterPropertyInteger('Interval', 300);
        $this->RegisterPropertyBoolean('Active', false);
        $this->RegisterPropertyBoolean('ActiveReads', true);
        $this->RegisterPropertyInteger('ReadInterval', 300);
        $this->RegisterPropertyInteger('ReadQoS', 0);
        $this->RegisterPropertyString('ReadPayload', 'empty');
        $this->RegisterTimer('ReadMeasurements', 0, 'BAYMQ_RequestMeasurements($_IPS["TARGET"]);');
        $this->RegisterTimer('Refresh', 0, 'BAYMQ_Refresh($_IPS["TARGET"]);');
        $this->RegisterTimer('KeepAlive', 0, 'BAYMQ_KeepAlive($_IPS["TARGET"]);');
        $this->RegisterTimer('HealthCheck', 0, 'BAYMQ_UpdateDashboard($_IPS["TARGET"]);');
        // Parent attachment may finish AFTER the first ApplyChanges at instance creation.
        $this->RegisterTimer('GatewaySetup', 0, 'BAYMQ_ConfigureGateway($_IPS["TARGET"]);');
        $this->ConnectParent(self::WSC);
        $this->RegisterVariableString('Stage', 'MQTT Diagnosestatus', '', 1);
        $this->RegisterVariableString('Error', 'MQTT Fehler', '', 2);
        $this->RegisterVariableBoolean('Connected', 'MQTT Verbindung hergestellt', '~Switch', 3);
        $this->RegisterVariableInteger('Messages', 'MQTT Nachrichten (letzter Test)', '', 4);
        $this->RegisterVariableString('Topics', 'MQTT Topics (gekürzt)', '', 5);
        $this->RegisterVariableString('LastCheck', 'Letzter Test', '', 6);
        // Existing diagnostics remain unchanged; measurements start after them.
        $this->RegisterVariableFloat('PH', 'pH-Wert', '', 10);
        $this->RegisterVariableFloat('Redox', 'Redox (mV)', '', 11);
        $this->RegisterVariableFloat('WaterTemperature', 'Wassertemperatur (°C)', '', 12);
        $this->RegisterVariableInteger('PHDoseRate', 'pH Dosierleistung (%)', '', 13);
        $this->RegisterVariableInteger('RedoxDoseRate', 'Redox Dosierleistung (%)', '', 14);
        $this->RegisterVariableString('SystemStatus', 'Gerätestatus (Rohcode)', '', 15);
        $this->RegisterVariableString('Alarms', 'Alarmmeldungen (JSON)', '', 16);
        $this->RegisterVariableString('FlowState', 'Durchfluss (Rohcode)', '', 17);
        $this->RegisterVariableString('PHEnabled', 'pH Regelung (Rohcode)', '', 18);
        $this->RegisterVariableString('RedoxEnabled', 'Redox Regelung (Rohcode)', '', 19);
        $this->RegisterVariableString('PHCanister', 'pH Minus Kanister (Rohcode)', '', 20);
        $this->RegisterVariableString('RedoxCanister', 'Redox Kanister (Rohcode)', '', 21);
        $this->RegisterVariableString('Firmware', 'Firmware', '', 22);
        $this->RegisterVariableString('LastMeasurement', 'Letzter Messwertempfang', '', 23);
        $this->RegisterVariableInteger('Decoded', 'Verarbeitete Werte (letzter Test)', '', 24);
        $this->RegisterVariableString('Result', 'Ergebnis letzter Abruf', '', 25);

        $this->RegisterVariableString('StreamState', 'Dauerverbindung Status', '', 26);
        $this->RegisterVariableString('LastPacket', 'Letztes MQTT Paket', '', 27);
        $this->RegisterVariableString('MeasurementSource', 'Messwertquelle', '', 28);
        $this->RegisterVariableString('ReadStatus', 'Aktiver MQTT-Abruf Status', '', 30);
        $this->RegisterVariableString('LastRead', 'Letzte MQTT-Anfrage', '', 31);
        $this->RegisterVariableInteger('ReadResponseCount', 'Messwertantworten seit Anfrage', '', 32);
        $this->RegisterVariableString('LastPH', 'pH letzte Aktualisierung', '', 33);
        $this->RegisterVariableString('LastRedox', 'Redox letzte Aktualisierung', '', 34);
        $this->RegisterVariableString('LastTemperature', 'Temperatur letzte Aktualisierung', '', 35);
        $this->RegisterVariableString('ReadAnswers', 'Antworten zur letzten Anfrage', '', 36);
        $this->RegisterVariableString('ReadMode', 'Automatische Abfrage', '', 37);
        $this->RegisterVariableString('ReadWireFormat', 'Leseanfrage Sendemodus', '', 38);
        $this->RegisterVariableInteger('ReadAckCount', 'PUBACK zur letzten Anfrage', '', 39);
        $this->RegisterVariableString('LastIncomingPublish', 'Letztes eingehendes PUBLISH', '', 40);
        $this->RegisterVariableString('ReadDiagnostics', 'Abfragediagnose', '', 41);
        $this->RegisterVariableString('WireDiagnostics', 'MQTT Paketdiagnose (ohne Zugangsdaten)', '', 42);
        $this->RegisterVariableInteger('OutgoingReads', 'Ausgehende Leseanfragen', '', 43);
        $this->RegisterVariableInteger('IncomingPublishes', 'Empfangene PUBLISH Pakete', '', 44);
        $this->RegisterVariableString('Dashboard', 'Poolübersicht', '~HTMLBox', 0);
        $this->RegisterVariableString('DataQuality', 'Messwertqualität', '', 7);
        $this->RegisterVariableString('ConnectionHealth', 'Verbindungsüberwachung', '', 8);
        $this->RegisterVariableBoolean('HasPH', 'pH-Wert empfangen', '~Switch', 45);
        $this->RegisterVariableBoolean('HasRedox', 'Redox-Wert empfangen', '~Switch', 46);
        $this->RegisterVariableBoolean('HasTemperature', 'Temperatur empfangen', '~Switch', 47);
    }
    public function ApplyChanges()
    {
        parent::ApplyChanges();
        // Parent creation/attachment in Symcon is asynchronous relative to Create().
        // Retry a short time after installation, including when the Bayrol instance is inactive.
        $this->SetTimerInterval('GatewaySetup', 2000);
        $this->ConfigureGateway();
        // Remove the obsolete HTTP fallback status variable from upgrades.
        $this->MaintainVariable('HTTPFallbackState', 'HTTP Fallback Status', 3, '', 29, false);
        $this->SetTimerInterval('Refresh', 0);
        $this->SetTimerInterval('HealthCheck', 60000);
        $diagnostics = ['Messages','Topics','LastCheck','Decoded','Result','StreamState','LastPacket','MeasurementSource','ReadStatus','LastRead','ReadResponseCount','ReadAnswers','ReadMode','ReadWireFormat','ReadAckCount','LastIncomingPublish','ReadDiagnostics','WireDiagnostics','OutgoingReads','IncomingPublishes'];
        foreach ($diagnostics as $ident) {
            $id = $this->GetIDForIdent($ident);
            IPS_SetHidden($id, !$this->ReadPropertyBoolean('ShowDiagnostics'));
        }
        // Never remove historical diagnostics or values during an update.
        $this->UpdateDashboard();
        $interval = max(60, min(3600, $this->ReadPropertyInteger('ReadInterval')));
        $auto = $this->ReadPropertyBoolean('Active') && $this->ReadPropertyBoolean('ActiveReads');
        $this->SetTimerInterval('ReadMeasurements', $auto ? $interval * 1000 : 0);
        $this->SetValue('ReadMode', $auto ? 'Aktiv – alle ' . $interval . ' Sekunden' : 'Deaktiviert');
        $this->SetValue('ReadWireFormat', 'QoS 0, Payload leer (Original-Bridge)');
        $this->SetTimerInterval('KeepAlive', $this->ReadPropertyBoolean('Active') ? 15000 : 0);
        if (!$this->ReadPropertyBoolean('Active')) {
            $this->SetBuffer('MQTTState', 'offline');
            $this->SetValue('Connected', false);
            $this->SetValue('StreamState', 'Deaktiviert');
            return;
        }
        $this->SetBuffer('MQTTState', 'waiting');
        $this->SetBuffer('MQTTBuffer', '');
        $this->SetBuffer('LastMQTTPacket', '0');
        $this->SetBuffer('AutoReadQueued', '0');
        $this->SetBuffer('LastPing', '0');
        $this->SetValue('StreamState', 'Warte auf WebSocket-Verbindung');
        $this->Refresh();
    }
    /**
     * Configure the native WS Client after Symcon has attached the parent.
     * Public method is also used by the GatewaySetup timer. It is safe to
     * call repeatedly and does not change custom (foreign-URL) clients.
     */
    public function ConfigureGateway(): bool
    {
        $instance = IPS_GetInstance($this->InstanceID);
        $parentID = (int) ($instance['ConnectionID'] ?? 0);
        if ($parentID <= 0 || !IPS_InstanceExists($parentID)) {
            $this->SendDebug('GatewaySetup', 'WS-Client noch nicht verbunden. Erneuter Versuch folgt.', 0);
            return false;
        }
        $parent = IPS_GetInstance($parentID);
        if (($parent['ModuleInfo']['ModuleID'] ?? '') !== self::WSC) {
            $this->SendDebug('GatewaySetup', 'Parent ist kein WS Client. Keine Änderung.', 0);
            $this->SetTimerInterval('GatewaySetup', 0);
            return false;
        }
        $expected = 'wss://www.bayrol-poolaccess.de:8083';
        $url = trim((string) IPS_GetProperty($parentID, 'URL'));
        if ($url !== '' && rtrim($url, '/') !== $expected) {
            $this->SendDebug('GatewaySetup', 'WS Client nutzt eine andere URL. Bestehende Konfiguration bleibt erhalten.', 0);
            $this->SetTimerInterval('GatewaySetup', 0);
            return false;
        }
        $changed = false;
        if ($url === '') {
            IPS_SetProperty($parentID, 'URL', $expected);
            $changed = true;
        }
        $headers = json_decode((string) IPS_GetProperty($parentID, 'Headers'), true);
        if (!is_array($headers)) $headers = [];
        $found = false;
        foreach ($headers as &$header) {
            if (is_array($header) && strcasecmp((string) ($header['Name'] ?? ''), 'Sec-WebSocket-Protocol') === 0) {
                $found = true;
                if (($header['Value'] ?? '') !== 'mqtt') {
                    $header['Value'] = 'mqtt';
                    $changed = true;
                }
            }
        }
        unset($header);
        if (!$found) {
            $headers[] = ['Name' => 'Sec-WebSocket-Protocol', 'Value' => 'mqtt'];
            $changed = true;
        }
        if ($changed) IPS_SetProperty($parentID, 'Headers', json_encode($headers, JSON_UNESCAPED_SLASHES));
        if ((int) IPS_GetProperty($parentID, 'Type') !== 1) {
            IPS_SetProperty($parentID, 'Type', 1);
            $changed = true;
        }
        if (!(bool) IPS_GetProperty($parentID, 'VerifyCertificate')) {
            IPS_SetProperty($parentID, 'VerifyCertificate', true);
            $changed = true;
        }
        if (!(bool) IPS_GetProperty($parentID, 'Active')) {
            IPS_SetProperty($parentID, 'Active', true);
            $changed = true;
        }
        $name = IPS_GetName($parentID);
        if (in_array($name, ['MQTT Test', 'WS Client', 'WebSocket Client', 'WebSocket-Client'], true)) {
            IPS_SetName($parentID, 'Bayrol PoolAccess – WebSocket');
        }
        if ($changed) {
            if (!IPS_ApplyChanges($parentID)) {
                $this->SendDebug('GatewaySetup', 'WS Client konnte nicht übernommen werden; erneuter Versuch folgt.', 0);
                return false;
            }
        }
        // The parent is now configured; stop retrying until ApplyChanges next runs.
        $this->SetTimerInterval('GatewaySetup', 0);
        $this->SendDebug('GatewaySetup', 'WS Client Konfiguration geprüft (Instanz ' . $parentID . ').', 0);
        return true;
    }

    private function sendPacket(string $packet): void
    {
        // Symcon Simple dataflow transports binary bytes as Latin-1 mapped to UTF-8.
        $encoded = mb_convert_encoding($packet, 'UTF-8', 'ISO-8859-1');
        $this->SendDataToParent(json_encode(['DataID' => self::SEND, 'Buffer' => $encoded], JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE));
    }
    private function errorState(string $message): bool
    {
        $this->SetValue('Error', $message);
        $this->SetValue('Stage', 'Fehler: ' . $message);
        $this->SetValue('StreamState', 'Fehler: ' . $message);
        $this->SetValue('Result', 'Fehler: ' . $message);
        $this->SetValue('Connected', false);
        return false;
    }
    public function Refresh(): bool
    {
        $serial = trim($this->ReadPropertyString('DeviceSerial'));
        $token = trim($this->ReadPropertyString('DeviceToken'));
        $topic = 'd02/' . $serial . '/v/#';
        if (!preg_match('/^[A-Za-z0-9-]{4,64}$/D', $serial)) return $this->errorState('Seriennummer ungültig');
        if (!preg_match('/^[0-9a-fA-F]{32}$/D', $token)) return $this->errorState('Gerätetoken muss 32 Hex-Zeichen haben');
        // Wildcard is generated from the validated serial; the old field is ignored.
        if (!$this->HasActiveParent()) return $this->errorState('WebSocket-Client nicht verbunden. URL und Aktivierung prüfen.');
        if ($this->GetBuffer('MQTTState') === 'subscribed') {
            $this->SetValue('Result', 'Dauerempfang läuft bereits; neue Nachrichten werden automatisch übernommen.');
            return true;
        }
        // Match app/mqtt/MqttClient.py and PoolAccessClient.py from the upstream bridge:
        // stable client ID during module lifetime, clean_session=false, keepalive=60,
        // username=token, MQTT password is literally '*'.
        $clientId = $this->GetBuffer('StableClientId');
        if ($clientId === '') {
            $clientId = 'user_' . substr(bin2hex(random_bytes(6)), 0, 8);
            $this->SetBuffer('StableClientId', $clientId);
        }
        $connect = $this->mqttString('MQTT') . chr(4) . chr(0xC0) . pack('n', 60) . $this->mqttString($clientId) . $this->mqttString($token) . $this->mqttString('*');
        $this->SetBuffer('MQTTState', 'connecting');
        $this->SetBuffer('AutoReadQueued', '0');
        $this->SetBuffer('LastPing', (string)time());
        $this->SetValue('StreamState', 'MQTT CONNECT gesendet; warte auf CONNACK');
        $this->SetValue('Result', 'MQTT-Anmeldung läuft über WebSocket.');
        $this->SetValue('LastCheck', date('c'));
        $this->sendPacket($this->mqttPacket(0x10, $connect));
        return true;
    }
    public function KeepAlive(): void
    {
        if (!$this->ReadPropertyBoolean('Active')) return;
        if (!$this->HasActiveParent()) {
            $this->SetBuffer('MQTTState', 'waiting');
            $this->SetValue('Connected', false);
            $this->SetValue('StreamState', 'WebSocket offline – Symcon übernimmt Wiederverbindung');
            return;
        }
        $state = $this->GetBuffer('MQTTState');
        $now = time();
        if ($state !== 'subscribed') {
            if ($state !== 'connecting' || $now - (int)$this->GetBuffer('LastPing') > 25) {
                $this->SetBuffer('LastPing', (string)$now);
                $this->Refresh();
            }
            return;
        }
        if ($now - (int)$this->GetBuffer('LastMQTTPacket') > 60) {
            $this->SetValue('StreamState', 'Keine MQTT-Antwort seit über 60 s; erneuter Verbindungsaufbau erforderlich');
            $this->SetBuffer('MQTTState', 'waiting');
            $this->SetValue('Connected', false);
            $this->Refresh();
            return;
        }
        $this->sendPacket($this->mqttPacket(0xC0, ''));
        $this->SetBuffer('LastPing', (string)$now);
    }
    public function ReceiveData($JSONString)
    {
        $obj = json_decode($JSONString, true);
        if (!is_array($obj) || !isset($obj['Buffer']) || !is_string($obj['Buffer'])) return '';
        if (!$this->ReadPropertyBoolean('Active')) return '';
        $incoming = mb_convert_encoding($obj['Buffer'], 'ISO-8859-1', 'UTF-8');
        $buffer = $this->GetBuffer('MQTTBuffer') . $incoming;
        if (strlen($buffer) > 2097152) { $this->SetBuffer('MQTTBuffer', ''); $this->errorState('MQTT-Empfangspuffer zu groß'); return ''; }
        try {
            $packets = $this->parsePackets($buffer);
            $this->SetBuffer('MQTTBuffer', $buffer);
            foreach ($packets as [$header, $body]) {
                $type = $header >> 4;
                $this->SetBuffer('LastMQTTPacket', (string)time());
                $this->SetValue('LastPacket', date('c') . ' (Typ ' . $type . ')');
                if ($type === 2) {
                    if (strlen($body) < 2 || ord($body[1]) !== 0) { $this->errorState('CONNACK abgelehnt'); continue; }
                    $this->SetBuffer('MQTTState', 'subscribing');
                    $this->SetValue('Stage', 'MQTT angemeldet; SUBSCRIBE gesendet');
                    $topic = 'd02/' . trim($this->ReadPropertyString('DeviceSerial')) . '/v/#';
                    $subscribe = $this->mqttPacket(0x82, pack('n', 1) . $this->mqttString($topic) . chr(0));
                    $this->sendPacket($subscribe);
                    // Upstream PoolAccessMqttBridge.on_poolaccess_connect() sends GETs
                    // immediately after subscribe(), before any SUBACK callback.
                    $this->SetValue('WireDiagnostics', 'CONNECT akzeptiert; SUBSCRIBE gesendet (' . strlen($subscribe) . ' Bytes)');
                    if ($this->ReadPropertyBoolean('ActiveReads')) {
                        $this->SetBuffer('AutoReadQueued', '1');
                        $this->RequestMeasurements();
                    }
                } elseif ($type === 9) {
                    if (strlen($body) < 3 || ord($body[2]) > 2) { $this->errorState('SUBSCRIBE verweigert'); continue; }
                    $this->SetBuffer('MQTTState', 'subscribed');
                    $this->SetValue('Connected', true);
                    $this->SetValue('Error', '');
                    $this->SetValue('Stage', 'MQTT verbunden, Subscription bestätigt');
                    $this->SetValue('StreamState', 'Dauerempfang aktiv');
                    $this->UpdateDashboard();
                    $this->SetValue('Result', 'MQTT-Verbindung und Subscription erfolgreich. Dauerempfang aktiv.');
                    $this->SetValue('WireDiagnostics', 'SUBACK bestätigt; Initialabfragen ' . ($this->GetBuffer('AutoReadQueued') === '1' ? 'bereits vor SUBACK gesendet' : 'nicht gesendet'));
                } elseif ($type === 4 && strlen($body) >= 2) {
                    $pid = unpack('n', substr($body, 0, 2))[1];
                    $sent = json_decode($this->GetBuffer('ReadPacketIds'), true);
                    if (is_array($sent) && in_array($pid, $sent, true)) {
                        $count = GetValueInteger($this->GetIDForIdent('ReadAckCount')) + 1;
                        $this->SetValue('ReadAckCount', $count);
                        $this->SetValue('ReadDiagnostics', 'PUBACK: ' . $count . ' / ' . count($sent) . '; Messwertantworten: ' . GetValueInteger($this->GetIDForIdent('ReadResponseCount')));
                    }
                } elseif ($type === 3 && strlen($body) >= 2) {
                    $len = unpack('n', substr($body, 0, 2))[1];
                    if (2 + $len > strlen($body) || $len > 180) continue;
                    $topic = substr($body, 2, $len);
                    $qos = ($header >> 1) & 3;
                    if ($qos > 2) continue;
                    $offset = 2 + $len;
                    $pid = 0;
                    if ($qos > 0) { if (strlen($body) < $offset + 2) continue; $pid = unpack('n', substr($body, $offset, 2))[1]; $offset += 2; }
                    $payload = substr($body, $offset);
                    $this->SetValue('LastIncomingPublish', date('c') . ' ' . substr($topic, 0, 180));
                    $this->SetValue('IncomingPublishes', GetValueInteger($this->GetIDForIdent('IncomingPublishes')) + 1);
                    $this->SetValue('WireDiagnostics', 'PUBLISH empfangen: ' . substr($topic, 0, 160) . ', Nutzlast ' . strlen($payload) . ' Bytes, QoS ' . $qos);
                    $this->SetValue('Messages', GetValueInteger($this->GetIDForIdent('Messages')) + 1);
                    $this->SetValue('Topics', substr($topic, 0, 180));
                    if ($this->processMeasurement($topic, $payload, trim($this->ReadPropertyString('DeviceSerial')))) {
                        $this->SetValue('Decoded', GetValueInteger($this->GetIDForIdent('Decoded')) + 1);
                        $this->UpdateDashboard();
                        $received = (int)$this->GetBuffer('LastReadEpoch');
                        if ($received > 0 && time() - $received < 120) {
                            $code = substr($topic, strlen('d02/' . trim($this->ReadPropertyString('DeviceSerial')) . '/v/'));
                            $seen = json_decode($this->GetBuffer('ReadSeen'), true);
                            if (!is_array($seen)) $seen = [];
                            if (!in_array($code, $seen, true)) {
                                $seen[] = $code;
                                $this->SetBuffer('ReadSeen', json_encode($seen));
                                $this->SetValue('ReadResponseCount', count($seen));
                                $this->SetValue('ReadAnswers', implode(', ', $seen));
                            }
                            $this->SetValue('ReadStatus', 'Anfrage aktiv: ' . count($seen) . ' unterschiedliche Antworten empfangen');
                            $this->SetValue('ReadDiagnostics', 'Messwertantworten: ' . count($seen) . '; PUBACK: ' . GetValueInteger($this->GetIDForIdent('ReadAckCount')));
                        }
                    }
                    if ($qos === 1) $this->sendPacket($this->mqttPacket(0x40, pack('n', $pid)));
                    if ($qos === 2) $this->sendPacket($this->mqttPacket(0x50, pack('n', $pid)));
                } elseif ($type === 13) {
                    // PINGRESP received, connection still healthy.
                }
            }
        } catch (Throwable $e) { $this->SetBuffer('MQTTBuffer', ''); $this->errorState($e->getMessage()); }
        return '';
    }
    /** Publish allowlisted Bayrol GET topics. No dosage/control commands are generated. */
    public function RequestMeasurements(): bool
    {
        if (!$this->ReadPropertyBoolean('Active') || !in_array($this->GetBuffer('MQTTState'), ['subscribed', 'subscribing'], true) || !$this->HasActiveParent()) {
            $this->SetValue('ReadStatus', 'Nicht gesendet: MQTT nicht angemeldet/abonniert');
            return false;
        }
        $serial = trim($this->ReadPropertyString('DeviceSerial'));
        if (!preg_match('/^[A-Za-z0-9-]{4,64}$/D', $serial)) {
            $this->SetValue('ReadStatus', 'Ungültige Geräte-Seriennummer');
            return false;
        }
        // Read-only property identifiers confirmed by HAS log.
        $codes = ['4.78', '4.82', '4.98', '4.89', '4.90', '1', '10', '5.29', '5.42', '5.154', '5.80', '5.169', '6.15'];
        $this->SetValue('ReadResponseCount', 0);
        $this->SetValue('ReadAnswers', 'Noch keine Antworten auf diese Anfrage');
        $this->SetBuffer('ReadSeen', '[]');
        $this->SetBuffer('LastReadEpoch', (string)time());
        $qos = 0; // Upstream client.publish(topic) defaults to QoS 0.
        $payloadMode = 'empty';
        $payload = ''; // Upstream client.publish(topic) defaults payload=None.
        $packetIds = [];
        $this->SetValue('ReadAckCount', 0);
        foreach ($codes as $code) {
            // Only known GET topics. QoS and payload are configurable for protocol validation.
            // Retain is NEVER set; no command (/s/) topic can be published here.
            $topic = 'd02/' . $serial . '/g/' . $code;
            $idPart = '';
            if ($qos === 1) {
                $pid = random_int(1, 65535);
                while (in_array($pid, $packetIds, true)) $pid = random_int(1, 65535);
                $packetIds[] = $pid;
                $idPart = pack('n', $pid);
            }
            $this->sendPacket($this->mqttPacket($qos ? 0x32 : 0x30, $this->mqttString($topic) . $idPart . $payload));
        }
        $this->SetBuffer('ReadPacketIds', json_encode($packetIds));
        $this->SetValue('ReadWireFormat', 'QoS ' . $qos . ', Payload ' . ($payloadMode === 'json' ? '{}': 'leer'));
        $this->SetValue('ReadDiagnostics', 'Anfragen: ' . count($codes) . '; QoS 0; Payload leer; PUBACK bei QoS 0 nicht vorgesehen; Antworten: 0');
        $this->SetValue('LastRead', date('c'));
        $this->SetValue('ReadStatus', 'Anfragen gesendet: ' . count($codes) . ' Lese-Topics (QoS ' . $qos . '); warte auf Antworten');
        $this->SetValue('OutgoingReads', GetValueInteger($this->GetIDForIdent('OutgoingReads')) + count($codes));
        $this->SetValue('WireDiagnostics', count($codes) . ' GET-PUBLISH versendet: QoS 0, leere Payload, Retain 0; MQTT-Status ' . $this->GetBuffer('MQTTState'));
        return true;
    }
    private function mqttString(string $value): string { return pack('n', strlen($value)) . $value; }
    private function mqttLength(int $value): string
    {
        $result = '';
        do { $digit = $value % 128; $value = intdiv($value, 128); if ($value > 0) { $digit |= 128; } $result .= chr($digit); } while ($value > 0);
        return $result;
    }
    private function mqttPacket(int $header, string $payload): string { return chr($header) . $this->mqttLength(strlen($payload)) . $payload; }
    private function parsePackets(string &$buffer): array
    {
        $packets = [];
        while (strlen($buffer) > 1) {
            $mult = 1; $length = 0; $i = 1;
            do {
                if ($i >= strlen($buffer)) { return $packets; }
                $byte = ord($buffer[$i]); $length += ($byte & 127) * $mult; $mult *= 128; ++$i;
                if ($i > 5) { throw new RuntimeException('Ungültige MQTT Paketlänge'); }
            } while ($byte & 128);
            if ($length > 1048576) { throw new RuntimeException('MQTT Paket zu groß'); }
            if (strlen($buffer) < $i + $length) { return $packets; }
            $packets[] = [ord($buffer[0]), substr($buffer, $i, $length)];
            $buffer = substr($buffer, $i + $length);
        }
        return $packets;
    }
    /** Parse a Bayrol value message. Unknown status enumerations are intentionally left as raw strings. */
    private function processMeasurement(string $topic, string $payload, string $serial): bool
    {
        $prefix = 'd02/' . $serial . '/v/';
        if (strncmp($topic, $prefix, strlen($prefix)) !== 0) { return false; }
        $code = substr($topic, strlen($prefix));
        $data = json_decode($payload, true);
        if (!is_array($data) || !array_key_exists('v', $data)) { return false; }
        $v = $data['v'];
        // Numeric limits are checked before any value is written.
        $numeric = is_int($v) || is_float($v) || (is_string($v) && is_numeric($v));
        switch ($code) {
            case '4.78':
                if (!$numeric || (float)$v < 0 || (float)$v > 140) return false;
                $this->SetValue('PH', round((float)$v / 10.0, 2)); $this->SetValue('LastPH', date('c')); $this->SetValue('HasPH', true); $this->SetValue('MeasurementSource', 'MQTT – pH aktualisiert'); break;
            case '4.82':
                if (!$numeric || (float)$v < 0 || (float)$v > 1500) return false;
                $this->SetValue('Redox', (float)$v); $this->SetValue('LastRedox', date('c')); $this->SetValue('HasRedox', true); $this->SetValue('MeasurementSource', 'MQTT – Redox aktualisiert'); break;
            case '4.98':
                if (!$numeric || (float)$v < -200 || (float)$v > 800) return false;
                $this->SetValue('WaterTemperature', round((float)$v / 10.0, 2)); $this->SetValue('LastTemperature', date('c')); $this->SetValue('HasTemperature', true); $this->SetValue('MeasurementSource', 'MQTT – Temperatur aktualisiert'); break;
            case '4.89': case '4.90':
                if (!$numeric || (float)$v < 0 || (float)$v > 100) return false;
                $this->SetValue($code === '4.89' ? 'PHDoseRate' : 'RedoxDoseRate', (int)round((float)$v)); break;
            case '1': $this->SetValue('SystemStatus', is_scalar($v) ? (string)$v : json_encode($v)); break;
            case '10': $this->SetValue('Alarms', json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); break;
            case '5.29': $this->SetValue('FlowState', is_scalar($v) ? (string)$v : json_encode($v)); break;
            case '5.42': $this->SetValue('PHEnabled', is_scalar($v) ? (string)$v : json_encode($v)); break;
            case '5.154': $this->SetValue('RedoxEnabled', is_scalar($v) ? (string)$v : json_encode($v)); break;
            case '5.80': $this->SetValue('PHCanister', is_scalar($v) ? (string)$v : json_encode($v)); break;
            case '5.169': $this->SetValue('RedoxCanister', is_scalar($v) ? (string)$v : json_encode($v)); break;
            case '6.15': $this->SetValue('Firmware', is_scalar($v) ? (string)$v : json_encode($v)); break;
            default: return false;
        }
        $this->SetValue('LastMeasurement', date('c'));
        return true;
    }
    /** Summarize real validity, not default zero values. Called on a minute timer and on data receipt. */
    public function UpdateDashboard(): void
    {
        $online = GetValueBoolean($this->GetIDForIdent('Connected')) && $this->GetBuffer('MQTTState') === 'subscribed';
        $limit = max(120, min(86400, $this->ReadPropertyInteger('StaleAfter')));
        $this->SetValue('ConnectionHealth', $online ? 'MQTT verbunden' : 'MQTT nicht verbunden');
        $fields = [
            ['pH', 'PH', 'LastPH', 'HasPH', 'pH'],
            ['Redox', 'Redox', 'LastRedox', 'HasRedox', 'mV'],
            ['Wassertemperatur', 'WaterTemperature', 'LastTemperature', 'HasTemperature', '°C'],
        ];
        $theme = $this->ReadPropertyString('VisualTheme') === 'dark' ? 'dark' : 'light';
        $cards = '';
        $health = [];
        $esc = static fn(string $v): string => htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        foreach ($fields as [$title, $ident, $timeIdent, $flagIdent, $unit]) {
            $stamp = GetValueString($this->GetIDForIdent($timeIdent));
            $ts = $stamp !== '' ? strtotime($stamp) : false;
            $valid = GetValueBoolean($this->GetIDForIdent($flagIdent)) || $ts !== false;
            $age = $ts === false ? -1 : max(0, time() - $ts);
            $stale = !$valid || $age < 0 || $age > $limit;
            $quality = !$valid ? 'Keine Daten' : ($stale ? 'Veraltet' : 'Aktuell');
            $value = $valid ? number_format((float) GetValue($this->GetIDForIdent($ident)), $ident === 'Redox' ? 0 : ($ident === 'PH' ? 2 : 1), ',', '.') : '—';
            $detail = $valid && $ts !== false ? date('d.m. H:i', $ts) : 'Nicht empfangen';
            $icon = $ident === 'PH' ? '◉' : ($ident === 'Redox' ? '〰' : '❄');
            $tone = $ident === 'PH' ? 'ph' : ($ident === 'Redox' ? 'rx' : 'temp');
            $cards .= '<div class="metric ' . $tone . '"><div class="metric-top"><span class="metric-icon">' . $icon . '</span><span class="metric-label">' . $esc($title) . '</span></div>'
               . '<div class="number">' . $esc($value) . '<small>' . $esc($unit) . '</small></div><div class="quality ' . ($stale ? 'stale' : 'fresh') . '">● ' . $esc($quality) . ' <span>· ' . $esc($detail) . '</span></div></div>';
            $health[] = $title . ': ' . $quality;
        }
        $this->SetValue('DataQuality', implode(' | ', $health));
        $statusRaw = GetValueString($this->GetIDForIdent('SystemStatus'));
        $flowRaw = GetValueString($this->GetIDForIdent('FlowState'));
        $alarms = GetValueString($this->GetIDForIdent('Alarms'));
        $rows = [
            'Gerätestatus (Rohcode)' => $statusRaw !== '' ? $statusRaw : 'Unbekannt',
            'Durchfluss (Rohcode)' => $flowRaw !== '' ? $flowRaw : 'Unbekannt',
            'pH-Dosierleistung' => GetValueInteger($this->GetIDForIdent('PHDoseRate')) . ' %',
            'Redox-Dosierleistung' => GetValueInteger($this->GetIDForIdent('RedoxDoseRate')) . ' %',
            'pH-Regelung (Rohcode)' => GetValueString($this->GetIDForIdent('PHEnabled')) ?: 'Unbekannt',
            'Redox-Regelung (Rohcode)' => GetValueString($this->GetIDForIdent('RedoxEnabled')) ?: 'Unbekannt',
            'pH-Kanister (Rohcode)' => GetValueString($this->GetIDForIdent('PHCanister')) ?: 'Unbekannt',
            'Redox-Kanister (Rohcode)' => GetValueString($this->GetIDForIdent('RedoxCanister')) ?: 'Unbekannt',
            'Firmware' => GetValueString($this->GetIDForIdent('Firmware')) ?: 'Unbekannt',
        ];
        $details = '';
        foreach ($rows as $label => $value) {
            $details .= '<div class="line"><span>' . $esc($label) . '</span><b>' . $esc($value) . '</b></div>';
        }
        $alarmText = $alarms !== '' ? $alarms : 'Noch keine Alarmdaten empfangen';
        $productImage = 'https://www.bayrol.de/sites/bayrol/files/product_file/2025-06/Product-Visual_Automatic-Cl-pH_BAYROL-Technik_0.png';
        $css = <<<'CSS'
/* Responsive HTMLBox: viewport follows the available Symcon tile width. */
*,*::before,*::after{box-sizing:border-box}
html{font-size:16px}
body{margin:0;padding:clamp(6px,2vw,20px);background:var(--bg);color:var(--ink);font:clamp(12px,1.4vw,14px)/1.45 -apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif;overflow-wrap:anywhere}
.light{--bg:#f3f7fa;--ink:#152e42;--muted:#71879b;--tile:#fff;--line:#dce7ef;--hero:#e6f3f7;--accent:#078faa;--ok:#099876;--shadow:0 4px 16px rgba(28,63,88,.04)}.dark{--bg:#0b1521;--ink:#edf6fc;--muted:#92a9ba;--tile:#152a3b;--line:#2b4658;--hero:#1b3545;--accent:#5bdac7;--ok:#62ddad;--shadow:none}
.shell{width:100%;max-width:1440px;min-width:0;margin-inline:auto}
.header{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px 16px;margin-bottom:clamp(10px,2vw,20px)}
.header>div{flex:1 1 220px;min-width:0}
.header h1{font-size:clamp(18px,2.8vw,28px);line-height:1.2;letter-spacing:-.4px;margin:0;font-weight:780}
.subtitle{font-size:clamp(11px,1.3vw,13px);color:var(--muted);margin-top:4px}
.pill{display:inline-flex;max-width:100%;gap:6px;align-items:center;padding:7px 10px;border-radius:30px;background:var(--tile);border:1px solid var(--line);color:var(--ok);font-size:12px;font-weight:750}
.dot{width:7px;height:7px;flex:none;border-radius:50%;background:currentColor}
.hero{display:flex;flex-wrap:wrap;align-items:center;gap:clamp(10px,3vw,28px);background:var(--hero);border-radius:15px;padding:clamp(10px,2vw,24px);margin-bottom:clamp(10px,1.5vw,18px)}
.hero>div:last-child{flex:1 1 140px;min-width:0}
.device{width:clamp(64px,12vw,110px);max-width:100%;height:clamp(80px,14vw,124px);flex:0 1 auto;display:flex;justify-content:center;align-items:center}
.device img{display:block;max-width:100%;max-height:100%;object-fit:contain;filter:drop-shadow(0 9px 12px #172e3b24)}
.hero-sub{font-size:11px;font-weight:750;color:var(--muted);letter-spacing:1px}
.hero-title{font-size:clamp(20px,3vw,32px);line-height:1.2;font-weight:800;letter-spacing:-.4px;margin:5px 0}
.hero-foot{color:var(--accent);font-size:clamp(12px,1.4vw,14px);font-weight:700}
.metrics{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,190px),1fr));gap:clamp(8px,1.5vw,18px)}
.metric{background:var(--tile);border:1px solid var(--line);box-shadow:var(--shadow);border-radius:14px;padding:clamp(12px,2vw,24px);min-width:0}
.metric-top{display:flex;align-items:center;gap:7px;color:var(--muted);font-weight:600;font-size:clamp(12px,1.4vw,15px)}
.metric-label{min-width:0}
.metric-icon{flex:none;color:#04a9c8;font-size:clamp(20px,2.5vw,28px);line-height:1}
.metric.rx .metric-icon{color:#ff8659}.metric.temp .metric-icon{color:#31a9e6}
.number{display:flex;flex-wrap:wrap;align-items:baseline;gap:4px 8px;margin:clamp(12px,2vw,24px) 0 10px;font-size:clamp(28px,4vw,46px);line-height:1.2;font-weight:800;letter-spacing:-.8px}
.number small{font-size:13px;letter-spacing:0;color:var(--muted);font-weight:600}
.quality{font-size:12px;font-weight:650;color:var(--ok)}.quality.stale{color:#d99135}.quality span{color:var(--muted);font-weight:400}
.strip{display:flex;flex-wrap:wrap;gap:8px 20px;margin:clamp(10px,2vw,20px) 2px;color:var(--muted);font-size:12px}.strip>span{min-width:0}.strip strong{color:var(--ink);margin-left:4px}
details{background:var(--tile);border:1px solid var(--line);border-radius:13px;min-width:0}
summary{list-style:none;padding:clamp(12px,2vw,20px);min-height:44px;display:flex;gap:12px;justify-content:space-between;align-items:center;font-size:clamp(12px,1.5vw,15px);font-weight:750;cursor:pointer;border-radius:13px}
summary::-webkit-details-marker{display:none}summary:focus-visible{outline:2px solid var(--accent);outline-offset:2px}
summary>span:first-child{min-width:0}.chevron{flex:none;font-size:18px;color:var(--accent)}details[open] .chevron{transform:rotate(180deg)}
.contents{border-top:1px solid var(--line);padding:7px clamp(12px,2vw,20px) 16px}
.line{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);align-items:start;gap:6px 16px;padding:10px 0;border-bottom:1px solid var(--line);font-size:12px}.line>*{min-width:0}.line span{color:var(--muted)}.line b{text-align:right;overflow-wrap:anywhere}
.alarm{margin-top:12px;font-size:12px;color:var(--muted);white-space:pre-wrap;overflow-wrap:anywhere}.notice{font-size:11px;color:var(--muted);margin-top:10px}
@media(max-width:360px){.line{grid-template-columns:minmax(0,1fr)}.line b{text-align:left}.header>div{flex-basis:100%}}
@media(min-width:1000px){.line{grid-template-columns:minmax(0,2fr) minmax(0,3fr)}}
CSS;
        $onlineText = $online ? 'MQTT verbunden' : 'Nicht verbunden';
        $phDose = GetValueInteger($this->GetIDForIdent('PHDoseRate'));
        $rxDose = GetValueInteger($this->GetIDForIdent('RedoxDoseRate'));
        $html = '<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>' . $css . '</style></head><body class="' . $theme . '"><main class="shell">'
            . '<header class="header"><div><h1>Bayrol Automatic Cl-pH</h1><div class="subtitle">Poolübersicht · IP-Symcon</div></div><span class="pill"><span class="dot"></span>' . $onlineText . '</span></header>'
            . '<section class="hero"><div class="device"><img src="' . $esc($productImage) . '" alt="Bayrol Automatic Cl-pH" loading="lazy"></div><div><div class="hero-sub">BAYROL AUTOMATIC</div><div class="hero-title">Wasserqualität</div><div class="hero-foot">● ' . ($online ? 'Anlage verbunden' : 'Keine MQTT-Verbindung') . '</div></div></section>'
            . '<section class="metrics">' . $cards . '</section><div class="strip"><span>pH-Dosierung <strong>' . $phDose . ' %</strong></span><span>Redox-Dosierung <strong>' . $rxDose . ' %</strong></span></div>'
            . '<details><summary><span>⚙ &nbsp; Anlagendetails anzeigen</span><span class="chevron">⌄</span></summary><div class="contents">' . $details . '<div class="alarm"><strong>Alarme &amp; Meldungen</strong><div>' . $esc($alarmText) . '</div></div><div class="notice">Nur Anzeige · Unbekannte Statuscodes bleiben unverändert · Keine Dosiersteuerung</div></div></details>'
            . '</main></body></html>';
        $this->SetValue('Dashboard', $html);
    }

 }
