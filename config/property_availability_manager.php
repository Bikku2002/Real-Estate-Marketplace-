<?php
declare(strict_types=1);

/**
 * Property Availability Manager
 * 
 * This class manages property availability status and provides:
 * - Status updates and tracking
 * - Availability history
 * - Admin notifications
 * - Buyer availability information
 */
class PropertyAvailabilityManager {
    private $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Update property availability status
     */
    public function updatePropertyStatus(int $propertyId, string $newStatus, int $changedBy, string $reason = null): bool {
        try {
            // Get current status
            $currentStatus = $this->getPropertyStatus($propertyId);
            if (!$currentStatus) {
                throw new Exception("Property not found");
            }
            
            // Validate status transition
            if (!$this->isValidStatusTransition($currentStatus, $newStatus)) {
                throw new Exception("Invalid status transition from {$currentStatus} to {$newStatus}");
            }
            
            // Begin transaction
            $this->pdo->beginTransaction();
            
            // Update property status
            $sql = "
                UPDATE properties 
                SET 
                    availability_status = :new_status,
                    last_status_change = CURRENT_TIMESTAMP,
                    status_change_reason = :reason
                WHERE id = :property_id
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':new_status' => $newStatus,
                ':reason' => $reason,
                ':property_id' => $propertyId
            ]);
            
            // Record status change in history
            $this->recordStatusChange($propertyId, $currentStatus, $newStatus, $changedBy, $reason);
            
            // Handle status-specific actions
            $this->handleStatusSpecificActions($propertyId, $newStatus, $changedBy);
            
            // Commit transaction
            $this->pdo->commit();
            
