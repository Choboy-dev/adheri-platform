-- Wipe development / placeholder CMS rows before production export.
-- Keeps: roles, admin_users, site_settings, pages, page_sections, programs, sdg_badges,
--         real testimonials (Iniobong, Ama, Cyril), people, categories, posts.
-- Clears: fabricated testimonials, empty albums/media links, inquiries, donations, logs.

SET NAMES utf8mb4;

DELETE FROM testimonials
WHERE person_name IN (
  'Chiamaka Nwosu',
  'Tunde Adebayo',
  'Halima Bello',
  'Blessing Eze',
  'Fatima Yusuf'
);

DELETE FROM album_media;
DELETE FROM albums;
DELETE FROM announcements;
DELETE FROM inquiries;
DELETE FROM donations;
DELETE FROM notification_logs;
DELETE FROM media_assets
WHERE url_path LIKE 'uploads/%';
