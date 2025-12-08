# Legacy Dashboard (DEPRECATED)

⚠️ **This dashboard is deprecated and no longer maintained.**

**Date Deprecated:** December 2024
**Reason:** Replaced by modern MVC architecture dashboard in `radius-gui/`

---

## Migration

Please use the new modern dashboard located in the `radius-gui/` directory.

See [Migration Guide](../../radius-gui/MIGRATION.md) for detailed instructions on migrating to the new dashboard.

---

## Why Deprecated?

The legacy dashboard (`dashboard/`) had several limitations:

### Technical Limitations
- ❌ **Monolithic Architecture**: Single 41KB `index.php` file with all logic
- ❌ **No MVC Structure**: Mixed presentation, business logic, and data access
- ❌ **Limited Maintainability**: Difficult to extend and modify
- ❌ **No Dependency Management**: No Composer integration
- ❌ **Basic Security**: Limited protection mechanisms
- ❌ **No Unit Tests**: No automated testing infrastructure

### Feature Limitations
- ❌ **No PDF Generation**: Only basic CSV export
- ❌ **No Role-Based Access Control**: All or nothing permissions
- ❌ **Limited Error Tracking**: Basic error display only
- ❌ **No Advanced Reporting**: Limited analytics capabilities
- ❌ **Basic UI**: No modern component framework
- ❌ **Limited Export Options**: CSV only, no PDF reports

---

## What Replaced It?

The new dashboard (`radius-gui/`) offers significant improvements:

### Modern Architecture
- ✅ **MVC Pattern**: Clean separation of concerns
- ✅ **PSR-4 Autoloading**: Modern PHP standards
- ✅ **Composer Dependencies**: Package management
- ✅ **Helper Classes**: Reusable utilities (Auth, Database, Utils, PDF)
- ✅ **Modular Design**: Easy to extend and maintain

### Enhanced Features
- ✅ **14 Comprehensive Pages**: Complete monitoring suite
- ✅ **PDF Report Generation**: Professional reports with TCPDF
- ✅ **CSV & PDF Exports**: Multiple export formats
- ✅ **Role-Based Access Control**: 3-tier permission system
  - Superadmin: Full access
  - Network Admin: All reports, no user management
  - Helpdesk: View-only access
- ✅ **Enhanced Error Tracking**: Integration with error_type, reply_message columns
- ✅ **Bootstrap 5 UI**: Modern, responsive design
- ✅ **DataTables Integration**: Sortable, searchable tables
- ✅ **Chart.js Integration**: Visual analytics

### Security Improvements
- ✅ **Bcrypt Password Hashing**: Modern password security
- ✅ **CSRF Protection**: Token-based form security
- ✅ **Prepared Statements**: SQL injection prevention
- ✅ **XSS Protection**: Output escaping throughout
- ✅ **Session Security**: Proper session management

### Additional Features
- ✅ **User Management**: CRUD operations for operators
- ✅ **Settings Page**: System configuration view
- ✅ **Real-time KPIs**: Live statistics
- ✅ **Advanced Filtering**: Date ranges, error types, etc.
- ✅ **Timezone Support**: GMT/UTC storage, IST display
- ✅ **Comprehensive Documentation**: 5+ detailed guides

---

## Legacy Dashboard Files

The original dashboard files have been archived in this directory for reference:

```
dashboard/
├── Dockerfile              # PHP 8.2 Apache container
├── index.php              # Main dashboard file (41KB monolithic)
├── login.php              # Login page
├── auth.php               # Authentication handler
├── change-password.php    # Password change functionality
├── disconnect.php         # Session disconnect
├── apache-config.conf     # Apache virtual host config
└── api/
    ├── stats.php          # Dashboard statistics API
    └── error-stats.php    # Error statistics API
```

---

## Keeping This for Reference

This code is preserved for:

1. **Historical Reference**: Understanding the evolution of the project
2. **Migration Assistance**: Comparing old vs new implementations
3. **Data Structure Reference**: Understanding legacy data formats
4. **Rollback Option**: Emergency fallback if needed (not recommended)

---

## Database Compatibility

✅ **Good News:** The new dashboard is 100% compatible with the existing database schema.

- Uses the same `operators` table for authentication
- Uses the same `radacct`, `radpostauth`, `radcheck` tables
- Enhanced with new columns (`reply_message`, `error_type`, `authdate_utc`)
- No data migration required - just switch dashboards!

---

## Performance Comparison

| Metric | Legacy Dashboard | New Dashboard |
|--------|-----------------|---------------|
| **Files** | 8 files | 40+ files |
| **Architecture** | Monolithic | MVC |
| **Lines of Code** | ~2,000 | ~10,000+ |
| **Pages** | 5 basic pages | 14 comprehensive pages |
| **Export Formats** | CSV only | CSV + PDF |
| **Reports** | Basic lists | 3 advanced reports |
| **Security Score** | Basic | Enterprise-grade |
| **Maintainability** | Low | High |
| **Extensibility** | Difficult | Easy |

---

## Support

❌ **No support will be provided for the legacy dashboard.**

For questions or issues, please:
1. Migrate to the new dashboard (`radius-gui/`)
2. See [Migration Guide](../../radius-gui/MIGRATION.md)
3. Refer to the new dashboard documentation

---

## Quick Migration Steps

1. **Backup** (optional but recommended):
   ```bash
   # Backup your database
   mysqldump -u radius -p radius > backup.sql
   ```

2. **Update Docker Compose**:
   - Comment out the old `dashboard` service
   - Add the new `webapp` service (see main README.md)

3. **Start New Dashboard**:
   ```bash
   docker-compose up -d webapp
   ```

4. **Access New Dashboard**:
   - URL: http://localhost:8080/radius-gui/public/
   - Login: Same credentials from `operators` table
   - Default: admin / password

5. **Verify**:
   - All data should be visible immediately
   - All features should work out of the box
   - No configuration changes needed

---

## Need Help?

See the comprehensive documentation:

- [New Dashboard README](../../radius-gui/README.md)
- [Deployment Guide](../../radius-gui/DEPLOYMENT.md)
- [Migration Guide](../../radius-gui/MIGRATION.md)
- [Testing Guide](../../TESTING.md)
- [Main Project README](../../README.md)

---

**Last Updated:** December 2024
**Status:** DEPRECATED - DO NOT USE FOR NEW DEPLOYMENTS
**Replacement:** `radius-gui/` - Modern Dashboard
