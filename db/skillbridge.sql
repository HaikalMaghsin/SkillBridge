--
-- PostgreSQL database dump
--

-- Dumped from database version 16.6
-- Dumped by pg_dump version 16.6

-- Started on 2026-11-25 14:32:16

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- TOC entry 874 (class 1247 OID 26504)
-- Name: evidence_source_type; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.evidence_source_type AS ENUM (
    'cv',
    'project',
    'portfolio',
    'certificate',
    'github',
    'assessment'
);


ALTER TYPE public.evidence_source_type OWNER TO postgres;

--
-- TOC entry 877 (class 1247 OID 26514)
-- Name: skill_source; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.skill_source AS ENUM (
    'manual',
    'cv_extracted'
);


ALTER TYPE public.skill_source OWNER TO postgres;

--
-- TOC entry 880 (class 1247 OID 26520)
-- Name: skill_status; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.skill_status AS ENUM (
    'candidate',
    'confirmed'
);


ALTER TYPE public.skill_status OWNER TO postgres;

--
-- TOC entry 871 (class 1247 OID 26499)
-- Name: user_role; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.user_role AS ENUM (
    'user',
    'admin',
    'hr'
);


ALTER TYPE public.user_role OWNER TO postgres;

--
-- TOC entry 246 (class 1255 OID 26525)
-- Name: set_updated_at(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.set_updated_at() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.set_updated_at() OWNER TO postgres;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 237 (class 1259 OID 26758)
-- Name: candidate_pipelines; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.candidate_pipelines (
    id integer NOT NULL,
    company_id integer NOT NULL,
    vacancy_id integer,
    user_id integer NOT NULL,
    status character varying(20) DEFAULT 'Shortlisted'::character varying NOT NULL,
    notes text,
    updated_by integer,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    decision_payload jsonb DEFAULT '{}'::jsonb,
    candidate_response character varying(40) DEFAULT 'pending'::character varying,
    candidate_reply_note text
);


ALTER TABLE public.candidate_pipelines OWNER TO postgres;

--
-- TOC entry 236 (class 1259 OID 26757)
-- Name: candidate_pipelines_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.candidate_pipelines_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.candidate_pipelines_id_seq OWNER TO postgres;

--
-- TOC entry 5155 (class 0 OID 0)
-- Dependencies: 236
-- Name: candidate_pipelines_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.candidate_pipelines_id_seq OWNED BY public.candidate_pipelines.id;


--
-- TOC entry 220 (class 1259 OID 26549)
-- Name: career_skill_requirements; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.career_skill_requirements (
    id integer NOT NULL,
    career_id integer NOT NULL,
    skill_id integer NOT NULL,
    is_mandatory boolean DEFAULT true NOT NULL
);


ALTER TABLE public.career_skill_requirements OWNER TO postgres;

--
-- TOC entry 219 (class 1259 OID 26548)
-- Name: career_skill_requirements_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.career_skill_requirements_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.career_skill_requirements_id_seq OWNER TO postgres;

--
-- TOC entry 5156 (class 0 OID 0)
-- Dependencies: 219
-- Name: career_skill_requirements_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.career_skill_requirements_id_seq OWNED BY public.career_skill_requirements.id;


--
-- TOC entry 216 (class 1259 OID 26527)
-- Name: careers; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.careers (
    id integer NOT NULL,
    name character varying(100) NOT NULL,
    description text
);


ALTER TABLE public.careers OWNER TO postgres;

--
-- TOC entry 215 (class 1259 OID 26526)
-- Name: careers_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.careers_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.careers_id_seq OWNER TO postgres;

--
-- TOC entry 5157 (class 0 OID 0)
-- Dependencies: 215
-- Name: careers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.careers_id_seq OWNED BY public.careers.id;


--
-- TOC entry 231 (class 1259 OID 26688)
-- Name: companies; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.companies (
    id integer NOT NULL,
    name character varying(150) NOT NULL,
    industry character varying(100),
    website character varying(255),
    description text,
    logo_url text,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.companies OWNER TO postgres;

--
-- TOC entry 230 (class 1259 OID 26687)
-- Name: companies_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.companies_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.companies_id_seq OWNER TO postgres;

--
-- TOC entry 5158 (class 0 OID 0)
-- Dependencies: 230
-- Name: companies_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.companies_id_seq OWNED BY public.companies.id;


--
-- TOC entry 233 (class 1259 OID 26700)
-- Name: company_recruiters; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.company_recruiters (
    id integer NOT NULL,
    company_id integer NOT NULL,
    user_id integer NOT NULL,
    role_title character varying(100) DEFAULT 'Recruiter'::character varying,
    status character varying(20) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.company_recruiters OWNER TO postgres;

--
-- TOC entry 232 (class 1259 OID 26699)
-- Name: company_recruiters_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.company_recruiters_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.company_recruiters_id_seq OWNER TO postgres;

--
-- TOC entry 5159 (class 0 OID 0)
-- Dependencies: 232
-- Name: company_recruiters_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.company_recruiters_id_seq OWNED BY public.company_recruiters.id;


--
-- TOC entry 225 (class 1259 OID 26607)
-- Name: evidence; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.evidence (
    id integer NOT NULL,
    user_id integer NOT NULL,
    source_type public.evidence_source_type NOT NULL,
    title character varying(150) NOT NULL,
    file_url character varying(500),
    metadata jsonb DEFAULT '{}'::jsonb NOT NULL,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.evidence OWNER TO postgres;

--
-- TOC entry 224 (class 1259 OID 26606)
-- Name: evidence_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.evidence_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.evidence_id_seq OWNER TO postgres;

--
-- TOC entry 5160 (class 0 OID 0)
-- Dependencies: 224
-- Name: evidence_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.evidence_id_seq OWNED BY public.evidence.id;


--
-- TOC entry 228 (class 1259 OID 26649)
-- Name: evidence_skills; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.evidence_skills (
    evidence_id integer NOT NULL,
    skill_id integer NOT NULL
);


ALTER TABLE public.evidence_skills OWNER TO postgres;

--
-- TOC entry 235 (class 1259 OID 26726)
-- Name: job_vacancies; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.job_vacancies (
    id integer NOT NULL,
    company_id integer NOT NULL,
    career_id integer,
    title character varying(150) NOT NULL,
    positions integer DEFAULT 1 NOT NULL,
    status character varying(20) DEFAULT 'active'::character varying NOT NULL,
    description text,
    requirements text,
    created_by integer,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.job_vacancies OWNER TO postgres;

--
-- TOC entry 234 (class 1259 OID 26725)
-- Name: job_vacancies_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.job_vacancies_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.job_vacancies_id_seq OWNER TO postgres;

--
-- TOC entry 5161 (class 0 OID 0)
-- Dependencies: 234
-- Name: job_vacancies_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.job_vacancies_id_seq OWNED BY public.job_vacancies.id;


--
-- TOC entry 245 (class 1259 OID 27070)
-- Name: mail_logs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.mail_logs (
    id integer NOT NULL,
    to_email character varying(255) NOT NULL,
    to_name character varying(255),
    subject character varying(255) NOT NULL,
    body_html text,
    note text,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.mail_logs OWNER TO postgres;

--
-- TOC entry 244 (class 1259 OID 27069)
-- Name: mail_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.mail_logs_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.mail_logs_id_seq OWNER TO postgres;

--
-- TOC entry 5162 (class 0 OID 0)
-- Dependencies: 244
-- Name: mail_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.mail_logs_id_seq OWNED BY public.mail_logs.id;


--
-- TOC entry 243 (class 1259 OID 27053)
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.password_reset_tokens (
    id integer NOT NULL,
    user_id integer NOT NULL,
    token_hash character varying(64) NOT NULL,
    expires_at timestamp with time zone NOT NULL,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    used_at timestamp with time zone
);


ALTER TABLE public.password_reset_tokens OWNER TO postgres;

--
-- TOC entry 242 (class 1259 OID 27052)
-- Name: password_reset_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.password_reset_tokens_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.password_reset_tokens_id_seq OWNER TO postgres;

--
-- TOC entry 5163 (class 0 OID 0)
-- Dependencies: 242
-- Name: password_reset_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.password_reset_tokens_id_seq OWNED BY public.password_reset_tokens.id;


--
-- TOC entry 239 (class 1259 OID 26796)
-- Name: recruiter_activity_logs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.recruiter_activity_logs (
    id integer NOT NULL,
    company_id integer NOT NULL,
    user_id integer NOT NULL,
    action_type character varying(50) NOT NULL,
    target_type character varying(50),
    target_id integer,
    description text,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.recruiter_activity_logs OWNER TO postgres;

--
-- TOC entry 238 (class 1259 OID 26795)
-- Name: recruiter_activity_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.recruiter_activity_logs_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.recruiter_activity_logs_id_seq OWNER TO postgres;

--
-- TOC entry 5164 (class 0 OID 0)
-- Dependencies: 238
-- Name: recruiter_activity_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.recruiter_activity_logs_id_seq OWNED BY public.recruiter_activity_logs.id;


--
-- TOC entry 241 (class 1259 OID 26833)
-- Name: schema_migrations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.schema_migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    applied_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.schema_migrations OWNER TO postgres;

--
-- TOC entry 240 (class 1259 OID 26832)
-- Name: schema_migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.schema_migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.schema_migrations_id_seq OWNER TO postgres;

--
-- TOC entry 5165 (class 0 OID 0)
-- Dependencies: 240
-- Name: schema_migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.schema_migrations_id_seq OWNED BY public.schema_migrations.id;


--
-- TOC entry 218 (class 1259 OID 26538)
-- Name: skills; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.skills (
    id integer NOT NULL,
    name character varying(100) NOT NULL,
    category character varying(50),
    description text
);


ALTER TABLE public.skills OWNER TO postgres;

--
-- TOC entry 217 (class 1259 OID 26537)
-- Name: skills_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.skills_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.skills_id_seq OWNER TO postgres;

--
-- TOC entry 5166 (class 0 OID 0)
-- Dependencies: 217
-- Name: skills_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.skills_id_seq OWNED BY public.skills.id;


--
-- TOC entry 223 (class 1259 OID 26584)
-- Name: student_profiles; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.student_profiles (
    user_id integer NOT NULL,
    target_career_id integer,
    is_public boolean DEFAULT false NOT NULL,
    public_token uuid,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    institution character varying(150) DEFAULT 'Politeknik Negeri Malang'::character varying,
    study_program character varying(150) DEFAULT 'Teknik Informatika'::character varying,
    semester integer DEFAULT 3,
    location character varying(150) DEFAULT 'Malang, Indonesia'::character varying,
    phone character varying(50) DEFAULT NULL::character varying,
    bio text,
    CONSTRAINT chk_public_needs_token CHECK (((NOT is_public) OR (public_token IS NOT NULL)))
);


ALTER TABLE public.student_profiles OWNER TO postgres;

--
-- TOC entry 227 (class 1259 OID 26625)
-- Name: user_skills; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.user_skills (
    id integer NOT NULL,
    user_id integer NOT NULL,
    skill_id integer NOT NULL,
    source public.skill_source DEFAULT 'manual'::public.skill_source NOT NULL,
    status public.skill_status DEFAULT 'confirmed'::public.skill_status NOT NULL,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.user_skills OWNER TO postgres;

--
-- TOC entry 226 (class 1259 OID 26624)
-- Name: user_skills_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.user_skills_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.user_skills_id_seq OWNER TO postgres;

--
-- TOC entry 5167 (class 0 OID 0)
-- Dependencies: 226
-- Name: user_skills_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.user_skills_id_seq OWNED BY public.user_skills.id;


--
-- TOC entry 222 (class 1259 OID 26570)
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    id integer NOT NULL,
    name character varying(100) NOT NULL,
    email character varying(150) NOT NULL,
    password_hash character varying(255) NOT NULL,
    role public.user_role DEFAULT 'user'::public.user_role NOT NULL,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    avatar_url text
);


ALTER TABLE public.users OWNER TO postgres;

--
-- TOC entry 5168 (class 0 OID 0)
-- Dependencies: 222
-- Name: COLUMN users.is_active; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.users.is_active IS 'FALSE = akun dinonaktifkan admin; ditolak saat login dan pada request berikutnya.';


--
-- TOC entry 221 (class 1259 OID 26569)
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.users_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO postgres;

--
-- TOC entry 5169 (class 0 OID 0)
-- Dependencies: 221
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- TOC entry 229 (class 1259 OID 26665)
-- Name: v_skill_gap; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.v_skill_gap AS
 SELECT sp.user_id,
    sp.target_career_id AS career_id,
    csr.skill_id,
    s.name AS skill_name,
    csr.is_mandatory,
    (us.id IS NOT NULL) AS has_skill
   FROM (((public.student_profiles sp
     JOIN public.career_skill_requirements csr ON ((csr.career_id = sp.target_career_id)))
     JOIN public.skills s ON ((s.id = csr.skill_id)))
     LEFT JOIN public.user_skills us ON (((us.user_id = sp.user_id) AND (us.skill_id = csr.skill_id) AND (us.status = 'confirmed'::public.skill_status))));


ALTER VIEW public.v_skill_gap OWNER TO postgres;

--
-- TOC entry 4862 (class 2604 OID 26761)
-- Name: candidate_pipelines id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.candidate_pipelines ALTER COLUMN id SET DEFAULT nextval('public.candidate_pipelines_id_seq'::regclass);


--
-- TOC entry 4827 (class 2604 OID 26552)
-- Name: career_skill_requirements id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.career_skill_requirements ALTER COLUMN id SET DEFAULT nextval('public.career_skill_requirements_id_seq'::regclass);


--
-- TOC entry 4825 (class 2604 OID 26530)
-- Name: careers id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.careers ALTER COLUMN id SET DEFAULT nextval('public.careers_id_seq'::regclass);


--
-- TOC entry 4849 (class 2604 OID 26691)
-- Name: companies id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.companies ALTER COLUMN id SET DEFAULT nextval('public.companies_id_seq'::regclass);


--
-- TOC entry 4852 (class 2604 OID 26703)
-- Name: company_recruiters id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.company_recruiters ALTER COLUMN id SET DEFAULT nextval('public.company_recruiters_id_seq'::regclass);


--
-- TOC entry 4841 (class 2604 OID 26610)
-- Name: evidence id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.evidence ALTER COLUMN id SET DEFAULT nextval('public.evidence_id_seq'::regclass);


--
-- TOC entry 4857 (class 2604 OID 26729)
-- Name: job_vacancies id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.job_vacancies ALTER COLUMN id SET DEFAULT nextval('public.job_vacancies_id_seq'::regclass);


--
-- TOC entry 4874 (class 2604 OID 27073)
-- Name: mail_logs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mail_logs ALTER COLUMN id SET DEFAULT nextval('public.mail_logs_id_seq'::regclass);


--
-- TOC entry 4872 (class 2604 OID 27056)
-- Name: password_reset_tokens id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.password_reset_tokens ALTER COLUMN id SET DEFAULT nextval('public.password_reset_tokens_id_seq'::regclass);


--
-- TOC entry 4868 (class 2604 OID 26799)
-- Name: recruiter_activity_logs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.recruiter_activity_logs ALTER COLUMN id SET DEFAULT nextval('public.recruiter_activity_logs_id_seq'::regclass);


--
-- TOC entry 4870 (class 2604 OID 26836)
-- Name: schema_migrations id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.schema_migrations ALTER COLUMN id SET DEFAULT nextval('public.schema_migrations_id_seq'::regclass);


--
-- TOC entry 4826 (class 2604 OID 26541)
-- Name: skills id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.skills ALTER COLUMN id SET DEFAULT nextval('public.skills_id_seq'::regclass);


--
-- TOC entry 4844 (class 2604 OID 26628)
-- Name: user_skills id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_skills ALTER COLUMN id SET DEFAULT nextval('public.user_skills_id_seq'::regclass);


--
-- TOC entry 4829 (class 2604 OID 26573)
-- Name: users id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- TOC entry 5141 (class 0 OID 26758)
-- Dependencies: 237
-- Data for Name: candidate_pipelines; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.candidate_pipelines (id, company_id, vacancy_id, user_id, status, notes, updated_by, created_at, updated_at, decision_payload, candidate_response, candidate_reply_note) FROM stdin;
3	1	4	1	Accepted	Student expressed interest via Career Target page	6	2026-11-10 07:26:25.734303+07	2026-11-10 07:28:16.778961+07	{}	pending	\N
1	1	1	1	Rejected	Student expressed interest via Career Target page	6	2026-11-10 03:47:41.684179+07	2026-11-10 13:40:02.666811+07	{}	pending	\N
4	1	2	1	Accepted	Student expressed interest via Career Target page	6	2026-11-10 13:41:16.593574+07	2026-11-10 13:45:23.780337+07	{}	pending	\N
\.


--
-- TOC entry 5125 (class 0 OID 26549)
-- Dependencies: 220
-- Data for Name: career_skill_requirements; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.career_skill_requirements (id, career_id, skill_id, is_mandatory) FROM stdin;
1	1	10	t
2	1	9	t
3	1	4	t
4	1	3	t
5	1	1	t
6	2	10	t
7	2	8	t
8	2	7	t
9	2	6	t
10	2	5	t
11	3	14	t
12	3	13	t
13	3	12	t
14	3	3	t
15	3	2	t
17	4	17	t
18	4	16	t
19	4	15	t
20	4	11	t
63	13	2	t
64	13	55	t
65	13	12	t
66	13	57	t
67	13	15	t
68	13	18	t
69	13	16	t
\.


--
-- TOC entry 5121 (class 0 OID 26527)
-- Dependencies: 216
-- Data for Name: careers; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.careers (id, name, description) FROM stdin;
1	Backend Developer	Build APIs, databases, and server-side services for web products.
2	Frontend Developer	Create accessible, responsive interfaces for websites and web apps.
3	Data Analyst	Prepare data, explore patterns, and explain useful findings clearly.
4	UI/UX Designer	Plan simple digital experiences and create clear interface designs.
13	Prompt Engineer	Tukang prompt
\.


--
-- TOC entry 5135 (class 0 OID 26688)
-- Dependencies: 231
-- Data for Name: companies; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.companies (id, name, industry, website, description, created_at, updated_at) FROM stdin;
1	PT Tech Indonesia	Technology	https://techindonesia.com	Leading technology company specializing in software development and digital transformation services.	2026-10-26 23:50:55.344842+07	2026-10-26 23:50:55.344842+07
\.


--
-- TOC entry 5137 (class 0 OID 26700)
-- Dependencies: 233
-- Data for Name: company_recruiters; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.company_recruiters (id, company_id, user_id, role_title, status, created_at, updated_at) FROM stdin;
1	1	6	Recruiter	active	2026-11-09 08:37:29.3038+07	2026-11-09 08:37:29.3038+07
\.


--
-- TOC entry 5130 (class 0 OID 26607)
-- Dependencies: 225
-- Data for Name: evidence; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.evidence (id, user_id, source_type, title, file_url, metadata, created_at) FROM stdin;
1	1	project	E-Commerce Web Application	https://github.com/student/ecommerce-project	{"description": "Full-stack e-commerce platform built with PHP and PostgreSQL"}	2026-10-26 23:50:55.385198+07
2	1	portfolio	Personal Portfolio Website	https://student-portfolio.dev	{"description": "Responsive portfolio showcasing web development projects"}	2026-10-26 23:50:55.402719+07
3	1	certificate	Web Development Certification	https://example.com/cert/12345	{"description": "Certificate from online bootcamp completing 200 hours of web development training"}	2026-10-26 23:50:55.408938+07
12	4	cv	CV ATS — CV 1 Rizky Pratama UIUX Product Designer	CV_1_Rizky_Pratama_UIUX_Product_Designer.pdf	{"date": "2026-11-08", "level": "advanced", "skills": ["Figma", "Prototyping", "Usability Testing", "User Research", "Wireframing", "Data Visualisation"], "fileName": "CV_1_Rizky_Pratama_UIUX_Product_Designer.pdf", "description": "Product Designer and Design Technologist with 4+ years of experience bridging human-centered design, front-end web engineering, and quantitative product analytics. Proven track record in end-to-end User Research, low-to-high fidelity Wireframing, interactive Prototyping in Figma, and moderated UsabilityTesting. Adept at translating validated design"}	2026-11-08 08:36:06.848492+07
13	8	project	Proyek Backend E-Commerce API	https://github.com/example/backend-api	{}	2026-11-09 08:37:52.414871+07
18	1	cv	CV ATS — CV 3 Dimas Mahendra Data Product Analyst	https://pub-09386e6302da4dc3a6b07bfe267949e6.r2.dev/evidence/user_1/1794800960_CV_3_Dimas_Mahendra_Data_Product_Analyst.pdf	{"date": "2026-11-16", "level": "advanced", "r2_key": "evidence/user_1/1794800960_CV_3_Dimas_Mahendra_Data_Product_Analyst.pdf", "skills": ["Git", "PHP", "PostgreSQL", "REST APIs", "SQL", "Data Visualisation"], "storage": "r2", "fileName": "CV_3_Dimas_Mahendra_Data_Product_Analyst.pdf", "description": "Analytical Data & Product Specialist with 4+ years of experience turning complex behavioral datasets into high- impact product and design decisions. Expert in advanced SQL, PostgreSQL data warehousing, statistical modeling (Statistics) in Python and Excel, and executive DataVisualisation storytelling. Combines rigorous quantitative skills with full"}	2026-11-16 10:49:23.578911+07
16	1	certificate	Sertifikat AWS Certified Cloud Practitioner	https://pub-09386e6302da4dc3a6b07bfe267949e6.r2.dev/evidence/user_1/1794754894_AWS_Cloud_Certificate.png	{"date": "2026-11-15", "level": "advanced", "r2_key": "evidence/user_1/1794754894_AWS_Cloud_Certificate.png", "storage": "r2", "fileName": "AWS_Cloud_Certificate.png", "description": "Sertifikasi kompetensi arsitektur Cloudflare dan AWS"}	2026-11-15 22:00:13.133976+07
11	1	cv	CV ATS — CV ATS Dummy Haikal Maghsin SkillBridge	CV_ATS_Dummy_Haikal_Maghsin_SkillBridge.pdf	{"date": "2026-11-13", "level": "advanced", "skills": ["CSS", "Git", "HTML", "JavaScript", "Data Visualisation", "Excel"], "fileName": null, "description": "Pengalaman 2+ tahun dalam rekayasa web frontend dan backend modern menggunakan JavaScript (ES6 / Vanilla JS), PHP, HTML, CSS, SQL (PostgreSQL & MySQL), dan REST APIs. Terbiasa memimpin pengembangan proyek Problem-Based Learning (PjBL), merancang antarmuka responsif di Figma, serta membangun analisis data interaktif. PENGALAMAN PROYEK & PRAKTIK Ketu"}	2026-11-04 20:35:09.175074+07
15	1	cv	CV ATS — CV 1 Rizky Pratama UIUX Product Designer	https://pub-09386e6302da4dc3a6b07bfe267949e6.r2.dev/evidence/user_1/1794792631_CV_1_Rizky_Pratama_UIUX_Product_Designer.pdf	{"date": "2026-11-16", "level": "advanced", "r2_key": "evidence/user_1/1794792631_CV_1_Rizky_Pratama_UIUX_Product_Designer.pdf", "skills": ["Git", "JavaScript", "Responsive Design", "Data Visualisation", "Excel", "Figma"], "storage": "r2", "fileName": "CV_1_Rizky_Pratama_UIUX_Product_Designer.pdf", "description": "Product Designer and Design Technologist with 4+ years of experience bridging human-centered design, front-end web engineering, and quantitative product analytics. Proven track record in end-to-end User Research, low-to-high fidelity Wireframing, interactive Prototyping in Figma, and moderated UsabilityTesting. Adept at translating validated design"}	2026-11-13 10:23:03.476271+07
\.


--
-- TOC entry 5133 (class 0 OID 26649)
-- Dependencies: 228
-- Data for Name: evidence_skills; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.evidence_skills (evidence_id, skill_id) FROM stdin;
1	17
1	14
2	14
2	3
3	3
3	1
12	11
12	17
12	18
12	15
12	16
12	13
11	6
11	10
11	5
11	7
11	13
11	12
16	1
16	2
16	3
15	10
15	7
15	8
15	13
15	12
15	11
18	10
18	1
18	4
18	9
18	3
18	13
\.


--
-- TOC entry 5139 (class 0 OID 26726)
-- Dependencies: 235
-- Data for Name: job_vacancies; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.job_vacancies (id, company_id, career_id, title, positions, status, description, requirements, created_by, created_at, updated_at) FROM stdin;
1	1	1	Senior Backend Developer	2	active	We are looking for experienced backend developers to join our engineering team.	Minimum 3 years experience with PHP, PostgreSQL, REST API development	\N	2026-10-26 23:50:55.365737+07	2026-10-26 23:50:55.365737+07
2	1	2	Frontend Developer (React)	3	active	Join our frontend team to build modern web applications.	Strong JavaScript skills, experience with React, responsive design	\N	2026-10-26 23:50:55.375917+07	2026-10-26 23:50:55.375917+07
3	1	3	Junior Data Analyst	1	active	Entry-level position for fresh graduates interested in data analysis.	Basic SQL, Excel, and data visualization skills	\N	2026-10-26 23:50:55.37669+07	2026-10-26 23:50:55.37669+07
4	1	4	UI UX Designer (Intern)	1	active	bblabla	mmmm,	6	2026-11-10 07:25:49.03933+07	2026-11-10 07:25:49.03933+07
5	1	4	Product Designer	2	active	Bridging Between UIUX and Development	5+ in UIUX, 3+ years in Front End Developer	6	2026-11-10 13:46:59.255137+07	2026-11-10 13:46:59.255137+07
\.


--
-- TOC entry 5149 (class 0 OID 27070)
-- Dependencies: 245
-- Data for Name: mail_logs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.mail_logs (id, to_email, to_name, subject, body_html, note, created_at) FROM stdin;
1	haikal@gmail.com	Haikal Maghsin	Verifikasi Reset Kata Sandi · SkillBridge	<!DOCTYPE html>\n<html>\n<head>\n  <meta charset="utf-8">\n  <meta name="viewport" content="width=device-width, initial-scale=1.0">\n  <title>Reset Password SkillBridge</title>\n</head>\n<body style="margin: 0; padding: 0; background-color: #F8FAFC; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1E293B;">\n  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #F8FAFC; padding: 40px 15px;">\n    <tr>\n      <td align="center">\n        <table role="presentation" width="100%" style="max-width: 540px; background-color: #FFFFFF; border-radius: 12px; border: 1px solid #E2E8F0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); overflow: hidden;" cellspacing="0" cellpadding="0">\n          <!-- Header -->\n          <tr>\n            <td style="padding: 28px 32px; background: linear-gradient(135deg, #4338CA 0%, #6366F1 100%); text-align: left;">\n              <table role="presentation" cellspacing="0" cellpadding="0">\n                <tr>\n                  <td style="width: 36px; height: 36px; background: rgba(255, 255, 255, 0.2); border-radius: 8px; text-align: center; vertical-align: middle; font-size: 20px; font-weight: 800; color: #FFFFFF;">S</td>\n                  <td style="padding-left: 12px; font-size: 20px; font-weight: 700; color: #FFFFFF; letter-spacing: -0.3px;">SkillBridge</td>\n                </tr>\n              </table>\n            </td>\n          </tr>\n          <!-- Body -->\n          <tr>\n            <td style="padding: 32px;">\n              <h1 style="margin: 0 0 16px; font-size: 22px; font-weight: 700; color: #0F172A; line-height: 1.3;">Permintaan Verifikasi Reset Kata Sandi</h1>\n              <p style="margin: 0 0 16px; font-size: 15px; line-height: 1.6; color: #475569;">\n                Halo <strong>Haikal Maghsin</strong>,\n              </p>\n              <p style="margin: 0 0 20px; font-size: 15px; line-height: 1.6; color: #475569;">\n                Kami menerima permintaan pengaturan ulang kata sandi untuk akun Anda dengan peran <strong>Mahasiswa / Talenta</strong>. Untuk melanjutkan dan mengatur kata sandi baru Anda, silakan klik tombol verifikasi di bawah ini:\n              </p>\n\n              <!-- Tombol CTA -->\n              <table role="presentation" cellspacing="0" cellpadding="0" style="margin: 28px 0;">\n                <tr>\n                  <td align="center" style="border-radius: 8px; background-color: #4F46E5;">\n                    <a href="http://localhost/tools/reset-password.php?token=bdc4c054df9144ab601f1adb2a41273a1aec96c201d27d8e35bd2156dcbc3129" target="_blank" style="display: inline-block; padding: 14px 28px; font-size: 15px; font-weight: 600; color: #FFFFFF; text-decoration: none; border-radius: 8px;">\n                      Verifikasi &amp; Reset Kata Sandi &rarr;\n                    </a>\n                  </td>\n                </tr>\n              </table>\n\n              <p style="margin: 0 0 16px; font-size: 13px; line-height: 1.6; color: #64748B;">\n                Atau salin dan tempel tautan berikut ke browser Anda jika tombol di atas tidak berfungsi:\n              </p>\n              <p style="margin: 0 0 24px; font-size: 12px; line-height: 1.5; color: #4F46E5; word-break: break-all; background: #F1F5F9; padding: 10px 12px; border-radius: 6px;">\n                http://localhost/tools/reset-password.php?token=bdc4c054df9144ab601f1adb2a41273a1aec96c201d27d8e35bd2156dcbc3129\n              </p>\n\n              <div style="border-top: 1px solid #E2E8F0; padding-top: 18px; margin-top: 24px;">\n                <p style="margin: 0 0 8px; font-size: 13px; color: #94A3B8; line-height: 1.5;">\n                  ⏳ <strong>Perhatian:</strong> Tautan verifikasi ini hanya berlaku selama <strong>1 jam</strong>.\n                </p>\n                <p style="margin: 0; font-size: 13px; color: #94A3B8; line-height: 1.5;">\n                  🔒 Jika Anda tidak pernah meminta reset kata sandi ini, abaikan email ini. Akun Anda tetap aman dan kata sandi tidak akan berubah.\n                </p>\n              </div>\n            </td>\n          </tr>\n          <!-- Footer -->\n          <tr>\n            <td style="padding: 20px 32px; background-color: #F8FAFC; border-top: 1px solid #E2E8F0; text-align: center; font-size: 12px; color: #94A3B8;">\n              &copy; SkillBridge — Platform Validasi Bukti &amp; Kesiapan Talenta.\n            </td>\n          </tr>\n        </table>\n      </td>\n    </tr>\n  </table>\n</body>\n</html>	SMTP_HOST belum dikonfigurasi di .env	2026-11-25 08:28:52.306792+07
2	haikal@gmail.com	Haikal Maghsin	Kata Sandi Berhasil Diperbarui · SkillBridge	<!DOCTYPE html>\n<html>\n<head>\n  <meta charset="utf-8">\n  <title>Kata Sandi Berhasil Diperbarui</title>\n</head>\n<body style="margin: 0; padding: 0; background-color: #F8FAFC; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1E293B;">\n  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding: 40px 15px;">\n    <tr>\n      <td align="center">\n        <table role="presentation" width="100%" style="max-width: 540px; background-color: #FFFFFF; border-radius: 12px; border: 1px solid #E2E8F0; padding: 32px;" cellspacing="0" cellpadding="0">\n          <tr>\n            <td>\n              <h2 style="margin: 0 0 14px; font-size: 20px; color: #16A34A;">✓ Kata Sandi Berhasil Diperbarui</h2>\n              <p style="margin: 0 0 14px; font-size: 14px; color: #475569;">\n                Halo <strong>Haikal Maghsin</strong>,\n              </p>\n              <p style="margin: 0 0 16px; font-size: 14px; color: #475569; line-height: 1.5;">\n                Kata sandi untuk akun SkillBridge Anda telah berhasil diubah pada <strong>25 Nov 2026, 01:28 WIB</strong>.\n              </p>\n              <p style="margin: 0; font-size: 13px; color: #64748B; line-height: 1.5;">\n                Jika bukan Anda yang melakukan perubahan ini, segera hubungi tim administrator SkillBridge untuk mengamankan akun Anda.\n              </p>\n            </td>\n          </tr>\n        </table>\n      </td>\n    </tr>\n  </table>\n</body>\n</html>	SMTP_HOST belum dikonfigurasi di .env	2026-11-25 08:28:52.578661+07
3	haikal@gmail.com	Haikal Maghsin	Verifikasi Reset Kata Sandi · SkillBridge	<!DOCTYPE html>\n<html>\n<head>\n  <meta charset="utf-8">\n  <meta name="viewport" content="width=device-width, initial-scale=1.0">\n  <title>Reset Password SkillBridge</title>\n</head>\n<body style="margin: 0; padding: 0; background-color: #F8FAFC; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1E293B;">\n  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #F8FAFC; padding: 40px 15px;">\n    <tr>\n      <td align="center">\n        <table role="presentation" width="100%" style="max-width: 540px; background-color: #FFFFFF; border-radius: 12px; border: 1px solid #E2E8F0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); overflow: hidden;" cellspacing="0" cellpadding="0">\n          <!-- Header -->\n          <tr>\n            <td style="padding: 28px 32px; background: linear-gradient(135deg, #4338CA 0%, #6366F1 100%); text-align: left;">\n              <table role="presentation" cellspacing="0" cellpadding="0">\n                <tr>\n                  <td style="width: 36px; height: 36px; background: rgba(255, 255, 255, 0.2); border-radius: 8px; text-align: center; vertical-align: middle; font-size: 20px; font-weight: 800; color: #FFFFFF;">S</td>\n                  <td style="padding-left: 12px; font-size: 20px; font-weight: 700; color: #FFFFFF; letter-spacing: -0.3px;">SkillBridge</td>\n                </tr>\n              </table>\n            </td>\n          </tr>\n          <!-- Body -->\n          <tr>\n            <td style="padding: 32px;">\n              <h1 style="margin: 0 0 16px; font-size: 22px; font-weight: 700; color: #0F172A; line-height: 1.3;">Permintaan Verifikasi Reset Kata Sandi</h1>\n              <p style="margin: 0 0 16px; font-size: 15px; line-height: 1.6; color: #475569;">\n                Halo <strong>Haikal Maghsin</strong>,\n              </p>\n              <p style="margin: 0 0 20px; font-size: 15px; line-height: 1.6; color: #475569;">\n                Kami menerima permintaan pengaturan ulang kata sandi untuk akun Anda dengan peran <strong>Mahasiswa / Talenta</strong>. Untuk melanjutkan dan mengatur kata sandi baru Anda, silakan klik tombol verifikasi di bawah ini:\n              </p>\n\n              <!-- Tombol CTA -->\n              <table role="presentation" cellspacing="0" cellpadding="0" style="margin: 28px 0;">\n                <tr>\n                  <td align="center" style="border-radius: 8px; background-color: #4F46E5;">\n                    <a href="http://localhost/tools/reset-password.php?token=062a729a4d81180b47b21eedf4bd6d170f22f815b1b22db8ea13b9ce5d85524b" target="_blank" style="display: inline-block; padding: 14px 28px; font-size: 15px; font-weight: 600; color: #FFFFFF; text-decoration: none; border-radius: 8px;">\n                      Verifikasi &amp; Reset Kata Sandi &rarr;\n                    </a>\n                  </td>\n                </tr>\n              </table>\n\n              <p style="margin: 0 0 16px; font-size: 13px; line-height: 1.6; color: #64748B;">\n                Atau salin dan tempel tautan berikut ke browser Anda jika tombol di atas tidak berfungsi:\n              </p>\n              <p style="margin: 0 0 24px; font-size: 12px; line-height: 1.5; color: #4F46E5; word-break: break-all; background: #F1F5F9; padding: 10px 12px; border-radius: 6px;">\n                http://localhost/tools/reset-password.php?token=062a729a4d81180b47b21eedf4bd6d170f22f815b1b22db8ea13b9ce5d85524b\n              </p>\n\n              <div style="border-top: 1px solid #E2E8F0; padding-top: 18px; margin-top: 24px;">\n                <p style="margin: 0 0 8px; font-size: 13px; color: #94A3B8; line-height: 1.5;">\n                  ⏳ <strong>Perhatian:</strong> Tautan verifikasi ini hanya berlaku selama <strong>1 jam</strong>.\n                </p>\n                <p style="margin: 0; font-size: 13px; color: #94A3B8; line-height: 1.5;">\n                  🔒 Jika Anda tidak pernah meminta reset kata sandi ini, abaikan email ini. Akun Anda tetap aman dan kata sandi tidak akan berubah.\n                </p>\n              </div>\n            </td>\n          </tr>\n          <!-- Footer -->\n          <tr>\n            <td style="padding: 20px 32px; background-color: #F8FAFC; border-top: 1px solid #E2E8F0; text-align: center; font-size: 12px; color: #94A3B8;">\n              &copy; SkillBridge — Platform Validasi Bukti &amp; Kesiapan Talenta.\n            </td>\n          </tr>\n        </table>\n      </td>\n    </tr>\n  </table>\n</body>\n</html>	SMTP_HOST belum dikonfigurasi di .env	2026-11-25 08:28:53.102348+07
4	haikal@gmail.com	Haikal Maghsin	Kata Sandi Berhasil Diperbarui · SkillBridge	<!DOCTYPE html>\n<html>\n<head>\n  <meta charset="utf-8">\n  <title>Kata Sandi Berhasil Diperbarui</title>\n</head>\n<body style="margin: 0; padding: 0; background-color: #F8FAFC; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1E293B;">\n  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding: 40px 15px;">\n    <tr>\n      <td align="center">\n        <table role="presentation" width="100%" style="max-width: 540px; background-color: #FFFFFF; border-radius: 12px; border: 1px solid #E2E8F0; padding: 32px;" cellspacing="0" cellpadding="0">\n          <tr>\n            <td>\n              <h2 style="margin: 0 0 14px; font-size: 20px; color: #16A34A;">✓ Kata Sandi Berhasil Diperbarui</h2>\n              <p style="margin: 0 0 14px; font-size: 14px; color: #475569;">\n                Halo <strong>Haikal Maghsin</strong>,\n              </p>\n              <p style="margin: 0 0 16px; font-size: 14px; color: #475569; line-height: 1.5;">\n                Kata sandi untuk akun SkillBridge Anda telah berhasil diubah pada <strong>25 Nov 2026, 01:28 WIB</strong>.\n              </p>\n              <p style="margin: 0; font-size: 13px; color: #64748B; line-height: 1.5;">\n                Jika bukan Anda yang melakukan perubahan ini, segera hubungi tim administrator SkillBridge untuk mengamankan akun Anda.\n              </p>\n            </td>\n          </tr>\n        </table>\n      </td>\n    </tr>\n  </table>\n</body>\n</html>	SMTP_HOST belum dikonfigurasi di .env	2026-11-25 08:28:53.356727+07
5	hr@skillbridge.test	HR Tech Indonesia	Verifikasi Reset Kata Sandi · SkillBridge	<!DOCTYPE html>\n<html>\n<head>\n  <meta charset="utf-8">\n  <meta name="viewport" content="width=device-width, initial-scale=1.0">\n  <title>Reset Password SkillBridge</title>\n</head>\n<body style="margin: 0; padding: 0; background-color: #F8FAFC; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1E293B;">\n  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #F8FAFC; padding: 40px 15px;">\n    <tr>\n      <td align="center">\n        <table role="presentation" width="100%" style="max-width: 540px; background-color: #FFFFFF; border-radius: 12px; border: 1px solid #E2E8F0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); overflow: hidden;" cellspacing="0" cellpadding="0">\n          <!-- Header -->\n          <tr>\n            <td style="padding: 28px 32px; background: linear-gradient(135deg, #4338CA 0%, #6366F1 100%); text-align: left;">\n              <table role="presentation" cellspacing="0" cellpadding="0">\n                <tr>\n                  <td style="width: 36px; height: 36px; background: rgba(255, 255, 255, 0.2); border-radius: 8px; text-align: center; vertical-align: middle; font-size: 20px; font-weight: 800; color: #FFFFFF;">S</td>\n                  <td style="padding-left: 12px; font-size: 20px; font-weight: 700; color: #FFFFFF; letter-spacing: -0.3px;">SkillBridge</td>\n                </tr>\n              </table>\n            </td>\n          </tr>\n          <!-- Body -->\n          <tr>\n            <td style="padding: 32px;">\n              <h1 style="margin: 0 0 16px; font-size: 22px; font-weight: 700; color: #0F172A; line-height: 1.3;">Permintaan Verifikasi Reset Kata Sandi</h1>\n              <p style="margin: 0 0 16px; font-size: 15px; line-height: 1.6; color: #475569;">\n                Halo <strong>HR Tech Indonesia</strong>,\n              </p>\n              <p style="margin: 0 0 20px; font-size: 15px; line-height: 1.6; color: #475569;">\n                Kami menerima permintaan pengaturan ulang kata sandi untuk akun Anda dengan peran <strong>Perusahaan / Recruiter (HR)</strong>. Untuk melanjutkan dan mengatur kata sandi baru Anda, silakan klik tombol verifikasi di bawah ini:\n              </p>\n\n              <!-- Tombol CTA -->\n              <table role="presentation" cellspacing="0" cellpadding="0" style="margin: 28px 0;">\n                <tr>\n                  <td align="center" style="border-radius: 8px; background-color: #4F46E5;">\n                    <a href="http://localhost/tools/reset-password.php?token=b040bad715d7f97b22c6d03e5dc22fc03942c5072dbbd286a0a14721992ea8e3" target="_blank" style="display: inline-block; padding: 14px 28px; font-size: 15px; font-weight: 600; color: #FFFFFF; text-decoration: none; border-radius: 8px;">\n                      Verifikasi &amp; Reset Kata Sandi &rarr;\n                    </a>\n                  </td>\n                </tr>\n              </table>\n\n              <p style="margin: 0 0 16px; font-size: 13px; line-height: 1.6; color: #64748B;">\n                Atau salin dan tempel tautan berikut ke browser Anda jika tombol di atas tidak berfungsi:\n              </p>\n              <p style="margin: 0 0 24px; font-size: 12px; line-height: 1.5; color: #4F46E5; word-break: break-all; background: #F1F5F9; padding: 10px 12px; border-radius: 6px;">\n                http://localhost/tools/reset-password.php?token=b040bad715d7f97b22c6d03e5dc22fc03942c5072dbbd286a0a14721992ea8e3\n              </p>\n\n              <div style="border-top: 1px solid #E2E8F0; padding-top: 18px; margin-top: 24px;">\n                <p style="margin: 0 0 8px; font-size: 13px; color: #94A3B8; line-height: 1.5;">\n                  ⏳ <strong>Perhatian:</strong> Tautan verifikasi ini hanya berlaku selama <strong>1 jam</strong>.\n                </p>\n                <p style="margin: 0; font-size: 13px; color: #94A3B8; line-height: 1.5;">\n                  🔒 Jika Anda tidak pernah meminta reset kata sandi ini, abaikan email ini. Akun Anda tetap aman dan kata sandi tidak akan berubah.\n                </p>\n              </div>\n            </td>\n          </tr>\n          <!-- Footer -->\n          <tr>\n            <td style="padding: 20px 32px; background-color: #F8FAFC; border-top: 1px solid #E2E8F0; text-align: center; font-size: 12px; color: #94A3B8;">\n              &copy; SkillBridge — Platform Validasi Bukti &amp; Kesiapan Talenta.\n            </td>\n          </tr>\n        </table>\n      </td>\n    </tr>\n  </table>\n</body>\n</html>	SMTP_HOST belum dikonfigurasi di .env	2026-11-25 08:28:53.361543+07
6	hr@skillbridge.test	HR Tech Indonesia	Kata Sandi Berhasil Diperbarui · SkillBridge	<!DOCTYPE html>\n<html>\n<head>\n  <meta charset="utf-8">\n  <title>Kata Sandi Berhasil Diperbarui</title>\n</head>\n<body style="margin: 0; padding: 0; background-color: #F8FAFC; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1E293B;">\n  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding: 40px 15px;">\n    <tr>\n      <td align="center">\n        <table role="presentation" width="100%" style="max-width: 540px; background-color: #FFFFFF; border-radius: 12px; border: 1px solid #E2E8F0; padding: 32px;" cellspacing="0" cellpadding="0">\n          <tr>\n            <td>\n              <h2 style="margin: 0 0 14px; font-size: 20px; color: #16A34A;">✓ Kata Sandi Berhasil Diperbarui</h2>\n              <p style="margin: 0 0 14px; font-size: 14px; color: #475569;">\n                Halo <strong>HR Tech Indonesia</strong>,\n              </p>\n              <p style="margin: 0 0 16px; font-size: 14px; color: #475569; line-height: 1.5;">\n                Kata sandi untuk akun SkillBridge Anda telah berhasil diubah pada <strong>25 Nov 2026, 01:28 WIB</strong>.\n              </p>\n              <p style="margin: 0; font-size: 13px; color: #64748B; line-height: 1.5;">\n                Jika bukan Anda yang melakukan perubahan ini, segera hubungi tim administrator SkillBridge untuk mengamankan akun Anda.\n              </p>\n            </td>\n          </tr>\n        </table>\n      </td>\n    </tr>\n  </table>\n</body>\n</html>	SMTP_HOST belum dikonfigurasi di .env	2026-11-25 08:28:53.619833+07
7	hr@skillbridge.test	HR Tech Indonesia	Verifikasi Reset Kata Sandi · SkillBridge	<!DOCTYPE html>\n<html>\n<head>\n  <meta charset="utf-8">\n  <meta name="viewport" content="width=device-width, initial-scale=1.0">\n  <title>Reset Password SkillBridge</title>\n</head>\n<body style="margin: 0; padding: 0; background-color: #F8FAFC; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1E293B;">\n  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #F8FAFC; padding: 40px 15px;">\n    <tr>\n      <td align="center">\n        <table role="presentation" width="100%" style="max-width: 540px; background-color: #FFFFFF; border-radius: 12px; border: 1px solid #E2E8F0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); overflow: hidden;" cellspacing="0" cellpadding="0">\n          <!-- Header -->\n          <tr>\n            <td style="padding: 28px 32px; background: linear-gradient(135deg, #4338CA 0%, #6366F1 100%); text-align: left;">\n              <table role="presentation" cellspacing="0" cellpadding="0">\n                <tr>\n                  <td style="width: 36px; height: 36px; background: rgba(255, 255, 255, 0.2); border-radius: 8px; text-align: center; vertical-align: middle; font-size: 20px; font-weight: 800; color: #FFFFFF;">S</td>\n                  <td style="padding-left: 12px; font-size: 20px; font-weight: 700; color: #FFFFFF; letter-spacing: -0.3px;">SkillBridge</td>\n                </tr>\n              </table>\n            </td>\n          </tr>\n          <!-- Body -->\n          <tr>\n            <td style="padding: 32px;">\n              <h1 style="margin: 0 0 16px; font-size: 22px; font-weight: 700; color: #0F172A; line-height: 1.3;">Permintaan Verifikasi Reset Kata Sandi</h1>\n              <p style="margin: 0 0 16px; font-size: 15px; line-height: 1.6; color: #475569;">\n                Halo <strong>HR Tech Indonesia</strong>,\n              </p>\n              <p style="margin: 0 0 20px; font-size: 15px; line-height: 1.6; color: #475569;">\n                Kami menerima permintaan pengaturan ulang kata sandi untuk akun Anda dengan peran <strong>Perusahaan / Recruiter (HR)</strong>. Untuk melanjutkan dan mengatur kata sandi baru Anda, silakan klik tombol verifikasi di bawah ini:\n              </p>\n\n              <!-- Tombol CTA -->\n              <table role="presentation" cellspacing="0" cellpadding="0" style="margin: 28px 0;">\n                <tr>\n                  <td align="center" style="border-radius: 8px; background-color: #4F46E5;">\n                    <a href="http://localhost/tools/reset-password.php?token=ef5f202176a40748e0410e9a6559312f1595c758088dbab0f3b26d2f2fd300c7" target="_blank" style="display: inline-block; padding: 14px 28px; font-size: 15px; font-weight: 600; color: #FFFFFF; text-decoration: none; border-radius: 8px;">\n                      Verifikasi &amp; Reset Kata Sandi &rarr;\n                    </a>\n                  </td>\n                </tr>\n              </table>\n\n              <p style="margin: 0 0 16px; font-size: 13px; line-height: 1.6; color: #64748B;">\n                Atau salin dan tempel tautan berikut ke browser Anda jika tombol di atas tidak berfungsi:\n              </p>\n              <p style="margin: 0 0 24px; font-size: 12px; line-height: 1.5; color: #4F46E5; word-break: break-all; background: #F1F5F9; padding: 10px 12px; border-radius: 6px;">\n                http://localhost/tools/reset-password.php?token=ef5f202176a40748e0410e9a6559312f1595c758088dbab0f3b26d2f2fd300c7\n              </p>\n\n              <div style="border-top: 1px solid #E2E8F0; padding-top: 18px; margin-top: 24px;">\n                <p style="margin: 0 0 8px; font-size: 13px; color: #94A3B8; line-height: 1.5;">\n                  ⏳ <strong>Perhatian:</strong> Tautan verifikasi ini hanya berlaku selama <strong>1 jam</strong>.\n                </p>\n                <p style="margin: 0; font-size: 13px; color: #94A3B8; line-height: 1.5;">\n                  🔒 Jika Anda tidak pernah meminta reset kata sandi ini, abaikan email ini. Akun Anda tetap aman dan kata sandi tidak akan berubah.\n                </p>\n              </div>\n            </td>\n          </tr>\n          <!-- Footer -->\n          <tr>\n            <td style="padding: 20px 32px; background-color: #F8FAFC; border-top: 1px solid #E2E8F0; text-align: center; font-size: 12px; color: #94A3B8;">\n              &copy; SkillBridge — Platform Validasi Bukti &amp; Kesiapan Talenta.\n            </td>\n          </tr>\n        </table>\n      </td>\n    </tr>\n  </table>\n</body>\n</html>	SMTP_HOST belum dikonfigurasi di .env	2026-11-25 08:28:53.87194+07
8	hr@skillbridge.test	HR Tech Indonesia	Kata Sandi Berhasil Diperbarui · SkillBridge	<!DOCTYPE html>\n<html>\n<head>\n  <meta charset="utf-8">\n  <title>Kata Sandi Berhasil Diperbarui</title>\n</head>\n<body style="margin: 0; padding: 0; background-color: #F8FAFC; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1E293B;">\n  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding: 40px 15px;">\n    <tr>\n      <td align="center">\n        <table role="presentation" width="100%" style="max-width: 540px; background-color: #FFFFFF; border-radius: 12px; border: 1px solid #E2E8F0; padding: 32px;" cellspacing="0" cellpadding="0">\n          <tr>\n            <td>\n              <h2 style="margin: 0 0 14px; font-size: 20px; color: #16A34A;">✓ Kata Sandi Berhasil Diperbarui</h2>\n              <p style="margin: 0 0 14px; font-size: 14px; color: #475569;">\n                Halo <strong>HR Tech Indonesia</strong>,\n              </p>\n              <p style="margin: 0 0 16px; font-size: 14px; color: #475569; line-height: 1.5;">\n                Kata sandi untuk akun SkillBridge Anda telah berhasil diubah pada <strong>25 Nov 2026, 01:28 WIB</strong>.\n              </p>\n              <p style="margin: 0; font-size: 13px; color: #64748B; line-height: 1.5;">\n                Jika bukan Anda yang melakukan perubahan ini, segera hubungi tim administrator SkillBridge untuk mengamankan akun Anda.\n              </p>\n            </td>\n          </tr>\n        </table>\n      </td>\n    </tr>\n  </table>\n</body>\n</html>	SMTP_HOST belum dikonfigurasi di .env	2026-11-25 08:28:54.131555+07
9	admin@skillbridge.test	Admin Test	Verifikasi Reset Kata Sandi · SkillBridge	<!DOCTYPE html>\n<html>\n<head>\n  <meta charset="utf-8">\n  <meta name="viewport" content="width=device-width, initial-scale=1.0">\n  <title>Reset Password SkillBridge</title>\n</head>\n<body style="margin: 0; padding: 0; background-color: #F8FAFC; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1E293B;">\n  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #F8FAFC; padding: 40px 15px;">\n    <tr>\n      <td align="center">\n        <table role="presentation" width="100%" style="max-width: 540px; background-color: #FFFFFF; border-radius: 12px; border: 1px solid #E2E8F0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); overflow: hidden;" cellspacing="0" cellpadding="0">\n          <!-- Header -->\n          <tr>\n            <td style="padding: 28px 32px; background: linear-gradient(135deg, #4338CA 0%, #6366F1 100%); text-align: left;">\n              <table role="presentation" cellspacing="0" cellpadding="0">\n                <tr>\n                  <td style="width: 36px; height: 36px; background: rgba(255, 255, 255, 0.2); border-radius: 8px; text-align: center; vertical-align: middle; font-size: 20px; font-weight: 800; color: #FFFFFF;">S</td>\n                  <td style="padding-left: 12px; font-size: 20px; font-weight: 700; color: #FFFFFF; letter-spacing: -0.3px;">SkillBridge</td>\n                </tr>\n              </table>\n            </td>\n          </tr>\n          <!-- Body -->\n          <tr>\n            <td style="padding: 32px;">\n              <h1 style="margin: 0 0 16px; font-size: 22px; font-weight: 700; color: #0F172A; line-height: 1.3;">Permintaan Verifikasi Reset Kata Sandi</h1>\n              <p style="margin: 0 0 16px; font-size: 15px; line-height: 1.6; color: #475569;">\n                Halo <strong>Admin Test</strong>,\n              </p>\n              <p style="margin: 0 0 20px; font-size: 15px; line-height: 1.6; color: #475569;">\n                Kami menerima permintaan pengaturan ulang kata sandi untuk akun Anda dengan peran <strong>Administrator</strong>. Untuk melanjutkan dan mengatur kata sandi baru Anda, silakan klik tombol verifikasi di bawah ini:\n              </p>\n\n              <!-- Tombol CTA -->\n              <table role="presentation" cellspacing="0" cellpadding="0" style="margin: 28px 0;">\n                <tr>\n                  <td align="center" style="border-radius: 8px; background-color: #4F46E5;">\n                    <a href="http://localhost/tools/reset-password.php?token=1bb2691bb08375c012a6ad01d2106db02a94b7640baca221ab97aac2bec00ef1" target="_blank" style="display: inline-block; padding: 14px 28px; font-size: 15px; font-weight: 600; color: #FFFFFF; text-decoration: none; border-radius: 8px;">\n                      Verifikasi &amp; Reset Kata Sandi &rarr;\n                    </a>\n                  </td>\n                </tr>\n              </table>\n\n              <p style="margin: 0 0 16px; font-size: 13px; line-height: 1.6; color: #64748B;">\n                Atau salin dan tempel tautan berikut ke browser Anda jika tombol di atas tidak berfungsi:\n              </p>\n              <p style="margin: 0 0 24px; font-size: 12px; line-height: 1.5; color: #4F46E5; word-break: break-all; background: #F1F5F9; padding: 10px 12px; border-radius: 6px;">\n                http://localhost/tools/reset-password.php?token=1bb2691bb08375c012a6ad01d2106db02a94b7640baca221ab97aac2bec00ef1\n              </p>\n\n              <div style="border-top: 1px solid #E2E8F0; padding-top: 18px; margin-top: 24px;">\n                <p style="margin: 0 0 8px; font-size: 13px; color: #94A3B8; line-height: 1.5;">\n                  ⏳ <strong>Perhatian:</strong> Tautan verifikasi ini hanya berlaku selama <strong>1 jam</strong>.\n                </p>\n                <p style="margin: 0; font-size: 13px; color: #94A3B8; line-height: 1.5;">\n                  🔒 Jika Anda tidak pernah meminta reset kata sandi ini, abaikan email ini. Akun Anda tetap aman dan kata sandi tidak akan berubah.\n                </p>\n              </div>\n            </td>\n          </tr>\n          <!-- Footer -->\n          <tr>\n            <td style="padding: 20px 32px; background-color: #F8FAFC; border-top: 1px solid #E2E8F0; text-align: center; font-size: 12px; color: #94A3B8;">\n              &copy; SkillBridge — Platform Validasi Bukti &amp; Kesiapan Talenta.\n            </td>\n          </tr>\n        </table>\n      </td>\n    </tr>\n  </table>\n</body>\n</html>	SMTP_HOST belum dikonfigurasi di .env	2026-11-25 08:28:54.138028+07
10	admin@skillbridge.test	Admin Test	Kata Sandi Berhasil Diperbarui · SkillBridge	<!DOCTYPE html>\n<html>\n<head>\n  <meta charset="utf-8">\n  <title>Kata Sandi Berhasil Diperbarui</title>\n</head>\n<body style="margin: 0; padding: 0; background-color: #F8FAFC; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1E293B;">\n  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding: 40px 15px;">\n    <tr>\n      <td align="center">\n        <table role="presentation" width="100%" style="max-width: 540px; background-color: #FFFFFF; border-radius: 12px; border: 1px solid #E2E8F0; padding: 32px;" cellspacing="0" cellpadding="0">\n          <tr>\n            <td>\n              <h2 style="margin: 0 0 14px; font-size: 20px; color: #16A34A;">✓ Kata Sandi Berhasil Diperbarui</h2>\n              <p style="margin: 0 0 14px; font-size: 14px; color: #475569;">\n                Halo <strong>Admin Test</strong>,\n              </p>\n              <p style="margin: 0 0 16px; font-size: 14px; color: #475569; line-height: 1.5;">\n                Kata sandi untuk akun SkillBridge Anda telah berhasil diubah pada <strong>25 Nov 2026, 01:28 WIB</strong>.\n              </p>\n              <p style="margin: 0; font-size: 13px; color: #64748B; line-height: 1.5;">\n                Jika bukan Anda yang melakukan perubahan ini, segera hubungi tim administrator SkillBridge untuk mengamankan akun Anda.\n              </p>\n            </td>\n          </tr>\n        </table>\n      </td>\n    </tr>\n  </table>\n</body>\n</html>	SMTP_HOST belum dikonfigurasi di .env	2026-11-25 08:28:54.40633+07
11	admin@skillbridge.test	Admin Test	Verifikasi Reset Kata Sandi · SkillBridge	<!DOCTYPE html>\n<html>\n<head>\n  <meta charset="utf-8">\n  <meta name="viewport" content="width=device-width, initial-scale=1.0">\n  <title>Reset Password SkillBridge</title>\n</head>\n<body style="margin: 0; padding: 0; background-color: #F8FAFC; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1E293B;">\n  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #F8FAFC; padding: 40px 15px;">\n    <tr>\n      <td align="center">\n        <table role="presentation" width="100%" style="max-width: 540px; background-color: #FFFFFF; border-radius: 12px; border: 1px solid #E2E8F0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); overflow: hidden;" cellspacing="0" cellpadding="0">\n          <!-- Header -->\n          <tr>\n            <td style="padding: 28px 32px; background: linear-gradient(135deg, #4338CA 0%, #6366F1 100%); text-align: left;">\n              <table role="presentation" cellspacing="0" cellpadding="0">\n                <tr>\n                  <td style="width: 36px; height: 36px; background: rgba(255, 255, 255, 0.2); border-radius: 8px; text-align: center; vertical-align: middle; font-size: 20px; font-weight: 800; color: #FFFFFF;">S</td>\n                  <td style="padding-left: 12px; font-size: 20px; font-weight: 700; color: #FFFFFF; letter-spacing: -0.3px;">SkillBridge</td>\n                </tr>\n              </table>\n            </td>\n          </tr>\n          <!-- Body -->\n          <tr>\n            <td style="padding: 32px;">\n              <h1 style="margin: 0 0 16px; font-size: 22px; font-weight: 700; color: #0F172A; line-height: 1.3;">Permintaan Verifikasi Reset Kata Sandi</h1>\n              <p style="margin: 0 0 16px; font-size: 15px; line-height: 1.6; color: #475569;">\n                Halo <strong>Admin Test</strong>,\n              </p>\n              <p style="margin: 0 0 20px; font-size: 15px; line-height: 1.6; color: #475569;">\n                Kami menerima permintaan pengaturan ulang kata sandi untuk akun Anda dengan peran <strong>Administrator</strong>. Untuk melanjutkan dan mengatur kata sandi baru Anda, silakan klik tombol verifikasi di bawah ini:\n              </p>\n\n              <!-- Tombol CTA -->\n              <table role="presentation" cellspacing="0" cellpadding="0" style="margin: 28px 0;">\n                <tr>\n                  <td align="center" style="border-radius: 8px; background-color: #4F46E5;">\n                    <a href="http://localhost/tools/reset-password.php?token=bbe10d23c40f1df29f543e80525560f2eecc3b8d1989b047e0564cbfe088efdd" target="_blank" style="display: inline-block; padding: 14px 28px; font-size: 15px; font-weight: 600; color: #FFFFFF; text-decoration: none; border-radius: 8px;">\n                      Verifikasi &amp; Reset Kata Sandi &rarr;\n                    </a>\n                  </td>\n                </tr>\n              </table>\n\n              <p style="margin: 0 0 16px; font-size: 13px; line-height: 1.6; color: #64748B;">\n                Atau salin dan tempel tautan berikut ke browser Anda jika tombol di atas tidak berfungsi:\n              </p>\n              <p style="margin: 0 0 24px; font-size: 12px; line-height: 1.5; color: #4F46E5; word-break: break-all; background: #F1F5F9; padding: 10px 12px; border-radius: 6px;">\n                http://localhost/tools/reset-password.php?token=bbe10d23c40f1df29f543e80525560f2eecc3b8d1989b047e0564cbfe088efdd\n              </p>\n\n              <div style="border-top: 1px solid #E2E8F0; padding-top: 18px; margin-top: 24px;">\n                <p style="margin: 0 0 8px; font-size: 13px; color: #94A3B8; line-height: 1.5;">\n                  ⏳ <strong>Perhatian:</strong> Tautan verifikasi ini hanya berlaku selama <strong>1 jam</strong>.\n                </p>\n                <p style="margin: 0; font-size: 13px; color: #94A3B8; line-height: 1.5;">\n                  🔒 Jika Anda tidak pernah meminta reset kata sandi ini, abaikan email ini. Akun Anda tetap aman dan kata sandi tidak akan berubah.\n                </p>\n              </div>\n            </td>\n          </tr>\n          <!-- Footer -->\n          <tr>\n            <td style="padding: 20px 32px; background-color: #F8FAFC; border-top: 1px solid #E2E8F0; text-align: center; font-size: 12px; color: #94A3B8;">\n              &copy; SkillBridge — Platform Validasi Bukti &amp; Kesiapan Talenta.\n            </td>\n          </tr>\n        </table>\n      </td>\n    </tr>\n  </table>\n</body>\n</html>	SMTP_HOST belum dikonfigurasi di .env	2026-11-25 08:28:54.672695+07
12	admin@skillbridge.test	Admin Test	Kata Sandi Berhasil Diperbarui · SkillBridge	<!DOCTYPE html>\n<html>\n<head>\n  <meta charset="utf-8">\n  <title>Kata Sandi Berhasil Diperbarui</title>\n</head>\n<body style="margin: 0; padding: 0; background-color: #F8FAFC; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1E293B;">\n  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding: 40px 15px;">\n    <tr>\n      <td align="center">\n        <table role="presentation" width="100%" style="max-width: 540px; background-color: #FFFFFF; border-radius: 12px; border: 1px solid #E2E8F0; padding: 32px;" cellspacing="0" cellpadding="0">\n          <tr>\n            <td>\n              <h2 style="margin: 0 0 14px; font-size: 20px; color: #16A34A;">✓ Kata Sandi Berhasil Diperbarui</h2>\n              <p style="margin: 0 0 14px; font-size: 14px; color: #475569;">\n                Halo <strong>Admin Test</strong>,\n              </p>\n              <p style="margin: 0 0 16px; font-size: 14px; color: #475569; line-height: 1.5;">\n                Kata sandi untuk akun SkillBridge Anda telah berhasil diubah pada <strong>25 Nov 2026, 01:28 WIB</strong>.\n              </p>\n              <p style="margin: 0; font-size: 13px; color: #64748B; line-height: 1.5;">\n                Jika bukan Anda yang melakukan perubahan ini, segera hubungi tim administrator SkillBridge untuk mengamankan akun Anda.\n              </p>\n            </td>\n          </tr>\n        </table>\n      </td>\n    </tr>\n  </table>\n</body>\n</html>	SMTP_HOST belum dikonfigurasi di .env	2026-11-25 08:28:54.935239+07
13	noreply@skillsbridge.my.id	Tester SkillBridge	Uji Coba Pengiriman Email · SkillBridge	\n<div style="font-family: sans-serif; padding: 20px; border: 1px solid #4F46E5; border-radius: 8px; max-width: 500px;">\n  <h2 style="color: #4F46E5; margin-top: 0;">✓ Tes Koneksi Email Berhasil!</h2>\n  <p>Halo,</p>\n  <p>Jika Anda membaca email ini, berarti konfigurasi SMTP di project <strong>SkillBridge</strong> Anda telah berhasil terhubung dan berjalan dengan sempurna.</p>\n  <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;" />\n  <small style="color: #888;">Dikirim pada: 2026-11-25 06:32:48 WIB</small>\n</div>	Fallback karena error SMTP: Koneksi ke ssl://skillsbridge.my.id:465 gagal: php_network_getaddresses: getaddrinfo for skillsbridge.my.id failed: No such host is known (0)	2026-11-25 13:32:48.497757+07
14	haikal.maghsin0756@gmail.com	Tester SkillBridge	Uji Coba Pengiriman Email · SkillBridge	\n<div style="font-family: sans-serif; padding: 20px; border: 1px solid #4F46E5; border-radius: 8px; max-width: 500px;">\n  <h2 style="color: #4F46E5; margin-top: 0;">✓ Tes Koneksi Email Berhasil!</h2>\n  <p>Halo,</p>\n  <p>Jika Anda membaca email ini, berarti konfigurasi SMTP di project <strong>SkillBridge</strong> Anda telah berhasil terhubung dan berjalan dengan sempurna.</p>\n  <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;" />\n  <small style="color: #888;">Dikirim pada: 2026-11-25 06:53:09 WIB</small>\n</div>	Fallback karena error SMTP: Koneksi ke ssl://skillsbridge.my.id:465 gagal: php_network_getaddresses: getaddrinfo for skillsbridge.my.id failed: No such host is known (0)	2026-11-25 13:53:10.007319+07
\.


--
-- TOC entry 5147 (class 0 OID 27053)
-- Dependencies: 243
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.password_reset_tokens (id, user_id, token_hash, expires_at, created_at, used_at) FROM stdin;
1	1	fac10ebd4f265991c41fd7442fa3d5290d76a685ccc8664552781d0ff0e078fd	2026-11-25 02:23:48+07	2026-11-25 08:23:48.009715+07	2026-11-25 08:25:23.258231+07
2	1	9cbfd89b5b828c354d3131904312e5a5672a936d41ad4f2adedee726525d5d60	2026-11-25 09:25:23.258231+07	2026-11-25 08:25:23.258231+07	2026-11-25 08:25:23.537069+07
3	1	b951d0f53a2ed9ebe37319561cbd96405703853da3e294feb153be16b523eae8	2026-11-25 09:25:24.056842+07	2026-11-25 08:25:24.056842+07	2026-11-25 08:25:24.310369+07
4	6	7cff26b3ecb1080fe473fdb38fcda6d2dddf2bf2533bd127e5c26e8ddebbfe6e	2026-11-25 09:25:24.315032+07	2026-11-25 08:25:24.315032+07	2026-11-25 08:25:24.578318+07
5	6	c30b7692d020cd6970304993792b69ac221d58dbd3278884d1a194907290e63d	2026-11-25 09:25:24.836641+07	2026-11-25 08:25:24.836641+07	2026-11-25 08:25:25.098674+07
6	5	f4ef08eafdda765a62a92dcb566614467670692139ea7e071ee34000a9beff92	2026-11-25 09:25:25.103201+07	2026-11-25 08:25:25.103201+07	2026-11-25 08:25:25.358887+07
7	5	717259eea134c7e6ac95471c3262834fcf363373e3fec7d18ab71929fda1f961	2026-11-25 09:25:25.617114+07	2026-11-25 08:25:25.617114+07	2026-11-25 08:25:25.975043+07
8	1	17233df8a2742f22c922c42b871d7acc2ebdab6e770693b4de1168ea1523d94b	2026-11-25 09:28:52.26317+07	2026-11-25 08:28:52.26317+07	2026-11-25 08:28:52.572618+07
9	1	ed614eb8a8985c25c2f12ab884de6fc3d782e827fdfbb8606dc4b3aba21ad3a9	2026-11-25 09:28:53.099139+07	2026-11-25 08:28:53.099139+07	2026-11-25 08:28:53.353185+07
10	6	a3106330d944aaca9976cc0b94f092751df3256953793365d658a2f0e2e8b4d0	2026-11-25 09:28:53.360097+07	2026-11-25 08:28:53.360097+07	2026-11-25 08:28:53.616278+07
11	6	b5cdc9492b5e42f932d1a6de72dc3abac825683b00f134c183435618b1a91a53	2026-11-25 09:28:53.870574+07	2026-11-25 08:28:53.870574+07	2026-11-25 08:28:54.12776+07
12	5	5ba483990502b05b529df04c157ec7ddb1bd405c45a272fa5a9c8804a7a6d113	2026-11-25 09:28:54.136474+07	2026-11-25 08:28:54.136474+07	2026-11-25 08:28:54.403214+07
13	5	6203a94e311356dd88ca4c858f98af86fdd56f56ca01f58a89d25f1bae558a0a	2026-11-25 09:28:54.66974+07	2026-11-25 08:28:54.66974+07	2026-11-25 08:28:54.932198+07
14	3	893639ed37334a93202db87e08c9ee9c6408a489e6645553d32aa69b94e8ed5c	2026-11-25 14:33:57.255926+07	2026-11-25 13:33:57.255926+07	\N
\.


--
-- TOC entry 5143 (class 0 OID 26796)
-- Dependencies: 239
-- Data for Name: recruiter_activity_logs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.recruiter_activity_logs (id, company_id, user_id, action_type, target_type, target_id, description, created_at) FROM stdin;
1	1	6	vacancy_created	vacancy	4	Created vacancy: UI UX Designer (Intern)	2026-11-10 07:25:49.070318+07
2	1	6	status_update	candidate	1	Memperbarui status Haikal Maghsin menjadi Shortlisted	2026-11-10 07:27:39.92445+07
3	1	6	status_update	candidate	1	Memperbarui status Haikal Maghsin menjadi Interview	2026-11-10 07:28:12.103466+07
4	1	6	status_update	candidate	1	Memperbarui status Haikal Maghsin menjadi Accepted	2026-11-10 07:28:16.798562+07
5	1	6	status_update	candidate	1	Memperbarui status Haikal Maghsin menjadi Rejected	2026-11-10 13:40:02.68407+07
6	1	6	status_update	candidate	1	Memperbarui status Haikal Maghsin menjadi Shortlisted	2026-11-10 13:41:50.758308+07
7	1	6	status_update	candidate	1	Memperbarui status Haikal Maghsin menjadi Interview	2026-11-10 13:44:25.885372+07
8	1	6	status_update	candidate	1	Memperbarui status Haikal Maghsin menjadi Shortlisted	2026-11-10 13:44:45.690602+07
9	1	6	status_update	candidate	1	Memperbarui status Haikal Maghsin menjadi Interview	2026-11-10 13:44:49.042168+07
10	1	6	status_update	candidate	1	Memperbarui status Haikal Maghsin menjadi Accepted	2026-11-10 13:45:23.788352+07
11	1	6	vacancy_created	vacancy	5	Created vacancy: Product Designer	2026-11-10 13:46:59.261067+07
\.


--
-- TOC entry 5145 (class 0 OID 26833)
-- Dependencies: 241
-- Data for Name: schema_migrations; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.schema_migrations (id, migration, applied_at) FROM stdin;
1	2026-11-05_01_users_is_active.sql	2026-11-16 10:55:19.236079
2	2026-11-13_01_core_bugs_and_r2.sql	2026-11-16 10:55:19.251359
3	2026-11-25_01_password_resets.sql	2026-11-25 08:20:41.842301
\.


--
-- TOC entry 5123 (class 0 OID 26538)
-- Dependencies: 218
-- Data for Name: skills; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.skills (id, name, category, description) FROM stdin;
1	PHP	Programming	\N
2	Python	Programming	\N
3	SQL	Database	\N
4	PostgreSQL	Database	\N
5	HTML	Web	\N
6	CSS	Web	\N
7	JavaScript	Web	\N
8	Responsive Design	Web	\N
9	REST APIs	Web	\N
10	Git	Tools	\N
11	Figma	Tools	\N
12	Excel	Data	\N
13	Data Visualisation	Data	\N
14	Statistics	Data	\N
15	User Research	Design	\N
16	Wireframing	Design	\N
17	Prototyping	Design	\N
18	Usability Testing	Design	\N
55	Copywriter	Design	
56	Quality Assurance	Design	
57	Quality Controll	Programming	
58	Membaca Dokumentasi	Data	
\.


--
-- TOC entry 5128 (class 0 OID 26584)
-- Dependencies: 223
-- Data for Name: student_profiles; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.student_profiles (user_id, target_career_id, is_public, public_token, updated_at, institution, study_program, semester, location, phone, bio) FROM stdin;
3	\N	f	\N	2026-10-23 14:57:21.961804+07	Politeknik Negeri Malang	Teknik Informatika	3	Malang, Indonesia	\N	\N
4	4	f	\N	2026-11-08 08:35:29.695333+07	Politeknik Negeri Malang	Teknik Informatika	3	Malang, Indonesia	\N	\N
8	1	t	a9f26c03-2302-4f3a-bf0f-fbf16ec69a73	2026-11-09 08:37:52.393409+07	Politeknik Negeri Malang	Teknik Informatika	3	Malang, Indonesia	\N	\N
9	2	f	b157826f-488b-424d-aeb2-3e5a3d457d23	2026-11-09 08:37:52.966665+07	Politeknik Negeri Malang	Teknik Informatika	3	Malang, Indonesia	\N	\N
10	\N	f	0d8d1f7b-9808-4a8a-91db-0f24cf5f9014	2026-11-09 08:37:53.462734+07	Politeknik Negeri Malang	Teknik Informatika	3	Malang, Indonesia	\N	\N
11	\N	f	13ab760e-b092-4dc4-aa14-7b570788d2b1	2026-11-09 08:37:54.010572+07	Politeknik Negeri Malang	Teknik Informatika	3	Malang, Indonesia	\N	\N
12	\N	f	\N	2026-11-10 13:17:11.157396+07	Politeknik Negeri Malang	Teknik Informatika	3	Malang, Indonesia	\N	\N
1	1	t	305f260c-1942-40c1-a599-a44c9cd57ff5	2026-11-16 08:42:26.382816+07	Politeknik Negeri Malang	Teknik Informatika	3	Malang, Indonesia	\N	\N
\.


--
-- TOC entry 5132 (class 0 OID 26625)
-- Dependencies: 227
-- Data for Name: user_skills; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.user_skills (id, user_id, skill_id, source, status, created_at, updated_at) FROM stdin;
1	1	13	manual	confirmed	2026-10-27 13:59:48.222567+07	2026-10-27 13:59:48.222567+07
2	1	10	manual	confirmed	2026-10-27 14:14:44.801147+07	2026-10-27 14:14:44.801147+07
3	1	12	manual	confirmed	2026-10-27 14:14:54.642359+07	2026-10-27 14:14:54.642359+07
4	1	2	manual	confirmed	2026-10-27 15:40:20.016474+07	2026-10-27 15:40:20.016474+07
5	1	3	manual	confirmed	2026-10-27 15:40:26.030527+07	2026-10-27 15:40:26.030527+07
6	1	14	manual	confirmed	2026-10-27 15:40:32.521702+07	2026-10-27 15:40:32.521702+07
7	1	8	manual	confirmed	2026-11-04 19:19:49.406204+07	2026-11-04 19:19:49.406204+07
8	1	7	manual	confirmed	2026-11-04 20:35:09.280874+07	2026-11-04 20:35:09.280874+07
9	1	1	manual	confirmed	2026-11-04 20:35:59.781659+07	2026-11-04 20:35:59.781659+07
10	1	4	manual	confirmed	2026-11-04 20:36:02.223078+07	2026-11-04 20:36:02.223078+07
11	1	9	manual	confirmed	2026-11-04 20:36:04.942813+07	2026-11-04 20:36:04.942813+07
12	1	11	manual	confirmed	2026-11-08 07:45:27.872684+07	2026-11-08 07:45:27.872684+07
13	1	17	manual	confirmed	2026-11-08 08:09:03.43088+07	2026-11-08 08:09:03.43088+07
14	1	18	manual	confirmed	2026-11-08 08:09:16.42376+07	2026-11-08 08:09:16.42376+07
15	1	15	manual	confirmed	2026-11-08 08:09:19.738653+07	2026-11-08 08:09:19.738653+07
16	1	16	manual	confirmed	2026-11-08 08:09:21.512134+07	2026-11-08 08:09:21.512134+07
17	4	13	manual	confirmed	2026-11-08 08:35:22.190808+07	2026-11-08 08:35:22.190808+07
18	4	11	manual	confirmed	2026-11-08 08:35:37.303367+07	2026-11-08 08:35:37.303367+07
19	4	17	manual	confirmed	2026-11-08 08:35:39.916056+07	2026-11-08 08:35:39.916056+07
20	4	18	manual	confirmed	2026-11-08 08:35:42.503876+07	2026-11-08 08:35:42.503876+07
21	4	15	manual	confirmed	2026-11-08 08:35:44.478967+07	2026-11-08 08:35:44.478967+07
22	4	16	manual	confirmed	2026-11-08 08:35:46.687683+07	2026-11-08 08:35:46.687683+07
23	8	1	manual	confirmed	2026-11-09 08:37:52.404889+07	2026-11-09 08:37:52.404889+07
24	8	3	manual	confirmed	2026-11-09 08:37:52.404889+07	2026-11-09 08:37:52.404889+07
25	1	56	manual	confirmed	2026-11-10 13:29:41.698692+07	2026-11-10 13:29:41.698692+07
26	1	57	manual	confirmed	2026-11-10 13:32:40.169921+07	2026-11-10 13:32:40.169921+07
27	1	6	manual	confirmed	2026-11-13 08:40:35.378163+07	2026-11-13 08:40:35.378163+07
28	1	5	manual	confirmed	2026-11-13 08:40:35.540657+07	2026-11-13 08:40:35.540657+07
\.


--
-- TOC entry 5127 (class 0 OID 26570)
-- Dependencies: 222
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.users (id, name, email, password_hash, role, created_at, updated_at, is_active, avatar_url) FROM stdin;
3	HAIKAL MAGHSIN	haikal.maghsin0756@gmail.com	$2y$12$7fOjri/bRm.5nMine6Zaye9OumXTULJRwT1h7PIyNHDVEjC95hVdu	user	2026-10-23 14:57:21.961804+07	2026-10-23 14:57:21.961804+07	t	\N
1	Haikal Maghsin	haikal@gmail.com	$2y$12$8aBFkFQx6MZSkte/e4smGeefwfD2iCIPQsuhUm7ZpGsMN7lnV5anK	user	2026-10-23 01:42:30.837117+07	2026-11-25 08:28:53.353185+07	t	https://pub-09386e6302da4dc3a6b07bfe267949e6.r2.dev/avatars/user_1_1794795457.webp
6	HR Tech Indonesia	hr@skillbridge.test	$2y$12$jMNsCt2KHbyKW/T2HLZFUezPhLTlyH82KsfTXj3nLL3kBO1IAs5se	hr	2026-11-09 08:36:49.643645+07	2026-11-25 08:28:54.12776+07	t	\N
5	Admin Test	admin@skillbridge.test	$2y$12$ITAc1xCg8BnD/mzb../lhOar/0N76hhltdHtU37Qm7XGIPU0ZnKI2	admin	2026-11-09 08:36:49.284956+07	2026-11-25 08:28:54.932198+07	t	\N
7	HR No Company	hr.nocompany@skillbridge.test	$2y$12$y3qqM1aDqc8zAZClOvsIZeY5.yhB0BbUYH3vZlaUUE2lM9roTMLdm	hr	2026-11-09 08:37:29.642086+07	2026-11-09 08:37:52.026543+07	t	\N
9	Student Private	student.private@skillbridge.test	$2y$12$8aKuUt7a.1PTiNl/5csQleiKFJUJXAMYN6JjnB0A9TNFW..BRNKdC	user	2026-11-09 08:37:52.965579+07	2026-11-09 08:37:52.965579+07	t	\N
11	Student Inactive	student.inactive@skillbridge.test	$2y$12$fApNDoRn.aUYGs9irgS92eUbO0O7suWW0h8/iByxK7/g5QvxnqBMm	user	2026-11-09 08:37:54.008908+07	2026-11-09 08:37:54.008908+07	f	\N
4	rizky pratama	rizkypratama@gmail.com	$2y$12$wliBp288R0HV94d46Tb35uRJAazpXaRQQSAdcuzfaAJB7BzVOKDKG	user	2026-11-08 08:35:06.714828+07	2026-11-10 13:17:23.780922+07	f	\N
12	Levi	levi@gmail.com	$2y$12$wGzdxPcqjSKVxn.196SC5.75E2/POsOKxN4bGEyCpMqCQylmvz6ZW	user	2026-11-10 13:17:11.157396+07	2026-11-10 13:17:30.861387+07	f	\N
8	Student Public	student.public@skillbridge.test	$2y$12$U4JnlrrmZ1BH2tTi4JyU2ekJrX93t4lD1ct4704hADQGPUCpQWIo.	user	2026-11-09 08:37:29.960028+07	2026-11-10 13:17:37.946491+07	f	\N
10	Student New Empty	student.new@skillbridge.test	$2y$12$24DvfUxSmkllkwtFt1IqPO23H1nUt8jw5xIPtL/g6JcUCW50Xx1Cu	user	2026-11-09 08:37:53.461488+07	2026-11-16 08:35:42.489666+07	t	\N
\.


--
-- TOC entry 5170 (class 0 OID 0)
-- Dependencies: 236
-- Name: candidate_pipelines_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.candidate_pipelines_id_seq', 4, true);


--
-- TOC entry 5171 (class 0 OID 0)
-- Dependencies: 219
-- Name: career_skill_requirements_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.career_skill_requirements_id_seq', 69, true);


--
-- TOC entry 5172 (class 0 OID 0)
-- Dependencies: 215
-- Name: careers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.careers_id_seq', 13, true);


--
-- TOC entry 5173 (class 0 OID 0)
-- Dependencies: 230
-- Name: companies_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.companies_id_seq', 1, true);


--
-- TOC entry 5174 (class 0 OID 0)
-- Dependencies: 232
-- Name: company_recruiters_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.company_recruiters_id_seq', 2, true);


--
-- TOC entry 5175 (class 0 OID 0)
-- Dependencies: 224
-- Name: evidence_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.evidence_id_seq', 18, true);


--
-- TOC entry 5176 (class 0 OID 0)
-- Dependencies: 234
-- Name: job_vacancies_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.job_vacancies_id_seq', 5, true);


--
-- TOC entry 5177 (class 0 OID 0)
-- Dependencies: 244
-- Name: mail_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.mail_logs_id_seq', 14, true);


--
-- TOC entry 5178 (class 0 OID 0)
-- Dependencies: 242
-- Name: password_reset_tokens_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.password_reset_tokens_id_seq', 14, true);


--
-- TOC entry 5179 (class 0 OID 0)
-- Dependencies: 238
-- Name: recruiter_activity_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.recruiter_activity_logs_id_seq', 11, true);


--
-- TOC entry 5180 (class 0 OID 0)
-- Dependencies: 240
-- Name: schema_migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.schema_migrations_id_seq', 3, true);


--
-- TOC entry 5181 (class 0 OID 0)
-- Dependencies: 217
-- Name: skills_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.skills_id_seq', 58, true);


--
-- TOC entry 5182 (class 0 OID 0)
-- Dependencies: 226
-- Name: user_skills_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.user_skills_id_seq', 28, true);


--
-- TOC entry 5183 (class 0 OID 0)
-- Dependencies: 221
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.users_id_seq', 12, true);


--
-- TOC entry 4925 (class 2606 OID 26770)
-- Name: candidate_pipelines candidate_pipelines_company_id_user_id_vacancy_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.candidate_pipelines
    ADD CONSTRAINT candidate_pipelines_company_id_user_id_vacancy_id_key UNIQUE (company_id, user_id, vacancy_id);


--
-- TOC entry 4927 (class 2606 OID 26768)
-- Name: candidate_pipelines candidate_pipelines_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.candidate_pipelines
    ADD CONSTRAINT candidate_pipelines_pkey PRIMARY KEY (id);


--
-- TOC entry 4886 (class 2606 OID 26557)
-- Name: career_skill_requirements career_skill_requirements_career_id_skill_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.career_skill_requirements
    ADD CONSTRAINT career_skill_requirements_career_id_skill_id_key UNIQUE (career_id, skill_id);


--
-- TOC entry 4888 (class 2606 OID 26555)
-- Name: career_skill_requirements career_skill_requirements_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.career_skill_requirements
    ADD CONSTRAINT career_skill_requirements_pkey PRIMARY KEY (id);


--
-- TOC entry 4878 (class 2606 OID 26536)
-- Name: careers careers_name_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.careers
    ADD CONSTRAINT careers_name_key UNIQUE (name);


--
-- TOC entry 4880 (class 2606 OID 26534)
-- Name: careers careers_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.careers
    ADD CONSTRAINT careers_pkey PRIMARY KEY (id);


--
-- TOC entry 4912 (class 2606 OID 26697)
-- Name: companies companies_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.companies
    ADD CONSTRAINT companies_pkey PRIMARY KEY (id);


--
-- TOC entry 4914 (class 2606 OID 26711)
-- Name: company_recruiters company_recruiters_company_id_user_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.company_recruiters
    ADD CONSTRAINT company_recruiters_company_id_user_id_key UNIQUE (company_id, user_id);


--
-- TOC entry 4916 (class 2606 OID 26709)
-- Name: company_recruiters company_recruiters_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.company_recruiters
    ADD CONSTRAINT company_recruiters_pkey PRIMARY KEY (id);


--
-- TOC entry 4900 (class 2606 OID 26616)
-- Name: evidence evidence_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.evidence
    ADD CONSTRAINT evidence_pkey PRIMARY KEY (id);


--
-- TOC entry 4909 (class 2606 OID 26653)
-- Name: evidence_skills evidence_skills_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.evidence_skills
    ADD CONSTRAINT evidence_skills_pkey PRIMARY KEY (evidence_id, skill_id);


--
-- TOC entry 4923 (class 2606 OID 26737)
-- Name: job_vacancies job_vacancies_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.job_vacancies
    ADD CONSTRAINT job_vacancies_pkey PRIMARY KEY (id);


--
-- TOC entry 4947 (class 2606 OID 27078)
-- Name: mail_logs mail_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mail_logs
    ADD CONSTRAINT mail_logs_pkey PRIMARY KEY (id);


--
-- TOC entry 4943 (class 2606 OID 27059)
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (id);


--
-- TOC entry 4945 (class 2606 OID 27061)
-- Name: password_reset_tokens password_reset_tokens_token_hash_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_token_hash_key UNIQUE (token_hash);


--
-- TOC entry 4935 (class 2606 OID 26804)
-- Name: recruiter_activity_logs recruiter_activity_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.recruiter_activity_logs
    ADD CONSTRAINT recruiter_activity_logs_pkey PRIMARY KEY (id);


--
-- TOC entry 4937 (class 2606 OID 26841)
-- Name: schema_migrations schema_migrations_migration_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.schema_migrations
    ADD CONSTRAINT schema_migrations_migration_key UNIQUE (migration);


--
-- TOC entry 4939 (class 2606 OID 26839)
-- Name: schema_migrations schema_migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.schema_migrations
    ADD CONSTRAINT schema_migrations_pkey PRIMARY KEY (id);


--
-- TOC entry 4882 (class 2606 OID 26547)
-- Name: skills skills_name_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.skills
    ADD CONSTRAINT skills_name_key UNIQUE (name);


--
-- TOC entry 4884 (class 2606 OID 26545)
-- Name: skills skills_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.skills
    ADD CONSTRAINT skills_pkey PRIMARY KEY (id);


--
-- TOC entry 4896 (class 2606 OID 26591)
-- Name: student_profiles student_profiles_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.student_profiles
    ADD CONSTRAINT student_profiles_pkey PRIMARY KEY (user_id);


--
-- TOC entry 4898 (class 2606 OID 26593)
-- Name: student_profiles student_profiles_public_token_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.student_profiles
    ADD CONSTRAINT student_profiles_public_token_key UNIQUE (public_token);


--
-- TOC entry 4905 (class 2606 OID 26634)
-- Name: user_skills user_skills_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_skills
    ADD CONSTRAINT user_skills_pkey PRIMARY KEY (id);


--
-- TOC entry 4907 (class 2606 OID 26636)
-- Name: user_skills user_skills_user_id_skill_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_skills
    ADD CONSTRAINT user_skills_user_id_skill_id_key UNIQUE (user_id, skill_id);


--
-- TOC entry 4891 (class 2606 OID 26582)
-- Name: users users_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_key UNIQUE (email);


--
-- TOC entry 4893 (class 2606 OID 26580)
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- TOC entry 4928 (class 1259 OID 26791)
-- Name: idx_candidate_pipelines_company; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_candidate_pipelines_company ON public.candidate_pipelines USING btree (company_id);


--
-- TOC entry 4929 (class 1259 OID 26793)
-- Name: idx_candidate_pipelines_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_candidate_pipelines_status ON public.candidate_pipelines USING btree (status);


--
-- TOC entry 4930 (class 1259 OID 26792)
-- Name: idx_candidate_pipelines_user; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_candidate_pipelines_user ON public.candidate_pipelines USING btree (user_id);


--
-- TOC entry 4917 (class 1259 OID 26722)
-- Name: idx_company_recruiters_company; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_company_recruiters_company ON public.company_recruiters USING btree (company_id);


--
-- TOC entry 4918 (class 1259 OID 26723)
-- Name: idx_company_recruiters_user; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_company_recruiters_user ON public.company_recruiters USING btree (user_id);


--
-- TOC entry 4889 (class 1259 OID 26568)
-- Name: idx_csr_skill; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_csr_skill ON public.career_skill_requirements USING btree (skill_id);


--
-- TOC entry 4901 (class 1259 OID 26623)
-- Name: idx_evidence_metadata; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_evidence_metadata ON public.evidence USING gin (metadata);


--
-- TOC entry 4910 (class 1259 OID 26664)
-- Name: idx_evidence_skills_skill; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_evidence_skills_skill ON public.evidence_skills USING btree (skill_id);


--
-- TOC entry 4902 (class 1259 OID 26622)
-- Name: idx_evidence_user; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_evidence_user ON public.evidence USING btree (user_id);


--
-- TOC entry 4919 (class 1259 OID 26754)
-- Name: idx_job_vacancies_career; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_job_vacancies_career ON public.job_vacancies USING btree (career_id);


--
-- TOC entry 4920 (class 1259 OID 26753)
-- Name: idx_job_vacancies_company; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_job_vacancies_company ON public.job_vacancies USING btree (company_id);


--
-- TOC entry 4921 (class 1259 OID 26755)
-- Name: idx_job_vacancies_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_job_vacancies_status ON public.job_vacancies USING btree (status);


--
-- TOC entry 4940 (class 1259 OID 27067)
-- Name: idx_prt_token_hash; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prt_token_hash ON public.password_reset_tokens USING btree (token_hash);


--
-- TOC entry 4941 (class 1259 OID 27068)
-- Name: idx_prt_user_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prt_user_id ON public.password_reset_tokens USING btree (user_id);


--
-- TOC entry 4931 (class 1259 OID 26815)
-- Name: idx_recruiter_activity_logs_company; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_recruiter_activity_logs_company ON public.recruiter_activity_logs USING btree (company_id);


--
-- TOC entry 4932 (class 1259 OID 26817)
-- Name: idx_recruiter_activity_logs_created; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_recruiter_activity_logs_created ON public.recruiter_activity_logs USING btree (created_at DESC);


--
-- TOC entry 4933 (class 1259 OID 26816)
-- Name: idx_recruiter_activity_logs_user; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_recruiter_activity_logs_user ON public.recruiter_activity_logs USING btree (user_id);


--
-- TOC entry 4894 (class 1259 OID 26604)
-- Name: idx_student_profiles_target; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_student_profiles_target ON public.student_profiles USING btree (target_career_id);


--
-- TOC entry 4903 (class 1259 OID 26647)
-- Name: idx_user_skills_skill; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_user_skills_skill ON public.user_skills USING btree (skill_id);


--
-- TOC entry 4975 (class 2620 OID 26794)
-- Name: candidate_pipelines trg_candidate_pipelines_updated_at; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_candidate_pipelines_updated_at BEFORE UPDATE ON public.candidate_pipelines FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();


--
-- TOC entry 4972 (class 2620 OID 26698)
-- Name: companies trg_companies_updated_at; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_companies_updated_at BEFORE UPDATE ON public.companies FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();


--
-- TOC entry 4973 (class 2620 OID 26724)
-- Name: company_recruiters trg_company_recruiters_updated_at; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_company_recruiters_updated_at BEFORE UPDATE ON public.company_recruiters FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();


--
-- TOC entry 4974 (class 2620 OID 26756)
-- Name: job_vacancies trg_job_vacancies_updated_at; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_job_vacancies_updated_at BEFORE UPDATE ON public.job_vacancies FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();


--
-- TOC entry 4970 (class 2620 OID 26605)
-- Name: student_profiles trg_student_profiles_updated_at; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_student_profiles_updated_at BEFORE UPDATE ON public.student_profiles FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();


--
-- TOC entry 4971 (class 2620 OID 26648)
-- Name: user_skills trg_user_skills_updated_at; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_user_skills_updated_at BEFORE UPDATE ON public.user_skills FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();


--
-- TOC entry 4969 (class 2620 OID 26583)
-- Name: users trg_users_updated_at; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_users_updated_at BEFORE UPDATE ON public.users FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();


--
-- TOC entry 4962 (class 2606 OID 26771)
-- Name: candidate_pipelines candidate_pipelines_company_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.candidate_pipelines
    ADD CONSTRAINT candidate_pipelines_company_id_fkey FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- TOC entry 4963 (class 2606 OID 26786)
-- Name: candidate_pipelines candidate_pipelines_updated_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.candidate_pipelines
    ADD CONSTRAINT candidate_pipelines_updated_by_fkey FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- TOC entry 4964 (class 2606 OID 26781)
-- Name: candidate_pipelines candidate_pipelines_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.candidate_pipelines
    ADD CONSTRAINT candidate_pipelines_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- TOC entry 4965 (class 2606 OID 26776)
-- Name: candidate_pipelines candidate_pipelines_vacancy_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.candidate_pipelines
    ADD CONSTRAINT candidate_pipelines_vacancy_id_fkey FOREIGN KEY (vacancy_id) REFERENCES public.job_vacancies(id) ON DELETE SET NULL;


--
-- TOC entry 4948 (class 2606 OID 26558)
-- Name: career_skill_requirements career_skill_requirements_career_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.career_skill_requirements
    ADD CONSTRAINT career_skill_requirements_career_id_fkey FOREIGN KEY (career_id) REFERENCES public.careers(id) ON DELETE CASCADE;


--
-- TOC entry 4949 (class 2606 OID 26563)
-- Name: career_skill_requirements career_skill_requirements_skill_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.career_skill_requirements
    ADD CONSTRAINT career_skill_requirements_skill_id_fkey FOREIGN KEY (skill_id) REFERENCES public.skills(id) ON DELETE CASCADE;


--
-- TOC entry 4957 (class 2606 OID 26712)
-- Name: company_recruiters company_recruiters_company_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.company_recruiters
    ADD CONSTRAINT company_recruiters_company_id_fkey FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- TOC entry 4958 (class 2606 OID 26717)
-- Name: company_recruiters company_recruiters_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.company_recruiters
    ADD CONSTRAINT company_recruiters_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- TOC entry 4955 (class 2606 OID 26654)
-- Name: evidence_skills evidence_skills_evidence_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.evidence_skills
    ADD CONSTRAINT evidence_skills_evidence_id_fkey FOREIGN KEY (evidence_id) REFERENCES public.evidence(id) ON DELETE CASCADE;


--
-- TOC entry 4956 (class 2606 OID 26659)
-- Name: evidence_skills evidence_skills_skill_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.evidence_skills
    ADD CONSTRAINT evidence_skills_skill_id_fkey FOREIGN KEY (skill_id) REFERENCES public.skills(id) ON DELETE CASCADE;


--
-- TOC entry 4952 (class 2606 OID 26617)
-- Name: evidence evidence_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.evidence
    ADD CONSTRAINT evidence_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- TOC entry 4959 (class 2606 OID 26743)
-- Name: job_vacancies job_vacancies_career_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.job_vacancies
    ADD CONSTRAINT job_vacancies_career_id_fkey FOREIGN KEY (career_id) REFERENCES public.careers(id) ON DELETE SET NULL;


--
-- TOC entry 4960 (class 2606 OID 26738)
-- Name: job_vacancies job_vacancies_company_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.job_vacancies
    ADD CONSTRAINT job_vacancies_company_id_fkey FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- TOC entry 4961 (class 2606 OID 26748)
-- Name: job_vacancies job_vacancies_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.job_vacancies
    ADD CONSTRAINT job_vacancies_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- TOC entry 4968 (class 2606 OID 27062)
-- Name: password_reset_tokens password_reset_tokens_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- TOC entry 4966 (class 2606 OID 26805)
-- Name: recruiter_activity_logs recruiter_activity_logs_company_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.recruiter_activity_logs
    ADD CONSTRAINT recruiter_activity_logs_company_id_fkey FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- TOC entry 4967 (class 2606 OID 26810)
-- Name: recruiter_activity_logs recruiter_activity_logs_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.recruiter_activity_logs
    ADD CONSTRAINT recruiter_activity_logs_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- TOC entry 4950 (class 2606 OID 26599)
-- Name: student_profiles student_profiles_target_career_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.student_profiles
    ADD CONSTRAINT student_profiles_target_career_id_fkey FOREIGN KEY (target_career_id) REFERENCES public.careers(id) ON DELETE SET NULL;


--
-- TOC entry 4951 (class 2606 OID 26594)
-- Name: student_profiles student_profiles_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.student_profiles
    ADD CONSTRAINT student_profiles_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- TOC entry 4953 (class 2606 OID 26642)
-- Name: user_skills user_skills_skill_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_skills
    ADD CONSTRAINT user_skills_skill_id_fkey FOREIGN KEY (skill_id) REFERENCES public.skills(id) ON DELETE CASCADE;


--
-- TOC entry 4954 (class 2606 OID 26637)
-- Name: user_skills user_skills_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_skills
    ADD CONSTRAINT user_skills_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


-- Completed on 2026-11-25 14:32:16

--
-- PostgreSQL database dump complete
--

