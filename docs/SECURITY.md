# AnySpace Security Documentation

## Overview

AnySpace implements a comprehensive, multi-layered security architecture designed to protect against common web application vulnerabilities while maintaining the accessibility and simplicity that defines the platform.

## Security Features

### 🔐 Authentication & Authorization

#### Enhanced Password Security
- **Minimum Requirements**: 8+ characters with uppercase, lowercase, numbers, and special characters
- **Strength Validation**: Real-time password complexity checking
- **Secure Hashing**: PHP's `password_hash()` with secure default algorithm
- **Pattern Detection**: Protection against common weak patterns

#### Account Protection
- **Rate Limiting**: 5 login attempts per 15 minutes per IP
- **Account Lockout**: Temporary lockout after failed attempts
- **Session Management**: Secure session storage with database persistence
- **Magic Login**: Passwordless login via email links for enhanced security

#### Multi-Factor Security
- **Magic Login Tokens**: Cryptographically secure tokens with 30-minute expiration
- **Token Rotation**: Single-use tokens prevent replay attacks
- **Session Fingerprinting**: Request fingerprinting for anomaly detection

### 🛡️ Input Validation & Sanitization

#### XSS Protection
- **DOMDocument Integration**: Server-side HTML sanitization using PHP's DOMDocument
- **Allowed Tags**: Only safe HTML tags permitted (`<p>`, `<strong>`, `<em>`, etc.)
- **Attribute Filtering**: Dangerous attributes automatically removed
- **Script Removal**: All `<script>` tags and JavaScript URLs blocked

#### SQL Injection Prevention
- **PDO Prepared Statements**: Parameterized queries prevent SQL injection
- **Input Validation**: Server-side validation for all inputs
- **Type Safety**: Strong typing ensures safe database operations

#### Request Validation
- **Size Limits**: 10MB maximum request size
- **Content-Type Validation**: Strict content-type checking
- **Header Validation**: Malicious headers filtered

### 📊 Security Monitoring & Audit

#### Comprehensive Logging
- **Event Types**: Authentication, authorization, suspicious activity
- **Risk Classification**: LOW, MEDIUM, HIGH, CRITICAL risk levels
- **Real-time Monitoring**: Immediate logging of security events
- **Database Persistence**: Events stored for long-term analysis

#### Monitored Events
- Authentication success/failure
- Permission violations
- Rate limit violations
- CSRF attempts
- Suspicious patterns
- Account lockouts
- Password changes
- Administrative actions

#### Alert System
- **High-Risk Alerts**: Immediate console alerts for critical events
- **IP Masking**: Privacy-preserving IP logging (first two octets only)
- **Request Fingerprinting**: Unique fingerprints for tracking patterns

### 🌐 Network Security

#### Security Headers
- **CSP**: Strict Content Security Policy
- **X-Frame-Options**: Clickjacking protection
- **X-Content-Type-Options**: MIME sniffing prevention
- **X-XSS-Protection**: Browser XSS filtering
- **Permissions Policy**: Feature restriction

#### Rate Limiting
- **Global Rate Limit**: 100 requests per 15 minutes
- **Auth Rate Limit**: 5 attempts per 15 minutes
- **Custom Limits**: Configurable per endpoint type
- **IP-based Tracking**: Rate limits tracked per IP + User-Agent

### 🍪 Session Security

#### Secure Sessions
- **HttpOnly**: Prevents JavaScript access
- **Secure**: HTTPS-only in production
- **SameSite**: CSRF protection
- **Database Storage**: Sessions stored in database with automatic cleanup

#### Session Management
- **Automatic Cleanup**: Expired sessions removed
- **User Session Control**: View and revoke active sessions
- **Cryptographic IDs**: SHA-256 session identifiers

### 🔒 CSRF Protection

#### Token-Based Protection
- **Unique Tokens**: Cryptographically secure random tokens
- **Session Association**: Tokens tied to user sessions
- **Automatic Validation**: All POST requests validated
- **Automatic Generation**: Tokens generated per session

