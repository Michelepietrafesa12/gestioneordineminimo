# Ordine Minimo IVA Inclusa - PrestaShop Module

Modulo PrestaShop 1.7+ per la gestione dell'ordine minimo con calcolo IVA inclusa, barra di progresso e prodotti consigliati intelligenti.

## Caratteristiche

### Funzionalita Principali
- **Ordine Minimo**: Imposta un importo minimo per completare l'acquisto
- **Calcolo IVA Inclusa**: L'importo minimo viene calcolato sui prezzi IVA inclusa
- **Barra di Progresso**: Mostra visivamente quanto manca per raggiungere l'ordine minimo
- **Blocco Checkout**: Impedisce l'accesso al checkout se l'ordine minimo non e raggiunto

### Prodotti Consigliati Intelligenti
- **Collaborative Filtering**: Suggerisce prodotti acquistati insieme da altri clienti
- **Bestseller**: Mostra i prodotti piu venduti come fallback
- **Ordinamento Smart**: I prodotti sono ordinati per vicinanza all'importo mancante
- **Badge "Piu acquistato"**: Evidenzia i bestseller con un badge rosso
- **Badge "Raggiungi la soglia"**: Evidenzia i prodotti che permettono di raggiungere l'ordine minimo

### Prezzi e Sconti
- Visualizza il prezzo scontato e il prezzo originale barrato
- Supporto completo per le regole di sconto di PrestaShop

### Design Responsive
- Layout ottimizzato per desktop e mobile
- Scroll orizzontale su dispositivi mobili
- Bottone "Aggiungi" con testo visibile su mobile

### Personalizzazione Colori (11 opzioni)
Tutti i colori sono personalizzabili dal pannello di amministrazione:
- Colore primario (barra/successo)
- Colore secondario (prezzi)
- Colore avviso
- Sfondo barra progresso
- Colore pulsante
- Colore testo pulsante
- Colore badge bestseller
- Colore prezzo barrato
- Colore testo
- Sfondo schede prodotto
- Bordo schede prodotto

## Installazione

1. Scarica il modulo
2. Vai su **Moduli > Gestione moduli** nel back-office PrestaShop
3. Clicca su **Carica un modulo** e seleziona il file ZIP
4. Configura il modulo dalle impostazioni

## Configurazione

### Impostazioni Principali
| Opzione | Descrizione |
|---------|-------------|
| Importo spedizione gratuita | Importo per ottenere la spedizione gratuita |
| Importo ordine minimo | Importo minimo richiesto per completare l'ordine |
| Mostra barra progresso | Abilita/disabilita la barra di progresso |
| Usa prezzi IVA inclusa | Calcola l'ordine minimo sui prezzi IVA inclusa |
| Mostra prodotti consigliati | Abilita/disabilita i prodotti consigliati |
| Numero prodotti | Numero di prodotti da mostrare (1-8) |

### Personalizzazione Colori
Accedi alla sezione **Personalizzazione Colori** nella configurazione del modulo per modificare tutti i colori dell'interfaccia.

## Compatibilita

- **PrestaShop**: 1.7.0.0 - 8.x
- **PHP**: 7.2+
- **Gateway di Pagamento**: PayPal, Nexi, Stripe, Braintree, Mollie, Adyen, Klarna, Satispay, Scalapay

## Sicurezza

Il modulo e stato sviluppato seguendo le best practice di sicurezza:

- **Protezione SQL Injection**: Tutte le query usano prepared statements e casting intval
- **Protezione XSS**: Output escapato nei template Smarty
- **Protezione Gateway Pagamenti**: I callback dei gateway di pagamento non vengono mai bloccati
- **Validazione Input**: Tutti gli input utente sono validati e sanitizzati

### Gateway di Pagamento Protetti
Il modulo riconosce e non interferisce con i seguenti gateway:
- PayPal (IPN, Return, Cancel)
- Nexi XPay
- Stripe (Webhook, Return)
- Braintree
- Mollie
- Adyen
- Klarna
- Satispay
- Scalapay

## Struttura File

```
minordertaxincluded/
├── config.xml                 # Configurazione modulo
├── index.php                  # Security file
├── logo.svg                   # Logo modulo
├── minordertaxincluded.php    # File principale
├── controllers/
│   └── front/
│       └── ajax.php           # Controller AJAX
├── translations/              # Traduzioni
├── views/
│   ├── css/
│   │   └── minordertaxincluded.css
│   ├── js/
│   │   └── minordertaxincluded.js
│   └── templates/
│       └── hook/
│           ├── min_order_progress.tpl
│           ├── banner.tpl
│           └── payment_block.tpl
```

## Hooks Utilizzati

- `displayShoppingCartFooter` - Barra progresso nel carrello
- `displayHeader` - CSS e JavaScript
- `actionFrontControllerSetMedia` - Blocco checkout
- `displayPaymentTop` - Avviso nella pagina pagamento
- `actionCarrierProcess` - Blocco selezione corriere

## Changelog

### v1.12.0
- Aggiunto colore testo pulsante personalizzabile
- Review finale per produzione

### v1.11.0
- Aggiunti 5 nuovi colori personalizzabili
- Separazione form impostazioni e colori

### v1.10.0
- Aggiunto badge "Piu acquistato"
- Aggiunto supporto prezzi scontati con prezzo barrato

### v1.9.0
- Implementato collaborative filtering per prodotti consigliati
- Ordinamento prodotti per vicinanza all'importo mancante

### v1.8.0
- Aggiunto scroll orizzontale mobile
- Testo "Aggiungi" visibile su mobile

## Licenza

Questo modulo e rilasciato sotto licenza proprietaria per uso commerciale.

## Supporto

Per assistenza o segnalazione bug, contattare lo sviluppatore.

---

**Versione**: 1.12.0
**Autore**: Developer
**Compatibilita**: PrestaShop 1.7.0.0+
