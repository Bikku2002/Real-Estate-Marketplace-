<?php
declare(strict_types=1);

// Language configuration
$available_languages = ['en', 'ne'];

// Default language
$default_language = 'en';

// Get current language from session or cookie
function get_current_language(): string {
    if (isset($_SESSION['language'])) {
        return $_SESSION['language'];
    }
    
    if (isset($_COOKIE['language'])) {
        return $_COOKIE['language'];
    }
    
    return $GLOBALS['default_language'];
}

// Set language
function set_language(string $lang): void {
    if (in_array($lang, $GLOBALS['available_languages'])) {
        $_SESSION['language'] = $lang;
        setcookie('language', $lang, time() + (365 * 24 * 60 * 60), '/');
    }
}

// Language strings
$translations = [
    'en' => [
        // Navigation
        'home' => 'Home',
        'contact' => 'Contact',
        'profile' => 'Profile',
        'logout' => 'Logout',
        'login' => 'Login',
        'register' => 'Register',
        
        // Common
        'search' => 'Search',
        'filter' => 'Filter',
        'clear' => 'Clear',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'view' => 'View',
        'submit' => 'Submit',
        'loading' => 'Loading...',
        
        // Property
        'property' => 'Property',
        'land' => 'Land',
        'house' => 'House',
        'price' => 'Price',
        'location' => 'Location',
        'size' => 'Size',
        'features' => 'Features',
        'description' => 'Description',
        
        // Pricing
        'estimated_value' => 'Estimated Value',
        'price_confidence' => 'Price Confidence',
        'market_analysis' => 'Market Analysis',
        'comparable_sales' => 'Comparable Sales',
        'price_trends' => 'Price Trends',
        'valuation_report' => 'Valuation Report',
        
        // User Interface
        'language' => 'Language',
        'nepali' => 'नेपाली',
        'english' => 'English',
        'choose_language' => 'Choose Language',
        'settings' => 'Settings',
        'preferences' => 'Preferences',
        
        // Messages
        'welcome' => 'Welcome',
        'thank_you' => 'Thank you',
        'error_occurred' => 'An error occurred',
        'success' => 'Success',
        'warning' => 'Warning',
        'info' => 'Information'
    ],
    
    'ne' => [
        // Navigation
        'home' => 'गृह',
        'contact' => 'सम्पर्क',
        'profile' => 'प्रोफाइल',
        'logout' => 'लगआउट',
        'login' => 'लगइन',
        'register' => 'दर्ता',
        
        // Common
        'search' => 'खोज्नुहोस्',
        'filter' => 'फिल्टर',
        'clear' => 'सफा गर्नुहोस्',
        'save' => 'सुरक्षित गर्नुहोस्',
        'cancel' => 'रद्द गर्नुहोस्',
        'edit' => 'सम्पादन गर्नुहोस्',
        'delete' => 'मेटाउनुहोस्',
        'view' => 'हेर्नुहोस्',
        'submit' => 'पेश गर्नुहोस्',
        'loading' => 'लोड हुँदै...',
        
        // Property
        'property' => 'सम्पत्ति',
        'land' => 'जग्गा',
        'house' => 'घर',
        'price' => 'मूल्य',
        'location' => 'स्थान',
        'size' => 'साइज',
        'features' => 'विशेषताहरू',
        'description' => 'विवरण',
        
        // Pricing
        'estimated_value' => 'अनुमानित मूल्य',
        'price_confidence' => 'मूल्य विश्वास',
        'market_analysis' => 'बजार विश्लेषण',
        'comparable_sales' => 'तुलनात्मक बिक्री',
        'price_trends' => 'मूल्य प्रवृत्ति',
        'valuation_report' => 'मूल्यांकन रिपोर्ट',
        
        // User Interface
        'language' => 'भाषा',
        'nepali' => 'नेपाली',
        'english' => 'English',
        'choose_language' => 'भाषा छान्नुहोस्',
        'settings' => 'सेटिङहरू',
        'preferences' => 'प्राथमिकताहरू',
        
        // Messages
        'welcome' => 'स्वागत छ',
        'thank_you' => 'धन्यवाद',
        'error_occurred' => 'एउटा त्रुटि भयो',
        'success' => 'सफल',
        'warning' => 'चेतावनी',
        'info' => 'जानकारी'
    ]
];

// Translation function
function __(string $key): string {
    $lang = get_current_language();
    $translations = $GLOBALS['translations'];
    
    if (isset($translations[$lang][$key])) {
        return $translations[$lang][$key];
    }
    
    // Fallback to English
    if (isset($translations['en'][$key])) {
        return $translations['en'][$key];
    }
    
    return $key;
}

// Get language display name
function get_language_name(string $code): string {
    $names = [
        'en' => 'English',
        'ne' => 'नेपाली'
    ];
    
    return $names[$code] ?? $code;
}
?>