            // Log the change
            error_log("Property {$propertyId} status changed from {$currentStatus} to {$newStatus} by user {$changedBy}");
            
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error updating property status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get property availability status
     */
    public function getPropertyStatus(int $propertyId): ?string {
        try {
            $sql = "SELECT availability_status FROM properties WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $propertyId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? $result['availability_status'] : null;
            
        } catch (Exception $e) {
            error_log("Error getting property status: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get property availability information for buyers
     */
    public function getPropertyAvailabilityInfo(int $propertyId): ?array {
        try {
            $sql = "
                SELECT 
                    p.id,
                    p.title,
                    p.availability_status,
                    p.available_from,
                    p.available_until,
                    p.last_status_change,
                    p.status_change_reason,
                    p.view_count,
                    p.favorite_count,
                    COUNT(DISTINCT o.id) as active_offers,
                    COUNT(DISTINCT f.id) as total_favorites,
                    u.name as seller_name,
                    u.kyc_status as seller_kyc_status
                FROM properties p
                JOIN users u ON p.seller_id = u.id
                LEFT JOIN offers o ON p.id = o.property_id AND o.status IN ('pending', 'countered')
                LEFT JOIN favorites f ON p.id = f.property_id
                WHERE p.id = :property_id
                GROUP BY p.id
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':property_id' => $propertyId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                // Add availability message
                $result['availability_message'] = $this->getAvailabilityMessage($result['availability_status']);
                $result['availability_badge'] = $this->getAvailabilityBadge($result['availability_status']);
                $result['can_make_offer'] = $this->canMakeOffer($result['availability_status']);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Error getting property availability info: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all properties with availability status for admin
     */
    public function getAdminPropertyAvailabilityList(array $filters = []): array {
        try {
            $sql = "
                SELECT 
                    p.id,
                    p.title,
                    p.type,
                    p.district,
                    p.price,
                    p.availability_status,
                    p.available_from,
                    p.last_status_change,
                    p.status_change_reason,
                    p.view_count,
                    p.favorite_count,
                    u.name as seller_name,
                    u.email as seller_email,
                    u.kyc_status as seller_kyc_status,
                    COUNT(DISTINCT o.id) as active_offers,
                    COUNT(DISTINCT f.id) as total_favorites,
                    DATEDIFF(NOW(), p.created_at) as days_listed
                FROM properties p
                JOIN users u ON p.seller_id = u.id
                LEFT JOIN offers o ON p.id = o.property_id AND o.status IN ('pending', 'countered')
                LEFT JOIN favorites f ON p.id = f.property_id
                WHERE 1=1
            ";
            
            $params = [];
            
            // Apply filters
            if (!empty($filters['status'])) {
                $sql .= " AND p.availability_status = :status";
                $params[':status'] = $filters['status'];
            }
            
            if (!empty($filters['district'])) {
                $sql .= " AND p.district = :district";
                $params[':district'] = $filters['district'];
            }
            
            if (!empty($filters['type'])) {
                $sql .= " AND p.type = :type";
                $params[':type'] = $filters['type'];
            }
            
            if (!empty($filters['seller_id'])) {
                $sql .= " AND p.seller_id = :seller_id";
                $params[':seller_id'] = $filters['seller_id'];
            }
            
            $sql .= " GROUP BY p.id ORDER BY p.last_status_change DESC, p.created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add availability actions for admin
            foreach ($properties as &$property) {
                $property['available_actions'] = $this->getAvailableAdminActions($property['availability_status']);
                $property['status_summary'] = $this->getStatusSummary($property);
            }
            
            return $properties;
            
        } catch (Exception $e) {
            error_log("Error getting admin property availability list: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get availability statistics for admin dashboard
     */
    public function getAvailabilityStatistics(): array {
        try {
            $sql = "
                SELECT 
                    availability_status,
                    COUNT(*) as count,
                    AVG(price) as avg_price,
                    SUM(view_count) as total_views,
                    SUM(favorite_count) as total_favorites
                FROM properties
                GROUP BY availability_status
            ";
            
            $stmt = $this->pdo->query($sql);
            $statusStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get recent status changes
            $recentChanges = $this->getRecentStatusChanges(10);
            
            // Get properties requiring attention
            $attentionNeeded = $this->getPropertiesRequiringAttention();
            
            return [
                'status_statistics' => $statusStats,
                'recent_changes' => $recentChanges,
                'attention_needed' => $attentionNeeded
            ];
            
        } catch (Exception $e) {
            error_log("Error getting availability statistics: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get properties that need admin attention
     */
    public function getPropertiesRequiringAttention(): array {
        try {
            $sql = "
                SELECT 
                    p.id,
                    p.title,
                    p.availability_status,
                    p.last_status_change,
                    p.status_change_reason,
                    u.name as seller_name,
                    u.email as seller_email,
                    DATEDIFF(NOW(), p.last_status_change) as days_since_change
                FROM properties p
                JOIN users u ON p.seller_id = u.id
                WHERE 
                    (p.availability_status = 'under_offer' AND DATEDIFF(NOW(), p.last_status_change) > 7)
                    OR (p.availability_status = 'available' AND DATEDIFF(NOW(), p.created_at) > 90)
                    OR (p.availability_status = 'expired')
                ORDER BY p.last_status_change ASC
                LIMIT 20
            ";
            
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error getting properties requiring attention: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get recent status changes
     */
    public function getRecentStatusChanges(int $limit = 10): array {
        try {
            $sql = "
                SELECT 
                    pah.*,
                    p.title as property_title,
                    p.type as property_type,
                    p.district as property_district,
                    u.name as changed_by_name
                FROM property_availability_history pah
                JOIN properties p ON pah.property_id = p.id
                JOIN users u ON pah.changed_by = u.id
                ORDER BY pah.changed_at DESC
                LIMIT :limit
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error getting recent status changes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if property is available for offers
     */
    public function isPropertyAvailableForOffers(int $propertyId): bool {
        $status = $this->getPropertyStatus($propertyId);
        return in_array($status, ['available', 'under_offer']);
    }
    
    /**
     * Get available properties count by status
     */
    public function getAvailablePropertiesCount(): array {
        try {
            $sql = "
                SELECT 
                    availability_status,
                    COUNT(*) as count
                FROM properties
                GROUP BY availability_status
            ";
            
            $stmt = $this->pdo->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $counts = [];
            foreach ($results as $result) {
                $counts[$result['availability_status']] = $result['count'];
            }
            
            return $counts;
            
        } catch (Exception $e) {
            error_log("Error getting available properties count: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Private helper methods
     */
    
    private function isValidStatusTransition(string $currentStatus, string $newStatus): bool {
        $validTransitions = [
            'available' => ['under_offer', 'sold', 'withdrawn', 'expired'],
            'under_offer' => ['available', 'sold', 'withdrawn'],
            'sold' => ['available'], // For relisting
            'withdrawn' => ['available'], // For relisting
            'expired' => ['available'] // For relisting
        ];
        
        return isset($validTransitions[$currentStatus]) && 
               in_array($newStatus, $validTransitions[$currentStatus]);
    }
    
    private function recordStatusChange(int $propertyId, string $oldStatus, string $newStatus, int $changedBy, ?string $reason): void {
        $sql = "
            INSERT INTO property_availability_history 
            (property_id, old_status, new_status, changed_by, change_reason)
            VALUES (:property_id, :old_status, :new_status, :changed_by, :reason)
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':property_id' => $propertyId,
            ':old_status' => $oldStatus,
            ':new_status' => $newStatus,
            ':changed_by' => $changedBy,
            ':reason' => $reason
        ]);
    }
    
    private function handleStatusSpecificActions(int $propertyId, string $newStatus, int $changedBy): void {
        switch ($newStatus) {
            case 'sold':
                // Mark all pending offers as rejected
                $this->rejectPendingOffers($propertyId);
                break;
                
            case 'withdrawn':
                // Notify interested buyers
                $this->notifyInterestedBuyers($propertyId, 'withdrawn');
                break;
                
            case 'expired':
                // Auto-expire old listings
                $this->handleExpiredListing($propertyId);
                break;
        }
    }
    
    private function rejectPendingOffers(int $propertyId): void {
        $sql = "
            UPDATE offers 
            SET status = 'rejected', message = 'Property has been sold'
            WHERE property_id = :property_id AND status = 'pending'
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':property_id' => $propertyId]);
    }
    
    private function notifyInterestedBuyers(int $propertyId, string $status): void {
        // Get interested buyers (those who favorited or made offers)
        $sql = "
            SELECT DISTINCT u.id, u.name, u.email
            FROM users u
            LEFT JOIN favorites f ON u.id = f.user_id
            LEFT JOIN offers o ON u.id = o.buyer_id
            WHERE (f.property_id = :property_id OR o.property_id = :property_id)
            AND u.role = 'buyer'
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':property_id' => $propertyId]);
        $interestedBuyers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Send notifications (implement notification system)
        foreach ($interestedBuyers as $buyer) {
            $this->sendPropertyStatusNotification($buyer, $propertyId, $status);
        }
    }
    
    private function handleExpiredListing(int $propertyId): void {
        // Auto-expire listings older than 90 days
        $sql = "
            UPDATE properties 
            SET availability_status = 'expired'
            WHERE id = :property_id 
            AND DATEDIFF(NOW(), created_at) > 90
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':property_id' => $propertyId]);
    }
    
    private function getAvailabilityMessage(string $status): string {
        $messages = [
            'available' => 'This property is currently available for purchase',
            'under_offer' => 'This property is under offer but still accepting backup offers',
            'sold' => 'This property has been sold',
            'withdrawn' => 'This property has been withdrawn from the market',
            'expired' => 'This listing has expired'
        ];
        
        return $messages[$status] ?? 'Status unknown';
    }
    
    private function getAvailabilityBadge(string $status): string {
        $badges = [
            'available' => '<span class="badge badge-success">Available</span>',
            'under_offer' => '<span class="badge badge-warning">Under Offer</span>',
            'sold' => '<span class="badge badge-danger">Sold</span>',
            'withdrawn' => '<span class="badge badge-secondary">Withdrawn</span>',
            'expired' => '<span class="badge badge-dark">Expired</span>'
        ];
        
        return $badges[$status] ?? '<span class="badge badge-secondary">Unknown</span>';
    }
    
    private function canMakeOffer(string $status): bool {
        return in_array($status, ['available', 'under_offer']);
    }
    
    private function getAvailableAdminActions(string $status): array {
        $actions = [];
        
        switch ($status) {
            case 'available':
                $actions[] = ['action' => 'mark_under_offer', 'label' => 'Mark Under Offer', 'class' => 'btn-warning'];
                $actions[] = ['action' => 'mark_sold', 'label' => 'Mark Sold', 'class' => 'btn-success'];
                $actions[] = ['action' => 'withdraw', 'label' => 'Withdraw', 'class' => 'btn-secondary'];
                break;
                
            case 'under_offer':
                $actions[] = ['action' => 'mark_sold', 'label' => 'Mark Sold', 'class' => 'btn-success'];
                $actions[] = ['action' => 'mark_available', 'label' => 'Mark Available', 'class' => 'btn-primary'];
                $actions[] = ['action' => 'withdraw', 'label' => 'Withdraw', 'class' => 'btn-secondary'];
                break;
                
            case 'sold':
            case 'withdrawn':
            case 'expired':
                $actions[] = ['action' => 'relist', 'label' => 'Relist', 'class' => 'btn-primary'];
                break;
        }
        
        return $actions;
    }
    
    private function getStatusSummary(array $property): string {
        $summary = "Property is {$property['availability_status']}";
        
        if ($property['availability_status'] === 'available') {
            $summary .= " for {$property['days_listed']} days";
        }
        
        if ($property['active_offers'] > 0) {
            $summary .= " with {$property['active_offers']} active offers";
        }
        
        if ($property['total_favorites'] > 0) {
            $summary .= " and {$property['total_favorites']} favorites";
        }
        
        return $summary;
    }
    
    private function sendPropertyStatusNotification(array $buyer, int $propertyId, string $status): void {
        // Implementation for sending email/SMS notifications
        // This would integrate with your notification system
        
        $property = $this->getPropertyDetails($propertyId);
        if ($property) {
            $subject = "Property Status Update: {$property['title']}";
            $message = "Dear {$buyer['name']},\n\n";
            $message .= "The property '{$property['title']}' in {$property['district']} has been marked as {$status}.\n\n";
            $message .= "Thank you for your interest.\n\n";
            $message .= "Best regards,\nRealEstate Team";
            
            // Log notification (implement actual sending)
            error_log("Notification sent to {$buyer['email']}: {$subject}");
        }
    }
    
    private function getPropertyDetails(int $propertyId): ?array {
        $sql = "SELECT title, district FROM properties WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $propertyId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
