# Guida: audit staff, health sintetico e compliance

Documento di riferimento per cosa è stato implementato, cosa resta opzionale e come operare in produzione.

## 1. Audit log azioni staff (tenant)

### Obiettivo
Tracciare le azioni rilevanti dello staff nell’area admin del tenant (mutazioni HTTP e download sensibili), con metadati sanitizzati e IP/user agent, per responsabilità interna e supporto incidenti.

### Implementato
- Tabella tenant `staff_audit_logs` (UUID, `user_id`, `route_name`, `http_method`, `path`, `ip_address`, `user_agent`, `response_status`, `metadata` JSON, `created_at`).
- Middleware terminabile `LogTenantStaffAudit` sulla prefix `admin` (dopo `auth` + staff): registra `POST`/`PUT`/`PATCH`/`DELETE` e `GET` su export PDF fatture / elenco learner PDF.
- I campi sensibili (`password`, `token`, `secret`, `_token`, ecc.) non vengono salvati nei `metadata`.
- Pagina **Admin → Registro attività** (`tenant.admin.audit-log.index`), permesso `audit.view` (solo ruoli con mappatura; gli Admin hanno `*`).

### Operazioni
- Dopo deploy: `php artisan tenants:migrate` (o il comando che usate per migrare i DB tenant).
- **Retention automatica**: comando `php artisan tenants:prune-staff-audit-logs` (schedulato ogni giorno alle 03:15). Giorni configurabili con `STAFF_AUDIT_LOG_RETENTION_DAYS` (default 365 in `config/audit.php`); valore `0` disattiva l’eliminazione. Override una tantum: `--days=N`.
- In produzione assicurarsi che il **scheduler** Laravel sia attivo (`* * * * * php artisan schedule:run`).

### Estensioni future (opzionali)
- Export CSV del registro per audit esterno.
- Campi `subject_type` / `subject_id` valorizzati esplicitamente nei controller per azioni critiche.
- Stream verso SIEM.

---

## 2. Health sintetico (central)

### Obiettivo
Nella console centrale **Organizzazioni**, mostrare uno stato compatto per tenant: DB raggiungibile, conteggi learner/staff, processing media in errore, stima storage media (se misurabile).

### Implementato
- Servizio `TenantOperationalHealthService::snapshot()` eseguito nel contesto `$tenant->run()`.
- Colonna **Stato** nella tabella tenant con livello `ok` / `attenzione` / `errore` e dettaglio testuale breve.

### Operazioni
- La misura storage può essere costosa con molti file su S3; oltre la soglia interna del servizio media resta “sconosciuta” senza bloccare la pagina.
- Se la lista tenant è molto grande, valutare cache TTL per snapshot o pagina dedicata per tenant.

### Estensioni future
- Ultimo errore job video/SCORM (tabella dedicata o log).
- Webhook di allerta se `errore` per più di N ore.

---

## 3. Gestione compliance (tenant)

### Obiettivo
Fornire all’admin un punto unico per export portability minimale dei dati trattati nel LMS (allievi + iscrizioni) e registro interno delle richieste degli interessati ricevute fuori piattaforma.

### Implementato
- Pagina **Admin → Compliance** (`compliance.manage`):
  - **Export portability (ZIP)**: CSV `learners.csv` + `enrollments.csv` (dati LMS; throttle).
  - **Registro richieste interessati**: form per annotare richieste ricevute (email, tipo, messaggio) e tabella ultimi record (`privacy_contact_requests`).
  - **Stati richiesta**: Nuova, In lavorazione, Chiusa, Respinta/archiviata; aggiornamento da UI con `status_updated_at`.

### Operazioni
- L’export non sostituisce una procedura legale completa: è una base tecnica per portability; DPA e valutazioni restano responsabilità del titolare.

### Estensioni future
- Job in coda + notifica email quando export grandi.
- Portale pubblico “Privacy” per richieste senza login (con CAPTCHA e rate limit).
- Workflow approvazione cancellazione (erasure) con checklist.

---

## Ordine di implementazione (sviluppo)

1. Guida (questo file).
2. Audit log (schema + middleware + UI).
3. Health central (servizio + UI tabella).
4. Compliance (schema richieste + hub + export).

## Checklist deploy

- [ ] `php artisan migrate` (landlord, se necessario).
- [ ] `php artisan tenants:migrate` per applicare migrazioni tenant `2026_04_12_000001_*`, `000002_*` e `000003_*` (`status_updated_at` su richieste privacy).
- [ ] Verificare permessi ruolo (istruttori: nessun accesso audit/compliance di default).
