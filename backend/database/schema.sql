CREATE TABLE IF NOT EXISTS users (
    id CHAR(36) PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    mfa_secret_encrypted TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('invited','active','revoked')),
    last_login_at TEXT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS roles (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(30) NOT NULL UNIQUE
);
CREATE TABLE IF NOT EXISTS role_user (
    user_id CHAR(36) NOT NULL,
    role_id CHAR(36) NOT NULL,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

CREATE TABLE IF NOT EXISTS projects (
    id CHAR(36) PRIMARY KEY,
    slug VARCHAR(160) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    summary TEXT NOT NULL,
    description TEXT NOT NULL,
    public_location VARCHAR(255) NULL,
    need TEXT NOT NULL,
    editorial_status VARCHAR(20) NOT NULL DEFAULT 'draft' CHECK (editorial_status IN ('draft','published','archived')),
    operational_status VARCHAR(20) NOT NULL DEFAULT 'preparation' CHECK (operational_status IN ('preparation','active','completed','suspended')),
    goal_cents INTEGER NULL CHECK (goal_cents IS NULL OR goal_cents > 0),
    goal_currency CHAR(3) NOT NULL DEFAULT 'CHF',
    starts_at TEXT NULL,
    ends_at TEXT NULL,
    published_at TEXT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS project_updates (
    id CHAR(36) PRIMARY KEY,
    project_id CHAR(36) NOT NULL,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    published_at TEXT NULL,
    created_at TEXT NOT NULL,
    FOREIGN KEY (project_id) REFERENCES projects(id)
);

CREATE TABLE IF NOT EXISTS pages (
    id CHAR(36) PRIMARY KEY,
    slug VARCHAR(160) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    blocks TEXT NOT NULL,
    seo_title VARCHAR(255) NULL,
    seo_description TEXT NULL,
    editorial_status VARCHAR(20) NOT NULL DEFAULT 'draft',
    published_at TEXT NULL,
    author_id CHAR(36) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (author_id) REFERENCES users(id)
);
CREATE TABLE IF NOT EXISTS page_revisions (
    id CHAR(36) PRIMARY KEY,
    page_id CHAR(36) NOT NULL,
    version INTEGER NOT NULL,
    blocks TEXT NOT NULL,
    author_id CHAR(36) NULL,
    created_at TEXT NOT NULL,
    UNIQUE(page_id, version),
    FOREIGN KEY (page_id) REFERENCES pages(id),
    FOREIGN KEY (author_id) REFERENCES users(id)
);
CREATE TABLE IF NOT EXISTS posts (
    id CHAR(36) PRIMARY KEY,
    slug VARCHAR(160) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    excerpt TEXT NOT NULL,
    body TEXT NOT NULL,
    project_id CHAR(36) NULL,
    editorial_status VARCHAR(20) NOT NULL DEFAULT 'draft',
    published_at TEXT NULL,
    author_id CHAR(36) NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (author_id) REFERENCES users(id)
);
CREATE TABLE IF NOT EXISTS faqs (
    id CHAR(36) PRIMARY KEY,
    question VARCHAR(500) NOT NULL,
    answer TEXT NOT NULL,
    display_order INTEGER NOT NULL DEFAULT 0,
    editorial_status VARCHAR(20) NOT NULL DEFAULT 'draft',
    published_at TEXT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS media (
    id CHAR(36) PRIMARY KEY,
    storage_path VARCHAR(768) NOT NULL UNIQUE,
    mime_type VARCHAR(100) NOT NULL,
    width INTEGER NULL,
    height INTEGER NULL,
    size_bytes INTEGER NOT NULL,
    visibility VARCHAR(20) NOT NULL DEFAULT 'private',
    credit VARCHAR(255) NULL,
    rights TEXT NULL,
    alt_text VARCHAR(500) NULL,
    created_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS media_usages (
    media_id CHAR(36) NOT NULL,
    object_type VARCHAR(60) NOT NULL,
    object_id CHAR(36) NOT NULL,
    PRIMARY KEY(media_id, object_type, object_id),
    FOREIGN KEY(media_id) REFERENCES media(id)
);
CREATE TABLE IF NOT EXISTS documents (
    id CHAR(36) PRIMARY KEY,
    media_id CHAR(36) NOT NULL,
    title VARCHAR(255) NOT NULL,
    fiscal_year VARCHAR(20) NULL,
    version VARCHAR(40) NOT NULL,
    visibility VARCHAR(20) NOT NULL DEFAULT 'private',
    published_at TEXT NULL,
    created_at TEXT NOT NULL,
    FOREIGN KEY(media_id) REFERENCES media(id)
);

CREATE TABLE IF NOT EXISTS donors (
    id CHAR(36) PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    first_name VARCHAR(120) NULL,
    last_name VARCHAR(120) NULL,
    postal_address TEXT NULL,
    locale VARCHAR(10) NOT NULL DEFAULT 'fr_CH',
    provider_customer_id VARCHAR(255) NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS donations (
    id CHAR(36) PRIMARY KEY,
    public_reference VARCHAR(40) NOT NULL UNIQUE,
    donor_id CHAR(36) NULL,
    project_id CHAR(36) NULL,
    amount_cents INTEGER NOT NULL CHECK (amount_cents > 0),
    currency CHAR(3) NOT NULL DEFAULT 'CHF',
    frequency VARCHAR(20) NOT NULL CHECK (frequency IN ('one_time','monthly')),
    status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','paid','failed','canceled','refunded','disputed')),
    provider_subscription_id VARCHAR(255) NULL,
    donor_snapshot TEXT NOT NULL,
    paid_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (donor_id) REFERENCES donors(id),
    FOREIGN KEY (project_id) REFERENCES projects(id)
);
CREATE INDEX donations_status_created_idx ON donations(status, created_at);
CREATE TABLE IF NOT EXISTS payment_attempts (
    id CHAR(36) PRIMARY KEY,
    donation_id CHAR(36) NOT NULL,
    idempotency_key VARCHAR(128) NOT NULL UNIQUE,
    session_id VARCHAR(128) NOT NULL,
    provider VARCHAR(40) NOT NULL,
    provider_session_id VARCHAR(255) NULL UNIQUE,
    provider_payment_id VARCHAR(255) NULL UNIQUE,
    mode VARCHAR(10) NOT NULL CHECK (mode IN ('test','live')),
    status VARCHAR(20) NOT NULL DEFAULT 'created' CHECK (status IN ('created','redirected','succeeded','failed','expired')),
    checkout_url TEXT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (donation_id) REFERENCES donations(id)
);
CREATE TABLE IF NOT EXISTS subscriptions (
    id CHAR(36) PRIMARY KEY,
    donor_id CHAR(36) NOT NULL,
    project_id CHAR(36) NULL,
    provider VARCHAR(40) NOT NULL,
    provider_subscription_id VARCHAR(255) NOT NULL UNIQUE,
    amount_cents INTEGER NOT NULL CHECK (amount_cents > 0),
    currency CHAR(3) NOT NULL,
    period VARCHAR(20) NOT NULL,
    status VARCHAR(20) NOT NULL,
    current_period_end TEXT NULL,
    cancel_requested_at TEXT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (donor_id) REFERENCES donors(id),
    FOREIGN KEY (project_id) REFERENCES projects(id)
);
CREATE TABLE IF NOT EXISTS payment_events (
    id CHAR(36) PRIMARY KEY,
    provider VARCHAR(40) NOT NULL,
    mode VARCHAR(10) NOT NULL,
    provider_event_id VARCHAR(255) NOT NULL,
    object_reference VARCHAR(255) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'received',
    error_message TEXT NULL,
    received_at TEXT NOT NULL,
    processed_at TEXT NULL,
    UNIQUE (provider, mode, provider_event_id)
);

CREATE TABLE IF NOT EXISTS refunds (
    id CHAR(36) PRIMARY KEY,
    donation_id CHAR(36) NOT NULL,
    idempotency_key VARCHAR(128) NOT NULL UNIQUE,
    provider_refund_id VARCHAR(255) NOT NULL UNIQUE,
    amount_cents INTEGER NOT NULL CHECK (amount_cents > 0),
    reason VARCHAR(80) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'requested',
    initiated_by CHAR(36) NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (donation_id) REFERENCES donations(id),
    FOREIGN KEY (initiated_by) REFERENCES users(id)
);
CREATE TABLE IF NOT EXISTS financial_entries (
    id CHAR(36) PRIMARY KEY,
    donation_id CHAR(36) NULL,
    type VARCHAR(20) NOT NULL CHECK (type IN ('donation','fee','refund','adjustment')),
    amount_cents INTEGER NOT NULL,
    currency CHAR(3) NOT NULL,
    external_reference VARCHAR(255) NULL,
    note TEXT NULL,
    created_at TEXT NOT NULL,
    FOREIGN KEY (donation_id) REFERENCES donations(id)
);

CREATE TABLE IF NOT EXISTS contact_messages (
    id CHAR(36) PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    name VARCHAR(200) NULL,
    subject VARCHAR(255) NULL,
    message TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'new',
    assigned_to CHAR(36) NULL,
    purge_at TEXT NULL,
    created_at TEXT NOT NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id)
);
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id CHAR(36) PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','subscribed','unsubscribed','suppressed')),
    confirmation_token_hash CHAR(64) NULL,
    unsubscribe_token_hash CHAR(64) NULL,
    token_expires_at TEXT NULL,
    subscribed_at TEXT NULL,
    unsubscribed_at TEXT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS consent_events (
    id CHAR(36) PRIMARY KEY,
    subscriber_id CHAR(36) NOT NULL,
    action VARCHAR(30) NOT NULL,
    policy_version VARCHAR(30) NOT NULL,
    ip_hash CHAR(64) NULL,
    created_at TEXT NOT NULL,
    FOREIGN KEY (subscriber_id) REFERENCES newsletter_subscribers(id)
);

CREATE TABLE IF NOT EXISTS outbox_messages (
    id CHAR(36) PRIMARY KEY,
    kind VARCHAR(60) NOT NULL,
    aggregate_type VARCHAR(60) NOT NULL,
    aggregate_id CHAR(36) NOT NULL,
    dedupe_key VARCHAR(160) NOT NULL UNIQUE,
    payload TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    available_at TEXT NOT NULL,
    reserved_at TEXT NULL,
    attempts INTEGER NOT NULL DEFAULT 0,
    last_error TEXT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS audit_logs (
    id CHAR(36) PRIMARY KEY,
    actor_id CHAR(36) NULL,
    action VARCHAR(100) NOT NULL,
    object_type VARCHAR(60) NOT NULL,
    object_id CHAR(36) NULL,
    result VARCHAR(20) NOT NULL,
    metadata TEXT NULL,
    created_at TEXT NOT NULL,
    FOREIGN KEY (actor_id) REFERENCES users(id)
);
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id CHAR(36) NULL,
    payload TEXT NOT NULL,
    last_activity_at TEXT NOT NULL,
    expires_at TEXT NOT NULL,
    FOREIGN KEY(user_id) REFERENCES users(id)
);
CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(120) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    value_type VARCHAR(20) NOT NULL,
    updated_by CHAR(36) NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(updated_by) REFERENCES users(id)
);
CREATE TABLE IF NOT EXISTS scheduled_tasks (
    task_key VARCHAR(120) PRIMARY KEY,
    next_run_at TEXT NOT NULL,
    locked_until TEXT NULL,
    last_run_at TEXT NULL,
    last_status VARCHAR(20) NULL,
    last_error TEXT NULL
);
CREATE TABLE IF NOT EXISTS failed_jobs (
    id CHAR(36) PRIMARY KEY,
    outbox_id CHAR(36) NULL,
    error_message TEXT NOT NULL,
    failed_at TEXT NOT NULL,
    FOREIGN KEY(outbox_id) REFERENCES outbox_messages(id)
);
CREATE TABLE IF NOT EXISTS rate_limits (
    bucket_key VARCHAR(160) PRIMARY KEY,
    hits INTEGER NOT NULL DEFAULT 0,
    window_started_at TEXT NOT NULL
);
