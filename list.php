<?php

require_once 'config.php';

$names = array();
$images = [];

// Get all couples $guestId => $guestName from the names directory
foreach (glob($NAMES_DIR . '*') as $file) {
    $file = basename($file); // Get the file name only

    // Load the guestId and guestName
    $guestId = pathinfo($file, PATHINFO_FILENAME);
    $guestId = explode('.', $guestId)[0];
    $guestName = file_get_contents($NAMES_DIR . $file);
    $names[$guestId] = trim($guestName);
}

// Get all images from the upload directory
foreach (glob($UPLOAD_DIR . '*') as $file) {
    $file = basename($file); // Get the file name only

    // Skip if the file is not a regular file
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (!in_array($ext, $ACCEPTED_EXTS)) continue;

    // Load the guestId (considering the file name format is $guestId . '_' . $file)
    $guestId = pathinfo($file, PATHINFO_FILENAME);
    $guestId = explode('_', $guestId)[0];
    $guestName = isset($names[$guestId]) ? $names[$guestId] : 'Sconosciuto';

    // Load the paths
    $original_path = $UPLOAD_DIR . $file;
    $thumb_path = $THUMB_DIR . $file . '.' . $THUMB_EXT;
    if (!file_exists($thumb_path)) {
        $thumb_path = $original_path;
    }

    // Add image to the array
    $images[] = [
        'name' => $file,
        'thumb_url' => $thumb_path,
        'download_url' => $original_path,
        'time' => filemtime($original_path),
        'guest_id' => $guestId,
        'guest_name' => $guestName,
    ];
}

// Sort images by time (newest first)
usort($images, function ($a, $b) {
    return $b['time'] <=> $a['time'];
});

$imagesJson = json_encode($images, JSON_PRETTY_PRINT);
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
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&display=swap');

        .font-main {
            font-family: 'Montserrat', sans-serif;
        }
        
        /* Add smooth loading effect for images */
        .image-fade {
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
        }
        
        .image-fade.loaded {
            opacity: 1;
        }
        
        /* Disable body scroll when lightbox is open */
        body.lightbox-open {
            overflow: hidden;
        }
    </style>
