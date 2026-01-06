<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

/**
 * Advanced Real Estate Pricing & Valuation System
 * Implements multiple algorithms for accurate price estimation
 */

class PropertyValuation {
    private $pdo;
    
    public function __construct() {
        $this->pdo = get_pdo();
    }
    
    /**
     * Main valuation function that combines multiple algorithms
     */
    public function estimatePropertyValue(array $propertyData): array {
        $results = [];
        
        // 1. Geographically Weighted Regression (GWR)
        $results['gwr'] = $this->geographicallyWeightedRegression($propertyData);
        
        // 2. K-Nearest Neighbors (KNN)
        $results['knn'] = $this->knnValuation($propertyData);
        
        // 3. Cosine Similarity Analysis
        $results['cosine'] = $this->cosineSimilarityValuation($propertyData);
        
        // 4. Ensemble Method (Weighted Average)
        $results['ensemble'] = $this->ensembleValuation($results);
        
        // 5. Confidence Score
        $results['confidence'] = $this->calculateConfidenceScore($results);
        
        return $results;
    }
    
    /**
     * Geographically Weighted Regression (GWR)
     * Handles hyper-local price variations
     */
    private function geographicallyWeightedRegression(array $propertyData): array {
        $latitude = $propertyData['latitude'] ?? 0;
        $longitude = $propertyData['longitude'] ?? 0;
        $propertyType = $propertyData['type'] ?? 'land';
        $size = $propertyData['size'] ?? 0;
        
        // Get nearby properties for local regression
        $nearbyProperties = $this->getNearbyProperties($latitude, $longitude, 2.0); // 2km radius
        
        if (empty($nearbyProperties)) {
            return ['estimated_price' => 0, 'confidence' => 0, 'method' => 'GWR'];
        }
        
        // Calculate distance-weighted average
        $totalWeight = 0;
        $weightedSum = 0;
        
        foreach ($nearbyProperties as $prop) {
            $distance = $this->calculateDistance($latitude, $longitude, $prop['latitude'], $prop['longitude']);
            $weight = 1 / (1 + $distance); // Inverse distance weighting
            
            // Adjust for property type similarity
            if ($prop['type'] === $propertyType) {
                $weight *= 1.5;
            }
            
            // Adjust for size similarity
            $sizeDiff = abs($size - $prop['size']) / max($size, $prop['size']);
            $sizeWeight = 1 / (1 + $sizeDiff);
            $weight *= $sizeWeight;
            
            $totalWeight += $weight;
            $weightedSum += $prop['price'] * $weight;
        }
        
        $estimatedPrice = $totalWeight > 0 ? $weightedSum / $totalWeight : 0;
        $confidence = min(0.95, count($nearbyProperties) / 20); // More data = higher confidence
        
        return [
            'estimated_price' => round($estimatedPrice),
            'confidence' => round($confidence, 3),
            'method' => 'GWR',
            'nearby_properties' => count($nearbyProperties)
        ];
    }
    
    /**
     * K-Nearest Neighbors (KNN) Algorithm
     * Finds most similar properties for price estimation
     */
    private function knnValuation(array $propertyData): array {
        $k = 5; // Number of nearest neighbors
        $propertyType = $propertyData['type'] ?? 'land';
        $size = $propertyData['size'] ?? 0;
        $district = $propertyData['district'] ?? '';
        $features = $propertyData['features'] ?? [];
        
        // Get similar properties
        $similarProperties = $this->getSimilarProperties($propertyType, $size, $district, $features, $k);
        
        if (empty($similarProperties)) {
            return ['estimated_price' => 0, 'confidence' => 0, 'method' => 'KNN'];
        }
        
        // Calculate weighted average based on similarity scores
        $totalSimilarity = 0;
        $weightedSum = 0;
        
        foreach ($similarProperties as $prop) {
            $similarity = $prop['similarity_score'];
            $totalSimilarity += $similarity;
            $weightedSum += $prop['price'] * $similarity;
        }
        
        $estimatedPrice = $totalSimilarity > 0 ? $weightedSum / $totalSimilarity : 0;
        $confidence = min(0.90, count($similarProperties) / $k);
        
        return [
            'estimated_price' => round($estimatedPrice),
            'confidence' => round($confidence, 3),
            'method' => 'KNN',
            'neighbors_used' => count($similarProperties)
        ];
    }
    
