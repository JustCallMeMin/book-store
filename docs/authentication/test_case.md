#  Authentication Documentation - Book Store API

*(Version: 1.0 - Updated: 10/03/2025)*

## Test Cases

## 1. User Registration

| Test Case ID | Description | Input Data | Expected Output |
|-------------|------------|------------|----------------|
| TC_REG_01 | Register with valid details | {"first_name": "John", "last_name": "Doe", "email": "john@example.com", "password": "Password123", "password_confirmation": "Password123"} | User successfully registered (201) |
| TC_REG_02 | Register with missing required fields | {"first_name": "", "last_name": "Doe", "email": "john@example.com", "password": "Password123"} | Error: Missing required fields (422) |
| TC_REG_03 | Register with invalid email format | {"first_name": "John", "last_name": "Doe", "email": "invalid-email", "password": "Password123"} | Error: Invalid email format (422) |
| TC_REG_04 | Register with a short password | {"first_name": "John", "last_name": "Doe", "email": "john@example.com", "password": "123"} | Error: Password too short (422) |
| TC_REG_05 | Register with already registered email | {"first_name": "John", "last_name": "Doe", "email": "existing@example.com", "password": "Password123"} | Error: Email already exists (422) |

## 2. User Login

| Test Case ID | Description | Input Data | Expected Output |
|-------------|------------|------------|----------------|
| TC_LOGIN_01 | Login with valid credentials | {"email": "john@example.com", "password": "Password123"} | Successfully logged in (200) |
| TC_LOGIN_02 | Login with incorrect password | {"email": "john@example.com", "password": "WrongPassword"} | Error: Invalid credentials (401) |
| TC_LOGIN_03 | Login with non-existing email | {"email": "nonexistent@example.com", "password": "Password123"} | Error: User not found (404) |
| TC_LOGIN_04 | Login with Remember Me option | {"email": "john@example.com", "password": "Password123", "remember_me": true} | Successfully logged in with remember token (200) |
| TC_LOGIN_05 | Login with missing password | {"email": "john@example.com"} | Error: Missing password (422) |

## 3. Remember Me Token Validation

| Test Case ID | Description | Input Data | Expected Output |
|-------------|------------|------------|----------------|
| TC_REM_01 | Verify valid Remember Me token | {"email": "john@example.com", "remember_token": "validToken"} | Successfully authenticated (200) |
| TC_REM_02 | Verify expired Remember Me token | {"email": "john@example.com", "remember_token": "expiredToken"} | Error: Remember token expired (401) |
| TC_REM_03 | Verify invalid Remember Me token | {"email": "john@example.com", "remember_token": "invalidToken"} | Error: Invalid remember token (401) |

## 4. Logout

| Test Case ID | Description | Input Data | Expected Output |
|-------------|------------|------------|----------------|
| TC_LOGOUT_01 | Logout user | Authenticated user | Successfully logged out (200) |
| TC_LOGOUT_02 | Logout without authentication | No token provided | Error: User not authenticated (401) |

## 5. Profile Management

| Test Case ID | Description | Input Data | Expected Output |
|-------------|------------|------------|----------------|
| TC_PROFILE_01 | Fetch user profile | Authenticated user | Profile details returned (200) |
| TC_PROFILE_02 | Fetch profile without authentication | No token provided | Error: User not authenticated (401) |
| TC_PROFILE_03 | Update profile with valid data | {"first_name": "John", "last_name": "UpdatedDoe", "email": "john@example.com"} | Profile updated successfully (200) |
| TC_PROFILE_04 | Update profile with duplicate email | {"email": "existing@example.com"} | Error: Email already exists (422) |

## 6. Password Management

| Test Case ID | Description | Input Data | Expected Output |
|-------------|------------|------------|----------------|
| TC_PWD_01 | Change password with valid current password | {"current_password": "Password123", "new_password": "NewPassword123", "new_password_confirmation": "NewPassword123"} | Password changed successfully (200) |
| TC_PWD_02 | Change password with incorrect current password | {"current_password": "WrongPassword", "new_password": "NewPassword123", "new_password_confirmation": "NewPassword123"} | Error: Incorrect current password (400) |
| TC_PWD_03 | Change password without authentication | No token provided | Error: User not authenticated (401) |
| TC_PWD_04 | Change password with non-matching confirmation | {"current_password": "Password123", "new_password": "NewPassword123", "new_password_confirmation": "WrongConfirm"} | Error: Password confirmation does not match (422) |

## 7. OTP Management

| Test Case ID | Description | Input Data | Expected Output |
|-------------|------------|------------|----------------|
| TC_OTP_01 | Request password reset OTP for valid email | {"email": "john@example.com"} | OTP sent successfully (200) |
| TC_OTP_02 | Request OTP for non-existing email | {"email": "nonexistent@example.com"} | Error: User not found (404) |
| TC_OTP_03 | Reset password with valid OTP | {"email": "john@example.com", "otp": "123456", "password": "NewPassword123", "password_confirmation": "NewPassword123"} | Password reset successfully (200) |
| TC_OTP_04 | Reset password with incorrect OTP | {"email": "john@example.com", "otp": "999999", "password": "NewPassword123", "password_confirmation": "NewPassword123"} | Error: Invalid OTP (400) |
| TC_OTP_05 | Reset password with expired OTP | {"email": "john@example.com", "otp": "expiredOTP", "password": "NewPassword123", "password_confirmation": "NewPassword123"} | Error: OTP expired (400) |

## 8. Token Refresh

| Test Case ID | Description | Input Data | Expected Output |
|-------------|------------|------------|----------------|
| TC_REFRESH_01 | Refresh token with valid session | Authenticated user | New token issued (200) |
| TC_REFRESH_02 | Refresh token with expired session | Expired token | Error: Token refresh failed (401) |
| TC_REFRESH_03 | Refresh token without authentication | No token provided | Error: Token missing (401) |

---
This document provides a comprehensive set of test cases to verify the authentication functionality of **AuthService** using black-box testing.

