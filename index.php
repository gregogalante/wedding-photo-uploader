<?php

require_once 'config.php';

// Function to generate UUID (random string of 12 characters)
function generateUUID() {
    return bin2hex(random_bytes(6));
}

// Function to get user photos
function getUserPhotos($guestId, $limit = 6) {
    global $UPLOAD_DIR, $THUMB_DIR, $THUMB_EXT, $ACCEPTED_EXTS;

    $photos = [];
    
    $files = scandir($UPLOAD_DIR);
    foreach ($files as $file) {
        // Skip directories
        if (in_array($file, ['.', '..'])) continue;

        // Check if file name starts with $guestId . '_'
        if (strpos($file, $guestId . '_') !== 0) continue;
        
        // Check if file extension is accepted
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, $ACCEPTED_EXTS)) continue;
        

        $original_path = $UPLOAD_DIR . $file;
        $thumb_path = $THUMB_DIR . $file . '.' . $THUMB_EXT;
        if (!file_exists($thumb_path)) {
            $thumb_path = $original_path; // Fallback to original if thumbnail doesn't exist
        }

        $photos[] = [
            'name' => $file,
            'thumb_url' => $thumb_path,
            'download_url' => $original_path,
            'time' => filemtime($original_path),
        ];
    }
    
    // Sort by upload time (newest first)
    usort($photos, function($a, $b) {
        return $b['time'] - $a['time'];
    });
    
    // Limit the number of photos
    $photos = array_slice($photos, 0, $limit);
    
    return $photos;
}

// Handle get photos request
if (isset($_GET['get_photos'])) {
    $guestId = $_COOKIE['guest_id'] ?? null;
    
    if (!$guestId) {
        echo json_encode(['success' => false, 'message' => 'Session expired, please refresh the page']);
        exit;
    }
    
    $photos = getUserPhotos($guestId);
    echo json_encode(['success' => true, 'photos' => $photos]);
    exit;
}

// Handle file upload
if (isset($_POST['upload'])) {
    $guestId = $_COOKIE['guest_id'] ?? null;
    
    if (!$guestId) {
        echo json_encode(['success' => false, 'message' => 'Session expired, please refresh the page']);
        exit;
    }
    
    $response = ['success' => true, 'files' => []];
    
    if (!empty($_FILES['photos'])) {
        $fileCount = count($_FILES['photos']['name']);
        
        for ($i = 0; $i < $fileCount; $i++) {
            $fileName = basename($_FILES['photos']['name'][$i]);
            $targetFile = $UPLOAD_DIR . $guestId . '_' . $fileName;
            
            // Check if file already exists
            if (file_exists($targetFile)) {
                $response['files'][] = [
                    'name' => $fileName,
                    'status' => 'duplicate',
                    'message' => 'Foto già caricata'
                ];
                continue;
            }
            
            // Upload file
            if (move_uploaded_file($_FILES['photos']['tmp_name'][$i], $targetFile)) {
                $response['files'][] = [
                    'name' => $fileName,
                    'status' => 'success',
                    'message' => 'Caricamento completato'
                ];
            } else {
                $response['files'][] = [
                    'name' => $fileName,
                    'status' => 'error',
                    'message' => 'Errore durante il caricamento'
                ];
            }
        }
    }
    
    // Get updated photos after upload
    $photos = getUserPhotos($guestId);
    $response['photos'] = $photos;
    
    echo json_encode($response);
    exit;
}

// Handle registration
if (isset($_POST['register'])) {
    $guestName = trim($_POST['guest_name']);
    
    if (empty($guestName)) {
        echo json_encode(['success' => false, 'message' => 'Per favore inserisci il tuo nome']);
        exit;
    }
    
    $guestId = generateUUID();

    // Save guest name to file
    file_put_contents($NAMES_DIR . $guestId . '.txt', $guestName);
    
    // Set cookie with guest ID
    setcookie('guest_id', $guestId, time() + (86400 * 30), "/"); // 30 days
    
    echo json_encode(['success' => true, 'guest_id' => $guestId, 'guest_name' => $guestName]);
    exit;
}

// Check if user is already registered
$guestId = $_COOKIE['guest_id'] ?? null;
$guestName = '';

