# 🏠 RealEstate Property Availability System

## Overview

The Property Availability System is a comprehensive solution that addresses the critical need for tracking property availability status and provides intelligent content-based filtering for property recommendations. This system ensures that buyers and admins always know the current status of properties and receive personalized recommendations.

## 🎯 Key Features

### 1. Property Availability Tracking
- **Real-time Status Updates**: Properties can be marked as Available, Under Offer, Sold, Withdrawn, or Expired
- **Status History**: Complete audit trail of all status changes with timestamps and reasons
- **Automatic Triggers**: Database triggers automatically update related data when status changes
- **Admin Controls**: Admins can manage property status with reason tracking

### 2. Content-Based Filtering & Recommendations
- **Personalized Recommendations**: AI-powered suggestions based on user preferences and behavior
- **Content Similarity**: Properties are matched based on features, location, price, and type
- **User Preference Learning**: System learns from user interactions (views, favorites, searches)
- **Trending Properties**: Popular properties based on views, favorites, and offers
- **Search-Based Suggestions**: Properties recommended based on search history

### 3. Advanced Analytics
- **View Tracking**: Monitor property popularity and user engagement
- **Performance Metrics**: Track favorites, offers, and conversion rates
- **Market Trends**: Analyze property performance by district and type
- **Admin Dashboard**: Comprehensive overview of all property statuses

## 🗄️ Database Schema

### Enhanced Properties Table
```sql
ALTER TABLE properties 
ADD COLUMN availability_status ENUM('available', 'under_offer', 'sold', 'withdrawn', 'expired') DEFAULT 'available',
ADD COLUMN available_from TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN available_until TIMESTAMP NULL,
ADD COLUMN last_status_change TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN status_change_reason VARCHAR(255) NULL,
ADD COLUMN property_features JSON NULL,
ADD COLUMN property_tags VARCHAR(500) NULL,
ADD COLUMN view_count INT DEFAULT 0,
ADD COLUMN favorite_count INT DEFAULT 0;
```

### New Tables Created
- `property_availability_history` - Tracks all status changes
- `property_features` - Stores property features for filtering
- `user_preferences` - User preferences for recommendations
- `property_views` - Tracks property views and engagement
- `property_recommendations` - Stores calculated recommendations
- `property_search_history` - User search patterns

## 🚀 Installation & Setup

### 1. Run the Setup Script
```bash
php setup_property_availability.php
```

### 2. Verify Database Tables
Check that all new tables and columns were created successfully.

### 3. Test the System
- Access buyer dashboard to see recommendations
- Test admin property availability management
- Verify status updates work correctly

## 📱 User Experience

### For Buyers
- **Personalized Dashboard**: See properties recommended specifically for you
- **Trending Properties**: Discover popular properties in your area
- **Search-Based Suggestions**: Get recommendations based on your search history
- **Availability Status**: Clear indication of property availability
- **Smart Filtering**: Advanced search with content-based suggestions

### For Admins
- **Status Management**: Easy property status updates with reason tracking
- **Performance Monitoring**: Track property views, favorites, and offers
- **Market Insights**: Analyze trends and property performance
- **Bulk Operations**: Manage multiple properties efficiently

### For Sellers
- **Status Control**: Update property availability as needed
- **Performance Tracking**: Monitor property engagement metrics
- **Market Visibility**: Properties appear in relevant recommendations

## 🔧 Technical Implementation

### Content-Based Filtering Algorithm
The recommendation system uses a sophisticated scoring algorithm:

1. **Property Type Match** (30%): House vs Land preferences
2. **District Match** (25%): Geographic preferences
3. **Price Range Match** (20%): Budget alignment
4. **Area Match** (15%): Size preferences
5. **Popularity Bonus** (10%): Views, favorites, offers

### User Preference Learning
The system continuously learns from user behavior:
- **View Tracking**: Updates preferences when properties are viewed
- **Favorite Actions**: Strengthens preferences for favorited properties
- **Search Patterns**: Learns from search queries and filters
- **Interaction History**: Builds comprehensive user profiles