</head>
<body class="bg-stone-50 font-main text-stone-800 min-h-screen">
    <div id="app-root"></div>
    
    <script type="text/babel">
        const { useState, useEffect, useRef, useMemo } = React;
        
        // Initialize images from PHP
        const images = <?php echo $imagesJson; ?>;
        
        // Header component
        const Header = () => {
            return (
                <header className="bg-white shadow-md py-6">
                    <div className="container mx-auto px-4">
                        <img src="./header.png" alt="<?php echo $TITLE; ?>" className="w-full mx-auto max-w-[350px]" />
                        <div className="flex justify-center mt-8 mb-2">
                          <button
                              onClick={() => window.location.href = './index.php'}
                              className="bg-blue-600 text-white px-4 py-2 rounded-md shadow-md hover:bg-blue-700 transition"
                          >
                            Carica le tue foto
                          </button>
                        </div>
                    </div>
                </header>
            );
        };
        
        // Image component with lazy loading
        const GalleryImage = ({ image, onClick }) => {
            const [loaded, setLoaded] = useState(false);
            
            return (
                <div className="aspect-square overflow-hidden rounded-lg shadow-md bg-white cursor-pointer transform transition hover:scale-105 relative">
                  {!loaded && (
                    <div className="absolute inset-0 flex items-center justify-center bg-gray-100">
                      <div className="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-blue-600"></div>
                    </div>
                  )}
                  <img 
                    src={image.thumb_url} 
                    alt={image.name}
                    className={`w-full h-full object-cover image-fade ${loaded ? 'loaded' : ''}`}
                    loading="lazy"
                    onClick={() => onClick(image)}
                    onLoad={() => setLoaded(true)}
                    onError={(e) => {
                        e.target.onerror = null;
                        e.target.src = "placeholder.jpg"; // Placeholder image
                    }}
                  />
                </div>
            );
        };
        
        // Lightbox component
        const Lightbox = ({ image, onClose, onPrev, onNext, hasNext, hasPrev }) => {
            const [loaded, setLoaded] = useState(false);
            
            useEffect(() => {
                // Disable body scroll when lightbox is open
                document.body.classList.add('lightbox-open');
                
                // Add ESC key listener
                const handleEsc = (e) => {
                    if (e.key === 'Escape') onClose();
                };
                
                window.addEventListener('keydown', handleEsc);
                
                return () => {
                    document.body.classList.remove('lightbox-open');
                    window.removeEventListener('keydown', handleEsc);
                };
            }, [onClose]);
            
            const downloadImage = () => {
                const link = document.createElement('a');
                link.href = image.download_url;
                link.download = image.name;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            };
            
            return (
                <div className="fixed inset-0 bg-black bg-opacity-90 z-50 flex flex-col justify-center items-center backdrop-blur-md">
                    <div className="absolute top-4 right-4 flex space-x-2 z-10">
                        <button 
                            onClick={downloadImage}
                            className="bg-white bg-opacity-20 hover:bg-opacity-30 rounded-full p-3 text-white transition"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </button>
                        <button 
                            onClick={onClose}
                            className="bg-white bg-opacity-20 hover:bg-opacity-30 rounded-full p-3 text-white transition"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    
                    <div className="relative w-full h-full flex items-center justify-center p-8">
                        {hasPrev && (
                            <button 
                                onClick={onPrev}
                                className="absolute left-4 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-full p-3 text-white transition z-10"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                        )}
                        
                        <div className="max-w-full max-h-full relative">
                          {!loaded && (
                            <div className="flex justify-center items-center absolute inset-0 bg-black bg-opacity-50">
                              <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-white"></div>
                            </div>
                          )}
                          <img 
                            src={image.thumb_url}  
                            alt={image.name}
                            className={`max-w-full max-h-[80vh] object-contain image-fade ${loaded ? 'loaded' : 'hidden'} rounded-lg shadow-lg`}
                            onLoad={() => setLoaded(true)}
                            onError={(e) => {
                                e.target.onerror = null;
                                e.target.src = "placeholder.jpg";
                            }}
                          />
                          {loaded && <div className="text-white text-center mt-4">
                            <p className="text-sm opacity-80">Caricata da <b>{image.guest_name}</b></p>
                          </div>}
                        </div>
                        
                        {hasNext && (
                            <button 
                                onClick={onNext}
                                className="absolute right-4 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-full p-3 text-white transition z-10"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        )}
                    </div>
                </div>
            );
        };
        
        // Pagination component
        const Pagination = ({ currentPage, totalPages, onPageChange }) => {
            const pageNumbers = [];
            
            // Calculate which page numbers to show
            if (totalPages <= 7) {
                // Show all pages if there are 7 or fewer
                for (let i = 1; i <= totalPages; i++) {
                    pageNumbers.push(i);
                }
            } else {
                // Show pages with ellipsis
                if (currentPage <= 3) {
                    for (let i = 1; i <= 5; i++) {
                        pageNumbers.push(i);
                    }
                    pageNumbers.push('...');
                    pageNumbers.push(totalPages);
                } else if (currentPage >= totalPages - 2) {
                    pageNumbers.push(1);
                    pageNumbers.push('...');
                    for (let i = totalPages - 4; i <= totalPages; i++) {
                        pageNumbers.push(i);
                    }
                } else {
                    pageNumbers.push(1);
                    pageNumbers.push('...');
                    for (let i = currentPage - 1; i <= currentPage + 1; i++) {
                        pageNumbers.push(i);
                    }
                    pageNumbers.push('...');
                    pageNumbers.push(totalPages);
                }
            }
            
            return (
                <div className="flex justify-center my-8">
                    <div className="flex items-center space-x-1">
                        <button
                            onClick={() => onPageChange(currentPage - 1)}
                            disabled={currentPage === 1}
                            className={`px-3 py-1 rounded-md ${
                                currentPage === 1 
                                ? 'text-gray-400 cursor-not-allowed' 
                                : 'text-blue-600 hover:bg-blue-100'
                            }`}
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        
                        {pageNumbers.map((number, index) => (
                            number === '...' ? (
                                <span key={`ellipsis-${index}`} className="px-3 py-1">...</span>
                            ) : (
                                <button
                                    key={number}
                                    onClick={() => onPageChange(number)}
                                    className={`px-3 py-1 rounded-md ${
                                        currentPage === number
                                        ? 'bg-blue-600 text-white'
                                        : 'text-blue-600 hover:bg-blue-100'
                                    }`}
                                >
                                    {number}
                                </button>
                            )
                        ))}
                        
                        <button
                            onClick={() => onPageChange(currentPage + 1)}
                            disabled={currentPage === totalPages}
                            className={`px-3 py-1 rounded-md ${
                                currentPage === totalPages
                                ? 'text-gray-400 cursor-not-allowed'
                                : 'text-blue-600 hover:bg-blue-100'
                            }`}
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </div>
            );
        };
        
        // Main App component
        const App = () => {
            const [currentPage, setCurrentPage] = useState(1);
            const [selectedImage, setSelectedImage] = useState(null);
            const imagesPerPage = 12;
            
            // Calculate total pages
            const totalPages = Math.ceil(images.length / imagesPerPage);
            
            // Calculate current images to display
            const indexOfLastImage = currentPage * imagesPerPage;
            const indexOfFirstImage = indexOfLastImage - imagesPerPage;
            const currentImages = images.slice(indexOfFirstImage, indexOfLastImage);
            
            // Handle page change
            const handlePageChange = (pageNumber) => {
                setCurrentPage(pageNumber);
                // Scroll to top when page changes
                window.scrollTo({ top: 0, behavior: 'smooth' });
            };
            
            // Handle opening the lightbox
            const handleImageClick = (image) => {
                setSelectedImage(image);
            };
            
            // Handle closing the lightbox
            const handleCloseLightbox = () => {
                setSelectedImage(null);
            };
            
            // Handle navigating to previous image in lightbox
            const handlePrevImage = () => {
                const currentIndex = images.findIndex(img => img.name === selectedImage.name);
                if (currentIndex > 0) {
                    setSelectedImage(images[currentIndex - 1]);
                }
            };
            
            // Handle navigating to next image in lightbox
            const handleNextImage = () => {
                const currentIndex = images.findIndex(img => img.name === selectedImage.name);
                if (currentIndex < images.length - 1) {
                    setSelectedImage(images[currentIndex + 1]);
                }
            };
            
            return (
                <div className="min-h-screen flex flex-col">
                    <Header />
                    
                    <main className="flex-grow container mx-auto px-4 py-8">
                        <h2 className="text-2xl font-semibold mb-8 text-center">Le vostre foto</h2>
                        
                        {images.length > 0 ? (
                            <>
                                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    {currentImages.map((image, index) => (
                                        <GalleryImage 
                                            key={`${image.galleryUuid}-${image.name}`}
                                            image={image}
                                            onClick={handleImageClick}
                                        />
                                    ))}
                                </div>
                                
                                {totalPages > 1 && (
                                    <Pagination 
                                        currentPage={currentPage}
                                        totalPages={totalPages}
                                        onPageChange={handlePageChange}
                                    />
                                )}
                            </>
                        ) : (
                            <div className="text-center py-12">
                                <p className="text-gray-500">Nessuna foto disponibile</p>
                            </div>
                        )}
                    </main>

                    {selectedImage && (
                        <Lightbox 
                            image={selectedImage}
                            onClose={handleCloseLightbox}
                            onPrev={handlePrevImage}
                            onNext={handleNextImage}
                            hasPrev={images.findIndex(img => img.name === selectedImage.name) > 0}
                            hasNext={images.findIndex(img => img.name === selectedImage.name) < images.length - 1}
                        />
                    )}
                </div>
            );
        };
        
        // Render the App
        ReactDOM.render(<App />, document.getElementById('app-root'));
    </script>
</body>
</html>