    /**
     * Cosine Similarity Analysis
     * Compares property features for similarity
     */
    private function cosineSimilarityValuation(array $propertyData): array {
        $features = $propertyData['features'] ?? [];
        $size = $propertyData['size'] ?? 0;
        $propertyType = $propertyData['type'] ?? 'land';
        
        // Get properties with similar feature vectors
        $similarProperties = $this->getFeatureSimilarProperties($features, $size, $propertyType, 10);
        
        if (empty($similarProperties)) {
            return ['estimated_price' => 0, 'confidence' => 0, 'method' => 'Cosine'];
        }
        
        // Calculate cosine similarity scores
        $totalSimilarity = 0;
        $weightedSum = 0;
        
        foreach ($similarProperties as $prop) {
            $cosineScore = $this->calculateCosineSimilarity($features, $prop['features']);
            $totalSimilarity += $cosineScore;
            $weightedSum += $prop['price'] * $cosineScore;
        }
        
        $estimatedPrice = $totalSimilarity > 0 ? $weightedSum / $totalSimilarity : 0;
        $confidence = min(0.85, count($similarProperties) / 15);
        
        return [
            'estimated_price' => round($estimatedPrice),
            'confidence' => round($confidence, 3),
            'method' => 'Cosine',
            'similarity_threshold' => 0.7
        ];
    }
    
    /**
     * Ensemble Method - Combines all algorithms
     */
    private function ensembleValuation(array $results): array {
        $weights = [
            'gwr' => 0.4,      // GWR gets highest weight (geographic importance)
            'knn' => 0.35,     // KNN second (similarity importance)
            'cosine' => 0.25   // Cosine similarity (feature importance)
        ];
        
        $totalWeight = 0;
        $weightedSum = 0;
        
        foreach ($weights as $method => $weight) {
            if (isset($results[$method]['estimated_price']) && $results[$method]['estimated_price'] > 0) {
                $totalWeight += $weight;
                $weightedSum += $results[$method]['estimated_price'] * $weight;
            }
        }
        
        $ensemblePrice = $totalWeight > 0 ? $weightedSum / $totalWeight : 0;
        
        return [
            'estimated_price' => round($ensemblePrice),
            'confidence' => $this->calculateEnsembleConfidence($results),
            'method' => 'Ensemble',
            'algorithm_weights' => $weights
        ];
    }
    
    /**
     * Calculate overall confidence score
     */
    private function calculateConfidenceScore(array $results): float {
        $confidences = [];
        
        foreach (['gwr', 'knn', 'cosine'] as $method) {
            if (isset($results[$method]['confidence'])) {
                $confidences[] = $results[$method]['confidence'];
            }
        }
        
        if (empty($confidences)) {
            return 0.0;
        }
        
        // Weighted average confidence
        $weights = [0.4, 0.35, 0.25]; // Same weights as ensemble
        $totalWeight = 0;
        $weightedSum = 0;
        
        for ($i = 0; $i < min(count($confidences), count($weights)); $i++) {
            $totalWeight += $weights[$i];
            $weightedSum += $confidences[$i] * $weights[$i];
        }
        
        return $totalWeight > 0 ? round($weightedSum / $totalWeight, 3) : 0.0;
    }
    
    /**
     * Helper Functions
     */
    private function getNearbyProperties(float $lat, float $lon, float $radius): array {
        $sql = "SELECT id, price, latitude, longitude, type, size, district 
                FROM properties 
                WHERE latitude BETWEEN ? - ? AND ? + ? 
                AND longitude BETWEEN ? - ? AND ? + ?
                AND price > 0
                ORDER BY ABS(latitude - ?) + ABS(longitude - ?)
                LIMIT 50";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$lat, $radius, $lat, $radius, $lon, $radius, $lon, $radius, $lat, $lon]);
        
        return $stmt->fetchAll();
    }
    
