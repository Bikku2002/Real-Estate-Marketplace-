<?php
declare(strict_types=1);

/**
 * Content-Based Filtering and Recommendation System
 * 
 * This class provides intelligent property recommendations based on:
 * - User preferences and search history
 * - Property features and characteristics
 * - Content similarity analysis
 * - Collaborative filtering elements
 */
class ContentBasedFiltering {
    private $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get personalized property recommendations for a user
     */
    public function getPersonalizedRecommendations(int $userId, int $limit = 10): array {
        try {
            // Get user preferences
            $userPreferences = $this->getUserPreferences($userId);
            
            // Get user search history
            $searchHistory = $this->getUserSearchHistory($userId);
            
            // Get user's favorite properties to understand taste
            $favoriteProperties = $this->getUserFavoriteProperties($userId);
            
            // Build recommendation query based on preferences
            $recommendations = $this->buildRecommendationQuery($userPreferences, $searchHistory, $favoriteProperties, $limit);
            
            // Calculate recommendation scores
            $scoredRecommendations = $this->calculateRecommendationScores($recommendations, $userPreferences);
            
            // Sort by score and return
            usort($scoredRecommendations, function($a, $b) {
                return $b['recommendation_score'] <=> $a['recommendation_score'];
            });
            
            return array_slice($scoredRecommendations, 0, $limit);
            
        } catch (Exception $e) {
            error_log("Error getting personalized recommendations: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get content-based recommendations based on a specific property
     */
    public function getSimilarProperties(int $propertyId, int $limit = 8): array {
        try {
            // Get the reference property details
            $referenceProperty = $this->getPropertyDetails($propertyId);
            if (!$referenceProperty) {
                return [];
            }
            
            // Find similar properties based on features
            $similarProperties = $this->findSimilarProperties($referenceProperty, $limit);
            
            // Calculate similarity scores
            $scoredProperties = [];
            foreach ($similarProperties as $property) {
                $similarityScore = $this->calculatePropertySimilarity($referenceProperty, $property);
                $property['similarity_score'] = $similarityScore;
                $scoredProperties[] = $property;
            }
            
            // Sort by similarity score
            usort($scoredProperties, function($a, $b) {
                return $b['similarity_score'] <=> $a['similarity_score'];
            });
            
            return array_slice($scoredProperties, 0, $limit);
            
        } catch (Exception $e) {
            error_log("Error getting similar properties: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get trending properties based on views, favorites, and offers
     */
    public function getTrendingProperties(int $limit = 10): array {
        try {
            $sql = "
                SELECT 
                    p.*,
                    u.name as seller_name,
                    u.kyc_status as seller_kyc_status,
                    p.view_count,
                    p.favorite_count,
                    COUNT(DISTINCT o.id) as offer_count,
                    AVG(o.offer_amount) as avg_offer_amount,
                    (
                        (p.view_count * 0.3) + 
                        (p.favorite_count * 0.4) + 
                        (COUNT(DISTINCT o.id) * 0.3)
                    ) as trending_score
                FROM properties p
                JOIN users u ON p.seller_id = u.id
                LEFT JOIN offers o ON p.id = o.property_id AND o.status IN ('pending', 'countered')
                WHERE p.availability_status = 'available'
                GROUP BY p.id
                HAVING trending_score > 0
                ORDER BY trending_score DESC, p.created_at DESC
                LIMIT :limit
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error getting trending properties: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get properties based on user's recent searches
     */
    public function getPropertiesBasedOnSearchHistory(int $userId, int $limit = 10): array {
        try {
            // Get user's recent search patterns
            $recentSearches = $this->getRecentSearchPatterns($userId);
            
            if (empty($recentSearches)) {
                return [];
            }
            
            // Build search-based query
            $searchCriteria = $this->buildSearchCriteriaFromHistory($recentSearches);
            
            // Get properties matching search criteria
            $properties = $this->getPropertiesByCriteria($searchCriteria, $limit);
            
            return $properties;
            
        } catch (Exception $e) {
            error_log("Error getting properties based on search history: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update user preferences based on their interactions
     */
    public function updateUserPreferences(int $userId, array $interactions): bool {
        try {
            foreach ($interactions as $interaction) {
                $this->processUserInteraction($userId, $interaction);
            }
            return true;
        } catch (Exception $e) {
            error_log("Error updating user preferences: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Track property view for better recommendations
     */
    public function trackPropertyView(int $propertyId, ?int $userId = null, array $context = []): bool {
        try {
            $sql = "
                INSERT INTO property_views (property_id, user_id, source_page, user_agent, ip_address)
                VALUES (:property_id, :user_id, :source_page, :user_agent, :ip_address)
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':property_id' => $propertyId,
                ':user_id' => $userId,
                ':source_page' => $context['source_page'] ?? null,
                ':user_agent' => $context['user_agent'] ?? null,
                ':ip_address' => $context['ip_address'] ?? null
            ]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error tracking property view: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get property recommendations by district
     */
    public function getDistrictBasedRecommendations(string $district, int $limit = 10): array {
        try {
            $sql = "
                SELECT 
                    p.*,
                    u.name as seller_name,
                    u.kyc_status as seller_kyc_status,
                    p.view_count,
                    p.favorite_count,
                    COUNT(DISTINCT o.id) as offer_count,
                    (
                        (p.view_count * 0.3) + 
                        (p.favorite_count * 0.4) + 
                        (COUNT(DISTINCT o.id) * 0.3)
                    ) as popularity_score
                FROM properties p
                JOIN users u ON p.seller_id = u.id
                LEFT JOIN offers o ON p.id = o.property_id AND o.status IN ('pending', 'countered')
                WHERE p.availability_status = 'available'
                AND p.district = :district
                GROUP BY p.id
                ORDER BY popularity_score DESC, p.created_at DESC
                LIMIT :limit
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':district', $district, PDO::PARAM_STR);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error getting district-based recommendations: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get property recommendations by price range
     */
    public function getPriceBasedRecommendations(int $minPrice, int $maxPrice, int $limit = 10): array {
        try {
            $sql = "
                SELECT 
                    p.*,
                    u.name as seller_name,
                    u.kyc_status as seller_kyc_status,
                    p.view_count,
                    p.favorite_count,
                    COUNT(DISTINCT o.id) as offer_count,
                    (
                        (p.view_count * 0.3) + 
                        (p.favorite_count * 0.4) + 
                        (COUNT(DISTINCT o.id) * 0.3)
                    ) as popularity_score
                FROM properties p
                JOIN users u ON p.seller_id = u.id
                LEFT JOIN offers o ON p.id = o.property_id AND o.status IN ('pending', 'countered')
                WHERE p.availability_status = 'available'
                AND p.price BETWEEN :min_price AND :max_price
                GROUP BY p.id
                ORDER BY popularity_score DESC, p.created_at DESC
                LIMIT :limit
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':min_price', $minPrice, PDO::PARAM_INT);
            $stmt->bindParam(':max_price', $maxPrice, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error getting price-based recommendations: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get property recommendations by features
     */
    public function getFeatureBasedRecommendations(array $features, int $limit = 10): array {
        try {
            if (empty($features)) {
                return [];
            }
            
            $placeholders = str_repeat('?,', count($features) - 1) . '?';
            
            $sql = "
                SELECT 
                    p.*,
                    u.name as seller_name,
                    u.kyc_status as seller_kyc_status,
                    p.view_count,
                    p.favorite_count,
                    COUNT(DISTINCT o.id) as offer_count,
                    COUNT(DISTINCT pf.id) as matching_features,
                    (
                        (p.view_count * 0.2) + 
                        (p.favorite_count * 0.3) + 
                        (COUNT(DISTINCT o.id) * 0.2) +
                        (COUNT(DISTINCT pf.id) * 0.3)
                    ) as feature_score
                FROM properties p
                JOIN users u ON p.seller_id = u.id
                LEFT JOIN offers o ON p.id = o.property_id AND o.status IN ('pending', 'countered')
                LEFT JOIN property_features pf ON p.id = pf.property_id 
                    AND pf.feature_name IN ($placeholders)
                WHERE p.availability_status = 'available'
                GROUP BY p.id
                HAVING matching_features > 0
                ORDER BY feature_score DESC, matching_features DESC, p.created_at DESC
                LIMIT ?
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $params = array_merge($features, [$limit]);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error getting feature-based recommendations: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Private helper methods
     */
    
    private function getUserPreferences(int $userId): array {
        $sql = "SELECT * FROM user_preferences WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getUserSearchHistory(int $userId): array {
        $sql = "SELECT * FROM property_search_history WHERE user_id = :user_id ORDER BY search_date DESC LIMIT 20";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getUserFavoriteProperties(int $userId): array {
        $sql = "
            SELECT p.* FROM properties p
            JOIN favorites f ON p.id = f.property_id
            WHERE f.user_id = :user_id
            ORDER BY f.created_at DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getPropertyDetails(int $propertyId): ?array {
        $sql = "SELECT * FROM properties WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $propertyId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    private function buildRecommendationQuery(array $preferences, array $searchHistory, array $favoriteProperties, int $limit): array {
        // Build complex query based on user preferences and history
        $sql = "
            SELECT 
                p.*,
                u.name as seller_name,
                u.kyc_status as seller_kyc_status,
                p.view_count,
                p.favorite_count,
                COUNT(DISTINCT o.id) as offer_count
            FROM properties p
            JOIN users u ON p.seller_id = u.id
            LEFT JOIN offers o ON p.id = o.property_id AND o.status IN ('pending', 'countered')
            WHERE p.availability_status = 'available'
        ";
        
        $params = [];
        $conditions = [];
        
        // Add preference-based conditions
        foreach ($preferences as $pref) {
            switch ($pref['preference_type']) {
                case 'property_type':
                    $conditions[] = "p.type = :type_" . $pref['id'];
                    $params[':type_' . $pref['id']] = $pref['preference_value'];
                    break;
                case 'district':
                    $conditions[] = "p.district = :district_" . $pref['id'];
                    $params[':district_' . $pref['id']] = $pref['preference_value'];
                    break;
                case 'price_range':
                    if ($pref['preference_key'] === 'max_price') {
                        $conditions[] = "p.price <= :max_price_" . $pref['id'];
                        $params[':max_price_' . $pref['id']] = $pref['preference_value'];
                    }
                    break;
                case 'area_range':
                    if ($pref['preference_key'] === 'min_area') {
                        $conditions[] = "p.area_sqft >= :min_area_" . $pref['id'];
                        $params[':min_area_' . $pref['id']] = $pref['preference_value'];
                    }
                    break;
            }
        }
        
        if (!empty($conditions)) {
            $sql .= " AND (" . implode(" OR ", $conditions) . ")";
        }
        
        $sql .= " GROUP BY p.id ORDER BY p.created_at DESC LIMIT :limit";
        $params[':limit'] = $limit;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function calculateRecommendationScores(array $properties, array $preferences): array {
        $scoredProperties = [];
        
        foreach ($properties as $property) {
            $score = 0.0;
            
            // Base score from popularity
            $score += ($property['view_count'] * 0.001);
            $score += ($property['favorite_count'] * 0.01);
            $score += ($property['offer_count'] * 0.005);
            
            // Preference matching score
            foreach ($preferences as $pref) {
                $matchScore = $this->calculatePreferenceMatch($property, $pref);
                $score += $matchScore * $pref['preference_weight'];
            }
            
            $property['recommendation_score'] = min(1.0, $score);
            $scoredProperties[] = $property;
        }
        
        return $scoredProperties;
    }
    
    private function calculatePreferenceMatch(array $property, array $preference): float {
        switch ($preference['preference_type']) {
            case 'property_type':
                return $property['type'] === $preference['preference_value'] ? 0.3 : 0.0;
            case 'district':
                return $property['district'] === $preference['preference_value'] ? 0.25 : 0.0;
            case 'price_range':
                if ($preference['preference_key'] === 'max_price') {
                    return $property['price'] <= $preference['preference_value'] ? 0.2 : 0.0;
                }
                break;
            case 'area_range':
                if ($preference['preference_key'] === 'min_area') {
                    return $property['area_sqft'] >= $preference['preference_value'] ? 0.15 : 0.0;
                }
                break;
        }
        return 0.0;
    }
    
    private function findSimilarProperties(array $referenceProperty, int $limit): array {
        $sql = "
            SELECT 
                p.*,
                u.name as seller_name,
                u.kyc_status as seller_kyc_status
            FROM properties p
            JOIN users u ON p.seller_id = u.id
            WHERE p.availability_status = 'available'
            AND p.id != :reference_id
            AND p.type = :type
            AND p.district = :district
            AND ABS(p.price - :price) / GREATEST(p.price, :price) <= 0.3
            ORDER BY 
                ABS(p.price - :price) / GREATEST(p.price, :price) ASC,
                p.created_at DESC
            LIMIT :limit
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':reference_id' => $referenceProperty['id'],
            ':type' => $referenceProperty['type'],
            ':district' => $referenceProperty['district'],
            ':price' => $referenceProperty['price'],
            ':limit' => $limit
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function calculatePropertySimilarity(array $property1, array $property2): float {
        $score = 0.0;
        
        // Type similarity (30%)
        if ($property1['type'] === $property2['type']) {
            $score += 0.3;
        }
        
        // District similarity (25%)
        if ($property1['district'] === $property2['district']) {
            $score += 0.25;
        }
        
        // Price similarity (20%) - within 30% range
        $priceDiff = abs($property1['price'] - $property2['price']) / max($property1['price'], $property2['price']);
        if ($priceDiff <= 0.3) {
            $score += 0.2 * (1 - $priceDiff);
        }
        
        // Area similarity (15%) - within 40% range
        if ($property1['area_sqft'] && $property2['area_sqft']) {
            $areaDiff = abs($property1['area_sqft'] - $property2['area_sqft']) / max($property1['area_sqft'], $property2['area_sqft']);
            if ($areaDiff <= 0.4) {
                $score += 0.15 * (1 - $areaDiff);
            }
        }
        
        // Feature similarity (10%)
        $score += 0.1;
        
        return $score;
    }
    
    private function getRecentSearchPatterns(int $userId): array {
        $sql = "
            SELECT 
                search_query,
                search_filters,
                COUNT(*) as search_count,
                MAX(search_date) as last_search
            FROM property_search_history
            WHERE user_id = :user_id
            AND search_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY search_query, search_filters
            ORDER BY search_count DESC, last_search DESC
            LIMIT 5
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function buildSearchCriteriaFromHistory(array $searchHistory): array {
        $criteria = [];
        
        foreach ($searchHistory as $search) {
            if ($search['search_filters']) {
                $filters = json_decode($search['search_filters'], true);
                if ($filters) {
                    $criteria = array_merge($criteria, $filters);
                }
            }
        }
        
        return $criteria;
    }
    
    private function getPropertiesByCriteria(array $criteria, int $limit): array {
        if (empty($criteria)) {
            return [];
        }
        
        $sql = "
            SELECT 
                p.*,
                u.name as seller_name,
                u.kyc_status as seller_kyc_status
            FROM properties p
            JOIN users u ON p.seller_id = u.id
            WHERE p.availability_status = 'available'
        ";
        
        $params = [];
        $conditions = [];
        
        foreach ($criteria as $key => $value) {
            if (is_string($value) && !empty($value)) {
                $conditions[] = "p.$key LIKE :$key";
                $params[":$key"] = "%$value%";
            } elseif (is_numeric($value)) {
                $conditions[] = "p.$key = :$key";
                $params[":$key"] = $value;
            }
        }
        
        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }
        
        $sql .= " ORDER BY p.created_at DESC LIMIT :limit";
        $params[':limit'] = $limit;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function processUserInteraction(int $userId, array $interaction): void {
        // Process different types of user interactions
        switch ($interaction['type']) {
            case 'property_view':
                $this->updateViewPreference($userId, $interaction['property_id']);
                break;
            case 'property_favorite':
                $this->updateFavoritePreference($userId, $interaction['property_id']);
                break;
            case 'property_search':
                $this->updateSearchPreference($userId, $interaction['search_data']);
                break;
        }
    }
    
    private function updateViewPreference(int $userId, int $propertyId): void {
        // Update user preferences based on viewed properties
        $property = $this->getPropertyDetails($propertyId);
        if ($property) {
            $this->updatePreference($userId, 'property_type', $property['type'], 0.1);
            $this->updatePreference($userId, 'district', $property['district'], 0.1);
        }
    }
    
    private function updateFavoritePreference(int $userId, int $propertyId): void {
        // Update user preferences based on favorited properties
        $property = $this->getPropertyDetails($propertyId);
        if ($property) {
            $this->updatePreference($userId, 'property_type', $property['type'], 0.2);
            $this->updatePreference($userId, 'district', $property['district'], 0.2);
        }
    }
    
    private function updateSearchPreference(int $userId, array $searchData): void {
        // Update user preferences based on search behavior
        if (isset($searchData['type'])) {
            $this->updatePreference($userId, 'property_type', $searchData['type'], 0.15);
        }
        if (isset($searchData['district'])) {
            $this->updatePreference($userId, 'district', $searchData['district'], 0.15);
        }
    }
    
    private function updatePreference(int $userId, string $type, string $value, float $weight): void {
        $sql = "
            INSERT INTO user_preferences (user_id, preference_type, preference_key, preference_value, preference_weight)
            VALUES (:user_id, :type, :value, :value, :weight)
            ON DUPLICATE KEY UPDATE 
                preference_weight = LEAST(1.0, preference_weight + :weight),
                updated_at = CURRENT_TIMESTAMP
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':type' => $type,
            ':value' => $value,
            ':weight' => $weight
        ]);
    }
}
