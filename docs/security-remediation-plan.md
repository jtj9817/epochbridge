# Security Remediation Plan (Pre‑Launch)

Date: 2026-01-29
Status: COMPLETED

## Goals
- Eliminate high‑risk authentication and transport issues before launch.
- Harden Livewire/Alpine flows that process user input.
- Reduce exposure of sensitive data in UI and serialized component state.
- Add verification steps so fixes are measurable and repeatable.

## Scope
- **Laravel/PHP**: auth, sessions, model assignment, data validation.
- **Livewire/Volt**: component authorization and state exposure.
- **Frontend**: XSS vectors, unsafe URLs, DOM‑level exposure.
- **Infrastructure**: HTTPS enforcement, secure cookies, public file exposure.

## Priority Workstreams

### 1) OAuth CSRF Protection (High)
**Issue:** OAuth callback uses `stateless()` which disables state verification.  
**Risk:** CSRF login or account linkage attacks.

**Plan**
- Replace `stateless()` with stateful OAuth flow.
- Enforce state validation (and PKCE if supported by provider).
- Add tests for mismatched/missing state behavior.

**Acceptance Criteria**
- OAuth callback rejects requests without a valid state.
- Tests cover success + failure cases.

---

### 2) HTTPS + Secure Cookies (Medium)
**Issue:** HTTPS not enforced; cookie `secure` depends on env.  
**Risk:** Session leakage over HTTP or misconfigured deployments.

**Plan**
- Enforce HTTPS in production (middleware or proxy config).
- Set `APP_URL` to `https://…` in prod.
- Set `SESSION_SECURE_COOKIE=true` and verify SameSite policies.
- Document proxy headers if behind a load balancer.

**Acceptance Criteria**
- All app routes redirect to HTTPS in prod.
- Session cookies are marked `Secure`, `HttpOnly`, and `SameSite`.

---

### 3) Server‑Side Validation for Notes (Medium)
**Issue:** Note length enforced only in UI.  
**Risk:** Oversized payloads or persistence of unvalidated data.

**Plan**
- Add validation for `note` length in:
  - Save conversion flow (Livewire landing component).
  - Update note flow (saved conversions component).
- Normalize/trim inputs server‑side.

**Acceptance Criteria**
- Notes longer than 2048 are rejected with a validation error.
- Database updates never persist invalid note length.

---

### 4) External Link Safety (Medium)
**Issue:** URLs from Wikimedia are rendered directly.  
**Risk:** Unexpected schemes (e.g., `javascript:`) if upstream changes.

**Plan**
- Allowlist schemes (`http`, `https`) before rendering.
- Add `rel="noopener noreferrer"` on outbound links.
- Optionally sanitize/strip URLs at the service layer.

**Acceptance Criteria**
- Non‑http(s) URLs are never rendered.
- Links include proper `rel` attributes.

---

### 5) Raw HTML Rendering Controls (Low)
**Issue:** `{!! !!}` is used for QR SVG and icons.  
**Risk:** XSS if data source becomes untrusted.

**Plan**
- Keep sources constrained to trusted generators.
- Replace raw HTML with component SVGs where feasible.
- Add comments or tests that enforce trusted sources only.

**Acceptance Criteria**
- Raw HTML rendering is limited to trusted sources.
- New HTML injections require explicit review.

---

### 6) Livewire Sensitive State Exposure (Low)
**Issue:** 2FA setup data stored in public Livewire properties.  
**Risk:** Exposure through client serialization or caching.

**Plan**
- Require re‑authentication before revealing QR/setup key.
- Ensure setup data is cleared from client state on close.
- Consider ephemeral endpoints for QR data if needed.

**Acceptance Criteria**
- 2FA secrets are not persisted in client state after modal close.
- Access to secrets requires recent auth.

---

### 7) Mass Assignment Hardening (Low)
**Issue:** `email_verified_at` is fillable on `User`.  
**Risk:** Untrusted assignment could mark email verified.

**Plan**
- Remove `email_verified_at` from `$fillable`.
- Set verification timestamps explicitly in trusted flows only.

**Acceptance Criteria**
- User verification can only be set by server‑side trusted paths.

---

## Environment/Config Checks (Blocking for Launch)
- Confirm `.env` does **not** include hardcoded secrets committed to git.
- Verify `APP_DEBUG=false`, `APP_ENV=production`, `APP_KEY` set.
- Ensure `.env` is not web‑accessible (web server rules).

## Progress Update (2026-01-29)
- OAuth flow is now stateful with PKCE; tests cover invalid state handling.
- HTTPS is enforced in production via middleware and HTTPS URL generation; secure cookies default to true in production.
- Server‑side note validation (max 2048 + trim) added for save flow; tests updated.
- Wikimedia links are allow‑listed to http/https and outbound links now include `rel="noopener noreferrer"`.
- Raw SVG rendering remains but is explicitly limited to trusted sources with inline notes.
- 2FA setup data requires recent password confirmation and is cleared on modal close.
- `email_verified_at` removed from mass assignment; set explicitly in trusted flow.

## Remaining Verification Items
- Manually confirm production `APP_URL=https://...`, `SESSION_SECURE_COOKIE=true`, `APP_DEBUG=false`, `APP_ENV=production`, `APP_KEY` set.
- Verify load balancer/proxy headers are configured correctly when HTTPS is terminated upstream.
- Run manual smoke checks for OAuth invalid state, HTTPS redirect behavior, and external link rendering.

## Validation & Testing
- Add/extend tests for OAuth state handling and note validation.
- Manual verification:
  - OAuth flow rejects invalid state.
  - HTTPS enforced and cookies marked `Secure`.
  - External links are safe and non‑http(s) removed.
- Security regression checklist for each release.

## Sequencing & Milestones
1. **High‑risk auth fixes** (OAuth state)
2. **Transport security** (HTTPS + cookies)
3. **Input validation hardening** (notes)
4. **Frontend/link sanitization**
5. **Low‑risk hardening** (raw HTML + mass assignment)

## Owners / Review
- Engineering: Implementation
- Security reviewer: Validation checklist + sign‑off
- Release manager: Ensure env/config verified before deploy
