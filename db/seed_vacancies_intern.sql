-- Seed Data: Lowongan Kerja Intern & Fresh Graduate (4 per Career Role)
-- Fokus: Mahasiswa & Fresh Graduate dengan variasi skill requirements

-- ============ BACKEND DEVELOPER (4 Lowongan) ============

-- 1. Backend Developer Intern (3 skill dasar - Tier 1 untuk pemula)
INSERT INTO job_vacancies (company_id, career_id, title, positions, status, description, requirements, created_by)
VALUES (
  1, -- Sesuaikan dengan company_id yang ada
  1, -- Backend Developer
  'Backend Developer Intern',
  2,
  'active',
  'Bergabunglah dengan tim engineering kami sebagai Backend Developer Intern. Kamu akan belajar membangun REST API, mengelola database, dan bekerja dalam tim agile. Program magang 6 bulan dengan kesempatan konversi menjadi full-time employee.',
  'Requirements: Familiar dengan Git version control, dasar-dasar PHP/Node.js, dan SQL database. Fresh graduate atau mahasiswa semester akhir dipersilakan melamar.',
  NULL
);

-- 2. Junior Backend Engineer - Fresh Graduate (5 skill)
INSERT INTO job_vacancies (company_id, career_id, title, positions, status, description, requirements, created_by)
VALUES (
  1,
  1,
  'Junior Backend Engineer (Fresh Graduate)',
  3,
  'active',
  'Posisi Junior Backend Engineer untuk fresh graduate yang passionate dalam server-side development. Kamu akan mengembangkan microservices, bekerja dengan cloud infrastructure (AWS/GCP), dan berkolaborasi dengan tim product.',
  'Requirements: Pengalaman dengan Laravel/Node.js, PostgreSQL/MySQL, REST APIs, Git, dan Docker. Minimal 1 project portfolio atau pengalaman magang.',
  NULL
);

-- 3. API Developer Intern (4 skill)
INSERT INTO job_vacancies (company_id, career_id, title, positions, status, description, requirements, created_by)
VALUES (
  1,
  1,
  'API & Database Developer Intern',
  2,
  'active',
  'Program magang fokus pada pengembangan REST API dan optimasi database. Kamu akan belajar API design best practices, database indexing, caching strategies, dan monitoring. Mentorship 1-on-1 dengan Senior Engineer.',
  'Requirements: Dasar PHP/Python/Node.js, SQL (PostgreSQL/MySQL), pemahaman REST API, dan Git. Pengalaman project pribadi menjadi nilai plus.',
  NULL
);

-- 4. Backend Trainee - Entry Level (3 skill)
INSERT INTO job_vacancies (company_id, career_id, title, positions, status, description, requirements, created_by)
VALUES (
  1,
  1,
  'Backend Development Trainee',
  4,
  'active',
  'Program pelatihan 3 bulan untuk fresh graduate dengan minat kuat di backend development. Training meliputi: PHP/Laravel fundamentals, database design, API development, dan software testing. Stipend provided.',
  'Requirements: Pemahaman dasar programming (PHP/Python/Java), SQL database, dan version control Git. Tidak perlu pengalaman kerja formal - kami lebih menilai passion dan kemampuan belajar.',
  NULL
);

-- ============ FRONTEND DEVELOPER (4 Lowongan) ============

-- 1. Frontend Developer Intern (3 skill: HTML, CSS, JavaScript)
INSERT INTO job_vacancies (company_id, career_id, title, positions, status, description, requirements, created_by)
VALUES (
  1,
  2, -- Frontend Developer
  'Frontend Developer Intern',
  2,
  'active',
  'Kesempatan magang sebagai Frontend Developer di tim product kami. Kamu akan mengimplementasikan UI design menjadi responsive web pages, belajar modern JavaScript framework, dan berkolaborasi dengan UX Designer.',
  'Requirements: HTML5, CSS3, JavaScript (ES6+). Familiar dengan Figma/design tools dan responsive design principles. Portfolio project atau assignment course dipersilakan.',
  NULL
);

-- 2. Junior Frontend Engineer (5 skill)
INSERT INTO job_vacancies (company_id, career_id, title, positions, status, description, requirements, created_by)
VALUES (
  1,
  2,
  'Junior Frontend Engineer - React',
  3,
  'active',
  'Posisi Junior Frontend Engineer untuk membangun modern web applications dengan React. Kamu akan bekerja dengan component-based architecture, state management, dan REST API integration.',
  'Requirements: HTML, CSS, JavaScript, React, dan Git. Pemahaman Responsive Design dan basic UI/UX principles. Minimal 1 project React portfolio.',
  NULL
);

-- 3. UI Developer Intern (4 skill)
INSERT INTO job_vacancies (company_id, career_id, title, positions, status, description, requirements, created_by)
VALUES (
  1,
  2,
  'UI Developer Intern (Design-to-Code)',
  2,
  'active',
  'Program magang fokus pada implementasi UI design ke production-ready code. Kamu akan belajar CSS architecture, component library, accessibility standards, dan collaboration dengan designers.',
  'Requirements: HTML, CSS (Flexbox/Grid), JavaScript, dan Figma/Adobe XD. Perhatian tinggi terhadap design detail dan pixel-perfect implementation.',
  NULL
);

-- 4. Frontend Trainee (3 skill)
INSERT INTO job_vacancies (company_id, career_id, title, positions, status, description, requirements, created_by)
VALUES (
  1,
  2,
  'Frontend Development Trainee',
  3,
  'active',
  'Program training 3 bulan untuk fresh graduate yang ingin memulai karier sebagai Frontend Developer. Curriculum: HTML/CSS fundamentals, JavaScript, framework introduction (React/Vue), dan real project experience.',
  'Requirements: Dasar HTML, CSS, JavaScript. Tidak perlu framework experience - kami akan mengajarkan dari nol. Yang penting: passion untuk web development dan UI/UX.',
  NULL
);