if ($guestId && file_exists($NAMES_DIR . $guestId . '.txt')) {
    $guestName = file_get_contents($NAMES_DIR . $guestId . '.txt');
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $TITLE; ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- React -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/react/18.2.0/umd/react.production.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/react-dom/18.2.0/umd/react-dom.production.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/babel-standalone/7.21.2/babel.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/heic2any/0.0.1/index.min.js" integrity="sha512-qosaXUCrlY9MGGVtPtsP5MBN+W+R25MfvvvK0oioo9GaAHR7DgpqwVv2NlSOctIX9wcIKsSjhgrSpst1iwmjtA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&display=swap');

        .font-main {
            font-family: 'Montserrat', sans-serif;
        }
    </style>
</head>
<body class="bg-stone-50 font-main text-stone-800 min-h-screen">
    <div id="app-root"></div>
    
    <script type="text/babel">
        const { useState, useEffect, useRef } = React;
        
        function App() {
            const [isRegistered, setIsRegistered] = useState(<?php echo !empty($guestName) ? 'true' : 'false'; ?>);
            const [guestName, setGuestName] = useState('<?php echo addslashes($guestName); ?>');
            const [isSubmitting, setIsSubmitting] = useState(false);
            const [files, setFiles] = useState([]);
            const [uploadProgress, setUploadProgress] = useState({});
            const [uploadResults, setUploadResults] = useState([]);
            const [errorMessage, setErrorMessage] = useState('');
            const fileInputRef = useRef(null);
            
            const handleRegister = async (e) => {
                e.preventDefault();
                
                if (!guestName.trim()) {
                    setErrorMessage('Per favore inserisci il tuo nome');
                    return;
                }
                
                setIsSubmitting(true);
                setErrorMessage('');
                
                try {
                    const formData = new FormData();
                    formData.append('register', '1');
                    formData.append('guest_name', guestName);
                    
                    const response = await fetch('', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        setIsRegistered(true);
                    } else {
                        setErrorMessage(data.message || 'Si è verificato un errore. Riprova più tardi.');
                    }
                } catch (error) {
                    setErrorMessage('Si è verificato un errore di connessione. Riprova più tardi.');
                } finally {
                    setIsSubmitting(false);
                }
            };
            
            const handleFileChange = (e) => {
                if (e.target.files.length > 0) {
                    const selectedFiles = Array.from(e.target.files);
                    // limit selectedFiles to 10
                    if (selectedFiles.length > 10) {
                        setErrorMessage('Puoi selezionare al massimo 10 foto');
                        return;
                    }

                    setFiles(prev => [...prev, ...selectedFiles]);
                }
            };
            
            const removeFile = (index) => {
                setFiles(prev => prev.filter((_, i) => i !== index));
            };
            
            const uploadFiles = async () => {
                setIsSubmitting(true);
                setErrorMessage('');
                
                const formData = new FormData();
                formData.append('upload', '1');

                // Convert HEIC files to JPG if necessary
                let finalFiles = files
                try {
                    const heicFiles = files.filter(file => file.type === 'image/heic');
                    if (heicFiles.length > 0) {
                        for (const file of heicFiles) {
                            const blob = await heic2any({ blob: file, toType: 'image/jpeg' });
                            const newFile = new File([blob], file.name.replace(/\.heic$/i, '.jpg'), { type: 'image/jpeg' });
                            finalFiles = finalFiles.map(f => f.name === file.name ? newFile : f);
                        }
                    }
                } catch (error) {
                    console.error('Error converting HEIC files:', error);
                }
                
                finalFiles.forEach(file => {
                    formData.append('photos[]', file);
                });
                
                const xhr = new XMLHttpRequest();
                
                xhr.upload.addEventListener('progress', (event) => {
                    if (event.lengthComputable) {
                        const percentComplete = Math.round((event.loaded / event.total) * 100);
                        setUploadProgress(prev => ({...prev, total: percentComplete}));
                    }
                });
                
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        setIsSubmitting(false);

                        if (xhr.status === 200) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                if (response.success) {
                                    setUploadResults(response.files);
                                    // Rimuove i file caricati con successo o duplicati
                                    const successfulFiles = response.files
                                        .filter(f => f.status === 'success' || f.status === 'duplicate')
                                        .map(f => f.name);

                                    setFiles(prev => prev.filter(file => !successfulFiles.includes(file.name)));

                                    // Aggiorna automaticamente la lista
                                    fetchUserPhotos();
                                } else {
                                    setErrorMessage(response.message || 'Si è verificato un errore durante il caricamento.');
                                }
                            } catch (error) {
                                setErrorMessage('Si è verificato un errore durante l\'elaborazione della risposta.');
                            }
                        } else {
                            setErrorMessage('Si è verificato un errore di connessione. Riprova più tardi.');
                        }
                    }
                };
                
                xhr.open('POST', '', true);
                xhr.send(formData);
            };
            
            const clearResults = () => {
                setUploadResults([]);
                setUploadProgress({});
            };
            
            const resetUpload = () => {
                setFiles([]);
                setUploadResults([]);
                setUploadProgress({});
                setErrorMessage('');
                if (fileInputRef.current) {
                    fileInputRef.current.value = '';
                }
            };
            
            const [userPhotos, setUserPhotos] = useState([]);
            const [isLoadingPhotos, setIsLoadingPhotos] = useState(false);
            
            // Fetch user photos when registered
            useEffect(() => {
                if (isRegistered) {
                    fetchUserPhotos();
                }
            }, [isRegistered]);
            
            const fetchUserPhotos = async () => {
                setIsLoadingPhotos(true);
                try {
                    const response = await fetch('?get_photos=1');
                    const data = await response.json();
                    
                    if (data.success) {
                        setUserPhotos(data.photos);
                    }
                } catch (error) {
                    console.error('Error fetching photos:', error);
                } finally {
                    setIsLoadingPhotos(false);
                }
            };
            
            return (
                <div className="max-w-2xl mx-auto p-4 sm:p-6">
                    <header className="text-center mb-8 pt-6">
                        <img src="./header.png" alt="<?php echo $TITLE; ?>" className="w-full mx-auto mb-7 max-w-[350px]" />
                        <p className="text-lg md:text-xl text-stone-600 font-light">Il nostro album di matrimonio</p>
                    </header>
                    
                    {!isRegistered ? (
                        <div className="bg-white rounded-lg shadow-md p-6 mb-8">
                            <h2 className="text-2xl font-light mb-6 text-center">Benvenuto/a!</h2>
                            <p className="text-center mb-6">Per favore, inserisci il tuo nome per iniziare a caricare le tue foto</p>
                            
                            <form onSubmit={handleRegister}>
                                <div className="mb-4">
                                    <input
                                        type="text"
                                        className="w-full px-4 py-2 border border-stone-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="Il tuo nome"
                                        value={guestName}
                                        onChange={(e) => setGuestName(e.target.value)}
                                        disabled={isSubmitting}
                                    />
                                </div>
                                
                                {errorMessage && (
                                    <div className="mb-4 text-red-500 text-sm">{errorMessage}</div>
                                )}
                                
                                <button
                                    type="submit"
                                    className="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md transition disabled:opacity-50"
                                    disabled={isSubmitting}
                                >
                                    {isSubmitting ? 'Registrazione in corso...' : 'Inizia'}
                                </button>
                            </form>
                        </div>
                    ) : (
                        <>
                            <div className="bg-white rounded-lg shadow-md p-6 mb-8">
                                <h2 className="text-2xl font-light mb-2 text-center">Ciao, {guestName}!</h2>
                                <p className="text-center mb-6">
                                    Grazie per condividere i tuoi ricordi del nostro matrimonio
                                </p>
                                
                                <div className="mb-6">
                                    <div className="flex items-center justify-center">
                                        <label className="w-full flex flex-col items-center px-4 py-6 bg-stone-50 text-stone-700 rounded-lg tracking-wide border-2 border-dashed border-stone-300 cursor-pointer hover:bg-stone-100">
                                            <svg className="w-8 h-8 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span className="text-base font-medium">Seleziona le foto</span>
                                            <span className="text-sm text-stone-500 mt-1">o trascina i file qui (max 10 per volta)</span>
                                            <input 
                                                type="file" 
                                                className="hidden" 
                                                accept="image/*" 
                                                multiple 
                                                onChange={handleFileChange}
                                                ref={fileInputRef}
                                                disabled={isSubmitting}
                                                onClick={(e) => {
                                                    if(files.length >= 10) {
                                                        e.preventDefault();
                                                        setErrorMessage('Puoi selezionare al massimo 10 foto');
                                                    }
                                                }}
                                            />
                                        </label>
                                    </div>
                                </div>
                                
                                {files.length > 0 && (
                                    <div className="mb-6">
                                        <h3 className="font-medium mb-2">File selezionati ({files.length})</h3>
                                        <div className="space-y-2 max-h-60 overflow-y-auto">
                                            {files.map((file, index) => (
                                                <div key={index} className="flex justify-between items-center bg-stone-50 p-2 rounded">
                                                    <div className="truncate flex-1 text-sm">{file.name}</div>
                                                    <button 
                                                        className="ml-2 text-blue-600 hover:text-blue-800"
                                                        onClick={() => removeFile(index)}
                                                        disabled={isSubmitting}
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fillRule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clipRule="evenodd" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}
                                
                                {errorMessage && (
                                    <div className="mb-4 text-red-500 text-sm">{errorMessage}</div>
                                )}
                                
                                {Object.keys(uploadProgress).length > 0 && (
                                    <div className="mb-6">
                                        <h3 className="font-medium mb-2">Progresso caricamento</h3>
                                        <div className="w-full bg-stone-200 rounded-full h-2.5">
                                            <div 
                                                className="bg-blue-600 h-2.5 rounded-full" 
                                                style={{ width: `${uploadProgress.total || 0}%` }}
                                            ></div>
                                        </div>
                                        <p className="text-right text-sm text-stone-600 mt-1">{uploadProgress.total || 0}%</p>
                                    </div>
                                )}
                                
                                {uploadResults.length > 0 && (
                                    <div className="mb-6">
                                        <h3 className="font-medium mb-2">Risultati caricamento</h3>
                                        <div className="space-y-2 max-h-60 overflow-y-auto">
                                            {uploadResults.map((result, index) => (
                                                <div 
                                                    key={index} 
                                                    className={`flex justify-between items-center p-2 rounded text-sm ${
                                                        result.status === 'success' ? 'bg-green-50 text-green-800' : 
                                                        result.status === 'duplicate' ? 'bg-yellow-50 text-yellow-800' : 
                                                        'bg-red-50 text-red-800'
                                                    }`}
                                                >
                                                    <div className="truncate flex-1">{result.name}</div>
                                                    <div className="ml-2">{result.message}</div>
                                                </div>
                                            ))}
                                        </div>
                                        <button 
                                            className="mt-2 text-sm text-blue-600 hover:text-blue-800 mx-auto block"
                                            onClick={clearResults}
                                        >
                                            Cancella risultati
                                        </button>
                                    </div>
                                )}
                                
                                <div className="flex flex-col sm:flex-row gap-3">
                                    <button
                                        className="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md transition disabled:opacity-50"
                                        onClick={uploadFiles}
                                        disabled={isSubmitting || files.length === 0}
                                    >
                                        {isSubmitting ? 'Caricamento in corso...' : 'Carica foto'}
                                    </button>
                                </div>
                            </div>
                            
                            {/* Le tue foto section */}
                            <div className="bg-white rounded-lg shadow-md p-6 mb-8">
                                <h2 className="text-2xl font-light mb-4 text-center">Le tue foto</h2>
                                
                                {isLoadingPhotos ? (
                                    <div className="text-center py-8 text-stone-500">
                                        <svg className="animate-spin h-8 w-8 mx-auto mb-2 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <p>Caricamento foto...</p>
                                    </div>
                                ) : userPhotos.length === 0 ? (
                                    <div className="text-center py-8 text-stone-500">
                                        <svg className="h-12 w-12 mx-auto mb-3 text-stone-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <p>Non hai ancora caricato nessuna foto</p>
                                        <p className="text-sm mt-1">Le tue foto appariranno qui dopo il caricamento</p>
                                    </div>
                                ) : (
                                    <>
                                        <p className="text-center mb-4 text-stone-600">
                                            Le ultime foto che hai caricato
                                        </p>
                                        <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                            {userPhotos.map((photo, index) => (
                                                <div key={index} className="aspect-square rounded-lg shadow overflow-hidden">
                                                    <img 
                                                        src={photo.thumb_url} 
                                                        alt={photo.name} 
                                                        className="w-full h-full object-cover"
                                                        onError={(e) => {
                                                            e.target.onerror = null;
                                                            e.target.src = "placeholder.jpg";
                                                        }}
                                                    />
                                                </div>
                                            ))}
                                        </div>
                                        <div className="text-center mt-4">
                                            <button 
                                                className="text-blue-600 hover:text-blue-800 text-sm font-medium"
                                                onClick={fetchUserPhotos}
                                            >
                                                Aggiorna lista
                                            </button>
                                        </div>
                                    </>
                                )}

                                <div className="text-center mt-4">
                                    <button 
                                        className="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md transition"
                                        onClick={() => window.location.href = './list.php'}
                                    >
                                        Vedi tutte le foto
                                    </button>
                                </div>
                            </div>
                        </>
                    )}
                    
                    <footer className="text-center text-stone-500 text-sm mt-8 pb-8">
                        <p>Grazie per aver condiviso i tuoi momenti speciali con noi</p>
                        <img src="./footer.png" alt="<?php echo $TITLE; ?>" className="w-36 mx-auto mt-2" />
                    </footer>
                </div>
            );
        }
        
        const root = ReactDOM.createRoot(document.getElementById('app-root'));
        root.render(<App />);
    </script>
</body>
</html>