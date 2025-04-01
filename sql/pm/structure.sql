CREATE TYPE login_status AS ENUM ('success', 'failed');

CREATE TYPE mfa_method_type AS ENUM ('email', 'sms', 'authenticator');

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    salt TEXT NOT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    first_name TEXT,
    last_name TEXT,
    phone_number TEXT,
    profile_image TEXT,
    mfa_config INT DEFAULT 0 CHECK (mfa_config >= 0), --Bitwise operation
    mfa_grace_period_until TIMESTAMP
);

CREATE INDEX idx_users_email ON users (email);

CREATE OR REPLACE FUNCTION update_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_update_users_timestamp
BEFORE UPDATE ON users
FOR EACH ROW EXECUTE FUNCTION update_timestamp();

CREATE TABLE passwords (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    service_name TEXT NOT NULL,
    username TEXT NOT NULL,
    password TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT
);

CREATE INDEX idx_passwords_user_id ON passwords (user_id);

CREATE TRIGGER trigger_update_passwords_timestamp
BEFORE UPDATE ON passwords
FOR EACH ROW EXECUTE FUNCTION update_timestamp();

CREATE TABLE login_attempts (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    ip_address INET NOT NULL,
    user_agent TEXT NOT NULL,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status login_status NOT NULL,
    location TEXT
);

CREATE INDEX idx_login_attempts_user_id_login_time ON login_attempts (user_id, login_time);

CREATE TABLE email_verification (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token TEXT NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_email_verification_token ON email_verification (token);

CREATE TABLE mfa_methods (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    method_type mfa_method_type NOT NULL,
    is_enabled BOOLEAN DEFAULT FALSE,
    secret_data TEXT,                         -- Encrypted by app (e.g., TOTP secret)
    last_verification TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT unique_user_method UNIQUE (user_id, method_type)
);

CREATE INDEX idx_mfa_methods_user_id_enabled ON mfa_methods (user_id, is_enabled);

CREATE TRIGGER trigger_update_mfa_methods_timestamp
BEFORE UPDATE ON mfa_methods
FOR EACH ROW EXECUTE FUNCTION update_timestamp();

CREATE TABLE password_sharing (
    id SERIAL PRIMARY KEY,
    password_id INT NOT NULL REFERENCES passwords(id) ON DELETE CASCADE,
    owner_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    shared_with_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status TEXT DEFAULT 'active' CHECK (status IN ('active', 'revoked'))
);

CREATE INDEX idx_password_sharing_owner_id ON password_sharing (owner_id);
CREATE INDEX idx_password_sharing_shared_with_id ON password_sharing (shared_with_id);

CREATE TRIGGER trigger_update_password_sharing_timestamp
BEFORE UPDATE ON password_sharing
FOR EACH ROW EXECUTE FUNCTION update_timestamp();