-- ============ UI/UX DESIGNER (4 Lowongan) ============

-- 1. UI/UX Design Intern (4 skill)
INSERT INTO job_vacancies (company_id, career_id, title, positions, status, description, requirements, created_by)
VALUES (
  1,
  4, -- UI/UX Designer
  'UI/UX Design Intern',
  2,
  'active',
  'Bergabung dengan Product Design team sebagai UI/UX Design Intern. Kamu akan terlibat dalam user research, wireframing, prototyping, dan usability testing. Mentorship langsung dari Senior Designer.',
  'Requirements: Figma, User Research, Prototyping, dan Usability Testing. Portfolio design case study (personal project, redesign concept, atau course assignment) wajib disertakan.',
  NULL
);

-- 2. Junior Product Designer (6 skill)
INSERT INTO job_vacancies (company_id, career_id, title, positions, status, description, requirements, created_by)
VALUES (
  1,
  4,
  'Junior Product Designer (Fresh Graduate)',
  2,
  'active',
  'Posisi Junior Product Designer untuk fresh graduate passionate tentang user-centered design. End-to-end ownership: research, ideation, design, prototyping, hingga collaboration dengan engineering untuk implementation.',
  'Requirements: Figma (advanced), User Research, Wireframing, Prototyping, Usability Testing, dan Design Systems. Portfolio dengan minimal 2 complete case studies.',
  NULL
);

-- 3. Visual & Interaction Designer Intern (4 skill)
INSERT INTO job_vacancies (company_id, career_id, title, positions, status, description, requirements, created_by)
VALUES (
  1,
  4,
  'Visual & Interaction Design Intern',
  2,
  'active',
  'Magang fokus pada visual design dan micro-interactions. Kamu akan belajar visual hierarchy, typography, color theory, motion design, dan implementasi design tokens ke frontend code.',
  'Requirements: Figma, Prototyping (dengan animation/micro-interactions), Wireframing, dan basic HTML/CSS (nilai plus). Portfolio visual design wajib.',
  NULL
);

-- 4. UX Research Trainee (3 skill)
INSERT INTO job_vacancies (company_id, career_id, title, positions, status, description, requirements, created_by)
VALUES (
  1,
  4,
  'UX Research & Testing Trainee',
  3,
  'active',
  'Program training 3 bulan fokus UX Research methodology. Kamu akan belajar: user interviews, survey design, usability testing, data analysis, dan presenting insights ke stakeholders.',
  'Requirements: User Research, Usability Testing, dan Figma (untuk documentation). Background psychology, HCI, atau design welcome. Tidak perlu pengalaman formal.',
  NULL
);

-- ============ DATA ANALYST (4 Lowongan) ============

-- 1. Data Analyst Intern (4 skill)
INSERT INTO job_vacancies (company_id, career_id, title, positions, status, description, requirements, created_by)
VALUES (
  1,
  3, -- Data Analyst
  'Data Analyst Intern',
  2,
  'active',
  'Kesempatan magang sebagai Data Analyst di Business Intelligence team. Kamu akan menganalisis business metrics, membuat dashboard, dan menyajikan insights ke management. Hands-on experience dengan real production data.',
  'Requirements: SQL, Excel (pivot tables, vlookup, charts), Data Visualization, dan Python (basic). Kemampuan storytelling dengan data menjadi nilai plus.',
  NULL
);

-- 2. Junior Data Analyst (5 skill)
INSERT INTO job_vacancies (company_id, career_id, title, positions, status, description, requirements, created_by)
VALUES (
  1,
  3,
  'Junior Data Analyst - Business Intelligence',
  3,
  'active',
  'Posisi Junior Data Analyst untuk fresh graduate dengan strong analytical skills. Kamu akan bekerja dengan large datasets, membangun automated reports, dan berkolaborasi dengan product & marketing teams untuk data-driven decisions.',
  'Requirements: SQL (intermediate), Python (Pandas, NumPy), Data Visualization (Tableau/Power BI), Excel, dan Statistics. Portfolio analysis project atau capstone wajib.',
  NULL
);

-- 3. BI Developer Intern (4 skill)
INSERT INTO job_vacancies (company_id, career_id, title, positions, status, description, requirements, created_by)
VALUES (
  1,
  3,
  'Business Intelligence Developer Intern',
  2,
  'active',
  'Program magang fokus pada dashboard development dan data pipeline. Kamu akan belajar ETL processes, data warehousing concepts, dan visualization best practices menggunakan modern BI tools.',
  'Requirements: SQL, Data Visualization (Tableau/Looker/Power BI), Excel, dan Python basics. Pengalaman dengan database management (PostgreSQL/MySQL) menjadi nilai plus.',
  NULL
);

-- 4. Data Analytics Trainee (3 skill)
INSERT INTO job_vacancies (company_id, career_id, title, positions, status, description, requirements, created_by)
VALUES (
  1,
  3,
  'Data Analytics Trainee Program',
  4,
  'active',
  'Program training 3 bulan untuk fresh graduate yang ingin memulai karier di data analytics. Curriculum: SQL fundamentals, Excel advanced, data visualization, basic statistics, dan storytelling dengan data.',
  'Requirements: SQL (basic queries), Excel (functions & charts), dan Data Visualization tools (intro level). Background matematika, statistika, atau business welcome. Passion untuk data adalah must.',
  NULL
);
