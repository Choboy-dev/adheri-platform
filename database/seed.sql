-- Seed baseline ADHERI content
SET NAMES utf8mb4;

INSERT INTO roles (name, label) VALUES
  ('super_admin', 'Super Admin'),
  ('editor', 'Editor'),
  ('viewer', 'Viewer')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- No usable password is seeded. After importing, run:
--   php scripts/set-admin-password.php admin@advocatesforhealthandrights.org
INSERT INTO admin_users (name, email, password_hash, role, is_active)
VALUES (
  'ADHERI Admin',
  'admin@advocatesforhealthandrights.org',
  '!unset',
  'super_admin',
  1
)
ON DUPLICATE KEY UPDATE email = email;

INSERT INTO site_settings (setting_key, setting_value) VALUES
  ('org_name', 'Advocates for Health & Rights of Women and Youths Initiative'),
  ('org_short_name', 'ADHERI'),
  ('contact_email_primary', 'contactus@advocatesforhealthandrights.org'),
  ('contact_email_info', 'info@advocatesforhealthandrights.org'),
  ('phone_primary', '(+234) 0818 422 3082'),
  ('phone_secondary', '(+234) 0906 628 3022'),
  ('address', 'Asap Chops Building, Jakande First Gate, Lekki, Lagos (After iFitness & FCMB)'),
  ('map_url', 'https://maps.app.goo.gl/1GGTkjmDNTQRbAeE8'),
  ('social_facebook', 'https://www.facebook.com/profile.php?id=100086568457221'),
  ('social_twitter', 'https://twitter.com/Advocatenearyou'),
  ('social_instagram', 'https://www.instagram.com/advocatesnearyou/'),
  ('social_linkedin', 'https://www.linkedin.com/company/advocates-for-health-and-rights-of-women-and-youth-initiative/'),
  ('donation_min_amount', '1000'),
  ('donation_currency', 'NGN'),
  ('policy_cta_label', 'Download our GBV & Arts Policy'),
  ('home_mission', 'We are on a mission to end the injustice and violence that plague the lives of vulnerable minority groups, youth, and marginalized women in Nigeria.'),
  ('home_mission_support', 'Join us in making the world a better place for all.'),
  ('about_vision', 'A world where everyone''s voice is heard.'),
  ('seo_default_title', 'Advocates for Health & Rights of Women and Youths Initiative'),
  ('seo_default_description', 'Women and youth-led organisation advancing health, rights, and dignity across Nigeria.')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

INSERT INTO pages (slug, title, seo_title, seo_description) VALUES
  ('home', 'Home', 'ADHERI — Advocates for Health & Rights of Women and Youths Initiative', 'Women and youth-led advocacy for health, rights, and dignity in Nigeria.'),
  ('about', 'About', 'About ADHERI', 'Vision, mission, values, Board of Trustees, and Youth Advisory Board.'),
  ('programs', 'Programs', 'Our Programs — ADHERI', 'Advocacy, counselling, empowerment, climate, media, rehab support, and artivism.'),
  ('gallery', 'Gallery', 'Gallery — ADHERI', 'Albums from ADHERI programs, artivism, and community work.'),
  ('blog', 'Blog', 'Blog — ADHERI', 'Articles on rights, GBV, climate, and community change.'),
  ('contact', 'Contact', 'Contact ADHERI', 'Reach ADHERI in Lekki, Lagos.'),
  ('volunteer', 'Volunteer', 'Volunteer with ADHERI', 'Offer your skills to advance health and rights.'),
  ('donate', 'Donate', 'Donate to ADHERI', 'Support ADHERI programs.')
ON DUPLICATE KEY UPDATE title = VALUES(title);

INSERT INTO page_sections (page_id, section_key, title, body, sort_order) VALUES
  ((SELECT id FROM pages WHERE slug = 'home'), 'hero', 'Mission', 'We are on a mission to end the injustice and violence that plague the lives of vulnerable minority groups, youth, and marginalized women in Nigeria.', 1),
  ((SELECT id FROM pages WHERE slug = 'home'), 'about_teaser', 'About us', 'We are a women and youth-led organization operating by feminist leadership principles, alleviating the issues of women and youth who are vulnerable minority persons in our society, with a major focus on advocacy to end stigma, human rights violations, gender-based violence, substance misuse, and climate change effects on women and youths, which are SDGs 1, 3, 5, 10, and 13.', 2),
  ((SELECT id FROM pages WHERE slug = 'about'), 'story', 'Who we are', 'Through media, storytelling, art and culture inclusion, community programming, and stakeholder engagement we have consistently impacted our communities, with life-transforming conversations and programs that drive change.', 1),
  ((SELECT id FROM pages WHERE slug = 'programs'), 'intro', 'Our Programs', 'We believe that everyone deserves dignity, respect, and opportunity, regardless of their gender, race, ethnicity, religion, health, and status. That’s why we are working hard to achieve SDGs 1, 3, 5, 10, and 13.', 1),
  ((SELECT id FROM pages WHERE slug = 'volunteer'), 'intro', 'Volunteer with us', 'Join ADHERI in advocacy, counselling support pathways, media, artivism, events, and operations. Tell us how you want to help — our team will follow up.', 1),
  ((SELECT id FROM pages WHERE slug = 'donate'), 'intro', 'Support the work', 'Your gift fuels advocacy, counselling, empowerment, rehab referral, media, and artivism for women and youth across Nigeria.', 1)
