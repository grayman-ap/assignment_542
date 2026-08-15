# Risk Register

**Project:** Student Registration Web Application (IFT 542) — hardening assessment
**Owner:** Peter Adeshina (2021/1/84154CF)
**Scoring:** Likelihood (L) and Impact (I) on 1-5. **Risk = L × I.**
Rating bands: 1-4 Low, 5-9 Medium, 10-16 High, 17-25 Critical.

| ID | Risk (STRIDE) | L | I | Risk | Rating | Status | Owner |
|----|---------------|---|---|------|--------|--------|-------|
| R1 | SQL injection via concatenated login/profile/course queries (Tampering) | 4 | 5 | 20 | Critical | **Mitigated** (prepared statements, validation, least privilege) | App dev |
| R2 | Identity/session spoofing: `' OR '1'='1'` login bypass, session fixation/theft (Spoofing) | 4 | 5 | 20 | Critical | **Mitigated** (prepared statements, session rotation, Argon2id, HttpOnly/SameSite) | App dev |
| R3 | Information disclosure: verbose errors, plaintext passwords, stored XSS, default admin (Info Disclosure) | 4 | 4 | 16 | High | **Mitigated** (generic errors, Argon2id, output encoding + CSP, no defaults) | App dev |
| R4 | Privilege escalation via missing role checks / unsafe upload / CSRF admin actions (Elevation) | 3 | 5 | 15 | High | **Mitigated** (role checks, upload hardening, CSRF tokens) | App dev |
| R5 | Repudiation: no audit trail for registrations and admin actions (Repudiation) | 4 | 3 | 12 | High | **Mitigated** (structured JSON audit log) | App dev |
| R6 | Brute-force login / upload disk exhaustion / SSRF-amplified DoS (DoS) | 4 | 3 | 12 | High | **Mitigated** (rate limit, lockout, upload caps, SSRF allowlist) | App dev |
| R7 | CSRF on state-changing forms (register, profile, admin unlock/reset) (Tampering/Spoofing) | 3 | 4 | 12 | High | **Mitigated** (per-session token, SameSite=Lax, Origin check) | App dev |
| R8 | SSRF via URL preview to internal/metadata endpoints (Info Disclosure) | 3 | 4 | 12 | High | **Mitigated** (allowlist, loopback/private reject, validated-IP connect) | App dev |
| R9 | Session cookie sniffing on plain-HTTP localhost (Spoofing) | 2 | 3 | 6 | Medium | **Accepted** (authorised lab only; TLS mandatory in production) | App owner |
| R10 | Local audit log tampering (no WORM store) (Repudiation) | 2 | 2 | 4 | Low | **Accepted** (log shipped to central store in production) | App owner |

## Mitigation status

- **R1, R2** — remediated in the hardened build (`app/`); verified by `tests/test_sqli.sh` and `tests/test_auth.sh`.
- **R3** — remediated; verified by `tests/test_webdefences.sh` (XSS, headers) and `tests/test_password_hashing.sh` (Argon2id).
- **R4, R7** — remediated; verified by `tests/test_auth.sh` (authorisation) and `tests/test_webdefences.sh` (CSRF).
- **R6** — remediated; verified by `tests/test_ratelimit.sh` (429 + lockout).
- **R8** — remediated; verified by `tests/test_webdefences.sh` (SSRF probes).
- **R5** — remediated; verified by `tests/test_logging.sh` (audit log records).

All eight critical/high risks are mitigated in the hardened build and closed by
automated evidence. Two medium/low risks are explicitly accepted for the
authorised local lab and are flagged for closure before any production use.
