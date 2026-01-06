# Role-Based Features for RealEstate

This document explains the new role-based functionality implemented for buyers and sellers on the RealEstate platform.

## Overview

The platform now supports two distinct user roles with different capabilities:

- **Buyers**: Can browse properties, save favorites, make offers, and negotiate prices
- **Sellers**: Can list properties, manage listings, and respond to buyer offers

## User Registration & Role Selection

### Registration Process
1. Users select their role during registration (Buyer or Seller)
2. Role selection determines available features and navigation
3. KYC verification is optional but recommended for trust building

### Role Selection Interface
- **Buy Property**: For users looking to purchase real estate
- **Sell Property**: For users wanting to list properties for sale

## Seller Features

### Property Management
- **Add Property**: Comprehensive property listing form with:
  - Basic information (title, type, price, location)
  - Detailed specifications (bedrooms, bathrooms, area)
  - Image uploads (cover + additional images)
  - Negotiable price option
  - District selection from all 77 Nepali districts

- **My Properties Dashboard**: 
  - View all listed properties
  - Track offer counts and property statistics
  - Edit or delete property listings
  - Monitor buyer interest

### Property Types Supported
- **Land**: Area in sq ft or ana, location details
- **House**: Bedrooms, bathrooms, area, amenities

### Image Management
- Cover image (required)
- Multiple additional images
- Automatic image optimization and storage
- Support for JPG, PNG, GIF formats

## Buyer Features

### Property Discovery
- **Browse Properties**: Advanced search and filtering
  - Property type (land/house)
  - District selection
  - Price range filtering
  - Keyword search
  - Auto-filtering for instant results

### Property Information
- Detailed property cards with:
  - High-quality images
  - Price and negotiable status
  - Location details
  - Property specifications
  - Seller information with KYC status

### Offer System
- **Make Offers**: 
  - Bid on properties with custom amounts
  - Add personal messages to sellers
  - Track offer status (pending, accepted, rejected)
  - Negotiate prices for negotiable properties

- **My Offers Dashboard**:
  - View all submitted offers
  - Track offer status and responses
  - Contact sellers for accepted offers
  - Withdraw pending offers

### Favorites & Queries
- Save favorite properties (placeholder functionality)
- Contact sellers with property-specific questions
- Build property wishlists

## Negotiation Features

### Price Negotiation
- **Negotiable Properties**: Clearly marked with 💬 badge
- **Offer System**: Structured bidding process
- **Counter Offers**: Support for back-and-forth negotiation
- **Price Transparency**: Listed price vs. offer amount tracking

### Communication
- Direct messaging between buyers and sellers
- Property-specific inquiries
- Offer explanations and negotiations
- Contact information sharing

## Admin Management

### Property Oversight
- **Property Management**: Admin can view and manage all listings
- **Offer Monitoring**: Track all offers and negotiations
- **User Verification**: KYC status management
- **Content Moderation**: Property listing approval

### Dashboard Enhancements
- Property statistics and counts
- User role distribution
- Offer activity monitoring
- Quick access to property management

## Database Schema Updates

### New Tables
- `favorites`: User property favorites
- `property_queries`: Buyer-seller communications

### Enhanced Properties Table
- `is_negotiable`: Boolean flag for price negotiation
- `status`: Property listing status (active, sold, inactive)

### Indexes for Performance
- Property search optimization
- Offer tracking efficiency
- User role-based queries

## Security & Trust Features

### KYC Verification
- Document upload and verification
- Identity verification status
- Trust badges for verified users
- Admin review process

### User Authentication
- Role-based access control
- Secure property management
- Offer validation and tracking
- User privacy protection

## Technical Implementation

### File Structure
```
Final6/public/
├── add-property.php          # Seller property listing
├── my-properties.php         # Seller property management
├── buyer-dashboard.php       # Buyer property browsing
├── my-offers.php            # Buyer offer tracking
└── property.php             # Enhanced property details
```

### Database Updates
- `update_role_based_features.sql`: Complete schema updates
- New indexes for performance
- Sample data for testing

### Navigation Updates
- Role-based navigation menus
- Conditional feature access
- User experience optimization

## User Experience Features

### Responsive Design
- Mobile-friendly interfaces
- Modern card-based layouts
- Interactive hover effects
- Smooth animations

### Search & Filtering
- Real-time search results
- Advanced filtering options
- Auto-complete suggestions
- Saved search preferences

### Notifications
- Offer status updates
- New property alerts
- Price change notifications
- Communication updates

## Future Enhancements

### Planned Features
- **Favorites System**: Complete implementation
- **Messaging System**: Real-time chat
- **Property Analytics**: View tracking and insights
- **Advanced Search**: Map-based property discovery
- **Mobile App**: Native mobile experience

### Integration Opportunities
- **Payment Gateway**: Secure transaction processing
- **Document Management**: Legal document handling
- **Property Valuation**: AI-powered price estimation
- **Market Analytics**: Real estate market insights

## Testing & Quality Assurance

### Test Scenarios
- User registration with different roles
- Property listing and management
- Offer creation and tracking
- Search and filtering functionality
- Admin property oversight

### Sample Data
- Test properties for different districts
- Sample offers and negotiations
- User accounts with various roles
- KYC verification examples

## Deployment Notes

### Requirements
- PHP 7.4+ with PDO support
- MySQL 5.7+ or MariaDB 10.2+
- File upload permissions for images
- Session management enabled

### Installation Steps
1. Run database update script
2. Upload new PHP files
3. Set proper file permissions
4. Test role-based functionality
5. Verify admin access

### Configuration
- Database connection settings
- File upload limits
- Image storage paths
- Admin access controls

## Support & Maintenance

### User Support
- Role-specific help documentation
- Feature tutorials and guides
- FAQ sections for common issues
- Contact support channels

### Technical Support
- Database optimization
- Performance monitoring
- Security updates
- Feature enhancements

---

This role-based system provides a comprehensive real estate platform experience, ensuring that buyers and sellers have access to the tools they need while maintaining security and trust throughout the transaction process.
