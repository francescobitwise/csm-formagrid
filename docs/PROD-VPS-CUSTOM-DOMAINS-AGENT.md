## Guida operativa (Agent) — Domini custom + HTTPS automatico su VPS

### Obiettivo
Attivare domini personalizzati per tenant premium **solo in HTTPS** con provisioning automatico (Nginx + Let’s Encrypt) quando l’admin preme **“Verifica”** nel profilo tenant.

### Contesto applicativo (codice già presente)
- **UI**: `resources/views/tenant/admin/profile.blade.php`
  - sezione “Dominio personalizzato”
  - pulsante **Verifica** per ciascun dominio
  - mostra esito DNS/SSL/HTTP.
- **Controller**: `app/Http/Controllers/Tenant/Admin/TenantProfileController.php`
  - `addCustomDomain()`: salva il dominio su tabella `domains` (stancl/tenancy)
  - `checkCustomDomain()`: esegue check DNS/HTTP e, se DNS ok, tenta provisioning SSL automaticamente
  - helper `tryProvisionSsl()` lancia lo script sul server.
- **Service**: `app/Services/CustomDomainService.php`
  - DNS: `dns_get_record` su A/CNAME
  - HTTP: probe su `https://{domain}/.well-known/tenant-domain-check` (fallback http solo come diagnostica)
  - valida che `tenant_id` risponda correttamente.
- **Endpoint check**: `routes/tenant.php` → `GET /.well-known/tenant-domain-check` (protetto da header token)

### Variabili `.env` richieste (PRODUZIONE)
Da impostare sul VPS nel `.env` dell’app:

```env
# Verifica DNS dal pannello (metti IP pubblico VPS)
CUSTOM_DOMAIN_TARGET_IP=XXX.XXX.XXX.XXX

# Alternativa se preferisci validare CNAME
# CUSTOM_DOMAIN_TARGET_HOST=app.tuodominio.it

# Provisioning automatico SSL quando l’admin preme “Verifica”
CUSTOM_DOMAIN_PROVISIONING_ENABLED=true
CUSTOM_DOMAIN_PROVISION_SCRIPT=/usr/local/bin/provision-tenant-domain
CUSTOM_DOMAIN_PROVISION_USE_SUDO=true
CUSTOM_DOMAIN_PROVISION_TIMEOUT=180
```

Note:
- `CUSTOM_DOMAIN_TARGET_IP` è il modo più semplice: il cliente fa `A record` verso IP VPS.
- Se usi CNAME, valorizza `CUSTOM_DOMAIN_TARGET_HOST` e documenta al cliente il target.

### Prerequisiti VPS (da verificare/installare)
Assumendo Ubuntu/Debian + Nginx + PHP-FPM.

- **Nginx** attivo e serve già l’app sul dominio principale.
- **Certbot** installato con plugin nginx.
  - Verifica: `certbot --version` e `certbot plugins` (deve esserci `nginx`)
- **Script provisioning** installato in path assoluto:
  - sorgente repo: `scripts/provision-tenant-domain.sh`
  - destinazione VPS: `/usr/local/bin/provision-tenant-domain`
  - permessi: `chmod +x /usr/local/bin/provision-tenant-domain`
  - adattare nel file:
    - `APP_ROOT=/var/www/e-learning` (o path reale)
    - `PHP_FPM_SOCKET=/run/php/phpX.Y-fpm.sock` (socket reale)
    - confermare layout nginx `sites-available/sites-enabled` (o adattare se diverso)

### Ubuntu (copy/paste) — install, firewall, PHP-FPM socket

> Obiettivo: avere Nginx + PHP-FPM + certbot nginx plugin pronti per emettere certificati per domini custom.

#### Installazione pacchetti (Ubuntu)

```bash
sudo apt update
sudo apt install -y nginx
sudo apt install -y certbot python3-certbot-nginx

# utili per debug DNS
sudo apt install -y dnsutils
```

#### Firewall (UFW) — porte 80/443

Se usi UFW:

```bash
sudo ufw allow 'Nginx Full'
sudo ufw status
```

In alternativa (se non usi UFW), verificare che il security group / firewall del provider esponga 80 e 443.

#### PHP-FPM socket (php8.*-fpm)

1) Elenca servizi fpm:

```bash
systemctl list-units --type=service | grep php8 | grep fpm
```

2) Trova socket disponibili:

```bash
ls -la /run/php/ | grep fpm
```

Esempi tipici:
- `/run/php/php8.2-fpm.sock`
- `/run/php/php8.3-fpm.sock`

3) Aggiorna lo script `provision-tenant-domain` (`PHP_FPM_SOCKET=...`) di conseguenza.

#### Certbot sanity check

```bash
sudo nginx -t
sudo systemctl reload nginx
sudo certbot --version
sudo certbot plugins
```

