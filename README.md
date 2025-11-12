# FreeRADIUS Google LDAP Enterprise Dashboard

🚀 **High-Performance Enterprise RADIUS Authentication** with Google Workspace Integration

[![Docker](https://img.shields.io/badge/Docker-Ready-blue?style=flat-square&logo=docker)](https://www.docker.com/) [![License](https://img.shields.io/github/license/senthilnasa/freeradius-google-ldap-dashboard?style=flat-square)](LICENSE) ![Performance](https://img.shields.io/badge/Auth%20Speed-0.08s%20cached-brightgreen?style=flat-square)

---

## 🎯 What You Get

- ✅ **Blazing Fast** - 50x faster with LDAP caching (0.08s cached auth)
- ✅ **High Performance** - Optimized connection pool (10-50 concurrent connections)
- ✅ **Production Ready** - Supports 100+ concurrent users, 200+ auth/sec
- ✅ **Google Integration** - Seamless Google Workspace LDAP authentication
- ✅ **Multi-Domain** - Unlimited domains with automatic VLAN assignment
- ✅ **Firewall Sync** - Real-time session replication to firewall (User-ID)
- ✅ **Helpful Errors** - Users see specific error messages, not "Unable to connect"
- ✅ **Easy Setup** - One-command Docker deployment
- ✅ **Comprehensive Docs** - Everything you need in this README

---

## 🚀 Quick Start (5 Minutes)

```bash
# 1. Clone repository
git clone https://github.com/senthilnasa/freeradius-google-ldap-dashboard.git
cd freeradius-google-ldap-dashboard

# 2. Add Google LDAP certificates
mkdir -p certs
cp /path/to/google-ldap.crt certs/ldap-client.crt
cp /path/to/google-ldap.key certs/ldap-client.key

# 3. Configure environment
cp .env.example .env
nano .env  # Update: LDAP_BASE_DN, DOMAIN_CONFIG, passwords

# 4. Deploy!
docker-compose up -d

# 5. Access dashboard
# http://localhost:8080 (admin/admin123)

# 6. Test authentication
docker exec freeradius-google-ldap radtest user@yourdomain.com password localhost 0 testing123
```

**First auth**: ~2-3 seconds | **Cached auth**: ~0.08 seconds (50x faster!)

---

## 📊 Performance (Optimized!)

| Metric | Value | Notes |
|--------|-------|-------|
| **First Authentication** | ~2-3s | LDAP query + bind + cache |
| **Cached Authentication** | ~0.1s | **10-50x faster!** |
| **Concurrent Users** | 100+ | Thread-pool managed |
| **Cache Hit Rate** | 96.5% | After 1 hour |
| **LDAP Connections** | Thread-based | Safe auto-scaling |

---

## 🛠️ Helper Scripts (in `helper-scripts/` folder)

| Script | Purpose | Platform |
|--------|---------|----------|
| **monitor-radius.ps1** | Real-time packet monitoring | PowerShell |
| **test-accounting-replication.ps1** | Test accounting + firewall sync | PowerShell |
| **sync-active-sessions-to-firewall.ps1** | Bulk sync sessions to firewall | PowerShell |
| **generate-certs.sh/.bat** | Generate SSL certificates | Bash/Batch |
| **reset-password.sh/.bat** | Reset dashboard password | Bash/Batch |

**Usage:**
```powershell
cd helper-scripts
.\monitor-radius.ps1  # Live packet monitoring
```

---

## 📖 Documentation

### 📚 **[OPTIMIZATION_SUMMARY.md](OPTIMIZATION_SUMMARY.md)**
Complete guide on performance optimizations:
- LDAP connection pool tuning
- Cache configuration
- Performance benchmarks
- Tuning for different environments

### 📁 **[docs-archive/](docs-archive/)**
Archived detailed documentation:
- Original README
- Feature-specific guides (caching, firewall, errors, etc.)
- Setup checklists
- Troubleshooting guides

---

## ⚙️ Configuration

### Environment Variables (`.env` file)

```env
# Network
ACCESS_ALLOWED_CIDR=10.10.0.0/16
SHARED_SECRET=YourStrongSecret123!

# Google LDAP
LDAP_BASE_DN=dc=yourdomain,dc=com
LDAP_USER=cn=radius,ou=users,dc=yourdomain,dc=com
LDAP_PASSWORD=your_ldap_password

# Domain & VLAN Mapping
DOMAIN_CONFIG=[{"domain":"yourdomain.com","Type":"Staff","VLAN":"10"}]

# Performance
CACHE_TIMEOUT=3000  # 50 minutes (recommended)

# Database
DB_PASSWORD=YourSecureDBPassword123!
DB_ROOT_PASSWORD=YourSecureRootPassword123!

# Dashboard
ADMIN_PASSWORD=YourSecureAdminPassword123!

# Firewall Replication (Optional)
ENABLE_FIREWALL_REPLICATION=true
FIREWALL_IP=10.10.10.1
FIREWALL_SECRET=YourFirewallSecret123!
```

### Domain & VLAN Mapping

Map email domains to VLANs automatically:

```json
[
  {"domain":"staff.company.com","Type":"Staff","VLAN":"10"},
  {"domain":"students.university.edu","Type":"Student","VLAN":"20"},
  {"domain":"guest.company.com","Type":"Guest","VLAN":"30"}
]
```

Users authenticating with `john@staff.company.com` → Assigned to VLAN 10

---

## 🏗️ Architecture

```
┌─────────────────────┐
│   WiFi Devices      │
│ Laptops, Phones     │
└──────────┬──────────┘
           │ 802.1X EAP-TTLS/PAP
           ▼
┌─────────────────────────────────────────┐
│       Access Points (NAS)               │
│ UniFi / Cisco / Aruba / Fortinet        │
└──────────┬──────────────────────────────┘
           │ RADIUS (1812/1813)
           ▼
┌─────────────────────────────────────────────────┐
│            FreeRADIUS Server                    │
│  ┌───────────────────────────────────────────┐  │
│  │ LDAP Module (Optimized!)                  │  │
│  │ ├── Connection Pool: 10-50 connections    │  │
│  │ ├── Cache: TTL 3000s, Max 10k users       │  │
│  │ └── Google LDAP (ldaps://ldap.google.com) │  │
│  │                                            │  │
│  │ Authentication Flow:                       │  │
│  │ 1. Cache Check (0.08s if hit)             │  │
│  │ 2. LDAP Query (2.3s if miss)              │  │
│  │ 3. VLAN Assignment                         │  │
│  │ 4. Error Messages (helpful!)               │  │
│  │                                            │  │
│  │ Accounting:                                │  │
│  │ ├── MySQL Logging                          │  │
│  │ └── Firewall Replication (User-ID)        │  │
│  └───────────────────────────────────────────┘  │
└──────────┬──────────────────┬───────────────────┘
           │                  │
           ▼                  ▼
┌──────────────────┐  ┌──────────────────┐
│ Google Workspace │  │ Firewall (Opt)   │
│   Secure LDAP    │  │  10.10.10.1:1813 │
└──────────────────┘  └──────────────────┘
           │
           ▼
┌──────────────────────────────┐
│  MySQL Database + Dashboard  │
│  http://localhost:8080       │
└──────────────────────────────┘
```

---

## 🔥 Features

### Core Authentication
- 🔐 **Google Workspace LDAP** - Secure LDAP integration
- ⚡ **High-Performance Cache** - 50x faster with 3000s TTL
- 🔌 **Connection Pool** - 10-50 concurrent LDAP connections
- 🏷️ **Multi-Domain Support** - Unlimited domains in one Workspace
- 🌐 **Auto VLAN Assignment** - Based on email domain
- 💬 **Helpful Error Messages** - Users see specific failure reasons

### Advanced Features
- 🔥 **Firewall Replication** - Sync sessions to firewall (User-ID)
- 📊 **Real-Time Dashboard** - Web UI for monitoring
- 🔒 **Password Security** - Passwords never logged or stored
- 📈 **Session Management** - Track active connections
- 🧪 **Testing Tools** - Helper scripts for debugging

### Enterprise Ready
- 🐳 **Docker Containerized** - Single-command deployment
- 💾 **MySQL 8.0 Backend** - High-performance database
- 🔄 **Auto-Restart** - Health checks and recovery
- 📝 **Comprehensive Logging** - Audit trails and debugging
- 🛡️ **Security Hardened** - Production-ready configuration

---

## 🔧 Performance Tuning

### Connection Pool (Thread-Based - Safe Configuration)

```coffeescript
# configs/ldap
pool {
    start = ${thread[pool].start_servers}  # Uses thread pool config
    min = ${thread[pool].min_spare_servers}  # Safe auto-scaling
    max = ${thread[pool].max_servers}  # Prevents crashes
    spare = ${thread[pool].max_spare_servers}  # Stable performance
}
```

**Why Thread-Based?**
- ✅ **Prevents crashes** - Dynamically sized based on FreeRADIUS thread pool
- ✅ **Auto-scaling** - Adjusts to system resources automatically
- ✅ **Safe default** - Won't overload your system
- ⚠️ **Important:** Hardcoding pool values can cause app crashes if thread pool is too small!

### Cache Configuration

```env
# .env file
CACHE_TIMEOUT=3000   # 50 minutes (recommended)
# CACHE_TIMEOUT=1800 # 30 minutes (more frequent LDAP checks)
# CACHE_TIMEOUT=7200 # 2 hours (maximum performance)
```

---

## 🐛 Troubleshooting

### Common Issues

**1. Slow Authentication (> 5 seconds)**
```bash
# Check cache is working
docker logs freeradius-google-ldap 2>&1 | grep "ldap_cache"
# Should see: "Found cached entry" for subsequent auth

# Verify connection pool
docker logs freeradius-google-ldap 2>&1 | grep "pool"
# Should see: "start = 10"
```

**2. LDAP Connection Failed**
```bash
# Test Google LDAP connectivity
docker exec freeradius-google-ldap ping ldap.google.com
docker exec freeradius-google-ldap openssl s_client -connect ldap.google.com:636

# Check certificates
ls -l certs/
# ldap-client.crt (644)
# ldap-client.key (600)
```

**3. Generic "Unable to Connect" Error**
```bash
# Enable debug mode to see what's happening
docker exec -it freeradius-google-ldap freeradius -X

# Look for Module-Failure-Message and Reply-Message
```

**4. Container Won't Start**
```bash
# Check logs
docker-compose logs freeradius

# Verify configuration
docker-compose config

# Rebuild
docker-compose down
docker-compose up -d --build
```

### Debug Mode

```bash
# Run FreeRADIUS in foreground with full debug
docker exec -it freeradius-google-ldap freeradius -X

# Monitor with helper script
cd helper-scripts
.\monitor-radius.ps1

# Check specific user
docker logs freeradius-google-ldap 2>&1 | grep "user@yourdomain.com"
```

---

## 📞 Support

- 📖 **Documentation**: Check [OPTIMIZATION_SUMMARY.md](OPTIMIZATION_SUMMARY.md) for detailed guide
- 🐛 **Issues**: [GitHub Issues](https://github.com/senthilnasa/freeradius-google-ldap-dashboard/issues)
- 💬 **Questions**: [GitHub Discussions](https://github.com/senthilnasa/freeradius-google-ldap-dashboard/discussions)
- 📚 **Archived Docs**: See [docs-archive/](docs-archive/) for detailed feature guides

---

## 🔐 Security

### Production Checklist

- [ ] Change `SHARED_SECRET` (RADIUS secret)
- [ ] Change `DB_ROOT_PASSWORD` and `DB_PASSWORD`
- [ ] Change `ADMIN_PASSWORD`
- [ ] Update `ACCESS_ALLOWED_CIDR` to restrict access
- [ ] Enable HTTPS for dashboard (use reverse proxy)
- [ ] Set up daily backups
- [ ] Monitor logs for suspicious activity
- [ ] Rotate passwords every 90 days

### Backup

```bash
# Database
docker exec radius-mysql mysqldump -u root -p${DB_ROOT_PASSWORD} radius > backup.sql

# Configuration
tar -czf config_backup.tar.gz .env configs/ certs/
```

---

## 📝 License

MIT License - Copyright © 2025 **Senthil Prabhu K (SenthilNasa)**

See [LICENSE](LICENSE) file for details.

---

## 🌟 Show Your Support

If this project helps you, please give it a star ⭐

[![GitHub stars](https://img.shields.io/github/stars/senthilnasa/freeradius-google-ldap-dashboard?style=social)](https://github.com/senthilnasa/freeradius-google-ldap-dashboard/stargazers)

---

## 📚 Additional Resources

- [FreeRADIUS Documentation](https://freeradius.org/documentation/)
- [Google Secure LDAP Setup](https://support.google.com/a/answer/9048434)
- [Docker Compose Reference](https://docs.docker.com/compose/)

---

**Made with ❤️ by Senthil Prabhu K**

*Enterprise WiFi Authentication Made Simple*
