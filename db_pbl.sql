-- SkillBridge Database Schema (PostgreSQL)
-- Evidence approach: JSONB metadata column (bukan class table inheritance)

CREATE TYPE evidence_source_type AS ENUM ('cv', 'github', 'portfolio', 'experience', 'assessment');
CREATE TYPE question_type AS ENUM ('multiple_choice', 'scale');

-- Fungsi generik untuk auto-update kolom updated_at (Postgres tidak punya ON UPDATE bawaan)
CREATE OR REPLACE FUNCTION set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TABLE careers (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT
);

CREATE TABLE skills (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    description TEXT
);

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    target_career_id INT REFERENCES careers(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TRIGGER trg_users_updated_at
    BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TABLE evidence (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    skill_id INT NOT NULL REFERENCES skills(id) ON DELETE CASCADE,
    source_type evidence_source_type NOT NULL,
    metadata JSONB,
    confidence_score NUMERIC(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_evidence_user_skill ON evidence (user_id, skill_id);

-- SkillBridge Database Schema (PostgreSQL)
-- Evidence approach: JSONB metadata column (bukan class table inheritance)

CREATE TYPE evidence_source_type AS ENUM ('cv', 'github', 'portfolio', 'experience', 'assessment');
CREATE TYPE question_type AS ENUM ('multiple_choice', 'scale');

-- Fungsi generik untuk auto-update kolom updated_at (Postgres tidak punya ON UPDATE bawaan)
CREATE OR REPLACE FUNCTION set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TABLE careers (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT
);

CREATE TABLE skills (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    description TEXT
);

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    target_career_id INT REFERENCES careers(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TRIGGER trg_users_updated_at
    BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TABLE evidence (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    skill_id INT NOT NULL REFERENCES skills(id) ON DELETE CASCADE,
    source_type evidence_source_type NOT NULL,
    metadata JSONB,
    confidence_score NUMERIC(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_evidence_user_skill ON evidence (user_id, skill_id);
CREATE INDEX idx_evidence_metadata ON evidence USING GIN (metadata);

--CREATE TABLE assessment_questions (
--    id SERIAL PRIMARY KEY,
--    skill_id INT NOT NULL REFERENCES skills(id) ON DELETE CASCADE,
--    question_text TEXT NOT NULL,
--    question_type question_type NOT NULL,
--    options JSONB,
--    weight NUMERIC(4,2) DEFAULT 1.00
--);
--
--CREATE TABLE user_skills (
--    id SERIAL PRIMARY KEY,
--    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
--    skill_id INT NOT NULL REFERENCES skills(id) ON DELETE CASCADE,
--    skill_confidence NUMERIC(5,2) DEFAULT 0.00,
--    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--    UNIQUE (user_id, skill_id)
--);
--
--CREATE OR REPLACE FUNCTION set_last_updated()
--RETURNS TRIGGER AS $$
--BEGIN
--    NEW.last_updated = CURRENT_TIMESTAMP;
--    RETURN NEW;
--END;
--$$ LANGUAGE plpgsql;
--
--CREATE TRIGGER trg_user_skills_last_updated
--    BEFORE UPDATE ON user_skills
--    FOR EACH ROW EXECUTE FUNCTION set_last_updated();
--
--CREATE TABLE career_skill_requirements (
--    id SERIAL PRIMARY KEY,
--    career_id INT NOT NULL REFERENCES careers(id) ON DELETE CASCADE,
--    skill_id INT NOT NULL REFERENCES skills(id) ON DELETE CASCADE,
--    required_level NUMERIC(5,2) NOT NULL,
--    is_mandatory BOOLEAN DEFAULT TRUE,
--    UNIQUE (career_id, skill_id)
--);


CREATE TABLE assessment_questions (
    id SERIAL PRIMARY KEY,
    skill_id INT NOT NULL REFERENCES skills(id) ON DELETE CASCADE,
    question_text TEXT NOT NULL,
    question_type question_type NOT NULL,
    options JSONB,
    weight NUMERIC(4,2) DEFAULT 1.00
);

CREATE TABLE user_skills (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    skill_id INT NOT NULL REFERENCES skills(id) ON DELETE CASCADE,
    skill_confidence NUMERIC(5,2) DEFAULT 0.00,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (user_id, skill_id)
);

CREATE OR REPLACE FUNCTION set_last_updated()
RETURNS TRIGGER AS $$
BEGIN
    NEW.last_updated = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_user_skills_last_updated
    BEFORE UPDATE ON user_skills
    FOR EACH ROW EXECUTE FUNCTION set_last_updated();

CREATE TABLE career_skill_requirements (
    id SERIAL PRIMARY KEY,
    career_id INT NOT NULL REFERENCES careers(id) ON DELETE CASCADE,
    skill_id INT NOT NULL REFERENCES skills(id) ON DELETE CASCADE,
    required_level NUMERIC(5,2) NOT NULL,
    is_mandatory BOOLEAN DEFAULT TRUE,
    UNIQUE (career_id, skill_id)
);