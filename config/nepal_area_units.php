<?php
/**
 * Nepal Area Measurement Units Utility Class
 * 
 * This class provides conversion functions between different area measurement units
 * commonly used in Nepal for real estate transactions.
 * 
 * Common Nepal Area Units:
 * - Ana (आना) - Traditional unit, 1 Ana = 342.25 sq ft
 * - Paisa (पैसा) - 1 Ana = 4 Paisa, 1 Paisa = 85.56 sq ft
 * - Dhur (धुर) - 1 Ana = 4 Dhur, 1 Dhur = 85.56 sq ft
 * - Ropani (रोपनी) - 1 Ropani = 16 Ana = 5,476 sq ft
 * - Kattha (कठ्ठा) - 1 Kattha = 20 Dhur = 1,711.2 sq ft
 * - Bigha (बिघा) - 1 Bigha = 20 Kattha = 34,224 sq ft
 * - Square Feet (sq ft) - International unit
 * - Square Meter (sq m) - International unit
 */

class NepalAreaUnits {
    
    // Conversion constants
    const ANA_TO_SQFT = 342.25;
    const PAISA_TO_SQFT = 85.56;
    const DHUR_TO_SQFT = 85.56;
    const ROPANI_TO_SQFT = 5476;
    const KATTHA_TO_SQFT = 1711.2;
    const BIGHA_TO_SQFT = 34224;
    const SQFT_TO_SQM = 0.092903;
    
    /**
     * Get all available area units with their display names
     */
    public static function getAvailableUnits() {
        return [
            'sqft' => [
                'name' => 'Square Feet (sq ft)',
                'short' => 'sq ft',
                'symbol' => 'sq ft',
                'description' => 'International unit, commonly used in urban areas'
            ],
            'ana' => [
                'name' => 'Ana (आना)',
                'short' => 'Ana',
                'symbol' => 'Ana',
                'description' => 'Traditional Nepal unit, 1 Ana = 342.25 sq ft'
            ],
            'paisa' => [
                'name' => 'Paisa (पैसा)',
                'short' => 'Paisa',
                'symbol' => 'Paisa',
                'description' => '1 Ana = 4 Paisa, 1 Paisa = 85.56 sq ft'
            ],
            'dhur' => [
                'name' => 'Dhur (धुर)',
                'short' => 'Dhur',
                'symbol' => 'Dhur',
                'description' => '1 Ana = 4 Dhur, 1 Dhur = 85.56 sq ft'
            ],
            'ropani' => [
                'name' => 'Ropani (रोपनी)',
                'short' => 'Ropani',
                'symbol' => 'Ropani',
                'description' => '1 Ropani = 16 Ana = 5,476 sq ft'
            ],
            'kattha' => [
                'name' => 'Kattha (कठ्ठा)',
                'short' => 'Kattha',
                'symbol' => 'Kattha',
                'description' => '1 Kattha = 20 Dhur = 1,711.2 sq ft'
            ],
            'bigha' => [
                'name' => 'Bigha (बिघा)',
                'short' => 'Bigha',
                'symbol' => 'Bigha',
                'description' => '1 Bigha = 20 Kattha = 34,224 sq ft'
            ],
            'sqm' => [
                'name' => 'Square Meter (sq m)',
                'short' => 'sq m',
                'symbol' => 'sq m',
                'description' => 'International unit, 1 sq m = 10.764 sq ft'
            ]
        ];
    }
    
    /**
     * Convert any area unit to square feet
     */
    public static function toSquareFeet($value, $fromUnit) {
        if (!is_numeric($value) || $value <= 0) {
            return 0;
        }
        
        switch ($fromUnit) {
            case 'sqft':
                return $value;
            case 'ana':
                return $value * self::ANA_TO_SQFT;
            case 'paisa':
                return $value * self::PAISA_TO_SQFT;
            case 'dhur':
                return $value * self::DHUR_TO_SQFT;
            case 'ropani':
                return $value * self::ROPANI_TO_SQFT;
            case 'kattha':
                return $value * self::KATTHA_TO_SQFT;
            case 'bigha':
                return $value * self::BIGHA_TO_SQFT;
            case 'sqm':
                return $value / self::SQFT_TO_SQM;
            default:
                return $value;
        }
    }
    