#### Install script provisioning (sul VPS)

```bash
sudo cp /var/www/e-learning/scripts/provision-tenant-domain.sh /usr/local/bin/provision-tenant-domain
sudo chmod +x /usr/local/bin/provision-tenant-domain
sudo nano /usr/local/bin/provision-tenant-domain
```

Nel file, impostare:
- `APP_ROOT` (path progetto, es. `/var/www/e-learning`)
- `PHP_FPM_SOCKET` (socket reale, es. `/run/php/php8.3-fpm.sock`)

Poi:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

#### Sudoers (www-data) per provisioning

```bash
echo "www-data ALL=(root) NOPASSWD: /usr/local/bin/provision-tenant-domain *" | sudo tee /etc/sudoers.d/tenant-domain-provision
sudo chmod 440 /etc/sudoers.d/tenant-domain-provision
sudo visudo -cf /etc/sudoers.d/tenant-domain-provision
sudo -l -U www-data
```

#### Test manuale (prima del pannello)

Dopo che il DNS del dominio punta al VPS:

```bash
sudo /usr/local/bin/provision-tenant-domain academy.cliente.it
curl -sS https://academy.cliente.it/.well-known/tenant-domain-check -H "X-Tenant-Domain-Check: $CUSTOM_DOMAIN_CHECK_TOKEN" | head
```

### Sudo “safe” (fondamentale)
Il controller esegue lo script con `sudo -- …`.
Serve una regola dedicata per l’utente del web server (tipicamente `www-data`).

Creare `/etc/sudoers.d/tenant-domain-provision`:

```text
www-data ALL=(root) NOPASSWD: /usr/local/bin/provision-tenant-domain *
```

Poi:
- `chmod 440 /etc/sudoers.d/tenant-domain-provision`
- valida: `sudo -l -U www-data`

Vincolo:
- NON dare `NOPASSWD: ALL`.
- consentire **solo** questo comando.

### Nginx: requisiti per wildcard e custom
- Per subdomini “standard” puoi usare wildcard (es. `*.tuodominio.it`) con cert wildcard.
- Per domini custom cliente: ogni dominio necessita certificato dedicato (Let’s Encrypt per dominio).
- Lo script provisioning crea un vhost per ogni dominio.

### Workflow end-to-end (da testare)
1) Scegli un tenant premium (quota custom_domain=true).
2) Nel profilo tenant, aggiungi `academy.cliente.it` (o dominio test).
3) Configura DNS del dominio verso VPS:
   - A record `academy` → `IP_VPS`
4) Attendi propagazione.
5) Nel profilo tenant, premi **Verifica** sul dominio:
   - Atteso: DNS OK
   - Provisioning SSL parte automaticamente
  - HTTP/HTTPS: deve risultare OK su `https://academy.cliente.it/.well-known/tenant-domain-check` con `tenant_id` corretto.
6) Apri `https://academy.cliente.it/login` e verifica che sia lo stesso tenant.

### Diagnostica rapida se fallisce
- **DNS non ok**:
  - controllare A/CNAME reali dal VPS:
    - `dig +short A academy.cliente.it`
    - `dig +short CNAME academy.cliente.it`
  - confermare `CUSTOM_DOMAIN_TARGET_IP` o `CUSTOM_DOMAIN_TARGET_HOST` nel `.env`.
- **Provisioning SSL fallito**:
  - eseguire manualmente sul VPS:
    - `sudo /usr/local/bin/provision-tenant-domain academy.cliente.it`
  - controllare output `nginx -t`, `systemctl status nginx`
  - controllare `certbot`:
    - rate limits / challenge fail / porta 80 bloccata / DNS non propagato
  - verificare firewall: 80 e 443 aperte.
- **HTTP ok ma tenant sbagliato**:
  - dominio associato al tenant sbagliato in tabella `domains`
  - caching DNS / CDN / reverse proxy.
- **/.well-known/tenant-domain-check non raggiungibile**:
  - Nginx vhost non caricato o root sbagliato
  - app non raggiungibile su quel server_name.

### Sicurezza / note importanti
- Lo script crea file di config Nginx basati su input dominio: assicurarsi che il controller normalizzi e validi l’host (già fatto).
- L’endpoint di check non espone dati sensibili ed è protetto da un token su header; mantenerlo attivo solo per la verifica domini.
- Considerare rate limiting sull’azione “Verifica” se diventa abuso (oggi è dietro area admin, ma può essere cliccato spesso).

### Deliverable richiesti (cosa l’agent deve restituire)
- Conferma esatta del path progetto su VPS e socket PHP-FPM.
- File Nginx generati e dove finiscono.
- Output di test end-to-end su un dominio di staging.
- Eventuali modifiche allo script (adattamento distro/layout).