ON DUPLICATE KEY UPDATE body = VALUES(body);

INSERT INTO programs (slug, title, summary, body, sort_order, featured_on_home, is_published) VALUES
  ('advocacy', 'Advocacy', 'Advocating for the rights and well-being of vulnerable and minority groups.', 'We address the dangers of stigma, gender-based violence, drug misuse, and poverty that have eroded the fabric of our society. We educate, empower, and transform key populations through evidence-based intervention programs like Women''s Rights in Human Rights advocacy events, Running on Heels, and Human Rights and Arts.', 1, 1, 1),
  ('counselling', 'Counselling', 'Hybrid and tele-counselling for substance misuse, sexual abuse, and GBV.', 'Due to the pandemic, there has been an obvious rise in the need for counselling and other psychosocial support services. Our team of experienced and qualified counsellors, therapists, and mental health doctors provide therapeutic tele-counselling services across our thematic areas covering substance misuse, sexual abuse, and other forms of gender-based violence (GBV). We adopt the hybrid style of counseling which can be tele-counseling or a one-on-one session.', 2, 1, 1),
  ('empowerment', 'Empowerment', 'Skills, digital marketing, coding, startup kits, and internships.', 'We empower minority and vulnerable women and youth in our communities by offering soft skills, digital marketing, coding training, skills acquisition, startup kits and internship opportunities. Through our partnership with companies, individuals, and corporate organizations, we are able to run our empowerment program.', 3, 1, 1),
  ('climate-change', 'Climate Change', 'Support for women and girls facing climate-linked GBV.', 'We provide succour to women and girls who suffer various forms of gender-based violence due to climate actions across the country. We offer psychosocial support, welfare, therapy, training, and empowerment to women and youths, helping them get their lives back on track.', 4, 0, 1),
  ('media', 'Media', 'Media cohorts and storytelling for advocacy.', 'We understand the importance of media in advocacy, hence we are big on media engagement and programming for media. Our media cohort program offers media personnel access to use their platform to advocate for GBV and other societal ills. Moreover, we leverage media as a tool for advocacy using storytelling to drive change within our communities.', 5, 0, 1),
  ('rehab-support', 'Rehab Support', 'Referrals to rehab centres and reintegration pathways.', 'We support people who need rehabilitation services by offering referral support to the best rehab centres across Nigeria. Our bespoke support gives our clients the opportunity to access the best and most affordable healthcare service across Nigeria. Furthermore, we help our clients reintegrate into society by giving them opportunities for a fresh start and job volunteering.', 6, 1, 1),
  ('artivism', 'Artivism (Arts & Activism)', 'Safe spaces, Human Rights and Arts, and artist training.', 'Our program is the fusion of art and activism. Through Creating Safe Spaces Through Arts, Human Rights and Arts, and Training for Artists, we empower artists and activists to amplify voices for the health and rights of women and youth — focusing on gender equality, reproductive health, and youth empowerment.', 7, 0, 1)
ON DUPLICATE KEY UPDATE summary = VALUES(summary);

INSERT INTO sdg_badges (code, label, sort_order, is_published) VALUES
  ('1', 'SDG 1 — No Poverty', 1, 1),
  ('3', 'SDG 3 — Good Health', 2, 1),
  ('5', 'SDG 5 — Gender Equality', 3, 1),
  ('10', 'SDG 10 — Reduced Inequalities', 4, 1),
  ('13', 'SDG 13 — Climate Action', 5, 1)
ON DUPLICATE KEY UPDATE label = VALUES(label);

DELETE FROM testimonials;

INSERT INTO testimonials (quote, person_name, location, photo_path, sort_order, is_published) VALUES
  ('I was part of Human Rights and Arts training organized by ADHERI. The training was very helpful and I am currently using the skills to better my life and family.', 'Iniobong Essien', 'Uyo, Akwa Ibom', 'img/people/2.png', 1, 1),
  ('I got referral services from ADHERI to rehab in Lagos and they followed up throughout the process. We still communicate eight months post-rehab. Thank you.', 'Ama Okoro', 'Lagos, Nigeria', 'img/people/3.png', 2, 1),
  ('ADHERI’s advocacy on TV gave me the courage to seek help for my sexual abuse situation. Today I am free from my abuser.', 'Cyril Madueke', 'Enugu, Nigeria', 'img/people/4.png', 3, 1);

