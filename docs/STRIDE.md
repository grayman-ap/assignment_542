# STRIDE Threat Model Worksheet

**Project:** Student Registration Web Application (IFT 542)
**Scope:** login, profile update, course registration, document upload, URL
preview/import, admin course/enrolment management.
**Method:** STRIDE per element + qualitative risk scoring. Likelihood (L) and
Impact (I) scored 1-5. **Risk = Likelihood × Impact.**

Scoring anchors: L5 = trivially exploitable with public tooling; L1 = requires
advanced skill + rare conditions. I5 = full compromise / data loss / legal
reprimand; I1 = cosmetic degradation.

| # | STRIDE | Asset / flow | Threat description | L | I | Risk | Rank |
|---|--------|--------------|--------------------|----|----|------|------|
| T1 | **Spoofing** | Identity (F1/F2) | An attacker authenticates as another student or the admin. In the starter app the login concatenates raw input into SQL (`' OR '1'='1' -- ` authenticates as the first user). A stolen/fixed session ID also impersonates a user because sessions are never rotated. | 4 | 5 | **20** | 1 |
| T2 | **Tampering** | Profile & registration (F3), DB D1 | SQL injection through concatenated `UPDATE`/`INSERT` (profile fields, `course_id`) lets an attacker read, alter or delete other users' rows or schema. The starter app also accepts `course_id=1; DROP TABLE courses; --`. | 4 | 5 | **20** | 2 |
| T3 | **Information Disclosure** | Errors, password store D1, rendered pages | Verbose PHP/SQL errors leak schema and DB internals; plaintext passwords are stored in the starter DB; unescaped user input enables stored XSS that can exfiltrate profiles; a default `admin/admin` account is usable. | 4 | 4 | **16** | 3 |
| T4 | **Elevation of Privilege** | Admin management (P9) | Missing server-side role checks let a student invoke admin actions; arbitrary file upload (starter stores original name + client MIME in a web-served folder) can escalate to code execution; CSRF against admin forms changes course/enrolment state. | 3 | 5 | **15** | 4 |
| T5 | **Repudiation** | Registration, admin actions (F6/F9) | The starter app keeps no audit trail, so a student can deny a course registration and an admin can deny an account unlock with no who/what/when record. | 4 | 3 | **12** | 5 |
| T6 | **Denial of Service** | Login (P5), upload (P7), preview (P8) | Unbounded credential brute-forcing exhausts DB/CPU; unlimited uploads fill disk; the unguarded preview endpoint can be pointed at internal hosts to amplify traffic. | 4 | 3 | **12** | 6 |
| T7 | **Spoofing/Tampering (CSRF)** | State-changing forms (F3/F6) | Cross-site requests replay a logged-in user's session to register courses, alter profile or, for admins, unlock accounts and reset passwords (starter has no anti-CSRF token and a lax cookie). | 3 | 4 | **12** | 7 |
| T8 | **Information Disclosure (SSRF)** | URL preview (F5/F10) | The preview/import fetches any URL server-side; in the starter app it can reach `127.0.0.1`, `169.254.169.254` (cloud metadata) or internal hosts, disclosing internal responses. | 3 | 4 | **12** | 8 |

## Top three risks and justification

1. **T1/T2 – SQL injection and identity spoofing (Risk 20).** The login and
   every write path are the entry point for the whole application. A single
   injected query authenticates the attacker as the first user (in the starter
   build this is the admin) or mutates the entire schema. These threats are
   trivially exploitable (L4) and their impact is total compromise (I5), so
   they rank above all others and are remediated first.
2. **T3 – Information disclosure (Risk 16).** Plaintext credential storage,
   verbose errors and stored XSS combine to expose identities and personal
   data at scale. Even with SQLi fixed, this class directly leaks the data the
   app is meant to protect, so it is the second priority.
3. **T4 – Elevation of privilege (Risk 15).** Server-side authorisation gaps,
   unsafe uploads and CSRF together give a low-privilege student a path to
   admin-level state changes or code execution. The impact is high (I5) and
   the fixes (role checks, upload hardening, CSRF tokens) are cheap, making
   this the third priority.

## Residual risk after controls

| # | Controls applied (preventive / detective / corrective) | Residual L | Residual I | Residual risk | Accepted? |
|---|--------------------------------------------------------|-----------|-----------|---------------|-----------|
| T1 | Prepared statements; `session_regenerate_id()` on login; HttpOnly+SameSite=Lax cookies; Argon2id verification; generic errors; lockout (preventive). | 2 | 3 | 6 | Partially – session cookies on plain HTTP localhost can still be sniffed (accepted for the authorised lab only; TLS mandated for any real deployment). |
| T2 | PDO `EMULATE_PREPARES=false`; strict input validation (type/length/format); least-privilege DB user (preventive). | 2 | 2 | 4 | Yes – residual risk accepted. |
| T3 | `display_errors=0` + generic messages; Argon2id hashes; `e()` output encoding + CSP; no default credentials (preventive). | 2 | 2 | 4 | Yes – residual risk accepted. |
| T4 | Server-side role checks; CSRF tokens; upload MIME/extension/size validation + storage outside web root (preventive). | 1 | 3 | 3 | Yes – residual risk accepted. |
| T5 | Structured JSON audit log of who/what/when (detective). | 2 | 2 | 4 | Yes – log is local and not append-only/WORM (accepted). |
| T6 | IP sliding-window rate limiting (429); account lockout; upload caps; SSRF allowlist (preventive). | 1 | 3 | 3 | Yes – residual risk accepted. |
| T7 | Per-session CSRF token verified with `hash_equals`; SameSite=Lax; Origin/Referer consistency (preventive). | 1 | 4 | 4 | Yes – residual risk accepted. |
| T8 | Destination allowlist; loopback/private/metadata rejection; connect-to-validated-IP; timeouts + redirect cap (preventive). | 2 | 2 | 4 | Yes – small DNS-rebinding window accepted for the lab allowlisted host. |

*Scoring rationale for accepted risks is in section 4 of the technical report.*
