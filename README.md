# Wedding Photo Uploader 📸

Un sistema web semplice ed elegante per la condivisione di foto di matrimonio, che permette agli ospiti di caricare e visualizzare le loro foto dell'evento.

## 🌟 Caratteristiche

- **Upload multiplo di foto**: Gli ospiti possono caricare più foto contemporaneamente
- **Generazione automatica di miniature**: Ottimizzazione automatica delle immagini per la visualizzazione web
- **Sistema di identificazione ospiti**: Ogni ospite ha un ID univoco per organizzare le foto
- **Interfaccia responsive**: Funziona perfettamente su desktop, tablet e smartphone
- **Galleria foto**: Visualizzazione delle foto caricate con possibilità di download
- **Supporto formati multipli**: JPG, JPEG, PNG, GIF, WebP, BMP, HEIC

## 🚀 Installazione

### Prerequisiti

- Server web con PHP 7.4 o superiore
- ImageMagick (per la generazione delle miniature)
- Permessi di scrittura per le cartelle dell'applicazione

### Passaggi

Copia il progetto nella directory del tuo server web e assicurati che tutto funzioni correttamente 😎

## ⚙️ Configurazione

Modifica il file `config.php` per personalizzare:

```php
$TITLE = 'Matrimonio di Luca e Giorgia - Album Foto'; // Titolo dell'evento
$THUMB_MAX_WIDTH = 1024;    // Larghezza massima miniature
$THUMB_MAX_HEIGHT = 1024;   // Altezza massima miniature
$THUMB_QUALITY = 80;        // Qualità JPEG delle miniature (1-100)
```

## 🛠️ Manutenzione

### Generazione miniature
Esegui periodicamente lo script per processare le nuove foto:
```bash
php thumbgenerator.php
```

### Backup
Assicurati di fare backup regolari delle directory:
- `names/` - Nomi degli ospiti
- `uploads/` - Foto originali
- `thumbs/` - Miniature (opzionale, rigenerabili)

## 🎨 Personalizzazione

- **Grafica**: Sostituisci `header.png` e `footer.png` con le tue immagini
- **Stili**: Modifica il CSS inline in `index.php` e `list.php`
- **Titolo**: Cambia `$TITLE` in `config.php`

## 📱 Compatibilità

- **Browser**: Tutti i browser moderni (Chrome, Firefox, Safari, Edge)
- **Dispositivi**: Desktop, tablet, smartphone
- **Server**: Apache, Nginx, server PHP integrato

## 📄 Licenza

Questo progetto è fornito "così com'è" per uso personale. Modifica e distribuisci liberamente secondo le tue necessità.

## 🤝 Supporto

Per problemi o domande:
1. Controlla i log del server web
2. Verifica i permessi delle directory
3. Assicurati che ImageMagick sia installato correttamente

---

*Creato con ❤️ per rendere speciali i ricordi del matrimonio*