--
-- EZP-30797: As an administrator, I want to configure a password expiration for users
--

ALTER TABLE ezuser ADD COLUMN password_updated_at integer;

--
-- EZP-30797: end.
--