### Performance Optimization
- **Database Indexes**: Optimized queries for fast recommendations
- **Caching**: Recommendation scores are cached and updated periodically
- **Lazy Loading**: Recommendations load asynchronously for better UX

## 📊 API Endpoints

### Property View Tracking
```
POST /track_property_view.php
{
    "action": "track_view",
    "property_id": 123
}
```

### Content-Based Filtering
```php
$contentFiltering = new ContentBasedFiltering($pdo);

// Get personalized recommendations
$recommendations = $contentFiltering->getPersonalizedRecommendations($userId, 10);

// Get similar properties
$similar = $contentFiltering->getSimilarProperties($propertyId, 8);

// Get trending properties
$trending = $contentFiltering->getTrendingProperties(10);
```

### Property Availability Management
```php
$availabilityManager = new PropertyAvailabilityManager($pdo);

// Update property status
$success = $availabilityManager->updatePropertyStatus($propertyId, 'sold', $userId, 'Property sold');

// Get availability info
$info = $availabilityManager->getPropertyAvailabilityInfo($propertyId);
```

## 🎨 Frontend Integration

### Buyer Dashboard Enhancements
- **Recommendations Section**: Personalized property suggestions
- **Trending Properties**: Popular properties with engagement metrics
- **Search-Based Suggestions**: Properties matching search history
- **Availability Badges**: Clear status indicators on all properties

### Admin Interface
- **Property Management Table**: Comprehensive property overview
- **Status Update Modal**: Easy status changes with reason tracking
- **Statistics Dashboard**: Performance metrics and trends
- **Filtering System**: Advanced search and filtering capabilities

## 🔒 Security & Validation

### Input Validation
- All property IDs are validated as integers
- Status transitions are validated against business rules
- User permissions are checked for admin actions

### Data Integrity
- Database transactions ensure data consistency
- Foreign key constraints maintain referential integrity
- Audit trails track all changes for compliance

## 📈 Monitoring & Analytics

### Key Metrics
- **Property Views**: Track engagement and popularity
- **Conversion Rates**: Monitor favorites and offers
- **Status Changes**: Track property lifecycle
- **User Engagement**: Monitor recommendation effectiveness

### Performance Monitoring
- **Query Performance**: Monitor database query execution times
- **Recommendation Accuracy**: Track user satisfaction with suggestions
- **System Load**: Monitor server performance during peak usage

## 🚨 Troubleshooting

### Common Issues
1. **Recommendations Not Showing**: Check if user preferences exist
2. **Status Updates Failing**: Verify database triggers are working
3. **Performance Issues**: Check database indexes and query optimization

### Debug Mode
Enable debug logging in the configuration files to troubleshoot issues.

## 🔮 Future Enhancements

### Planned Features
- **Machine Learning**: Advanced recommendation algorithms
- **Real-time Notifications**: Instant updates on property status changes
- **Mobile App Integration**: Native mobile experience
- **Advanced Analytics**: Predictive market insights
- **Multi-language Support**: International property markets

### Scalability Improvements
- **Microservices Architecture**: Separate recommendation engine
- **Redis Caching**: Faster recommendation delivery
- **Elasticsearch**: Advanced property search capabilities
- **CDN Integration**: Faster content delivery

## 📚 Documentation

### Related Files
- `config/content_based_filtering.php` - Recommendation engine
- `config/property_availability_manager.php` - Status management
- `database/property_availability_system.sql` - Database schema
- `public/admin/property-availability.php` - Admin interface
- `public/track_property_view.php` - View tracking endpoint

### Configuration
- Database connection settings in `config/db.php`
- Recommendation algorithm parameters in content filtering class
- Status transition rules in availability manager

## 🤝 Support

For technical support or feature requests, please refer to the main project documentation or contact the development team.

---

**RealEstate Property Availability System** - Making property discovery intelligent and transparent. 🏠✨