## Security Configuration

### Environment Variables

```php
// Database Configuration
$host = 'localhost';
$dbname = 'anyspace';
$username = 'anyspace_user';
$password = 'secure_database_password';

// Site Configuration
$siteName = "AnySpace";
$domainName = "your-domain.com";

// Security Settings (set in core/security.php)
$maxFailedAttempts = 5;
$lockoutDuration = 900; // 15 minutes
$windowDuration = 900;  // 15 minutes
```

### Database Schema

The security system uses additional tables:

- **security_logs**: Audit trail of security events
- **rate_limits**: Rate limiting tracking
- **user_sessions**: Secure session storage
- **blacklisted_tokens**: Token revocation list
- **login_tokens**: Magic login tokens

## Installation & Setup

### 1. Run Security Migration

```bash
php core/security_migration.php
```

### 2. Set Security Headers

Security headers are automatically set by including `core/security.php`.

### 3. Configure HTTPS

Ensure your web server is configured with HTTPS in production:

```apache
# Apache example
<VirtualHost *:443>
    ServerName your-domain.com
    DocumentRoot /path/to/anyspace/public
    
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
    
    Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"
</VirtualHost>
```

## Security Testing

### Automated Tests

Run the security test suite:

```bash
php scripts/security-test.php http://your-domain.com
```

### Manual Testing Checklist

- [ ] XSS injection attempts
- [ ] SQL injection attempts
- [ ] CSRF attacks
- [ ] Session hijacking
- [ ] Brute force attacks
- [ ] Rate limit bypassing
- [ ] Authorization bypassing

## Security Best Practices

### For Developers

1. **Never log sensitive data** (passwords, tokens, personal info)
2. **Validate all inputs** at the application boundary
3. **Use prepared statements** (PDO handles this)
4. **Implement proper error handling** without information leakage
5. **Keep dependencies updated** regularly
6. **Use HTTPS in production** always

### For Deployment

1. **Set strong database passwords** (32+ characters, random)
2. **Enable HTTPS** with proper certificates
3. **Configure firewall** to limit database access
4. **Set up monitoring** for security events
5. **Regular backups** of security logs
6. **Keep system updated** with security patches

## Incident Response

### Security Event Detection

1. **Monitor logs** for HIGH/CRITICAL events
2. **Set up alerts** for suspicious patterns
3. **Track failed logins** per IP/user
4. **Monitor rate limit violations**

### Response Procedures

1. **Investigate alerts** immediately
2. **Block malicious IPs** if confirmed
3. **Revoke compromised sessions**
4. **Force password resets** if needed
5. **Document incidents** for analysis

## Compliance & Standards

### Security Standards Coverage

- ✅ SQL Injection Prevention
- ✅ Cross-Site Scripting (XSS) Protection
- ✅ Cross-Site Request Forgery (CSRF) Protection
- ✅ Session Management Security
- ✅ Authentication Security
- ✅ Input Validation
- ✅ Security Headers
- ✅ Rate Limiting
- ✅ Security Logging & Monitoring
- ✅ Password Security

### Accessibility & Security Balance

AnySpace maintains security without compromising accessibility:
- Clear error messages for screen readers
- Consistent security flows for cognitive load reduction
- Timeout warnings for users who need more time
- Simple but secure authentication methods

## Migration from Basic Security

If upgrading from a basic AnySpace installation:

1. Run the security migration script
2. Update your configuration files
3. Test all security features
4. Review and update any custom code
5. Train users on new security features

## Regular Security Reviews

### Monthly
- Review security logs for patterns
- Update dependencies
- Check for new vulnerabilities

### Quarterly
- Full security audit
- Penetration testing
- Update security documentation
- Review and update security policies

### Annually
- Complete security assessment
- Third-party security audit
- Update security training
- Review incident response procedures

---

**Security Contact**: For security issues, please report to the development team through secure channels. Do not post security vulnerabilities in public issues.

*Last Updated: Current Date - Version 1.0*