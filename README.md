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

### Setup rapido

1. **Clona o scarica il progetto**
   ```bash
   git clone <repository-url>
   cd wedding-photo-uploader
   ```

2. **Configura i permessi**
   ```bash
   chmod 755 .
   chmod 777 names/ uploads/ thumbs/
   ```

3. **Installa ImageMagick** (se non già presente)
   ```bash
   # Ubuntu/Debian
   sudo apt-get install imagemagick
   
   # macOS
   brew install imagemagick
   
   # CentOS/RHEL
   sudo yum install ImageMagick
   ```

4. **Configura il server web** per puntare alla directory del progetto

## ⚙️ Configurazione

Modifica il file `config.php` per personalizzare:

```php
$TITLE = 'Matrimonio di Luca e Giorgia - Album Foto'; // Titolo dell'evento
$THUMB_MAX_WIDTH = 1024;    // Larghezza massima miniature
$THUMB_MAX_HEIGHT = 1024;   // Altezza massima miniature
$THUMB_QUALITY = 80;        // Qualità JPEG delle miniature (1-100)
```

## 📁 Struttura del progetto

```
wedding-photo-uploader/
├── index.php              # Pagina principale per upload foto
├── list.php              # Galleria per visualizzare tutte le foto
├── config.php            # File di configurazione
├── thumbgenerator.php    # Script per generare miniature
├── header.png            # Header grafico
├── footer.png            # Footer grafico
├── placeholder.jpg       # Immagine placeholder
├── names/                # Directory per i nomi degli ospiti
├── uploads/              # Directory per le foto originali
└── thumbs/               # Directory per le miniature
```

## 🎯 Utilizzo

### Per gli ospiti

1. **Accesso**: Visitare l'URL del sito
2. **Inserimento nome**: Digitare il proprio nome per identificarsi
3. **Upload foto**: Selezionare e caricare le foto dell'evento
4. **Visualizzazione**: Vedere le proprie foto caricate nella galleria personale

### Per gli amministratori

1. **Visualizzazione completa**: Accedere a `list.php` per vedere tutte le foto di tutti gli ospiti
2. **Generazione miniature**: Eseguire `thumbgenerator.php` per processare le nuove foto
3. **Download**: Scaricare le foto originali direttamente dalla galleria

## 🔧 Funzionalità tecniche

### Sistema di identificazione
- Ogni ospite riceve un ID univoco di 12 caratteri
- L'ID viene salvato in un cookie per le sessioni successive
- Il nome dell'ospite viene associato all'ID nella directory `names/`

### Gestione file
- Le foto vengono rinominate con il formato: `{guest_id}_{nome_originale}`
- Controllo automatico dei duplicati
- Supporto per formati immagine multipli

### Ottimizzazione immagini
- Generazione automatica di miniature con ImageMagick
- Compressione intelligente per ridurre i tempi di caricamento
- Fallback alle immagini originali se le miniature non sono disponibili

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

## 🔒 Sicurezza

- Validazione dei tipi di file
- Controllo delle dimensioni dei file
- Sanitizzazione dei nomi dei file
- Protezione contro upload di file eseguibili

## 📄 Licenza

Questo progetto è fornito "così com'è" per uso personale. Modifica e distribuisci liberamente secondo le tue necessità.

## 🤝 Supporto

Per problemi o domande:
1. Controlla i log del server web
2. Verifica i permessi delle directory
3. Assicurati che ImageMagick sia installato correttamente

---

*Creato con ❤️ per rendere speciali i ricordi del matrimonio*