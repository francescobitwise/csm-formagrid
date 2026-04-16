## Domini personalizzati (piano premium)

Questa piattaforma supporta:

- **dominio standard**: `{tenantId}.{CENTRAL_DOMAIN}` (es. `acme.e-learning.it`)
- **dominio premium (custom)**: un dominio del cliente (es. `academy.cliente.it`)

### Istruzioni per il cliente (DNS)

Scegli una delle 2 opzioni:

1) **Record A (consigliato)**
- Crea: `academy.cliente.it` → **A** → `IP_VPS`

2) **Record CNAME (se richiesto)**
- Crea: `academy.cliente.it` → **CNAME** → `TARGET_HOST`

Note:
- Non inserire `https://` nei record DNS.
- La propagazione può richiedere da pochi minuti a diverse ore (dipende dal TTL/provider DNS).

### Verifica dal pannello

Nel profilo tenant (Admin → Profilo → Dominio personalizzato):
- aggiungi il dominio
- premi **Verifica**

La verifica controlla:
- DNS: che il dominio punti al server (A/CNAME)
- **HTTPS obbligatorio**: se il DNS è OK, il server prova ad attivare automaticamente il certificato SSL (Let’s Encrypt)
- HTTP/HTTPS: che `/.well-known/tenant-domain-check` risponda e che il tenant identificato sia quello corretto (su HTTPS)

### Provisioning SSL automatico (VPS)

Il provisioning automatico richiede:
- `certbot` installato sul server
- Nginx configurato per servire l’app Laravel
- `sudo` consentito **solo** per lo script di provisioning (NOPASSWD)

Variabili `.env` (server):

```env
# IP pubblico del VPS (per verifiche DNS)
CUSTOM_DOMAIN_TARGET_IP=203.0.113.10

# oppure, in alternativa, un hostname target (se usi CNAME)
# CUSTOM_DOMAIN_TARGET_HOST=app.tuodominio.it

# Abilitazione provisioning
CUSTOM_DOMAIN_PROVISIONING_ENABLED=true
CUSTOM_DOMAIN_PROVISION_SCRIPT=/usr/local/bin/provision-tenant-domain
CUSTOM_DOMAIN_PROVISION_USE_SUDO=true
CUSTOM_DOMAIN_PROVISION_TIMEOUT=180
```

Lo script può:
- creare/aggiornare un server block Nginx per il dominio
- richiedere certificato Let’s Encrypt
- ricaricare Nginx

### `sudo` sicuro (NOPASSWD) solo per provisioning

Esempio (Ubuntu/Debian). Crea `/etc/sudoers.d/tenant-domain-provision`:

```text
www-data ALL=(root) NOPASSWD: /usr/local/bin/provision-tenant-domain *
```

Poi:
- `chmod 440 /etc/sudoers.d/tenant-domain-provision`
- verifica: `sudo -l -U www-data`

### Installazione script

Nel server:
- copia `scripts/provision-tenant-domain.sh` in `/usr/local/bin/provision-tenant-domain`
- `chmod +x /usr/local/bin/provision-tenant-domain`
- apri lo script e imposta `APP_ROOT` e `PHP_FPM_SOCKET`

