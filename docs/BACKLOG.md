# Backlog — cosa manca / da fare

Elenco **operativo** di lavori non ancora chiusi o da validare end-to-end. Per lo stato dell’implementazione nel codice vedere [`CURRENT_STATE.md`](CURRENT_STATE.md); per la visione lunga [`CONTEXT.md`](../CONTEXT.md).

**Come usarlo:** aggiungere voci quando emergono gap; spuntare o spostare in `CURRENT_STATE.md` quando una epica è davvero completata in produzione. Aggiornare la data in fondo a ogni modifica sostanziale.

---

## Certificazioni / attestati

- [ ] **Modello dati**: definire dove vivono certificati (tenant DB: tabella dedicata? legame enrollment/corso/completamento?).
- [ ] **Regole di emissione**: solo corso completato al 100%? scadenza? rinnovi?
- [ ] **Generazione PDF**: template brandizzabile per tenant, firma/QR opzionale, storage su `MEDIA_DISK`.
- [ ] **UI learner**: download / elenco certificati ottenuti.
- [ ] **UI admin**: anteprima, revoca, rigenerazione (se previsto).

---

## Billing / SaaS

- [ ] **Stripe / Cashier**: (legacy SaaS) modello subscription tenant.
- [ ] **Portale fatture / customer portal**: completare flussi dove ancora placeholder o mancanti.
- [ ] **Webhook / sync**: stato subscription → feature flags tenant.

---

## Qualità, sicurezza, osservabilità

- [ ] **Test automatici**: copertura critica (auth tenant, progressi video/SCORM, iscrizioni).
- [ ] **SCORM reale**: validazione su pacchetti 1.2 / 2004 da clienti (non solo happy path API).
- [ ] **Monitoraggio**: errori queue, job video, rate limit API se esposte.

---

## Funzionalità prodotto (da CONTEXT o mercato)

- [ ] **Ricerca corsi (MeiliSearch o DB)**: se in roadmap, integrazione UI catalogo.
- [ ] **UX learner (player)**: fullscreen, mobile e permessi iframe SCORM dove necessario.
- [ ] **Notifiche**: promemoria scadenze corsi, completamento, ecc. (se in scope).

---

## Idee / nice-to-have

_Spazio per voci non prioritarie; spostare in sezioni sopra quando diventano decisioni._

- (nessuna voce)

---

*Creato: 2026-04-06 · Ultimo aggiornamento: 2026-04-06.*