    /**
     * Convert square feet to any other unit
     */
    public static function fromSquareFeet($sqft, $toUnit) {
        if (!is_numeric($sqft) || $sqft <= 0) {
            return 0;
        }
        
        switch ($toUnit) {
            case 'sqft':
                return $sqft;
            case 'ana':
                return $sqft / self::ANA_TO_SQFT;
            case 'paisa':
                return $sqft / self::PAISA_TO_SQFT;
            case 'dhur':
                return $sqft / self::DHUR_TO_SQFT;
            case 'ropani':
                return $sqft / self::ROPANI_TO_SQFT;
            case 'kattha':
                return $sqft / self::KATTHA_TO_SQFT;
            case 'bigha':
                return $sqft / self::BIGHA_TO_SQFT;
            case 'sqm':
                return $sqft * self::SQFT_TO_SQM;
            default:
                return $sqft;
        }
    }
    
    /**
     * Convert between any two units
     */
    public static function convert($value, $fromUnit, $toUnit) {
        if ($fromUnit === $toUnit) {
            return $value;
        }
        
        $sqft = self::toSquareFeet($value, $fromUnit);
        return self::fromSquareFeet($sqft, $toUnit);
    }
    
    /**
     * Format area value with proper unit display
     */
    public static function formatArea($value, $unit, $decimals = 2) {
        if (!is_numeric($value) || $value <= 0) {
            return 'N/A';
        }
        
        $units = self::getAvailableUnits();
        if (!isset($units[$unit])) {
            return number_format($value, $decimals) . ' ' . $unit;
        }
        
        $formattedValue = number_format($value, $decimals);
        $symbol = $units[$unit]['symbol'];
        
        return $formattedValue . ' ' . $symbol;
    }
    
    /**
     * Get area in multiple units for display
     */
    public static function getAreaInMultipleUnits($value, $primaryUnit) {
        if (!is_numeric($value) || $value <= 0) {
            return [];
        }
        
        $sqft = self::toSquareFeet($value, $primaryUnit);
        $units = self::getAvailableUnits();
        $results = [];
        
        foreach ($units as $unit => $unitInfo) {
            $convertedValue = self::fromSquareFeet($sqft, $unit);
            $results[$unit] = [
                'value' => $convertedValue,
                'formatted' => self::formatArea($convertedValue, $unit),
                'name' => $unitInfo['name'],
                'short' => $unitInfo['short'],
                'symbol' => $unitInfo['symbol'],
                'description' => $unitInfo['description']
            ];
        }
        
        return $results;
    }
    
    /**
     * Get recommended units based on area size
     */
    public static function getRecommendedUnits($sqft) {
        if ($sqft <= 1000) {
            return ['sqft', 'sqm'];
        } elseif ($sqft <= 5000) {
            return ['sqft', 'sqm', 'ana'];
        } elseif ($sqft <= 50000) {
            return ['ana', 'paisa', 'sqft'];
        } elseif ($sqft <= 500000) {
            return ['ropani', 'ana', 'kattha'];
        } else {
            return ['bigha', 'kattha', 'ropani'];
        }
    }
    
    /**
     * Validate area input
     */
    public static function validateArea($value, $unit) {
        if (!is_numeric($value) || $value <= 0) {
            return false;
        }
        
        $units = self::getAvailableUnits();
        if (!isset($units[$unit])) {
            return false;
        }
        
        // Check reasonable limits
        $sqft = self::toSquareFeet($value, $unit);
        if ($sqft > 10000000) { // 10 million sq ft = ~230 acres
            return false;
        }
        
        return true;
    }
    
    /**
     * Get area comparison for similar properties
     */
    public static function getAreaComparison($area1, $unit1, $area2, $unit2) {
        $sqft1 = self::toSquareFeet($area1, $unit1);
        $sqft2 = self::toSquareFeet($area2, $unit2);
        
        if ($sqft2 == 0) {
            return 0;
        }
        
        $ratio = $sqft1 / $sqft2;
        
        if ($ratio >= 0.9 && $ratio <= 1.1) {
            return 'similar';
        } elseif ($ratio > 1.1) {
            return 'larger';
        } else {
            return 'smaller';
        }
    }
}
?>