INSERT INTO people (name, person_group, role_title, bio, sort_order, is_published) VALUES
  ('Emmanuel Etim', 'board', 'Trustee', 'Emmanuel Etim has 13 years of cumulative multi-sectorial experience driving impact through advocacy, GBV-related issues, mental health, media, and training. He is passionate to see the lives of young people experience a positive transformation through skills, opportunities, and life enhancement.', 1, 1),
  ('Nnenna Okoli', 'board', 'Trustee', 'Nnenna Okoli is a purpose driven international development scholar passionate about addressing complex global challenges, with keen interest in poverty, social protection, gender equality and empowerment.', 2, 1),
  ('Melody Otutuloro', 'board', 'Trustee', 'Melody Otutuloro is an Industrial Chemist with an eye for Interior Design, Architecture, and all things Aesthetic. She is very passionate about women''s development and empowerment.', 3, 1),
  ('Emmanuel Gabari', 'board', 'Trustee', 'Ambassador Emmanuel Gabari is an accomplished broadcaster, United Nations Youth Parliament Ambassador, and very passionate about the general development of youth across the globe. His weekly radio program spotlights the struggles and opportunities for youth and points them to global developmental opportunities.', 4, 1),
  ('Umanta Ude Umanta', 'board', 'Trustee', 'Umanta Ude Umanta is a notary public and a seasoned lawyer with over 20 years of experience in the legal sector. He obtained his Bachelor of Laws degree from the University of Ibadan, Nigeria and has since worked in various high-level legal positions in both public and private sectors.', 5, 1),
  ('Omoye Oriaghan', 'youth_advisory', 'Youth Advisory Board', NULL, 1, 1),
  ('Emerald Emmanuel', 'youth_advisory', 'Youth Advisory Board', NULL, 2, 1),
  ('Oluwadolapo Ojo', 'youth_advisory', 'Youth Advisory Board', NULL, 3, 1),
  ('Victoria Oletu', 'youth_advisory', 'Youth Advisory Board', NULL, 4, 1),
  ('Charles Egbuokporo', 'youth_advisory', 'Youth Advisory Board', NULL, 5, 1);

INSERT INTO categories (slug, name) VALUES ('articles', 'Articles')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO posts (slug, title, excerpt, body, category_id, status, published_at) VALUES
  (
    'advocating-for-change-our-mission-impact-and-how-you-can-get-involved',
    'Advocating for Change: Our Mission, Impact, and How You Can Get Involved',
    'At Advocates for Health & Rights of Women and Youths Initiative, our mission is clear: to empower women and youth.',
    '<p>At Advocates for Health &amp; Rights of Women and Youths Initiative, our mission is clear: to empower women and youth and amplify their voices through feminist, evidence-based, community-centred work.</p><p>Through advocacy, counselling, empowerment, rehab support, media, and artivism, we invite partners, donors, and volunteers to build a Nigeria where everyone''s voice is heard.</p>',
    (SELECT id FROM categories WHERE slug = 'articles'),
    'published',
    '2024-09-03 11:19:59'
  ),
  (
    'men-and-sexual-abuse',
    'Men and Sexual Abuse',
    'Sexual abuse on men is a serious and often hidden problem that affects many men and boys.',
    '<p>Sexual abuse of men and boys is a serious and often hidden problem. It can cause lasting physical, emotional, and social harm. Silence and stigma keep survivors from seeking help.</p><p>ADHERI works to end stigma, expand counselling pathways, and affirm that all survivors deserve safety, care, and justice.</p>',
    (SELECT id FROM categories WHERE slug = 'articles'),
    'published',
    '2024-02-09 12:59:32'
  ),
  (
    'how-climate-change-fuels-gender-based-violence-and-what-we-can-do-about-it',
    'How climate change fuels gender-based violence — and what we can do about it',
    'Climate change is not only a threat to our planet, but also to our people — especially women and youth.',
    '<p>Climate change intensifies risks for women and youth, including forms of gender-based violence linked to displacement, scarcity, and insecurity.</p><p>ADHERI responds with psychosocial support, welfare, therapy, training, and empowerment for those affected by climate-linked GBV.</p>',
    (SELECT id FROM categories WHERE slug = 'articles'),
    'published',
    '2024-02-07 01:37:04'
  ),
  (
    'ending-the-bystander-syndrome-could-end-gbv',
    'Ending The Bystander Syndrome Could End GBV',
    'Have you ever witnessed someone in trouble and felt like you should do something, but ended up doing nothing?',
    '<p>Bystander silence sustains gender-based violence. When communities learn to intervene safely — through awareness, media, and artivism — survivors are less alone and perpetrators less protected by silence.</p><p>ADHERI invites everyone to move from watching to acting with care, courage, and accountability.</p>',
    (SELECT id FROM categories WHERE slug = 'articles'),
    'published',
    '2024-02-07 01:35:03'
  )
ON DUPLICATE KEY UPDATE title = VALUES(title);
