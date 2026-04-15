# Implementation Plan - Port Configuration Fix

This plan addresses the port `8080` conflict in the local development environment by moving the application to port `8081` and ensuring proper synchronization between the Laravel backend and Vite frontend.

## Objective
- Resolve port `8080` occupation conflict.
- Properly configure Laravel and Vite for local development using Sail.
- Synchronize OAuth redirect URIs and application URLs.

## Key Files & Context
- `.env`: Primary configuration for ports and URLs (User must update this).
- `compose.yaml`: Sail service definitions.
- `CLAUDE.md` & `README.md`: Documentation for local setup.

## Proposed Changes

### 1. Environment Configuration (`.env`)
The user should update the following variables in their `.env` file:

```env
# Backend Configuration
APP_PORT=8081
APP_URL=http://localhost:8081

# Frontend Configuration
VITE_PORT=5173
VITE_APP_URL="${APP_URL}"

# Database Configuration (to avoid potential 3306 conflicts)
FORWARD_DB_PORT=3307

# OAuth Configuration
GOOGLE_REDIRECT_URI=http://localhost:8081/auth/google/callback
```

### 2. Documentation Updates
Update `CLAUDE.md` and `README.md` to reflect the new port standards.

### 3. Verification
- Restart Sail: `vendor/bin/sail down && vendor/bin/sail up -d`
- Access the app at `http://localhost:8081`.
- Verify Vite HMR is working.

## Implementation Steps

### Step 1: Update Documentation
- Update `CLAUDE.md` to change `8080` references to `8081`.
- Update `README.md` to change `8080` references to `8081`.

### Step 2: Provide .env Instructions
- Since I cannot directly modify the `.env` file, I will provide the user with the exact block to copy-paste.

## Verification & Testing
- Check if port `8081` is available before proposing.
- Verify `APP_URL` is correctly picked up by Laravel config.
- Verify `GOOGLE_REDIRECT_URI` is correctly generated.