    private function getSimilarProperties(string $type, float $size, string $district, array $features, int $k): array {
        $sql = "SELECT id, price, type, size, district, features
                FROM properties 
                WHERE type = ? 
                AND price > 0
                ORDER BY ABS(size - ?) / GREATEST(size, ?)
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$type, $size, $size, $k]);
        
        $properties = $stmt->fetchAll();
        
        // Calculate similarity scores
        foreach ($properties as &$prop) {
            $prop['similarity_score'] = $this->calculatePropertySimilarity($type, $size, $district, $features, $prop);
        }
        
        // Sort by similarity score
        usort($properties, function($a, $b) {
            return $b['similarity_score'] <=> $a['similarity_score'];
        });
        
        return array_slice($properties, 0, $k);
    }
    
    private function getFeatureSimilarProperties(array $features, float $size, string $type, int $limit): array {
        $sql = "SELECT id, price, features, size, type
                FROM properties 
                WHERE type = ? 
                AND price > 0
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$type, $limit]);
        
        $properties = $stmt->fetchAll();
        
        // Ensure features are properly formatted
        foreach ($properties as &$prop) {
            if (is_string($prop['features'])) {
                $prop['features'] = json_decode($prop['features'], true) ?: [];
            } elseif (!is_array($prop['features'])) {
                $prop['features'] = [];
            }
        }
        
        return $properties;
    }
    
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float {
        $earthRadius = 6371; // Earth's radius in kilometers
        
        $latDiff = deg2rad($lat2 - $lat1);
        $lonDiff = deg2rad($lon2 - $lon1);
        
        $a = sin($latDiff/2) * sin($latDiff/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDiff/2) * sin($lonDiff/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }
    
    private function calculatePropertySimilarity(string $type1, float $size1, string $district1, array $features1, array $prop2): float {
        $similarity = 0;
        
        // Type similarity
        if ($type1 === $prop2['type']) {
            $similarity += 0.3;
        }
        
        // Size similarity (normalized)
        $sizeDiff = abs($size1 - $prop2['size']) / max($size1, $prop2['size']);
        $similarity += (1 - $sizeDiff) * 0.3;
        
        // District similarity
        if ($district1 === $prop2['district']) {
            $similarity += 0.2;
        }
        
        // Features similarity
        $features2 = json_decode($prop2['features'] ?? '[]', true) ?: [];
        $featureSimilarity = $this->calculateFeatureSimilarity($features1, $features2);
        $similarity += $featureSimilarity * 0.2;
        
        return min(1.0, $similarity);
    }
    
    private function calculateFeatureSimilarity(array $features1, array $features2): float {
        if (empty($features1) && empty($features2)) {
            return 1.0;
        }
        
        if (empty($features1) || empty($features2)) {
            return 0.0;
        }
        
        $intersection = array_intersect($features1, $features2);
        $union = array_unique(array_merge($features1, $features2));
        
        return count($union) > 0 ? count($intersection) / count($union) : 0.0;
    }
    
    private function calculateCosineSimilarity(array $features1, array $features2): float {
        // Ensure both arrays are valid
        if (!is_array($features1) || !is_array($features2)) {
            return 0.0;
        }
        
        if (empty($features1) && empty($features2)) {
            return 1.0;
        }
        
        if (empty($features1) || empty($features2)) {
            return 0.0;
        }
        
        // Create feature vectors
        $allFeatures = array_unique(array_merge($features1, $features2));
        $vector1 = [];
        $vector2 = [];
        
        foreach ($allFeatures as $feature) {
            $vector1[] = in_array($feature, $features1) ? 1 : 0;
            $vector2[] = in_array($feature, $features2) ? 1 : 0;
        }
        
        // Calculate cosine similarity
        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;
        
        for ($i = 0; $i < count($vector1); $i++) {
            $dotProduct += $vector1[$i] * $vector2[$i];
            $magnitude1 += $vector1[$i] * $vector1[$i];
            $magnitude2 += $vector2[$i] * $vector2[$i];
        }
        
        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);
        
        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0.0;
        }
        
        return $dotProduct / ($magnitude1 * $magnitude2);
    }
    
    private function calculateEnsembleConfidence(array $results): float {
        $confidences = [];
        
        foreach (['gwr', 'knn', 'cosine'] as $method) {
            if (isset($results[$method]['confidence'])) {
                $confidences[] = $results[$method]['confidence'];
            }
        }
        
        if (empty($confidences)) {
            return 0.0;
        }
        
        return round(array_sum($confidences) / count($confidences), 3);
    }
}

/**
 * Market Analysis Functions
 */
class MarketAnalysis {
    private $pdo;
    
    public function __construct() {
        $this->pdo = get_pdo();
    }
    
    /**
     * Get price trends for a specific area
     */
    public function getPriceTrends(string $district, string $propertyType, int $months = 12): array {
        $sql = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    AVG(price) as avg_price,
                    COUNT(*) as sales_count,
                    MIN(price) as min_price,
                    MAX(price) as max_price
                FROM properties 
                WHERE district = ? 
                AND type = ? 
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$district, $propertyType, $months]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get comparable sales
     */
    public function getComparableSales(array $propertyData, int $limit = 10): array {
        $sql = "SELECT id, title, price, size, district, created_at, features
                FROM properties 
                WHERE type = ? 
                AND district = ? 
                AND price > 0
                AND id != ?
                ORDER BY created_at DESC
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $propertyData['type'],
            $propertyData['district'],
            $propertyData['id'] ?? 0,
            $limit
        ]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Calculate market statistics
     */
    public function getMarketStats(string $district, string $propertyType): array {
        $sql = "SELECT 
                    COUNT(*) as total_properties,
                    AVG(price) as avg_price,
                    MIN(price) as min_price,
                    MAX(price) as max_price,
                    STDDEV(price) as price_std,
                    AVG(size) as avg_size
                FROM properties 
                WHERE district = ? 
                AND type = ? 
                AND price > 0";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$district, $propertyType]);
        
        return $stmt->fetch();
    }
}
?>
