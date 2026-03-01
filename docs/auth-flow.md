# Auth Service Flow

## Overview

This service supports a single login endpoint with a provider strategy:

- Email/password
- Phone/password
- Google (id_token)
- Apple (id_token)

The response is always a JWT issued by this service.

## Data Model

- `users`: core account and status
- `user_identities`: auth methods per user
  - `provider`: `email`, `phone`, `google`, `apple`
  - `identifier`: email, phone, or provider `sub`
  - `password_hash`: nullable for social providers
  - `verified_at`: optional

## Endpoints

### Register

`POST /auth/register`

Body (email):
```json
{ "email": "user@example.com", "password": "StrongPass123!" }
```

Body (phone):
```json
{ "phone": "+15551234567", "password": "StrongPass123!" }
```

Behavior:
- Validates input.
- Creates `users` + `user_identities`.
- Returns user and identity ids.

### Login (Provider Strategy)

`POST /auth/login`

Body (email):
```json
{ "provider": "email", "email": "user@example.com", "password": "StrongPass123!" }
```

Body (google):
```json
{ "provider": "google", "token": "google-id-token" }
```

Body (apple):
```json
{ "provider": "apple", "token": "apple-id-token" }
```

Behavior:
- Validates input.
- Resolves provider through `AuthProviderRegistry`.
- Uses the provider strategy to authenticate.
- Issues JWT with claims: `user_id`, `identity_id`, `provider`.

## Internal Flow (Strategy)

1. Controller builds `ProviderLoginRequest`.
2. Validator enforces provider-specific requirements.
3. `LoginWithProvider` gets the correct strategy.
4. Strategy authenticates and returns `{ token }`.

## Phone Login (OTP)

Phone login uses OTP:

- `POST /auth/otp/request` with `{ "phone": "+15551234567" }`
- `POST /auth/otp/verify` with `{ "phone": "+15551234567", "code": "123456" }`

The verify endpoint returns a JWT and creates the user/identity if needed.

## Social Login (Google/Apple)

Currently scaffolded. To enable real validation:
- Set env vars:
  - `GOOGLE_CLIENT_ID`
  - `APPLE_CLIENT_ID`, `APPLE_TEAM_ID`, `APPLE_KEY_ID`, `APPLE_PRIVATE_KEY_PATH`
- Implement OIDC/JWKS validation in the provider strategies.
- On success, use `sub` as identifier.

## Post-Identification Rewards (After Login)

Yes, post-identification is a good pattern:

Example:
- User logs in with Google.
- Later completes additional verification (phone, email, KYC).
- Reward logic: "first verified identity after social login = free ride".

Suggested design:
- Add user flags or a `verification_events` table.
- Emit domain events on verification:
  - `identity.verified`
  - `user.verified`
- A rewards service listens and grants the benefit.

Minimal rules example:
- `reward_eligible = true` when `provider` is social and `verified_at` was null.
- `reward_granted_at` stored to prevent duplicates.

This keeps auth clean and rewards as a separate microservice.
